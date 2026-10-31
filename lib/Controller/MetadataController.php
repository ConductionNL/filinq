<?php

/**
 * Metadata Controller
 *
 * Controller for document metadata operations.
 * Provides an endpoint for triggering metadata enrichment on document objects.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Kernel\Contracts\InboundClassificationServiceInterface;
use OCA\Filinq\Kernel\Contracts\MetadataServiceInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Controller for metadata enrichment operations
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class MetadataController extends Controller {
	use AnswersNotAuthenticated;
	/**
	 * Constructor for MetadataController
	 *
	 * @param string $appName The application name
	 * @param IRequest $request The request object
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param MetadataServiceInterface $metadataService Service for metadata operations
	 * @param IL10N $l10n The localization service
	 * @param IUserSession $userSession User session for authentication
	 * @param InboundClassificationServiceInterface $classification Suggests a type, correspondent and dossier
	 * @param IRootFolder $rootFolder Resolves the caller's own files
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly LoggerInterface $logger,
		private readonly MetadataServiceInterface $metadataService,
		private readonly IL10N $l10n,
		private readonly IUserSession $userSession,
		private readonly InboundClassificationServiceInterface $classification,
		private readonly IRootFolder $rootFolder,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Trigger metadata enrichment for a document object
	 *
	 * Accepts object data (or objectId + register + schema to look it up),
	 * runs metadata enrichment, and saves the results back to OpenRegister.
	 *
	 * @return JSONResponse JSON response with enrichment results
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 *
	 * @no-admin-idor-exempt the object is read and saved through OpenRegister with RBAC and multitenancy
	 * on (MetadataService::persistEnrichedMetadata()), so the write needs the caller's own update right on
	 * that schema; classification runs only for a file in the caller's user folder.
	 */
	public function enrich(): JSONResponse {
		try {
			if ($this->userSession->getUser() === null) {
				return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
			}

			$data = $this->request->getParams();

			$validationError = $this->validateEnrichParams(data: $data);
			if ($validationError !== null) {
				return $validationError;
			}

			return $this->enrichValidated(data: $data);
		} catch (Exception $e) {
			return $this->enrichFailed(exception: $e);
		}

	}//end enrich()

	/**
	 * Enrich and save a validated request's object, and classify its file.
	 *
	 * @param array<string, mixed> $data The request: objectId, register, schema, objectData.
	 *
	 * @return JSONResponse The enrichment result.
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	private function enrichValidated(array $data): JSONResponse {
		// Get object data for enrichment.
		$objectData = $data['objectData'] ?? [];

		// Run metadata enhancement.
		$metadata = $this->metadataService->enhanceMetadata($objectData);

		// The same classification hook as the event path; suggestions only.
		$classification = $this->classifyReachable(objectData: $objectData, data: $data);

		if (empty($metadata) === true) {
			return new JSONResponse(
				[
					'success' => true,
					'message' => $this->l10n->t('No metadata enrichment needed'),
					'classification' => $classification,
				]
			);
		}

		// Save enriched metadata back to OpenRegister.
		$result = $this->metadataService->saveEnrichedMetadata(
			$data['objectId'],
			$data['register'],
			$data['schema'],
			$metadata
		);

		return new JSONResponse(
			[
				'success' => true,
				'enrichedFields' => array_keys($metadata),
				'object' => $result,
				'classification' => $classification,
			]
		);

	}//end enrichValidated()

	/**
	 * Log a failed enrichment and answer 500 with its message.
	 *
	 * @param Exception $exception The failure.
	 *
	 * @return JSONResponse The 500 response.
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	private function enrichFailed(Exception $exception): JSONResponse {
		$this->logger->error(
			'Failed to enrich metadata: ' . $exception->getMessage(),
			[
				'exception' => $exception,
			]
		);
		return new JSONResponse(
			['error' => $this->l10n->t('Failed to enrich metadata: %s', [$exception->getMessage()])],
			500
		);

	}//end enrichFailed()

	/**
	 * Offer the document to classification when the caller may update its file.
	 *
	 * The body's objectData is the caller's word, so the file is resolved in
	 * the caller's own folder first and must carry update permission: nobody
	 * makes or replaces a suggestion on a file they cannot change.
	 *
	 * @param array<string, mixed> $objectData The object's fields.
	 * @param array<string, mixed> $data The request: objectId, register, schema.
	 *
	 * @return string `suggested`, `skipped` or `failed`.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-3
	 */
	private function classifyReachable(array $objectData, array $data): string {
		$fileId = (int)($objectData['file'] ?? ($objectData['fileId'] ?? 0));
		try {
			$uid = (string)$this->userSession->getUser()?->getUID();
			// The suggestion is saved with RBAC off, so only a caller who may
			// change the file may replace it; reading it is not enough.
			$node = null;
			if ($fileId > 0) {
				$node = $this->rootFolder->getUserFolder($uid)->getFirstNodeById($fileId);
			}

			if ($node === null || $node->isUpdateable() === false) {
				return 'skipped';
			}

			$outcome = $this->classification->classify(
				objectData: $objectData,
				objectRef: ['id' => (string)$data['objectId'], 'register' => (string)$data['register'], 'schema' => (string)$data['schema']]
			);
		} catch (Throwable $e) {
			$this->logger->warning('Classification on enrich failed: ' . $e->getMessage(), ['exception' => $e]);

			return 'failed';
		}

		return $outcome['outcome'];
	}//end classifyReachable()

	/**
	 * Validate the required enrichment request parameters.
	 *
	 * @param array<string, mixed> $data The raw request parameters.
	 *
	 * @return JSONResponse|null A 400 response naming the first missing field, or null when valid.
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	private function validateEnrichParams(array $data): ?JSONResponse {
		if (empty($data['objectId']) === true) {
			return new JSONResponse(['error' => $this->l10n->t('objectId is required')], 400);
		}

		if (empty($data['register']) === true) {
			return new JSONResponse(['error' => $this->l10n->t('register is required')], 400);
		}

		if (empty($data['schema']) === true) {
			return new JSONResponse(['error' => $this->l10n->t('schema is required')], 400);
		}

		return null;
	}//end validateEnrichParams()
}//end class

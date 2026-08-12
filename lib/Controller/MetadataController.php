<?php

/**
 * Metadata Controller
 *
 * Controller for document metadata operations.
 * Provides an endpoint for triggering metadata enrichment on document objects.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\MetadataService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for metadata enrichment operations
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class MetadataController extends Controller {
	/**
	 * Constructor for MetadataController
	 *
	 * @param string $appName The application name
	 * @param IRequest $request The request object
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param MetadataService $metadataService Service for metadata operations
	 * @param IL10N $l10n The localization service
	 * @param IUserSession $userSession User session for authentication
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly LoggerInterface $logger,
		private readonly MetadataService $metadataService,
		private readonly IL10N $l10n,
		private readonly IUserSession $userSession,
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
	 */
	public function enrich(): JSONResponse {
		try {
			if ($this->userSession->getUser() === null) {
				return new JSONResponse(
					data: ['error' => $this->l10n->t('Not authenticated')],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$data = $this->request->getParams();

			$validationError = $this->validateEnrichParams(data: $data);
			if ($validationError !== null) {
				return $validationError;
			}

			// Get object data for enrichment.
			$objectData = $data['objectData'] ?? [];

			// Run metadata enhancement.
			$metadata = $this->metadataService->enhanceMetadata($objectData);

			if (empty($metadata) === true) {
				return new JSONResponse(
					[
						'success' => true,
						'message' => $this->l10n->t('No metadata enrichment needed'),
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
				]
			);
		} catch (Exception $e) {
			$this->logger->error(
				'Failed to enrich metadata: ' . $e->getMessage(),
				[
					'exception' => $e,
				]
			);
			return new JSONResponse(
				['error' => $this->l10n->t('Failed to enrich metadata: %s', [$e->getMessage()])],
				500
			);
		}//end try

	}//end enrich()

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

<?php

/**
 * Comparison Controller
 *
 * Exposes the on-demand document-comparison endpoint. Both subjects are resolved
 * through the requesting user's folder inside the service, so the endpoint is
 * IDOR-safe (ADR-005): an inaccessible subject yields 404 with no existence
 * disclosure.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\DocuDesk\Exception\ComparisonException;
use OCA\DocuDesk\Service\DocumentComparisonService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Controller for document comparison.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ComparisonController extends Controller {
	/**
	 * Constructor.
	 *
	 * @param string $appName The app name.
	 * @param IRequest $request The request.
	 * @param LoggerInterface $logger Logger.
	 * @param DocumentComparisonService $service The comparison service.
	 * @param IL10N $l10n Localisation.
	 * @param IUserSession $userSession User session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly LoggerInterface $logger,
		private readonly DocumentComparisonService $service,
		private readonly IL10N $l10n,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Compare two document subjects.
	 *
	 * Body: {"left": {"fileId": int, "versionTimestamp"?: int},
	 *        "right": {"fileId": int, "versionTimestamp"?: int}}
	 *
	 * @return JSONResponse The structured comparison or an error.
	 *
	 * @spec openspec/specs/document-comparison/spec.md
	 */
	#[NoAdminRequired]
	public function compare(): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Not authenticated')],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		$left = $this->request->getParam('left');
		$right = $this->request->getParam('right');

		if (is_array($left) === false || is_array($right) === false
			|| isset($left['fileId']) === false || isset($right['fileId']) === false
		) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Both "left" and "right" subjects with a fileId are required')],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$result = $this->service->compare(left: $left, right: $right);
			return new JSONResponse(data: $result, statusCode: Http::STATUS_OK);
		} catch (ComparisonException $e) {
			return new JSONResponse(
				data: [
					'error' => $this->mapReasonToMessage(reason: $e->getReason()),
					'reason' => $e->getReason(),
				],
				statusCode: $e->getStatusCode()
			);
		} catch (Throwable $e) {
			$this->logger->error('Comparison failed', ['exception' => $e->getMessage()]);
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Comparison failed')],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}//end try

	}//end compare()

	/**
	 * Map a machine-readable reason code to a localised message.
	 *
	 * @param string $reason The reason code.
	 *
	 * @return string The localised message.
	 */
	private function mapReasonToMessage(string $reason): string {
		switch ($reason) {
			case 'not-found':
				return $this->l10n->t('Subject not found');
			case 'versions-unavailable':
				return $this->l10n->t('File versions are not available on this instance');
			case 'too-large':
				return $this->l10n->t('A subject is too large to compare');
			case 'unsupported-format':
				return $this->l10n->t('A subject has an unsupported format');
			default:
				return $this->l10n->t('Comparison failed');
		}

	}//end mapReasonToMessage()
}//end class

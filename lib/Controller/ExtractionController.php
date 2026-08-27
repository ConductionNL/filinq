<?php

/**
 * Extraction Controller
 *
 * REST API controller for financial-document field extraction
 * ("scan-en-herken"): the financial extraction endpoint and the
 * correction-feedback endpoint.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Service\FinancialExtractionService;
use OCA\Filinq\Service\GlAccountSuggestionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for financial-document field extraction endpoints.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */
class ExtractionController extends Controller {
	/**
	 * Constructor.
	 *
	 * @param string $appName The application name.
	 * @param IRequest $request The request object.
	 * @param FinancialExtractionService $extractionService Extraction orchestration service.
	 * @param GlAccountSuggestionService $suggestionService GL-account suggestion service (booking-history feed).
	 * @param IUserSession $userSession User session for authentication.
	 * @param IL10N $l10n Localization service.
	 * @param LoggerInterface $logger Logger.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly FinancialExtractionService $extractionService,
		private readonly GlAccountSuggestionService $suggestionService,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Run financial-document field extraction (REQ-FIN-01).
	 *
	 * Accepts `{fileId|documentUri, docType, callbackEvent}`, runs the
	 * extraction pipeline, persists the result, and optionally publishes
	 * `nl.conduction.filinq.extraction.completed`.
	 *
	 * @return JSONResponse The extracted fields with per-field/overall confidence.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function financial(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Not authenticated')],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		try {
			$data = $this->request->getParams();
			$result = $this->extractionService->extractFinancial(data: $data, requestedBy: $user->getUID());
			return new JSONResponse($result, Http::STATUS_CREATED);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to run financial extraction', exception: $e);
		}

	}//end financial()

	/**
	 * Store human-corrected field values for a prior extraction (REQ-FIN-07).
	 *
	 * When the posted `fields` map includes a `glAccountCode` key, this also
	 * feeds the GL-account booking-history corpus for the extraction's
	 * resolved supplier identity (ai-gl-account-suggestion, REQ-GLS-05) — no
	 * new endpoint is introduced for this; it extends this existing one.
	 *
	 * @param string $id The `financialExtraction` object id.
	 *
	 * @return JSONResponse The updated extraction object.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	public function corrections(string $id): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Not authenticated')],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		try {
			$fields = $this->request->getParam('fields', []);
			if (is_array($fields) === false) {
				$fields = [];
			}

			$result = $this->extractionService->addCorrection(
				id: $id,
				correctedFields: $fields,
				correctedBy: $user->getUID()
			);

			$this->recordGlAccountBookingIfPresent(id: $id, fields: $fields, correctedBy: $user->getUID());

			return new JSONResponse($result);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to store correction', exception: $e);
		}

	}//end corrections()

	/**
	 * Feed the GL-account booking history when a correction includes a
	 * `glAccountCode` (ai-gl-account-suggestion, REQ-GLS-05). Best-effort:
	 * never turns a successful correction into a failed response.
	 *
	 * @param string $id The `financialExtraction` object id.
	 * @param array<string, mixed> $fields The posted corrected-fields map.
	 * @param string $correctedBy Nextcloud user id submitting the correction.
	 *
	 * @return void
	 */
	private function recordGlAccountBookingIfPresent(string $id, array $fields, string $correctedBy): void {
		if (array_key_exists('glAccountCode', $fields) === false) {
			return;
		}

		$accountCode = trim((string)$fields['glAccountCode']);
		if ($accountCode === '') {
			return;
		}

		$accountLabel = null;
		if (isset($fields['glAccountLabel']) === true) {
			$accountLabel = (string)$fields['glAccountLabel'];
		}

		try {
			$this->suggestionService->recordBooking(
				extractionId: $id,
				accountCode: $accountCode,
				accountLabel: $accountLabel,
				correctedBy: $correctedBy
			);
		} catch (Exception $e) {
			$this->logger->warning(
				'Filinq: correction stored but GL-account booking-history recording failed: ' . $e->getMessage()
			);
		}

	}//end recordGlAccountBookingIfPresent()

	/**
	 * Build an error JSON response with logging (mirrors
	 * SigningController::errorResponse() — never echoes exception text).
	 *
	 * @param string $message The log message prefix.
	 * @param Exception $exception The exception.
	 *
	 * @return JSONResponse The error response.
	 */
	private function errorResponse(string $message, Exception $exception): JSONResponse {
		$this->logger->error($message . ': ' . $exception->getMessage(), ['exception' => $exception]);

		$statusCode = Http::STATUS_INTERNAL_SERVER_ERROR;
		if ($exception->getCode() >= 400 && $exception->getCode() < 600) {
			$statusCode = $exception->getCode();
		}

		return new JSONResponse(
			['error' => $this->l10n->t($message)],
			$statusCode
		);

	}//end errorResponse()
}//end class

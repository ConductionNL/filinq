<?php

/**
 * Extraction Controller
 *
 * REST API controller for financial-document field extraction
 * ("scan-en-herken"): the financial extraction endpoint and the
 * correction-feedback endpoint.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\FinancialExtractionService;
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
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
 */
class ExtractionController extends Controller
{
    /**
     * Constructor.
     *
     * @param string                     $appName           The application name.
     * @param IRequest                   $request           The request object.
     * @param FinancialExtractionService $extractionService Extraction orchestration service.
     * @param IUserSession               $userSession       User session for authentication.
     * @param IL10N                      $l10n              Localization service.
     * @param LoggerInterface            $logger            Logger.
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly FinancialExtractionService $extractionService,
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
     * `nl.conduction.docudesk.extraction.completed`.
     *
     * @return JSONResponse The extracted fields with per-field/overall confidence.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
     */
    public function financial(): JSONResponse
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return new JSONResponse(
                data: ['error' => $this->l10n->t('Not authenticated')],
                statusCode: Http::STATUS_UNAUTHORIZED
            );
        }

        try {
            $data   = $this->request->getParams();
            $result = $this->extractionService->extractFinancial(data: $data, requestedBy: $user->getUID());
            return new JSONResponse($result, Http::STATUS_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to run financial extraction', exception: $e);
        }

    }//end financial()

    /**
     * Store human-corrected field values for a prior extraction (REQ-FIN-07).
     *
     * @param string $id The `financialExtraction` object id.
     *
     * @return JSONResponse The updated extraction object.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
     */
    public function corrections(string $id): JSONResponse
    {
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

            return new JSONResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to store correction', exception: $e);
        }

    }//end corrections()

    /**
     * Build an error JSON response with logging (mirrors
     * SigningController::errorResponse() — never echoes exception text).
     *
     * @param string    $message   The log message prefix.
     * @param Exception $exception The exception.
     *
     * @return JSONResponse The error response.
     */
    private function errorResponse(string $message, Exception $exception): JSONResponse
    {
        $this->logger->error($message.': '.$exception->getMessage(), ['exception' => $exception]);

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

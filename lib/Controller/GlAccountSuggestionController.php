<?php

/**
 * GL Account Suggestion Controller
 *
 * REST API controller for the GL-account ("grootboekrekening") suggestion
 * endpoint that sits on top of an existing financial extraction.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\GlAccountSuggestionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for the GL-account suggestion endpoint.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */
class GlAccountSuggestionController extends Controller
{
    /**
     * Constructor.
     *
     * @param string                     $appName           The application name.
     * @param IRequest                   $request           The request object.
     * @param GlAccountSuggestionService $suggestionService Suggestion orchestration service.
     * @param IUserSession               $userSession       User session for authentication.
     * @param IL10N                      $l10n              Localization service.
     * @param LoggerInterface            $logger            Logger.
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly GlAccountSuggestionService $suggestionService,
        private readonly IUserSession $userSession,
        private readonly IL10N $l10n,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Compute a GL-account suggestion for a prior financial extraction
     * (REQ-GLS-06).
     *
     * Accepts an optional `{candidateAccounts: [{code, label}]}` body to
     * constrain/seed ranking, and dispatches
     * `nl.conduction.docudesk.gl-account.suggested` on success.
     *
     * @param string $id The `financialExtraction` object id.
     *
     * @return JSONResponse The ranked suggestion result.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/ai-gl-account-suggestion/spec.md
     */
    public function suggestAccount(string $id): JSONResponse
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return new JSONResponse(
                data: ['error' => $this->l10n->t('Not authenticated')],
                statusCode: Http::STATUS_UNAUTHORIZED
            );
        }

        try {
            $candidateAccounts = $this->request->getParam('candidateAccounts', []);
            if (is_array($candidateAccounts) === false) {
                $candidateAccounts = [];
            }

            $sourceApp = (string) $this->request->getParam('sourceApp', '');

            $result = $this->suggestionService->suggest(
                extractionId: $id,
                candidateAccounts: $candidateAccounts,
                sourceApp: $sourceApp,
                requestedBy: $user->getUID()
            );

            return new JSONResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to compute GL-account suggestion', exception: $e);
        }

    }//end suggestAccount()

    /**
     * Build an error JSON response with logging (mirrors
     * ExtractionController::errorResponse() — never echoes exception text).
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

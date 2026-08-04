<?php
/**
 * Dossier Controller
 *
 * HTTP entry points for dossier-level operations. Currently surfaces a
 * single endpoint — `POST /api/anonymization/dossier/{dossierId}/grondslagen-pdf`
 * — which (re)generates the per-dossier grondslagen summary PDF aggregating
 * every redacted entity under the dossier's folder. See
 * `GrondslagenSummaryService::renderDossierSummary` for the render itself.
 *
 * Authentication is required (the route is `@NoAdminRequired` but
 * non-anonymous). Authorisation: the caller MUST be able to read the
 * dossier object via OpenRegister's standard RBAC + the file listing
 * uses the session user's view, so visibility of files under the
 * dossier folder mirrors the operator's permissions.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for dossier-level endpoints.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DossierController extends Controller
{
    /**
     * Constructor for DossierController.
     *
     * @param string                    $appName            The application name.
     * @param IRequest                  $request            The current HTTP request.
     * @param LoggerInterface           $logger             Logger for error reporting.
     * @param GrondslagenSummaryService $grondslagenSummary Per-dossier renderer.
     * @param IL10N                     $l10n               Localisation service.
     * @param IUserSession              $userSession        User session for auth check.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly GrondslagenSummaryService $grondslagenSummary,
        private readonly IL10N $l10n,
        private readonly IUserSession $userSession
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Regenerate the per-dossier grondslagen summary PDF.
     *
     * Walks every file under the dossier folder, aggregates anonymised
     * entities + their grondslagen, and writes the resulting summary PDF
     * to `<dossier-folder>/grondslagen.pdf`. The dossier object's
     * `configuration.grondslagen.{fileId, lastGeneratedAt}` is updated on
     * success so the dossier UI can badge the report as fresh.
     *
     * @param string $dossierId The OR dossier object UUID.
     *
     * @return JSONResponse The generated file's metadata, or an error payload.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function generateGrondslagenSummary(string $dossierId): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(
                ['error' => $this->l10n->t('Not authenticated')],
                Http::STATUS_UNAUTHORIZED
            );
        }

        try {
            $this->grondslagenSummary->authorizeAccess(dossierId: $dossierId);
            $file = $this->grondslagenSummary->renderDossierSummary(dossierUuid: $dossierId);

            return new JSONResponse(
                [
                    'fileId'      => $file->getId(),
                    'filename'    => $file->getName(),
                    'filePath'    => $file->getPath(),
                    'size'        => $file->getSize(),
                    'generatedAt' => date('c'),
                ]
            );
        } catch (Exception $e) {
            $this->logger->error(
                'DossierController::generateGrondslagenSummary failed: '.$e->getMessage(),
                ['dossierId' => $dossierId, 'exception' => $e]
            );
            return new JSONResponse(
                [
                    'error' => $this->l10n->t(
                        'Failed to generate dossier summary: %s',
                        [$e->getMessage()]
                    ),
                ],
                500
            );
        }//end try

    }//end generateGrondslagenSummary()
}//end class

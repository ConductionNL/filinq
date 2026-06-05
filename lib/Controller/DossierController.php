<?php

/**
 * Dossier Controller
 *
 * Controller for dossier-level operations within the anonymisation pipeline.
 * Provides the on-demand per-dossier grondslagen summary PDF endpoint.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-5
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-6
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
 * Controller for dossier-level anonymisation operations
 *
 * Provides the POST /api/anonymization/dossier/{dossierId}/grondslagen-pdf
 * endpoint for on-demand per-dossier grondslagen summary generation.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-5
 */
class DossierController extends Controller
{
    /**
     * Constructor for DossierController
     *
     * @param string                    $appName                   Application name
     * @param IRequest                  $request                   Incoming request
     * @param LoggerInterface           $logger                    Logger
     * @param GrondslagenSummaryService $grondslagenSummaryService Grondslagen summary service
     * @param IL10N                     $l10n                      Localisation
     * @param IUserSession              $userSession               User session for auth
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-5
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly GrondslagenSummaryService $grondslagenSummaryService,
        private readonly IL10N $l10n,
        private readonly IUserSession $userSession,
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Generate (or regenerate) the per-dossier grondslagen summary PDF on demand.
     *
     * Resolves the dossier via OR, aggregates anonymised entity-grondslag data
     * across all files in the dossier folder, renders a PDF/A-3b summary, and
     * writes it to `<dossier-folder>/anonymised/grondslagen.pdf` (fallback:
     * `<dossier-folder>/grondslagen.pdf`). Updates `configuration.grondslagen.*`
     * on the dossier object and returns the updated dossier metadata.
     *
     * @param string $dossierId Dossier UUID (from the URL path)
     *
     * @return JSONResponse HTTP 200 with dossier metadata, or 4xx/5xx on error
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-5
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-6
     */
    public function generateGrondslagenPdf(string $dossierId): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('Not authenticated')],
                    Http::STATUS_UNAUTHORIZED
                );
            }

            $authError = $this->authoriseDossierAccess(dossierId: $dossierId);
            if ($authError !== null) {
                return $authError;
            }

            $dossier = $this->grondslagenSummaryService->renderDossierSummary(
                dossierId: $dossierId
            );

            if ($dossier === null) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('Dossier not found')],
                    Http::STATUS_NOT_FOUND
                );
            }

            $data = (array) $dossier;
            if (method_exists(object_or_class: $dossier, method: 'getObject') === true) {
                $data = $dossier->getObject();
            }

            return new JSONResponse($data);
        } catch (Exception $e) {
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                message: 'Failed to generate grondslagen PDF for dossier '.$dossierId.': '.$e->getMessage(),
                context: ['exception' => $e]
            );

            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to generate grondslagen PDF: %s', [$e->getMessage()])],
                $statusCode
            );
        }//end try

    }//end generateGrondslagenPdf()

    /**
     * Validate the current user and dossier ID before allowing access.
     *
     * Returns null when validation passes (authenticated user, non-empty ID).
     * Returns a JSONResponse when validation fails (unauthenticated, empty ID).
     *
     * Note: all authenticated users may invoke this endpoint. Per-object
     * access control is enforced downstream by OpenRegister's RBAC — an
     * inaccessible dossier returns null from find(), surfacing as 404.
     *
     * @param string $dossierId Dossier UUID
     *
     * @return JSONResponse|null Error response or null when validation passes
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-5
     */
    private function authoriseDossierAccess(string $dossierId): ?JSONResponse
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return new JSONResponse(
                ['error' => $this->l10n->t('Not authenticated')],
                Http::STATUS_UNAUTHORIZED
            );
        }

        // Validate that dossierId is non-empty.
        if (trim(string: $dossierId) === '') {
            return new JSONResponse(
                ['error' => $this->l10n->t('Invalid dossier ID')],
                Http::STATUS_BAD_REQUEST
            );
        }

        return null;

    }//end authoriseDossierAccess()
}//end class

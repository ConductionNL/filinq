<?php
/**
 * Validation Controller
 *
 * On-demand document-validation endpoint. Resolves the file through the
 * requesting user's folder (IDOR-safe per ADR-005) and returns the verdict +
 * findings without persisting anything.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-validation-checks/specs/document-validation-checks/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\DocuDesk\Service\DocumentValidationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Controller for on-demand document validation.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ValidationController extends Controller
{


    /**
     * Constructor.
     *
     * @param string                    $appName     The app name.
     * @param IRequest                  $request     The request.
     * @param LoggerInterface           $logger      Logger.
     * @param DocumentValidationService $service     The validation service.
     * @param IRootFolder               $rootFolder  Root folder.
     * @param IL10N                     $l10n        Localisation.
     * @param IUserSession              $userSession User session.
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly DocumentValidationService $service,
        private readonly IRootFolder $rootFolder,
        private readonly IL10N $l10n,
        private readonly IUserSession $userSession
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()


    /**
     * Validate a file on demand without persisting.
     *
     * Body: {"fileId": int, "documentType"?: string}
     *
     * @return JSONResponse The verdict + findings, or an error.
     *
     * @spec openspec/changes/document-validation-checks/specs/document-validation-checks/spec.md
     */
    #[NoAdminRequired]
    public function validate(): JSONResponse
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return new JSONResponse(
                data: ['error' => $this->l10n->t('Not authenticated')],
                statusCode: Http::STATUS_UNAUTHORIZED
            );
        }

        $fileId = (int) $this->request->getParam('fileId', 0);
        if ($fileId <= 0) {
            return new JSONResponse(
                data: ['error' => $this->l10n->t('fileId is required')],
                statusCode: Http::STATUS_BAD_REQUEST
            );
        }

        $documentType = $this->request->getParam('documentType');
        if ($documentType !== null) {
            $documentType = (string) $documentType;
        }

        // IDOR-safe resolution: a file the user cannot read is a 404, no disclosure.
        $userFolder = $this->rootFolder->getUserFolder($user->getUID());
        $nodes      = $userFolder->getById($fileId);
        if (empty($nodes) === true || ($nodes[0] instanceof File) === false) {
            return new JSONResponse(
                data: ['error' => $this->l10n->t('File not found')],
                statusCode: Http::STATUS_NOT_FOUND
            );
        }

        try {
            $result = $this->service->validate(file: $nodes[0], record: [], documentType: $documentType);
            return new JSONResponse(data: $result, statusCode: Http::STATUS_OK);
        } catch (Throwable $e) {
            $this->logger->error('Validation failed', ['fileId' => $fileId, 'exception' => $e->getMessage()]);
            return new JSONResponse(
                data: ['error' => $this->l10n->t('Validation failed')],
                statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }

    }//end validate()
}//end class

<?php
/**
 * Anonymization Controller
 *
 * Controller for the document anonymization pipeline.
 * Provides endpoints for uploading files, extracting/detecting entities,
 * and anonymizing documents.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\AnonymizeRequestService;
use OCA\DocuDesk\Service\FileListingService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for anonymization pipeline endpoints
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
 */
class AnonymizationController extends Controller
{

    /**
     * Workflow behind the anonymize endpoints: authentication, per-user file
     * access verification, body validation, the prohibition guards and the
     * anonymisation call itself.
     *
     * @var AnonymizeRequestService
     */
    private readonly AnonymizeRequestService $anonymizeRequest;

    /**
     * Constructor for AnonymizationController
     *
     * @param string               $appName              The application name
     * @param IRequest             $request              The request object
     * @param LoggerInterface      $logger               Logger for error reporting
     * @param AnonymizationService $anonymizationService Service for anonymization operations
     * @param FileListingService   $fileListingService   Service for file listing operations
     * @param IL10N                $l10n                 The localization service
     * @param IAppConfig           $appConfig            Tenant configuration provider (reads
     *                                                   docudesk.anonymisation.default_output_format)
     * @param IUserSession         $userSession          User session for authentication
     * @param IRootFolder          $rootFolder           Root folder for file access checks
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly AnonymizationService $anonymizationService,
        private readonly FileListingService $fileListingService,
        private readonly IL10N $l10n,
        private readonly IAppConfig $appConfig,
        private readonly IUserSession $userSession,
        private readonly IRootFolder $rootFolder,
    ) {
        parent::__construct(appName: $appName, request: $request);

        $this->anonymizeRequest = new AnonymizeRequestService(
            logger: $this->logger,
            anonymizationService: $this->anonymizationService,
            l10n: $this->l10n,
            rootFolder: $this->rootFolder,
            userSession: $this->userSession
        );

    }//end __construct()

    /**
     * Default-output-format tenant config key. The per-call
     * `outputFormat` request param overrides this when supplied.
     */
    private const DEFAULT_OUTPUT_FORMAT_KEY = 'docudesk.anonymisation.default_output_format';


    /**
     * Supported values for the `outputFormat` request param + tenant
     * config. Anything else from the request results in HTTP 400.
     *
     * - `pdf-only` (default): convert to PDF and delete the native
     *   anonymised intermediate so only the PDF remains.
     * - `pdf`: convert to PDF but keep the native intermediate too.
     * - `preserve`: skip conversion; native format is the only output.
     */
    private const VALID_OUTPUT_FORMATS = ['pdf-only', 'pdf', 'preserve'];

    /**
     * List all processed files with entity counts and status
     *
     * Returns files from the user's DocuDesk folder with their
     * entity detection counts and anonymization status.
     *
     * @return JSONResponse JSON response with array of file data
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    public function files(): JSONResponse
    {
        try {
            $authError = $this->anonymizeRequest->requireAuthenticated();
            if ($authError !== null) {
                return new JSONResponse($authError['body'], $authError['status']);
            }

            $result = $this->fileListingService->listProcessedFiles();

            return new JSONResponse($result);
        } catch (\Throwable $e) {
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                'Failed to list processed files: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to list processed files: %s', [$e->getMessage()])],
                $statusCode
            );
        }//end try

    }//end files()

    /**
     * Upload a file to the user's DocuDesk folder
     *
     * Reads the uploaded file from the request and saves it
     * to the user's DocuDesk folder.
     *
     * @return JSONResponse JSON response with upload result
     *
     * @NoAdminRequired
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    public function upload(): JSONResponse
    {
        try {
            $authError = $this->anonymizeRequest->requireAuthenticated();
            if ($authError !== null) {
                return new JSONResponse($authError['body'], $authError['status']);
            }

            $file = $this->request->getUploadedFile('file');

            if (empty($file) === true || isset($file['tmp_name']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('No file uploaded')],
                    400
                );
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('File upload failed with error code: %s', [$file['error']])],
                    400
                );
            }

            $fileName    = $file['name'];
            $fileContent = file_get_contents($file['tmp_name']);

            if ($fileContent === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('Failed to read uploaded file')],
                    500
                );
            }

            $result = $this->fileListingService->uploadFile($fileName, $fileContent);

            return new JSONResponse($result);
        } catch (\Throwable $e) {
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                'Failed to upload file: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to upload file: %s', [$e->getMessage()])],
                $statusCode
            );
        }//end try

    }//end upload()

    /**
     * Extract text and detect entities in a file
     *
     * Runs text extraction and entity recognition on the specified file.
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return JSONResponse JSON response with extraction and detection results
     *
     * @NoAdminRequired
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    public function extract(int $fileId): JSONResponse
    {
        try {
            $authError = $this->anonymizeRequest->requireAuthenticated();
            if ($authError !== null) {
                return new JSONResponse($authError['body'], $authError['status']);
            }

            $accessError = $this->anonymizeRequest->verifyFileAccess(fileId: $fileId);
            if ($accessError !== null) {
                return new JSONResponse($accessError['body'], $accessError['status']);
            }

            // Default: resume (cached) — reuse existing entities when the file
            // is unchanged. `force=true` requests an explicit re-analysis.
            $force = filter_var($this->request->getParam('force', false), FILTER_VALIDATE_BOOLEAN);
            if ($force === true) {
                return new JSONResponse($this->anonymizationService->reExtractAndDetectEntities($fileId));
            }

            return new JSONResponse($this->anonymizationService->extractAndDetectEntities($fileId));
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to extract and detect entities: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to extract and detect entities: %s', [$e->getMessage()])],
                500
            );
        }//end try

    }//end extract()

    /**
     * Anonymize entities in a document
     *
     * Replaces detected entities in the document with anonymized placeholders.
     * Supports optional excludeTypes, minConfidence, appendBasisSummary, and
     * outputFormat parameters. Stray `bases[]` fields on entity entries are
     * silently ignored (per 2026-05-12 explore-mode rework); bases are set via
     * OR's PATCH /api/entity-relations/{id}.
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return JSONResponse JSON response with anonymization result
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
     * @spec openspec/specs/anonymization/spec.md
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
     */
    public function anonymize(int $fileId): JSONResponse
    {
        try {
            $authError = $this->anonymizeRequest->requireAuthenticated();
            if ($authError !== null) {
                return new JSONResponse($authError['body'], $authError['status']);
            }

            // Validate request body BEFORE file-access checks so that malformed
            // input always yields HTTP 400 regardless of whether the file exists.
            $params    = $this->request->getParams();
            $validated = $this->anonymizeRequest->validateBody(params: $params);
            if ($validated['error'] !== null) {
                return new JSONResponse($validated['error']['body'], $validated['error']['status']);
            }

            // File access check MUST precede the prohibition oracle so that unauthenticated
            // or unauthorized callers cannot probe prohibition rules by supplying arbitrary
            // entity names without holding access to the target file (OWASP A01:2021).
            $accessError = $this->anonymizeRequest->verifyFileAccess(fileId: $fileId);
            if ($accessError !== null) {
                return new JSONResponse($accessError['body'], $accessError['status']);
            }

            $prohibited = $this->anonymizeRequest->checkUnredactedProhibitions(
                unredactedEntities: $validated['request']['unredactedEntities']
            );
            if ($prohibited !== null) {
                return new JSONResponse($prohibited['body'], $prohibited['status']);
            }

            // Anonymise-output-as-pdf-by-default: optional `outputFormat`
            // selects PDF conversion vs native-format preservation.
            // Per-call value overrides the tenant default; missing
            // value falls back to the tenant default which itself
            // defaults to 'pdf-only'.
            $outputFormat = $this->resolveOutputFormat(params: $params);
            if ($outputFormat === null) {
                return new JSONResponse(
                    [
                        'error'         => $this->l10n->t(
                            'Invalid outputFormat: must be one of %s',
                            [implode(', ', self::VALID_OUTPUT_FORMATS)]
                        ),
                        'allowedValues' => self::VALID_OUTPUT_FORMATS,
                    ],
                    400
                );
            }

            try {
                $result = $this->anonymizeRequest->executeAnonymize(
                    fileId: $fileId,
                    userId: $this->anonymizeRequest->currentUserId(),
                    params: $params,
                    request: $validated['request'],
                    outputFormat: $outputFormat
                );
            } catch (ConversionFailedException $e) {
                $this->logger->warning(
                    'PDF conversion cascade exhausted; returning 422.',
                    ['attempts' => $e->getAttempts()]
                );
                return new JSONResponse(
                    [
                        'error'              => $this->l10n->t(
                            'Conversion to PDF failed; anonymisation rolled back.'
                        ),
                        'conversionAttempts' => $e->getAttempts(),
                        'outputFormat'       => $outputFormat,
                        'fallback'           => $this->l10n->t(
                            'Set outputFormat to "preserve" to bypass conversion if you must keep the native format.'
                        ),
                    ],
                    422
                );
            }//end try

            return new JSONResponse($result['body'], $result['status']);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to anonymize document: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to anonymize document: %s', [$e->getMessage()])],
                500
            );
        }//end try

    }//end anonymize()

    /**
     * Record a per-entity skip/include decision, guarded by the prohibition policy.
     *
     * Called by the review UI on skip-toggle in place of PATCHing OpenRegister's
     * relation endpoint directly. Skipping a prohibition-matched entity is
     * rejected with HTTP 422 (absolute at/above the threshold; below it only
     * unless `force`). Include / non-skip decisions are always allowed and
     * forwarded to OpenRegister.
     *
     * `$id` is a caller-supplied primary key into a table that is NOT scoped to
     * the caller, so the decision path is authorised twice: authentication here,
     * and per-document ownership inside RelationSkipDecisionService (which is
     * where the relation — and therefore its Nextcloud file id — is loaded). A
     * relation on a document the caller cannot reach yields the same 404 as one
     * that does not exist.
     *
     * @param int $id The EntityRelation id.
     *
     * @return JSONResponse Success, or 401 / 404 / 422 with `{threshold, prohibitionMatch}`.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
     */
    public function updateRelation(int $id): JSONResponse
    {
        $denied = $this->anonymizeRequest->requireAuthenticated();
        if ($denied !== null) {
            return new JSONResponse($denied['body'], $denied['status']);
        }

        $result = $this->anonymizeRequest->applyRelationDecision(
            relationId: $id,
            params: $this->request->getParams()
        );

        return new JSONResponse($result['body'], $result['status']);

    }//end updateRelation()

    /**
     * Resolve the effective `outputFormat` for this request.
     *
     * Order: per-call value (when supplied and valid) → tenant default
     * from IAppConfig → hard-coded `"pdf-only"` fallback.
     *
     * Returns `null` when the per-call value is supplied but invalid;
     * the caller maps that to HTTP 400.
     *
     * @param array<string,mixed> $params Request params.
     *
     * @return string|null Resolved outputFormat ('pdf-only'|'pdf'|'preserve'),
     *                     or null when an invalid value was supplied.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    private function resolveOutputFormat(array $params): ?string
    {
        if (array_key_exists('outputFormat', $params) === true) {
            $value = $params['outputFormat'];
            if (is_string($value) === false
                || in_array($value, self::VALID_OUTPUT_FORMATS, true) === false
            ) {
                return null;
            }

            return $value;
        }

        $tenantDefault = $this->appConfig->getValueString(
            'docudesk',
            self::DEFAULT_OUTPUT_FORMAT_KEY,
            'pdf-only'
        );

        if (in_array($tenantDefault, self::VALID_OUTPUT_FORMATS, true) === false) {
            // Malformed tenant setting falls back to spec default
            // rather than rejecting the call.
            return 'pdf-only';
        }

        return $tenantDefault;

    }//end resolveOutputFormat()
}//end class

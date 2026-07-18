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
use OCA\DocuDesk\Exception\ProhibitionGateException;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\FileListingService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
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
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
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
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
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
     * Verify the current user has access to the given file ID
     *
     * Resolves the file via the user's own file tree so that an authenticated
     * user cannot operate on files they do not own (security finding C3 —
     * file IDOR). Returns 404 on failure so callers cannot probe for existence.
     *
     * @param int $fileId The Nextcloud file ID to check
     *
     * @return JSONResponse|null Null when access is granted, 404 response otherwise
     */
    private function verifyFileAccess(int $fileId): ?JSONResponse
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return new JSONResponse(
                ['error' => $this->l10n->t('Not authenticated')],
                Http::STATUS_UNAUTHORIZED
            );
        }

        $nodes = $this->rootFolder->getUserFolder($user->getUID())->getById($fileId);
        if (empty($nodes) === true) {
            return new JSONResponse(
                ['error' => $this->l10n->t('File not found')],
                Http::STATUS_NOT_FOUND
            );
        }

        if (($nodes[0] instanceof File) === false) {
            return new JSONResponse(
                ['error' => $this->l10n->t('File not found')],
                Http::STATUS_NOT_FOUND
            );
        }

        return null;

    }//end verifyFileAccess()

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
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            $accessError = $this->verifyFileAccess(fileId: $fileId);
            if ($accessError !== null) {
                return $accessError;
            }

            // Default: resume (cached) — reuse existing entities when the file
            // is unchanged. `force=true` requests an explicit re-analysis.
            $force  = filter_var($this->request->getParam('force', false), FILTER_VALIDATE_BOOLEAN);
            $result = $this->anonymizationService->extractAndDetectEntities($fileId, $force);

            return new JSONResponse($result);
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
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            // Validate request body BEFORE file-access checks so that malformed
            // input always yields HTTP 400 regardless of whether the file exists.
            $params   = $this->request->getParams();
            $entities = $params['entities'] ?? [];

            if (is_array($entities) === false || empty($entities) === true) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('No entities provided for anonymization')],
                    400
                );
            }

            // Detect stray bases fields for ignoredFields hint (GDPR accountability).
            $hasStrayBases = false;
            foreach ($entities as $entity) {
                if (is_array($entity) === true && array_key_exists('bases', $entity) === true) {
                    $hasStrayBases = true;
                    break;
                }
            }

            $appendBasisSummary = $this->extractAppendBasisSummary(params: $params);
            if ($appendBasisSummary instanceof JSONResponse) {
                return $appendBasisSummary;
            }

            $unredactedEntities = $params['unredactedEntities'] ?? [];
            if (is_array($unredactedEntities) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('unredactedEntities must be an array')],
                    400
                );
            }

            if (empty($unredactedEntities) === false) {
                $validationError = $this->validateUnredactedEntities(entries: $unredactedEntities);
                if ($validationError !== null) {
                    return $validationError;
                }
            }//end if

            // Parse and validate acknowledgedOverrides.
            $acknowledgedOverrides = $params['acknowledgedOverrides'] ?? [];
            if (is_array($acknowledgedOverrides) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('acknowledgedOverrides must be an array')],
                    400
                );
            }

            $overridesError = $this->validateAcknowledgedOverrides(overrides: $acknowledgedOverrides);
            if ($overridesError !== null) {
                return $overridesError;
            }

            // File access check MUST precede the prohibition oracle so that unauthenticated
            // or unauthorized callers cannot probe prohibition rules by supplying arbitrary
            // entity names without holding access to the target file (OWASP A01:2021).
            $accessError = $this->verifyFileAccess(fileId: $fileId);
            if ($accessError !== null) {
                return $accessError;
            }

            if (empty($unredactedEntities) === false) {
                $violations = $this->anonymizationService->checkUnredactedProhibitions(
                    unredactedEntities: $unredactedEntities
                );
                if (empty($violations) === false) {
                    return new JSONResponse(
                        [
                            'error'             => $this->l10n->t(
                                'One or more unredacted entities match a publication-prohibition rule. '
                                .'Move those entities to entities[] to anonymize them instead.'
                            ),
                            'prohibitedEntries' => $violations,
                        ],
                        422
                    );
                }
            }//end if

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

            $entities = $this->filterByExcludeTypes(entities: $entities, params: $params);
            $entities = $this->filterByConfidence(entities: $entities, params: $params);

            // Placeholder-numbering scope (anonymisation-placeholder-id-scope):
            // forwarded to OpenRegister. 'document' (default) numbers entities
            // locally to this file; 'dossier' makes the number consistent across
            // the dossier folder's files. `dossierKey` is the stable folder id;
            // when omitted under scope=dossier, OpenRegister falls back to the
            // file's parent folder. Any value other than 'dossier' normalises to
            // per-document.
            $scopeParam = (string) ($params['scope'] ?? 'document');
            $scope      = 'document';
            if ($scopeParam === 'dossier') {
                $scope = 'dossier';
            }

            $dossierKeyParam = $params['dossierKey'] ?? null;
            $dossierKey      = null;
            if ($dossierKeyParam !== null && $dossierKeyParam !== '') {
                $dossierKey = (string) $dossierKeyParam;
            }

            // Defence-in-depth backstop (Robert): refuse if an absolute-tier
            // prohibition entity would be left un-redacted (e.g. skipped by
            // bypassing the guarded skip endpoint and PATCHing OpenRegister
            // directly). Complements the request-payload prohibition gate that
            // runProhibitionGate() enforces inside anonymizeDocument().
            $violations = $this->anonymizationService->absoluteProhibitionViolations($fileId);
            if (empty($violations) === false) {
                return new JSONResponse(
                    [
                        'error'                     => $this->l10n->t(
                            'Anonymisation blocked: prohibition-listed entities would be left un-redacted.'
                        ),
                        'missingProhibitionMatches' => $violations,
                    ],
                    422
                );
            }

            try {
                $result = $this->anonymizationService->anonymizeDocument(
                    fileId: $fileId,
                    entities: $entities,
                    appendBasisSummary: $appendBasisSummary,
                    outputFormat: $outputFormat,
                    unredactedEntities: $unredactedEntities,
                    acknowledgedOverrides: $acknowledgedOverrides,
                    userId: $user->getUID(),
                    scope: $scope,
                    dossierKey: $dossierKey
                );
            } catch (ProhibitionGateException $e) {
                // Fail-closed (backend outage) → 503 so clients can retry;
                // rule-match block → 422 with structured missing/rejected
                // body so the UI can prompt for overrides.
                $backendReason = $e->getBackendUnavailable();
                if ($backendReason !== null) {
                    $this->logger->warning(
                        'ProhibitionGate failed closed: '.$backendReason,
                        ['fileId' => $fileId]
                    );
                    return new JSONResponse(
                        [
                            'error'              => $this->l10n->t(
                                'Anonymisation temporarily unavailable: the prohibition gate could not '
                                .'verify the document. Please retry shortly.'
                            ),
                            'backendUnavailable' => $backendReason,
                        ],
                        503
                    );
                }

                return new JSONResponse(
                    [
                        'error'                     => $this->l10n->t(
                            'Anonymisation blocked: one or more prohibition-listed entities are missing '
                            .'from the to-be-anonymised set or an override was rejected.'
                        ),
                        'missingProhibitionMatches' => $e->getMissingProhibitionMatches(),
                        'rejectedOverrides'         => $e->getRejectedOverrides(),
                    ],
                    422
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

            if ($hasStrayBases === true) {
                $result['ignoredFields'] = ['bases'];
            }

            return new JSONResponse($result);
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
     * Extract and validate the appendBasisSummary flag from request params.
     *
     * Returns a JSONResponse (HTTP 400) when the field is present but not boolean.
     *
     * @param array<string, mixed> $params Request parameters
     *
     * @return bool|JSONResponse False when omitted, true when set, 400 response on type error.
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
     */
    private function extractAppendBasisSummary(array $params): bool|JSONResponse
    {
        if (array_key_exists('appendBasisSummary', $params) === false) {
            return false;
        }

        $value = $params['appendBasisSummary'];
        if (is_bool($value) === false) {
            return new JSONResponse(
                ['error' => $this->l10n->t('appendBasisSummary must be a boolean')],
                400
            );
        }

        return $value;

    }//end extractAppendBasisSummary()

    /**
     * Validate the unredactedEntities[] payload entries.
     *
     * Each entry must have:
     *   - entityId   (int, required)
     *   - entityText (string, required)
     *   - entityType (string, required)
     *   - publicationBases (array of strings, required, non-empty)
     * Optional:
     *   - contactEmail   (string)
     *   - contactAddress (string)
     *
     * @param array<int, mixed> $entries The unredactedEntities array from the request
     *
     * @return JSONResponse|null HTTP 400 on the first invalid entry, null when all valid
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
     */
    private function validateUnredactedEntities(array $entries): ?JSONResponse
    {
        foreach ($entries as $idx => $entry) {
            if (is_array($entry) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('Each unredactedEntities entry must be an object (index %s)', [$idx])],
                    400
                );
            }

            if (isset($entry['entityId']) === false || is_int($entry['entityId']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('unredactedEntities[%s].entityId is required and must be an integer', [$idx])],
                    400
                );
            }

            if (empty($entry['entityText']) === true || is_string($entry['entityText']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('unredactedEntities[%s].entityText is required and must be a string', [$idx])],
                    400
                );
            }

            if (empty($entry['entityType']) === true || is_string($entry['entityType']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('unredactedEntities[%s].entityType is required and must be a string', [$idx])],
                    400
                );
            }

            if (isset($entry['publicationBases']) === false || is_array($entry['publicationBases']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('unredactedEntities[%s].publicationBases is required and must be an array', [$idx])],
                    400
                );
            }

            if (empty($entry['publicationBases']) === true) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('unredactedEntities[%s].publicationBases must contain at least one basis', [$idx])],
                    400
                );
            }

            foreach ($entry['publicationBases'] as $base) {
                if (is_string($base) === false) {
                    return new JSONResponse(
                        ['error' => $this->l10n->t('Each entry in unredactedEntities[%s].publicationBases must be a string', [$idx])],
                        400
                    );
                }
            }

            if (isset($entry['contactEmail']) === true && is_string($entry['contactEmail']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('unredactedEntities[%s].contactEmail must be a string', [$idx])],
                    400
                );
            }

            if (isset($entry['contactAddress']) === true && is_string($entry['contactAddress']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('unredactedEntities[%s].contactAddress must be a string', [$idx])],
                    400
                );
            }
        }//end foreach

        return null;

    }//end validateUnredactedEntities()

    /**
     * Record a per-entity skip/include decision, guarded by the prohibition policy.
     *
     * Called by the review UI on skip-toggle in place of PATCHing OpenRegister's
     * relation endpoint directly. Skipping a prohibition-matched entity is
     * rejected with HTTP 422 (absolute at/above the threshold; below it only
     * unless `force`). Include / non-skip decisions are always allowed and
     * forwarded to OpenRegister.
     *
     * @param int $id The EntityRelation id.
     *
     * @return JSONResponse Success, or 422 with `{threshold, prohibitionMatch}`.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function updateRelation(int $id): JSONResponse
    {
        try {
            $params = $this->request->getParams();

            $skip = false;
            if (array_key_exists('skipAnonymization', $params) === true) {
                if (is_bool($params['skipAnonymization']) === false) {
                    return new JSONResponse(
                        ['error' => $this->l10n->t('Invalid skipAnonymization: must be a boolean')],
                        400
                    );
                }

                $skip = $params['skipAnonymization'];
            }

            $bases = null;
            if (array_key_exists('bases', $params) === true && is_array($params['bases']) === true) {
                $bases = array_values($params['bases']);
            }

            $force = filter_var(($params['force'] ?? false), FILTER_VALIDATE_BOOLEAN);

            $result = $this->anonymizationService->applyRelationSkipDecision(
                relationId: $id,
                skip: $skip,
                bases: $bases,
                force: $force
            );

            return new JSONResponse($result['body'], $result['status']);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to update entity relation decision: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to update entity relation decision')],
                500
            );
        }//end try

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

    /**
     * Validate the acknowledgedOverrides[] payload entries.
     *
     * Each entry must have:
     *   - ruleId   (string, required)
     *   - entityId (int, required)
     * Optional:
     *   - reason (string)
     *
     * @param array<int, mixed> $overrides The acknowledgedOverrides array from the request.
     *
     * @return JSONResponse|null HTTP 400 on the first invalid entry, null when all valid.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
     */
    private function validateAcknowledgedOverrides(array $overrides): ?JSONResponse
    {
        foreach ($overrides as $idx => $override) {
            if (is_array($override) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('Each acknowledgedOverrides entry must be an object (index %s)', [$idx])],
                    400
                );
            }

            if (empty($override['ruleId']) === true || is_string($override['ruleId']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('acknowledgedOverrides[%s].ruleId is required and must be a string', [$idx])],
                    400
                );
            }

            if (isset($override['entityId']) === false || is_int($override['entityId']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('acknowledgedOverrides[%s].entityId is required and must be an integer', [$idx])],
                    400
                );
            }

            if (isset($override['reason']) === true && is_string($override['reason']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('acknowledgedOverrides[%s].reason must be a string', [$idx])],
                    400
                );
            }
        }//end foreach

        return null;

    }//end validateAcknowledgedOverrides()

    /**
     * Filter entities by excluded types
     *
     * @param array<int, array<string, mixed>> $entities The entities
     * @param array<string, mixed>             $params   Request parameters
     *
     * @return array<int, array<string, mixed>> Filtered entities
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    private function filterByExcludeTypes(array $entities, array $params): array
    {
        $excludeTypes = $params['excludeTypes'] ?? [];
        if (is_array($excludeTypes) === false || empty($excludeTypes) === true) {
            return $entities;
        }

        return array_values(
            array_filter(
                $entities,
                static function (array $entity) use ($excludeTypes): bool {
                    $type = $entity['type'] ?? $entity['entityType'] ?? '';
                    return in_array($type, $excludeTypes, true) === false;
                }
            )
        );

    }//end filterByExcludeTypes()

    /**
     * Filter entities by minimum confidence threshold
     *
     * @param array<int, array<string, mixed>> $entities The entities
     * @param array<string, mixed>             $params   Request parameters
     *
     * @return array<int, array<string, mixed>> Filtered entities
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    private function filterByConfidence(array $entities, array $params): array
    {
        if (isset($params['minConfidence']) === false) {
            return $entities;
        }

        $minConfidence = (float) $params['minConfidence'];

        return array_values(
            array_filter(
                $entities,
                static function (array $entity) use ($minConfidence): bool {
                    return (float) ($entity['confidence'] ?? 0.0) >= $minConfidence;
                }
            )
        );

    }//end filterByConfidence()
}//end class

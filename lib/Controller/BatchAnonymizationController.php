<?php
/**
 * Batch Anonymization Controller
 *
 * HTTP entry points for the multi-file anonymization workflow: uploading
 * a batch (or adopting a folder), kicking off extraction, inspecting the
 * consolidated entity list, applying the user-approved replacements, and
 * downloading the final CSV report. Also exposes the WOO entity profile
 * used to decide which entity types get anonymized by default.
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
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-6
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-7
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-8
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-9
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-10
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-11
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\BatchAnonymizeService;
use OCA\DocuDesk\Service\BatchExtractionService;
use OCA\DocuDesk\Service\BatchReportService;
use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\BatchUploadService;
use OCA\DocuDesk\Service\EntityConsolidationService;
use OCA\DocuDesk\Service\FolderBatchService;
use OCA\DocuDesk\Service\WooProfileService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller that wires the batch-anonymization routes to their service layer.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */
class BatchAnonymizationController extends Controller
{
    /**
     * Constructor for BatchAnonymizationController
     *
     * @param string                     $appName            App name passed through to the base Controller.
     * @param IRequest                   $request            Current HTTP request.
     * @param LoggerInterface            $logger             Logger used by the err() helper for failure reporting.
     * @param BatchStateService          $stateService       Service that stores and loads batch records.
     * @param BatchUploadService         $uploadService      Service that persists uploaded files into a new batch.
     * @param BatchExtractionService     $extractService     Service that drives per-file entity extraction.
     * @param BatchAnonymizeService      $anonService        Service that applies approved entities across a batch.
     * @param BatchReportService         $reportService      Service that produces the per-batch CSV report.
     * @param EntityConsolidationService $entityService      Service that merges per-file entity detections into one list.
     * @param WooProfileService          $profileService     Service that stores the WOO entity profile.
     * @param FolderBatchService         $folderBatchService Service that turns an existing folder into a batch.
     * @param IL10N                      $l10n               Translator for user-facing error messages.
     * @param IAppConfig                 $appConfig          Tenant configuration provider (reads
     *                                                       docudesk.anonymisation.default_output_format).
     * @param IUserSession               $userSession        User session for authentication.
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly BatchStateService $stateService,
        private readonly BatchUploadService $uploadService,
        private readonly BatchExtractionService $extractService,
        private readonly BatchAnonymizeService $anonService,
        private readonly BatchReportService $reportService,
        private readonly EntityConsolidationService $entityService,
        private readonly WooProfileService $profileService,
        private readonly FolderBatchService $folderBatchService,
        private readonly IL10N $l10n,
        private readonly IAppConfig $appConfig,
        private readonly IUserSession $userSession,
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Tenant config key for default outputFormat. Mirrors the constant
     * used by AnonymizationController; defined here so both controllers
     * stay aligned on the lookup key.
     */
    private const DEFAULT_OUTPUT_FORMAT_KEY = 'docudesk.anonymisation.default_output_format';


    /**
     * Supported values for the `outputFormat` request param.
     */
    private const VALID_OUTPUT_FORMATS = ['pdf', 'preserve'];

    /**
     * Accept a multipart upload and create a new anonymization batch.
     *
     * @return JSONResponse Batch metadata (id, file count, per-file entries) or an error payload.
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
     */
    public function batchUpload(): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            $files = $this->uploadService->collectFiles($this->request);
            if (empty($files) === true) {
                return new JSONResponse(['error' => $this->l10n->t('No files uploaded')], 400);
            }

            if (count($files) > $this->stateService->getMaxFiles()) {
                return new JSONResponse(['error' => $this->l10n->t('Batch size exceeds maximum')], 400);
            }

            $batch = $this->uploadService->processBatchUpload($this->uploadService->getUserId(), $files);
            return new JSONResponse(
                [
                    'batchId'   => $batch['batchId'],
                    'fileCount' => count($batch['files']),
                    'files'     => $batch['files'],
                ]
            );
        } catch (Exception $e) {
            return $this->err(msg: 'Batch upload failed', e: $e);
        }//end try

    }//end batchUpload()

    /**
     * Create a folder-based batch from either folderId or folderPath.
     *
     * @return JSONResponse Batch metadata or an error payload.
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
     */
    public function folderBatch(): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            $folderId   = self::coerceFolderId(raw: $this->request->getParam('folderId'));
            $folderPath = self::coerceFolderPath(raw: $this->request->getParam('folderPath', ''));

            $validationError = $this->validateFolderParams(folderId: $folderId, folderPath: $folderPath);
            if ($validationError !== null) {
                return $validationError;
            }

            $batch = $this->folderBatchService->createFolderBatch(
                folderId: $folderId,
                folderPath: $folderPath
            );

            return new JSONResponse(
            [
                'batchId'    => $batch['batchId'],
                'folderId'   => $batch['folderId'],
                'folderPath' => $batch['folderPath'],
                'fileCount'  => count($batch['files']),
                'files'      => $batch['files'],
            ]
            );
        } catch (Exception $e) {
            return $this->err(msg: 'Folder batch failed', e: $e);
        }//end try

    }//end folderBatch()

    /**
     * Extract entities from the next pending file in a batch.
     *
     * @param string $batchId Identifier of the batch to advance.
     *
     * @return JSONResponse Per-file extraction result, or an error payload.
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-6
     */
    public function batchExtract(string $batchId): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            return new JSONResponse($this->extractService->extractNext($batchId));
        } catch (Exception $e) {
            return $this->err(msg: 'Extraction failed', e: $e);
        }

    }//end batchExtract()

    /**
     * Return progress, per-file status, and total entity count for a batch.
     *
     * @param string $batchId Identifier of the batch to inspect.
     *
     * @return JSONResponse Batch status snapshot, or 404 when the batch is unknown.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-7
     */
    public function batchStatus(string $batchId): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        $batch = $this->stateService->getBatch($batchId);
        if ($batch === null) {
            return new JSONResponse(['error' => $this->l10n->t('Batch not found')], 404);
        }

        $ent = 0;
        $ext = 0;
        foreach ($batch['files'] as $f) {
            $ent += ($f['entityCount'] ?? 0);
            if (in_array($f['status'], ['extracted', 'anonymized', 'error'], true) === true) {
                $ext++;
            }
        }

        $total = count($batch['files']);
        $prog  = 0;
        if ($total > 0) {
            $prog = round(($ext / $total) * 100, 1);
        }

        return new JSONResponse(
                [
                    'batchId'       => $batch['batchId'],
                    'batchStatus'   => $batch['status'],
                    'files'         => $batch['files'],
                    'totalEntities' => $ent,
                    'progress'      => $prog,
                    'totalFiles'    => $total,
                ]
                );

    }//end batchStatus()

    /**
     * Return the consolidated entity list for a batch once extraction has started.
     *
     * Accepts an optional `minConfidence` query parameter; entities below the
     * threshold are returned but flagged as not-included so the UI can still
     * surface them for manual review.
     *
     * @param string $batchId Identifier of the batch whose entities should be returned.
     *
     * @return JSONResponse Consolidated entity list plus progress metadata, or an error payload.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-8
     */
    public function batchEntities(string $batchId): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            $batch = $this->stateService->getBatch($batchId);
            if ($batch === null) {
                return new JSONResponse(['error' => 'Batch not found'], 404);
            }

            if (in_array($batch['status'], ['extracting', 'review'], true) === false) {
                return new JSONResponse(['error' => $this->l10n->t('Extraction has not started')], 409);
            }

            $minConfidence  = (float) ($this->request->getParam('minConfidence', '0.0'));
            $entities       = $this->entityService->consolidateEntities($batch, $minConfidence);
            $filesProcessed = 0;
            foreach ($batch['files'] as $f) {
                if (in_array($f['status'], ['extracted', 'error'], true) === true) {
                    $filesProcessed++;
                }
            }

            return new JSONResponse(
                    [
                        'entities'       => $entities,
                        'entityCount'    => count($entities),
                        'complete'       => $batch['status'] === 'review',
                        'filesProcessed' => $filesProcessed,
                    ]
                    );
        } catch (Exception $e) {
            return $this->err(msg: 'Failed to get entities', e: $e);
        }//end try

    }//end batchEntities()

    /**
     * Apply the user-approved entity list to every extracted file in a batch.
     *
     * Stray `bases[]` fields on entity entries are silently ignored (per 2026-05-12
     * explore-mode rework); bases are set via OR's PATCH /api/entity-relations/{id}.
     * Accepts an optional `appendBasisSummary` boolean flag (default false).
     * When true, invokes the grondslagen summary service after each file's
     * anonymization. Per-file summary failures surface as per-file warnings
     * in the response; the overall batch still completes as HTTP 200.
     *
     * @param string $batchId Identifier of the batch to anonymize.
     *
     * @return JSONResponse Summary of the run, or an error payload when the request body is malformed.
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-9
     */
    public function batchAnonymize(string $batchId): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            $params   = $this->request->getParams();
            $entities = $params['entities'] ?? [];
            if (is_array($entities) === false || empty($entities) === true) {
                return new JSONResponse(['error' => $this->l10n->t('No entities provided')], 400);
            }

            $appendBasisSummary = false;
            if (array_key_exists('appendBasisSummary', $params) === true) {
                $appendBasisSummary = $params['appendBasisSummary'];
                if (is_bool($appendBasisSummary) === false) {
                    return new JSONResponse(
                        ['error' => $this->l10n->t('appendBasisSummary must be a boolean')],
                        400
                    );
                }
            }

            // Detect stray bases fields for ignoredFields hint (GDPR accountability).
            $hasStrayBases = false;
            foreach ($entities as $entity) {
                if (is_array($entity) === true && array_key_exists('bases', $entity) === true) {
                    $hasStrayBases = true;
                    break;
                }
            }

            $unredactedEntities = $params['unredactedEntities'] ?? [];
            if (is_array($unredactedEntities) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('unredactedEntities must be an array')],
                    400
                );
            }

            // Anonymise-output-as-pdf-by-default: per-batch outputFormat.
            // Per-call value overrides tenant default; missing/invalid
            // values mirror AnonymizationController semantics.
            $outputFormat = $this->resolveOutputFormat(params: $params);
            if ($outputFormat === null) {
                return new JSONResponse(
                    [
                        'error' => sprintf(
                            'Invalid outputFormat: must be one of %s',
                            implode(', ', self::VALID_OUTPUT_FORMATS)
                        ),
                    ],
                    400
                );
            }

            if (empty($unredactedEntities) === false) {
                $unredactedError = $this->validateUnredactedEntities(entries: $unredactedEntities);
                if ($unredactedError !== null) {
                    return $unredactedError;
                }
            }

            $batchResult = $this->anonService->anonymizeBatch(
                batchId: $batchId,
                entities: $entities,
                appendBasisSummary: $appendBasisSummary,
                unredactedEntities: $unredactedEntities,
                outputFormat: $outputFormat
            );

            if ($hasStrayBases === true) {
                $batchResult['ignoredFields'] = ['bases'];
            }

            $httpStatus = $this->resolveBatchHttpStatus(result: $batchResult);
            return new JSONResponse($batchResult, $httpStatus);
        } catch (Exception $e) {
            return $this->err(msg: 'Anonymization failed', e: $e);
        }//end try

    }//end batchAnonymize()

    /**
     * Resolve HTTP status for a batch anonymization result.
     *
     * HTTP 200 — all files processed without prohibition failures.
     * HTTP 207 — some files had per-file 422 prohibition violations; others succeeded.
     * HTTP 422 — every processed file had a prohibition violation (none succeeded).
     *
     * @param array<string, mixed> $result Batch anonymization result
     *
     * @return int HTTP status code
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
     */
    private function resolveBatchHttpStatus(array $result): int
    {
        $prohibitionFiles = (int) ($result['prohibitionSkippedFiles'] ?? 0);
        $processed        = (int) ($result['processedFiles'] ?? 0);
        $total            = (int) ($result['totalFiles'] ?? 0);

        if ($prohibitionFiles === 0) {
            return 200;
        }

        if ($processed === 0 && $prohibitionFiles === $total) {
            return 422;
        }

        return 207;

    }//end resolveBatchHttpStatus()

    /**
     * Resolve the effective `outputFormat` for this batch call.
     *
     * Per-batch value overrides tenant default; tenant default defaults
     * to `"pdf"`. Returns null when an invalid per-call value was
     * supplied; the caller maps that to HTTP 400.
     *
     * @param array<string,mixed> $params Request params.
     *
     * @return string|null Resolved outputFormat or null on invalid input.
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
            'pdf'
        );

        if (in_array($tenantDefault, self::VALID_OUTPUT_FORMATS, true) === false) {
            return 'pdf';
        }

        return $tenantDefault;

    }//end resolveOutputFormat()

    /**
     * Produce the CSV anonymization report for a batch as a file download.
     *
     * @param string $batchId Identifier of the batch to report on.
     *
     * @return JSONResponse|DataDownloadResponse CSV download on success, JSON error payload on failure.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-10
     */
    public function batchReport(string $batchId): JSONResponse|DataDownloadResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            $csv = $this->reportService->generateReport($batchId);
            return new DataDownloadResponse($csv, 'anonymization-report-'.$batchId.'.csv', 'text/csv');
        } catch (Exception $e) {
            return $this->err(msg: $e->getMessage(), e: $e);
        }

    }//end batchReport()

    /**
     * Return the active WOO anonymization profile.
     *
     * @return JSONResponse Profile with `anonymize` and `keep` entity-type arrays.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-11
     */
    public function getProfiles(): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        return new JSONResponse($this->profileService->getProfile());

    }//end getProfiles()

    /**
     * Persist a new WOO anonymization profile from the request body.
     *
     * @return JSONResponse Success message, or an error payload when the body is malformed.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-11
     */
    public function updateProfiles(): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            $params = $this->request->getParams();
            if (is_array($params['anonymize'] ?? null) === false || is_array($params['keep'] ?? null) === false) {
                return new JSONResponse(['error' => 'Invalid format'], 400);
            }

            $this->profileService->saveProfile(['anonymize' => $params['anonymize'], 'keep' => $params['keep']]);
            return new JSONResponse(['message' => 'Profile updated']);
        } catch (Exception $e) {
            return $this->err(msg: 'Failed to update profile', e: $e);
        }

    }//end updateProfiles()

    /**
     * Validate the structure of each unredactedEntities[] entry.
     *
     * Mirrors AnonymizationController::validateUnredactedEntities so that
     * the batch endpoint rejects malformed payloads with HTTP 400 before
     * forwarding to the service layer.
     *
     * @param array<int, mixed> $entries The unredactedEntities array from the request
     *
     * @return JSONResponse|null HTTP 400 on the first invalid entry, null when all valid
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
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
     * Build a JSON error response, logging the underlying exception.
     *
     * Exception codes outside the HTTP error range (400..599) are normalized
     * to 500 so the client always receives a valid status.
     *
     * @param string    $msg Human-readable description of what failed.
     * @param Exception $e   Exception captured at the controller boundary.
     *
     * @return JSONResponse Error payload with an appropriate HTTP status.
     *
     * @psalm-suppress InvalidArgument $code is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
     */
    private function err(string $msg, Exception $e): JSONResponse
    {
        $code = (int) $e->getCode();
        if ($code < 400 || $code >= 600) {
            $code = 500;
        }

        $this->logger->error($msg.': '.$e->getMessage(), ['exception' => $e]);

        return new JSONResponse(['error' => $msg.': '.$e->getMessage()], $code);

    }//end err()

    /**
     * Coerce the raw folderId request param to an int, or null when absent/empty.
     *
     * @param mixed $raw Raw param value from the request.
     *
     * @return int|null Integer folder ID, or null when the caller did not supply one.
     */
    private static function coerceFolderId(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return (int) $raw;

    }//end coerceFolderId()

    /**
     * Coerce the raw folderPath request param to a string, or null when absent/empty.
     *
     * @param mixed $raw Raw param value from the request.
     *
     * @return string|null Path string, or null when the caller did not supply one.
     */
    private static function coerceFolderPath(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return (string) $raw;

    }//end coerceFolderPath()

    /**
     * Validate XOR between folderId and folderPath at the controller boundary.
     *
     * @param int|null    $folderId   Coerced folder ID.
     * @param string|null $folderPath Coerced folder path.
     *
     * @return JSONResponse|null Error response when validation fails, null when OK.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
     */
    private function validateFolderParams(?int $folderId, ?string $folderPath): ?JSONResponse
    {
        if ($folderId === null && $folderPath === null) {
            return new JSONResponse(
                ['error' => $this->l10n->t('Either folderId or folderPath must be provided')],
                400
            );
        }

        if ($folderId !== null && $folderPath !== null) {
            return new JSONResponse(
                ['error' => $this->l10n->t('Provide only one of folderId or folderPath')],
                400
            );
        }

        return null;

    }//end validateFolderParams()
}//end class

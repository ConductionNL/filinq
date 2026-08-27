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
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-completion-report
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\BatchAnonymizeService;
use OCA\Filinq\Service\BatchExtractionService;
use OCA\Filinq\Service\BatchReportService;
use OCA\Filinq\Service\BatchRequestService;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\BatchUploadService;
use OCA\Filinq\Service\EntityConsolidationService;
use OCA\Filinq\Service\FolderBatchService;
use OCA\Filinq\Service\WooProfileService;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
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
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class BatchAnonymizationController extends Controller {

	/**
	 * Request/response shaping helpers for the batch endpoints: body
	 * validation, folder-parameter coercion, progress aggregation and the
	 * multi-status mapping.
	 *
	 * @var BatchRequestService
	 */
	private readonly BatchRequestService $batchRequest;

	/**
	 * Constructor for BatchAnonymizationController
	 *
	 * @param string $appName App name passed through to the base Controller.
	 * @param IRequest $request Current HTTP request.
	 * @param LoggerInterface $logger Logger used by the err() helper for failure reporting.
	 * @param BatchStateService $stateService Service that stores and loads batch records.
	 * @param BatchUploadService $uploadService Service that persists uploaded files into a new batch.
	 * @param BatchExtractionService $extractService Service that drives per-file entity extraction.
	 * @param BatchAnonymizeService $anonService Service that applies approved entities across a batch.
	 * @param BatchReportService $reportService Service that produces the per-batch CSV report.
	 * @param EntityConsolidationService $entityService Service that merges per-file entity detections into one list.
	 * @param WooProfileService $profileService Service that stores the WOO entity profile.
	 * @param FolderBatchService $folderBatchService Service that turns an existing folder into a batch.
	 * @param IL10N $l10n Translator for user-facing error messages.
	 * @param IAppConfig $appConfig Tenant configuration provider (reads
	 *                              filinq.anonymisation.default_output_format).
	 * @param IUserSession $userSession User session for authentication.
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

		$this->batchRequest = new BatchRequestService(l10n: $this->l10n);

	}//end __construct()

	/**
	 * Tenant config key for default outputFormat. Mirrors the constant
	 * used by AnonymizationController; defined here so both controllers
	 * stay aligned on the lookup key.
	 */
	private const DEFAULT_OUTPUT_FORMAT_KEY = 'filinq.anonymisation.default_output_format';

	/**
	 * Supported values for the `outputFormat` request param.
	 *
	 * - `pdf-only` (default): convert to PDF and delete the native
	 *   anonymised intermediate so only the PDF remains.
	 * - `pdf`: convert to PDF but keep the native intermediate too.
	 * - `preserve`: skip conversion; native format is the only output.
	 */
	private const VALID_OUTPUT_FORMATS = ['pdf-only', 'pdf', 'preserve'];

	/**
	 * Accept a multipart upload and create a new anonymization batch.
	 *
	 * @return JSONResponse Batch metadata (id, file count, per-file entries) or an error payload.
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
	 */
	public function batchUpload(): JSONResponse {
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
					'batchId' => $batch['batchId'],
					'fileCount' => count($batch['files']),
					'files' => $batch['files'],
				]
			);
		} catch (\Throwable $e) {
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
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
	 *
	 * @no-admin-idor-exempt ownership is enforced one layer down.
	 * Every path here loads the batch through BatchStateService::getBatch(),
	 * which compares the record's userId against the session user and returns
	 * null for a mismatch (admins excepted for support). It returns null
	 * rather than throwing precisely so a denied read is indistinguishable
	 * from a missing one — both answer 404 — so a caller cannot even confirm
	 * another user's batch exists.
	 */
	public function folderBatch(): JSONResponse {
		try {
			if ($this->userSession->getUser() === null) {
				return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
			}

			$folder = $this->batchRequest->resolveFolderParams(
				rawFolderId: $this->request->getParam('folderId'),
				rawFolderPath: $this->request->getParam('folderPath', '')
			);
			if ($folder['error'] !== null) {
				return new JSONResponse($folder['error']['body'], $folder['error']['status']);
			}

			$batch = $this->folderBatchService->createFolderBatch(
				folderId: $folder['folderId'],
				folderPath: $folder['folderPath']
			);

			return new JSONResponse(
				[
					'batchId' => $batch['batchId'],
					'folderId' => $batch['folderId'],
					'folderPath' => $batch['folderPath'],
					'fileCount' => count($batch['files']),
					'files' => $batch['files'],
				]
			);
		} catch (\Throwable $e) {
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
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
	 *
	 * @no-admin-idor-exempt ownership is enforced one layer down.
	 * Every path here loads the batch through BatchStateService::getBatch(),
	 * which compares the record's userId against the session user and returns
	 * null for a mismatch (admins excepted for support). It returns null
	 * rather than throwing precisely so a denied read is indistinguishable
	 * from a missing one — both answer 404 — so a caller cannot even confirm
	 * another user's batch exists.
	 */
	public function batchExtract(string $batchId): JSONResponse {
		try {
			if ($this->userSession->getUser() === null) {
				return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
			}

			return new JSONResponse($this->extractService->extractNext($batchId));
		} catch (\Throwable $e) {
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
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
	 */
	public function batchStatus(string $batchId): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
		}

		$batch = $this->stateService->getBatch($batchId);
		if ($batch === null) {
			return new JSONResponse(['error' => $this->l10n->t('Batch not found')], 404);
		}

		$summary = $this->batchRequest->summariseBatch(batch: $batch);

		return new JSONResponse(
			[
				'batchId' => $batch['batchId'],
				'batchStatus' => $batch['status'],
				'files' => $batch['files'],
				'totalEntities' => $summary['totalEntities'],
				'progress' => $summary['progress'],
				'totalFiles' => $summary['totalFiles'],
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
	 * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
	 */
	public function batchEntities(string $batchId): JSONResponse {
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

			$minConfidence = (float)($this->request->getParam('minConfidence', '0.0'));
			$entities = $this->entityService->consolidateEntities($batch, $minConfidence);

			return new JSONResponse(
				[
					'entities' => $entities,
					'entityCount' => count($entities),
					'complete' => $batch['status'] === 'review',
					'filesProcessed' => $this->batchRequest->countProcessedFiles(batch: $batch),
				]
			);
		} catch (\Throwable $e) {
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
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 *
	 * @no-admin-idor-exempt ownership is enforced one layer down.
	 * Every path here loads the batch through BatchStateService::getBatch(),
	 * which compares the record's userId against the session user and returns
	 * null for a mismatch (admins excepted for support). It returns null
	 * rather than throwing precisely so a denied read is indistinguishable
	 * from a missing one — both answer 404 — so a caller cannot even confirm
	 * another user's batch exists.
	 */
	public function batchAnonymize(string $batchId): JSONResponse {
		try {
			if ($this->userSession->getUser() === null) {
				return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
			}

			$params = $this->request->getParams();
			$validated = $this->batchRequest->validateBody(params: $params);
			if ($validated['error'] !== null) {
				return new JSONResponse($validated['error']['body'], $validated['error']['status']);
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

			$unredactedError = $this->batchRequest->validateUnredactedEntries(
				entries: $validated['request']['unredactedEntities']
			);
			if ($unredactedError !== null) {
				return new JSONResponse($unredactedError['body'], $unredactedError['status']);
			}

			$batchResult = $this->runBatchAnonymize(
				batchId: $batchId,
				request: $validated['request'],
				outputFormat: $outputFormat,
				scope: $this->batchRequest->resolveScope(params: $params)
			);

			if ($validated['request']['hasStrayBases'] === true) {
				$batchResult['ignoredFields'] = ['bases'];
			}

			return new JSONResponse($batchResult, $this->batchRequest->resolveHttpStatus(result: $batchResult));
		} catch (\Throwable $e) {
			return $this->err(msg: 'Anonymization failed', e: $e);
		}//end try

	}//end batchAnonymize()

	/**
	 * Dispatch to the batch entry point this request asked for.
	 *
	 * `appendBasisSummary` selects between the plain and the summary-producing
	 * BatchAnonymizeService entry point; every other value is forwarded verbatim.
	 *
	 * @param string $batchId Identifier of the batch to anonymize.
	 * @param array<string, mixed> $request The validated request body.
	 * @param string $outputFormat Resolved per-batch output format.
	 * @param string $scope Resolved placeholder-numbering scope.
	 *
	 * @return array<string, mixed> The batch run summary.
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-7
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	private function runBatchAnonymize(
		string $batchId,
		array $request,
		string $outputFormat,
		string $scope,
	): array {
		if ($request['appendBasisSummary'] === true) {
			return $this->anonService->anonymizeBatchWithBasisSummary(
				batchId: $batchId,
				entities: $request['entities'],
				unredactedEntities: $request['unredactedEntities'],
				outputFormat: $outputFormat,
				scope: $scope
			);
		}

		return $this->anonService->anonymizeBatch(
			batchId: $batchId,
			entities: $request['entities'],
			unredactedEntities: $request['unredactedEntities'],
			outputFormat: $outputFormat,
			scope: $scope
		);

	}//end runBatchAnonymize()

	/**
	 * Resolve the effective `outputFormat` for this batch call.
	 *
	 * Per-batch value overrides tenant default; tenant default defaults
	 * to `"pdf-only"`. Returns null when an invalid per-call value was
	 * supplied; the caller maps that to HTTP 400.
	 *
	 * @param array<string,mixed> $params Request params.
	 *
	 * @return string|null Resolved outputFormat or null on invalid input.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	private function resolveOutputFormat(array $params): ?string {
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
			'filinq',
			self::DEFAULT_OUTPUT_FORMAT_KEY,
			'pdf-only'
		);

		if (in_array($tenantDefault, self::VALID_OUTPUT_FORMATS, true) === false) {
			return 'pdf-only';
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
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-completion-report
	 *
	 * @no-admin-idor-exempt ownership is enforced one layer down.
	 * Every path here loads the batch through BatchStateService::getBatch(),
	 * which compares the record's userId against the session user and returns
	 * null for a mismatch (admins excepted for support). It returns null
	 * rather than throwing precisely so a denied read is indistinguishable
	 * from a missing one — both answer 404 — so a caller cannot even confirm
	 * another user's batch exists.
	 */
	public function batchReport(string $batchId): JSONResponse|DataDownloadResponse {
		try {
			if ($this->userSession->getUser() === null) {
				return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
			}

			$csv = $this->reportService->generateReport($batchId);
			return new DataDownloadResponse($csv, 'anonymization-report-' . $batchId . '.csv', 'text/csv');
		} catch (\Throwable $e) {
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
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
	 */
	public function getProfiles(): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
		}

		return new JSONResponse($this->profileService->getProfile());
	}//end getProfiles()

	/**
	 * Persist a new WOO anonymization profile from the request body.
	 *
	 * ADMIN-ONLY, DELIBERATELY. The profile this writes is INSTANCE-WIDE: it
	 * lands in `IAppConfig` under `filinq_woo_entity_profiles`
	 * ({@see WooProfileService::saveProfile()}), and
	 * {@see EntityConsolidationService} reads it to decide which entity types
	 * are redacted for EVERY user. Moving `PERSON` / `BSN` / `EMAIL` out of the
	 * `anonymize` set therefore leaves other people's PII in place on every
	 * subsequent run.
	 *
	 * ⚠️ The obvious way to satisfy a "missing auth attribute" finding here —
	 * copying the sibling `getProfiles()`'s user-level annotation onto this
	 * method — WOULD INTRODUCE EXACTLY THAT VULNERABILITY. The two are not
	 * symmetric: reading the profile is a user-level concern, writing it is
	 * instance policy. Until this attribute existed the endpoint was admin-only
	 * only by ACCIDENT (Nextcloud defaults an unannotated method to admin), and
	 * the body's lone `getUser() === null` check reads as though user-level
	 * access were intended, which is what makes the wrong fix look right.
	 *
	 * `#[AuthorizedAdminSetting]` matches how every other instance-wide write in
	 * this app declares itself ({@see SettingsController},
	 * {@see AnonymiserWarningController}).
	 *
	 * The null-session check below is retained as defence in depth, not as the
	 * authorization boundary — the attribute is the boundary.
	 *
	 * @return JSONResponse Success message, or an error payload when the body is malformed.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function updateProfiles(): JSONResponse {
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
		} catch (\Throwable $e) {
			return $this->err(msg: 'Failed to update profile', e: $e);
		}

	}//end updateProfiles()

	/**
	 * Build a JSON error response, logging the underlying exception.
	 *
	 * Exception codes outside the HTTP error range (400..599) are normalized
	 * to 500 so the client always receives a valid status.
	 *
	 * @param string $msg Human-readable description of what failed.
	 * @param \Throwable $e Throwable captured at the controller boundary.
	 *
	 * @return JSONResponse Error payload with an appropriate HTTP status.
	 *
	 * @psalm-suppress InvalidArgument $code is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	private function err(string $msg, \Throwable $e): JSONResponse {
		$code = (int)$e->getCode();
		if ($code < 400 || $code >= 600) {
			$code = 500;
		}

		$this->logger->error($msg . ': ' . $e->getMessage(), ['exception' => $e]);

		return new JSONResponse(['error' => $msg . ': ' . $e->getMessage()], $code);
	}//end err()
}//end class

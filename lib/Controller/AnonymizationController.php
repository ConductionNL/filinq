<?php

/**
 * Anonymization Controller
 *
 * Controller for the document anonymization pipeline.
 * Provides endpoints for uploading files, extracting/detecting entities,
 * and anonymizing documents.
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
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Exception\RedactionNotReviewedException;
use OCA\Filinq\Kernel\AnonymizeCall;
use OCA\Filinq\Kernel\Contracts\AnonymizationServiceInterface;
use OCA\Filinq\Kernel\Contracts\AnonymizeRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\FileListingServiceInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for anonymization pipeline endpoints
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The thirteenth type is
 * RedactionNotReviewedException, caught here so a document nobody checked is
 * answered with a 409 the reviewer can act on instead of falling through to
 * the catch-all 500. The fourteenth is AnonymizeCall, the one value a
 * validated request is run with.
 */
class AnonymizationController extends Controller {

	/**
	 * Constructor for AnonymizationController
	 *
	 * @param string $appName The application name
	 * @param IRequest $request The request object
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param AnonymizationServiceInterface $anonymizationService Service for anonymization operations
	 * @param FileListingServiceInterface $fileListingService Service for file listing operations
	 * @param IL10N $l10n The localization service
	 * @param IAppConfig $appConfig Tenant configuration provider (reads
	 *                              filinq.anonymisation.default_output_format)
	 * @param AnonymizeRequestServiceInterface $anonymizeRequest Workflow behind the anonymize endpoints:
	 *                                                          authentication, file access, body validation,
	 *                                                          the prohibition guards and the anonymisation call.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly LoggerInterface $logger,
		private readonly AnonymizationServiceInterface $anonymizationService,
		private readonly FileListingServiceInterface $fileListingService,
		private readonly IL10N $l10n,
		private readonly IAppConfig $appConfig,
		private readonly AnonymizeRequestServiceInterface $anonymizeRequest,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Default-output-format tenant config key. The per-call
	 * `outputFormat` request param overrides this when supplied.
	 */
	private const DEFAULT_OUTPUT_FORMAT_KEY = 'filinq.anonymisation.default_output_format';

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
	 * Returns files from the user's Filinq folder with their
	 * entity detection counts and anonymization status.
	 *
	 * @return JSONResponse JSON response with array of file data
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 *
	 * @no-admin-idor-exempt no id is taken. FileListingService::listProcessedFiles() lists the Filinq
	 * folder inside the caller's own user folder.
	 */
	public function files(): JSONResponse {
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
				'Failed to list processed files: ' . $e->getMessage(),
				['exception' => $e]
			);
			return new JSONResponse(
				['error' => $this->l10n->t('Failed to list processed files: %s', [$e->getMessage()])],
				$statusCode
			);
		}//end try

	}//end files()

	/**
	 * Upload a file to the user's Filinq folder
	 *
	 * Reads the uploaded file from the request and saves it
	 * to the user's Filinq folder.
	 *
	 * @return JSONResponse JSON response with upload result
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 *
	 * @no-admin-idor-exempt no id is taken. FileUploadService::uploadFile() writes into the Filinq folder
	 * inside the caller's own user folder.
	 */
	public function upload(): JSONResponse {
		try {
			$authError = $this->anonymizeRequest->requireAuthenticated();
			if ($authError !== null) {
				return new JSONResponse($authError['body'], $authError['status']);
			}

			return $this->storeUpload();
		} catch (\Throwable $e) {
			return $this->uploadFailed(exception: $e);
		}

	}//end upload()

	/**
	 * Read the request's `file` part and store it in the caller's Filinq folder.
	 *
	 * @return JSONResponse The service's result, or a 400/500 refusal.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function storeUpload(): JSONResponse {
		$file = $this->request->getUploadedFile('file');

		$refusal = $this->uploadRefusal(file: $file);
		if ($refusal !== null) {
			return $refusal;
		}

		$fileContent = file_get_contents($file['tmp_name']);
		if ($fileContent === false) {
			return new JSONResponse(
				['error' => $this->l10n->t('Failed to read uploaded file')],
				500
			);
		}

		return new JSONResponse($this->fileListingService->uploadFile($file['name'], $fileContent));
	}//end storeUpload()

	/**
	 * Refuse a missing upload or a transfer PHP flagged as failed.
	 *
	 * @param mixed $file The uploaded `file` part: an array, or null when absent.
	 *
	 * @return JSONResponse|null A 400 refusal, or null when the upload can be read.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function uploadRefusal(mixed $file): ?JSONResponse {
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

		return null;
	}//end uploadRefusal()

	/**
	 * Log a failed upload and answer with its HTTP-range code, else 500.
	 *
	 * @param \Throwable $exception The failure.
	 *
	 * @return JSONResponse The error response.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function uploadFailed(\Throwable $exception): JSONResponse {
		$statusCode = 500;
		if ($exception->getCode() >= 400 && $exception->getCode() < 600) {
			$statusCode = $exception->getCode();
		}

		$this->logger->error(
			'Failed to upload file: ' . $exception->getMessage(),
			['exception' => $exception]
		);
		return new JSONResponse(
			['error' => $this->l10n->t('Failed to upload file: %s', [$exception->getMessage()])],
			$statusCode
		);

	}//end uploadFailed()

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
	 *
	 * @no-admin-idor-exempt AnonymizeRequestService::verifyFileAccess() in the method body refuses a file
	 * outside the caller's own user folder before anything is read.
	 */
	public function extract(int $fileId): JSONResponse {
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
				'Failed to extract and detect entities: ' . $e->getMessage(),
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
	 * @spec openspec/changes/archive/2026-10-30-anonymise-asks-for-the-review-mark/tasks.md#task-1
	 */
	public function anonymize(int $fileId): JSONResponse {
		try {
			$authError = $this->anonymizeRequest->requireAuthenticated();
			if ($authError !== null) {
				return $this->payloadResponse(payload: $authError);
			}

			// Validate request body BEFORE file-access checks so that malformed
			// input always yields HTTP 400 regardless of whether the file exists.
			$params = $this->request->getParams();
			$validated = $this->anonymizeRequest->validateBody(params: $params);
			if ($validated['error'] !== null) {
				return $this->payloadResponse(payload: $validated['error']);
			}

			$refusal = $this->accessOrProhibitionRefusal(
				fileId: $fileId,
				unredactedEntities: $validated['request']['unredactedEntities']
			);
			if ($refusal !== null) {
				return $this->payloadResponse(payload: $refusal);
			}

			// Optional `outputFormat`: the per-call value overrides the tenant
			// default, which itself defaults to 'pdf-only'.
			$outputFormat = $this->resolveOutputFormat(params: $params);
			if ($outputFormat === null) {
				return $this->invalidOutputFormatResponse();
			}

			return $this->runAnonymize(
				call: new AnonymizeCall(fileId: $fileId, params: $params, request: $validated['request'], outputFormat: $outputFormat)
			);
		} catch (\Throwable $e) {
			return $this->anonymizeFailedResponse(exception: $e);
		}//end try

	}//end anonymize()

	/**
	 * Log an unexpected failure of a run and answer 500 with its message.
	 *
	 * @param \Throwable $exception The failure.
	 *
	 * @return JSONResponse The response.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function anonymizeFailedResponse(\Throwable $exception): JSONResponse {
		$this->logger->error(
			'Failed to anonymize document: ' . $exception->getMessage(),
			['exception' => $exception]
		);
		return new JSONResponse(
			['error' => $this->l10n->t('Failed to anonymize document: %s', [$exception->getMessage()])],
			500
		);

	}//end anonymizeFailedResponse()

	/**
	 * A JSON response from a `{body, status}` pair of the request service.
	 *
	 * @param array{body: array<array-key, mixed>, status: int} $payload The response pair.
	 *
	 * @return JSONResponse The response.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function payloadResponse(array $payload): JSONResponse {
		return new JSONResponse($payload['body'], $payload['status']);

	}//end payloadResponse()

	/**
	 * Refuse a run on a file the caller cannot reach, or one that leaves a
	 * prohibited entity unredacted.
	 *
	 * The file access check MUST precede the prohibition oracle, so callers
	 * without access to the file cannot probe prohibition rules by supplying
	 * arbitrary entity names (OWASP A01:2021).
	 *
	 * @param int $fileId The Nextcloud file ID.
	 * @param array<array-key, mixed> $unredactedEntities The entities the run leaves in place.
	 *
	 * @return array{body: array<array-key, mixed>, status: int}|null The refusal, or null to go on.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
	 */
	private function accessOrProhibitionRefusal(int $fileId, array $unredactedEntities): ?array {
		$accessError = $this->anonymizeRequest->verifyFileAccess(fileId: $fileId);
		if ($accessError !== null) {
			return $accessError;
		}

		return $this->anonymizeRequest->checkUnredactedProhibitions(unredactedEntities: $unredactedEntities);

	}//end accessOrProhibitionRefusal()

	/**
	 * The 400 answer for an `outputFormat` outside the allowed values.
	 *
	 * @return JSONResponse The response.
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
	 */
	private function invalidOutputFormatResponse(): JSONResponse {
		return new JSONResponse(
			[
				'error' => $this->l10n->t(
					'Invalid outputFormat: must be one of %s',
					[implode(', ', self::VALID_OUTPUT_FORMATS)]
				),
				'allowedValues' => self::VALID_OUTPUT_FORMATS,
			],
			400
		);

	}//end invalidOutputFormatResponse()

	/**
	 * Run a validated anonymisation and answer with its result.
	 *
	 * A failed PDF conversion answers 422 (the run is rolled back) and a run
	 * nobody checked answers 409. Any other failure reaches the caller.
	 *
	 * @param AnonymizeCall $call The file, parameters, validated request and output format.
	 *
	 * @return JSONResponse The result.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 * @spec openspec/changes/archive/2026-10-30-anonymise-asks-for-the-review-mark/tasks.md#task-1
	 */
	private function runAnonymize(AnonymizeCall $call): JSONResponse {
		try {
			$result = $this->anonymizeRequest->executeAnonymize(
				call: $call,
				userId: $this->anonymizeRequest->currentUserId()
			);
		} catch (ConversionFailedException $e) {
			return $this->conversionFailedResponse(exception: $e, outputFormat: $call->outputFormat);
		} catch (RedactionNotReviewedException $e) {
			return $this->reviewRefusal(refusal: $e);
		}

		return new JSONResponse($result['body'], $result['status']);

	}//end runAnonymize()

	/**
	 * The 422 answer when the PDF conversion cascade is exhausted.
	 *
	 * @param ConversionFailedException $exception The conversion failure.
	 * @param string $outputFormat The requested output format.
	 *
	 * @return JSONResponse The response.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function conversionFailedResponse(ConversionFailedException $exception, string $outputFormat): JSONResponse {
		$this->logger->warning(
			'PDF conversion cascade exhausted; returning 422.',
			['attempts' => $exception->getAttempts()]
		);
		return new JSONResponse(
			[
				'error' => $this->l10n->t(
					'Conversion to PDF failed; anonymisation rolled back.'
				),
				'conversionAttempts' => $exception->getAttempts(),
				'outputFormat' => $outputFormat,
				'fallback' => $this->l10n->t(
					'Set outputFormat to "preserve" to bypass conversion if you must keep the native format.'
				),
			],
			422
		);

	}//end conversionFailedResponse()

	/**
	 * Answer a run nobody has checked with 409 and what to do about it.
	 *
	 * The document's state does not allow a copy yet: the reviewer marks the
	 * detection run checked and asks again. No file was written.
	 *
	 * @param RedactionNotReviewedException $refusal The gate's refusal.
	 *
	 * @return JSONResponse The 409 with `error`, `reviewRequired` and `document`.
	 *
	 * @spec openspec/changes/archive/2026-10-30-anonymise-asks-for-the-review-mark/tasks.md#task-1
	 */
	private function reviewRefusal(RedactionNotReviewedException $refusal): JSONResponse {
		$this->logger->info(
			$refusal->getMessage(),
			['document' => $refusal->getDocument(), 'reason' => $refusal->getReason()]
		);

		$instruction = $refusal->getInstruction();
		if ($instruction === '') {
			$instruction = $this->l10n->t('Check the detected data first');
		}

		return new JSONResponse(
			[
				'error' => $instruction,
				'reviewRequired' => $refusal->getReason(),
				'document' => $refusal->getDocument(),
			],
			409
		);

	}//end reviewRefusal()

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
	 *
	 * @no-admin-idor-exempt RelationSkipDecisionService::apply() refuses (404) a relation whose file is
	 * not in the caller's own user folder (requireRelationAccess()) before the decision is written.
	 */
	public function updateRelation(int $id): JSONResponse {
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
			// Malformed tenant setting falls back to spec default
			// rather than rejecting the call.
			return 'pdf-only';
		}

		return $tenantDefault;
	}//end resolveOutputFormat()
}//end class

<?php

/**
 * Correspondence Controller
 *
 * Controller for correspondence generation endpoints.
 * Provides single generation, batch generation, and job status queries.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-rest-endpoint
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-rest-endpoints
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-output-format-selection
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Exception\CorrespondenceSendRefused;
use OCA\Filinq\Kernel\Contracts\CorrespondenceServiceInterface;
use OCA\Filinq\Service\CorrespondenceSendService;
use OCA\Filinq\Service\ReadsCurrentUserId;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for correspondence generation endpoints
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-1
 */
class CorrespondenceController extends Controller {

	use ReadsCurrentUserId;
	use AnswersNotAuthenticated;

	/**
	 * Constructor for CorrespondenceController
	 *
	 * @param string $appName The application name
	 * @param IRequest $request The request object
	 * @param CorrespondenceServiceInterface $corrSvc Correspondence generation service
	 * @param IUserSession $userSession User session for auth info
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param IL10N $l10n The localization service
	 * @param CorrespondenceSendService $sender Sends a composed letter by email
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly CorrespondenceServiceInterface $corrSvc,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
		private readonly IL10N $l10n,
		private readonly CorrespondenceSendService $sender,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Generate a single correspondence document
	 *
	 * Accepts JSON body with:
	 * - templateId (string, required): UUID of the template to use
	 * - dataRefs (array, required): Data references with register/schema/id
	 * - options (object, optional): format, huisstijlId, caseReference
	 * - filename (string, optional): Download filename
	 *
	 * @return DataDownloadResponse|JSONResponse Generated document or error
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-rest-endpoint
	 */
	public function generate(): DataDownloadResponse|JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
			}

			$params = $this->parseGenerateParams();
			if ($params instanceof JSONResponse) {
				return $params;
			}

			$result = $this->corrSvc->generate(
				templateId: $params['templateId'],
				dataRefs: $params['dataRefs'],
				options: $params['options']
			);

			return $this->formatGenerateResponse(
				result: $result,
				filename: $params['filename']
			);
		} catch (Exception $e) {
			return $this->handleException(exception: $e);
		}//end try

	}//end generate()

	/**
	 * Parse and validate generation request parameters
	 *
	 * @return array|JSONResponse Parsed params or error response
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-rest-endpoint
	 */
	private function parseGenerateParams(): array|JSONResponse {
		$templateId = $this->request->getParam('templateId');
		$dataRefs = $this->request->getParam('dataRefs', []);
		$options = $this->request->getParam('options', []);
		$filename = $this->request->getParam('filename', 'correspondence.pdf');

		if (empty($templateId) === true) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('templateId is required')],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		if (empty($dataRefs) === true || is_array($dataRefs) === false) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('dataRefs is required and must be an array')],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		if (is_array($options) === false) {
			$options = [];
		}

		$options['userId'] = $this->currentUserId();

		return [
			'templateId' => $templateId,
			'dataRefs' => $dataRefs,
			'options' => $options,
			'filename' => $filename,
		];

	}//end parseGenerateParams()

	/**
	 * Format the generate response based on output format
	 *
	 * @param array $result The generation result
	 * @param string $filename The requested filename
	 *
	 * @return DataDownloadResponse|JSONResponse The formatted response
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-output-format-selection
	 */
	private function formatGenerateResponse(
		array $result,
		string $filename,
	): DataDownloadResponse|JSONResponse {
		$format = $result['format'];

		// For binary formats, return as download.
		if ($format === 'pdf' || $format === 'docx') {
			return $this->buildDownloadResponse(
				result: $result,
				format: $format,
				filename: $filename
			);
		}

		// For HTML/email formats, return as JSON with content.
		return new JSONResponse(
			data: [
				'content' => $result['content'],
				'format' => $format,
				'warnings' => $result['warnings'],
			],
			statusCode: Http::STATUS_OK
		);

	}//end formatGenerateResponse()

	/**
	 * Build a download response for binary document formats
	 *
	 * @param array $result The generation result
	 * @param string $format The output format (pdf or docx)
	 * @param string $filename The requested filename
	 *
	 * @return DataDownloadResponse The download response
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-rest-endpoint
	 */
	private function buildDownloadResponse(
		array $result,
		string $format,
		string $filename,
	): DataDownloadResponse {
		$extension = '.pdf';
		$contentType = 'application/pdf';
		if ($format === 'docx') {
			$extension = '.docx';
			$contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
		}

		if (str_ends_with($filename, $extension) === false) {
			$filename = pathinfo($filename, PATHINFO_FILENAME) . $extension;
		}

		return new DataDownloadResponse(
			data: $result['content'],
			filename: $filename,
			contentType: $contentType
		);

	}//end buildDownloadResponse()

	/**
	 * Generate correspondence for a batch of recipients
	 *
	 * Accepts JSON body with:
	 * - templateId (string, required): UUID of the template
	 * - recipientIds (array, required): Array of recipient object UUIDs
	 * - options (object, optional): format, register, schema, huisstijlId
	 *
	 * @return JSONResponse Batch results or job info
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-rest-endpoints
	 */
	public function generateBatch(): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
			}

			$templateId = $this->request->getParam('templateId');
			$recipientIds = $this->request->getParam('recipientIds', []);
			$options = $this->request->getParam('options', []);

			$refusal = $this->batchRefusal();
			if ($refusal !== null) {
				return $refusal;
			}

			if (is_array($options) === false) {
				$options = [];
			}

			$options['userId'] = $this->currentUserId();

			$result = $this->corrSvc->generateBatch(
				templateId: $templateId,
				recipientIds: $recipientIds,
				options: $options
			);

			return $this->batchResponse(result: $result);
		} catch (Exception $e) {
			return $this->handleException(exception: $e);
		}//end try

	}//end generateBatch()

	/**
	 * Refuse a batch request without a template or without a list of recipients.
	 *
	 * @return JSONResponse|null A 400 naming the missing field, or null.
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-rest-endpoints
	 */
	private function batchRefusal(): ?JSONResponse {
		if (empty($this->request->getParam('templateId')) === true) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('templateId is required')],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		$recipientIds = $this->request->getParam('recipientIds', []);
		if (empty($recipientIds) === true || is_array($recipientIds) === false) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('recipientIds is required and must be an array')],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		return null;
	}//end batchRefusal()

	/**
	 * Answer a batch run: 202 when it was queued as a job, else 200.
	 *
	 * @param array<string, mixed> $result The service's result.
	 *
	 * @return JSONResponse The answer.
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-rest-endpoints
	 */
	private function batchResponse(array $result): JSONResponse {
		if (isset($result['jobId']) === true) {
			return new JSONResponse(
				data: $result,
				statusCode: Http::STATUS_ACCEPTED
			);
		}

		return new JSONResponse(data: $result, statusCode: Http::STATUS_OK);
	}//end batchResponse()

	/**
	 * Get the status of a batch correspondence job
	 *
	 * @param string $jobId The job UUID
	 *
	 * @return JSONResponse The job status
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-rest-endpoints
	 */
	public function jobStatus(string $jobId): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
			}

			$status = $this->corrSvc->getJobStatus(jobId: $jobId);

			if ($status === null) {
				return new JSONResponse(
					data: ['error' => $this->l10n->t('Job not found')],
					statusCode: Http::STATUS_NOT_FOUND
				);
			}

			// C3 / SB1 security fix: enforce job ownership so an authenticated user
			// cannot poll another user's job by guessing or brute-forcing the jobId.
			// ownerUserId is stored at the top level of the status payload (persisted
			// by dispatchBatchJob and every storeJobStatus call in the background job).
			// The old check read options.userId which was NEVER persisted — SB1 fix.
			//
			// This test FAILS CLOSED. It previously read
			// `$jobUserId !== '' && $jobUserId !== $uid`, which let a status
			// payload carrying no owner through to any authenticated caller —
			// i.e. the one input an attacker benefits from was the one that
			// skipped the check. Every writer sets the key today
			// (CorrespondenceService::dispatchBatchJob and all three
			// BatchCorrespondenceJob::storeJobStatus calls, seeded from
			// `$options['userId']`, which this controller sets from the session
			// before dispatch), so refusing an empty owner costs nothing that
			// works — it only removes the fallback. Same shape as
			// PrintJobController::authorizeJobAccess(), which already compares
			// for equality rather than for inequality-when-present.
			$jobUserId = (string)($status['ownerUserId'] ?? '');
			if ($jobUserId !== $user->getUID()) {
				return new JSONResponse(
					data: ['error' => $this->l10n->t('This job belongs to another user')],
					statusCode: Http::STATUS_FORBIDDEN
				);
			}

			$status['jobId'] = $jobId;

			return new JSONResponse(data: $status, statusCode: Http::STATUS_OK);
		} catch (Exception $e) {
			return $this->handleException(exception: $e);
		}//end try

	}//end jobStatus()

	/**
	 * Send one composed letter by email to its recipient
	 *
	 * Accepts JSON body with:
	 * - templateId (string, required): UUID of the template to use
	 * - dataRefs (array, required): data references, the first being the recipient
	 * - options (object, optional): huisstijlId, caseReference, recipientType, attachPdf
	 *
	 * 201 when the letter went, 502 when the mail server refused it (logged as
	 * failed), 400/404/422 when it could not be sent at all (nothing logged as sent).
	 *
	 * @return JSONResponse What the send did
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md#requirement-a-composed-letter-can-be-sent-by-email-req-lcs-01
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-2
	 */
	#[NoAdminRequired]
	public function send(): JSONResponse {
		return $this->sending(
			action: function (string $templateId, array $dataRefs, array $options): JSONResponse {
				$result = $this->sender->send(templateId: $templateId, dataRefs: $dataRefs, options: $options);
				if ($result['status'] !== 'sent') {
					$result['error'] = $this->l10n->t('The letter was not sent: the mail server did not accept it.');
					return new JSONResponse(data: $result, statusCode: Http::STATUS_BAD_GATEWAY);
				}

				return new JSONResponse(data: $result, statusCode: Http::STATUS_CREATED);
			}
		);

	}//end send()

	/**
	 * The address and subject a letter would be sent with, for the confirm dialog
	 *
	 * Same body as {@see send()}; composes and sends nothing.
	 *
	 * @return JSONResponse {to, subject}, or the refusal send() would give
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-3
	 */
	#[NoAdminRequired]
	public function sendPreview(): JSONResponse {
		return $this->sending(
			action: function (string $templateId, array $dataRefs): JSONResponse {
				return new JSONResponse(data: $this->sender->preview(templateId: $templateId, dataRefs: $dataRefs));
			}
		);

	}//end sendPreview()

	/**
	 * Run a send action for the signed-in user, answering its refusals with their status.
	 *
	 * @param callable(string, array, array): JSONResponse $action The send or the preview.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-2
	 */
	private function sending(callable $action): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
		}

		$params = $this->parseGenerateParams();
		if ($params instanceof JSONResponse) {
			return $params;
		}

		$params['options']['attachPdf'] = filter_var(($params['options']['attachPdf'] ?? false), FILTER_VALIDATE_BOOLEAN);

		try {
			return $action($params['templateId'], $params['dataRefs'], $params['options']);
		} catch (CorrespondenceSendRefused $refused) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t($refused->getMessage())],
				statusCode: $refused->getCode()
			);
		} catch (Exception $e) {
			return $this->handleException(exception: $e);
		}

	}//end sending()

	/**
	 * Handle exceptions and return appropriate JSON error responses
	 *
	 * @param Exception $exception The exception to handle
	 *
	 * @return JSONResponse The error response
	 *
	 * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
	 */
	private function handleException(Exception $exception): JSONResponse {
		$statusCode = Http::STATUS_INTERNAL_SERVER_ERROR;
		$code = $exception->getCode();
		if ($code >= 400 && $code < 600) {
			$statusCode = $code;
		}

		$this->logger->error(
			message: 'Correspondence generation failed: ' . $exception->getMessage(),
			context: ['exception' => $exception]
		);

		return new JSONResponse(
			data: ['error' => $exception->getMessage()],
			statusCode: $statusCode
		);

	}//end handleException()
}//end class

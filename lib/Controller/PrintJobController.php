<?php

/**
 * Print Job Controller
 *
 * API controller for external print services to retrieve print-ready documents
 * and manage print job state. Exposes endpoints for creating, querying, downloading,
 * and acknowledging print jobs.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use InvalidArgumentException;
use OCA\Filinq\Service\PrintJobService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller providing the print job queue API for external print services
 *
 * External print services (Ricoh, Canon, etc.) call these endpoints to retrieve
 * print-ready PDFs and print instruction metadata, and to acknowledge print
 * status updates.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-5
 */
class PrintJobController extends Controller {

	/**
	 * Constructor for PrintJobController
	 *
	 * @param string $appName The application name
	 * @param IRequest $request The request object
	 * @param PrintJobService $printJobSvc Print job service
	 * @param IUserSession $userSession User session for authentication
	 * @param IGroupManager $groupManager Group manager for admin check
	 * @param LoggerInterface $logger Logger for error reporting
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly PrintJobService $printJobSvc,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Create a single-document print job
	 *
	 * Accepts JSON body with:
	 * - templateId (string, required): UUID of the template to use
	 * - data (object, optional): Data context for template rendering
	 * - options (object, optional): Print options (pdfa, cropMarks, duplex, color, etc.)
	 * - filename (string, optional): Desired download filename
	 *
	 * @return JSONResponse Job creation response with jobId, status, and printConfig
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-5
	 */
	public function create(): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => 'Not authenticated'],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$templateId = $this->request->getParam('templateId', '');
			if (empty($templateId) === true) {
				return new JSONResponse(
					data: ['error' => 'templateId is required'],
					statusCode: Http::STATUS_BAD_REQUEST
				);
			}

			$data = $this->request->getParam('data', []);
			$options = $this->request->getParam('options', []);
			$filename = $this->request->getParam('filename', 'document.pdf');

			if (is_array($data) === false) {
				$data = [];
			}

			if (is_array($options) === false) {
				$options = [];
			}

			$result = $this->printJobSvc->createJob(
				templateId: $templateId,
				data: $data,
				options: $options,
				userId: $user->getUID(),
				filename: (string)$filename
			);

			return new JSONResponse(data: $result, statusCode: Http::STATUS_CREATED);
		} catch (Exception $e) {
			$statusCode = 500;
			if ($e->getCode() >= 400 && $e->getCode() < 600) {
				$statusCode = $e->getCode();
			}

			$this->logger->error(
				message: 'Print job create failed: ' . $e->getMessage(),
				context: ['exception' => $e]
			);

			return new JSONResponse(
				data: ['error' => 'Operation failed'],
				statusCode: $statusCode
			);
		}//end try

	}//end create()

	/**
	 * List the caller's own print jobs, newest first.
	 *
	 * Nobody else's jobs are listed, admins included: the page is "my print
	 * jobs", and an admin reads another user's job by its id.
	 *
	 * @return JSONResponse {results: job[]} or an error
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.3
	 */
	public function index(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(
				data: ['error' => 'Not authenticated'],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		try {
			return new JSONResponse(data: ['results' => $this->printJobSvc->listJobs(userId: $user->getUID())]);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Print job list failed: ' . $e->getMessage(),
				context: ['exception' => $e]
			);

			return new JSONResponse(
				data: ['error' => 'Operation failed'],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

	}//end index()

	/**
	 * Get print job info
	 *
	 * Returns job metadata including status, manifest, and print configuration.
	 * Only the job owner or an admin may view a job.
	 *
	 * @param string $id Job UUID
	 *
	 * @return JSONResponse Job info or error
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-5
	 */
	public function show(string $id): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => 'Not authenticated'],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$job = $this->printJobSvc->getJob(jobId: $id);
			if ($job === null) {
				return new JSONResponse(
					data: ['error' => 'Job not found'],
					statusCode: Http::STATUS_NOT_FOUND
				);
			}

			$this->authorizeJobAccess(job: $job, userId: $user->getUID());

			return new JSONResponse(data: $job);
		} catch (OCSForbiddenException $e) {
			return new JSONResponse(
				data: ['error' => 'Not authorized'],
				statusCode: Http::STATUS_FORBIDDEN
			);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Print job show failed: ' . $e->getMessage(),
				context: ['jobId' => $id, 'exception' => $e]
			);

			return new JSONResponse(
				data: ['error' => 'Operation failed'],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}//end try

	}//end show()

	/**
	 * Download the generated PDF for a completed print job
	 *
	 * Returns the PDF binary. Only the job owner or an admin may download.
	 *
	 * @param string $id Job UUID
	 *
	 * @return DataDownloadResponse|JSONResponse PDF download or error
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-5
	 */
	public function download(string $id): DataDownloadResponse|JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => 'Not authenticated'],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$job = $this->printJobSvc->getJob(jobId: $id);
			if ($job === null) {
				return new JSONResponse(
					data: ['error' => 'Job not found'],
					statusCode: Http::STATUS_NOT_FOUND
				);
			}

			$this->authorizeJobAccess(job: $job, userId: $user->getUID());

			if (($job['status'] ?? '') === 'rendering') {
				return new JSONResponse(
					data: ['error' => 'Job not rendered yet', 'status' => 'rendering'],
					statusCode: Http::STATUS_CONFLICT
				);
			}

			$item = $this->request->getParam('item', null);
			$download = $this->printJobSvc->download(job: $job, item: $this->itemIndex(item: $item));
			if ($download === null) {
				return new JSONResponse(
					data: ['error' => 'PDF not found for this job'],
					statusCode: Http::STATUS_NOT_FOUND
				);
			}

			return new DataDownloadResponse(
				data: $download['content'],
				filename: $download['filename'],
				contentType: $download['contentType']
			);
		} catch (OCSForbiddenException $e) {
			return new JSONResponse(
				data: ['error' => 'Not authorized'],
				statusCode: Http::STATUS_FORBIDDEN
			);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Print job download failed: ' . $e->getMessage(),
				context: ['jobId' => $id, 'exception' => $e]
			);

			return new JSONResponse(
				data: ['error' => 'Operation failed'],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}//end try

	}//end download()

	/**
	 * Update job status (called by external print service to acknowledge)
	 *
	 * External print services call this endpoint to report that they have
	 * started printing, completed, or failed. Only the job owner or admin
	 * may update status.
	 *
	 * @param string $id Job UUID
	 *
	 * @return JSONResponse Updated job info or error
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-5
	 */
	public function updateStatus(string $id): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => 'Not authenticated'],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$job = $this->printJobSvc->getJob(jobId: $id);
			if ($job === null) {
				return new JSONResponse(
					data: ['error' => 'Job not found'],
					statusCode: Http::STATUS_NOT_FOUND
				);
			}

			$this->authorizeJobAccess(job: $job, userId: $user->getUID());

			$status = (string)$this->request->getParam('status', '');
			$details = $this->request->getParam('details', null);
			if (is_array($details) === true) {
				$details = (string) json_encode($details);
			}

			if ($details !== null) {
				$details = (string) $details;
			}

			try {
				$job = $this->printJobSvc->recordExternalStatus(
					job: $job,
					externalStatus: $status,
					details: $details
				);
			} catch (InvalidArgumentException $e) {
				return new JSONResponse(
					data: ['error' => $e->getMessage()],
					statusCode: Http::STATUS_BAD_REQUEST
				);
			}

			return new JSONResponse(data: $job);
		} catch (OCSForbiddenException $e) {
			return new JSONResponse(
				data: ['error' => 'Not authorized'],
				statusCode: Http::STATUS_FORBIDDEN
			);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Print job status update failed: ' . $e->getMessage(),
				context: ['jobId' => $id, 'exception' => $e]
			);

			return new JSONResponse(
				data: ['error' => 'Operation failed'],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}//end try

	}//end updateStatus()

	/**
	 * Create a batch print job
	 *
	 * Accepts JSON body with:
	 * - templateId (string, required): UUID of the template to use
	 * - items (array, required): Array of items, each with optional 'data' and 'filename'
	 * - options (object, optional): Print options
	 *
	 * For >10 items, a background job is dispatched. For ≤10 items, generation
	 * is synchronous.
	 *
	 * @return JSONResponse Batch job info or error
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-5
	 */
	public function batch(): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => 'Not authenticated'],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$templateId = $this->request->getParam('templateId', '');
			if (empty($templateId) === true) {
				return new JSONResponse(
					data: ['error' => 'templateId is required'],
					statusCode: Http::STATUS_BAD_REQUEST
				);
			}

			$items = $this->request->getParam('items', []);
			$options = $this->request->getParam('options', []);

			if (is_array($items) === false || empty($items) === true) {
				return new JSONResponse(
					data: ['error' => 'items array is required and must not be empty'],
					statusCode: Http::STATUS_BAD_REQUEST
				);
			}

			if (is_array($options) === false) {
				$options = [];
			}

			$result = $this->printJobSvc->createBatchJob(
				templateId: (string)$templateId,
				items: $items,
				options: $options,
				userId: $user->getUID(),
				filename: (string) $this->request->getParam('filename', 'print-job.pdf')
			);

			return new JSONResponse(data: $result, statusCode: Http::STATUS_CREATED);
		} catch (Exception $e) {
			$statusCode = 500;
			if ($e->getCode() >= 400 && $e->getCode() < 600) {
				$statusCode = $e->getCode();
			}

			$this->logger->error(
				message: 'Batch print job create failed: ' . $e->getMessage(),
				context: ['exception' => $e]
			);

			return new JSONResponse(
				data: ['error' => 'Operation failed'],
				statusCode: $statusCode
			);
		}//end try

	}//end batch()

	/**
	 * Read the optional `item` query parameter as a letter index.
	 *
	 * @param mixed $item The raw parameter
	 *
	 * @return int|null The index, or null for the whole job.
	 */
	private function itemIndex(mixed $item): ?int {
		if (is_numeric($item) === false || (int) $item < 0) {
			return null;
		}

		return (int) $item;

	}//end itemIndex()

	/**
	 * Authorize job access: throw OCSForbiddenException if not owner and not admin
	 *
	 * @param array $job Job data array including ownerUserId
	 * @param string $userId Requesting user UID
	 *
	 * @return void
	 *
	 * @throws OCSForbiddenException If user is not authorized
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-5
	 */
	private function authorizeJobAccess(array $job, string $userId): void {
		$ownerUserId = (string)($job['requestedBy'] ?? '');
		$isOwner = ($userId === $ownerUserId);
		$isAdmin = $this->groupManager->isAdmin($userId);

		if ($isOwner === false && $isAdmin === false) {
			throw new OCSForbiddenException('Not authorized');
		}

	}//end authorizeJobAccess()
}//end class

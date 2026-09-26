<?php

/**
 * Merge Controller
 *
 * The HTTP surface of merging documents into one PDF: ask for a merge, and ask
 * how a queued one is getting on.
 *
 * Small selections are merged while the caller waits, because waiting three
 * seconds beats polling. Above `mergeSyncThresholdPages` the answer is a queued
 * job, and the same checks have already run either way.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\MergeJobStoreUnreadableException;
use OCA\Filinq\Exception\MergeRefusedException;
use OCA\Filinq\Service\DocumentMergeService;
use OCA\Filinq\Service\MergeJobRepository;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/**
 * Controller for the merge endpoint and the progress of a queued merge.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
class MergeController extends Controller {

	/**
	 * The setting that says when a merge is queued instead of waited for.
	 *
	 * @var string
	 */
	public const THRESHOLD_KEY = 'mergeSyncThresholdPages';

	/**
	 * The threshold used when an instance declared none.
	 *
	 * @var int
	 */
	public const DEFAULT_THRESHOLD = 50;

	/**
	 * Constructor.
	 *
	 * @param string $appName The app name.
	 * @param IRequest $request The request.
	 * @param DocumentMergeService $merges The merge itself.
	 * @param MergeJobRepository $jobs The job store.
	 * @param IAppConfig $config App configuration, for the threshold.
	 * @param IUserSession $userSession The current session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly DocumentMergeService $merges,
		private readonly MergeJobRepository $jobs,
		private readonly IAppConfig $config,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Merge a selection into one PDF.
	 *
	 * @param array<int, array<string, mixed>> $inputs The documents, in the order they go in.
	 * @param array<string, mixed> $options The cover template, the bookmarks toggle, the target folder and the name.
	 * @param array<string, mixed> $hostObject The object the merge was started from.
	 *
	 * @return JSONResponse The finished job, the queued job, or the refusal.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	#[NoAdminRequired]
	public function create(array $inputs = [], array $options = [], array $hostObject = []): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		try {
			$threshold = $this->threshold();
			if ($this->merges->shouldQueue(inputs: $inputs, threshold: $threshold) === true) {
				return new JSONResponse(
					data: $this->merges->queue(inputs: $inputs, options: $options, hostObject: $hostObject),
					statusCode: Http::STATUS_ACCEPTED
				);
			}

			return new JSONResponse(
				data: $this->merges->merge(inputs: $inputs, options: $options, hostObject: $hostObject),
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return $this->failure(error: $e);
		}

	}//end create()

	/**
	 * How a merge is getting on.
	 *
	 * A read that failed answers 503, not 404. The two are the same shape from
	 * here and opposite in meaning: 404 tells the caller the merge is gone and
	 * ends the polling, while the merge is still in the queue. 503 says the
	 * answer is not available yet, which is both true and worth retrying.
	 *
	 * @param string $id The job's uuid.
	 *
	 * @return JSONResponse The job, 404 when there is no such merge, or 503 when the store could not be read.
	 *
	 * @throws MergeJobStoreUnreadableException Never: it is caught here and translated to 503.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	#[NoAdminRequired]
	public function show(string $id): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		try {
			$job = $this->jobs->find(uuid: $id);
		} catch (MergeJobStoreUnreadableException $e) {
			return new JSONResponse(
				data: [
					'error' => 'The merge could not be looked up right now. It is still queued, so try again in a moment.',
					'retryable' => true,
				],
				statusCode: Http::STATUS_SERVICE_UNAVAILABLE
			);
		}

		if ($job === null) {
			return new JSONResponse(
				data: ['error' => 'There is no merge with that id.'],
				statusCode: Http::STATUS_NOT_FOUND
			);
		}

		return new JSONResponse(data: $job, statusCode: Http::STATUS_OK);

	}//end show()

	/**
	 * The page count above which a merge is queued.
	 *
	 * @return int The threshold.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	private function threshold(): int {
		$declared = (int)$this->config->getValueInt(
			$this->appName,
			self::THRESHOLD_KEY,
			self::DEFAULT_THRESHOLD
		);

		if ($declared <= 0) {
			return self::DEFAULT_THRESHOLD;
		}

		return $declared;

	}//end threshold()

	/**
	 * Refuse an anonymous caller.
	 *
	 * @return JSONResponse|null The refusal, or null when somebody is logged in.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	private function requireUser(): ?JSONResponse {
		if ($this->userSession->getUser() !== null) {
			return null;
		}

		return new JSONResponse(
			data: ['error' => 'You must be logged in to merge documents.'],
			statusCode: Http::STATUS_UNAUTHORIZED
		);

	}//end requireUser()

	/**
	 * Answer one failure with the status the service gave it.
	 *
	 * @param Throwable $error The failure.
	 *
	 * @return JSONResponse The refusal.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	private function failure(Throwable $error): JSONResponse {
		$status = Http::STATUS_INTERNAL_SERVER_ERROR;
		if ($error instanceof MergeRefusedException) {
			$status = $error->getStatus();
		}

		return new JSONResponse(data: ['error' => $error->getMessage()], statusCode: $status);

	}//end failure()
}//end class

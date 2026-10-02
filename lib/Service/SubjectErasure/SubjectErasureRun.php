<?php

/**
 * Runs a previewed erasure over every document, resumably, and certifies it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-3.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

use OCA\Filinq\Exception\SubjectErasureRefusedException;
use Throwable;

/**
 * The run refuses to start before a preview was read and before the audit
 * trail took its start entry. It re-reads where the person is and what stands
 * over each document at run time, so a hold placed after the preview wins.
 * Progress and each document's result are saved after every document; a run
 * that stops reads `partially_completed` and resumes at the document it was
 * on (SubjectErasureJob). No document record is deleted by any step.
 */
class SubjectErasureRun {

	/**
	 * Constructor.
	 *
	 * @param SubjectErasureService      $requests    The request lifecycle and its checks.
	 * @param SubjectErasureStore        $store       The requests.
	 * @param SubjectErasureAudit        $audit       The audit trail.
	 * @param SubjectErasureLocator      $locator     Where the person appears.
	 * @param SubjectErasureObligations  $obligations What stands over each document.
	 * @param SubjectErasureDocumentStep $step        One document.
	 * @param SubjectErasureJob          $job         Resume point and final state.
	 * @param SubjectErasureCertifier    $certifier   The certificate.
	 */
	public function __construct(
		private readonly SubjectErasureService $requests,
		private readonly SubjectErasureStore $store,
		private readonly SubjectErasureAudit $audit,
		private readonly SubjectErasureLocator $locator,
		private readonly SubjectErasureObligations $obligations,
		private readonly SubjectErasureDocumentStep $step,
		private readonly SubjectErasureJob $job,
		private readonly SubjectErasureCertifier $certifier,
	) {

	}//end __construct()

	/**
	 * Run, or resume, the erasure.
	 *
	 * @param string $uuid   The request uuid.
	 * @param string $userId The caller.
	 *
	 * @return array{request: array<string, mixed>, certificate: array<string, mixed>|null} The
	 *         request as it stands, and the certificate when every document was reached.
	 *
	 * @throws SubjectErasureRefusedException When refused, before a preview, or when
	 *                                        the audit trail stops the run.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-3.1
	 */
	public function run(string $uuid, string $userId): array {
		$this->requests->assertMayErase(userId: $userId, requestUuid: $uuid);
		$request = $this->requests->load(uuid: $uuid);
		$this->requests->assertState(request: $request, allowed: ['previewed', 'partially_completed', 'running']);

		$progress = (array) ($request['progress'] ?? []);
		if ((string) $request['status'] === 'previewed') {
			$progress = ['documentsTotal' => 0, 'documentsDone' => 0, 'lastDocument' => '', 'occurrencesErased' => 0];
			$request['results'] = [];
		}

		$this->audit->record(
			requestUuid: $uuid,
			action: SubjectErasureAudit::ACTION_RUN_STARTED,
			userId: $userId,
			details: ['resumeAt' => (string) ($progress['lastDocument'] ?? '')]
		);

		$located = $this->locator->locate(identifiers: (array) ($request['identifiers'] ?? []));
		$documents = array_column($this->obligations->assess(located: $located), null, 'id');
		$scope = array_map('strval', array_keys($documents));
		$progress['documentsTotal'] = count($scope);
		$results = array_column((array) ($request['results'] ?? []), null, 'document');

		$request['status'] = 'running';
		$request = $this->saveProgress(request: $request, progress: $progress, results: $results);

		try {
			foreach ($this->job->resumeFrom(documents: $scope, progress: $progress) as $documentId) {
				$results[$documentId] = $this->step->process(
					document: $documents[$documentId],
					exclusions: (array) ($request['excluded'] ?? []),
					requestUuid: $uuid,
					userId: $userId
				);
				$progress['lastDocument'] = $documentId;
				$progress['documentsDone'] = ((int) array_search($documentId, $scope, true) + 1);
				$progress['occurrencesErased'] = $this->erasedOccurrences(results: $results);
				$request = $this->saveProgress(request: $request, progress: $progress, results: $results);
			}
		} catch (Throwable $e) {
			$request['status'] = 'partially_completed';
			$this->saveProgress(request: $request, progress: $progress, results: $results);
			throw $e;
		}

		$request['status'] = $this->job->statusFor(total: (int) $progress['documentsTotal'], done: (int) $progress['documentsDone']);
		$certificate = null;
		if ($request['status'] === SubjectErasureJob::COMPLETED) {
			$certificate = $this->certifier->issue(request: $request, results: array_values($results), userId: $userId);
			$request['certificate'] = (string) $certificate['uuid'];
		}

		return ['request' => $this->saveProgress(request: $request, progress: $progress, results: $results), 'certificate' => $certificate];

	}//end run()

	/**
	 * The certificate of a completed request.
	 *
	 * @param string $uuid   The request uuid.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed> The certificate.
	 *
	 * @throws SubjectErasureRefusedException When refused or there is no certificate yet.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-4.2
	 */
	public function certificate(string $uuid, string $userId): array {
		$this->requests->assertMayErase(userId: $userId, requestUuid: $uuid);
		$request = $this->requests->load(uuid: $uuid);
		$certificate = $this->store->find(schema: SubjectErasureStore::CERTIFICATE, uuid: (string) ($request['certificate'] ?? ''));
		if ($certificate === null) {
			throw new SubjectErasureRefusedException(
				reason: SubjectErasureRefusedException::REASON_NOT_FOUND,
				message: 'This request has no certificate yet.'
			);
		}

		return $certificate;

	}//end certificate()

	/**
	 * Save the request with its progress and results.
	 *
	 * @param array<string, mixed>                $request  The request.
	 * @param array<string, mixed>                $progress The progress.
	 * @param array<string, array<string, mixed>> $results  The results by document.
	 *
	 * @return array<string, mixed> The stored request.
	 */
	private function saveProgress(array $request, array $progress, array $results): array {
		$request['progress'] = [
			'documentsTotal' => (int) ($progress['documentsTotal'] ?? 0),
			'documentsDone' => (int) ($progress['documentsDone'] ?? 0),
			'lastDocument' => (string) ($progress['lastDocument'] ?? ''),
			'occurrencesErased' => (int) ($progress['occurrencesErased'] ?? 0),
		];
		$request['results'] = array_values($results);

		return $this->store->save(schema: SubjectErasureStore::REQUEST, row: $request);

	}//end saveProgress()

	/**
	 * How many occurrences the erased documents held.
	 *
	 * @param array<string, array<string, mixed>> $results The results by document.
	 *
	 * @return int The count.
	 */
	private function erasedOccurrences(array $results): int {
		$count = 0;
		foreach ($results as $row) {
			if ($row['outcome'] === SubjectErasureDocumentStep::ERASED) {
				$count += (int) $row['occurrences'];
			}
		}

		return $count;

	}//end erasedOccurrences()
}//end class

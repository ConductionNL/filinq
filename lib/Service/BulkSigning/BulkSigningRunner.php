<?php

/**
 * Bulk Signing Runner
 *
 * Bulk send, phase 2: one ordinary signing request per accepted row, each row
 * isolated from the others.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\BulkSigning
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\BulkSigning;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Service\SigningService;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Works through a confirmed batch as its initiator.
 *
 * SigningService::createRequest() takes the initiator from the session, and a
 * background job has none, so the rows run under the initiator set as the
 * volatile active user, cleared afterwards. The batch status is read before
 * every row, so a cancel stops the loop at the next row; progress is saved
 * after every row, so a job killed half way resumes where it stopped.
 *
 * @category Service
 * @package  OCA\Filinq\Service\BulkSigning
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
class BulkSigningRunner {

	/**
	 * Constructor.
	 *
	 * @param BulkSigningBatchRepository $repository  Batch storage
	 * @param SigningService             $signing     The single-request path
	 * @param IUserSession               $userSession Where the initiator is set for the rows
	 * @param IUserManager               $userManager Resolves the initiator
	 * @param LoggerInterface            $logger      Logger
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function __construct(
		private readonly BulkSigningBatchRepository $repository,
		private readonly SigningService $signing,
		private readonly IUserSession $userSession,
		private readonly IUserManager $userManager,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Create the requests of a confirmed batch.
	 *
	 * @param string $batchId The batch uuid
	 * @param string $userId  The initiator
	 *
	 * @return array The batch as it ended, or [] when it could not be read
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function run(string $batchId, string $userId): array {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			$this->logger->warning('Bulk send {batch} not run: its initiator {user} no longer exists', ['batch' => $batchId, 'user' => $userId]);
			return [];
		}

		$this->userSession->setVolatileActiveUser($user);
		try {
			return $this->work(batchId: $batchId);
		} finally {
			$this->userSession->setVolatileActiveUser(null);
		}

	}//end run()

	/**
	 * The loop, under the initiator's session.
	 *
	 * @param string $batchId The batch uuid
	 *
	 * @return array The batch as it ended
	 */
	private function work(string $batchId): array {
		$batch = ($this->repository->find(uuid: $batchId) ?? []);
		$recipients = (array) ($batch['recipients'] ?? []);
		$next = (int) ($batch['processedRows'] ?? 0);

		while (($batch['status'] ?? '') === 'creating' && $next < count($recipients)) {
			$batch = $this->createOne(batch: $batch, recipient: (array) $recipients[$next]);
			$batch['processedRows'] = ++$next;
			$fresh = ($this->repository->find(uuid: $batchId) ?? []);
			if (($fresh['status'] ?? '') === 'cancelled') {
				// Cancelled while this row was created: keep the cancel, record the row.
				$batch['status'] = 'cancelled';
			}

			$batch = $this->repository->save(batch: $batch, uuid: $batchId);
		}

		if (($batch['status'] ?? '') !== 'creating') {
			return $batch;
		}

		$batch['status'] = 'completed';
		if ((array) ($batch['rejectedRows'] ?? []) !== []) {
			$batch['status'] = 'completed_with_errors';
		}

		$batch['completedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

		return $this->repository->save(batch: $batch, uuid: $batchId);

	}//end work()

	/**
	 * Create one row's request; a failure is recorded on the batch, never thrown.
	 *
	 * @param array $batch     The batch
	 * @param array $recipient The accepted row
	 *
	 * @return array The batch with the request or the rejection added
	 */
	private function createOne(array $batch, array $recipient): array {
		try {
			$request = $this->signing->createRequest(
				data: [
					'documentFileId' => $batch['documentFileId'],
					'documentName' => $batch['documentName'],
					'signatureLevel' => $batch['signatureLevel'],
					'signingMode' => $batch['signingMode'],
					'provider' => $batch['provider'],
					'signers' => [
						[
							'userId' => (string) ($recipient['userId'] ?? ''),
							'email' => (string) ($recipient['email'] ?? ''),
							'displayName' => (string) ($recipient['displayName'] ?? ''),
						],
					],
				]
			);
			$batch['requestRefs'][] = (string) ($request['id'] ?? $request['uuid'] ?? '');
		} catch (Throwable $e) {
			$batch['rejectedRows'][] = [
				'row' => (int) ($recipient['row'] ?? 0),
				'reason' => 'creation-failed',
				'detail' => mb_substr($e->getMessage(), 0, 500),
			];
		}

		return $batch;

	}//end createOne()
}//end class

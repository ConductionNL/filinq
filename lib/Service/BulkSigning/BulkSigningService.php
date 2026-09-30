<?php

/**
 * Bulk Signing Service
 *
 * Bulk send, phase 1 and the batch's controls: validate a recipient list into
 * a report without sending anything, confirm it, cancel it, read it.
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
use OCA\Filinq\BackgroundJob\BulkSigningJob;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SigningRequestValidator;
use OCA\Filinq\Service\SigningService;
use OCP\BackgroundJob\IJobList;
use RuntimeException;
use Throwable;

/**
 * Creates, confirms, cancels and reads bulk-send batches.
 *
 * A batch never bypasses the single-request path: the document and the
 * level/provider pair pass the same validator a single request passes before
 * anything is stored, and phase 2 (BulkSigningRunner) creates every request
 * through SigningService::createRequest(). Access is the initiator's or an
 * admin's; anybody else gets null, which the controller answers as 404.
 *
 * @category Service
 * @package  OCA\Filinq\Service\BulkSigning
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
class BulkSigningService {

	/**
	 * States a batch can no longer move out of, except by cancel.
	 */
	private const FINISHED = ['completed', 'completed_with_errors', 'cancelled'];

	/**
	 * Constructor.
	 *
	 * @param BulkSigningRecipientParser $parser           Reads the list
	 * @param BulkSigningRowValidator    $rowValidator     Sorts rows into accepted and rejected
	 * @param SigningRequestValidator    $requestValidator The single-request validator
	 * @param SettingsService            $settings         Default level and provider
	 * @param BulkSigningBatchRepository $repository       Batch storage
	 * @param IJobList                   $jobList          Queues phase 2
	 * @param SigningService             $signing          Cancels member requests
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function __construct(
		private readonly BulkSigningRecipientParser $parser,
		private readonly BulkSigningRowValidator $rowValidator,
		private readonly SigningRequestValidator $requestValidator,
		private readonly SettingsService $settings,
		private readonly BulkSigningBatchRepository $repository,
		private readonly IJobList $jobList,
		private readonly SigningService $signing,
	) {

	}//end __construct()

	/**
	 * Phase 1: validate a recipient list into a batch that is ready and has sent nothing.
	 *
	 * @param array  $settings Title, documentFileId, documentName, signatureLevel, signingMode, provider
	 * @param string $content  The list's bytes
	 * @param string $filename The list's file name
	 * @param string $userId   The initiator
	 *
	 * @return array The stored batch, status `ready`
	 *
	 * @throws RuntimeException 400 when the request a row would become is invalid for everybody
	 * @throws \InvalidArgumentException 400 when the list cannot be read
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function createBatch(array $settings, string $content, string $filename, string $userId): array {
		$toggles = $this->settings->getFeatureToggles();
		$request = [
			'documentFileId' => (string) ($settings['documentFileId'] ?? ''),
			'documentName' => (string) ($settings['documentName'] ?? ''),
			'signatureLevel' => (string) ($settings['signatureLevel'] ?? $toggles['signing_default_level']),
			'signingMode' => (string) ($settings['signingMode'] ?? 'parallel'),
			'provider' => (string) ($settings['provider'] ?? $toggles['signing_provider']),
		];
		// The same two checks SigningService::createRequest() runs first, so a
		// batch every row of which would be refused is refused before it exists.
		$this->requestValidator->validateRequestData(data: $request);
		$this->requestValidator->validateProviderLevelPair(provider: $request['provider'], level: $request['signatureLevel']);

		$rows = $this->parser->parse(content: $content, filename: $filename);
		$sorted = $this->rowValidator->validate(rows: $rows);

		$title = trim((string) ($settings['title'] ?? ''));
		if ($title === '') {
			$title = $request['documentName'];
		}

		return $this->repository->save(
			batch: $request + [
				'title' => mb_substr($title, 0, 255),
				'recipientSource' => BulkSigningRecipientParser::sourceOf(filename: $filename),
				'totalRows' => count($rows),
				'acceptedRows' => count($sorted['accepted']),
				'rejectedRows' => $sorted['rejected'],
				'recipients' => $sorted['accepted'],
				'processedRows' => 0,
				'status' => 'ready',
				'requestRefs' => [],
				'createdBy' => $userId,
				'createdAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
			]
		);

	}//end createBatch()

	/**
	 * Phase 2 starts here: the initiator confirms and the job is queued.
	 *
	 * @param string $id      The batch uuid
	 * @param string $userId  The caller
	 * @param bool   $isAdmin Whether the caller is an admin
	 *
	 * @return array|null The batch, status `creating`, or null when the caller may not reach it
	 *
	 * @throws RuntimeException 409 when the batch is not ready or names nobody to send to
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function confirm(string $id, string $userId, bool $isAdmin): ?array {
		$batch = $this->get(id: $id, userId: $userId, isAdmin: $isAdmin);
		if ($batch === null) {
			return null;
		}

		if (($batch['status'] ?? '') !== 'ready') {
			throw new RuntimeException(message: 'This bulk send was already confirmed or cancelled', code: 409);
		}

		if ((int) ($batch['acceptedRows'] ?? 0) === 0) {
			throw new RuntimeException(message: 'This bulk send has nobody to send to', code: 409);
		}

		$batch['status'] = 'creating';
		$batch = $this->repository->save(batch: $batch, uuid: $id);
		// The job runs the rows as the initiator, never as the admin who confirmed.
		$this->jobList->add(BulkSigningJob::class, ['batchId' => $id, 'userId' => (string) $batch['createdBy']]);

		return $batch;

	}//end confirm()

	/**
	 * Cancel a batch, and every member request that can still be cancelled.
	 *
	 * A member that is already signed, declined or expired stays as it is.
	 *
	 * @param string $id      The batch uuid
	 * @param string $userId  The caller
	 * @param bool   $isAdmin Whether the caller is an admin
	 *
	 * @return array|null The batch with `cancelledRequests`, or null when the caller may not reach it
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function cancel(string $id, string $userId, bool $isAdmin): ?array {
		$batch = $this->get(id: $id, userId: $userId, isAdmin: $isAdmin);
		if ($batch === null) {
			return null;
		}

		// Stop the job first: it reads the status before every row.
		$batch['status'] = 'cancelled';
		$batch = $this->repository->save(batch: $batch, uuid: $id);

		$cancelled = 0;
		foreach ((array) ($batch['requestRefs'] ?? []) as $requestId) {
			try {
				$this->signing->cancelRequest(requestId: (string) $requestId);
				$cancelled++;
			} catch (Throwable $e) {
				// Signed, declined or expired: not cancellable, left alone.
				continue;
			}
		}

		$batch['cancelledRequests'] = $cancelled;
		$batch['completedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

		return $this->repository->save(batch: $batch, uuid: $id);

	}//end cancel()

	/**
	 * Read one batch.
	 *
	 * @param string $id      The batch uuid
	 * @param string $userId  The caller
	 * @param bool   $isAdmin Whether the caller is an admin
	 *
	 * @return array|null The batch, or null when absent or not the caller's
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function get(string $id, string $userId, bool $isAdmin): ?array {
		$batch = $this->repository->find(uuid: $id);
		if ($batch === null) {
			return null;
		}

		// The report is the initiator's or an admin's; nobody else learns the batch exists.
		if ($isAdmin === false && ($batch['createdBy'] ?? '') !== $userId) {
			return null;
		}

		return $batch;

	}//end get()

	/**
	 * List the caller's batches, or every batch for an admin.
	 *
	 * @param string $userId  The caller
	 * @param bool   $isAdmin Whether the caller is an admin
	 *
	 * @return list<array>
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function listFor(string $userId, bool $isAdmin): array {
		$createdBy = $userId;
		if ($isAdmin === true) {
			$createdBy = null;
		}

		return $this->repository->list(createdBy: $createdBy);

	}//end listFor()

	/**
	 * Whether a batch is finished.
	 *
	 * @param array $batch The batch
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public static function isFinished(array $batch): bool {
		return in_array(($batch['status'] ?? ''), self::FINISHED, true);

	}//end isFinished()
}//end class

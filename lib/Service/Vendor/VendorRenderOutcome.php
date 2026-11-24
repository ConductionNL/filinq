<?php

/**
 * Vendor Render Outcome
 *
 * Settles the pending generatedDocument of a vendor render when integriq
 * reports how the render ended. A failed render marks the entry `failed`
 * with integriq's error, so a render the vendor could not make no longer
 * reads as still underway. A rendered outcome leaves the entry pending:
 * integriq's outcome event names the vendor's file reference but carries no
 * document, so there is nothing for filinq to store yet.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Vendor
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md#2-backend
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Vendor;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Marks the pending entry of a vendor render with the outcome integriq reports.
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md#2-backend
 */
class VendorRenderOutcome {

	/**
	 * The register the generatedDocument entries live in.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * The schema of the generation entries.
	 *
	 * @var string
	 */
	public const SCHEMA = 'generatedDocument';

	/**
	 * The status integriq reports for a render the vendor could not make.
	 *
	 * @var string
	 */
	public const VENDOR_FAILED = 'failed';

	/**
	 * The status a settled failure is written with.
	 *
	 * @var string
	 */
	public const STATUS_FAILED = 'failed';

	/**
	 * The error written when integriq reports a failure without saying why.
	 *
	 * @var string
	 */
	public const NO_REASON = 'The vendor did not produce the document and gave no reason.';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objects Resolves OpenRegister's ObjectService.
	 * @param LoggerInterface               $logger  Logs what could not be settled.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objects,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Settle the pending entry of one vendor job.
	 *
	 * @param string $jobId  The integriq job id the entry was recorded with.
	 * @param string $status The outcome integriq reports: `rendered` or `failed`.
	 * @param string $error  Why there is no document, '' when there is one.
	 *
	 * @return array<string, mixed>|null The entry as written, or null when nothing was written:
	 *                                   no pending entry for the job, a rendered outcome, or a
	 *                                   write that failed.
	 *
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md#2-backend
	 */
	public function settle(string $jobId, string $status, string $error): ?array {
		$jobId = trim($jobId);
		if ($jobId === '') {
			return null;
		}

		try {
			$entry = $this->pendingEntry(jobId: $jobId);
			if ($entry === null) {
				// Another app's job, or one filinq has not recorded yet.
				return null;
			}

			if ($status !== self::VENDOR_FAILED) {
				$this->logger->info(
					message: 'Vendor render {jobId} is rendered, but integriq hands over no document, so its entry stays pending.',
					context: ['jobId' => $jobId, 'status' => $status]
				);
				return null;
			}

			return $this->markFailed(entry: $entry, error: $error);
		} catch (Throwable $e) {
			$this->logger->error(
				message: 'Could not settle vendor render {jobId}: {error}',
				context: ['jobId' => $jobId, 'error' => $e->getMessage()]
			);
			return null;
		}//end try

	}//end settle()

	/**
	 * The pending generatedDocument recorded for a job, or null.
	 *
	 * @param string $jobId The integriq job id.
	 *
	 * @return array<string, mixed>|null The entry, its id under `id`.
	 */
	private function pendingEntry(string $jobId): ?array {
		$found = $this->objects->resolve()->findAll(
			config: [
				'filters' => [
					'register' => self::REGISTER,
					'schema' => self::SCHEMA,
					'vendorJobId' => $jobId,
					'status' => VendorRenderService::STATUS_PENDING,
				],
				'limit' => 1,
			],
			_rbac: false
		);

		foreach ($found as $candidate) {
			$row = self::rowOf(entry: $candidate);
			if ($row !== null
				&& (string) ($row['vendorJobId'] ?? '') === $jobId
				&& ($row['status'] ?? null) === VendorRenderService::STATUS_PENDING
			) {
				return $row;
			}
		}

		return null;

	}//end pendingEntry()

	/**
	 * Write the entry as failed with integriq's error.
	 *
	 * @param array<string, mixed> $entry The pending entry, its id under `id`.
	 * @param string               $error Why there is no document.
	 *
	 * @return array<string, mixed> The entry as written.
	 */
	private function markFailed(array $entry, string $error): array {
		$entryId = (string) $entry['id'];
		$fields  = $entry;
		unset($fields['@self'], $fields['id']);
		$fields['status'] = self::STATUS_FAILED;

		$error = trim($error);
		if ($error === '') {
			$error = self::NO_REASON;
		}

		$fields['errorMessage'] = $error;

		$saved = $this->objects->resolve()->saveObject(
			object: $fields,
			options: new ObjectSaveOptions(
				target: new ObjectFindTarget(register: self::REGISTER, schema: self::SCHEMA),
				uuid: $entryId
			)
		);

		return (self::rowOf(entry: $saved) ?? ($fields + ['id' => $entryId]));

	}//end markFailed()

	/**
	 * One stored object as an array, its id under `id`.
	 *
	 * @param mixed $entry An array or an object entity.
	 *
	 * @return array<string, mixed>|null The row, or null when it has no id.
	 */
	private static function rowOf(mixed $entry): ?array {
		$row = $entry;
		if (is_object($entry) === true && method_exists($entry, 'jsonSerialize') === true) {
			$row = $entry->jsonSerialize();
		}

		if (is_array($row) === false) {
			return null;
		}

		$rowId = ($row['id'] ?? ($row['@self']['id'] ?? ($row['uuid'] ?? null)));
		if ($rowId === null || (string) $rowId === '') {
			return null;
		}

		$row['id'] = (string) $rowId;
		return $row;

	}//end rowOf()
}//end class

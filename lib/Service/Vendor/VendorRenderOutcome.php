<?php

/**
 * Vendor Render Outcome
 *
 * Settles the pending generatedDocument of a vendor render when integriq
 * reports how the render ended. A failed render marks the entry `failed`
 * with integriq's error, so a render the vendor could not make no longer
 * reads as still underway. A rendered outcome files the document integriq
 * hands over, in the folder and under the name a filinq render of the same
 * run would get, and marks the entry `generated` with the file. An integriq
 * that hands over no document leaves the entry pending.
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
use OCA\Filinq\Service\DocumentStorageService;
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
	 * The status a filed render is written with.
	 *
	 * @var string
	 */
	public const STATUS_GENERATED = 'generated';

	/**
	 * The folder a render is filed in when its entry names none.
	 *
	 * @var string
	 */
	public const DEFAULT_FOLDER = 'DocuDesk/default';

	/**
	 * How many outcomes of jobs without an entry are kept for one request.
	 *
	 * @var integer
	 */
	public const EARLY_LIMIT = 10;

	/**
	 * Outcomes that arrived before their pending entry was written, by job id.
	 *
	 * A vendor that settles inside the request makes integriq announce the
	 * outcome before the render command returns, so before filinq has
	 * recorded the job. The outcome waits here for {@see settleEarly()}.
	 *
	 * @var array<string, array{status: string, error: string, document: string}>
	 */
	private array $early = [];

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objects Resolves OpenRegister's ObjectService.
	 * @param LoggerInterface               $logger  Logs what could not be settled.
	 * @param DocumentStorageService|null   $storage Files a rendered document; without it a render stays pending.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objects,
		private readonly LoggerInterface $logger,
		private readonly ?DocumentStorageService $storage = null,
	) {

	}//end __construct()

	/**
	 * Settle the pending entry of one vendor job.
	 *
	 * @param string $jobId    The integriq job id the entry was recorded with.
	 * @param string $status   The outcome integriq reports: `rendered` or `failed`.
	 * @param string $error    Why there is no document, '' when there is one.
	 * @param string $document The rendered document integriq hands over, '' when it hands over none.
	 *
	 * @return array<string, mixed>|null The entry as written, or null when nothing was written:
	 *                                   no pending entry for the job (yet), a rendered outcome
	 *                                   without a document, or a write that failed.
	 *
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-the-vendors-file-is-stored-like-a-filinq-render-req-tev-003
	 */
	public function settle(string $jobId, string $status, string $error, string $document = ''): ?array {
		$jobId = trim($jobId);
		if ($jobId === '') {
			return null;
		}

		try {
			$entry = $this->pendingEntry(jobId: $jobId);
			if ($entry === null) {
				// Another app's job, or one filinq has not recorded yet.
				$this->keepEarly(jobId: $jobId, outcome: ['status' => $status, 'error' => $error, 'document' => $document]);
				return null;
			}

			return $this->apply(entry: $entry, status: $status, error: $error, document: $document);
		} catch (Throwable $e) {
			$this->logger->error(
				message: 'Could not settle vendor render {jobId}: {error}',
				context: ['jobId' => $jobId, 'error' => $e->getMessage()]
			);
			return null;
		}//end try

	}//end settle()

	/**
	 * Settle a job whose outcome arrived before its pending entry was written.
	 *
	 * Called by the vendor render right after it records the pending entry.
	 *
	 * @param string $jobId The integriq job id.
	 *
	 * @return array<string, mixed>|null The entry as written, or null when no outcome was waiting.
	 *
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-the-vendors-file-is-stored-like-a-filinq-render-req-tev-003
	 */
	public function settleEarly(string $jobId): ?array {
		$jobId = trim($jobId);
		if (isset($this->early[$jobId]) === false) {
			return null;
		}

		$outcome = $this->early[$jobId];
		unset($this->early[$jobId]);

		return $this->settle(
			jobId: $jobId,
			status: $outcome['status'],
			error: $outcome['error'],
			document: $outcome['document']
		);

	}//end settleEarly()

	/**
	 * Keep the outcome of a job without an entry, for at most EARLY_LIMIT jobs.
	 *
	 * @param string                                                $jobId   The integriq job id.
	 * @param array{status: string, error: string, document: string} $outcome The outcome.
	 *
	 * @return void
	 */
	private function keepEarly(string $jobId, array $outcome): void {
		unset($this->early[$jobId]);
		$this->early[$jobId] = $outcome;
		$this->early         = array_slice($this->early, -self::EARLY_LIMIT, null, true);

	}//end keepEarly()

	/**
	 * Write the outcome onto a pending entry.
	 *
	 * @param array<string, mixed> $entry    The pending entry, its id under `id`.
	 * @param string               $status   The outcome integriq reports.
	 * @param string               $error    Why there is no document.
	 * @param string               $document The rendered document, '' when none was handed over.
	 *
	 * @return array<string, mixed>|null The entry as written, or null when it stays pending.
	 */
	private function apply(array $entry, string $status, string $error, string $document): ?array {
		if ($status === self::VENDOR_FAILED) {
			return $this->write(entry: $entry, fields: ['status' => self::STATUS_FAILED, 'errorMessage' => $this->reason(error: $error)]);
		}

		if ($document === '' || $this->storage === null) {
			$this->logger->info(
				message: 'Vendor render {jobId} is rendered, but no document was handed over to file, so its entry stays pending.',
				context: ['jobId' => (string) ($entry['vendorJobId'] ?? ''), 'status' => $status]
			);
			return null;
		}

		return $this->file(entry: $entry, document: $document);

	}//end apply()

	/**
	 * File a rendered document for the user it was made for and mark the entry generated.
	 *
	 * A document that cannot be filed marks the entry failed with the reason,
	 * so the render never reads as underway when it has ended.
	 *
	 * @param array<string, mixed> $entry    The pending entry, its id under `id`.
	 * @param string               $document The rendered document.
	 *
	 * @return array<string, mixed> The entry as written.
	 */
	private function file(array $entry, string $document): array {
		$userId = trim((string) ($entry['generatedBy'] ?? ''));
		if ($userId === '') {
			return $this->write(
				entry: $entry,
				fields: ['status' => self::STATUS_FAILED, 'errorMessage' => 'The vendor rendered the document, but the render names no user to file it for.']
			);
		}

		$folder = trim((string) ($entry['outputFolder'] ?? ''));
		if ($folder === '') {
			$folder = self::DEFAULT_FOLDER;
		}

		$name = trim((string) ($entry['outputName'] ?? ''));
		if ($name === '') {
			$name = 'document.' . self::extensionOf(format: (string) ($entry['format'] ?? ''));
		}

		try {
			$stored = $this->storage->store(userId: $userId, targetPath: $folder, filename: $name, content: $document);
		} catch (Throwable $e) {
			return $this->write(
				entry: $entry,
				fields: ['status' => self::STATUS_FAILED, 'errorMessage' => 'The vendor rendered the document, but it could not be filed: ' . $e->getMessage()]
			);
		}

		return $this->write(
			entry: $entry,
			fields: [
				'status' => self::STATUS_GENERATED,
				'errorMessage' => null,
				'fileId' => $stored['fileId'],
				'filePath' => $stored['path'],
			]
		);

	}//end file()

	/**
	 * The error written for a failure, a sentence when integriq gave none.
	 *
	 * @param string $error integriq's error.
	 *
	 * @return string The error.
	 */
	private function reason(string $error): string {
		$error = trim($error);
		if ($error === '') {
			return self::NO_REASON;
		}

		return $error;

	}//end reason()

	/**
	 * The file extension of an output format, as a filinq render names it.
	 *
	 * @param string $format The output format.
	 *
	 * @return string The extension without the dot.
	 */
	private static function extensionOf(string $format): string {
		return match ($format) {
			'odf' => 'odt',
			'html' => 'html',
			'docx' => 'docx',
			default => 'pdf',
		};

	}//end extensionOf()

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
	 * Write fields onto the entry.
	 *
	 * @param array<string, mixed> $entry  The pending entry, its id under `id`.
	 * @param array<string, mixed> $fields The fields to set.
	 *
	 * @return array<string, mixed> The entry as written.
	 */
	private function write(array $entry, array $fields): array {
		$entryId = (string) $entry['id'];
		$object  = $entry;
		unset($object['@self'], $object['id']);
		$object = array_merge($object, $fields);

		$saved = $this->objects->resolve()->saveObject(
			object: $object,
			options: new ObjectSaveOptions(
				target: new ObjectFindTarget(register: self::REGISTER, schema: self::SCHEMA),
				uuid: $entryId
			)
		);

		return (self::rowOf(entry: $saved) ?? ($object + ['id' => $entryId]));

	}//end write()

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

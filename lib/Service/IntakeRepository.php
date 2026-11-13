<?php

/**
 * Intake Repository
 *
 * Reads and writes the `intakeDocument` rows in the `filinq` register. It is
 * the only class that knows the register, the schema and the stored shape, so
 * the inbox, the assign and the reject all see one intake document rather than
 * three readings of the same row.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Stores and finds the documents waiting in the intake inbox.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
class IntakeRepository implements IntakeRepositoryInterface {

	/**
	 * The post register's schema: inbound and outbound registrations.
	 *
	 * @var string
	 */
	private const REGISTRATION_SCHEMA = 'documentRegistration';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Every document still waiting for a clerk, newest first.
	 *
	 * @return array<int, array<string, mixed>> The waiting documents.
	 *
	 * @throws RuntimeException When the register could not be read.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function findWaiting(): array {
		return $this->search(filters: ['status' => self::STATUS_RECEIVED]);

	}//end findWaiting()

	/**
	 * Every document in one state, newest first.
	 *
	 * @param string $status The state to list.
	 *
	 * @return array<int, array<string, mixed>> The documents in that state.
	 *
	 * @throws RuntimeException When the register could not be read.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function findByStatus(string $status): array {
		return $this->search(filters: ['status' => $status]);

	}//end findByStatus()

	/**
	 * Everything that arrived with one message.
	 *
	 * @param string $uuid The message's intake document.
	 *
	 * @return array<int, array<string, mixed>> The attachments.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function findArrivedWith(string $uuid): array {
		if ($uuid === '') {
			return [];
		}

		return $this->search(filters: ['arrivedWith' => $uuid]);

	}//end findArrivedWith()

	/**
	 * Find the intake document that belongs to one file, whatever its state.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The document, or null when the file never had one.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function findByFile(int $fileId): ?array {
		if ($fileId <= 0) {
			return null;
		}

		$rows = $this->search(filters: ['file' => $fileId]);
		if ($rows === []) {
			return null;
		}

		return $rows[0];

	}//end findByFile()

	/**
	 * Find the intake document a channel already delivered under this reference.
	 *
	 * A channel that delivers the same message twice must not produce two rows
	 * in the inbox: a clerk would then assign one and leave the other waiting
	 * forever, and nothing would say why.
	 *
	 * @param string $sourceRef The channel's own reference.
	 *
	 * @return array<string, mixed>|null The stored document, or null when this is the first delivery.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function findBySourceRef(string $sourceRef): ?array {
		if ($sourceRef === '') {
			return null;
		}

		$rows = $this->search(filters: ['sourceRef' => $sourceRef]);
		if ($rows === []) {
			return null;
		}

		return $rows[0];

	}//end findBySourceRef()

	/**
	 * Find one intake document by its uuid.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed>|null The document, or null when there is none.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function findByUuid(string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		try {
			$object = $this->objectResolver->resolve()->find(
				id: $uuid,
				register: self::REGISTER,
				schema: self::SCHEMA
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[IntakeRepository] could not read one intake document',
				context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => $uuid, 'error' => $e->getMessage()]
			);

			return null;
		}

		if ($object === null) {
			return null;
		}

		return $this->normalise(row: $object);

	}//end findByUuid()

	/**
	 * The earliest intake document with this content that was not rejected.
	 *
	 * A rejected copy is no reason to warn: it already left the inbox as not
	 * belonging here.
	 *
	 * @param string $contentHash The SHA-256 of the file's bytes.
	 *
	 * @return array<string, mixed>|null The document, or null when none came before.
	 *
	 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-an-arrival-whose-content-came-before-is-marked-never-refused-req-iws-003
	 */
	public function findByContentHash(string $contentHash): ?array {
		if ($contentHash === '') {
			return null;
		}

		$earlier = array_values(
			array_filter(
				$this->search(filters: ['contentHash' => $contentHash]),
				static fn (array $document): bool => ($document['status'] ?? '') !== self::STATUS_REJECTED
			)
		);
		if ($earlier === []) {
			return null;
		}

		usort(
			$earlier,
			static fn (array $left, array $right): int => strcmp((string)($left['receivedAt'] ?? ''), (string)($right['receivedAt'] ?? ''))
		);

		return $earlier[0];

	}//end findByContentHash()

	/**
	 * Every document past its handling deadline that is not handled yet.
	 *
	 * A rejected document is always handled. A waiting or detached one never
	 * is. An assigned one is handled under its rule's `handledWhen`:
	 * `assigned` (the default, also for a document no rule stamped) at once,
	 * `answered` only once an outbound registration answers its inbound one.
	 *
	 * @param string                $today       Today as Y-m-d; a deadline before it has passed.
	 * @param array<string, string> $handledWhen Rule uuid => `assigned` or `answered`.
	 *
	 * @return array<int, array<string, mixed>> The overdue documents, the longest overdue first.
	 *
	 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-the-worklist-shows-what-is-overdue-req-iws-002
	 */
	public function findOverdue(string $today, array $handledWhen): array {
		$overdue = [];
		foreach ([self::STATUS_RECEIVED, self::STATUS_DETACHED, self::STATUS_ASSIGNED] as $status) {
			foreach ($this->search(filters: ['status' => $status]) as $document) {
				$deadline = (string)($document['handlingDeadline'] ?? '');
				if ($deadline === '' || substr($deadline, 0, 10) >= $today) {
					continue;
				}

				if ($status === self::STATUS_ASSIGNED
					&& $this->assignedIsOpen(document: $document, handledWhen: $handledWhen) === false
				) {
					continue;
				}

				$overdue[] = $document;
			}
		}

		usort(
			$overdue,
			static fn (array $left, array $right): int => strcmp((string)$left['handlingDeadline'], (string)$right['handlingDeadline'])
		);

		return $overdue;

	}//end findOverdue()

	/**
	 * Whether an assigned document is still open under its rule.
	 *
	 * @param array<string, mixed>  $document    The assigned document.
	 * @param array<string, string> $handledWhen Rule uuid => `assigned` or `answered`.
	 *
	 * @return bool True when its rule waits for an answer that has not been registered.
	 */
	private function assignedIsOpen(array $document, array $handledWhen): bool {
		$rule = (string)($document['defaultRule'] ?? '');
		if (($handledWhen[$rule] ?? 'assigned') !== 'answered') {
			return false;
		}

		return $this->isAnswered(uuid: (string)($document['uuid'] ?? '')) === false;

	}//end assignedIsOpen()

	/**
	 * Whether an outbound registration answers an inbound registration of this document.
	 *
	 * The discharge is read from the `answers` link of the post register and
	 * never written as a status on the inbound entry.
	 *
	 * @param string $uuid The intake document.
	 *
	 * @return bool True when it was answered.
	 *
	 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-the-worklist-shows-what-is-overdue-req-iws-002
	 */
	private function isAnswered(string $uuid): bool {
		if ($uuid === '') {
			return false;
		}

		$inbound = $this->search(filters: ['document' => $uuid, 'direction' => 'inbound'], schema: self::REGISTRATION_SCHEMA);
		foreach ($inbound as $registration) {
			$registrationUuid = (string)($registration['uuid'] ?? '');
			if ($registrationUuid === '') {
				continue;
			}

			$answers = $this->search(filters: ['answers' => $registrationUuid, 'direction' => 'outbound'], schema: self::REGISTRATION_SCHEMA);
			if ($answers !== []) {
				return true;
			}
		}

		return false;

	}//end isAnswered()

	/**
	 * Write an intake document, creating it when its uuid is not known yet.
	 *
	 * @param array<string, mixed> $document The document to store.
	 * @param string|null $uuid The uuid to write under, or null to create one.
	 *
	 * @return array<string, mixed> The stored document.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function save(array $document, ?string $uuid = null): array {
		unset($document['uuid']);

		try {
			$arguments = [
				'object' => $document,
				'register' => self::REGISTER,
				'schema' => self::SCHEMA,
			];
			if ($uuid !== null) {
				$arguments['uuid'] = $uuid;
			}

			$stored = $this->objectResolver->resolve()->saveObject(...$arguments);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[IntakeRepository] could not store an intake document',
				context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => $uuid, 'error' => $e->getMessage()]
			);

			throw new RuntimeException(
				message: 'Could not store the intake document: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}//end try

		$normalised = $this->normalise(row: $stored);
		if (($normalised['uuid'] ?? '') === '' && $uuid !== null) {
			$normalised['uuid'] = $uuid;
		}

		return $normalised;

	}//end save()

	/**
	 * Search intake documents on equality filters.
	 *
	 * 🔴 The filters are BARE keys, not `filter[x]`. OpenRegister's objects
	 * endpoint reads a bare key as a field filter and a `filter[x]` key as the
	 * empty set, and it answers the empty set with a confident zero rather than
	 * an error (openregister#3611).
	 *
	 * @param array<string, mixed> $filters Field equality filters.
	 * @param string $schema The schema slug; the intake documents unless another is named.
	 *
	 * @return array<int, array<string, mixed>> The matching documents.
	 *
	 * @throws RuntimeException When the register could not be read.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	private function search(array $filters, string $schema=self::SCHEMA): array {
		try {
			// 🔴 SLUGS GO THROUGH `searchObjectsBySlug`, NEVER `searchObjects`.
			// `self::REGISTER` is `filinq` and `self::SCHEMA` is
			// `intakeDocument`; `searchObjects` reads both as numeric ids and
			// answered every call here with zero rows and no error. The whole
			// inbox read as empty, and worse, findBySourceRef() never found
			// the row it exists to find, so a channel redelivering a message
			// wrote a second inbox document every time.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: self::REGISTER,
				schemaSlug: $schema,
				filters: $filters
			);
		} catch (DoesNotExistException $e) {
			// The schema is not on this instance, so the inbox holds nothing.
			// That is an answer, not a failed read, and raising here would 500
			// the worklist on an instance that never imported the schema.
			$this->logger->warning(
				message: '[IntakeRepository] no intakeDocument schema on this instance, so the inbox is empty',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return [];
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[IntakeRepository] could not read the intake inbox',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			throw new RuntimeException(
				message: 'Could not read the intake inbox: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		if (is_array($results) === false) {
			return [];
		}

		$documents = [];
		foreach ($results as $result) {
			$documents[] = $this->normalise(row: $result);
		}

		return $documents;

	}//end search()

	/**
	 * Read one OpenRegister row into the flat shape this app uses.
	 *
	 * @param mixed $row The row as OpenRegister returned it.
	 *
	 * @return array<string, mixed> The flat document, including its `uuid`.
	 *
	 * @spec exclude Shape adapter over an OpenRegister response; no behaviour of its own.
	 */
	private function normalise(mixed $row): array {
		return (new StoredRecordFields())->read(row: $row);

	}//end normalise()
}//end class

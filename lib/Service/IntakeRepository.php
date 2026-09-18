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
class IntakeRepository {

	/**
	 * The register holding Filinq's schemas.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * The schema holding the waiting documents.
	 *
	 * @var string
	 */
	public const SCHEMA = 'intakeDocument';

	/**
	 * Waiting for a clerk. The initial state.
	 *
	 * @var string
	 */
	public const STATUS_RECEIVED = 'received';

	/**
	 * Assigned to a record. Terminal.
	 *
	 * @var string
	 */
	public const STATUS_ASSIGNED = 'assigned';

	/**
	 * Rejected with a reason. Terminal.
	 *
	 * @var string
	 */
	public const STATUS_REJECTED = 'rejected';

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
	 * Taken back off a record, waiting on the worklist.
	 *
	 * @var string
	 */
	public const STATUS_DETACHED = 'detached';

	/**
	 * Every document in one state, newest first.
	 *
	 * @param string $status The state to list.
	 *
	 * @return array<int, array<string, mixed>> The documents in that state.
	 *
	 * @throws RuntimeException When the register could not be read.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
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
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
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
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
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
	 *
	 * @return array<int, array<string, mixed>> The matching documents.
	 *
	 * @throws RuntimeException When the register could not be read.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	private function search(array $filters): array {
		$query = array_merge(
			[
				'@self' => [
					'register' => self::REGISTER,
					'schema' => self::SCHEMA,
				],
			],
			$filters
		);

		try {
			$results = $this->objectResolver->resolve()->searchObjects(query: $query);
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
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		$self = [];
		if (isset($data['@self']) === true && is_array($data['@self']) === true) {
			$self = $data['@self'];
		}

		$fields = $data;
		if (isset($data['object']) === true && is_array($data['object']) === true) {
			$fields = $data['object'];
		}

		$fields['uuid'] = (string)($fields['uuid'] ?? ($data['uuid'] ?? ($self['uuid'] ?? ($data['id'] ?? ''))));

		return $fields;

	}//end normalise()
}//end class

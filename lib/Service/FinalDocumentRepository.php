<?php

/**
 * Final Document Repository
 *
 * Reads and writes the `documentVersion` finalisation records in the `filinq`
 * register. It is the only class that knows the register, the schema and the
 * stored shape, so the guard, the correction and the unfreeze all see one
 * version record rather than three readings of the same rows.
 *
 * It stores no file bytes and no version history. Nextcloud `files_versions`
 * stays the only version store, exactly as the document-versions spec requires;
 * a record here says what is true about one version, not what it contained.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Stores and finds the finalisation record of a document version.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
class FinalDocumentRepository {

	/**
	 * The OpenRegister register holding Filinq's schemas.
	 *
	 * `filinq`, not `document`: this app declares one register since the
	 * consolidation in descriptor v8.0.0.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * The schema holding the finalisation records.
	 *
	 * @var string
	 */
	public const SCHEMA = 'documentVersion';

	/**
	 * The final state. Terminal: there is no transition out of it.
	 *
	 * @var string
	 */
	public const STATUS_FINAL = 'final';

	/**
	 * The draft state. Everything is draft until somebody makes it final.
	 *
	 * @var string
	 */
	public const STATUS_DRAFT = 'draft';

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
	 * Find every finalisation record for one document, newest last.
	 *
	 * 🔴 Returns null, not an empty array, when the register could not be read.
	 * The guard above this fails closed on null, and it can only do that if
	 * "no rows" and "no answer" arrive as different values. An unreachable
	 * register is exactly when an unnoticed write to a final document is most
	 * likely.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<int, array<string, mixed>>|null The records, or null when the register could not be read.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function findForFile(int $fileId): ?array {
		try {
			// 🔴 SLUGS GO THROUGH `searchObjectsBySlug`, NEVER `searchObjects`.
			// `searchObjects` has a numeric-id contract on `@self.register`
			// and `@self.schema`; handed `filinq` and `documentVersion` it
			// returns ZERO ROWS AND NO ERROR. Zero rows arrive here as `[]`
			// rather than `null`, so FinalDocumentService::refusalFor() walks
			// an empty list, returns no refusal, and assertWritable() lets the
			// write through. Every final document was writable.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: self::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['fileId' => $fileId]
			);
		} catch (DoesNotExistException $e) {
			// The register or the schema is not on this instance, so nothing
			// was ever finalised. That is an answer, not a failed read: an
			// instance that never imported `documentVersion` has no frozen
			// documents, and refusing every write over it would take editing
			// down for a feature nobody enabled.
			$this->logger->warning(
				message: '[FinalDocumentRepository] no documentVersion schema on this instance, so nothing is final',
				context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'error' => $e->getMessage()]
			);

			return [];
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[FinalDocumentRepository] could not read the finalisation records',
				context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'error' => $e->getMessage()]
			);

			return null;
		}

		if (is_array($results) === false) {
			return [];
		}

		$records = [];
		foreach ($results as $result) {
			$records[] = $this->normalise(row: $result);
		}

		return $records;

	}//end findForFile()

	/**
	 * Find the record describing a document's current content.
	 *
	 * The current content is the record with an empty `versionLabel`. A record
	 * that names a `files_versions` label describes an older set of bytes, and
	 * those are frozen by the file store rather than by this guard.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The record, or null when there is none.
	 *
	 * @throws RuntimeException When the register could not be read.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function findCurrent(int $fileId): ?array {
		$records = $this->findForFile(fileId: $fileId);
		if ($records === null) {
			throw new RuntimeException(
				message: 'Could not read the finalisation record of this document.'
			);
		}

		foreach ($records as $record) {
			if ((string)($record['versionLabel'] ?? '') === '') {
				return $record;
			}
		}

		return null;

	}//end findCurrent()

	/**
	 * Every document record one person created.
	 *
	 * @param string $userId The user id.
	 *
	 * @return array<int, array<string, mixed>> Their records.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function findByCreator(string $userId): array {
		if ($userId === '') {
			return [];
		}

		try {
			// Slugs, so `searchObjectsBySlug`. See findForFile() above.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: self::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['createdByUser' => $userId]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[FinalDocumentRepository] could not read one person\'s document records',
				context: ['file' => __FILE__, 'line' => __LINE__, 'userId' => $userId, 'error' => $e->getMessage()]
			);

			return [];
		}

		if (is_array($results) === false) {
			return [];
		}

		$records = [];
		foreach ($results as $result) {
			$records[] = $this->normalise(row: $result);
		}

		return $records;

	}//end findByCreator()

	/**
	 * Every document record linked to one domain.
	 *
	 * 🔴 The domain filter is applied in the READING, not in the query.
	 * OpenRegister's object filters are scalar equality: a filter on `domains`
	 * would be compared against an ARRAY of references and would answer the
	 * empty set with a confident zero rather than an error. Reading the rows
	 * and matching here is slower and correct.
	 *
	 * @param array<string, mixed> $domain The domain, as register, schema and id.
	 *
	 * @return array<int, array<string, mixed>> The records on that domain.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function findByDomain(array $domain): array {
		$reference = [
			'register' => (string)($domain['register'] ?? ''),
			'schema' => (string)($domain['schema'] ?? ''),
			'id' => (string)($domain['id'] ?? ''),
		];

		if ($reference['register'] === '' || $reference['schema'] === '' || $reference['id'] === '') {
			return [];
		}

		try {
			// Slugs, so `searchObjectsBySlug`. See findForFile() above. No
			// filter: the domain match is done in PHP below, deliberately.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: self::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: []
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[FinalDocumentRepository] could not read the document records of a domain',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return [];
		}

		if (is_array($results) === false) {
			return [];
		}

		$records = [];
		foreach ($results as $result) {
			$record = $this->normalise(row: $result);
			if ($this->sitsIn(record: $record, reference: $reference) === true) {
				$records[] = $record;
			}
		}

		return $records;

	}//end findByDomain()

	/**
	 * Whether one record names the given domain among its own.
	 *
	 * A domain is matched on all three of register, schema and id. Matching on
	 * fewer would put a record in a domain it only half belongs to, and the
	 * domains are what decides who may read it.
	 *
	 * @param array<string, mixed> $record The record.
	 * @param array<string, string> $reference The domain, as register, schema and id.
	 *
	 * @return bool True when the record names it.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function sitsIn(array $record, array $reference): bool {
		$domains = ($record['domains'] ?? []);
		if (is_array($domains) === false) {
			return false;
		}

		foreach ($domains as $candidate) {
			if (is_array($candidate) === false) {
				continue;
			}

			if ($this->sameDomain(candidate: $candidate, reference: $reference) === true) {
				return true;
			}
		}

		return false;

	}//end sitsIn()

	/**
	 * Whether one domain entry is the domain asked for.
	 *
	 * @param array<string, mixed> $candidate The entry on the record.
	 * @param array<string, string> $reference The domain asked for.
	 *
	 * @return bool True when all three fields match.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function sameDomain(array $candidate, array $reference): bool {
		foreach ($reference as $key => $value) {
			if ((string)($candidate[$key] ?? '') !== $value) {
				return false;
			}
		}

		return true;

	}//end sameDomain()

	/**
	 * Find one record by its uuid.
	 *
	 * @param string $uuid The record uuid.
	 *
	 * @return array<string, mixed>|null The record, or null when there is none.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function findByUuid(string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		try {
			// Find, not getObject: OpenRegister's getObject() takes no
			// arguments and answers with the service's current object context,
			// so the named arguments below would have thrown and this method
			// would have answered null for every record that exists.
			$object = $this->objectResolver->resolve()->find(
				id: $uuid,
				register: self::REGISTER,
				schema: self::SCHEMA
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[FinalDocumentRepository] could not read one finalisation record',
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
	 * Write a finalisation record, creating it when its uuid is not known yet.
	 *
	 * @param array<string, mixed> $record The record to store.
	 * @param string|null $uuid The uuid to write under, or null to create one.
	 *
	 * @return array<string, mixed> The stored record.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function save(array $record, ?string $uuid = null): array {
		unset($record['uuid']);

		try {
			$objectService = $this->objectResolver->resolve();
			$arguments = [
				'object'   => $record,
				'register' => self::REGISTER,
				'schema'   => self::SCHEMA,
			];
			if ($uuid !== null) {
				$arguments['uuid'] = $uuid;
			}

			$stored = $objectService->saveObject(...$arguments);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[FinalDocumentRepository] could not store a finalisation record',
				context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => ($record['fileId'] ?? null), 'error' => $e->getMessage()]
			);

			throw new RuntimeException(
				message: 'Could not store the finalisation record: ' . $e->getMessage(),
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
	 * Read one OpenRegister row into the flat record shape this app uses.
	 *
	 * OpenRegister answers with an entity, an array, or an array wrapping the
	 * data under `object`, depending on the caller and the version. Reading all
	 * three here keeps every consumer from re-discovering that.
	 *
	 * @param mixed $row The row as OpenRegister returned it.
	 *
	 * @return array<string, mixed> The flat record, including its `uuid`.
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

		$uuid = (string)($fields['uuid'] ?? ($data['uuid'] ?? ($self['uuid'] ?? ($data['id'] ?? ''))));

		$fields['uuid'] = $uuid;

		return $fields;

	}//end normalise()
}//end class

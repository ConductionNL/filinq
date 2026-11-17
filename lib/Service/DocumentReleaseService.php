<?php

/**
 * Document Release Service
 *
 * Releases a document to the person who requested it, on the record their
 * request is, and withdraws a release. The portal lists a person's releases
 * and serves each document from them.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\FinalDocumentRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\DocumentReleaseServiceInterface;
use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Exception\DomainLinkRefusedException;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\SaveActor;
use OCP\IUserSession;
use RuntimeException;
use Throwable;

/**
 * Writes and reads `documentRelease` records.
 *
 * Both writes pass `_rbac: false` and store the record unowned: the schema
 * lets nobody write a release through the API, and this service decides who
 * may, by asking whether the caller may write the record the document is
 * released on. The portal read is a system read by the release id that
 * portaliq has already proven belongs to the person asking.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
 */
class DocumentReleaseService implements DocumentReleaseServiceInterface {
	use ReadsCurrentUserId;
	use ReadsStoredObjectRows;

	/**
	 * The register the releases live in.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * The schema of a release.
	 *
	 * @var string
	 */
	public const SCHEMA = 'documentRelease';

	/**
	 * The kinds a release may have.
	 *
	 * @var array<int, string>
	 */
	private const KINDS = ['decision', 'document'];

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param FinalDocumentRepositoryInterface       $documents      The document records.
	 * @param IntakeAuthorizationGate       $recordGate     Whether the caller may write the record.
	 * @param IUserSession                  $userSession    The current session.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly FinalDocumentRepositoryInterface $documents,
		private readonly IntakeAuthorizationGate $recordGate,
		private readonly IUserSession $userSession,
	) {

	}//end __construct()

	/**
	 * Release one document to the person who requested a record.
	 *
	 * The caller must be allowed to write the record, and the document must
	 * be on it: a document cannot be released on a record it is not part of.
	 *
	 * @param string               $uuid       The document record.
	 * @param array<string, mixed> $record     The record, as register, schema and id.
	 * @param string               $contactRef The requester's contact reference.
	 * @param string               $kind       `decision` or `document`.
	 *
	 * @return array<string, mixed> The stored release.
	 *
	 * @throws DomainLinkRefusedException When the caller may not write the record.
	 * @throws RuntimeException When the request is incomplete or the release cannot be stored.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
	 */
	public function release(string $uuid, array $record, string $contactRef, string $kind = 'document'): array {
		$record = $this->requireRecord(record: $record);
		$contactRef = trim($contactRef);
		if ($contactRef === '') {
			throw new RuntimeException(message: 'A release names the contact of the person it is for.');
		}

		if (in_array($kind, self::KINDS, true) === false) {
			throw new RuntimeException(message: 'A release is a decision or a document.');
		}

		$this->requireWritable(record: $record);

		$document = $this->documents->findByUuid(uuid: $uuid);
		if ($document === null || $this->isOn(document: $document, record: $record) === false) {
			throw new RuntimeException(message: 'That document is not on that record.');
		}

		$release = [
			'document' => $uuid,
			'documentName' => (string)($document['documentName'] ?? ''),
			'kind' => $kind,
			'fileId' => (int)($document['fileId'] ?? 0),
			'recordRegister' => $record['register'],
			'recordSchema' => $record['schema'],
			'recordId' => $record['id'],
			'contactRef' => $contactRef,
			'releasedBy' => $this->currentUserId(),
			'releasedAt' => $this->now(),
		];

		return $this->store(release: $release, uuid: null);

	}//end release()

	/**
	 * Withdraw a release, so the portal no longer lists or serves it.
	 *
	 * @param string $releaseId The release.
	 *
	 * @return array<string, mixed> The withdrawn release.
	 *
	 * @throws DomainLinkRefusedException When the caller may not write the release's record.
	 * @throws RuntimeException When there is no such release or it cannot be stored.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
	 */
	public function withdraw(string $releaseId): array {
		$release = $this->find(releaseId: $releaseId);
		if ($release === null) {
			throw new RuntimeException(message: 'There is no release with that id.');
		}

		$this->requireWritable(
			record: [
				'register' => (string)($release['recordRegister'] ?? ''),
				'schema' => (string)($release['recordSchema'] ?? ''),
				'id' => (string)($release['recordId'] ?? ''),
			]
		);

		if ((string)($release['withdrawnAt'] ?? '') !== '') {
			return $release;
		}

		$release['withdrawnBy'] = $this->currentUserId();
		$release['withdrawnAt'] = $this->now();
		unset($release['uuid']);

		return $this->store(release: $release, uuid: $releaseId);

	}//end withdraw()

	/**
	 * The documents of one release as the portal lists them, without where the file lives in the browser.
	 *
	 * A withdrawn release, an unknown id or an unreadable store lists
	 * nothing. Portaliq calls this only for a release it has proven is the
	 * person's own, and keeps the `file` reference on the server.
	 *
	 * @param string $releaseId The release.
	 *
	 * @return array<int, array<string, mixed>> The entry, or none.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
	 */
	public function portalEntries(string $releaseId): array {
		try {
			$release = $this->find(releaseId: $releaseId);
		} catch (Throwable) {
			return [];
		}

		if ($release === null || (string)($release['withdrawnAt'] ?? '') !== '' || (int)($release['fileId'] ?? 0) <= 0) {
			return [];
		}

		return [
			[
				'id' => $releaseId,
				'title' => (string)($release['documentName'] ?? ''),
				'kind' => (string)($release['kind'] ?? 'document'),
				'date' => (string)($release['releasedAt'] ?? ''),
				'file' => [
					'register' => (string)($release['recordRegister'] ?? ''),
					'schema' => (string)($release['recordSchema'] ?? ''),
					'id' => (string)($release['recordId'] ?? ''),
					'fileId' => (string)$release['fileId'],
				],
			],
		];

	}//end portalEntries()

	/**
	 * One release by id, read as the system.
	 *
	 * @param string $releaseId The release.
	 *
	 * @return array<string, mixed>|null The release, or null when there is none.
	 *
	 * @throws RuntimeException When the store cannot be read.
	 */
	private function find(string $releaseId): ?array {
		if (trim($releaseId) === '') {
			return null;
		}

		try {
			$row = $this->objectResolver->resolve()->find(
				id: $releaseId,
				options: new ObjectFindOptions(register: self::REGISTER, schema: self::SCHEMA),
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'Could not read the release: ' . $e->getMessage(), code: 0, previous: $e);
		}

		if ($row === null) {
			return null;
		}

		$fields = $this->storedFields(row: $row);
		if ($fields === []) {
			return null;
		}

		return $fields;

	}//end find()

	/**
	 * Store a release as the system.
	 *
	 * @param array<string, mixed> $release The release.
	 * @param string|null          $uuid    The release to update, or null for a new one.
	 *
	 * @return array<string, mixed> The stored release.
	 *
	 * @throws RuntimeException When it cannot be stored.
	 */
	private function store(array $release, ?string $uuid): array {
		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $release,
				options: new ObjectSaveOptions(
					target: new ObjectFindTarget(register: self::REGISTER, schema: self::SCHEMA),
					uuid: $uuid,
					actor: new SaveActor(unowned: true)
				),
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'Could not store the release: ' . $e->getMessage(), code: 0, previous: $e);
		}

		return array_merge($release, $this->storedFields(row: $stored));

	}//end store()

	/**
	 * Refuse a caller who may not write the record.
	 *
	 * @param array<string, string> $record The record, as register, schema and id.
	 *
	 * @return void
	 *
	 * @throws DomainLinkRefusedException When the caller may not write it.
	 */
	private function requireWritable(array $record): void {
		if ($record['register'] !== '' && $record['schema'] !== '' && $record['id'] !== ''
			&& $this->recordGate->mayFileOnto(register: $record['register'], schema: $record['schema'], id: $record['id']) === true
		) {
			return;
		}

		throw new DomainLinkRefusedException(message: 'You may not change the documents of that record.');

	}//end requireWritable()

	/**
	 * The record as three trimmed parts, refused when one is missing.
	 *
	 * @param array<string, mixed> $record The record as given.
	 *
	 * @return array<string, string> Register, schema and id.
	 *
	 * @throws RuntimeException When a part is missing.
	 */
	private function requireRecord(array $record): array {
		$reference = [
			'register' => trim((string)($record['register'] ?? '')),
			'schema' => trim((string)($record['schema'] ?? '')),
			'id' => trim((string)($record['id'] ?? '')),
		];
		if (in_array('', $reference, true) === true) {
			throw new RuntimeException(message: 'A release names the register, the schema and the id of the record.');
		}

		return $reference;

	}//end requireRecord()

	/**
	 * Whether the document is linked to the record.
	 *
	 * @param array<string, mixed>  $document The document record.
	 * @param array<string, string> $record   The record.
	 *
	 * @return bool True when one of its domains is the record.
	 */
	private function isOn(array $document, array $record): bool {
		$domains = ($document['domains'] ?? []);
		if (is_array($domains) === false) {
			return false;
		}

		foreach ($domains as $domain) {
			if (is_array($domain) === true
				&& (string)($domain['register'] ?? '') === $record['register']
				&& (string)($domain['schema'] ?? '') === $record['schema']
				&& (string)($domain['id'] ?? '') === $record['id']
			) {
				return true;
			}
		}

		return false;

	}//end isOn()

	/**
	 * Now, in ISO 8601.
	 *
	 * @return string The timestamp.
	 *
	 * @spec exclude Clock accessor with no behaviour of its own.
	 */
	private function now(): string {
		return (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

	}//end now()
}//end class

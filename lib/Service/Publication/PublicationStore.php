<?php

/**
 * Publication store
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Publication;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

/**
 * Where publications, their log and their platform counterpart are kept.
 *
 * Publication records and log entries are objects in Filinq's register.
 * The platform counterpart is an object in OpenCatalogi's `publication`
 * register, written through OpenRegister like any other object, so the
 * acting user's rights on that register apply.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/woo-publicatie-pipeline/specs/woo-publicatie-pipeline/spec.md
 */
class PublicationStore {

	/**
	 * Filinq's register.
	 */
	public const REGISTER = 'filinq';

	/**
	 * The record schema.
	 */
	public const RECORD = 'publicationRecord';

	/**
	 * The log schema.
	 */
	public const LOG = 'publicationLogEntry';

	/**
	 * OpenRegister's file service, by name so Filinq loads without it.
	 */
	private const FILE_SERVICE = 'OCA\OpenRegister\Service\FileService';

	/**
	 * OpenCatalogi's TOOI value lists, by name so Filinq loads without it.
	 */
	private const TOOI = 'OCA\OpenCatalogi\Service\TooiVocabularyService';

	/**
	 * Constructor
	 *
	 * @param DocumentObjectServiceResolver $objects    OpenRegister's object service
	 * @param IAppManager                   $appManager Whether OpenCatalogi is there
	 * @param ContainerInterface            $container  OpenRegister's file service
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objects,
		private readonly IAppManager $appManager,
		private readonly ContainerInterface $container,
	) {

	}//end __construct()

	/**
	 * Store a record.
	 *
	 * @param array<string, mixed> $record The record
	 * @param string|null          $uuid   Its id, for an update
	 *
	 * @return array<string, mixed> The stored record, with uuid.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.2
	 */
	public function saveRecord(array $record, ?string $uuid = null): array {
		return $this->save(register: self::REGISTER, schema: self::RECORD, object: $record, uuid: $uuid);

	}//end saveRecord()

	/**
	 * Read a record.
	 *
	 * @param string $uuid The id
	 *
	 * @return array<string, mixed>|null The record.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.2
	 */
	public function findRecord(string $uuid): ?array {
		return $this->find(register: self::REGISTER, schema: self::RECORD, uuid: $uuid);

	}//end findRecord()

	/**
	 * Every record, or those matching the filters.
	 *
	 * @param array<string, mixed> $filters Property filters
	 *
	 * @return array<int, array<string, mixed>> The records.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-3.1
	 */
	public function listRecords(array $filters = []): array {
		return $this->search(schema: self::RECORD, filters: $filters);

	}//end listRecords()

	/**
	 * Append a log entry. There is no way to change one.
	 *
	 * @param array<string, mixed> $entry The entry
	 *
	 * @return array<string, mixed> The stored entry.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	public function appendLog(array $entry): array {
		return $this->save(register: self::REGISTER, schema: self::LOG, object: $entry, uuid: null);

	}//end appendLog()

	/**
	 * The log of one record, oldest first.
	 *
	 * @param string $recordUuid The record
	 *
	 * @return array<int, array<string, mixed>> The entries.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	public function logFor(string $recordUuid): array {
		$entries = $this->search(schema: self::LOG, filters: ['publicationRecordRef' => $recordUuid]);
		usort($entries, static fn (array $a, array $b): int => strcmp((string) ($a['timestamp'] ?? ''), (string) ($b['timestamp'] ?? '')));

		return $entries;

	}//end logFor()

	/**
	 * The redacted copy of a document, from its anonymisation link.
	 *
	 * @param string $fileId The original's file id
	 *
	 * @return string|null The redacted copy's file id.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.2
	 */
	public function findRedactedCopy(string $fileId): ?string {
		foreach ($this->search(schema: 'anonymizationLink', filters: ['sourceFileId' => $fileId]) as $link) {
			$copy = (string) ($link['anonymizedFileId'] ?? '');
			if ($copy !== '') {
				return $copy;
			}
		}

		return null;

	}//end findRedactedCopy()

	/**
	 * Whether the publication platform (OpenCatalogi) is there.
	 *
	 * @return bool True when OpenCatalogi is enabled.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function platformAvailable(): bool {
		return $this->appManager->isEnabledForAnyone('opencatalogi');

	}//end platformAvailable()

	/**
	 * The Woo information categories, from OpenCatalogi's own TOOI value list.
	 *
	 * @return list<array{code: string, label: string}> The categories, empty when OpenCatalogi is not there.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-3.1
	 */
	public function categories(): array {
		if ($this->platformAvailable() === false) {
			return [];
		}

		try {
			$list = $this->container->get(self::TOOI)->informatiecategorieList();
		} catch (Throwable) {
			return [];
		}

		$categories = [];
		foreach ((array) $list as $entry) {
			$categories[] = ['code' => basename((string) ($entry['uri'] ?? '')), 'label' => (string) ($entry['label'] ?? '')];
		}

		return $categories;

	}//end categories()

	/**
	 * Create or update the platform's publication object.
	 *
	 * @param array<string, mixed> $publication The fields to write
	 * @param string|null          $uuid        The existing object's id
	 *
	 * @return array<string, mixed> The stored object, with uuid.
	 *
	 * @throws RuntimeException When OpenRegister refuses, for example for a missing right.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function savePlatformPublication(array $publication, ?string $uuid = null): array {
		if ($uuid !== null && $uuid !== '') {
			$current = $this->find(register: OpenCatalogiPublicationMap::REGISTER, schema: OpenCatalogiPublicationMap::SCHEMA, uuid: $uuid);
			$publication = array_merge(($current ?? []), $publication);
		}

		return $this->save(
			register: OpenCatalogiPublicationMap::REGISTER,
			schema: OpenCatalogiPublicationMap::SCHEMA,
			object: $publication,
			uuid: $uuid
		);

	}//end savePlatformPublication()

	/**
	 * Attach the redacted copy to the platform's publication, shared so the public sees it.
	 *
	 * @param string $publicationUuid The platform's publication
	 * @param string $fileName        The file name
	 * @param string $content         The bytes
	 *
	 * @return void
	 *
	 * @throws RuntimeException When OpenRegister cannot attach it.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function attachToPlatformPublication(string $publicationUuid, string $fileName, string $content): void {
		try {
			$this->container->get(self::FILE_SERVICE)->addFile(
				objectEntity: $publicationUuid,
				fileName: $fileName,
				content: $content,
				share: true
			);
		} catch (Throwable $e) {
			throw new RuntimeException('The redacted copy could not be attached to the publication: ' . $e->getMessage(), 0, $e);
		}

	}//end attachToPlatformPublication()

	/**
	 * One save.
	 *
	 * @param string               $register The register slug
	 * @param string               $schema   The schema slug
	 * @param array<string, mixed> $object   The object
	 * @param string|null          $uuid     The id, for an update
	 *
	 * @return array<string, mixed> The stored object.
	 *
	 * @throws RuntimeException When OpenRegister refuses.
	 */
	private function save(string $register, string $schema, array $object, ?string $uuid): array {
		unset($object['uuid'], $object['@self'], $object['log']);
		$arguments = ['object' => $object, 'register' => $register, 'schema' => $schema];
		if ($uuid !== null && $uuid !== '') {
			$arguments['uuid'] = $uuid;
		}

		try {
			$stored = $this->normalise(row: $this->objects->resolve()->saveObject(...$arguments));
		} catch (Throwable $e) {
			throw new RuntimeException('OpenRegister refused the ' . $schema . ': ' . $e->getMessage(), (int) $e->getCode(), $e);
		}

		if (($stored['uuid'] ?? '') === '' && $uuid !== null) {
			$stored['uuid'] = $uuid;
		}

		return $stored;

	}//end save()

	/**
	 * One read.
	 *
	 * @param string $register The register slug
	 * @param string $schema   The schema slug
	 * @param string $uuid     The id
	 *
	 * @return array<string, mixed>|null The object.
	 */
	private function find(string $register, string $schema, string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		try {
			$object = $this->objects->resolve()->find(id: $uuid, register: $register, schema: $schema);
		} catch (Throwable) {
			return null;
		}

		if ($object === null) {
			return null;
		}

		return $this->normalise(row: $object);

	}//end find()

	/**
	 * A search in Filinq's register.
	 *
	 * @param string               $schema  The schema slug
	 * @param array<string, mixed> $filters Property filters
	 *
	 * @return array<int, array<string, mixed>> The objects.
	 */
	private function search(string $schema, array $filters): array {
		// Slugs go through searchObjectsBySlug: searchObjects answers a slug with zero rows.
		$rows = $this->objects->resolve()->searchObjectsBySlug(registerSlug: self::REGISTER, schemaSlug: $schema, filters: $filters);

		return array_values(array_filter(array_map(fn (mixed $row): array => $this->normalise(row: $row), (array) $rows)));

	}//end search()

	/**
	 * An object as a flat array with its uuid.
	 *
	 * @param mixed $row What OpenRegister returned
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function normalise(mixed $row): array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		$fields = $data;
		if (is_array($data['object'] ?? null) === true) {
			$fields = $data['object'];
		}

		unset($fields['@self']);
		$fields['uuid'] = (string) ($fields['uuid'] ?? ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? ''))));

		return $fields;

	}//end normalise()
}//end class

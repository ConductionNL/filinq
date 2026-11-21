<?php

/**
 * Retention Surface Service
 *
 * The destruction date a publication record hands on, read from where it is
 * decided: the zaaksysteem that sent the document, else OpenRegister's
 * retention block on the record that holds it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-destruction-dates-propagate-to-the-publication-pipeline-with-source-precedence-req-ddare-007
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\LegalHold\LegalHoldRecordFreeze;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Picks the destruction date of a published document by precedence.
 *
 * 1. A vernietigingsdatum the zaaksysteem sent with the document (the bridge's
 *    `externalDocument`) wins, named after that source.
 * 2. Otherwise `retention.archiefactiedatum` of a record appraised
 *    `vernietigen`, named after its selectielijst category.
 * 3. Otherwise there is no date, and nothing is blocked.
 *
 * It computes nothing: OpenRegister derives `archiefactiedatum` and the
 * zaaksysteem sends its own date; this class only chooses between them. Any
 * read that fails counts as "no date", because a destruction date is
 * propagation metadata, never a reason to stop a publication.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-destruction-dates-propagate-to-the-publication-pipeline-with-source-precedence-req-ddare-007
 */
class RetentionSurfaceService {

	/**
	 * The source a selectielijst-derived date names, with the category.
	 *
	 * @var string
	 */
	public const ENGINE_SOURCE = 'Archiefwet 1995, selectielijst categorie ';

	/**
	 * The source a selectielijst-derived date names when the record has no category.
	 *
	 * @var string
	 */
	public const ENGINE_SOURCE_UNCLASSIFIED = 'Archiefwet 1995, selectielijst';

	/**
	 * The source named when a bridge document's source has no name.
	 *
	 * @var string
	 */
	public const BRIDGE_SOURCE_UNNAMED = 'zaaksysteem';

	/**
	 * The appraisal that carries a destruction date.
	 *
	 * @var string
	 */
	private const DESTROY = 'vernietigen';

	/**
	 * A folder name OpenRegister gives an object folder.
	 *
	 * @var string
	 */
	private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

	/**
	 * The calendar date at the start of a date or date-time.
	 *
	 * @var string
	 */
	private const DATE = '/^(\d{4}-\d{2}-\d{2})(?:$|T)/';

	/**
	 * Constructor.
	 *
	 * @param IRootFolder                   $rootFolder     The file tree, read as the server.
	 * @param LegalHoldRecordFreeze         $records        Loads the record that holds a file, without the caller's rights.
	 * @param DocumentObjectServiceResolver $objectResolver OpenRegister's ObjectService, for the bridge records.
	 * @param LoggerInterface               $logger         Where an unreadable source is noted.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly LegalHoldRecordFreeze $records,
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * The destruction date and its source for a publication record's document.
	 *
	 * @param array<string, mixed> $record The publication record (`documentFileRef`).
	 *
	 * @return array{date: string, source: string}|null The date (Y-m-d) and where it came from, or null when there is none.
	 *
	 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-destruction-dates-propagate-to-the-publication-pipeline-with-source-precedence-req-ddare-007
	 */
	public function destructionDateFor(array $record): ?array {
		$fileId = (int) ($record['documentFileRef'] ?? 0);
		if ($fileId <= 0) {
			return null;
		}

		return ($this->fromBridge(fileId: $fileId) ?? $this->fromRetention(fileId: $fileId));

	}//end destructionDateFor()

	/**
	 * The vernietigingsdatum the zaaksysteem sent with the document, if any.
	 *
	 * @param integer $fileId The document's file id.
	 *
	 * @return array{date: string, source: string}|null The date and the source system.
	 */
	private function fromBridge(int $fileId): ?array {
		try {
			foreach ($this->search(schema: BridgeService::SCHEMA_DOCUMENT, filters: ['stagedFileRef' => $fileId]) as $document) {
				if ((int) ($document['stagedFileRef'] ?? 0) !== $fileId) {
					continue;
				}

				$date = self::dateOf(value: ($document['vernietigingsdatum'] ?? null));
				if ($date !== null) {
					return ['date' => $date, 'source' => $this->sourceName(sourceId: (string) ($document['sourceId'] ?? ''))];
				}
			}
		} catch (Throwable $e) {
			$this->unread(what: 'the bridge record', fileId: $fileId, error: $e);
		}

		return null;

	}//end fromBridge()

	/**
	 * The name of the bridge source a document came from.
	 *
	 * @param string $sourceId The `bridgeSource` uuid.
	 *
	 * @return string Its name, its vendor, or "zaaksysteem" when it has neither.
	 */
	private function sourceName(string $sourceId): string {
		foreach ($this->search(schema: BridgeService::SCHEMA_SOURCE, filters: []) as $source) {
			if ($sourceId === '' || (string) ($source['uuid'] ?? '') !== $sourceId) {
				continue;
			}

			$name = trim((string) ($source['name'] ?? ''));
			if ($name === '') {
				$name = trim((string) ($source['vendor'] ?? ''));
			}

			if ($name !== '') {
				return $name;
			}
		}

		return self::BRIDGE_SOURCE_UNNAMED;

	}//end sourceName()

	/**
	 * The archiefactiedatum OpenRegister derived for the record that holds the file.
	 *
	 * @param integer $fileId The document's file id.
	 *
	 * @return array{date: string, source: string}|null The date and its selectielijst category, null unless appraised `vernietigen` with a date.
	 */
	private function fromRetention(int $fileId): ?array {
		$retention = $this->retentionOf(fileId: $fileId);
		if (strtolower(trim((string) ($retention['archiefnominatie'] ?? ''))) !== self::DESTROY) {
			return null;
		}

		$date = self::dateOf(value: ($retention['archiefactiedatum'] ?? null));
		if ($date === null) {
			return null;
		}

		$category = trim((string) ($retention['classificatie'] ?? ''));
		$source = self::ENGINE_SOURCE_UNCLASSIFIED;
		if ($category !== '') {
			$source = self::ENGINE_SOURCE . $category;
		}

		return ['date' => $date, 'source' => $source];

	}//end fromRetention()

	/**
	 * The retention block of the record whose object folder holds the file.
	 *
	 * OpenRegister names an object's folder after its uuid; a file anywhere
	 * else belongs to no record and has no retention here.
	 *
	 * @param integer $fileId The file id.
	 *
	 * @return array<string, mixed> The retention block, empty when there is none or it cannot be read.
	 */
	private function retentionOf(int $fileId): array {
		try {
			$node = $this->rootFolder->getFirstNodeById($fileId);
			if ($node === null) {
				return [];
			}

			$uuid = (string) $node->getParent()->getName();
			if (preg_match(self::UUID, $uuid) !== 1) {
				return [];
			}

			$entity = $this->records->load(ref: $uuid);
			if ($entity === null) {
				return [];
			}

			return (array) ($entity->getRetention() ?? []);
		} catch (Throwable $e) {
			$this->unread(what: 'the record holding the file', fileId: $fileId, error: $e);
			return [];
		}//end try

	}//end retentionOf()

	/**
	 * Search one bridge schema as the server.
	 *
	 * @param string               $schema  The schema slug.
	 * @param array<string, mixed> $filters The filters.
	 *
	 * @return array<int, array<string, mixed>> The rows as flat fields.
	 */
	private function search(string $schema, array $filters): array {
		$results = $this->objectResolver->resolve()->searchObjectsBySlug(
			registerSlug: BridgeService::REGISTER,
			schemaSlug: $schema,
			filters: $filters,
			_rbac: false
		);
		if (is_array($results) === false) {
			return [];
		}

		$rows = [];
		foreach ($results as $result) {
			$rows[] = (new StoredRecordFields())->read(row: $result);
		}

		return $rows;

	}//end search()

	/**
	 * Note a source that could not be read; the date then counts as absent.
	 *
	 * @param string    $what   What could not be read.
	 * @param integer   $fileId The document's file id.
	 * @param Throwable $error  Why.
	 *
	 * @return void
	 */
	private function unread(string $what, int $fileId, Throwable $error): void {
		$this->logger->warning(
			message: '[RetentionSurfaceService] ' . $what . ' could not be read, so no destruction date is taken from it',
			context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'error' => $error->getMessage()]
		);

	}//end unread()

	/**
	 * The calendar date of a date or date-time value.
	 *
	 * @param mixed $value The stored value.
	 *
	 * @return string|null The date (Y-m-d), or null when the value is no date.
	 */
	private static function dateOf(mixed $value): ?string {
		if (is_string($value) === false || preg_match(self::DATE, trim($value), $match) !== 1) {
			return null;
		}

		return $match[1];

	}//end dateOf()
}//end class

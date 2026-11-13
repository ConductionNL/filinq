<?php

/**
 * Bridge Service: filinq's side of the case-system document bridge.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/zgw-document-bridge
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use OCP\AppFramework\Utility\ITimeFactory;
use RuntimeException;
use Throwable;

/**
 * Reads the bridge sources with their computed health and moves staged
 * documents through filinq's half of the processing lifecycle
 * (zgw-document-bridge D3, D5).
 *
 * The contract with OpenConnector, which owns every call to the case system:
 * OpenConnector creates `externalDocument` objects as `staged` and writes
 * `lastSyncAt` / `lastSyncStatus` / `lastSyncError` on the `bridgeSource`
 * after each run. filinq moves a document to `in_processing`, `processed`
 * and `ready_for_writeback` only. A push-direction synchronization watches
 * `ready_for_writeback`, writes the derivative to the case system as a NEW
 * informatieobject related to the same zaak (title suffixed
 * "(geanonimiseerd)", a relation to the original's identificatie, filinq's
 * processing metadata), never touching the original, and sets
 * `written_back` with `resultExternalId` or `writeback_failed` with
 * `writeBackError`. There is no ZGW, StUF or HTTP client here.
 *
 * @spec openspec/changes/zgw-document-bridge
 */
class BridgeService {

	public const REGISTER = 'filinq';

	public const SCHEMA_SOURCE = 'bridgeSource';

	public const SCHEMA_DOCUMENT = 'externalDocument';

	public const HEALTH_FRESH = 'fresh';

	public const HEALTH_STALE = 'stale';

	public const HEALTH_FAILING = 'failing';

	public const HEALTH_INACTIVE = 'inactive';

	/**
	 * The freshness promise: a successful sync within 24 hours.
	 */
	public const FRESH_SECONDS = 86400;

	/**
	 * Filinq's transitions: from, to.
	 */
	private const TRANSITIONS = [
		'release' => ['processed', 'ready_for_writeback'],
		'retry' => ['writeback_failed', 'ready_for_writeback'],
	];

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's object service.
	 * @param ITimeFactory                  $time           The clock health is computed against.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly ITimeFactory $time,
	) {
	}//end __construct()

	/**
	 * Every bridge source with its health.
	 *
	 * @return array<int, array<string, mixed>> The sources, each with `health`.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function sources(): array {
		$now = DateTimeImmutable::createFromMutable($this->time->getDateTime('now', new DateTimeZone('UTC')));
		$sources = [];
		foreach ($this->search(schema: self::SCHEMA_SOURCE, filters: []) as $source) {
			$sources[] = $source + ['health' => self::health(source: $source, now: $now)];
		}

		return $sources;
	}//end sources()

	/**
	 * The documents still staged, optionally of one source.
	 *
	 * @param string $sourceId A bridge source's uuid, or empty for every source.
	 *
	 * @return array<int, array<string, mixed>> The staged documents.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function stagedDocuments(string $sourceId = ''): array {
		$filters = ['processingStatus' => 'staged'];
		if ($sourceId !== '') {
			$filters['sourceId'] = $sourceId;
		}

		return $this->search(schema: self::SCHEMA_DOCUMENT, filters: $filters);
	}//end stagedDocuments()

	/**
	 * The health of one source at a moment (D5): inactive, failing, fresh or stale.
	 *
	 * @param array<string, mixed> $source The bridge source.
	 * @param DateTimeImmutable    $now    The moment.
	 *
	 * @return string One of the HEALTH_* constants.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public static function health(array $source, DateTimeImmutable $now): string {
		if (($source['active'] ?? true) === false) {
			return self::HEALTH_INACTIVE;
		}

		if (($source['lastSyncStatus'] ?? '') === 'error') {
			return self::HEALTH_FAILING;
		}

		try {
			$last = new DateTimeImmutable((string)($source['lastSyncAt'] ?? ''));
		} catch (Throwable $e) {
			return self::HEALTH_STALE;
		}

		if (trim((string)($source['lastSyncAt'] ?? '')) === '' || ($now->getTimestamp() - $last->getTimestamp()) >= self::FRESH_SECONDS) {
			return self::HEALTH_STALE;
		}

		return self::HEALTH_FRESH;
	}//end health()

	/**
	 * Release a processed document's derivative for write-back.
	 *
	 * @param string $id The external document's uuid.
	 *
	 * @return array<string, mixed> The saved document.
	 *
	 * @throws InvalidArgumentException When it is not processed or has no result file.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function release(string $id): array {
		$document = $this->document(id: $id);
		if (is_numeric($document['resultFileRef'] ?? null) === false) {
			throw new InvalidArgumentException('A document can only be released with its redacted result file.');
		}

		return $this->transition(document: $document, action: 'release', changes: []);
	}//end release()

	/**
	 * Try a failed write-back again; the failure reason is cleared.
	 *
	 * @param string $id The external document's uuid.
	 *
	 * @return array<string, mixed> The saved document.
	 *
	 * @throws InvalidArgumentException When the write-back did not fail.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function retry(string $id): array {
		return $this->transition(document: $this->document(id: $id), action: 'retry', changes: ['writeBackError' => null]);
	}//end retry()

	/**
	 * Move a document along one of filinq's transitions and save the whole object.
	 *
	 * @param array<string, mixed> $document The document as read, with its uuid.
	 * @param string               $action   A key of TRANSITIONS.
	 * @param array<string, mixed> $changes  Further fields to write.
	 *
	 * @return array<string, mixed> The saved document.
	 *
	 * @throws InvalidArgumentException When the document is not in the transition's from state.
	 */
	private function transition(array $document, string $action, array $changes): array {
		[$from, $to] = self::TRANSITIONS[$action];
		$status = (string)($document['processingStatus'] ?? '');
		if ($status !== $from) {
			if ($status === '') {
				$status = 'statusless';
			}

			throw new InvalidArgumentException(sprintf('A %s document cannot be moved to %s.', $status, $to));
		}

		$uuid = (string)$document['uuid'];
		unset($document['uuid'], $document['@self']);
		$saved = $this->objectResolver->resolve()->saveObject(
			object: array_merge($document, $changes, ['processingStatus' => $to]),
			register: self::REGISTER,
			schema: self::SCHEMA_DOCUMENT,
			uuid: $uuid
		);

		return (new StoredRecordFields())->read(row: $saved);
	}//end transition()

	/**
	 * One external document by uuid.
	 *
	 * @param string $id The uuid.
	 *
	 * @return array<string, mixed> The document, with its uuid.
	 *
	 * @throws RuntimeException When there is none.
	 */
	private function document(string $id): array {
		$found = $this->objectResolver->resolve()->find(id: $id, register: self::REGISTER, schema: self::SCHEMA_DOCUMENT);
		$document = (new StoredRecordFields())->read(row: $found);
		if ($found === null || $document === []) {
			throw new RuntimeException('External document not found.', 404);
		}

		if ($document['uuid'] === '') {
			$document['uuid'] = $id;
		}

		return $document;
	}//end document()

	/**
	 * The rows of one bridge schema.
	 *
	 * @param string               $schema  The schema slug.
	 * @param array<string, mixed> $filters Equality filters.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function search(string $schema, array $filters): array {
		$results = $this->objectResolver->resolve()->searchObjectsBySlug(registerSlug: self::REGISTER, schemaSlug: $schema, filters: $filters);
		if (is_array($results) === false) {
			return [];
		}

		$rows = [];
		foreach ($results as $result) {
			$rows[] = (new StoredRecordFields())->read(row: $result);
		}

		return $rows;
	}//end search()
}//end class

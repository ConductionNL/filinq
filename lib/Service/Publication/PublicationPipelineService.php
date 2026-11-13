<?php

/**
 * Publication pipeline
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

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use OCA\Filinq\Exception\PublicationNotReadyException;
use OCA\Filinq\Kernel\Contracts\PublicationPipelineServiceInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use RuntimeException;

/**
 * A document's way from Filinq to the publication platform, logged.
 *
 * Filinq prepares and tracks; OpenCatalogi publishes. Every step appends a
 * `publicationLogEntry`, and a hand-off runs the readiness checks again
 * first, so a verdict from yesterday never publishes anything today.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
 */
class PublicationPipelineService implements PublicationPipelineServiceInterface {

	/**
	 * The Woo metadata an operator may set.
	 */
	/**
	 * The admin setting holding the organisation's default publisher (a TOOI URI).
	 *
	 * @var string
	 */
	public const CFG_DEFAULT_PUBLISHER = 'woo.default_publisher';

	/**
	 * The fields one bulk action may set on a selection.
	 *
	 * @var array<int, string>
	 */
	public const BULK_FIELDS = ['wooCategory', 'documentsoort', 'publisher'];

	/**
	 * Statuses past handoff: a bulk change skips them.
	 *
	 * @var array<int, string>
	 */
	public const PAST_HANDOFF = ['handed_off', 'published', 'depublication_requested', 'depublished'];

	public const METADATA = [
		'wooCategory',
		'documentsoort',
		'publisher',
		'officieleTitel',
		'creatiedatum',
		'publicatiedatum',
		'accessibilityOverrideReason',
	];

	/**
	 * Constructor
	 *
	 * @param PublicationStore           $store      Records, log and the platform object
	 * @param PublicationReadiness       $readiness  The three checks
	 * @param OpenCatalogiPublicationMap $map        The platform's field names
	 * @param OpenCatalogiPlatform       $platform   Whether it is there, its categories, attachments
	 * @param ITimeFactory               $clock      The moment
	 * @param IAppConfig|null            $config     App settings; the default publisher is read from them
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PublicationStore $store,
		private readonly PublicationReadiness $readiness,
		private readonly OpenCatalogiPublicationMap $map,
		private readonly OpenCatalogiPlatform $platform,
		private readonly ITimeFactory $clock,
		private readonly ?IAppConfig $config = null,
	) {

	}//end __construct()

	/**
	 * Start a publication for a document and check it straight away.
	 *
	 * @param string $documentFileRef The document's file id
	 * @param string $subjectType     document or dossier
	 * @param string $dossierRef      The dossier, if any
	 * @param string $actor           Who starts it
	 *
	 * @return array<string, mixed> The record.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.2
	 */
	public function create(string $documentFileRef, string $subjectType, string $dossierRef, string $actor): array {
		if ($documentFileRef === '') {
			throw new InvalidArgumentException('A publication needs a document', 400);
		}

		$subject = 'document';
		if ($subjectType === 'dossier') {
			$subject = 'dossier';
		}

		$record = $this->store->saveRecord(
			record: [
				'subjectType' => $subject,
				'documentFileRef' => $documentFileRef,
				'dossierRef' => $dossierRef,
				'status' => 'draft',
			] + $this->defaultPublisher()
		);
		$this->log(record: $record, action: 'created', actor: $actor, details: '');

		return $this->evaluate(record: $record, actor: $actor);

	}//end create()

	/**
	 * Run the readiness checks, store the verdicts, and move the status
	 * between draft and ready to match.
	 *
	 * @param array<string, mixed> $record The record
	 * @param string               $actor  Who asks
	 *
	 * @return array<string, mixed> The stored record.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.2
	 */
	public function evaluate(array $record, string $actor): array {
		$uuid = (string) $record['uuid'];
		$record = $this->readiness->evaluate(record: $record, now: $this->now());
		$status = (string) ($record['status'] ?? 'draft');
		if ($status === 'draft' || $status === 'ready') {
			$record['status'] = 'draft';
			if ($this->readiness->isReady(record: $record) === true) {
				$record['status'] = 'ready';
			}
		}

		$record = $this->store->saveRecord(record: $record, uuid: $uuid);
		$this->log(record: $record, action: 'readiness_evaluated', actor: $actor, details: implode('; ', (array) $record['readinessReasons']));

		return $record;

	}//end evaluate()

	/**
	 * Set the Woo metadata.
	 *
	 * @param array<string, mixed> $record   The record
	 * @param array<string, mixed> $metadata The fields, only those in METADATA are taken
	 * @param string               $actor    Who sets them
	 *
	 * @return array<string, mixed> The stored record.
	 *
	 * @throws InvalidArgumentException When the category is not one of OpenCatalogi's TOOI categories.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
	 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-2.3
	 */
	public function updateMetadata(array $record, array $metadata, string $actor): array {
		$this->assertKnownCategory(category: (string) ($metadata['wooCategory'] ?? ''));

		foreach (self::METADATA as $field) {
			if (array_key_exists($field, $metadata) === true) {
				$record[$field] = (string) $metadata[$field];
			}
		}

		$record = $this->store->saveRecord(record: $record, uuid: (string) $record['uuid']);
		$details = '';
		if (trim((string) ($metadata['accessibilityOverrideReason'] ?? '')) !== '') {
			// The reason to publish a copy that lost its accessibility goes in the log as well.
			$details = 'accessibility override: ' . trim((string) $metadata['accessibilityOverrideReason']);
		}

		$this->log(record: $record, action: 'metadata_assembled', actor: $actor, details: $details);

		return $record;

	}//end updateMetadata()

	/**
	 * Set one value on many records, each with its own log entry.
	 *
	 * Only `wooCategory` (from the TOOI list), `documentsoort` and `publisher`
	 * may be set; a value is never blank. A record past handoff is skipped and
	 * reported, not changed (woo-metadata-worklist D2).
	 *
	 * @param array<int, array<string, mixed>> $records The selected records.
	 * @param array<string, mixed>             $values  The field values to set.
	 * @param string                           $actor   Who sets them.
	 *
	 * @return array{updated: array<int, array<string, mixed>>, skipped: array<int, array{uuid: string, reason: string}>} The outcome.
	 *
	 * @throws InvalidArgumentException When a field may not be bulk-set, a value is blank, or the category is unknown.
	 *
	 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
	 */
	public function updateMetadataMany(array $records, array $values, string $actor): array {
		$metadata = $this->bulkValues(values: $values);
		$this->assertKnownCategory(category: (string) ($metadata['wooCategory'] ?? ''));

		$outcome = ['updated' => [], 'skipped' => []];
		foreach ($records as $record) {
			$uuid = (string) ($record['uuid'] ?? '');
			if (in_array((string) ($record['status'] ?? ''), self::PAST_HANDOFF, true) === true) {
				$outcome['skipped'][] = ['uuid' => $uuid, 'reason' => 'already handed off'];
				continue;
			}

			$outcome['updated'][] = $this->updateMetadata(record: $record, metadata: $metadata, actor: $actor);
		}

		return $outcome;

	}//end updateMetadataMany()

	/**
	 * The values a bulk action may set, checked before any record is touched.
	 *
	 * @param array<string, mixed> $values The requested values.
	 *
	 * @return array<string, string> The values to write.
	 *
	 * @throws InvalidArgumentException When a field is not bulk-settable or a value is blank.
	 */
	private function bulkValues(array $values): array {
		if ($values === []) {
			throw new InvalidArgumentException('Name at least one field to set', 400);
		}

		$metadata = [];
		foreach ($values as $field => $value) {
			if (in_array($field, self::BULK_FIELDS, true) === false) {
				throw new InvalidArgumentException('This field cannot be set on a selection: ' . $field, 400);
			}

			if (is_scalar($value) === false || trim((string) $value) === '') {
				throw new InvalidArgumentException('A value for ' . $field . ' is required', 400);
			}

			$metadata[$field] = trim((string) $value);
		}

		return $metadata;

	}//end bulkValues()

	/**
	 * Refuse a Woo category that is not in OpenCatalogi's TOOI list.
	 *
	 * @param string $category The category code; '' when none is set.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When the category is unknown.
	 */
	private function assertKnownCategory(string $category): void {
		$codes = array_column($this->platform->categories(), 'code');
		if ($category !== '' && $codes !== [] && in_array($category, $codes, true) === false) {
			throw new InvalidArgumentException('Unknown Woo information category: ' . $category, 400);
		}

	}//end assertKnownCategory()

	/**
	 * The default publisher for a new record, when an admin set one.
	 *
	 * @return array<string, string> `['publisher' => uri]`, or [] when none is set.
	 *
	 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-new-records-start-with-the-organisations-publisher-req-wmw-003
	 */
	private function defaultPublisher(): array {
		if ($this->config === null) {
			return [];
		}

		$publisher = trim($this->config->getValueString('filinq', self::CFG_DEFAULT_PUBLISHER, ''));
		if ($publisher === '') {
			return [];
		}

		return ['publisher' => $publisher];

	}//end defaultPublisher()

	/**
	 * Hand a ready publication to the platform with its redacted copy.
	 *
	 * @param array<string, mixed> $record The record
	 * @param string               $actor  Who hands it off; the copy is read from their files
	 *
	 * @return array<string, mixed> The stored record.
	 *
	 * @throws PublicationNotReadyException When a check fails now, or the metadata is incomplete.
	 * @throws RuntimeException             When the platform is not there or refuses.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function handoff(array $record, string $actor): array {
		if ($this->platform->available() === false) {
			throw new RuntimeException('The publication platform (OpenCatalogi) is not installed, so nothing can be handed off', 503);
		}

		$record = $this->evaluate(record: $record, actor: $actor);
		$missing = array_values(
			array_filter(['officieleTitel', 'wooCategory', 'publicatiedatum'], static fn (string $f): bool => (string) ($record[$f] ?? '') === '')
		);
		if ($record['status'] !== 'ready' || $missing !== []) {
			$reasons = (array) $record['readinessReasons'];
			if ($missing !== []) {
				$reasons[] = 'Missing Woo metadata: ' . implode(', ', $missing);
			}

			throw new PublicationNotReadyException(reasons: $reasons);
		}

		$copy = $this->platform->readCopy(fileId: (string) $record['redactedFileRef'], actor: $actor);
		$publication = $this->store->savePlatformPublication(
			publication: $this->map->toPublication(record: $record),
			uuid: (string) ($record['endpointPublicationRef'] ?? '')
		);
		$this->platform->attach(publicationUuid: (string) $publication['uuid'], fileName: $copy->getName(), content: $copy->getContent());

		$record['endpointPublicationRef'] = (string) $publication['uuid'];
		$record['handoffAt'] = $this->now()->format(DateTimeInterface::ATOM);
		$record['status'] = 'handed_off';
		$record = $this->store->saveRecord(record: $record, uuid: (string) $record['uuid']);
		$this->log(record: $record, action: 'handed_off', actor: $actor, details: 'Publication ' . $publication['uuid'] . ' at OpenCatalogi');

		return $this->sync(record: $record);

	}//end handoff()

	/**
	 * Withdraw a publication: the platform's publication gets a
	 * depublication date of now and is never deleted.
	 *
	 * @param array<string, mixed> $record The record
	 * @param string               $reason Why, required
	 * @param string               $actor  Who withdraws it
	 *
	 * @return array<string, mixed> The stored record.
	 *
	 * @throws InvalidArgumentException When there is no reason, or nothing was handed off.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.4
	 */
	public function withdraw(array $record, string $reason, string $actor): array {
		if (trim($reason) === '') {
			throw new InvalidArgumentException('A withdrawal needs a reason', 400);
		}

		if (in_array($record['status'] ?? '', ['handed_off', 'published'], true) === false) {
			throw new InvalidArgumentException('Only a publication that was handed off can be withdrawn', 409);
		}

		$now = $this->now()->format(DateTimeInterface::ATOM);
		$this->store->savePlatformPublication(publication: $this->map->withdrawal(now: $now), uuid: (string) $record['endpointPublicationRef']);

		$record['status'] = 'depublication_requested';
		$record['depublicationReason'] = trim($reason);
		$record['depublicationRequestedAt'] = $now;
		$record = $this->store->saveRecord(record: $record, uuid: (string) $record['uuid']);
		$this->log(record: $record, action: 'depublication_requested', actor: $actor, details: trim($reason));

		return $this->sync(record: $record);

	}//end withdraw()

	/**
	 * Record a destruction date and pass it to the platform.
	 *
	 * @param array<string, mixed> $record The record
	 * @param string               $date   Y-m-d
	 * @param string               $source Where the date came from
	 * @param string               $actor  Who records it
	 *
	 * @return array<string, mixed> The stored record.
	 *
	 * @throws InvalidArgumentException When the date or its source is missing.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.4
	 */
	public function setDestructionDate(array $record, string $date, string $source, string $actor): array {
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1 || trim($source) === '') {
			throw new InvalidArgumentException('A destruction date needs a date (YYYY-MM-DD) and its source', 400);
		}

		$record['destructionDate'] = $date;
		$record['destructionDateSource'] = trim($source);
		if ((string) ($record['endpointPublicationRef'] ?? '') !== '') {
			$this->store->savePlatformPublication(
				publication: $this->map->destruction(date: $date, source: trim($source)),
				uuid: (string) $record['endpointPublicationRef']
			);
		}

		$record = $this->store->saveRecord(record: $record, uuid: (string) $record['uuid']);
		$this->log(record: $record, action: 'destruction_date_propagated', actor: $actor, details: $date . ' (' . trim($source) . ')');

		return $record;

	}//end setDestructionDate()

	/**
	 * Move a record on when the platform side has: published once the
	 * publication date has come, withdrawn once the depublication date has.
	 *
	 * @param array<string, mixed> $record The record
	 *
	 * @return array<string, mixed> The record, stored again when it moved.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.4
	 */
	public function sync(array $record): array {
		$today = $this->now()->format('Y-m-d');
		$status = (string) ($record['status'] ?? '');
		$next = null;
		if ($status === 'handed_off' && (string) ($record['publicatiedatum'] ?? '9999') <= $today) {
			$next = 'published';
		}

		$requestedAt = (string) ($record['depublicationRequestedAt'] ?? '9999');
		if ($status === 'depublication_requested' && $requestedAt <= $this->now()->format(DateTimeInterface::ATOM)) {
			$next = 'depublished';
		}

		if ($next === null) {
			return $record;
		}

		$record['status'] = $next;
		$record = $this->store->saveRecord(record: $record, uuid: (string) $record['uuid']);
		$this->log(record: $record, action: $next, actor: 'system', details: '');

		return $record;

	}//end sync()

	/**
	 * Append one log entry with the state at that moment.
	 *
	 * @param array<string, mixed> $record  The record after the step
	 * @param string               $action  The step
	 * @param string               $actor   Who did it
	 * @param string               $details What was said or decided
	 *
	 * @return void
	 */
	private function log(array $record, string $action, string $actor, string $details): void {
		$snapshot = [];
		$fields = ['status', 'entitiesReviewed', 'consentClear', 'prohibitionsClear', 'wooCategory', 'publicatiedatum', 'endpointPublicationRef'];
		foreach ($fields as $field) {
			if (array_key_exists($field, $record) === true) {
				$snapshot[$field] = $record[$field];
			}
		}

		if ($actor === '') {
			$actor = 'system';
		}

		$this->store->appendLog(
			entry: [
				'publicationRecordRef' => (string) $record['uuid'],
				'action' => $action,
				'actor' => $actor,
				'timestamp' => $this->now()->format(DateTimeInterface::ATOM),
				'details' => mb_substr($details, 0, 4096),
				'snapshot' => $snapshot,
			]
		);

	}//end log()

	/**
	 * The moment.
	 *
	 * @return DateTimeImmutable Now, from the clock.
	 */
	private function now(): DateTimeImmutable {
		return (new DateTimeImmutable())->setTimestamp($this->clock->getTime());

	}//end now()
}//end class

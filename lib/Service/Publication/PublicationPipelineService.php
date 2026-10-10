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
use OCP\AppFramework\Utility\ITimeFactory;
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
class PublicationPipelineService {

	/**
	 * The Woo metadata an operator may set.
	 */
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
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PublicationStore $store,
		private readonly PublicationReadiness $readiness,
		private readonly OpenCatalogiPublicationMap $map,
		private readonly OpenCatalogiPlatform $platform,
		private readonly ITimeFactory $clock,
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
			]
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
		$category = (string) ($metadata['wooCategory'] ?? '');
		if ($category !== '' && $this->platform->categories() !== []) {
			$code = $this->platform->toPlatformCode(value: $category);
			if ($code === null) {
				throw new InvalidArgumentException('Unknown Woo information category: ' . $category, 400);
			}

			$metadata['wooCategory'] = $code;
		}

		$categoryBefore = (string) ($record['wooCategory'] ?? '');
		foreach (self::METADATA as $field) {
			if (array_key_exists($field, $metadata) === true) {
				$record[$field] = (string) $metadata[$field];
			}
		}

		$record = $this->store->saveRecord(record: $record, uuid: (string) $record['uuid']);
		$this->followCategoryEdit(record: $record, categoryBefore: $categoryBefore);
		$details = '';
		if (trim((string) ($metadata['accessibilityOverrideReason'] ?? '')) !== '') {
			// The reason to publish a copy that lost its accessibility goes in the log as well.
			$details = 'accessibility override: ' . trim((string) $metadata['accessibilityOverrideReason']);
		}

		$this->log(record: $record, action: 'metadata_assembled', actor: $actor, details: $details);

		return $record;

	}//end updateMetadata()

	/**
	 * After the hand-off, a changed category reaches the publication.
	 *
	 * The publication is filed in the sitemap of its category, so an edit
	 * that stayed on the record would leave it in the wrong list.
	 *
	 * @param array<string, mixed> $record         The stored record.
	 * @param string               $categoryBefore The category before the edit.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/woo-hand-off-files-its-category/tasks.md#task-1-3
	 */
	private function followCategoryEdit(array $record, string $categoryBefore): void {
		$publication = (string) ($record['endpointPublicationRef'] ?? '');
		$category = (string) ($record['wooCategory'] ?? '');
		if ($publication === '' || $category === '' || $category === $categoryBefore) {
			return;
		}

		$this->store->savePlatformPublication(publication: ['wooCategory' => $category], uuid: $publication);

	}//end followCategoryEdit()

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
		$code = null;
		if ($missing === []) {
			$code = $this->platform->toPlatformCode(value: (string) $record['wooCategory']);
		}

		if ($record['status'] !== 'ready' || $missing !== [] || $code === null) {
			$reasons = (array) $record['readinessReasons'];
			if ($missing !== []) {
				$reasons[] = 'Missing Woo metadata: ' . implode(', ', $missing);
			} else if ($code === null) {
				$reasons[] = 'Unknown Woo category: ' . (string) $record['wooCategory'];
			}

			throw new PublicationNotReadyException(reasons: $reasons);
		}

		$record['wooCategory'] = $code;

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

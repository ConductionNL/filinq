<?php

/**
 * Periodic Document Service
 *
 * Renders a template over the records a saved view returns, on a cadence or on
 * demand, into a stored PDF. The besluitenlijst makes itself, and last week's
 * list is left exactly as it was: each run writes a NEW document and edits none.
 *
 * A run against a view somebody deleted FAILS, naming the view. The alternative
 * is a besluitenlijst with no besluiten on it, which looks like a quiet week
 * rather than like a broken schedule, and nobody would go looking.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 * @spec openspec/changes/periodic-documents-on-a-schedule/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Runs periodic documents, and records what each run read, made or hit.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
class PeriodicDocumentService {

	/**
	 * The schema a schedule lives in.
	 *
	 * @var string
	 */
	public const SCHEMA = 'periodicDocument';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 * @param SavedViewReader               $views          Reads a saved view's records.
	 * @param PageLayoutService             $layouts        Resolves and stamps the layout.
	 * @param LoggerInterface               $logger         Logs what could not be recorded.
	 * @param DocumentService               $documents      The one generation path: render, store, audit.
	 * @param PeriodicCadence               $cadence        The due rule.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly SavedViewReader $views,
		private readonly PageLayoutService $layouts,
		private readonly LoggerInterface $logger,
		private readonly DocumentService $documents,
		private readonly PeriodicCadence $cadence = new PeriodicCadence(),
	) {
	}//end __construct()

	/**
	 * Run one periodic document now and return the document it produced.
	 *
	 * 🔴 IT GOES THROUGH THE SAME GENERATION PATH AS `POST api/documents/generate`.
	 * The template renders over the view's records (as `records` in the
	 * template, with `view` naming the view), the PDF is stored in the owner's
	 * Files and the generatedDocument entry points at it. A periodic document
	 * that wrote an entry and no file was an entry nobody could open.
	 *
	 * @param array<string, mixed> $schedule The schedule.
	 * @param string               $userId   Whose Files receive the PDF; empty means the schedule's owner.
	 *
	 * @return array<string, mixed> The generatedDocument entry, with the stored file id.
	 *
	 * @throws RuntimeException When the schedule names no view, the view is gone,
	 *                          nobody owns the schedule, or generation fails.
	 *
	 * @spec openspec/changes/periodic-documents-on-a-schedule/specs/document-creatie-sjablonen/spec.md
	 */
	public function run(array $schedule, string $userId = ''): array {
		$viewSlug = trim((string)($schedule['viewSlug'] ?? ''));
		if ($viewSlug === '') {
			throw new RuntimeException(message: 'This periodic document names no view.');
		}

		$records = $this->readView(slug: $viewSlug);
		if ($records === null) {
			throw new RuntimeException(
				message: 'The view "' . $viewSlug . '" no longer exists, so no document was produced.'
			);
		}

		$owner = $userId;
		if ($owner === '') {
			$owner = $this->ownerOf(schedule: $schedule);
		}

		if ($owner === '') {
			throw new RuntimeException(
				message: 'This periodic document has no owner, so there is no Files folder to put the PDF in.'
			);
		}

		$layout = null;
		$layoutName = trim((string)($schedule['layout'] ?? ''));
		if ($layoutName !== '') {
			$layout = $this->layouts->resolve(name: $layoutName);
		}

		$now = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
		$name = trim((string)($schedule['name'] ?? ''));

		try {
			$result = $this->documents->generateDocument(
				templateId: (string)($schedule['templateId'] ?? ''),
				dataRefs: [],
				options: [
					'format' => 'pdf',
					'adHocData' => ['records' => $records, 'view' => $viewSlug, 'periodic' => ['name' => $name, 'ranAt' => $now]],
					'output' => ['mode' => 'files'],
					'userId' => $owner,
					'filename' => $this->filename(name: $name, ranAt: $now),
				],
				recordFields: $this->layouts->stamp(
					document: ['viewSlug' => $viewSlug, 'recordCount' => count($records)],
					layout: $layout
				)
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The document could not be generated: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		$document = ((array)($result['metadata'] ?? [])) + [
			'viewSlug' => $viewSlug,
			'recordCount' => count($records),
		];
		$document['fileId'] = ($result['output']['fileId'] ?? null);

		$this->recordRun(schedule: $schedule, ranAt: $now, records: count($records), document: $document);

		return $document;

	}//end run()

	/**
	 * Run every active schedule whose cadence has come round.
	 *
	 * 🔴 ONE BROKEN SCHEDULE DOES NOT STOP THE OTHERS, AND IT IS WRITTEN DOWN.
	 * A failure lands on that schedule as `lastRunError`, and its
	 * `lastRunDocument` keeps pointing at the last good one.
	 *
	 * @param DateTimeImmutable $now The moment the job runs.
	 *
	 * @return array{ran: int, failed: int, skipped: int} What the sweep did.
	 *
	 * @spec openspec/changes/periodic-documents-on-a-schedule/specs/document-creatie-sjablonen/spec.md
	 */
	public function runDue(DateTimeImmutable $now): array {
		$summary = ['ran' => 0, 'failed' => 0, 'skipped' => 0];

		foreach ($this->activeSchedules() as $schedule) {
			$lastRunAt = $schedule['lastRunAt'] ?? null;
			if (is_string($lastRunAt) === false) {
				$lastRunAt = null;
			}

			if ($this->cadence->isDue((string)($schedule['cadence'] ?? ''), $lastRunAt, $now) === false) {
				$summary['skipped']++;
				continue;
			}

			try {
				$this->run(schedule: $schedule);
				$summary['ran']++;
			} catch (Throwable $e) {
				$summary['failed']++;
				$this->recordFailure(schedule: $schedule, message: $e->getMessage(), at: $now);
			}
		}

		return $summary;

	}//end runDue()

	/**
	 * The records a saved view returns, or null when the view is gone.
	 *
	 * @param string $slug The view slug.
	 *
	 * @return array<int, mixed>|null The records.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function readView(string $slug): ?array {
		return $this->views->records(view: $slug);

	}//end readView()

	/**
	 * The active schedules, as plain fields with their uuid and owner.
	 *
	 * @return array<int, array<string, mixed>> The schedules.
	 */
	private function activeSchedules(): array {
		try {
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['active' => true]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[PeriodicDocumentService] could not list the periodic documents',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);
			return [];
		}

		$schedules = [];
		foreach ((array)$results as $result) {
			$data = $result;
			if (is_object($result) === true && method_exists($result, 'jsonSerialize') === true) {
				$data = $result->jsonSerialize();
			}

			if (is_array($data) === false || ($data['active'] ?? true) === false) {
				continue;
			}

			$data['uuid'] = (string)($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? '')));
			$schedules[] = $data;
		}

		return $schedules;

	}//end activeSchedules()

	/**
	 * Who owns a schedule, as OpenRegister recorded it.
	 *
	 * @param array<string, mixed> $schedule The schedule.
	 *
	 * @return string The owner's user id, or an empty string.
	 */
	private function ownerOf(array $schedule): string {
		$owner = ($schedule['@self']['owner'] ?? ($schedule['owner'] ?? ''));
		if (is_string($owner) === false) {
			return '';
		}

		return trim($owner);

	}//end ownerOf()

	/**
	 * The file name of one run: the schedule's name and the day.
	 *
	 * @param string $name  The schedule's name.
	 * @param string $ranAt The run moment (ISO 8601).
	 *
	 * @return string The file name, without extension.
	 */
	private function filename(string $name, string $ranAt): string {
		$base = preg_replace('/[^\p{L}\p{N} _-]+/u', '', $name);
		if ($base === null || trim($base) === '') {
			$base = 'periodic-document';
		}

		return trim($base) . ' ' . substr($ranAt, 0, 10);

	}//end filename()

	/**
	 * Record a good run on the schedule and clear an earlier error.
	 *
	 * @param array<string, mixed> $schedule The schedule.
	 * @param string               $ranAt    When it ran.
	 * @param int                  $records  How many records the view returned.
	 * @param array<string, mixed> $document The document it produced.
	 *
	 * @return void
	 */
	private function recordRun(array $schedule, string $ranAt, int $records, array $document): void {
		$this->saveSchedule(
			schedule: $schedule,
			changes: [
				'lastRunAt' => $ranAt,
				'lastRunRecords' => $records,
				'lastRunDocument' => (string)($document['uuid'] ?? ($document['@self']['id'] ?? ($document['id'] ?? ''))),
				'lastRunError' => '',
				'lastRunErrorAt' => '',
			]
		);

	}//end recordRun()

	/**
	 * Record a failed run on the schedule, leaving its last document alone.
	 *
	 * @param array<string, mixed> $schedule The schedule.
	 * @param string               $message  Why the run failed.
	 * @param DateTimeImmutable    $at       When it failed.
	 *
	 * @return void
	 */
	private function recordFailure(array $schedule, string $message, DateTimeImmutable $at): void {
		$this->saveSchedule(
			schedule: $schedule,
			changes: [
				'lastRunError' => mb_substr($message, 0, 1000),
				'lastRunErrorAt' => $at->format(DateTimeInterface::ATOM),
			]
		);

	}//end recordFailure()

	/**
	 * Write changed fields onto a stored schedule.
	 *
	 * @param array<string, mixed> $schedule The schedule.
	 * @param array<string, mixed> $changes  The fields to set.
	 *
	 * @return void
	 */
	private function saveSchedule(array $schedule, array $changes): void {
		$uuid = (string)($schedule['uuid'] ?? '');
		if ($uuid === '') {
			return;
		}

		$fields = array_merge($schedule, $changes);
		unset($fields['uuid'], $fields['@self'], $fields['id']);

		try {
			$this->objectResolver->resolve()->saveObject(
				object: $fields,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				uuid: $uuid
			);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[PeriodicDocumentService] could not record the run on the schedule',
				context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => $uuid, 'error' => $e->getMessage()]
			);
		}

	}//end saveSchedule()
}//end class

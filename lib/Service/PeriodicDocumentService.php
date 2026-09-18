<?php

/**
 * Periodic Document Service
 *
 * Renders a template over the records a saved view returns, on a cadence or on
 * demand. The besluitenlijst makes itself, and last week's list is left exactly
 * as it was: each run writes a NEW document and edits none.
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
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
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
 * Runs one periodic document, and records what the run read.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
class PeriodicDocumentService {

	/**
	 * The schema holding the schedules.
	 *
	 * @var string
	 */
	public const SCHEMA = 'periodicDocument';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param SavedViewReader $views The records a saved view returns.
	 * @param PageLayoutService $layouts The paper a run goes out on.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly SavedViewReader $views,
		private readonly PageLayoutService $layouts,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Run one periodic document now.
	 *
	 * @param array<string, mixed> $schedule The schedule to run.
	 *
	 * @return array<string, mixed> The generated-document entry this run produced.
	 *
	 * @throws RuntimeException When the view is gone, or the run cannot be recorded.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function run(array $schedule): array {
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

		$layout = null;
		$layoutName = trim((string)($schedule['layout'] ?? ''));
		if ($layoutName !== '') {
			$layout = $this->layouts->resolve(name: $layoutName);
		}

		$now = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
		$document = [
			'templateId' => (string)($schedule['templateId'] ?? ''),
			'templateName' => (string)($schedule['name'] ?? ''),
			'format' => 'pdf',
			'status' => 'generated',
			'generatedAt' => $now,
			'generatedBy' => 'periodic',
			'viewSlug' => $viewSlug,
			'recordCount' => count($records),
			'dataRefs' => [],
			'warnings' => [],
		];

		$document = $this->layouts->stamp(document: $document, layout: $layout);

		$stored = $this->write(document: $document);
		$this->recordRun(schedule: $schedule, at: $now, records: count($records), document: $stored);

		return $stored;

	}//end run()

	/**
	 * The records one saved view returns, or null when the view is gone.
	 *
	 * 🔴 An empty view and a MISSING view are different answers. Both look like
	 * "no records" to a caller that only counts rows, and only one of them is a
	 * broken schedule, so the missing view comes back as null and the empty one
	 * as an empty list.
	 *
	 * @param string $slug The view slug.
	 *
	 * @return array<int, mixed>|null The records, or null when the view does not exist.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function readView(string $slug): ?array {
		return $this->views->records(view: $slug);

	}//end readView()

	/**
	 * Write the generated document this run produced.
	 *
	 * @param array<string, mixed> $document The entry.
	 *
	 * @return array<string, mixed> The stored entry.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	private function write(array $document): array {
		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $document,
				register: IntakeRepository::REGISTER,
				schema: DocumentReviewService::SCHEMA
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not store the generated document: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		if (is_array($stored) === false) {
			return $document;
		}

		return $stored;

	}//end write()

	/**
	 * Record on the schedule what this run did.
	 *
	 * The schedule keeps only the LAST run. Every run's own document is the
	 * record of that run, which is why nothing here edits an earlier one.
	 *
	 * @param array<string, mixed> $schedule The schedule.
	 * @param string $at When the run happened.
	 * @param int $records How many records the view returned.
	 * @param array<string, mixed> $document The document produced.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	private function recordRun(array $schedule, string $at, int $records, array $document): void {
		$uuid = (string)($schedule['uuid'] ?? '');
		if ($uuid === '') {
			return;
		}

		$schedule['lastRunAt'] = $at;
		$schedule['lastRunRecords'] = $records;
		$schedule['lastRunDocument'] = (string)($document['uuid'] ?? '');
		unset($schedule['uuid']);

		try {
			$this->objectResolver->resolve()->saveObject(
				object: $schedule,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				uuid: $uuid
			);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[PeriodicDocumentService] the run produced a document but could not be recorded on the schedule',
				context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => $uuid, 'error' => $e->getMessage()]
			);
		}

	}//end recordRun()
}//end class

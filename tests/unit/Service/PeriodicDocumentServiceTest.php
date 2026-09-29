<?php

/**
 * Tests for PeriodicDocumentService (periodic-documents-on-a-schedule).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTimeImmutable;
use Exception;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\PageLayoutService;
use OCA\Filinq\Service\PeriodicCadence;
use OCA\Filinq\Service\PeriodicDocumentService;
use OCA\Filinq\Service\SavedViewReader;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The besluitenlijst makes itself as a stored PDF, last week's list is
 * untouched, and a broken schedule says so on the schedule.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PeriodicDocumentServiceTest extends TestCase {

	/**
	 * Schedule writes: [uuid, fields].
	 *
	 * @var array<int, array{0: string|null, 1: array<string, mixed>}>
	 */
	private array $scheduleWrites = [];

	/**
	 * The generateDocument calls: [templateId, options, recordFields].
	 *
	 * @var array<int, array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}>
	 */
	private array $generations = [];

	/**
	 * The stored schedules searchObjectsBySlug answers with.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $stored = [];

	/**
	 * Build the service.
	 *
	 * @param array<int, mixed>|null    $records  What the view returns, or null when it is gone.
	 * @param array<string, mixed>|null $layout   The layout the schedule names.
	 * @param Exception|null            $failWith What generation throws, if anything.
	 *
	 * @return PeriodicDocumentService
	 */
	private function service(?array $records, ?array $layout = null, ?Exception $failWith = null): PeriodicDocumentService {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				$this->scheduleWrites[] = [($arguments[3] ?? null), ($arguments[0] ?? [])];
				return ($arguments[0] ?? []);
			}
		);
		$objectService->method('searchObjectsBySlug')->willReturnCallback(fn (): array => $this->stored);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$views = $this->createMock(SavedViewReader::class);
		$views->method('records')->willReturn($records);

		$layouts = $this->createMock(PageLayoutService::class);
		$layouts->method('resolve')->willReturn($layout);
		$layouts->method('stamp')->willReturnCallback(
			static function (array $document, ?array $resolved): array {
				if ($resolved !== null) {
					$document['layoutId'] = (string)$resolved['uuid'];
					$document['layoutVersion'] = (int)$resolved['layoutVersion'];
				}
				return $document;
			}
		);

		$documents = $this->createMock(DocumentService::class);
		$documents->method('generateDocument')->willReturnCallback(
			function (string $templateId, array $dataRefs, array $options = [], array $recordFields = []) use ($failWith): array {
				$this->generations[] = [$templateId, $options, $recordFields];
				if ($failWith !== null) {
					throw $failWith;
				}
				return [
					'metadata' => ['uuid' => 'document-new', 'status' => 'generated', 'templateId' => $templateId] + $recordFields,
					'output' => ['mode' => 'files', 'fileId' => 991, 'path' => '/x'],
				];
			}
		);

		return new PeriodicDocumentService(
			$resolver,
			$views,
			$layouts,
			$this->createMock(LoggerInterface::class),
			$documents,
			new PeriodicCadence()
		);

	}//end service()

	/**
	 * The besluitenlijst schedule as OpenRegister stores it.
	 *
	 * @param array<string, mixed> $overrides Fields to change.
	 *
	 * @return array<string, mixed>
	 */
	private function schedule(array $overrides = []): array {
		return $overrides + [
			'uuid' => 'schedule-1',
			'name' => 'Besluitenlijst',
			'viewSlug' => 'besluiten-deze-maand',
			'templateId' => 'template-1',
			'layout' => 'Gemeente, besluit',
			'cadence' => 'weekly',
			'active' => true,
			'lastRunDocument' => 'document-last-week',
			'@self' => ['owner' => 'griffier'],
		];

	}//end schedule()

	public function testARunRendersTheViewIntoAStoredPdfInTheOwnersFiles(): void {
		$service = $this->service(
			records: [['id' => 'a'], ['id' => 'b'], ['id' => 'c']],
			layout: ['uuid' => 'layout-2', 'layoutVersion' => 2]
		);

		$document = $service->run(schedule: $this->schedule());

		[$templateId, $options, $recordFields] = $this->generations[0];
		$this->assertSame('template-1', $templateId);
		$this->assertSame('pdf', $options['format']);
		$this->assertSame(['mode' => 'files'], $options['output']);
		$this->assertSame('griffier', $options['userId']);
		$this->assertSame([['id' => 'a'], ['id' => 'b'], ['id' => 'c']], $options['adHocData']['records']);
		$this->assertSame('besluiten-deze-maand', $options['adHocData']['view']);
		$this->assertStringStartsWith('Besluitenlijst ', $options['filename']);
		$this->assertSame(['viewSlug' => 'besluiten-deze-maand', 'recordCount' => 3, 'layoutId' => 'layout-2', 'layoutVersion' => 2], $recordFields);

		$this->assertSame(991, $document['fileId']);
		$this->assertSame(3, $document['recordCount']);
	}

	public function testTheScheduleNowPointsAtTheNewDocumentAndLastWeeksIsUntouched(): void {
		$service = $this->service(records: [['id' => 'a']]);

		$service->run(schedule: $this->schedule(['lastRunError' => 'old trouble']));

		$this->assertCount(1, $this->scheduleWrites, 'Only the schedule is written here; the document entry is the generation path\'s.');
		[$uuid, $fields] = $this->scheduleWrites[0];
		$this->assertSame('schedule-1', $uuid);
		$this->assertSame('document-new', $fields['lastRunDocument']);
		$this->assertSame(1, $fields['lastRunRecords']);
		$this->assertSame('', $fields['lastRunError']);
		$this->assertArrayNotHasKey('@self', $fields);
	}

	public function testOnDemandTheCallerReceivesThePdf(): void {
		$service = $this->service(records: []);

		$service->run(schedule: $this->schedule(), userId: 'handler');

		$this->assertSame('handler', $this->generations[0][1]['userId']);
	}

	public function testADeletedViewFailsNamingItAndGeneratesNothing(): void {
		$service = $this->service(records: null);

		$failure = null;
		try {
			$service->run(schedule: $this->schedule());
		} catch (RuntimeException $caught) {
			$failure = $caught;
		}

		$this->assertNotNull($failure);
		$this->assertStringContainsString('besluiten-deze-maand', $failure->getMessage());
		$this->assertSame([], $this->generations);
		$this->assertSame([], $this->scheduleWrites);
	}

	public function testAnEmptyViewIsAQuietWeekAndStillADocument(): void {
		$service = $this->service(records: []);

		$document = $service->run(schedule: $this->schedule());

		$this->assertSame(0, $document['recordCount']);
		$this->assertCount(1, $this->generations);
	}

	public function testAScheduleWithoutAViewIsRefused(): void {
		$service = $this->service(records: []);
		$schedule = $this->schedule();
		unset($schedule['viewSlug']);

		$this->expectException(RuntimeException::class);
		$service->run(schedule: $schedule);
	}

	public function testTheSweepRunsWhatIsDueAndSkipsTheRest(): void {
		$this->stored = [
			$this->schedule(['uuid' => 'due', 'lastRunAt' => '2026-09-21T09:00:00+00:00']),
			$this->schedule(['uuid' => 'fresh', 'lastRunAt' => '2026-09-27T09:00:00+00:00']),
			$this->schedule(['uuid' => 'manual', 'cadence' => 'onDemand']),
		];
		$service = $this->service(records: [['id' => 'a']]);

		$summary = $service->runDue(now: new DateTimeImmutable('2026-09-28T10:00:00+00:00'));

		$this->assertSame(['ran' => 1, 'failed' => 0, 'skipped' => 2], $summary);
		$this->assertCount(1, $this->generations);
		$this->assertSame('due', $this->scheduleWrites[0][0]);
	}

	public function testABrokenViewIsWrittenOnTheScheduleAndLastWeeksDocumentStays(): void {
		$this->stored = [$this->schedule(['lastRunAt' => '2026-09-21T09:00:00+00:00'])];
		$service = $this->service(records: null);

		$summary = $service->runDue(now: new DateTimeImmutable('2026-09-28T10:00:00+00:00'));

		$this->assertSame(1, $summary['failed']);
		[$uuid, $fields] = $this->scheduleWrites[0];
		$this->assertSame('schedule-1', $uuid);
		$this->assertStringContainsString('no longer exists', $fields['lastRunError']);
		$this->assertSame('2026-09-28T10:00:00+00:00', $fields['lastRunErrorAt']);
		$this->assertSame('document-last-week', $fields['lastRunDocument']);
		$this->assertSame('2026-09-21T09:00:00+00:00', $fields['lastRunAt']);
	}

	public function testAFailedGenerationIsRecordedAndTheNextScheduleStillRuns(): void {
		$this->stored = [
			$this->schedule(['uuid' => 'one']),
			$this->schedule(['uuid' => 'two']),
		];
		$service = $this->service(records: [], failWith: new Exception('Template not found', 404));

		$summary = $service->runDue(now: new DateTimeImmutable('2026-09-28T10:00:00+00:00'));

		$this->assertSame(['ran' => 0, 'failed' => 2, 'skipped' => 0], $summary);
		$this->assertStringContainsString('Template not found', $this->scheduleWrites[1][1]['lastRunError']);
	}

	public function testAScheduleWithoutAnOwnerIsAFailureNotAFileInNobodysFolder(): void {
		$this->stored = [$this->schedule(['@self' => []])];
		$service = $this->service(records: []);

		$service->runDue(now: new DateTimeImmutable('2026-09-28T10:00:00+00:00'));

		$this->assertSame([], $this->generations);
		$this->assertStringContainsString('no owner', $this->scheduleWrites[0][1]['lastRunError']);
	}
}

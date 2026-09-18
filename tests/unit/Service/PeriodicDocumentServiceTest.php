<?php

/**
 * Unit tests for PeriodicDocumentService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\PageLayoutService;
use OCA\Filinq\Service\PeriodicDocumentService;
use OCA\Filinq\Service\SavedViewReader;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts that the besluitenlijst makes itself, that last week's list is
 * untouched, and that a deleted view fails LOUDLY instead of producing an empty
 * document that reads like a quiet week.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PeriodicDocumentServiceTest extends TestCase {

	/**
	 * Objects the fake OpenRegister was asked to store.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * The schemas those writes were addressed to.
	 *
	 * @var array<int, string>
	 */
	private array $writtenTo = [];

	/**
	 * The uuids those writes were addressed to.
	 *
	 * @var array<int, string|null>
	 */
	private array $writtenUuids = [];

	/**
	 * Build the service over a view that returns the given records.
	 *
	 * @param array<int, mixed>|null $records What the view returns, or null when it is gone.
	 * @param array<string, mixed>|null $layout The layout the schedule names, if any.
	 *
	 * @return PeriodicDocumentService The service under test.
	 */
	private function service(?array $records, ?array $layout = null): PeriodicDocumentService {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				$object = ($arguments[0] ?? []);
				$this->written[] = $object;
				$this->writtenTo[] = (string)($arguments[2] ?? '');
				$this->writtenUuids[] = ($arguments[3] ?? null);

				return ($object + ['uuid' => ($arguments[3] ?? 'document-new')]);
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$views = $this->createMock(SavedViewReader::class);
		$views->method('records')->willReturn($records);

		$layouts = $this->createMock(PageLayoutService::class);
		$layouts->method('resolve')->willReturn($layout);
		$layouts->method('stamp')->willReturnCallback(
			static function (array $document, ?array $resolved): array {
				if ($resolved === null) {
					return $document;
				}

				$document['layoutId'] = (string)($resolved['uuid'] ?? '');
				$document['layoutVersion'] = (int)($resolved['layoutVersion'] ?? 0);

				return $document;
			}
		);

		return new PeriodicDocumentService(
			$resolver,
			$views,
			$layouts,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * The besluitenlijst schedule.
	 *
	 * @return array<string, mixed> The schedule.
	 */
	private function schedule(): array {
		return [
			'uuid' => 'schedule-1',
			'name' => 'Besluitenlijst',
			'viewSlug' => 'besluiten-deze-maand',
			'templateId' => 'template-1',
			'layout' => 'Gemeente, besluit',
			'cadence' => 'weekly',
			'active' => true,
		];

	}//end schedule()

	/**
	 * The run writes a document naming the view and the count.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testTheBesluitenlijstMakesItself(): void {
		$service = $this->service(
			records: [['id' => 'a'], ['id' => 'b'], ['id' => 'c']],
			layout: ['uuid' => 'layout-2', 'layoutVersion' => 2]
		);

		$document = $service->run(schedule: $this->schedule());

		$this->assertSame('besluiten-deze-maand', $document['viewSlug']);
		$this->assertSame(3, $document['recordCount']);
		$this->assertSame(2, $document['layoutVersion']);
		$this->assertSame('generated', $document['status']);

	}//end testTheBesluitenlijstMakesItself()

	/**
	 * A run writes a NEW document and updates only the schedule.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testLastWeeksListIsUntouched(): void {
		$service = $this->service(records: [['id' => 'a']]);

		$service->run(schedule: $this->schedule());

		// Two writes: the new document (created, no uuid) and the schedule
		// (updated). Nothing addresses an earlier document.
		$this->assertCount(2, $this->written);
		$this->assertSame('generatedDocument', $this->writtenTo[0]);
		$this->assertNull($this->writtenUuids[0], 'Each run CREATES a document.');
		$this->assertSame('periodicDocument', $this->writtenTo[1]);
		$this->assertSame('schedule-1', $this->writtenUuids[1]);

	}//end testLastWeeksListIsUntouched()

	/**
	 * A deleted view fails, naming it, and produces no document.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testADeletedViewFailsLoudlyAndWritesNothing(): void {
		$service = $this->service(records: null);

		// The failure is CAPTURED rather than asserted inside the catch:
		// PHPUnit's own fail() throws a RuntimeException subclass, so a catch
		// on RuntimeException would swallow it and the test could not fail.
		$failure = null;
		try {
			$service->run(schedule: $this->schedule());
		} catch (RuntimeException $caught) {
			$failure = $caught;
		}

		$this->assertNotNull($failure, 'A run against a deleted view must fail.');
		$this->assertStringContainsString('besluiten-deze-maand', $failure->getMessage());

		$this->assertSame([], $this->written, 'A failed run must not leave an empty document behind.');

	}//end testADeletedViewFailsLoudlyAndWritesNothing()

	/**
	 * An EMPTY view is a quiet month, and still produces its document.
	 *
	 * This is the other half of the deleted-view case: both look like "no
	 * records" to anything that only counts rows, and only one of them is a
	 * broken schedule.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testAnEmptyViewIsNotADeletedView(): void {
		$service = $this->service(records: []);

		$document = $service->run(schedule: $this->schedule());

		$this->assertSame(0, $document['recordCount']);

	}//end testAnEmptyViewIsNotADeletedView()

	/**
	 * A schedule that names no view is refused.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testAScheduleWithoutAViewIsRefused(): void {
		$service = $this->service(records: []);
		$schedule = $this->schedule();
		unset($schedule['viewSlug']);

		$this->expectException(RuntimeException::class);
		$service->run(schedule: $schedule);

	}//end testAScheduleWithoutAViewIsRefused()
}//end class

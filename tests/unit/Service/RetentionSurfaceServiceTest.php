<?php

/**
 * Unit tests for RetentionSurfaceService (archiefwet-retention-engine REQ-DDARE-007).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-destruction-dates-propagate-to-the-publication-pipeline-with-source-precedence-req-ddare-007
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\LegalHold\LegalHoldRecordFreeze;
use OCA\Filinq\Service\RetentionSurfaceService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The precedence matrix: the zaaksysteem's date, else OpenRegister's
 * archiefactiedatum of a `vernietigen` record, else nothing; and every
 * unreadable source reads as "no date", never as an error.
 *
 * @covers \OCA\Filinq\Service\RetentionSurfaceService
 * @uses   \OCA\Filinq\Service\StoredRecordFields
 */
class RetentionSurfaceServiceTest extends TestCase {

	/**
	 * The record uuid OpenRegister names the document's folder after.
	 *
	 * @var string
	 */
	private const RECORD = '0b7e6a52-3c1d-4f8e-9a2b-5d6c7e8f9a01';

	/**
	 * The bridge records per schema; a string throws that message.
	 *
	 * @var array<string, array<int, array<string, mixed>>|string>
	 */
	private array $bridge = ['externalDocument' => [], 'bridgeSource' => []];

	/**
	 * The retention block of the record holding file 42; null when there is no record.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $retention = null;

	/**
	 * The folder file 42 sits in.
	 *
	 * @var string
	 */
	private string $folderName = self::RECORD;

	/**
	 * Whether the file tree fails.
	 *
	 * @var boolean
	 */
	private bool $treeFails = false;

	/**
	 * The warnings the service logged.
	 *
	 * @var array<int, string>
	 */
	private array $warnings = [];

	/**
	 * The service over the doubles of this test.
	 *
	 * @return RetentionSurfaceService
	 */
	private function service(): RetentionSurfaceService {
		$objects = $this->createMock(originalClassName: ObjectService::class);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug, array $filters, bool $_rbac): array {
				$rows = $this->bridge[$schemaSlug];
				if (is_string($rows) === true) {
					throw new RuntimeException(message: $rows);
				}

				return $rows;
			}
		);
		$resolver = $this->createMock(originalClassName: DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$folder = $this->createMock(originalClassName: Folder::class);
		$folder->method('getName')->willReturnCallback(fn (): string => $this->folderName);
		$file = $this->createMock(originalClassName: File::class);
		$file->method('getParent')->willReturn($folder);
		$root = $this->createMock(originalClassName: IRootFolder::class);
		$root->method('getFirstNodeById')->willReturnCallback(
			function (int $id) use ($file): ?File {
				if ($this->treeFails === true) {
					throw new RuntimeException(message: 'storage offline');
				}

				return ($id === 42) ? $file : null;
			}
		);

		$records = $this->createMock(originalClassName: LegalHoldRecordFreeze::class);
		$records->method('load')->willReturnCallback(
			function (string $ref): ?object {
				if ($ref !== self::RECORD || $this->retention === null) {
					return null;
				}

				return new class($this->retention) {
					/**
					 * @param array<string, mixed> $retention The block.
					 */
					public function __construct(private array $retention) {
					}//end __construct()

					/**
					 * @return array<string, mixed>
					 */
					public function getRetention(): array {
						return $this->retention;
					}//end getRetention()
				};
			}
		);

		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		$logger->method('warning')->willReturnCallback(
			function (string $message): void {
				$this->warnings[] = $message;
			}
		);

		return new RetentionSurfaceService(rootFolder: $root, records: $records, objectResolver: $resolver, logger: $logger);
	}//end service()

	/**
	 * The engine's archiefactiedatum of a `vernietigen` record fills the date, named after its category.
	 *
	 * @return void
	 */
	public function testTheEngineDateFillsTheRecord(): void {
		$this->retention = ['archiefnominatie' => 'vernietigen', 'archiefactiedatum' => '2033-10-01T00:00:00+00:00', 'classificatie' => '11.1.2'];

		$this->assertSame(
			expected: ['date' => '2033-10-01', 'source' => 'Archiefwet 1995, selectielijst categorie 11.1.2'],
			actual: $this->service()->destructionDateFor(record: ['documentFileRef' => '42'])
		);
	}//end testTheEngineDateFillsTheRecord()

	/**
	 * The zaaksysteem's date wins over a different engine date, named after the source system.
	 *
	 * @return void
	 */
	public function testTheZaaksysteemDateTakesPrecedence(): void {
		$this->retention = ['archiefnominatie' => 'vernietigen', 'archiefactiedatum' => '2033-10-01'];
		$this->bridge = [
			'externalDocument' => [
				['stagedFileRef' => 7, 'sourceId' => 's-1', 'vernietigingsdatum' => '2030-01-01'],
				['object' => ['stagedFileRef' => 42, 'sourceId' => 's-1', 'vernietigingsdatum' => '2031-05-17'], '@self' => ['uuid' => 'd-1']],
			],
			'bridgeSource' => [
				['uuid' => 's-0', 'name' => 'Other'],
				['uuid' => 's-1', 'name' => 'Zaaksysteem Gemeente', 'vendor' => 'zaaksysteem.nl'],
			],
		];

		$this->assertSame(
			expected: ['date' => '2031-05-17', 'source' => 'Zaaksysteem Gemeente'],
			actual: $this->service()->destructionDateFor(record: ['documentFileRef' => 42])
		);
	}//end testTheZaaksysteemDateTakesPrecedence()

	/**
	 * A source without a name is named by its vendor, and without either as "zaaksysteem".
	 *
	 * @return void
	 */
	public function testAnUnnamedSourceFallsBackToItsVendorThenToZaaksysteem(): void {
		$this->bridge = [
			'externalDocument' => [['stagedFileRef' => 42, 'sourceId' => 's-1', 'vernietigingsdatum' => '2031-05-17']],
			'bridgeSource' => [['uuid' => 's-1', 'name' => ' ', 'vendor' => 'zaaksysteem.nl']],
		];
		$this->assertSame(expected: 'zaaksysteem.nl', actual: $this->service()->destructionDateFor(record: ['documentFileRef' => 42])['source'] ?? null);

		$this->bridge['bridgeSource'] = [['uuid' => 's-1', 'name' => '', 'vendor' => '']];
		$this->assertSame(expected: 'zaaksysteem', actual: $this->service()->destructionDateFor(record: ['documentFileRef' => 42])['source'] ?? null);

		$this->bridge['bridgeSource'] = [];
		$this->assertSame(expected: 'zaaksysteem', actual: $this->service()->destructionDateFor(record: ['documentFileRef' => 42])['source'] ?? null);
	}//end testAnUnnamedSourceFallsBackToItsVendorThenToZaaksysteem()

	/**
	 * A bridge record without a date leaves the engine date in charge.
	 *
	 * @return void
	 */
	public function testABridgeRecordWithoutADateLeavesTheEngineDate(): void {
		$this->retention = ['archiefnominatie' => 'vernietigen', 'archiefactiedatum' => '2033-10-01'];
		$this->bridge['externalDocument'] = [['stagedFileRef' => 42, 'sourceId' => 's-1', 'vernietigingsdatum' => 'soon']];

		$this->assertSame(
			expected: ['date' => '2033-10-01', 'source' => 'Archiefwet 1995, selectielijst'],
			actual: $this->service()->destructionDateFor(record: ['documentFileRef' => 42])
		);
	}//end testABridgeRecordWithoutADateLeavesTheEngineDate()

	/**
	 * Without either date there is none, and nothing is blocked.
	 *
	 * @return void
	 */
	public function testWithNeitherDateThereIsNone(): void {
		$service = $this->service();
		$this->assertNull(actual: $service->destructionDateFor(record: ['documentFileRef' => 42]));
		$this->assertNull(actual: $service->destructionDateFor(record: ['documentFileRef' => '']));
		$this->assertNull(actual: $service->destructionDateFor(record: []));
		$this->assertNull(actual: $service->destructionDateFor(record: ['documentFileRef' => 404]));
	}//end testWithNeitherDateThereIsNone()

	/**
	 * A record kept permanently, or with no derived date yet, gives no destruction date.
	 *
	 * @return void
	 */
	public function testOnlyAVernietigenRecordWithADateGivesOne(): void {
		$this->retention = ['archiefnominatie' => 'bewaren', 'archiefactiedatum' => '2033-10-01'];
		$this->assertNull(actual: $this->service()->destructionDateFor(record: ['documentFileRef' => 42]));

		$this->retention = ['archiefnominatie' => 'vernietigen'];
		$this->assertNull(actual: $this->service()->destructionDateFor(record: ['documentFileRef' => 42]));

		$this->retention = ['archiefnominatie' => 'vernietigen', 'archiefactiedatum' => '01-10-2033'];
		$this->assertNull(actual: $this->service()->destructionDateFor(record: ['documentFileRef' => 42]));
	}//end testOnlyAVernietigenRecordWithADateGivesOne()

	/**
	 * A file outside an object folder belongs to no record and has no engine date.
	 *
	 * @return void
	 */
	public function testAFileOutsideAnObjectFolderHasNoEngineDate(): void {
		$this->retention = ['archiefnominatie' => 'vernietigen', 'archiefactiedatum' => '2033-10-01'];
		$this->folderName = 'Documents';

		$this->assertNull(actual: $this->service()->destructionDateFor(record: ['documentFileRef' => 42]));
	}//end testAFileOutsideAnObjectFolderHasNoEngineDate()

	/**
	 * An unreadable bridge or file tree reads as "no date from there" and is logged, never thrown.
	 *
	 * @return void
	 */
	public function testAnUnreadableSourceIsNoDateNotAnError(): void {
		$this->retention = ['archiefnominatie' => 'vernietigen', 'archiefactiedatum' => '2033-10-01', 'classificatie' => '1.1'];
		$this->bridge['externalDocument'] = 'bridge register down';
		$this->assertSame(
			expected: '2033-10-01',
			actual: $this->service()->destructionDateFor(record: ['documentFileRef' => 42])['date'] ?? null
		);

		$this->treeFails = true;
		$this->assertNull(actual: $this->service()->destructionDateFor(record: ['documentFileRef' => 42]));
		$this->assertCount(expectedCount: 3, haystack: $this->warnings);
	}//end testAnUnreadableSourceIsNoDateNotAnError()
	/**
	 * The bridge's document schema holds the date a zaaksysteem sends, as a date.
	 *
	 * @return void
	 */
	public function testTheBridgeDocumentHoldsTheSourceDate(): void {
		$register = json_decode(json: (string) file_get_contents(filename: __DIR__ . '/../../../lib/Settings/filinq_register.json'), associative: true);
		$field = ($register['components']['schemas']['externalDocument']['properties']['vernietigingsdatum'] ?? []);

		$this->assertSame(expected: 'string', actual: $field['type'] ?? null);
		$this->assertSame(expected: 'date', actual: $field['format'] ?? null);
		$this->assertFalse(condition: $field['required'] ?? true);
	}//end testTheBridgeDocumentHoldsTheSourceDate()
}//end class

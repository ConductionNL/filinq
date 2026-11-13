<?php

/**
 * conformanceReport rows: the search is scoped to the file, a row for
 * another file is ignored, a second save of the same file and subject
 * updates its row, and register failures surface as RuntimeException.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\VeraPdf\ConformanceReportRepository;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\VeraPdf\ConformanceReportRepository
 */
class ConformanceReportRepositoryTest extends TestCase {

	/**
	 * The OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The repository under test.
	 *
	 * @var ConformanceReportRepository
	 */
	private ConformanceReportRepository $repository;

	/**
	 * A repository over a mocked object service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->objects = $this->createMock(ObjectService::class);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$this->repository = new ConformanceReportRepository($resolver);

	}//end setUp()

	/**
	 * A stored row as OpenRegister serialises it.
	 *
	 * @param string               $uuid   The uuid.
	 * @param array<string, mixed> $fields The object fields.
	 *
	 * @return object The row.
	 */
	private function row(string $uuid, array $fields): object {
		return new class($uuid, $fields) implements \JsonSerializable {
			/**
			 * Constructor.
			 *
			 * @param string               $uuid   The uuid.
			 * @param array<string, mixed> $fields The fields.
			 */
			public function __construct(private readonly string $uuid, private readonly array $fields) {
			}

			/**
			 * The serialised row.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['object' => $this->fields, '@self' => ['id' => $this->uuid]];
			}
		};

	}//end row()

	/**
	 * The search is scoped to the register, the schema and the file id, and
	 * the rows come back keyed by subject with their uuid.
	 *
	 * @return void
	 */
	public function testFindForFileSearchesTheFileAndKeysRowsBySubject(): void {
		$this->objects->expects($this->once())->method('searchObjectsBySlug')
			->with(IntakeRepository::REGISTER, ConformanceReportRepository::SCHEMA, ['fileId' => 42])
			->willReturn(
				[
					$this->row('u-file', ['fileId' => 42, 'subject' => 'file', 'compliant' => true]),
					$this->row('u-conv', ['fileId' => '42', 'subject' => 'conversionOutput', 'compliant' => false]),
				]
			);

		$reports = $this->repository->findForFile(fileId: 42);

		$this->assertSame(['file', 'conversionOutput'], array_keys($reports));
		$this->assertSame(['fileId' => 42, 'subject' => 'file', 'compliant' => true, 'uuid' => 'u-file'], $reports['file']);
		$this->assertSame('u-conv', $reports['conversionOutput']['uuid']);
		$this->assertFalse($reports['conversionOutput']['compliant']);

	}//end testFindForFileSearchesTheFileAndKeysRowsBySubject()

	/**
	 * A row of another file the search let through is dropped, and the first
	 * row for a subject wins over a later duplicate.
	 *
	 * @return void
	 */
	public function testFindForFileDropsOtherFilesAndKeepsTheFirstRowPerSubject(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn(
			[
				$this->row('u-other', ['fileId' => 7, 'subject' => 'file']),
				$this->row('u-first', ['fileId' => 42, 'subject' => 'file']),
				$this->row('u-second', ['fileId' => 42, 'subject' => 'file']),
				$this->row('u-none', ['subject' => 'conversionOutput']),
			]
		);

		$reports = $this->repository->findForFile(fileId: 42);

		$this->assertSame(['file'], array_keys($reports));
		$this->assertSame('u-first', $reports['file']['uuid']);

	}//end testFindForFileDropsOtherFilesAndKeepsTheFirstRowPerSubject()

	/**
	 * A search with no hits is an empty list.
	 *
	 * @return void
	 */
	public function testFindForFileWithNoRowsIsEmpty(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn([]);

		$this->assertSame([], $this->repository->findForFile(fileId: 1));

	}//end testFindForFileWithNoRowsIsEmpty()

	/**
	 * A register that cannot be searched is a RuntimeException carrying the cause.
	 *
	 * @return void
	 */
	public function testFindForFileWrapsARegisterFailure(): void {
		$cause = new \LogicException('register down');
		$this->objects->method('searchObjectsBySlug')->willThrowException($cause);

		try {
			$this->repository->findForFile(fileId: 3);
			$this->fail('Expected a RuntimeException.');
		} catch (RuntimeException $e) {
			$this->assertSame('The conformance report could not be read: register down', $e->getMessage());
			$this->assertSame($cause, $e->getPrevious());
		}

	}//end testFindForFileWrapsARegisterFailure()

	/**
	 * A first report for a file and subject is created: no uuid is passed,
	 * even one the caller put on the report.
	 *
	 * @return void
	 */
	public function testSaveCreatesARowWhenNoneExists(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn([]);
		$saved = [];
		$this->objects->expects($this->once())->method('saveObject')->willReturnCallback(
			function (...$args) use (&$saved): object {
				$saved = $args;

				return $this->row('u-new', ['fileId' => 5, 'subject' => 'file', 'compliant' => true]);
			}
		);

		$stored = $this->repository->save(report: ['uuid' => 'caller-uuid', 'fileId' => 5, 'subject' => 'file', 'compliant' => true]);

		// The mock receives the call positionally: object, register, schema, uuid.
		$this->assertSame(['fileId' => 5, 'subject' => 'file', 'compliant' => true], $saved[0]);
		$this->assertSame(IntakeRepository::REGISTER, $saved[1]);
		$this->assertSame(ConformanceReportRepository::SCHEMA, $saved[2]);
		$this->assertNull($saved[3]);
		$this->assertSame(['fileId' => 5, 'subject' => 'file', 'compliant' => true, 'uuid' => 'u-new'], $stored);

	}//end testSaveCreatesARowWhenNoneExists()

	/**
	 * A second report for the same file and subject updates the stored row.
	 *
	 * @return void
	 */
	public function testSaveUpdatesTheRowOfTheSameFileAndSubject(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn(
			[
				$this->row('u-conv', ['fileId' => 5, 'subject' => 'conversionOutput']),
				$this->row('u-file', ['fileId' => 5, 'subject' => 'file']),
			]
		);
		$saved = [];
		$this->objects->expects($this->once())->method('saveObject')->willReturnCallback(
			function (...$args) use (&$saved): array {
				$saved = $args;

				return ['uuid' => 'u-file', 'fileId' => 5, 'subject' => 'file', 'compliant' => false];
			}
		);

		$stored = $this->repository->save(report: ['fileId' => 5, 'subject' => 'file', 'compliant' => false]);

		$this->assertSame(['fileId' => 5, 'subject' => 'file', 'compliant' => false], $saved[0]);
		$this->assertSame('u-file', $saved[3]);
		$this->assertSame('u-file', $stored['uuid']);
		$this->assertFalse($stored['compliant']);

	}//end testSaveUpdatesTheRowOfTheSameFileAndSubject()

	/**
	 * A stored row without a uuid cannot be updated, so a new row is created.
	 *
	 * @return void
	 */
	public function testSaveCreatesWhenTheExistingRowHasNoUuid(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn(
			[['fileId' => 5, 'subject' => 'file']]
		);
		$saved = [];
		$this->objects->method('saveObject')->willReturnCallback(
			function (...$args) use (&$saved): array {
				$saved = $args;

				return ['id' => 'u-new', 'fileId' => 5, 'subject' => 'file'];
			}
		);

		$stored = $this->repository->save(report: ['fileId' => 5, 'subject' => 'file']);

		$this->assertNull($saved[3]);
		$this->assertSame('u-new', $stored['uuid']);

	}//end testSaveCreatesWhenTheExistingRowHasNoUuid()

	/**
	 * A register that refuses the row is a RuntimeException carrying the cause.
	 *
	 * @return void
	 */
	public function testSaveWrapsARegisterRefusal(): void {
		$cause = new \InvalidArgumentException('schema says no');
		$this->objects->method('searchObjectsBySlug')->willReturn([]);
		$this->objects->method('saveObject')->willThrowException($cause);

		try {
			$this->repository->save(report: ['fileId' => 9, 'subject' => 'file']);
			$this->fail('Expected a RuntimeException.');
		} catch (RuntimeException $e) {
			$this->assertSame('Could not store the conformance report: schema says no', $e->getMessage());
			$this->assertSame($cause, $e->getPrevious());
		}

	}//end testSaveWrapsARegisterRefusal()
}//end class

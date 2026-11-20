<?php

/**
 * Unit tests for BatchReportService
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
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-4.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\BatchReportService;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\ReportOrganisationLabel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Unit tests for BatchReportService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchReportServiceTest extends TestCase {

	/**
	 * @var BatchReportService
	 */
	private BatchReportService $service;

	/**
	 * @var BatchStateService|MockObject
	 */
	private BatchStateService|MockObject $mockStateService;

	protected function setUp(): void {
		parent::setUp();

		$this->mockStateService = $this->createMock(BatchStateService::class);
		$this->service = new BatchReportService(stateService: $this->mockStateService, organisationLabel: new ReportOrganisationLabel(container: $this->createMock(ContainerInterface::class)));

	}//end setUp()

	/**
	 * Test generateReport throws when batch not found.
	 *
	 * @return void
	 */
	public function testGenerateReportThrowsWhenBatchNotFound(): void {
		$this->mockStateService->method('getBatch')->willReturn(null);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(404);

		$this->service->generateReport('missing-batch-id');

	}//end testGenerateReportThrowsWhenBatchNotFound()

	/**
	 * Test generateReport throws when batch is not completed.
	 *
	 * @return void
	 */
	public function testGenerateReportThrowsForIncompleteBatch(): void {
		$this->mockStateService->method('getBatch')->willReturn(
			['batchId' => 'abc', 'status' => 'extracting', 'files' => []]
		);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(409);

		$this->service->generateReport('abc');

	}//end testGenerateReportThrowsForIncompleteBatch()

	/**
	 * Test generateReport returns CSV string for completed batch.
	 *
	 * @return void
	 */
	public function testGenerateReportReturnsCsvForCompletedBatch(): void {
		$batch = [
			'batchId' => 'abc',
			'status' => 'completed',
			'createdAt' => time(),
			'files' => [
				[
					'fileName' => 'test.pdf',
					'fileId' => 42,
					'anonymizedFileId' => 43,
					'entityCount' => 3,
					'replacementCount' => 3,
					'status' => 'completed',
				],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);

		$csv = $this->service->generateReport('abc');

		$this->assertStringContainsString('fileName', $csv);
		$this->assertStringContainsString('test.pdf', $csv);

	}//end testGenerateReportReturnsCsvForCompletedBatch()

	/**
	 * Test generateReport CSV has correct header columns.
	 *
	 * @return void
	 */
	public function testGenerateReportCsvHasHeaders(): void {
		$batch = [
			'batchId' => 'abc',
			'status' => 'completed',
			'createdAt' => time(),
			'files' => [],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);

		$csv = $this->service->generateReport('abc');
		$lines = explode("\n", trim($csv));

		$this->assertStringContainsString('entityCount', $lines[0]);
		$this->assertStringContainsString('status', $lines[0]);

	}//end testGenerateReportCsvHasHeaders()
	/**
	 * Every row names the organisation the batch is stamped with, by name and UUID.
	 *
	 * @return void
	 */
	public function testEveryRowNamesTheBatchOrganisation(): void {
		$organisation = new class {
			/**
			 * The UUID.
			 *
			 * @return string
			 */
			public function getUuid(): string {
				return 'org-a-uuid';
			}

			/**
			 * The name.
			 *
			 * @return string
			 */
			public function getName(): string {
				return 'Gemeente A';
			}
		};
		$organisations = new class ($organisation) {
			/**
			 * Constructor.
			 *
			 * @param object $organisation The caller's organisation.
			 */
			public function __construct(private object $organisation) {
			}

			/**
			 * The caller's organisations.
			 *
			 * @return array<int, object>
			 */
			public function getUserOrganisations(): array {
				return [$this->organisation];
			}
		};
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($organisations);
		$service = new BatchReportService(
			stateService: $this->mockStateService,
			organisationLabel: new ReportOrganisationLabel(container: $container)
		);
		$this->mockStateService->method('getBatch')->willReturn(
			[
				'status' => 'completed',
				'createdAt' => 1750000000,
				'organisation' => 'org-a-uuid',
				'files' => [['fileName' => 'a.pdf', 'fileId' => 1, 'status' => 'completed']],
			]
		);

		$rows = array_map('str_getcsv', explode("\n", trim($service->generateReport('abc'))));

		$this->assertSame(['organisationName', 'organisationUuid'], array_slice($rows[0], -2));
		$this->assertSame(['Gemeente A', 'org-a-uuid'], array_slice($rows[1], -2));
	}//end testEveryRowNamesTheBatchOrganisation()

}//end class

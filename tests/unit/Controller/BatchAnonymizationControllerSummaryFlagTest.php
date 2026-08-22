<?php

/**
 * Unit tests for BatchAnonymizationController appendBasisSummary flag
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-7
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\BatchAnonymizationController;
use OCA\Filinq\Service\BatchAnonymizeService;
use OCA\Filinq\Service\BatchExtractionService;
use OCA\Filinq\Service\BatchReportService;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\BatchUploadService;
use OCA\Filinq\Service\EntityConsolidationService;
use OCA\Filinq\Service\FolderBatchService;
use OCA\Filinq\Service\WooProfileService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the appendBasisSummary flag on the batchAnonymize endpoint.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress                                 PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BatchAnonymizationControllerSummaryFlagTest extends TestCase {

	/**
	 * @var IRequest|MockObject
	 */
	private IRequest|MockObject $mockRequest;

	/**
	 * @var BatchAnonymizeService|MockObject
	 */
	private BatchAnonymizeService|MockObject $mockAnonService;

	/**
	 * @var IL10N|MockObject
	 */
	private IL10N|MockObject $mockL10n;

	/**
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $mockUserSession;

	/**
	 * @var BatchAnonymizationController
	 */
	private BatchAnonymizationController $controller;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockRequest = $this->createMock(IRequest::class);
		$this->mockAnonService = $this->createMock(BatchAnonymizeService::class);
		$this->mockL10n = $this->createMock(IL10N::class);
		$this->mockL10n->method('t')->willReturnCallback(static fn ($s) => $s);

		$mockUser = $this->createMock(IUser::class);
		$this->mockUserSession = $this->createMock(IUserSession::class);
		$this->mockUserSession->method('getUser')->willReturn($mockUser);

		$this->controller = new BatchAnonymizationController(
			appName: 'filinq',
			request: $this->mockRequest,
			logger: $this->createMock(LoggerInterface::class),
			stateService: $this->createMock(BatchStateService::class),
			uploadService: $this->createMock(BatchUploadService::class),
			extractService: $this->createMock(BatchExtractionService::class),
			anonService: $this->mockAnonService,
			reportService: $this->createMock(BatchReportService::class),
			entityService: $this->createMock(EntityConsolidationService::class),
			profileService: $this->createMock(WooProfileService::class),
			folderBatchService: $this->createMock(FolderBatchService::class),
			l10n: $this->mockL10n,
			appConfig: $this->createMock(\OCP\IAppConfig::class),
			userSession: $this->mockUserSession
		);

	}//end setUp()

	/**
	 * Payload validation rejects a non-boolean appendBasisSummary on the batch endpoint.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-7
	 */
	public function testBatchAnonymizeRejectsNonBooleanSummaryFlag(): void {
		$this->mockRequest->method('getParams')->willReturn(
			[
				'entities' => [['text' => 'Test', 'type' => 'PERSON']],
				'appendBasisSummary' => 'yes',
			]
		);

		$this->mockAnonService->expects($this->never())->method('anonymizeBatch');

		$response = $this->controller->batchAnonymize(batchId: 'batch-1');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(400, $response->getStatus());
		$data = $response->getData();
		$this->assertArrayHasKey('error', $data);
		$this->assertStringContainsString('appendBasisSummary', $data['error']);

	}//end testBatchAnonymizeRejectsNonBooleanSummaryFlag()

	/**
	 * Flag true is forwarded to the batch anonymize service.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-7
	 */
	public function testBatchAnonymizeForwardsFlagTrueToService(): void {
		$entities = [['text' => 'Test', 'type' => 'PERSON']];

		$this->mockRequest->method('getParams')->willReturn(
			[
				'entities' => $entities,
				'appendBasisSummary' => true,
			]
		);

		$this->mockAnonService->expects($this->never())->method('anonymizeBatch');
		$this->mockAnonService->expects($this->once())
			->method('anonymizeBatchWithBasisSummary')
			->with(
				batchId: 'batch-2',
				entities: $entities
			)
			->willReturn(
				[
					'batchId' => 'batch-2',
					'batchStatus' => 'completed',
					'processedFiles' => 1,
					'skippedFiles' => [],
					'totalFiles' => 1,
				]
			);

		$response = $this->controller->batchAnonymize(batchId: 'batch-2');

		$this->assertSame(200, $response->getStatus());

	}//end testBatchAnonymizeForwardsFlagTrueToService()

	/**
	 * Flag omitted defaults to false — service is called with appendBasisSummary=false.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-7
	 */
	public function testBatchAnonymizeFlagDefaultsToFalse(): void {
		$entities = [['text' => 'Test', 'type' => 'PERSON']];

		$this->mockRequest->method('getParams')->willReturn(['entities' => $entities]);

		$this->mockAnonService->expects($this->never())->method('anonymizeBatchWithBasisSummary');
		$this->mockAnonService->expects($this->once())
			->method('anonymizeBatch')
			->with(
				batchId: 'batch-3',
				entities: $entities
			)
			->willReturn(
				[
					'batchId' => 'batch-3',
					'batchStatus' => 'completed',
					'processedFiles' => 1,
					'skippedFiles' => [],
					'totalFiles' => 1,
				]
			);

		$response = $this->controller->batchAnonymize(batchId: 'batch-3');

		$this->assertSame(200, $response->getStatus());

	}//end testBatchAnonymizeFlagDefaultsToFalse()
}//end class

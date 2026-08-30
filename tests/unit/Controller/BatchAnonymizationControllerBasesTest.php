<?php

/**
 * Unit tests for BatchAnonymizationController bases-passthrough validation
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
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
 * Tests that batchAnonymize validates per-entity bases[] per the spec
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BatchAnonymizationControllerBasesTest extends TestCase {

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
		$this->mockL10n->method('t')->willReturnCallback(fn ($s) => $s);

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
	 * Stray non-array `bases` field on a payload entry is silently ignored — succeeds.
	 *
	 * Per spec.md (post-explore-mode rework 2026-05-12): Filinq MUST ignore any
	 * `bases` field that erroneously appears on incoming payload entries (do NOT 400).
	 * Bases are set per-relation via OR's own PATCH endpoint, not validated here.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
	 */
	public function testBatchAnonymizeIgnoresStrayNonArrayBases(): void {
		$this->mockRequest->method('getParams')->willReturn(
			[
				'entities' => [
					['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => 'not-an-array'],
				],
			]
		);

		$this->mockAnonService->method('anonymizeBatch')
			->willReturn(
				[
					'batchId' => 'batch-1',
					'batchStatus' => 'completed',
					'processedFiles' => 1,
					'skippedFiles' => [],
					'totalFiles' => 1,
				]
			);

		$response = $this->controller->batchAnonymize(batchId: 'batch-1');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(200, $response->getStatus());
		$data = $response->getData();
		$this->assertArrayHasKey('ignoredFields', $data);
		$this->assertContains('bases', $data['ignoredFields']);

	}//end testBatchAnonymizeIgnoresStrayNonArrayBases()

	/**
	 * Stray non-string `bases` entries on a payload entry are silently ignored — succeeds.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
	 */
	public function testBatchAnonymizeIgnoresStrayNonStringBasesEntries(): void {
		$this->mockRequest->method('getParams')->willReturn(
			[
				'entities' => [
					['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => [true, 'uuid-b']],
				],
			]
		);

		$this->mockAnonService->method('anonymizeBatch')
			->willReturn(
				[
					'batchId' => 'batch-1',
					'batchStatus' => 'completed',
					'processedFiles' => 1,
					'skippedFiles' => [],
					'totalFiles' => 1,
				]
			);

		$response = $this->controller->batchAnonymize(batchId: 'batch-1');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(200, $response->getStatus());
		$data = $response->getData();
		$this->assertArrayHasKey('ignoredFields', $data);
		$this->assertContains('bases', $data['ignoredFields']);

	}//end testBatchAnonymizeIgnoresStrayNonStringBasesEntries()

	/**
	 * Test batchAnonymize succeeds when bases is a valid array of strings
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
	 */
	public function testBatchAnonymizeSucceedsWhenBasesIsValidStringArray(): void {
		$this->mockRequest->method('getParams')->willReturn(
			[
				'entities' => [
					['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => ['uuid-a']],
				],
			]
		);

		$this->mockAnonService->method('anonymizeBatch')
			->willReturn(
				[
					'batchId' => 'batch-1',
					'batchStatus' => 'completed',
					'processedFiles' => 1,
					'skippedFiles' => [],
					'totalFiles' => 1,
				]
			);

		$response = $this->controller->batchAnonymize(batchId: 'batch-1');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(200, $response->getStatus());

	}//end testBatchAnonymizeSucceedsWhenBasesIsValidStringArray()

	/**
	 * Test batchAnonymize succeeds when entities have no bases field (backward-compat)
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
	 */
	public function testBatchAnonymizeSucceedsWhenBasesAbsent(): void {
		$this->mockRequest->method('getParams')->willReturn(
			[
				'entities' => [
					['text' => 'Amsterdam', 'type' => 'LOCATION'],
				],
			]
		);

		$this->mockAnonService->method('anonymizeBatch')
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

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(200, $response->getStatus());

	}//end testBatchAnonymizeSucceedsWhenBasesAbsent()

	/**
	 * Test batchAnonymize succeeds when bases is an empty array
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
	 */
	public function testBatchAnonymizeSucceedsWhenBasesIsEmptyArray(): void {
		$this->mockRequest->method('getParams')->willReturn(
			[
				'entities' => [
					['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => []],
				],
			]
		);

		$this->mockAnonService->method('anonymizeBatch')
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

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(200, $response->getStatus());

	}//end testBatchAnonymizeSucceedsWhenBasesIsEmptyArray()

}//end class

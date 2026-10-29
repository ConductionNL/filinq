<?php

/**
 * The anonymisation controllers answer through the request service the
 * container injects, not through one they build themselves.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Container wiring, no functional requirement.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\AnonymizationController;
use OCA\Filinq\Controller\BatchAnonymizationController;
use OCA\Filinq\Kernel\Contracts\AnonymizationServiceInterface;
use OCA\Filinq\Kernel\Contracts\AnonymizeRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchAnonymizeServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchExtractionServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchReportServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchUploadServiceInterface;
use OCA\Filinq\Kernel\Contracts\EntityConsolidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\FileListingServiceInterface;
use OCA\Filinq\Kernel\Contracts\FolderBatchServiceInterface;
use OCA\Filinq\Kernel\Contracts\WooProfileServiceInterface;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class RequestServiceInjectionTest extends TestCase {
	/**
	 * The anonymize endpoint returns the injected service's refusal as is.
	 *
	 * @return void
	 */
	public function testAnonymizeAnswersWithTheInjectedServiceRefusal(): void {
		$requests = $this->createMock(originalClassName: AnonymizeRequestServiceInterface::class);
		$requests->expects($this->once())->method('requireAuthenticated')->willReturn(
			value: ['status' => 401, 'body' => ['error' => 'Not authenticated']]
		);

		$controller = new AnonymizationController(
			appName: 'filinq',
			request: $this->createMock(originalClassName: IRequest::class),
			logger: new NullLogger(),
			anonymizationService: $this->createMock(originalClassName: AnonymizationServiceInterface::class),
			fileListingService: $this->createMock(originalClassName: FileListingServiceInterface::class),
			l10n: $this->createMock(originalClassName: IL10N::class),
			appConfig: $this->createMock(originalClassName: IAppConfig::class),
			anonymizeRequest: $requests
		);

		$response = $controller->anonymize(fileId: 7);

		$this->assertSame(expected: 401, actual: $response->getStatus());
		$this->assertSame(expected: ['error' => 'Not authenticated'], actual: $response->getData());

	}//end testAnonymizeAnswersWithTheInjectedServiceRefusal()

	/**
	 * The batch status endpoint reports the injected service's summary.
	 *
	 * @return void
	 */
	public function testBatchStatusReportsTheInjectedServiceSummary(): void {
		$batch = ['batchId' => 'b1', 'status' => 'extracting', 'files' => []];
		$state = $this->createMock(originalClassName: BatchStateServiceInterface::class);
		$state->method('getBatch')->willReturn(value: $batch);
		$requests = $this->createMock(originalClassName: BatchRequestServiceInterface::class);
		$requests->expects($this->once())->method('summariseBatch')->with($batch)->willReturn(
			value: ['totalEntities' => 3, 'progress' => 50, 'totalFiles' => 2]
		);
		$session = $this->createMock(originalClassName: IUserSession::class);
		$session->method('getUser')->willReturn(value: $this->createMock(originalClassName: IUser::class));

		$controller = new BatchAnonymizationController(
			appName: 'filinq',
			request: $this->createMock(originalClassName: IRequest::class),
			logger: new NullLogger(),
			stateService: $state,
			uploadService: $this->createMock(originalClassName: BatchUploadServiceInterface::class),
			extractService: $this->createMock(originalClassName: BatchExtractionServiceInterface::class),
			anonService: $this->createMock(originalClassName: BatchAnonymizeServiceInterface::class),
			reportService: $this->createMock(originalClassName: BatchReportServiceInterface::class),
			entityService: $this->createMock(originalClassName: EntityConsolidationServiceInterface::class),
			profileService: $this->createMock(originalClassName: WooProfileServiceInterface::class),
			folderBatchService: $this->createMock(originalClassName: FolderBatchServiceInterface::class),
			l10n: $this->createMock(originalClassName: IL10N::class),
			appConfig: $this->createMock(originalClassName: IAppConfig::class),
			userSession: $session,
			batchRequest: $requests
		);

		$data = $controller->batchStatus(batchId: 'b1')->getData();

		$this->assertSame(expected: 3, actual: $data['totalEntities']);
		$this->assertSame(expected: 50, actual: $data['progress']);
		$this->assertSame(expected: 2, actual: $data['totalFiles']);

	}//end testBatchStatusReportsTheInjectedServiceSummary()
}//end class

<?php

/**
 * The job that closes lapsed share records: it runs the service, logs what
 * it closed, survives an unreadable register, and is registered.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-2.5
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\ExpiredShareCloserJob;
use OCA\Filinq\Service\Share\DocumentShareService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * @covers \OCA\Filinq\BackgroundJob\ExpiredShareCloserJob
 */
class ExpiredShareCloserJobTest extends TestCase {

	private function runJob(DocumentShareService $shares, LoggerInterface $logger): ExpiredShareCloserJob {
		$job = new ExpiredShareCloserJob($this->createMock(ITimeFactory::class), $shares, $logger);
		(new ReflectionMethod($job, 'run'))->invoke($job, null);

		return $job;
	}

	public function testTheJobClosesTheLapsedRecordsAndSaysWhat(): void {
		$shares = $this->createMock(DocumentShareService::class);
		$shares->expects($this->once())->method('closeLapsed')->willReturn(['expired' => 2, 'revoked' => 1, 'failed' => 0]);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('info')
			->with('[ExpiredShareCloserJob] Closed lapsed share records', ['expired' => 2, 'revoked' => 1, 'failed' => 0]);

		$this->runJob($shares, $logger);
	}

	public function testAQuietRunLogsNothingAndAnUnreadableRegisterOnlyWarns(): void {
		$quiet = $this->createMock(DocumentShareService::class);
		$quiet->method('closeLapsed')->willReturn(['expired' => 0, 'revoked' => 0, 'failed' => 0]);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('info');
		$this->runJob($quiet, $logger);

		$broken = $this->createMock(DocumentShareService::class);
		$broken->method('closeLapsed')->willThrowException(new RuntimeException('The share records could not be read: down', 500));
		$warned = $this->createMock(LoggerInterface::class);
		$warned->expects($this->once())->method('warning')
			->with('[ExpiredShareCloserJob] The share records could not be read', ['exception' => 'The share records could not be read: down']);
		$this->runJob($broken, $warned);
	}

	public function testTheJobIsRegisteredInInfoXml(): void {
		$info = (string) file_get_contents(__DIR__ . '/../../../appinfo/info.xml');

		$this->assertStringContainsString('<job>OCA\Filinq\BackgroundJob\ExpiredShareCloserJob</job>', $info);
	}
}

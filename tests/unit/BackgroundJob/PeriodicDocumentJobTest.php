<?php

/**
 * The hourly job runs the due periodic documents (periodic-documents-on-a-schedule D1).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use DateTimeImmutable;
use OCA\Filinq\BackgroundJob\PeriodicDocumentJob;
use OCA\Filinq\Service\PeriodicDocumentService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;

/**
 * The job hands the clock's moment to the sweep, and is registered.
 */
class PeriodicDocumentJobTest extends TestCase {

	public function testTheJobSweepsAtTheClocksMoment(): void {
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getTime')->willReturn(1790589600);
		$periodic = $this->createMock(PeriodicDocumentService::class);
		$periodic->expects($this->once())
			->method('runDue')
			->with($this->callback(static fn (DateTimeImmutable $now): bool => $now->getTimestamp() === 1790589600))
			->willReturn(['ran' => 1, 'failed' => 0, 'skipped' => 0]);

		$job = new PeriodicDocumentJob($time, $periodic, new NullLogger());
		(new ReflectionMethod($job, 'run'))->invoke($job, null);
	}

	public function testTheJobIsRegisteredInInfoXml(): void {
		$info = (string)file_get_contents(__DIR__ . '/../../../appinfo/info.xml');

		$this->assertStringContainsString('<job>OCA\Filinq\BackgroundJob\PeriodicDocumentJob</job>', $info);
	}
}

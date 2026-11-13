<?php

/**
 * Tests for DocumentDraftExpiryJob.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use DateTimeImmutable;
use Exception;
use OCA\Filinq\BackgroundJob\DocumentDraftExpiryJob;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * With a fixed clock: a draft untouched for 31 days goes, one of 29 days stays.
 *
 * @covers \OCA\Filinq\BackgroundJob\DocumentDraftExpiryJob
 */
class DocumentDraftExpiryJobTest extends TestCase {

	/**
	 * The job with a clock at 2026-11-13 and the given object service.
	 *
	 * @param ObjectService|null $objects The object service, or null when OpenRegister is absent.
	 *
	 * @return DocumentDraftExpiryJob
	 */
	private function job(?ObjectService $objects): DocumentDraftExpiryJob {
		$time = $this->createMock(ITimeFactory::class);
		$time->method('now')->willReturn(new DateTimeImmutable('2026-11-13T12:00:00+00:00'));
		$settings = $this->createMock(SettingsServiceInterface::class);
		$settings->method('getObjectService')->willReturn($objects);

		return new DocumentDraftExpiryJob(clock: $time, settings: $settings, logger: $this->createMock(LoggerInterface::class));
	}//end job()

	/**
	 * A draft last saved a number of days before the clock.
	 *
	 * @param string $id       The draft id.
	 * @param int    $daysAgo  Days since the last save.
	 *
	 * @return array<string, mixed> The draft.
	 */
	private function draft(string $id, int $daysAgo): array {
		$expiresAt = (new DateTimeImmutable('2026-11-13T12:00:00+00:00'))->modify('-' . $daysAgo . ' days')->modify('+30 days');

		return ['@self' => ['id' => $id], 'stage' => 'editing', 'expiresAt' => $expiresAt->format(DATE_ATOM)];
	}//end draft()

	public function testOnlyTheDraftPastItsExpiryIsDeleted(): void {
		$objects = $this->createMock(ObjectService::class);
		$objects->expects($this->once())->method('findAll')
			->with(['filters' => ['register' => 'filinq', 'schema' => 'documentDraft']], false)
			->willReturn([$this->draft('old', 31), $this->draft('recent', 29), ['@self' => ['id' => 'no-expiry']]]);
		$objects->expects($this->once())->method('deleteObject')
			->with('old', 'filinq', 'documentDraft', false)
			->willReturn(true);

		$this->assertSame(1, $this->job($objects)->sweep());
	}//end testOnlyTheDraftPastItsExpiryIsDeleted()

	public function testAFailedDeleteIsNotCountedAndTheSweepGoesOn(): void {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('findAll')->willReturn([$this->draft('a', 40), $this->draft('b', 40)]);
		$objects->expects($this->exactly(2))->method('deleteObject')
			->willReturnCallback(static function (string $uuid): bool {
				if ($uuid === 'a') {
					throw new Exception('locked');
				}

				return true;
			});

		$this->assertSame(1, $this->job($objects)->sweep());
	}//end testAFailedDeleteIsNotCountedAndTheSweepGoesOn()

	public function testWithoutOpenRegisterNothingIsDeleted(): void {
		$this->assertSame(0, $this->job(null)->sweep());
	}//end testWithoutOpenRegisterNothingIsDeleted()

	public function testAFailedListingDeletesNothing(): void {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('findAll')->willThrowException(new Exception('down'));
		$objects->expects($this->never())->method('deleteObject');

		$this->assertSame(0, $this->job($objects)->sweep());
	}//end testAFailedListingDeletesNothing()

	public function testADraftWithoutAReadableExpiryIsKept(): void {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('findAll')->willReturn([
			['@self' => ['id' => 'a'], 'expiresAt' => 'not a date'],
			['@self' => ['id' => 'b'], 'expiresAt' => ''],
			['expiresAt' => '2020-01-01T00:00:00+00:00'],
		]);
		$objects->expects($this->never())->method('deleteObject');

		$this->assertSame(0, $this->job($objects)->sweep());
	}//end testADraftWithoutAReadableExpiryIsKept()
}//end class

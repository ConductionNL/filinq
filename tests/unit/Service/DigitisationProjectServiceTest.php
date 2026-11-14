<?php

/**
 * Unit tests for DigitisationProjectService: the sampling draw of scanned
 * paper originals into the checking queue.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-sampled-share-is-checked-by-a-person-req-pad-004
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DigitisationProjectService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

#[CoversClass(DigitisationProjectService::class)]
class DigitisationProjectServiceTest extends TestCase {

	private const NOW = '2026-11-13T10:00:00+01:00';

	/**
	 * How many of a thousand readable, paged scans are drawn at this share.
	 *
	 * @param mixed $share The project's check share.
	 *
	 * @return int
	 */
	private static function drawnOfAThousand(mixed $share): int {
		$service = new DigitisationProjectService(new Randomizer(new Mt19937(20261113)));
		$drawn   = 0;
		for ($i = 0; $i < 1000; $i++) {
			if ($service->drawn(['checkShare' => $share], true, 3) === true) {
				$drawn++;
			}
		}

		return $drawn;

	}//end drawnOfAThousand()

	public function testAZeroShareDrawsNothingAndAFullShareDrawsEverything(): void {
		$this->assertSame(0, self::drawnOfAThousand(0));
		$this->assertSame(1000, self::drawnOfAThousand(100));

	}//end testAZeroShareDrawsNothingAndAFullShareDrawsEverything()

	public function testATenPercentShareDrawsAboutOneInTen(): void {
		$drawn = self::drawnOfAThousand(10);

		$this->assertGreaterThan(70, $drawn);
		$this->assertLessThan(130, $drawn);

	}//end testATenPercentShareDrawsAboutOneInTen()

	public function testTheDrawIsRepeatableWithTheSameSeed(): void {
		$this->assertSame(self::drawnOfAThousand(37), self::drawnOfAThousand(37));

	}//end testTheDrawIsRepeatableWithTheSameSeed()

	public function testAnUnreadableSeparatorOrAnEmptyScanIsAlwaysChecked(): void {
		$service = new DigitisationProjectService(new Randomizer(new Mt19937(1)));

		$this->assertTrue($service->drawn(['checkShare' => 0], false, 3));
		$this->assertTrue($service->drawn(['checkShare' => 0], true, 0));
		$this->assertSame(['status' => 'scanned'], $service->afterScan(['checkShare' => 0], false, 3, self::NOW));

	}//end testAnUnreadableSeparatorOrAnEmptyScanIsAlwaysChecked()

	public function testAnOriginalNotDrawnIsRecordedAsPassedWithoutALook(): void {
		$service = new DigitisationProjectService(new Randomizer(new Mt19937(1)));

		$this->assertSame(
			['status' => 'checked', 'check' => ['result' => 'passed', 'checkedBy' => 'sample-not-drawn', 'checkedAt' => self::NOW]],
			$service->afterScan(['checkShare' => 0], true, 4, self::NOW)
		);
		$this->assertSame(['status' => 'scanned'], $service->afterScan(['checkShare' => 100], true, 4, self::NOW));

	}//end testAnOriginalNotDrawnIsRecordedAsPassedWithoutALook()

	public function testTheShareDefaultsToTenAndIsClampedToZeroToAHundred(): void {
		$this->assertSame(10, DigitisationProjectService::checkShare([]));
		$this->assertSame(10, DigitisationProjectService::checkShare(['checkShare' => 'many']));
		$this->assertSame(0, DigitisationProjectService::checkShare(['checkShare' => -5]));
		$this->assertSame(100, DigitisationProjectService::checkShare(['checkShare' => 250]));
		$this->assertSame(13, DigitisationProjectService::checkShare(['checkShare' => '12.6']));

	}//end testTheShareDefaultsToTenAndIsClampedToZeroToAHundred()

	public function testWithoutARandomSourceTheServiceStillDraws(): void {
		$service = new DigitisationProjectService();

		$this->assertTrue($service->drawn(['checkShare' => 100], true, 1));
		$this->assertFalse($service->drawn(['checkShare' => 0], true, 1));

	}//end testWithoutARandomSourceTheServiceStillDraws()
}//end class

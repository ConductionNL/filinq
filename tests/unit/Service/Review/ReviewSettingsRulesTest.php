<?php

/**
 * Unit tests for ReviewSettingsRules.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Review
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Service\Review\CertaintyThresholds;
use OCA\Filinq\Service\Review\ReviewSettingsRules;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Both review save rules run: the gate mode always, certain-from when the thresholds are wired.
 *
 * @covers \OCA\Filinq\Service\Review\ReviewSettingsRules
 * @uses   \OCA\Filinq\Service\Review\CheckedGateModeRule
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ReviewSettingsRulesTest extends TestCase {

	/**
	 * An unknown gate mode is refused with or without thresholds.
	 *
	 * @return void
	 */
	public function testTheGateModeIsAlwaysChecked(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The review gate mode must be "enforced" or "advisory".');

		(new ReviewSettingsRules())->assertValid(data: ['filinq.review.checked_gate' => 'off']);
	}//end testTheGateModeIsAlwaysChecked()

	/**
	 * The save goes to the thresholds, whose refusal comes through unchanged.
	 *
	 * @return void
	 */
	public function testCertainFromIsCheckedByTheThresholds(): void {
		$data = ['filinq.review.checked_gate' => 'advisory', 'filinq.review.certain_from' => '0.1'];
		$thresholds = $this->createMock(CertaintyThresholds::class);
		$thresholds->expects($this->once())->method('assertValid')->with($data)
			->willThrowException(new RuntimeException('below the floor', 400));

		try {
			(new ReviewSettingsRules(thresholds: $thresholds))->assertValid(data: $data);
			$this->fail('expected a refusal');
		} catch (RuntimeException $e) {
			$this->assertSame('below the floor', $e->getMessage());
			$this->assertSame(400, $e->getCode());
		}
	}//end testCertainFromIsCheckedByTheThresholds()

	/**
	 * A save without review settings passes both rules.
	 *
	 * @return void
	 */
	public function testOtherSettingsPass(): void {
		$thresholds = $this->createMock(CertaintyThresholds::class);
		$thresholds->expects($this->once())->method('assertValid')->with(['signing_provider' => 'native']);

		(new ReviewSettingsRules(thresholds: $thresholds))->assertValid(data: ['signing_provider' => 'native']);
	}//end testOtherSettingsPass()
}//end class

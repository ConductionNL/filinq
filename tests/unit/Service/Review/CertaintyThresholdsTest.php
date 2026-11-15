<?php

/**
 * Unit tests for CertaintyThresholds.
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

use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\Review\CertaintyThresholds;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The floor from OpenRegister, certain-from from filinq, the save rule and the classification.
 *
 * @covers \OCA\Filinq\Service\Review\CertaintyThresholds
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CertaintyThresholdsTest extends TestCase {

	/**
	 * The thresholds over a stored certain-from and an OpenRegister floor holder.
	 *
	 * @param string      $stored  The stored certain-from.
	 * @param object|null $handler What OpenRegister answers for its file settings; null: OpenRegister is absent.
	 *
	 * @return CertaintyThresholds The thresholds.
	 */
	private function thresholds(string $stored, ?object $handler): CertaintyThresholds {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->with('filinq', 'filinq.review.certain_from', '0.85')->willReturn($stored);
		$locator = $this->createMock(OpenRegisterServiceLocator::class);
		if ($handler === null) {
			$locator->method('get')->willThrowException(new RuntimeException('not available'));
		} else {
			$locator->method('get')->with('OCA\OpenRegister\Service\Settings\FileSettingsHandler')->willReturn($handler);
		}

		return new CertaintyThresholds(appConfig: $config, locator: $locator, logger: $this->createMock(LoggerInterface::class));
	}//end thresholds()

	/**
	 * An OpenRegister file settings handler with a floor.
	 *
	 * @param float $floor The floor.
	 *
	 * @return object The handler.
	 */
	private static function floor(float $floor): object {
		return new class ($floor) {
			/**
			 * Constructor.
			 *
			 * @param float $floor The floor.
			 */
			public function __construct(private float $floor) {
			}

			/**
			 * The floor.
			 *
			 * @return float The floor.
			 */
			public function confidenceThreshold(): float {
				return $this->floor;
			}
		};
	}//end floor()

	/**
	 * Both numbers and their sources, read from OpenRegister and filinq.
	 *
	 * @return void
	 */
	public function testReadsBothNumbers(): void {
		$this->assertSame(
			['floor' => 0.6, 'floorSource' => 'openregister', 'floorConfigurable' => true, 'certainFrom' => 0.9, 'certainFromSource' => 'filinq'],
			$this->thresholds(stored: '0.9', handler: self::floor(floor: 0.6))->read()
		);
	}//end testReadsBothNumbers()

	/**
	 * Without OpenRegister, or without its floor, 0.5 is shown and marked not configurable.
	 *
	 * @return void
	 */
	public function testAMissingFloorFallsBackAndSaysSo(): void {
		$absent = $this->thresholds(stored: '0.85', handler: null)->read();
		$this->assertSame(0.5, $absent['floor']);
		$this->assertFalse($absent['floorConfigurable']);

		$older = $this->thresholds(stored: '0.85', handler: new \stdClass())->read();
		$this->assertSame(0.5, $older['floor']);
		$this->assertFalse($older['floorConfigurable']);
	}//end testAMissingFloorFallsBackAndSaysSo()

	/**
	 * A stored certain-from that is not a number between 0 and 1 reads as the default.
	 *
	 * @return void
	 */
	public function testAnUnusableStoredValueReadsAsTheDefault(): void {
		foreach (['x', '', '1.5', '-0.1'] as $stored) {
			$this->assertSame(0.85, $this->thresholds(stored: $stored, handler: null)->certainFrom(), $stored);
		}

		$this->assertSame(1.0, $this->thresholds(stored: '1', handler: null)->certainFrom());
	}//end testAnUnusableStoredValueReadsAsTheDefault()

	/**
	 * The save rule: absent passes, the floor itself passes, below it or out of range is refused.
	 *
	 * @return void
	 */
	public function testTheSaveRule(): void {
		$thresholds = $this->thresholds(stored: '0.85', handler: self::floor(floor: 0.6));
		$thresholds->assertValid(data: ['signing_provider' => 'native']);
		$thresholds->assertValid(data: ['filinq.review.certain_from' => '0.6']);
		$thresholds->assertValid(data: ['filinq.review.certain_from' => 1]);

		$refusals = [
			'0.59' => 'Certain-from cannot be below the detection floor of 0.6 set in OpenRegister.',
			'abc' => 'Certain-from must be a number between 0 and 1.',
			'1.01' => 'Certain-from must be a number between 0 and 1.',
		];
		foreach ($refusals as $value => $message) {
			try {
				$thresholds->assertValid(data: ['filinq.review.certain_from' => (string) $value]);
				$this->fail('accepted ' . $value);
			} catch (RuntimeException $e) {
				$this->assertSame(400, $e->getCode());
				$this->assertSame($message, $e->getMessage());
			}
		}
	}//end testTheSaveRule()

	/**
	 * Below certain-from is uncertain, at it is certain, and a manual entity is always certain.
	 *
	 * @return void
	 */
	public function testClassification(): void {
		$thresholds = $this->thresholds(stored: '0.9', handler: null);

		$this->assertTrue($thresholds->isUncertain(relation: ['confidence' => 0.65, 'detectionMethod' => 'presidio'], certainFrom: 0.9));
		$this->assertFalse($thresholds->isUncertain(relation: ['confidence' => 0.9, 'detectionMethod' => 'presidio'], certainFrom: 0.9));
		$this->assertFalse($thresholds->isUncertain(relation: ['confidence' => 0.1, 'detectionMethod' => 'manual'], certainFrom: 0.9));
		$this->assertTrue($thresholds->isUncertain(relation: [], certainFrom: 0.9));
	}//end testClassification()
}//end class

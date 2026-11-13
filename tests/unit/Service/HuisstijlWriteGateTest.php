<?php

/**
 * The gate the profile sync writes a managed house style through.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-2.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\HuisstijlWriteGate;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\HuisstijlWriteGate
 */
class HuisstijlWriteGateTest extends TestCase {

	public function testTheGateIsOpenOnlyDuringTheWriteAndSharedAcrossInstances(): void {
		$gate = new HuisstijlWriteGate();
		$this->assertFalse($gate->isOpen());

		$result = $gate->during(static fn (): array => [(new HuisstijlWriteGate())->isOpen(), 'saved']);

		$this->assertSame([true, 'saved'], $result);
		$this->assertFalse($gate->isOpen());
	}//end testTheGateIsOpenOnlyDuringTheWriteAndSharedAcrossInstances()

	public function testANestedWriteKeepsTheGateOpenUntilTheOuterOneEnds(): void {
		$gate = new HuisstijlWriteGate();

		$inner = $gate->during(static fn (): bool => $gate->during(static fn (): bool => true) && $gate->isOpen());

		$this->assertTrue($inner);
		$this->assertFalse($gate->isOpen());
	}//end testANestedWriteKeepsTheGateOpenUntilTheOuterOneEnds()

	public function testAFailingWriteShutsTheGate(): void {
		$gate = new HuisstijlWriteGate();

		try {
			$gate->during(static function (): never {
				throw new RuntimeException('refused');
			});
			$this->fail('The failure did not come through');
		} catch (RuntimeException $e) {
			$this->assertSame('refused', $e->getMessage());
		}

		$this->assertFalse($gate->isOpen());
	}//end testAFailingWriteShutsTheGate()
}//end class

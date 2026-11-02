<?php

/**
 * Unit tests for the signer step value objects
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\SignerStepDecision;
use OCA\Filinq\EventListener\SignerStepTask;
use PHPUnit\Framework\TestCase;

/**
 * SignerStepTask and SignerStepDecision carry their values unchanged.
 *
 * @covers \OCA\Filinq\EventListener\SignerStepTask
 * @covers \OCA\Filinq\EventListener\SignerStepDecision
 */
final class SignerStepValuesTest extends TestCase {

	/**
	 * A step task exposes its sequence, task, position and object.
	 *
	 * @return void
	 */
	public function testStepTaskCarriesItsValues(): void {
		$task = new SignerStepTask(sequenceUuid: 'seq-1', taskUuid: 'task-1', position: 2, objectUuid: 'sign-req-1');

		$this->assertSame(
			['sequenceUuid' => 'seq-1', 'taskUuid' => 'task-1', 'position' => 2, 'objectUuid' => 'sign-req-1'],
			get_object_vars($task)
		);
	}//end testStepTaskCarriesItsValues()

	/**
	 * A decision exposes its signer, comment and classification, nulls included.
	 *
	 * @return void
	 */
	public function testDecisionCarriesItsValues(): void {
		$decision = new SignerStepDecision(userId: 'alice', comment: 'akkoord', isRejecting: false);
		$this->assertSame(['userId' => 'alice', 'comment' => 'akkoord', 'isRejecting' => false], get_object_vars($decision));

		$empty = new SignerStepDecision(userId: null, comment: null, isRejecting: true);
		$this->assertSame(['userId' => null, 'comment' => null, 'isRejecting' => true], get_object_vars($empty));
	}//end testDecisionCarriesItsValues()
}//end class

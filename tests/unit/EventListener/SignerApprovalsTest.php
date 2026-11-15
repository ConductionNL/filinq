<?php

/**
 * Tests for SignerApprovals.
 *
 * @category  Test
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

use OCA\Filinq\EventListener\SignerApprovals;
use OCA\OpenRegister\Db\Task;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests for SignerApprovals.
 *
 * @covers \OCA\Filinq\EventListener\SignerApprovals
 */
class SignerApprovalsTest extends TestCase {

	/**
	 * Only `approved` approves, trimmed and case-insensitive.
	 *
	 * @return void
	 */
	public function testOnlyApprovedApproves(): void {
		$approvals = new SignerApprovals();

		$this->assertTrue($approvals->isApprovingOutcome(outcome: ' Approved '));
		$this->assertFalse($approvals->isApprovingOutcome(outcome: 'done'));
		$this->assertFalse($approvals->isApprovingOutcome(outcome: ''));
	}//end testOnlyApprovedApproves()

	/**
	 * Every task needs an approving outcome and a non-blank signer.
	 *
	 * @return void
	 */
	public function testEveryTaskNeedsAnApprovingOutcomeAndASigner(): void {
		$approvals = new SignerApprovals();
		$signed = new Task(state: 'completed', outcome: 'approved', completedBy: 'alice');

		$this->assertTrue($approvals->areSigned(tasks: [$signed, $signed]));
		$this->assertTrue($approvals->areSigned(tasks: []));
		$this->assertFalse($approvals->areSigned(tasks: [$signed, new Task(state: 'completed', outcome: 'done', completedBy: 'bob')]));
		$this->assertFalse($approvals->areSigned(tasks: [new Task(state: 'completed', outcome: 'approved', completedBy: ' ')]));
		$this->assertFalse($approvals->areSigned(tasks: [new stdClass()]));
		$this->assertFalse($approvals->areSigned(tasks: [new Task(state: 'enabled', outcome: 'approved', completedBy: 'alice')]));
	}//end testEveryTaskNeedsAnApprovingOutcomeAndASigner()
}//end class

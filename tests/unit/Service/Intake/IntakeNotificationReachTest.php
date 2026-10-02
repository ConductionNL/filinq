<?php

/**
 * Whether the failure notification reaches anybody, said where a person is.
 *
 * 🔴 THE ANNOTATION ALONE WOULD HAVE REACHED NOBODY. The rule addresses
 * docudesk-woo-officers, and every declared group in this fleet ships EMPTY on
 * purpose. OpenRegister now records a rule that resolved to nobody, once per
 * run, at warning level — but a log line is not a person, and the person who
 * needs to know is the registrar who will never be told a scan failed. They are
 * looking at the inbox, not the server log.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Intake
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-failure-reaches-someone/specs/filinq-notifications/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use OCA\Filinq\Service\Intake\IntakeNotificationReach;
use PHPUnit\Framework\TestCase;

/**
 * Tests for IntakeNotificationReach.
 */
class IntakeNotificationReachTest extends TestCase {

	private IntakeNotificationReach $reach;

	/**
	 * Wire the service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->reach = new IntakeNotificationReach();
	}//end setUp()

	/**
	 * 🔴 WITH FAILURES AND AN EMPTY GROUP, THE INBOX SAYS NOBODY WAS TOLD, and
	 * says the reader is seeing it only because they opened the inbox.
	 *
	 * @return void
	 */
	public function testAnUnstaffedGroupIsNamedOnTheInboxWhenSomethingFailed(): void {
		$shown = $this->reach->describe(failureCount: 3, groupMembers: 0);

		$this->assertFalse($shown['staffed']);
		$this->assertStringContainsString('no one was told', $shown['warning']);
		$this->assertStringContainsString('because you opened the inbox', $shown['warning']);
		$this->assertStringContainsString(IntakeNotificationReach::GROUP, $shown['warning']);
	}//end testAnUnstaffedGroupIsNamedOnTheInboxWhenSomethingFailed()

	/**
	 * 🔴 AND IT IS SAID BEFORE ANYTHING FAILS TOO. An unstaffed rule is worth
	 * knowing about before the night it is needed; an inbox that mentions it
	 * only once something has gone unnoticed is telling somebody too late.
	 *
	 * @return void
	 */
	public function testAnUnstaffedGroupIsNamedEvenWithNothingFailing(): void {
		$shown = $this->reach->describe(failureCount: 0, groupMembers: 0);

		$this->assertNotSame('', $shown['warning']);
		$this->assertStringContainsString('no one will be told', $shown['warning']);
		$this->assertTrue($shown['needsAPerson']);
	}//end testAnUnstaffedGroupIsNamedEvenWithNothingFailing()

	/**
	 * The warning names the group, because "nobody is configured" sends an
	 * administrator hunting through settings and the group name makes it a
	 * two-minute fix.
	 *
	 * @return void
	 */
	public function testTheWarningNamesTheGroupToAddPeopleTo(): void {
		foreach ([0, 5] as $failures) {
			$shown = $this->reach->describe(failureCount: $failures, groupMembers: 0);

			$this->assertStringContainsString('docudesk-woo-officers', $shown['warning'], (string)$failures);
		}
	}//end testTheWarningNamesTheGroupToAddPeopleTo()

	/**
	 * A staffed group warns about nothing, so the warning means something when
	 * it does appear.
	 *
	 * @return void
	 */
	public function testAStaffedGroupProducesNoWarning(): void {
		$this->assertSame('', $this->reach->describe(failureCount: 3, groupMembers: 2)['warning']);
		$this->assertSame('', $this->reach->describe(failureCount: 0, groupMembers: 2)['warning']);
	}//end testAStaffedGroupProducesNoWarning()

	/**
	 * A staffed group with failures still needs a person: somebody was told,
	 * and somebody still has to do something about the documents.
	 *
	 * @return void
	 */
	public function testFailuresStillNeedAPersonEvenWhenSomebodyWasTold(): void {
		$this->assertTrue($this->reach->describe(failureCount: 3, groupMembers: 2)['needsAPerson']);
	}//end testFailuresStillNeedAPersonEvenWhenSomebodyWasTold()

	/**
	 * A quiet, staffed inbox needs nobody.
	 *
	 * @return void
	 */
	public function testAQuietStaffedInboxNeedsNobody(): void {
		$this->assertFalse($this->reach->describe(failureCount: 0, groupMembers: 2)['needsAPerson']);
	}//end testAQuietStaffedInboxNeedsNobody()

	/**
	 * How many people would actually be reached is reported, not just whether
	 * anybody would be: one person on holiday is a different risk from four.
	 *
	 * @return void
	 */
	public function testItSaysHowManyWouldBeReached(): void {
		$this->assertSame(4, $this->reach->describe(failureCount: 1, groupMembers: 4)['notificationReaches']);
		$this->assertSame(0, $this->reach->describe(failureCount: 1, groupMembers: 0)['notificationReaches']);
	}//end testItSaysHowManyWouldBeReached()
}//end class

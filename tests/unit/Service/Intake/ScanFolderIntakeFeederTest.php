<?php

/**
 * Where a refused scan ends up, and who sees it.
 *
 * 🔴 A REFUSAL THAT ONLY LANDS WHERE THERE IS A RESPONSE TO RETURN IS NOT A
 * RULE IN A BACKGROUND PATH. Before this, `IntakeRefusedException` was caught
 * in exactly one place — the HTTP controller, where a refusal becomes a status
 * code somebody sees. A folder watch has no response to return.
 *
 * The obvious listener catches, logs and returns. Then a scanned page sits in a
 * folder believed processed, the inbox shows nothing, the paper original has
 * already been filed, and nobody is looking. That is worse than a crash,
 * because a crash gets noticed.
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
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use OCA\Filinq\Service\Intake\ScanFolderIntakeFeeder;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ScanFolderIntakeFeeder.
 */
class ScanFolderIntakeFeederTest extends TestCase {

	private ScanFolderIntakeFeeder $feeder;

	/**
	 * Wire the feeder.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->feeder = new ScanFolderIntakeFeeder();
	}//end setUp()

	/**
	 * 🔴 A REFUSED PAGE IS LEFT WHERE A PERSON FINDS IT. Moving it to the
	 * processed folder puts a page nobody has into a folder everybody treats as
	 * done, and the paper original has already been filed.
	 *
	 * @return void
	 */
	public function testARefusedScanIsLeftWhereAPersonWillFindIt(): void {
		$outcome = $this->feeder->outcomeFor(
			outcome: ScanFolderIntakeFeeder::REFUSED,
			fileName: 'scan-2026-09-18-0041.pdf',
			reason: 'Unknown intake channel: scanner-2'
		);

		$this->assertSame(ScanFolderIntakeFeeder::LEAVE_FOR_A_PERSON, $outcome['disposition']);
		$this->assertFalse($this->feeder->mayMoveOn(outcome: $outcome));
		$this->assertTrue($outcome['needsAPerson']);
	}//end testARefusedScanIsLeftWhereAPersonWillFindIt()

	/**
	 * And the refusal says which file and why, so the person who finds it can
	 * act rather than guess.
	 *
	 * @return void
	 */
	public function testTheRefusalNamesTheFileAndTheReason(): void {
		$outcome = $this->feeder->outcomeFor(
			outcome: ScanFolderIntakeFeeder::REFUSED,
			fileName: 'scan-2026-09-18-0041.pdf',
			reason: 'Unknown intake channel: scanner-2'
		);

		$this->assertStringContainsString('scan-2026-09-18-0041.pdf', $outcome['message']);
		$this->assertStringContainsString('scanner-2', $outcome['message']);
		$this->assertStringContainsString('NOT taken into the inbox', $outcome['message']);
	}//end testTheRefusalNamesTheFileAndTheReason()

	/**
	 * 🔴 A REFUSAL WITH NO RECORDED REASON STILL SAYS SOMETHING, and says that
	 * the missing reason itself needs looking at. An empty message is how a
	 * refusal becomes invisible one level down.
	 *
	 * @return void
	 */
	public function testARefusalWithNoReasonStillSaysSomething(): void {
		$outcome = $this->feeder->outcomeFor(outcome: ScanFolderIntakeFeeder::REFUSED, fileName: 'scan.pdf');

		$this->assertNotSame('', trim($outcome['message']));
		$this->assertStringContainsString('no reason was recorded', $outcome['message']);
	}//end testARefusalWithNoReasonStillSaysSomething()

	/**
	 * A received file may move on, so the rule is not a blanket that leaves
	 * every scan in the folder until somebody clears it by hand.
	 *
	 * @return void
	 */
	public function testAReceivedScanMayMoveOn(): void {
		$outcome = $this->feeder->outcomeFor(outcome: ScanFolderIntakeFeeder::RECEIVED, fileName: 'scan.pdf');

		$this->assertTrue($this->feeder->mayMoveOn(outcome: $outcome));
		$this->assertTrue($outcome['countsAsNewIntake']);
		$this->assertFalse($outcome['needsAPerson']);
	}//end testAReceivedScanMayMoveOn()

	/**
	 * 🔴 "ALREADY SEEN" IS NOT "RECEIVED". The intake exists so the file may
	 * move, but counting it as a fresh receipt inflates the only number an
	 * operator has for whether the scanner is working.
	 *
	 * @return void
	 */
	public function testADuplicateMovesOnButIsNotCountedAsANewIntake(): void {
		$outcome = $this->feeder->outcomeFor(outcome: ScanFolderIntakeFeeder::ALREADY_SEEN, fileName: 'scan.pdf');

		$this->assertTrue($this->feeder->mayMoveOn(outcome: $outcome));
		$this->assertFalse($outcome['countsAsNewIntake']);
	}//end testADuplicateMovesOnButIsNotCountedAsANewIntake()

	/**
	 * 🔴 AN UNKNOWN OUTCOME IS TREATED AS REFUSED. The safe reading of "I do
	 * not recognise this state" is not "move the file on".
	 *
	 * @return void
	 */
	public function testAnUnrecognisedOutcomeIsTreatedAsRefused(): void {
		$outcome = $this->feeder->outcomeFor(outcome: 'something_new', fileName: 'scan.pdf');

		$this->assertSame(ScanFolderIntakeFeeder::REFUSED, $outcome['outcome']);
		$this->assertFalse($this->feeder->mayMoveOn(outcome: $outcome));
	}//end testAnUnrecognisedOutcomeIsTreatedAsRefused()

	/**
	 * And an outcome with no disposition at all does not move either, so a
	 * caller cannot get a move by handing over a malformed array.
	 *
	 * @return void
	 */
	public function testAnOutcomeWithNoDispositionDoesNotMove(): void {
		$this->assertFalse($this->feeder->mayMoveOn(outcome: []));
	}//end testAnOutcomeWithNoDispositionDoesNotMove()

	/**
	 * 🔴 THE SWEEP COUNTS THE THREE STATES APART. "40 files processed" over 37
	 * receipts, one duplicate and two refusals is a sentence that hides the
	 * only two a person has to act on.
	 *
	 * @return void
	 */
	public function testASweepCountsTheRefusalsApartAndSaysAPersonIsNeeded(): void {
		$summary = $this->feeder->summarise(
			outcomes: [
				$this->feeder->outcomeFor(outcome: ScanFolderIntakeFeeder::RECEIVED, fileName: 'a.pdf'),
				$this->feeder->outcomeFor(outcome: ScanFolderIntakeFeeder::ALREADY_SEEN, fileName: 'b.pdf'),
				$this->feeder->outcomeFor(outcome: ScanFolderIntakeFeeder::REFUSED, fileName: 'c.pdf', reason: 'no channel'),
				$this->feeder->outcomeFor(outcome: ScanFolderIntakeFeeder::REFUSED, fileName: 'd.pdf', reason: 'no channel'),
			]
		);

		$this->assertSame(1, $summary['received']);
		$this->assertSame(1, $summary['alreadySeen']);
		$this->assertSame(2, $summary['refusedCount']);
		$this->assertTrue($summary['needsAPerson']);
		$this->assertSame('c.pdf', $summary['refused'][0]['file']);
	}//end testASweepCountsTheRefusalsApartAndSaysAPersonIsNeeded()

	/**
	 * A clean sweep does not claim somebody is needed.
	 *
	 * @return void
	 */
	public function testACleanSweepNeedsNobody(): void {
		$summary = $this->feeder->summarise(
			outcomes: [$this->feeder->outcomeFor(outcome: ScanFolderIntakeFeeder::RECEIVED, fileName: 'a.pdf')]
		);

		$this->assertFalse($summary['needsAPerson']);
		$this->assertSame(0, $summary['refusedCount']);
	}//end testACleanSweepNeedsNobody()
}//end class

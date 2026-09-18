<?php

/**
 * Tests for the review gate.
 *
 * 🔴 DETECTION IS A MACHINE'S OPINION ABOUT WHERE THE NAMES ARE. It is a good
 * opinion and it is not a decision. The decision — "this copy may leave the
 * building" — is a person's, and before this gate nothing in the service
 * required one to have been made. The screen had a review step; the API did
 * not, and the batch path ran fifty-five thousand documents past it in an
 * afternoon.
 *
 * The case these tests exist for most is the third one: a document IS checked,
 * detection is re-run with a new model or a new prohibition list, and the old
 * mark still authorises output for findings nobody has looked at. The approval
 * was real. It was about a different set of findings.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-and-what-leaves-the-building/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Service\Redaction\RedactionReviewGate;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RedactionReviewGate.
 */
class RedactionReviewGateTest extends TestCase {

	private RedactionReviewGate $gate;

	/**
	 * Wire the gate.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->gate = new RedactionReviewGate();
	}//end setUp()

	/**
	 * A mark for one run, signed.
	 *
	 * @param string $run The detection run it covers.
	 *
	 * @return array<string, mixed> The mark.
	 */
	private function mark(string $run = 'run-1'): array {
		return ['detectionRun' => $run, 'checkedBy' => 'jdevries', 'checkedAt' => '2026-09-18T10:00:00+00:00'];
	}//end mark()

	/**
	 * 🔴 AN UNREVIEWED DOCUMENT IS REFUSED, AND THE MESSAGE SAYS WHAT TO DO.
	 *
	 * @return void
	 */
	public function testAnUnreviewedDocumentIsRefused(): void {
		$refusal = $this->gate->refuse(mark: null, detectionRunId: 'run-1');

		$this->assertNotNull($refusal);
		$this->assertSame(RedactionReviewGate::NEVER_CHECKED, $refusal['reason']);
		$this->assertStringContainsString('Nobody has checked this document yet', $refusal['message']);
		$this->assertStringContainsString('confirm it', $refusal['message']);
		$this->assertFalse($this->gate->mayWrite(mark: null, detectionRunId: 'run-1'));
	}//end testAnUnreviewedDocumentIsRefused()

	/**
	 * A reviewed document is written, so the gate is not a blanket that stops
	 * every publication and gets switched off.
	 *
	 * @return void
	 */
	public function testAReviewedDocumentMayBeWritten(): void {
		$this->assertNull($this->gate->refuse(mark: $this->mark(), detectionRunId: 'run-1'));
		$this->assertTrue($this->gate->mayWrite(mark: $this->mark(), detectionRunId: 'run-1'));
	}//end testAReviewedDocumentMayBeWritten()

	/**
	 * 🔴 RE-DETECTING INVALIDATES THE CHECK. The approval was real and it was
	 * about a different set of findings. A mark that outlives its run is a
	 * stale name telling an administrator the binding works.
	 *
	 * @return void
	 */
	public function testAMarkFromAnEarlierDetectionRunDoesNotAuthoriseThisOne(): void {
		$refusal = $this->gate->refuse(mark: $this->mark(run: 'run-1'), detectionRunId: 'run-2');

		$this->assertNotNull($refusal);
		$this->assertSame(RedactionReviewGate::CHECKED_AN_OLDER_RUN, $refusal['reason']);
		$this->assertStringContainsString('run again since', $refusal['message']);
		$this->assertStringContainsString('different set of findings', $refusal['message']);
	}//end testAMarkFromAnEarlierDetectionRunDoesNotAuthoriseThisOne()

	/**
	 * The refusal names who checked it before, so an operator can go and ask
	 * them rather than starting from nothing.
	 *
	 * @return void
	 */
	public function testTheStaleRefusalNamesWhoCheckedItBefore(): void {
		$refusal = $this->gate->refuse(mark: $this->mark(run: 'run-1'), detectionRunId: 'run-2');

		$this->assertStringContainsString('jdevries', $refusal['message']);
		$this->assertStringContainsString('2026-09-18', $refusal['message']);
	}//end testTheStaleRefusalNamesWhoCheckedItBefore()

	/**
	 * 🔴 A RE-RUN LEAVES NO MARK AT ALL. Carrying the old one forward "for
	 * reference" is how it ends up read as an approval: every surface that asks
	 * "is there a mark" gets yes.
	 *
	 * @return void
	 */
	public function testRedetectionLeavesNoMarkBehind(): void {
		$this->assertNull($this->gate->markAfterRedetection());
		$this->assertFalse(
			$this->gate->mayWrite(mark: $this->gate->markAfterRedetection(), detectionRunId: 'run-2')
		);
	}//end testRedetectionLeavesNoMarkBehind()

	/**
	 * A check nobody signed is a check nobody can be asked about.
	 *
	 * @return void
	 */
	public function testAnUnsignedMarkIsRefused(): void {
		$refusal = $this->gate->refuse(
			mark: ['detectionRun' => 'run-1', 'checkedAt' => '2026-09-18T10:00:00+00:00'],
			detectionRunId: 'run-1'
		);

		$this->assertSame(RedactionReviewGate::UNATTRIBUTED, $refusal['reason']);
		$this->assertStringContainsString('does not say who checked it', $refusal['message']);
	}//end testAnUnsignedMarkIsRefused()

	/**
	 * 🔴 NO DETECTION RUN MEANS EVERY MARK LOOKS CURRENT, which is exactly the
	 * shape that lets a stale approval through. So it refuses.
	 *
	 * @return void
	 */
	public function testAnAbsentDetectionRunIsRefusedRatherThanMatchingAnything(): void {
		$refusal = $this->gate->refuse(mark: $this->mark(run: ''), detectionRunId: '');

		$this->assertNotNull($refusal);
		$this->assertFalse($this->gate->mayWrite(mark: $this->mark(run: ''), detectionRunId: ''));
	}//end testAnAbsentDetectionRunIsRefusedRatherThanMatchingAnything()

	/**
	 * An empty mark array is treated as no mark, not as a mark with no fields.
	 *
	 * @return void
	 */
	public function testAnEmptyMarkIsNoMark(): void {
		$this->assertSame(
			RedactionReviewGate::NEVER_CHECKED,
			$this->gate->refuse(mark: [], detectionRunId: 'run-1')['reason']
		);
	}//end testAnEmptyMarkIsNoMark()

	/**
	 * 🔴 THE BATCH IS GATED BY THE SAME RULE, AND REPORTS WHAT IT REFUSED. A
	 * run over fifty-five thousand documents that quietly writes the reviewed
	 * ones and says nothing about the rest reads as a complete run, and the gap
	 * is invisible precisely because it is large.
	 *
	 * @return void
	 */
	public function testABatchIsGatedByTheSameRuleAndReportsItsRefusals(): void {
		$screened = $this->gate->screenBatch(
			documents: [
				['id' => 'doc-1', 'mark' => $this->mark(), 'detectionRun' => 'run-1'],
				['id' => 'doc-2', 'mark' => null, 'detectionRun' => 'run-1'],
				['id' => 'doc-3', 'mark' => $this->mark(run: 'run-0'), 'detectionRun' => 'run-1'],
			]
		);

		$this->assertSame(['doc-1'], $screened['write']);
		$this->assertCount(2, $screened['refused']);
		$this->assertSame('doc-2', $screened['refused'][0]['document']);
		$this->assertSame(RedactionReviewGate::CHECKED_AN_OLDER_RUN, $screened['refused'][1]['reason']);
	}//end testABatchIsGatedByTheSameRuleAndReportsItsRefusals()

	/**
	 * Every refusal carries a message somebody can act on, so no path produces
	 * a bare reason code an operator has to look up.
	 *
	 * @return void
	 */
	public function testEveryRefusalCarriesWordsAnOperatorCanActOn(): void {
		$cases = [
			[null, 'run-1'],
			[$this->mark(run: 'run-0'), 'run-1'],
			[['detectionRun' => 'run-1'], 'run-1'],
			[$this->mark(), ''],
		];

		foreach ($cases as [$mark, $run]) {
			$refusal = $this->gate->refuse(mark: $mark, detectionRunId: $run);

			$this->assertNotNull($refusal);
			$this->assertNotSame('', trim($refusal['message']));
			$this->assertGreaterThan(40, strlen($refusal['message']), 'a refusal must explain, not label');
		}
	}//end testEveryRefusalCarriesWordsAnOperatorCanActOn()
}//end class

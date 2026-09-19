<?php

/**
 * What the inbox says while it is reading, and when it could not.
 *
 * 🔴 A BACKGROUND JOB HAS NO RESPONSE TO RETURN. A job that throws leaves the
 * record saying `reading` forever, and on screen that is indistinguishable from
 * a slow OCR — the longer it sits, the more it looks like patience rather than
 * breakage. This is the same shape as the folder watch swallowing a refusal,
 * one layer along.
 *
 * ⚠️ And the honest limit: nothing here notifies anybody. A failure reaches a
 * person who OPENS the inbox and nobody who does not. That is recorded in the
 * class and in the PR rather than implied away.
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
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use OCA\Filinq\Service\Intake\IntakeReadingProgress;
use PHPUnit\Framework\TestCase;

/**
 * Tests for IntakeReadingProgress.
 */
class IntakeReadingProgressTest extends TestCase {

	private IntakeReadingProgress $progress;

	/**
	 * Wire the service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->progress = new IntakeReadingProgress();
	}//end setUp()

	/**
	 * 🔴 A FAILED READING IS ITS OWN STATE, not a reading that never ended.
	 *
	 * @return void
	 */
	public function testAFailedReadingIsNotLeftLookingLikeASlowOne(): void {
		$recorded = $this->progress->progressFor(
			state: IntakeReadingProgress::FAILED,
			error: 'no text layer and OCR returned nothing'
		);

		$this->assertSame(IntakeReadingProgress::FAILED, $recorded['readingState']);
		$this->assertNotSame(IntakeReadingProgress::READING, $recorded['readingState']);
		$this->assertStringContainsString('no text layer', $recorded['readingError']);
	}//end testAFailedReadingIsNotLeftLookingLikeASlowOne()

	/**
	 * 🔴 A FAILURE WITH NO WORDS STILL SAYS SOMETHING. The person who meets it
	 * is a registrar, not the developer who wrote the job.
	 *
	 * @return void
	 */
	public function testAFailureWithNoReasonStillRecordsOne(): void {
		$recorded = $this->progress->progressFor(state: IntakeReadingProgress::FAILED);

		$this->assertNotSame('', trim((string)$recorded['readingError']));
		$this->assertStringContainsString('needs looking at', $recorded['readingError']);
	}//end testAFailureWithNoReasonStillRecordsOne()

	/**
	 * 🔴 AN UNKNOWN STATE IS NOT `read`. Treating it as finished is how a
	 * document with no text becomes searchable by nothing while the inbox
	 * reports it done.
	 *
	 * @return void
	 */
	public function testAnUnrecognisedStateIsRecordedAsFailedRatherThanRead(): void {
		$recorded = $this->progress->progressFor(state: 'nearly_done');

		$this->assertSame(IntakeReadingProgress::FAILED, $recorded['readingState']);
		$this->assertStringContainsString('nobody recognises', $recorded['readingError']);
	}//end testAnUnrecognisedStateIsRecordedAsFailedRatherThanRead()

	/**
	 * A successful reading clears any earlier error, so a document that failed
	 * and was re-read does not keep a stale reason beside a good result.
	 *
	 * @return void
	 */
	public function testASuccessfulReadingClearsTheEarlierError(): void {
		$recorded = $this->progress->progressFor(state: IntakeReadingProgress::READ);

		$this->assertNull($recorded['readingError']);
	}//end testASuccessfulReadingClearsTheEarlierError()

	/**
	 * 🔴 A DOCUMENT STILL BEING READ, AND ONE THAT COULD NOT BE, BOTH STAY IN
	 * THE INBOX. Listing only the finished ones is how an inbox reports itself
	 * empty while work sits in a queue.
	 *
	 * @return void
	 */
	public function testEveryDocumentStaysInTheInboxWhateverItsReadingState(): void {
		foreach (IntakeReadingProgress::STATES as $state) {
			$this->assertTrue(
				$this->progress->showsInInbox(document: ['readingState' => $state]),
				$state
			);
		}
	}//end testEveryDocumentStaysInTheInboxWhateverItsReadingState()

	/**
	 * 🔴 STILL-READING AND COULD-NOT-BE-READ ARE COUNTED APART. Folding them
	 * produces "3 being read" over one queue and two breakages.
	 *
	 * @return void
	 */
	public function testStillReadingAndCouldNotBeReadAreCountedApart(): void {
		$summary = $this->progress->summarise(
			documents: [
				['id' => 'a', 'readingState' => IntakeReadingProgress::QUEUED],
				['id' => 'b', 'readingState' => IntakeReadingProgress::READING],
				['id' => 'c', 'readingState' => IntakeReadingProgress::FAILED, 'readingError' => 'no text layer'],
				['id' => 'd', 'readingState' => IntakeReadingProgress::READ],
			]
		);

		$this->assertSame(2, $summary['stillReading']);
		$this->assertSame(1, $summary['couldNotBeRead']);
		$this->assertTrue($summary['needsAPerson']);
		$this->assertSame('c', $summary['failures'][0]['document']);
	}//end testStillReadingAndCouldNotBeReadAreCountedApart()

	/**
	 * Every document is accounted for in exactly one state, so the counts
	 * cannot quietly omit one.
	 *
	 * @return void
	 */
	public function testEveryDocumentIsAccountedForExactlyOnce(): void {
		$documents = [
			['id' => 'a', 'readingState' => IntakeReadingProgress::QUEUED],
			['id' => 'b'],
			['id' => 'c', 'readingState' => 'something_new'],
		];

		$summary = $this->progress->summarise(documents: $documents);

		$this->assertSame(count($documents), $summary['accountedFor']);
	}//end testEveryDocumentIsAccountedForExactlyOnce()

	/**
	 * A document with an unrecognised state counts as a failure rather than
	 * vanishing from every bucket.
	 *
	 * @return void
	 */
	public function testAnUnrecognisedStateCountsAsAFailureRatherThanVanishing(): void {
		$summary = $this->progress->summarise(documents: [['id' => 'a', 'readingState' => 'something_new']]);

		$this->assertSame(1, $summary['couldNotBeRead']);
		$this->assertTrue($summary['needsAPerson']);
	}//end testAnUnrecognisedStateCountsAsAFailureRatherThanVanishing()

	/**
	 * A clean inbox does not claim somebody is needed.
	 *
	 * @return void
	 */
	public function testACleanInboxNeedsNobody(): void {
		$summary = $this->progress->summarise(documents: [['id' => 'a', 'readingState' => IntakeReadingProgress::READ]]);

		$this->assertFalse($summary['needsAPerson']);
		$this->assertSame(0, $summary['couldNotBeRead']);
	}//end testACleanInboxNeedsNobody()
}//end class

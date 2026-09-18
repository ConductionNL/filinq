<?php

/**
 * What a half-finished erasure leaves behind, and whether it can be resumed.
 *
 * 🔴 THIS RUN CANNOT BE ALL-OR-NOTHING, WHICH IS THE DIFFERENCE FROM THE
 * PLATFORM. OpenRegister's anonymisation prepares every target before applying
 * any, so a record is never half done — it can, because the unit is one record
 * and a handful of stores. Here the unit is a person across eighty documents,
 * each a separate file with its own versions, and there is no transaction
 * across them.
 *
 * So the honest design makes the half-way state finishable and visible rather
 * than denying it. And the deadline is why it matters more here than for an
 * archival sweep: a retention sweep that stops runs again next night, while a
 * stopped erasure nobody notices is a missed legal deadline with a person on
 * the other end.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SubjectErasure;

use OCA\Filinq\Service\SubjectErasure\SubjectErasureJob;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SubjectErasureJob.
 */
class SubjectErasureJobTest extends TestCase {

	private SubjectErasureJob $job;

	/**
	 * Wire the job.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->job = new SubjectErasureJob();
	}//end setUp()

	/**
	 * 🔴 A STOPPED RUN IS `partially_completed`, NOT `running`. A stopped run
	 * that still says running is indistinguishable from a slow one, and nobody
	 * looks at it until the deadline has passed.
	 *
	 * @return void
	 */
	public function testAStoppedRunSaysPartiallyCompletedRatherThanRunning(): void {
		$this->assertSame(
			SubjectErasureJob::PARTIALLY_COMPLETED,
			$this->job->statusFor(total: 80, done: 31)
		);
		$this->assertNotSame(
			SubjectErasureJob::RUNNING,
			$this->job->statusFor(total: 80, done: 31)
		);
	}//end testAStoppedRunSaysPartiallyCompletedRatherThanRunning()

	/**
	 * A finished run says so, and an empty scope is finished rather than stuck.
	 *
	 * @return void
	 */
	public function testAFinishedRunSaysCompleted(): void {
		$this->assertSame(SubjectErasureJob::COMPLETED, $this->job->statusFor(total: 80, done: 80));
		$this->assertSame(SubjectErasureJob::COMPLETED, $this->job->statusFor(total: 0, done: 0));
	}//end testAFinishedRunSaysCompleted()

	/**
	 * 🔴 IT RESUMES AT THE LAST RECORDED DOCUMENT, NOT AFTER IT. That document
	 * may have been mid-write when the run stopped. Erasing an occurrence that
	 * is already gone finds nothing and changes nothing, so re-doing one
	 * document is free — and skipping one is not.
	 *
	 * @return void
	 */
	public function testItResumesAtTheLastDocumentRatherThanAfterIt(): void {
		$remaining = $this->job->resumeFrom(
			documents: ['doc-1', 'doc-2', 'doc-3', 'doc-4'],
			progress: ['lastDocument' => 'doc-2']
		);

		$this->assertSame(['doc-2', 'doc-3', 'doc-4'], $remaining);
	}//end testItResumesAtTheLastDocumentRatherThanAfterIt()

	/**
	 * With no resume point it starts from the beginning.
	 *
	 * @return void
	 */
	public function testWithNoResumePointItStartsFromTheBeginning(): void {
		$documents = ['doc-1', 'doc-2'];

		$this->assertSame($documents, $this->job->resumeFrom(documents: $documents, progress: []));
	}//end testWithNoResumePointItStartsFromTheBeginning()

	/**
	 * 🔴 A RESUME POINT THAT IS NO LONGER IN SCOPE STARTS OVER. The scope
	 * changed between runs; resuming from an index would silently skip whatever
	 * moved, and the skipped documents are the ones nobody would ever look at
	 * again.
	 *
	 * @return void
	 */
	public function testAResumePointThatVanishedStartsOverRatherThanSkipping(): void {
		$documents = ['doc-9', 'doc-10'];

		$this->assertSame(
			$documents,
			$this->job->resumeFrom(documents: $documents, progress: ['lastDocument' => 'doc-2'])
		);
	}//end testAResumePointThatVanishedStartsOverRatherThanSkipping()

	/**
	 * 🔴 NOTHING IS ROLLED BACK, AND THE ACCOUNT SAYS SO. Rolling an erasure
	 * back means putting the name back into a record somebody asked to be
	 * removed from.
	 *
	 * @return void
	 */
	public function testAStoppedRunRollsNothingBackAndSaysWhatItLeft(): void {
		$account = $this->job->accountOf(
			progress: ['documentsTotal' => 80, 'documentsDone' => 31, 'lastDocument' => 'doc-31', 'occurrencesErased' => 74],
			dueAt: '2026-10-16'
		);

		$this->assertFalse($account['rolledBack']);
		$this->assertSame(49, $account['documentsRemaining']);
		$this->assertSame('doc-31', $account['resumePoint']);
		$this->assertStringContainsString('stay erased', $account['message']);
		$this->assertStringContainsString('nothing is rolled back', $account['message']);
	}//end testAStoppedRunRollsNothingBackAndSaysWhatItLeft()

	/**
	 * 🔴 AND IT SAYS THE REQUEST IS NOT ANSWERED, WITH THE DEADLINE. A stopped
	 * erasure nobody notices is a missed legal deadline, not a delayed job.
	 *
	 * @return void
	 */
	public function testTheAccountSaysTheRequestIsNotAnsweredAndWhenItIsDue(): void {
		$account = $this->job->accountOf(
			progress: ['documentsTotal' => 80, 'documentsDone' => 31, 'lastDocument' => 'doc-31'],
			dueAt: '2026-10-16'
		);

		$this->assertStringContainsString('NOT answered yet', $account['message']);
		$this->assertStringContainsString('2026-10-16', $account['message']);
	}//end testTheAccountSaysTheRequestIsNotAnsweredAndWhenItIsDue()

	/**
	 * A completed run does not claim to be unfinished.
	 *
	 * @return void
	 */
	public function testACompletedRunReportsItself(): void {
		$account = $this->job->accountOf(progress: ['documentsTotal' => 12, 'documentsDone' => 12]);

		$this->assertSame(SubjectErasureJob::COMPLETED, $account['status']);
		$this->assertStringNotContainsString('NOT answered', $account['message']);
	}//end testACompletedRunReportsItself()
}//end class

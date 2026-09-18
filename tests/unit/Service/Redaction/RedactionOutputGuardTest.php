<?php

/**
 * Tests for the seam where the review gate actually stops a write.
 *
 * 🔴 THE GATE WAS ALREADY CORRECT AND NOTHING CALLED IT. That is the failure
 * these tests are about: a decision class with its own green suite, sitting
 * beside an output path that never asks it anything. So these assertions are
 * about the asking, and about what happens when the asking cannot be answered.
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
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Exception\RedactionNotReviewedException;
use OCA\Filinq\Service\Redaction\DetectionRunIdentity;
use OCA\Filinq\Service\Redaction\RedactionOutputGuard;
use OCA\Filinq\Service\Redaction\RedactionReviewGate;
use OCA\Filinq\Service\Redaction\RedactionReviewMarkRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RedactionOutputGuard.
 */
class RedactionOutputGuardTest extends TestCase {

	/**
	 * The names a person was found under, as one detection run returns them.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private const FOUND = [
		['entityType' => 'PERSON', 'text' => 'Fatima El-Amrani', 'start' => 120, 'end' => 136],
		['entityType' => 'BSN', 'text' => '123456782', 'start' => 300, 'end' => 309],
	];

	/**
	 * The mark store.
	 *
	 * @var RedactionReviewMarkRepository&MockObject
	 */
	private RedactionReviewMarkRepository $marks;

	/**
	 * Names one detection run. The real one: it is pure.
	 *
	 * @var DetectionRunIdentity
	 */
	private DetectionRunIdentity $runs;

	/**
	 * The guard under test.
	 *
	 * @var RedactionOutputGuard
	 */
	private RedactionOutputGuard $guard;

	/**
	 * Wire the guard over a mark store that says nothing by default.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		// onlyMethods, so a typo in a method name fails here rather than
		// inventing a store method the real class does not have.
		$this->marks = $this->getMockBuilder(RedactionReviewMarkRepository::class)
			->disableOriginalConstructor()
			->onlyMethods(['findFor', 'save'])
			->getMock();

		$this->runs = new DetectionRunIdentity();

		$this->guard = new RedactionOutputGuard(
			gate: new RedactionReviewGate(),
			marks: $this->marks,
			runs: $this->runs
		);

	}//end setUp()

	/**
	 * A document nobody has checked produces no copy.
	 *
	 * @return void
	 */
	public function testADocumentNobodyCheckedIsRefused(): void {
		$this->marks->method('findFor')->willReturn(null);

		$this->expectException(RedactionNotReviewedException::class);
		$this->expectExceptionMessage('Nobody has checked this document yet');

		$this->guard->assertMayWrite(fileId: 42, entities: self::FOUND);

	}//end testADocumentNobodyCheckedIsRefused()

	/**
	 * A checked document is written.
	 *
	 * @return void
	 */
	public function testACheckedDocumentMayBeWritten(): void {
		$this->marks->method('findFor')->willReturn(
			[
				'document' => '42',
				'detectionRun' => $this->runs->of(fileId: 42, entities: self::FOUND),
				'checkedBy' => 'noor',
				'checkedAt' => '2026-09-18T09:00:00+00:00',
			]
		);

		$this->guard->assertMayWrite(fileId: 42, entities: self::FOUND);

		$this->addToAssertionCount(1);

	}//end testACheckedDocumentMayBeWritten()

	/**
	 * A check of yesterday's findings does not cover today's.
	 *
	 * 🔴 THIS IS THE ONE THAT HIDES. The approval is real, it names a person,
	 * it has a date, and every surface that asks "is there a mark" gets yes.
	 * It was about a different set of findings: detection has run again and
	 * found one more name, and nobody has looked at that one.
	 *
	 * @return void
	 */
	public function testAnEarlierCheckDoesNotCoverANewDetectionRun(): void {
		$this->marks->method('findFor')->willReturn(
			[
				'document' => '42',
				'detectionRun' => $this->runs->of(fileId: 42, entities: self::FOUND),
				'checkedBy' => 'noor',
				'checkedAt' => '2026-09-18T09:00:00+00:00',
			]
		);

		$foundAgain = array_merge(
			self::FOUND,
			[['entityType' => 'PERSON', 'text' => 'Henk Bakker', 'start' => 900, 'end' => 911]]
		);

		$this->expectException(RedactionNotReviewedException::class);
		$this->expectExceptionMessage('the detection has been run again since');

		$this->guard->assertMayWrite(fileId: 42, entities: $foundAgain);

	}//end testAnEarlierCheckDoesNotCoverANewDetectionRun()

	/**
	 * The same findings in a different order are the same run.
	 *
	 * Refusing here would train an operator to re-confirm a document that has
	 * not changed, which is how a refusal stops being read.
	 *
	 * @return void
	 */
	public function testTheSameFindingsInAnotherOrderAreTheSameRun(): void {
		$this->marks->method('findFor')->willReturn(
			[
				'document' => '42',
				'detectionRun' => $this->runs->of(fileId: 42, entities: self::FOUND),
				'checkedBy' => 'noor',
				'checkedAt' => '2026-09-18T09:00:00+00:00',
			]
		);

		$this->guard->assertMayWrite(fileId: 42, entities: array_reverse(self::FOUND));

		$this->addToAssertionCount(1);

	}//end testTheSameFindingsInAnotherOrderAreTheSameRun()

	/**
	 * An edited file is a different run, even with the same findings.
	 *
	 * @return void
	 */
	public function testEditingTheFileEndsTheCheck(): void {
		$this->marks->method('findFor')->willReturn(
			[
				'document' => '42',
				'detectionRun' => $this->runs->of(fileId: 42, entities: self::FOUND, etag: 'aaa'),
				'checkedBy' => 'noor',
				'checkedAt' => '2026-09-18T09:00:00+00:00',
			]
		);

		$this->expectException(RedactionNotReviewedException::class);

		$this->guard->assertMayWrite(fileId: 42, entities: self::FOUND, etag: 'bbb');

	}//end testEditingTheFileEndsTheCheck()

	/**
	 * A check nobody signed is refused.
	 *
	 * @return void
	 */
	public function testAnUnsignedCheckIsRefused(): void {
		$this->marks->method('findFor')->willReturn(
			[
				'document' => '42',
				'detectionRun' => $this->runs->of(fileId: 42, entities: self::FOUND),
				'checkedBy' => '',
				'checkedAt' => '2026-09-18T09:00:00+00:00',
			]
		);

		$this->expectException(RedactionNotReviewedException::class);
		$this->expectExceptionMessage('does not say who checked it');

		$this->guard->assertMayWrite(fileId: 42, entities: self::FOUND);

	}//end testAnUnsignedCheckIsRefused()

	/**
	 * A mark store that cannot be read refuses rather than waves through.
	 *
	 * @return void
	 */
	public function testAnUnreadableMarkStoreRefuses(): void {
		// The repository turns a failed read into null; the guard must treat
		// that as "nobody checked", not as "no objection recorded".
		$this->marks->method('findFor')->willReturn(null);

		$this->expectException(RedactionNotReviewedException::class);

		$this->guard->assertMayWrite(fileId: 42, entities: self::FOUND);

	}//end testAnUnreadableMarkStoreRefuses()

	/**
	 * Marking a document checked records this run and this person.
	 *
	 * @return void
	 */
	public function testMarkingCheckedRecordsTheRunAndThePerson(): void {
		$written = [];
		$this->marks->method('save')->willReturnCallback(
			function (array $mark) use (&$written): array {
				$written = $mark;

				return $mark;
			}
		);

		$this->guard->markChecked(fileId: 42, entities: self::FOUND, checkedBy: 'noor', note: 'two names');

		$this->assertSame(
			$this->runs->of(fileId: 42, entities: self::FOUND),
			$written['detectionRun'],
			'the mark must cover the run that was actually looked at'
		);
		$this->assertSame('noor', $written['checkedBy']);
		$this->assertSame(2, $written['entityCount']);
		$this->assertSame('two names', $written['note']);

	}//end testMarkingCheckedRecordsTheRunAndThePerson()

	/**
	 * A batch reports its refusals rather than dropping them.
	 *
	 * @return void
	 */
	public function testABatchReportsWhichDocumentsItRefused(): void {
		$this->marks->method('findFor')->willReturnCallback(
			function (string $document): ?array {
				if ($document !== '42') {
					return null;
				}

				return [
					'document' => '42',
					'detectionRun' => $this->runs->of(fileId: 42, entities: self::FOUND),
					'checkedBy' => 'noor',
					'checkedAt' => '2026-09-18T09:00:00+00:00',
				];
			}
		);

		$screened = $this->guard->screen(
			documents: [
				['fileId' => 42, 'entities' => self::FOUND],
				['fileId' => 43, 'entities' => self::FOUND],
			]
		);

		$this->assertSame([42], $screened['allowed']);
		$this->assertCount(1, $screened['refused']);
		$this->assertSame(43, $screened['refused'][0]['fileId']);

	}//end testABatchReportsWhichDocumentsItRefused()
}//end class

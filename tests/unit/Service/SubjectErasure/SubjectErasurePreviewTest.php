<?php

/**
 * The preview an operator reads before an erasure runs.
 *
 * 🔴 THE BLAST RADIUS IS THE POINT. "Fatima El-Amrani" is one person; "de
 * Vries" is four thousand occurrences across a municipality, and a run started
 * on the second without looking is a mass edit of records belonging to people
 * who asked for nothing.
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

use OCA\Filinq\Service\SubjectErasure\SubjectErasurePreview;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SubjectErasurePreview.
 */
class SubjectErasurePreviewTest extends TestCase {

	private SubjectErasurePreview $preview;

	/**
	 * Wire the preview.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->preview = new SubjectErasurePreview();
	}//end setUp()

	/**
	 * Many plain documents.
	 *
	 * @param int $count How many.
	 *
	 * @return array<int, array<string, mixed>> The documents.
	 */
	private function documents(int $count): array {
		$out = [];
		for ($i = 0; $i < $count; $i++) {
			$out[] = ['id' => 'doc-'.$i, 'occurrences' => 2];
		}

		return $out;
	}//end documents()

	/**
	 * Each document is listed with its count, version state and obligations.
	 *
	 * @return void
	 */
	public function testEachDocumentIsListedWithWhatAnOperatorNeeds(): void {
		$built = $this->preview->build(
			documents: [['id' => 'doc-1', 'occurrences' => 4, 'finalVersion' => true, 'legal_hold' => 'a hold']]
		);

		$entry = $built['documents'][0];

		$this->assertSame(4, $entry['occurrences']);
		$this->assertTrue($entry['finalVersion']);
		$this->assertCount(1, $entry['obligations']);
	}//end testEachDocumentIsListedWithWhatAnOperatorNeeds()

	/**
	 * 🔴 THE CAP STATES ITSELF AND THE FULL COUNT. A list silently cut at two
	 * hundred reads as the whole answer: the operator approves what they can
	 * see and the job runs over everything they cannot.
	 *
	 * @return void
	 */
	public function testACappedPreviewSaysSoAndSaysHowManyThereReallyAre(): void {
		$built = $this->preview->build(documents: $this->documents(count: 450), cap: 200);

		$this->assertCount(200, $built['documents']);
		$this->assertSame(200, $built['listed']);
		$this->assertSame(450, $built['documentsTotal']);
		$this->assertTrue($built['truncated']);
		$this->assertSame(900, $built['occurrencesTotal'], 'the count covers everything, not just the page');
	}//end testACappedPreviewSaysSoAndSaysHowManyThereReallyAre()

	/**
	 * An uncapped preview does not claim to be truncated.
	 *
	 * @return void
	 */
	public function testAShortPreviewIsNotReportedAsTruncated(): void {
		$built = $this->preview->build(documents: $this->documents(count: 3));

		$this->assertFalse($built['truncated']);
		$this->assertSame(3, $built['listed']);
	}//end testAShortPreviewIsNotReportedAsTruncated()

	/**
	 * 🔴 A FILE THAT CANNOT BE READ IS LISTED, NOT DROPPED. It is a place the
	 * person may still be, and leaving it out makes the erasure look complete.
	 *
	 * @return void
	 */
	public function testAnUnreadableFileIsNamedRatherThanOmitted(): void {
		$built = $this->preview->build(
			documents: [
				['id' => 'doc-1', 'occurrences' => 2],
				['id' => 'doc-2', 'unreadable' => 'encrypted archive, no password on file'],
			]
		);

		$this->assertSame(1, $built['unprocessableCount']);
		$this->assertSame('doc-2', $built['unprocessable'][0]['document']);
		$this->assertStringContainsString('encrypted archive', $built['unprocessable'][0]['reason']);
	}//end testAnUnreadableFileIsNamedRatherThanOmitted()

	/**
	 * Erasable and refused documents are counted apart, so an operator can see
	 * how much of the request is actually grantable before starting.
	 *
	 * @return void
	 */
	public function testErasableAndRefusedAreCountedApart(): void {
		$built = $this->preview->build(
			documents: [
				['id' => 'doc-1', 'occurrences' => 1],
				['id' => 'doc-2', 'occurrences' => 1, 'retention' => 'P10Y'],
			]
		);

		$this->assertSame(1, $built['erasableDocuments']);
		$this->assertSame(1, $built['refusedDocuments']);
	}//end testErasableAndRefusedAreCountedApart()

	/**
	 * 🔴 AN EXCLUSION WITHOUT A REASON IS REFUSED. The person who asked to be
	 * removed can ask why a voorkomen is still there, and "somebody unticked
	 * it" is not an answer.
	 *
	 * @return void
	 */
	public function testAnExclusionWithoutAReasonIsRefused(): void {
		$refusals = $this->preview->refuseExclusions(
			exclusions: [['occurrence' => 'doc-1:3'], ['occurrence' => 'doc-2:1', 'reason' => 'a different person']]
		);

		$this->assertCount(1, $refusals);
		$this->assertStringContainsString('doc-1:3', $refusals[0]);
	}//end testAnExclusionWithoutAReasonIsRefused()
}//end class

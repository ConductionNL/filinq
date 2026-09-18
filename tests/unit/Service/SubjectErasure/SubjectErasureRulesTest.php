<?php

/**
 * Erasing a person while the records stay.
 *
 * 🔴 THE DIVERGENCE FROM THE PLATFORM IS THE POINT OF THESE TESTS. OpenRegister
 * anonymises as an ARCHIVAL OUTCOME: a retention clock runs out, and its
 * `pseudonym` treatment deliberately leaves a stable, joinable token so the
 * statistics survive. Its own docblock names the residual risk.
 *
 * A data subject's erasure is the opposite requirement. A stable token still
 * links every document that mentioned them — the linkage the request exists to
 * break — and a reversible mapping is a way back to the person that outlives
 * the erasure entirely.
 *
 * And the trigger differs, so the refusals must. The platform refuses a whole
 * act under a legal hold, which is proportionate for a sweep it chose to run. A
 * person exercising a right did not choose these documents, and answering a
 * mostly-grantable request with a flat no because one document is held is not
 * an answer.
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
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/erase-a-person-while-the-records-stay/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SubjectErasure;

use OCA\Filinq\Service\SubjectErasure\SubjectErasureRules;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SubjectErasureRules.
 */
class SubjectErasureRulesTest extends TestCase {

	private SubjectErasureRules $rules;

	/**
	 * Wire the rules.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->rules = new SubjectErasureRules();
	}//end setUp()

	/**
	 * 🔴 THE PSEUDONYM DOES NOT ERASE. The platform's stable token keeps every
	 * document that mentioned the person pointing at the same value, which is
	 * exactly the linkage the request exists to break.
	 *
	 * @return void
	 */
	public function testAStablePseudonymDoesNotCountAsErasure(): void {
		$this->assertFalse($this->rules->treatmentErases(treatment: 'pseudonym'));
		$this->assertFalse($this->rules->treatmentErases(treatment: 'generalise'));
	}//end testAStablePseudonymDoesNotCountAsErasure()

	/**
	 * Removing the value, or writing one fixed value over everybody, does.
	 *
	 * @return void
	 */
	public function testRemovingOrFlatteningDoesErase(): void {
		$this->assertTrue($this->rules->treatmentErases(treatment: 'remove'));
		$this->assertTrue($this->rules->treatmentErases(treatment: 'fixed'));
	}//end testRemovingOrFlatteningDoesErase()

	/**
	 * 🔴 A LEGAL HOLD REFUSES ITS OWN DOCUMENT AND NAMES WHO DECIDES. A refusal
	 * the requester cannot escalate reads as a silent failure, and they have a
	 * statutory clock running.
	 *
	 * @return void
	 */
	public function testALegalHoldRefusesAndNamesWhoDecides(): void {
		$refusals = $this->rules->refusals(document: ['legal_hold' => 'bezwaarprocedure 2026-114']);

		$this->assertCount(1, $refusals);
		$this->assertSame(SubjectErasureRules::LEGAL_HOLD, $refusals[0]['obligation']);
		$this->assertStringContainsString('bezwaarprocedure 2026-114', $refusals[0]['reason']);
		$this->assertStringContainsString('legal department', $refusals[0]['decidedBy']);
	}//end testALegalHoldRefusesAndNamesWhoDecides()

	/**
	 * Each obligation kind refuses and is named, so none is silently skipped.
	 *
	 * @return void
	 */
	public function testEveryObligationKindRefusesAndIsNamed(): void {
		foreach (SubjectErasureRules::OBLIGATIONS as $obligation) {
			$refusals = $this->rules->refusals(document: [$obligation => 'a reason']);

			$this->assertCount(1, $refusals, $obligation);
			$this->assertNotSame('', trim($refusals[0]['reason']), $obligation);
			$this->assertArrayHasKey($obligation, SubjectErasureRules::DECIDES, $obligation);
		}
	}//end testEveryObligationKindRefusesAndIsNamed()

	/**
	 * Every obligation is reported, not the first. A requester told about a
	 * hold, who waits for it to lift and then meets a retention term, has been
	 * answered twice and helped once.
	 *
	 * @return void
	 */
	public function testEveryObligationIsReportedNotOnlyTheFirst(): void {
		$refusals = $this->rules->refusals(
			document: ['legal_hold' => 'a hold', 'retention' => 'P10Y', 'publication_prohibition' => 'a ban']
		);

		$this->assertCount(3, $refusals);
	}//end testEveryObligationIsReportedNotOnlyTheFirst()

	/**
	 * A document with no obligation is refused nothing, so the rules are not a
	 * blanket that denies every erasure.
	 *
	 * @return void
	 */
	public function testAnUnencumberedDocumentIsNotRefused(): void {
		$this->assertSame([], $this->rules->refusals(document: ['id' => 'doc-1']));
		$this->assertSame([], $this->rules->refusals(document: ['legal_hold' => false, 'retention' => null]));
	}//end testAnUnencumberedDocumentIsNotRefused()

	/**
	 * 🔴 ONE HELD DOCUMENT DOES NOT DENY THE REQUEST. This is the divergence
	 * from the platform, which refuses the whole act under a hold.
	 *
	 * @return void
	 */
	public function testOneHeldDocumentDoesNotDenyTheWholeRequest(): void {
		$screened = $this->rules->screen(
			documents: [
				['id' => 'doc-1'],
				['id' => 'doc-2', 'legal_hold' => 'bezwaar 2026-114'],
				['id' => 'doc-3'],
			]
		);

		$this->assertSame(['doc-1', 'doc-3'], $screened['erase']);
		$this->assertCount(1, $screened['refused']);
		$this->assertSame('doc-2', $screened['refused'][0]['document']);
	}//end testOneHeldDocumentDoesNotDenyTheWholeRequest()

	/**
	 * 🔴 THE CERTIFICATE CARRIES THE REFUSALS AS PROMINENTLY AS THE ERASURES.
	 * One that lists only what was erased reads as a completed request, and the
	 * refused documents are exactly what the requester has to be told about.
	 *
	 * @return void
	 */
	public function testTheCertificateCarriesTheRefusals(): void {
		$certificate = $this->rules->certificate(
			request: ['subject' => 'Fatima El-Amrani', 'ground' => 'AVG 17', 'actor' => 'jdevries'],
			erased: [['document' => 'doc-1', 'occurrences' => 4]],
			refused: [['document' => 'doc-2', 'obligations' => [['obligation' => 'legal_hold']]]]
		);

		$this->assertSame(1, $certificate['erasedCount']);
		$this->assertSame(1, $certificate['refusedCount']);
		$this->assertFalse($certificate['complete']);
	}//end testTheCertificateCarriesTheRefusals()

	/**
	 * 🔴 AND A PUBLISHED COPY IS NAMED, BECAUSE IT IS THE REQUESTER'S NEXT
	 * PROBLEM. An erasure that does not say a copy is already out there leaves
	 * them believing it is done.
	 *
	 * @return void
	 */
	public function testAnAlreadyPublishedCopyStopsTheCertificateBeingComplete(): void {
		$certificate = $this->rules->certificate(
			request: ['subject' => 'Fatima El-Amrani'],
			erased: [['document' => 'doc-1', 'occurrences' => 2]],
			refused: [],
			republish: ['woo-publicatie-2026-41']
		);

		$this->assertFalse($certificate['complete']);
		$this->assertSame(['woo-publicatie-2026-41'], $certificate['needsRepublishing']);
	}//end testAnAlreadyPublishedCopyStopsTheCertificateBeingComplete()

	/**
	 * A wholly granted request certifies as complete, so `complete` means
	 * something rather than never being true.
	 *
	 * @return void
	 */
	public function testAWhollyGrantedRequestIsComplete(): void {
		$certificate = $this->rules->certificate(
			request: ['subject' => 'Fatima El-Amrani'],
			erased: [['document' => 'doc-1', 'occurrences' => 2]],
			refused: []
		);

		$this->assertTrue($certificate['complete']);
	}//end testAWhollyGrantedRequestIsComplete()
}//end class

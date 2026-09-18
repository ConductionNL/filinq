<?php

/**
 * Tests for the irreversibility verification.
 *
 * 🔴 THE COMMONEST AND MOST SERIOUS WOO FAILURE IS A BLACK RECTANGLE DRAWN OVER
 * LIVE TEXT. The document looks redacted to the person who made it and to
 * everyone who approves it, because the only surface any of them look at is the
 * rendered page. The text is still there, selectable and copyable, and the name
 * is published.
 *
 * 🔴 AND FILINQ DOES NOT PRODUCE THESE BYTES. The redaction is OpenRegister's
 * anonymisation backend driving the anonymiq ExApp; filinq asks, waits and
 * stores the link. So filinq cannot promise irreversibility, it can only CHECK
 * it, and these tests are about the difference. An archivist told a document is
 * safe stops looking at it, so a confident claim is worse than no claim.
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

use OCA\Filinq\Service\Redaction\RedactionIrreversibilityVerifier;
use OCA\Filinq\Service\Redaction\RedactionLeakRoute;
use OCA\Filinq\Service\Redaction\RedactionOutputModes;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RedactionIrreversibilityVerifier.
 */
class RedactionIrreversibilityVerifierTest extends TestCase {

	private RedactionIrreversibilityVerifier $verifier;

	/**
	 * Wire the verifier.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->verifier = new RedactionIrreversibilityVerifier();
	}//end setUp()

	/**
	 * A PDF with the name gone from every route.
	 *
	 * @return string The bytes.
	 */
	private function cleanPdf(): string {
		return "%PDF-1.7\n1 0 obj\n<< /Type /Page >>\nstream\nBT (geanonimiseerd) Tj ET\nendstream\nendobj\n"
			."<x:xmpmeta><dc:creator>gemeente</dc:creator></x:xmpmeta>\ntrailer\n%%EOF\n";
	}//end cleanPdf()

	/**
	 * 🔴 THE BLACK RECTANGLE. The mark is drawn and the text is still under it.
	 *
	 * @return void
	 */
	public function testTextLeftUnderTheMarkIsFound(): void {
		$bytes = "%PDF-1.7\n1 0 obj\nstream\n0 0 0 rg 100 100 200 20 re f\nBT (Fatima El-Amrani) Tj ET\nendstream\nendobj\ntrailer\n%%EOF\n";

		$result = $this->verifier->verify(bytes: $bytes, redactedValues: ['Fatima El-Amrani'], outputMode: 'pdf');

		$this->assertSame(RedactionIrreversibilityVerifier::LEAKING, $result['verdict']);
		$this->assertFalse($this->verifier->mayBePublished(result: $result));
		$this->assertSame(RedactionLeakRoute::TEXT_UNDER_MARK, $result['findings'][0]['route']);
	}//end testTextLeftUnderTheMarkIsFound()

	/**
	 * The finding is described in words an archivist can act on, not by a
	 * route name only a developer recognises.
	 *
	 * @return void
	 */
	public function testAFindingIsDescribedInWords(): void {
		$bytes = "%PDF-1.7\nBT (Fatima El-Amrani) Tj ET\ntrailer\n%%EOF\n";

		$result = $this->verifier->verify(bytes: $bytes, redactedValues: ['Fatima El-Amrani']);

		$this->assertStringContainsString('still in the file', $result['findings'][0]['description']);
	}//end testAFindingIsDescribedInWords()

	/**
	 * An earlier revision left by an incremental save still holds everything.
	 *
	 * @return void
	 */
	public function testAnEarlierRevisionIsFound(): void {
		$bytes = "%PDF-1.7\nBT (Fatima El-Amrani) Tj ET\ntrailer\n%%EOF\n"
			."1 0 obj\nBT (geanonimiseerd) Tj ET\nendobj\ntrailer\n%%EOF\n";

		$result = $this->verifier->verify(bytes: $bytes, redactedValues: ['Fatima El-Amrani']);

		$routes = array_column($result['findings'], 'route');
		$this->assertContains(RedactionLeakRoute::INCREMENTAL_UPDATE, $routes);
	}//end testAnEarlierRevisionIsFound()

	/**
	 * XMP keeps the author's name long after the page stops showing it.
	 *
	 * @return void
	 */
	public function testTheNameLeftInXmpIsFound(): void {
		$bytes = "%PDF-1.7\nBT (geanonimiseerd) Tj ET\n"
			."<x:xmpmeta><dc:creator>Fatima El-Amrani</dc:creator></x:xmpmeta>\ntrailer\n%%EOF\n";

		$result = $this->verifier->verify(bytes: $bytes, redactedValues: ['Fatima El-Amrani']);

		$routes = array_column($result['findings'], 'route');
		$this->assertContains(RedactionLeakRoute::XMP, $routes);
	}//end testTheNameLeftInXmpIsFound()

	/**
	 * An annotation's contents survive a mark drawn over the page.
	 *
	 * @return void
	 */
	public function testAnAnnotationHoldingTheValueIsFound(): void {
		$bytes = "%PDF-1.7\nBT (geanonimiseerd) Tj ET\n"
			."2 0 obj\n/Annots [ << /Contents (Fatima El-Amrani) >> ]\nendobj\ntrailer\n%%EOF\n";

		$result = $this->verifier->verify(bytes: $bytes, redactedValues: ['Fatima El-Amrani']);

		$routes = array_column($result['findings'], 'route');
		$this->assertContains(RedactionLeakRoute::ANNOTATIONS_AND_FIELDS, $routes);
	}//end testAnAnnotationHoldingTheValueIsFound()

	/**
	 * A thumbnail drawn before the mark was applied shows the original page.
	 *
	 * @return void
	 */
	public function testAPreviewStreamHoldingTheValueIsFound(): void {
		$bytes = "%PDF-1.7\nBT (geanonimiseerd) Tj ET\n"
			."3 0 obj\n/Thumb << (Fatima El-Amrani) >>\nendobj\ntrailer\n%%EOF\n";

		$result = $this->verifier->verify(bytes: $bytes, redactedValues: ['Fatima El-Amrani']);

		$routes = array_column($result['findings'], 'route');
		$this->assertContains(RedactionLeakRoute::EMBEDDED_PREVIEW, $routes);
	}//end testAPreviewStreamHoldingTheValueIsFound()

	/**
	 * A spreadsheet attached inside the document nobody opened.
	 *
	 * @return void
	 */
	public function testAnEmbeddedAttachmentHoldingTheValueIsFound(): void {
		$bytes = "%PDF-1.7\nBT (geanonimiseerd) Tj ET\n"
			."4 0 obj\n/EmbeddedFile << (Fatima El-Amrani) >>\nendobj\ntrailer\n%%EOF\n";

		$result = $this->verifier->verify(bytes: $bytes, redactedValues: ['Fatima El-Amrani']);

		$routes = array_column($result['findings'], 'route');
		$this->assertContains(RedactionLeakRoute::EMBEDDED_ATTACHMENT, $routes);
	}//end testAnEmbeddedAttachmentHoldingTheValueIsFound()

	/**
	 * A genuinely clean document passes, so the verifier is not a blanket that
	 * refuses everything and gets switched off in a week.
	 *
	 * @return void
	 */
	public function testACleanDocumentPasses(): void {
		$result = $this->verifier->verify(
			bytes: $this->cleanPdf(),
			redactedValues: ['Fatima El-Amrani'],
			outputMode: 'pdf'
		);

		$this->assertSame(RedactionIrreversibilityVerifier::CLEAN, $result['verdict']);
		$this->assertTrue($this->verifier->mayBePublished(result: $result));
		$this->assertSame([], $result['findings']);
	}//end testACleanDocumentPasses()

	/**
	 * 🔴 EVERY ROUTE IS EXAMINED, AND THE RESULT SAYS WHICH. A verifier that
	 * checked six of seven returns exactly what a clean document returns.
	 *
	 * @return void
	 */
	public function testEveryRouteIsExaminedAndNamed(): void {
		$result = $this->verifier->verify(bytes: $this->cleanPdf(), redactedValues: ['Fatima El-Amrani']);

		$this->assertSame(RedactionLeakRoute::ALL, $result['routesChecked']);
		$this->assertCount(7, $result['routesChecked']);
	}//end testEveryRouteIsExaminedAndNamed()

	/**
	 * 🔴 AN UNREADABLE FILE IS NOT A CLEAN FILE. "We could not look" and "we
	 * looked and found nothing" are different answers and only one of them
	 * means the document may be published.
	 *
	 * @return void
	 */
	public function testAnEmptyFileIsUnverifiableRatherThanClean(): void {
		$result = $this->verifier->verify(bytes: '   ', redactedValues: ['Fatima El-Amrani']);

		$this->assertSame(RedactionIrreversibilityVerifier::UNVERIFIABLE, $result['verdict']);
		$this->assertFalse($this->verifier->mayBePublished(result: $result));
		$this->assertStringContainsString('nothing could be examined', $result['reason']);
	}//end testAnEmptyFileIsUnverifiableRatherThanClean()

	/**
	 * 🔴 VERIFYING AGAINST NOTHING WOULD PASS EVERY DOCUMENT, including one
	 * where the redaction never ran at all.
	 *
	 * @return void
	 */
	public function testNoRedactedValuesIsUnverifiableRatherThanClean(): void {
		$result = $this->verifier->verify(bytes: $this->cleanPdf(), redactedValues: []);

		$this->assertSame(RedactionIrreversibilityVerifier::UNVERIFIABLE, $result['verdict']);
		$this->assertFalse($this->verifier->mayBePublished(result: $result));
	}//end testNoRedactedValuesIsUnverifiableRatherThanClean()

	/**
	 * A value too short to be distinctive is not searched for: a verification
	 * that always fails is switched off within a week.
	 *
	 * @return void
	 */
	public function testATrivallyShortValueIsNotSearchedFor(): void {
		$result = $this->verifier->verify(bytes: $this->cleanPdf(), redactedValues: ['ab']);

		$this->assertSame(RedactionIrreversibilityVerifier::UNVERIFIABLE, $result['verdict']);
	}//end testATrivallyShortValueIsNotSearchedFor()

	/**
	 * 🔴 `mayBePublished` REFUSES BOTH FAILING STATES. A caller comparing
	 * against `leaking` alone publishes every document the verifier could not
	 * read, which is the larger set.
	 *
	 * @return void
	 */
	public function testMayBePublishedRefusesUnverifiableAsWellAsLeaking(): void {
		$this->assertFalse($this->verifier->mayBePublished(result: ['verdict' => RedactionIrreversibilityVerifier::LEAKING]));
		$this->assertFalse($this->verifier->mayBePublished(result: ['verdict' => RedactionIrreversibilityVerifier::UNVERIFIABLE]));
		$this->assertFalse($this->verifier->mayBePublished(result: []));
	}//end testMayBePublishedRefusesUnverifiableAsWellAsLeaking()

	/**
	 * The match is case-insensitive: a name re-cased by an extractor is the
	 * same name.
	 *
	 * @return void
	 */
	public function testTheMatchIgnoresCase(): void {
		$bytes = "%PDF-1.7\nBT (FATIMA EL-AMRANI) Tj ET\ntrailer\n%%EOF\n";

		$result = $this->verifier->verify(bytes: $bytes, redactedValues: ['Fatima El-Amrani']);

		$this->assertSame(RedactionIrreversibilityVerifier::LEAKING, $result['verdict']);
	}//end testTheMatchIgnoresCase()

	/**
	 * 🔴 EVERY OUTPUT MODE IS VERIFIED, AND THIS IS THE TEST THAT NOTICES WHEN
	 * ONE IS NOT. Add PDF/A next quarter for an archival hand-off, wire the
	 * conversion, forget the verification, and that mode writes redacted copies
	 * nobody checks. Nothing else fails: the other modes stay green and the new
	 * one is silent, which reads exactly like working.
	 *
	 * @return void
	 */
	public function testEveryOutputModeIsVerified(): void {
		$this->assertSame(
			[],
			RedactionOutputModes::unverified(),
			'an output mode that writes a redacted copy with no verification entry must fail the suite'
		);
	}//end testEveryOutputModeIsVerified()

	/**
	 * A mode nobody declared is not verified. The safe reading of "I have not
	 * heard of this mode" is not "it is fine".
	 *
	 * @return void
	 */
	public function testAnUndeclaredModeIsNotTreatedAsVerified(): void {
		$this->assertFalse(RedactionOutputModes::isVerified(mode: 'pdf/x-4'));
		$this->assertFalse(RedactionOutputModes::isVerified(mode: ''));
		$this->assertTrue(RedactionOutputModes::isVerified(mode: RedactionOutputModes::PDF_A));
	}//end testAnUndeclaredModeIsNotTreatedAsVerified()

	/**
	 * Every route in the vocabulary has a description, so a finding can always
	 * be explained rather than printed as an identifier.
	 *
	 * @return void
	 */
	public function testEveryRouteHasWordsForIt(): void {
		foreach (RedactionLeakRoute::ALL as $route) {
			$this->assertArrayHasKey($route, RedactionLeakRoute::DESCRIPTIONS);
			$this->assertNotSame('', RedactionLeakRoute::DESCRIPTIONS[$route]);
		}
	}//end testEveryRouteHasWordsForIt()
}//end class

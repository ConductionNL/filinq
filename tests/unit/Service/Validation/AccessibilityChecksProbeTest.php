<?php

/**
 * The PDF accessibility probes in isolation: which markers each probe recognises and which findings fire under which severities.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Validation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Validation;


use OCA\Filinq\Service\Validation\AccessibilityChecks;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\Validation\AccessibilityChecks
 */
class AccessibilityChecksProbeTest extends TestCase {

	/**
	 * A tagged PDF with language, title and PDF/UA identifier.
	 */
	private const ACCESSIBLE = "%PDF-1.7\n<< /Type /Catalog /StructTreeRoot 5 0 R /MarkInfo << /Marked true >> /Lang (nl-NL) >>\n"
		. "<< /Title (Besluit) >>\n<pdfuaid:part>1</pdfuaid:part>";

	/**
	 * The four check ids, in the order the checks run.
	 */
	private const CHECKS = ['pdf-not-tagged', 'pdf-language-missing', 'pdf-title-missing', 'pdfua-identifier-missing'];

	/**
	 * The check ids of a findings list.
	 *
	 * @param array<int, array<string, mixed>> $findings The findings.
	 *
	 * @return list<string>
	 */
	private static function ids(array $findings): array {
		return array_column($findings, 'checkId');
	}//end ids()

	public function testOnlyANonEmptyPdfStringIsChecked(): void {
		$checks = new AccessibilityChecks();

		$this->assertSame([], $checks->findings(profile: [], mime: 'text/plain', content: 'plain text'));
		$this->assertSame([], $checks->findings(profile: [], mime: 'application/pdf', content: ''));
		$this->assertSame([], $checks->findings(profile: [], mime: 'application/pdf', content: null));
	}//end testOnlyANonEmptyPdfStringIsChecked()

	public function testAnAccessiblePdfHasNoFindings(): void {
		$this->assertSame([], (new AccessibilityChecks())->findings(profile: [], mime: 'application/pdf', content: self::ACCESSIBLE));
	}//end testAnAccessiblePdfHasNoFindings()

	public function testABarePdfFailsAllButThePdfUaCheckAsWarnings(): void {
		$findings = (new AccessibilityChecks())->findings(profile: [], mime: 'application/pdf', content: '%PDF-1.4 nothing');

		$this->assertSame(['pdf-not-tagged', 'pdf-language-missing', 'pdf-title-missing'], self::ids($findings));
		$this->assertSame(
			[
				'checkId' => 'pdf-not-tagged',
				'category' => 'accessibility',
				'severity' => 'warning',
				'message' => 'The PDF has no tags, so a screen reader cannot follow its structure.',
				'params' => [],
			],
			$findings[0]
		);
	}//end testABarePdfFailsAllButThePdfUaCheckAsWarnings()

	public function testATaggedPdfWithoutIdentifierFailsOnlyThePdfUaCheck(): void {
		$content  = str_replace('<pdfuaid:part>1</pdfuaid:part>', '', self::ACCESSIBLE);
		$findings = (new AccessibilityChecks())->findings(profile: [], mime: 'application/pdf', content: $content);

		$this->assertSame(['pdfua-identifier-missing'], self::ids($findings));
		$this->assertSame('The PDF does not say it follows PDF/UA.', $findings[0]['message']);
	}//end testATaggedPdfWithoutIdentifierFailsOnlyThePdfUaCheck()

	public function testTheProfileSetsSeverityAndOffSuppresses(): void {
		$profile  = ['severities' => ['pdf-not-tagged' => 'blocking', 'pdf-language-missing' => 'off']];
		$findings = (new AccessibilityChecks())->findings(profile: $profile, mime: 'application/pdf', content: '%PDF-1.4');

		$this->assertSame(['pdf-not-tagged', 'pdf-title-missing'], self::ids($findings));
		$this->assertSame(['blocking', 'warning'], array_column($findings, 'severity'));
	}//end testTheProfileSetsSeverityAndOffSuppresses()

	public function testTaggingNeedsBothTheStructTreeAndTheMarkedFlag(): void {
		$checks = new AccessibilityChecks();

		$this->assertTrue($checks->isTagged(bytes: '/StructTreeRoot 1 0 R /MarkInfo<</Marked true>>'));
		$this->assertFalse($checks->isTagged(bytes: '/StructTreeRoot 1 0 R'));
		$this->assertFalse($checks->isTagged(bytes: '/MarkInfo << /Marked true >>'));
		$this->assertFalse($checks->isTagged(bytes: '/StructTreeRoot 1 0 R /MarkInfo << /Marked false >>'));
	}//end testTaggingNeedsBothTheStructTreeAndTheMarkedFlag()

	public function testLanguageAcceptsALiteralOrHexValueButNotAnEmptyOne(): void {
		$checks = new AccessibilityChecks();

		$this->assertTrue($checks->hasLanguage(bytes: '/Lang (en-GB)'));
		$this->assertTrue($checks->hasLanguage(bytes: '/Lang<FEFF006E006C>'));
		$this->assertFalse($checks->hasLanguage(bytes: '/Lang ()'));
		$this->assertFalse($checks->hasLanguage(bytes: '/Lang <>'));
		$this->assertFalse($checks->hasLanguage(bytes: 'no language'));
	}//end testLanguageAcceptsALiteralOrHexValueButNotAnEmptyOne()

	public function testTitleComesFromXmpOrANonEmptyInfoTitle(): void {
		$checks = new AccessibilityChecks();

		$this->assertTrue($checks->hasTitle(bytes: '<dc:title><rdf:Alt><rdf:li xml:lang="x-default">Besluit</rdf:li></rdf:Alt></dc:title>'));
		$this->assertFalse($checks->hasTitle(bytes: '<dc:title><rdf:Alt><rdf:li xml:lang="x-default">  </rdf:li></rdf:Alt></dc:title>'));
		$this->assertTrue($checks->hasTitle(bytes: '/Title (Besluit)'));
		$this->assertFalse($checks->hasTitle(bytes: '/Title ()'));
		$this->assertTrue($checks->hasTitle(bytes: '/Title <FEFF0042>'));
		$this->assertFalse($checks->hasTitle(bytes: '/Title <FEFF>'));
		$this->assertFalse($checks->hasTitle(bytes: '/Title <>'));
	}//end testTitleComesFromXmpOrANonEmptyInfoTitle()

	public function testPdfUaIdentifierAsElementOrAttribute(): void {
		$checks = new AccessibilityChecks();

		$this->assertTrue($checks->hasPdfUaIdentifier(bytes: '<pdfuaid:part>1</pdfuaid:part>'));
		$this->assertTrue($checks->hasPdfUaIdentifier(bytes: 'pdfuaid:part="2"'));
		$this->assertTrue($checks->hasPdfUaIdentifier(bytes: "pdfuaid:part = '1'"));
		$this->assertFalse($checks->hasPdfUaIdentifier(bytes: '<pdfuaid:part></pdfuaid:part>'));
		$this->assertFalse($checks->hasPdfUaIdentifier(bytes: 'pdfaid:part="3"'));
	}//end testPdfUaIdentifierAsElementOrAttribute()
}//end class

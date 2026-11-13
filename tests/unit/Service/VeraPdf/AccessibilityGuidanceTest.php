<?php

/**
 * Remediation guidance for a PDF/UA-1 verdict.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use OCA\Filinq\Service\VeraPdf\AccessibilityGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\VeraPdf\AccessibilityGuidance
 * @uses   \OCA\Filinq\Service\VeraPdf\ConformanceGuidance
 */
class AccessibilityGuidanceTest extends TestCase {

	private const FAILED = ['compliant' => false, 'fontsInImportedPages' => false];

	private const FILINQ_TAGGED = "%PDF-1.7\n<< /Creator (Filinq 1.0) >>\n<< /Type /Catalog /StructTreeRoot 4 0 R >>";

	private const FILINQ_UNTAGGED = "%PDF-1.7\n<< /Creator (Filinq 1.0) /Producer (mPDF 8.2) >>";

	private const IMPORTED_TAGGED = "%PDF-1.7\n<< /Creator (Word) >>\n<< /StructTreeRoot 4 0 R >>";

	private function guidance(): AccessibilityGuidance {
		return new AccessibilityGuidance(new ConformanceGuidance());

	}//end guidance()

	public function testAConformantDocumentNeedsNothing(): void {
		$this->assertSame(AccessibilityGuidance::NONE, $this->guidance()->for(verdict: ['compliant' => true], bytes: self::IMPORTED_TAGGED));

	}//end testAConformantDocumentNeedsNothing()

	public function testATaggedDocumentFilinqWroteIsRegenerated(): void {
		$this->assertSame(AccessibilityGuidance::REGENERATE_ACCESSIBLE, $this->guidance()->for(verdict: self::FAILED, bytes: self::FILINQ_TAGGED));

	}//end testATaggedDocumentFilinqWroteIsRegenerated()

	public function testAnImportedDocumentIsReauthoredEvenWhenTagged(): void {
		$this->assertSame(AccessibilityGuidance::REAUTHOR_FROM_SOURCE, $this->guidance()->for(verdict: self::FAILED, bytes: self::IMPORTED_TAGGED));

	}//end testAnImportedDocumentIsReauthoredEvenWhenTagged()

	public function testFilinqsUntaggedOutputIsReauthoredNotRegenerated(): void {
		$this->assertSame(AccessibilityGuidance::REAUTHOR_FROM_SOURCE, $this->guidance()->for(verdict: self::FAILED, bytes: self::FILINQ_UNTAGGED));

	}//end testFilinqsUntaggedOutputIsReauthoredNotRegenerated()

	public function testPagesImportedIntoAFilinqDocumentAreReauthored(): void {
		$verdict = ['compliant' => false, 'fontsInImportedPages' => true];

		$this->assertSame(AccessibilityGuidance::REAUTHOR_FROM_SOURCE, $this->guidance()->for(verdict: $verdict, bytes: self::FILINQ_TAGGED));

	}//end testPagesImportedIntoAFilinqDocumentAreReauthored()
}//end class

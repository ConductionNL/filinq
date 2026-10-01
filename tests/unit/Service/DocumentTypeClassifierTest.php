<?php

/**
 * DocumentTypeClassifier: the intake-type vocabulary and its threshold
 *
 * The first red test of inbound-auto-classification: the vocabulary owns
 * the intake types, an ambiguous text gets overig, and LanguageClassifier
 * keeps its own constants.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentTypeClassifier;
use OCA\Filinq\Service\LanguageClassifier;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Intake types, confidence and the honest fallback.
 */
class DocumentTypeClassifierTest extends TestCase {

	/**
	 * An invoice with its structural cues is typed factuur with a confidence above the threshold.
	 *
	 * @return void
	 */
	public function testAnInvoiceIsTypedWithConfidence(): void {
		$text = "Factuur\nFactuurnummer: 2026-0412\nFactuurdatum: 12 september 2026\n"
			. "Te betalen binnen 30 dagen op IBAN NL91ABNA0417164300 onder vermelding van het factuurnummer.\n"
			. "Bedrag excl. btw: 1.250,00\nBtw 21%: 262,50";

		$result = (new DocumentTypeClassifier())->classify(text: $text);

		$this->assertSame('factuur', $result['type']);
		$this->assertGreaterThan(DocumentTypeClassifier::MIN_CONFIDENCE, $result['confidence']);
		$this->assertLessThanOrEqual(1.0, $result['confidence']);

	}//end testAnInvoiceIsTypedWithConfidence()

	/**
	 * A decision is typed besluit.
	 *
	 * @return void
	 */
	public function testADecisionIsTypedBesluit(): void {
		$text = "Besluit\nOverwegende dat de aanvraag op 1 juli 2026 is ontvangen,\n"
			. "besluiten burgemeester en wethouders de vergunning te verlenen.\n"
			. "Bezwaar: binnen zes weken na de dag van verzending kunt u bezwaar maken.";

		$this->assertSame('besluit', (new DocumentTypeClassifier())->classify(text: $text)['type']);

	}//end testADecisionIsTypedBesluit()

	/**
	 * Text without any cue falls back to overig with a low confidence, never a confident guess.
	 *
	 * @return void
	 */
	public function testAnAmbiguousDocumentFallsBackToOverig(): void {
		$result = (new DocumentTypeClassifier())->classify(text: 'Hierbij de gevraagde stukken. Met vriendelijke groet.');

		$this->assertSame('overig', $result['type']);
		$this->assertLessThan(DocumentTypeClassifier::MIN_CONFIDENCE, $result['confidence']);

	}//end testAnAmbiguousDocumentFallsBackToOverig()

	/**
	 * Every type the classifier can answer is a value of the classificationResult enum.
	 *
	 * @return void
	 */
	public function testEveryTypeIsInTheSchemaEnum(): void {
		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);
		$enum     = $register['components']['schemas']['classificationResult']['properties']['suggestedDocumentType']['enum'];

		$this->assertSame(['brief', 'besluit', 'factuur', 'rapport', 'contract', 'formulier', 'overig'], $enum);
		foreach (array_keys(DocumentTypeClassifier::TYPE_KEYWORDS) as $type) {
			$this->assertContains($type, $enum);
		}

	}//end testEveryTypeIsInTheSchemaEnum()

	/**
	 * The language classifier keeps its own vocabularies (REQ-META-11 boundary).
	 *
	 * @return void
	 */
	public function testLanguageClassifierIsUntouched(): void {
		$constants = (new ReflectionClass(LanguageClassifier::class))->getConstants();
		$this->assertArrayNotHasKey('TYPE_KEYWORDS', $constants);

	}//end testLanguageClassifierIsUntouched()
}//end class

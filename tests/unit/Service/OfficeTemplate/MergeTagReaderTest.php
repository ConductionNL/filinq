<?php

/**
 * The merge tags of a DOCX are read with PhpWord, each once and without block
 * closings, and its text is read one line per paragraph.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

use OCA\Filinq\Exception\OfficeSourceRefusedException;
use OCA\Filinq\Service\OfficeTemplate\MergeTagReader;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/OfficeFixtures.php';

/**
 * @covers \OCA\Filinq\Service\OfficeTemplate\MergeTagReader
 * @uses   \OCA\Filinq\Exception\OfficeSourceRefusedException
 */
class MergeTagReaderTest extends TestCase {

	/**
	 * Tags come back once each, in order, dotted paths and fragments included,
	 * and a block's closing tag is not a field.
	 *
	 * @return void
	 */
	public function testTheTagsAreReadOnceEachWithoutBlockClosings(): void {
		$docx = OfficeFixtures::docx(paragraphs: [
			'Geachte ${aanvrager.naam},',
			'${besluit.datum} en nogmaals ${aanvrager.naam}',
			'${regels}',
			'${regel.omschrijving}',
			'${/regels}',
			'${fragment:ondertekening-burgemeester}',
		]);

		$this->assertSame(
			['aanvrager.naam', 'besluit.datum', 'regels', 'regel.omschrijving', 'fragment:ondertekening-burgemeester'],
			(new MergeTagReader())->tags(docx: $docx)
		);
	}//end testTheTagsAreReadOnceEachWithoutBlockClosings()

	/**
	 * A document without tags has none.
	 *
	 * @return void
	 */
	public function testADocumentWithoutTagsHasNone(): void {
		$this->assertSame([], (new MergeTagReader())->tags(docx: OfficeFixtures::docx(paragraphs: ['Geen velden hier.'])));
	}//end testADocumentWithoutTagsHasNone()

	/**
	 * Bytes that are not a DOCX are refused as the wrong type.
	 *
	 * @return void
	 */
	public function testBytesThatAreNotADocxAreRefused(): void {
		try {
			(new MergeTagReader())->tags(docx: 'not a docx');
			$this->fail('Expected a refusal.');
		} catch (OfficeSourceRefusedException $e) {
			$this->assertSame(OfficeSourceRefusedException::REASON_TYPE, $e->getReason());
		}
	}//end testBytesThatAreNotADocxAreRefused()

	/**
	 * The text is one line per non-empty paragraph, entities decoded.
	 *
	 * @return void
	 */
	public function testTheTextIsOneLinePerParagraph(): void {
		$docx = OfficeFixtures::docx(paragraphs: ['Geachte ${aanvrager.naam},', '', 'Kosten & baten']);

		$this->assertSame("Geachte \${aanvrager.naam},\nKosten & baten", (new MergeTagReader())->text(docx: $docx));
		$this->assertSame('', (new MergeTagReader())->text(docx: 'not a docx'));
	}//end testTheTextIsOneLinePerParagraph()
}//end class

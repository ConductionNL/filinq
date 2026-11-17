<?php

/**
 * An office template is filled from data: fields, aliases, blocks, table rows
 * and text fragments, with every value escaped and every gap named.
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

use Exception;
use OCA\Filinq\Service\OfficeTemplate\MergeTagReader;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateFiller;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\TestCase;
use ZipArchive;

require_once __DIR__.'/OfficeFixtures.php';

/**
 * @covers \OCA\Filinq\Service\OfficeTemplate\OfficeTemplateFiller
 * @uses   \OCA\Filinq\Service\OfficeTemplate\MergeTagReader
 * @uses   \OCA\Filinq\Service\OfficeTemplate\OfficeFillRun
 */
class OfficeTemplateFillerTest extends TestCase {

	/**
	 * Fields fill from dotted paths, an alias reads another path, and a
	 * missing value is left empty and named in the warnings.
	 *
	 * @return void
	 */
	public function testFieldsFillAndAGapIsNamed(): void {
		$docx = OfficeFixtures::docx(paragraphs: ['Geachte ${aanvrager.naam},', 'Datum: ${besluit.datum}', 'Kort: ${naam_aanvrager}', 'Akkoord: ${akkoord}']);

		$result = (new OfficeTemplateFiller())->fill(
			docx: $docx,
			data: ['aanvrager' => ['naam' => 'J. de Vries'], 'akkoord' => true],
			fragments: [],
			fieldMap: ['naam_aanvrager' => 'aanvrager.naam']
		);

		$this->assertSame("Geachte J. de Vries,\nDatum:\nKort: J. de Vries\nAkkoord: ja", (new MergeTagReader())->text(docx: $result['docx']));
		$this->assertSame(['No value for merge tag "besluit.datum"; it is left empty.'], $result['warnings']);
		$this->assertSame([], $result['fragments']);
	}//end testFieldsFillAndAGapIsNamed()

	/**
	 * A value cannot add markup to the document: it is written escaped.
	 *
	 * @return void
	 */
	public function testAValueIsWrittenEscaped(): void {
		$docx = OfficeFixtures::docx(paragraphs: ['${naam}']);

		$result = (new OfficeTemplateFiller())->fill(docx: $docx, data: ['naam' => '</w:t><w:b/>&"x"']);

		$this->assertStringContainsString('&lt;/w:t&gt;&lt;w:b/&gt;&amp;&quot;x&quot;', $this->documentXml(docx: $result['docx']));
		$this->assertSame('</w:t><w:b/>&"x"', (new MergeTagReader())->text(docx: $result['docx']));
	}//end testAValueIsWrittenEscaped()

	/**
	 * A fragment's text is inserted with its own tags filled; a missing one
	 * shows a marker and a warning; a fragment inside a fragment is left out.
	 *
	 * @return void
	 */
	public function testFragmentsAreFilledAndAMissingOneIsMarked(): void {
		$docx = OfficeFixtures::docx(paragraphs: ['${fragment:ondertekening}', '${fragment:bestaat-niet}']);

		$result = (new OfficeTemplateFiller())->fill(
			docx: $docx,
			data: ['burgemeester' => 'A. Jansen'],
			fragments: ['ondertekening' => 'Hoogachtend, ${burgemeester}${fragment:ander}']
		);

		$this->assertSame("Hoogachtend, A. Jansen\n[ontbrekende bouwsteen: bestaat-niet]", (new MergeTagReader())->text(docx: $result['docx']));
		$this->assertSame(['ondertekening'], $result['fragments']);
		$this->assertSame(
			[
				'A text fragment cannot contain another fragment ("fragment:ander"); it is left out.',
				'Text fragment "bestaat-niet" does not exist; a marker is shown in its place.',
			],
			$result['warnings']
		);
	}//end testFragmentsAreFilledAndAMissingOneIsMarked()

	/**
	 * A block repeats once per item, reading the item's fields with or
	 * without the item's name, and an empty list removes it.
	 *
	 * @return void
	 */
	public function testABlockRepeatsPerItemAndAnEmptyListRemovesIt(): void {
		$docx = OfficeFixtures::docx(paragraphs: ['Regels:', '${regels}', '- ${regel.omschrijving} (${bedrag})', '${/regels}', '${bijlagen}', 'Bijlage', '${/bijlagen}', 'Einde']);

		$result = (new OfficeTemplateFiller())->fill(
			docx: $docx,
			data: ['regels' => [['omschrijving' => 'Leges', 'bedrag' => '12,50'], ['omschrijving' => 'Porto', 'bedrag' => '1,00']], 'bijlagen' => []]
		);

		$this->assertSame("Regels:\n- Leges (12,50)\n- Porto (1,00)\nEinde", (new MergeTagReader())->text(docx: $result['docx']));
		$this->assertSame([], $result['warnings']);
	}//end testABlockRepeatsPerItemAndAnEmptyListRemovesIt()

	/**
	 * A table row repeats once per record of the list its tags read.
	 *
	 * @return void
	 */
	public function testATableRowRepeatsPerRecord(): void {
		$word = new PhpWord();
		$table = $word->addSection()->addTable();
		$row = $table->addRow();
		$row->addCell(2000)->addText('${posten.naam}');
		$row->addCell(2000)->addText('${posten.prijs}');
		$path = (string) tempnam(sys_get_temp_dir(), 'filinq-test-');
		IOFactory::createWriter($word, 'Word2007')->save($path);
		$docx = (string) file_get_contents($path);
		unlink($path);

		$result = (new OfficeTemplateFiller())->fill(
			docx: $docx,
			data: ['posten' => [['naam' => 'A', 'prijs' => '1'], ['naam' => 'B', 'prijs' => '2'], ['naam' => 'C', 'prijs' => '3']]]
		);

		$this->assertSame("A\n1\nB\n2\nC\n3", (new MergeTagReader())->text(docx: $result['docx']));
		$this->assertSame(3, preg_match_all('#<w:tr[ >]#', $this->documentXml(docx: $result['docx'])));
	}//end testATableRowRepeatsPerRecord()

	/**
	 * Lists of scalars join; records and absent paths have no value.
	 *
	 * @return void
	 */
	public function testLookupReadsScalarsAndJoinsLists(): void {
		$filler = new OfficeTemplateFiller();
		$data = ['a' => ['b' => 3], 'tags' => ['x', 'y'], 'records' => [['n' => 1]], 'dotted.key' => 'kept', 'nee' => false];

		$this->assertSame('3', $filler->lookup(data: $data, path: 'a.b'));
		$this->assertSame('x, y', $filler->lookup(data: $data, path: 'tags'));
		$this->assertSame('kept', $filler->lookup(data: $data, path: 'dotted.key'));
		$this->assertSame('nee', $filler->lookup(data: $data, path: 'nee'));
		$this->assertNull($filler->lookup(data: $data, path: 'records'));
		$this->assertNull($filler->lookup(data: $data, path: 'a.c'));
	}//end testLookupReadsScalarsAndJoinsLists()

	/**
	 * The fragment slugs of a tag list come back once each.
	 *
	 * @return void
	 */
	public function testFragmentSlugsAreReadFromTheTags(): void {
		$this->assertSame(
			['ondertekening', 'kop'],
			(new OfficeTemplateFiller())->fragmentSlugs(tags: ['naam', 'fragment:ondertekening', 'fragment: kop', 'fragment:ondertekening', 'fragment:'])
		);
	}//end testFragmentSlugsAreReadFromTheTags()

	/**
	 * A file that is not a DOCX is refused with 422.
	 *
	 * @return void
	 */
	public function testAFileThatIsNoDocxIsRefused(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(422);

		(new OfficeTemplateFiller())->fill(docx: 'not a zip', data: []);
	}//end testAFileThatIsNoDocxIsRefused()

	/**
	 * The main document XML of a DOCX.
	 *
	 * @param string $docx The DOCX.
	 *
	 * @return string
	 */
	private function documentXml(string $docx): string {
		$path = (string) tempnam(sys_get_temp_dir(), 'filinq-test-');
		file_put_contents($path, $docx);
		$zip = new ZipArchive();
		$zip->open($path);
		$xml = (string) $zip->getFromName('word/document.xml');
		$zip->close();
		unlink($path);

		return $xml;
	}//end documentXml()
}//end class

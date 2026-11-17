<?php

/**
 * An office template renders from its stored source with its fragments, to
 * DOCX as-is, to PDF through the conversion cascade and to ODT or HTML
 * through LibreOffice; Twig templates get their fragments resolved first.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-templates-render-through-the-conversion-cascade-req-ddota-003
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

use Exception;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\OfficeTemplate\MergeTagReader;
use OCA\Filinq\Service\OfficeTemplate\OfficeSourceStore;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateFiller;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRenderer;
use OCA\Filinq\Service\OfficeTemplate\TextFragmentService;
use OCA\Filinq\Service\PdfConversionService;
use OCP\Files\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/OfficeFixtures.php';

/**
 * @covers \OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRenderer
 * @uses   \OCA\Filinq\Service\OfficeTemplate\OfficeTemplateFiller
 * @uses   \OCA\Filinq\Service\OfficeTemplate\OfficeFillRun
 * @uses   \OCA\Filinq\Service\OfficeTemplate\MergeTagReader
 */
class OfficeTemplateRendererTest extends TestCase {

	/**
	 * The stored sources.
	 *
	 * @var OfficeSourceStore&MockObject
	 */
	private OfficeSourceStore&MockObject $store;

	/**
	 * The fragments.
	 *
	 * @var TextFragmentService&MockObject
	 */
	private TextFragmentService&MockObject $fragments;

	/**
	 * The cascade.
	 *
	 * @var PdfConversionService&MockObject
	 */
	private PdfConversionService&MockObject $pdf;

	/**
	 * LibreOffice.
	 *
	 * @var LibreOfficeHeadlessBackend&MockObject
	 */
	private LibreOfficeHeadlessBackend&MockObject $libreOffice;

	/**
	 * Build the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->store = $this->createMock(OfficeSourceStore::class);
		$this->fragments = $this->createMock(TextFragmentService::class);
		$this->pdf = $this->createMock(PdfConversionService::class);
		$this->libreOffice = $this->createMock(LibreOfficeHeadlessBackend::class);
	}//end setUp()

	/**
	 * DOCX output is the filled source, with the namespace's fragments.
	 *
	 * @return void
	 */
	public function testDocxIsTheFilledSourceWithItsFragments(): void {
		$this->store->method('read')->with(42)->willReturn(OfficeFixtures::docx(paragraphs: ['Geachte ${aanvrager.naam},', '${fragment:groet}']));
		$this->fragments->expects($this->once())->method('texts')->with(['groet'], 'filinq')->willReturn(['groet' => 'Met vriendelijke groet']);
		$this->pdf->expects($this->never())->method('convertToPdf');

		$result = $this->renderer()->render(
			template: ['templateType' => 'office', 'sourceFileId' => 42, 'namespace' => 'filinq'],
			data: ['aanvrager' => ['naam' => 'Piet']],
			format: 'docx'
		);

		$this->assertSame("Geachte Piet,\nMet vriendelijke groet", (new MergeTagReader())->text(docx: $result['content']));
		$this->assertSame('<p>Geachte Piet,</p><p>Met vriendelijke groet</p>', $result['html']);
		$this->assertSame(['groet'], $result['fragments']);
		$this->assertSame([], $result['warnings']);
	}//end testDocxIsTheFilledSourceWithItsFragments()

	/**
	 * PDF output converts the filled DOCX through the cascade, passing the
	 * accessibility options, and deletes both working files.
	 *
	 * @return void
	 */
	public function testPdfGoesThroughTheCascadeAndCleansUp(): void {
		$this->store->method('read')->willReturn(OfficeFixtures::docx(paragraphs: ['${naam}']));
		$scratch = $this->createMock(File::class);
		$scratch->expects($this->once())->method('delete');
		$pdfFile = $this->createMock(File::class);
		$pdfFile->method('getContent')->willReturn('%PDF-1.7');
		$pdfFile->expects($this->once())->method('delete');
		$this->store->expects($this->once())->method('scratch')
			->with($this->callback(static fn (string $bytes): bool => str_contains((new MergeTagReader())->text(docx: $bytes), 'Klaas')), 'docx')
			->willReturn($scratch);
		$this->pdf->expects($this->once())->method('convertToPdf')->with($scratch, ['accessible' => true])->willReturn($pdfFile);

		$result = $this->renderer()->render(
			template: ['templateType' => 'office', 'sourceFileId' => 7],
			data: ['naam' => 'Klaas'],
			format: 'pdf',
			options: ['pdfOptions' => ['accessible' => true, 'margin' => 10]]
		);

		$this->assertSame('%PDF-1.7', $result['content']);
		$this->assertSame([], $result['fragments']);
	}//end testPdfGoesThroughTheCascadeAndCleansUp()

	/**
	 * ODT and HTML come from LibreOffice, converting the filled DOCX.
	 *
	 * @return void
	 */
	public function testOdtAndHtmlComeFromLibreOffice(): void {
		$this->store->method('read')->willReturn(OfficeFixtures::docx(paragraphs: ['Tekst']));
		$this->libreOffice->expects($this->exactly(2))->method('convertDocument')
			->willReturnCallback(static fn (string $bytes, string $fromExtension, string $toExtension): string => $fromExtension.'>'.$toExtension);

		$this->assertSame('docx>odt', $this->renderer()->render(template: ['templateType' => 'office', 'sourceFileId' => 1], data: [], format: 'odf')['content']);
		$this->assertSame('docx>html', $this->renderer()->render(template: ['templateType' => 'office', 'sourceFileId' => 1], data: [], format: 'html')['content']);
	}//end testOdtAndHtmlComeFromLibreOffice()

	/**
	 * A template without a stored source is a 404, and a mapped tag reads its
	 * alias.
	 *
	 * @return void
	 */
	public function testAMissingSourceIsNotFound(): void {
		$this->store->method('read')->willReturn(null);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(404);

		$this->renderer()->render(template: ['templateType' => 'office', 'sourceFileId' => 9], data: [], format: 'docx');
	}//end testAMissingSourceIsNotFound()

	/**
	 * The field mapping is applied when filling.
	 *
	 * @return void
	 */
	public function testTheFieldMapAliasesATag(): void {
		$this->store->method('read')->willReturn(OfficeFixtures::docx(paragraphs: ['${naam_aanvrager}']));
		$this->fragments->expects($this->never())->method('texts');

		$filled = $this->renderer()->fill(
			template: ['templateType' => 'office', 'sourceFileId' => 3, 'fieldMap' => ['naam_aanvrager' => 'aanvrager.naam', 'leeg' => '', 5 => 'x']],
			data: ['aanvrager' => ['naam' => 'Mies']]
		);

		$this->assertSame('<p>Mies</p>', $filled['html']);
	}//end testTheFieldMapAliasesATag()

	/**
	 * Only office templates are office templates; absent type reads as Twig.
	 *
	 * @return void
	 */
	public function testAbsentTypeReadsAsTwig(): void {
		$this->assertTrue($this->renderer()->isOffice(template: ['templateType' => 'office']));
		$this->assertFalse($this->renderer()->isOffice(template: ['templateType' => 'twig']));
		$this->assertFalse($this->renderer()->isOffice(template: []));
	}//end testAbsentTypeReadsAsTwig()

	/**
	 * A Twig template's fragment references become the fragment text, its
	 * tags Twig prints and its own Twig syntax nothing; a missing fragment
	 * is a marker.
	 *
	 * @return void
	 */
	public function testTwigFragmentsResolveBeforeRendering(): void {
		$this->fragments->expects($this->once())->method('texts')->with(['groet', 'weg'], 'filinq')
			->willReturn(['groet' => "Groet <b>{{ evil }}</b>\n\${aanvrager.naam}{% if x %}\${fragment:ander}\${a b}"]);

		$result = $this->renderer()->resolveTwigFragments(content: '<p>${fragment:groet}</p><p>${ fragment:weg }</p>{{ zaak }}', namespace: 'filinq');

		$this->assertSame(
			'<p>Groet &lt;b&gt; evil &lt;/b&gt;<br>'."\n".'{{ aanvrager.naam }} if x </p><p>[ontbrekende bouwsteen: weg]</p>{{ zaak }}',
			$result['content']
		);
		$this->assertSame(['groet'], $result['fragments']);
		$this->assertSame(
			[
				'A text fragment cannot contain another fragment ("fragment:ander"); it is left out.',
				'Merge tag "a b" in a text fragment is not a field path; it is left out.',
				'Text fragment "weg" does not exist; a marker is shown in its place.',
			],
			$result['warnings']
		);
	}//end testTwigFragmentsResolveBeforeRendering()

	/**
	 * A Twig template without fragment references is left alone.
	 *
	 * @return void
	 */
	public function testTwigWithoutFragmentsIsUntouched(): void {
		$this->fragments->expects($this->never())->method('texts');

		$this->assertSame(
			['content' => '{{ naam }}', 'warnings' => [], 'fragments' => []],
			$this->renderer()->resolveTwigFragments(content: '{{ naam }}', namespace: 'filinq')
		);
	}//end testTwigWithoutFragmentsIsUntouched()

	/**
	 * An unknown format is refused with 400.
	 *
	 * @return void
	 */
	public function testAnUnknownFormatIsRefused(): void {
		$this->store->method('read')->willReturn(OfficeFixtures::docx(paragraphs: ['x']));

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->renderer()->render(template: ['templateType' => 'office', 'sourceFileId' => 1], data: [], format: 'xlsx');
	}//end testAnUnknownFormatIsRefused()

	/**
	 * The renderer over the doubles and the real filler and reader.
	 *
	 * @return OfficeTemplateRenderer
	 */
	private function renderer(): OfficeTemplateRenderer {
		return new OfficeTemplateRenderer(
			store: $this->store,
			filler: new OfficeTemplateFiller(),
			fragments: $this->fragments,
			reader: new MergeTagReader(),
			pdf: $this->pdf,
			libreOffice: $this->libreOffice
		);
	}//end renderer()
}//end class

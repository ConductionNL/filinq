<?php

/**
 * Every format of an office template is made from its one filled DOCX: the
 * DOCX itself, and the PDF, ODT and HTML converted from it; the matrix gates
 * office HTML on LibreOffice and offers office DOCX without it.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\DocumentGenerationRecord;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\FormatMatrixService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\MultiFormatOutputProducer;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRenderer;
use OCA\Filinq\Service\PdfConversionService;
use OCA\Filinq\Service\Support\DocumentOutput;
use OCA\Filinq\Service\TemplateService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\MultiFormatOutputProducer
 * @covers \OCA\Filinq\Service\FormatMatrixService
 */
class MultiFormatOfficeOutputTest extends TestCase {

	/**
	 * The office template is filled once; each format converts that DOCX,
	 * a failing one fails alone, and the entry records the office type.
	 *
	 * @return void
	 */
	public function testEveryFormatComesFromTheOneFilledDocx(): void {
		$template = ['id' => 't-1', 'name' => 'Besluit', 'namespace' => 'besluiten', 'templateType' => 'office', 'sourceFileId' => 4];
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn($template);
		$documents = $this->createMock(DocumentService::class);
		$documents->expects($this->never())->method('generatePreview');
		$documents->expects($this->once())->method('fillOfficeTemplate')->with($template, [], $this->arrayHasKey('formats'))
			->willReturn(['docx' => 'FILLED', 'html' => '<p>Jan</p>', 'warnings' => ['gap'], 'fragments' => []]);
		$documents->method('buildOutputTargetPath')->willReturn('DocuDesk/besluiten');
		$office = $this->createMock(OfficeTemplateRenderer::class);
		$office->method('isOffice')->willReturn(true);
		$office->method('convert')->willReturnCallback(
			static fn (string $docx, string $format, array $options=[]): string => ($format === 'html' ? throw new \RuntimeException('LibreOffice is not available on this server') : $format.' of '.$docx)
		);
		$pipeline = $this->createMock(DocumentRenderPipeline::class);
		$pipeline->expects($this->never())->method('produceOutput');
		$filed = [];
		$storage = $this->createMock(DocumentStorageService::class);
		$storage->method('store')->willReturnCallback(
			static function (string $userId, string $targetPath, string $filename, string $content) use (&$filed): array {
				$filed[$filename] = $content;
				return ['fileId' => count($filed), 'path' => '/'.$filename, 'name' => $filename, 'size' => strlen($content)];
			}
		);
		$logged = null;
		$log = $this->createMock(GeneratedDocumentLogger::class);
		$log->method('log')->willReturnCallback(
			static function (DocumentGenerationRecord $generation, array $outcome) use (&$logged): array {
				$logged = [$generation->extra, $outcome['warnings']];
				return ['id' => 'log-1'];
			}
		);

		$producer = new MultiFormatOutputProducer($documents, $templates, $pipeline, new DocumentOutput(storage: $storage, log: $log), null, null, $office);
		$result = $producer->generate('t-1', [], ['formats' => ['docx', 'pdf', 'html'], 'userId' => 'clerk', 'filename' => 'besluit']);

		$this->assertSame(['besluit.docx' => 'docx of FILLED', 'besluit.pdf' => 'pdf of FILLED'], $filed);
		$this->assertSame(['generated', 'generated', 'failed'], array_column($result['outputs'], 'status'));
		$this->assertSame('office', $logged[0]['templateType']);
		$this->assertSame(['gap'], $logged[1]);
	}//end testEveryFormatComesFromTheOneFilledDocx()

	/**
	 * Without LibreOffice an office template keeps DOCX and loses HTML; a
	 * Twig template's matrix is the instance's.
	 *
	 * @return void
	 */
	public function testTheMatrixGatesOfficeHtmlOnLibreOffice(): void {
		$libreOffice = $this->createMock(LibreOfficeHeadlessBackend::class);
		$libreOffice->method('name')->willReturn('libreoffice_headless');
		$libreOffice->method('isAvailable')->willReturn(false);
		$libreOffice->method('canHandle')->willReturn(true);
		$matrix = new FormatMatrixService(new PdfConversionService([$libreOffice], new \Psr\Log\NullLogger()));

		$office = $matrix->forTemplate(template: ['templateType' => 'office']);
		$twig = $matrix->forTemplate(template: ['content' => 'x']);

		$this->assertSame(['available' => true], $office['docx']);
		$this->assertSame(['available' => false, 'reason' => LibreOfficeHeadlessBackend::UNAVAILABLE_REASON], $office['html']);
		$this->assertSame(['available' => true], $office['pdf']);
		$this->assertSame($matrix->forInstance(), $twig);
		$this->assertFalse($twig['docx']['available']);
		$this->assertTrue($twig['html']['available']);
	}//end testTheMatrixGatesOfficeHtmlOnLibreOffice()
}//end class

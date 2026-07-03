<?php

/**
 * Unit tests for PhpWordBackend
 *
 * Covers convert(): a Word document is rendered to PDF via PdfService with the
 * PDF/A-3b options (pdfa/format/title) so the docx output carries the same
 * archival container and normalization print-CSS as every other anonymised
 * output — matching MpdfBackend, which the docx path previously did not.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Conversion
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service\Conversion;

use OCA\DocuDesk\Service\Conversion\PhpWordBackend;
use OCA\DocuDesk\Service\PdfService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use OCP\ITempManager;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for {@see PhpWordBackend::convert()}.
 */
class PhpWordBackendTest extends TestCase
{
    /**
     * Build a minimal real .docx and return its bytes.
     *
     * @return string DOCX file content.
     */
    private function makeDocxBytes(): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText('Hello world');

        $tmp = tempnam(sys_get_temp_dir(), 'ddtest_').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $bytes;
    }

    /**
     * convert() must request PDF/A output (pdfa=true, format=A4, title=basename)
     * from PdfService and write the returned bytes as <basename>.pdf.
     *
     * @return void
     */
    public function testConvertRequestsPdfAOutput(): void
    {
        $docxBytes = $this->makeDocxBytes();

        // ITempManager hands back a real writable temp path for the source.
        $sourceTmp   = tempnam(sys_get_temp_dir(), 'ddsrc_').'.docx';
        $tempManager = $this->createMock(ITempManager::class);
        $tempManager->method('getTemporaryFile')->willReturn($sourceTmp);

        // PdfService: assert it receives the PDF/A options, return fake bytes.
        $pdfService = $this->createMock(PdfService::class);
        $pdfService->expects($this->once())
            ->method('generatePdfFromHtml')
            ->with(
                $this->isType('string'),
                $this->callback(
                    static function (array $options): bool {
                        return ($options['pdfa'] ?? null) === true
                            && ($options['format'] ?? null) === 'A4'
                            && ($options['title'] ?? null) === 'sample';
                    }
                )
            )
            ->willReturn('PDF-BYTES');

        // Output folder: no pre-existing file; newFile returns a File stub.
        $resultFile = $this->createMock(File::class);
        $parent     = $this->createMock(Folder::class);
        $parent->method('nodeExists')->with('sample.pdf')->willReturn(false);
        $parent->expects($this->once())
            ->method('newFile')
            ->with('sample.pdf', 'PDF-BYTES')
            ->willReturn($resultFile);

        // Source file node.
        $source = $this->createMock(File::class);
        $source->method('getName')->willReturn('sample.docx');
        $source->method('getContent')->willReturn($docxBytes);
        $source->method('getParent')->willReturn($parent);

        $backend = new PhpWordBackend(
            appConfig: $this->createMock(IAppConfig::class),
            tempManager: $tempManager,
            pdfService: $pdfService,
            logger: $this->createMock(LoggerInterface::class),
        );

        $out = $backend->convert($source);
        $this->assertSame($resultFile, $out);

        @unlink($sourceTmp);
    }
}
<?php

/**
 * Unit tests for PhpWordBackend
 *
 * Covers supported MIME types, rejected MIME types, isAvailable() paths,
 * and the convert() flow (via mocks — PhpWord is not actually invoked).
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
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-11
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PhpWordBackend
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PhpWordBackendTest extends TestCase
{

    /**
     * App config mock.
     *
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $appConfig;

    /**
     * Temp manager mock.
     *
     * @var ITempManager|MockObject
     */
    private ITempManager|MockObject $tempManager;

    /**
     * PdfService mock.
     *
     * @var PdfService|MockObject
     */
    private PdfService|MockObject $pdfService;

    /**
     * Logger mock.
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $logger;

    /**
     * Backend under test.
     *
     * @var PhpWordBackend
     */
    private PhpWordBackend $backend;

    /**
     * Set up the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->appConfig   = $this->createMock(originalClassName: IAppConfig::class);
        $this->tempManager = $this->createMock(originalClassName: ITempManager::class);
        $this->pdfService  = $this->createMock(originalClassName: PdfService::class);
        $this->logger      = $this->createMock(originalClassName: LoggerInterface::class);

        $this->backend = new PhpWordBackend(
            appConfig: $this->appConfig,
            tempManager: $this->tempManager,
            pdfService: $this->pdfService,
            logger: $this->logger,
        );

    }//end setUp()

    /**
     * Test that name() returns the stable identifier 'phpword'
     *
     * @return void
     */
    public function testNameReturnsPhpword(): void
    {
        $this->assertSame(expected: 'phpword', actual: $this->backend->name());

    }//end testNameReturnsPhpword()

    /**
     * Test that isAvailable() returns false when tenant flag is 'false'
     *
     * @return void
     */
    public function testIsUnavailableWhenFlagFalse(): void
    {
        $this->appConfig->method('getValueString')->willReturn('false');
        $this->assertFalse(condition: $this->backend->isAvailable());

    }//end testIsUnavailableWhenFlagFalse()

    /**
     * Test that isAvailable() returns true when flag is 'true' and PhpWord class exists
     *
     * @return void
     */
    public function testIsAvailableWhenFlagTrueAndClassExists(): void
    {
        $this->appConfig->method('getValueString')->willReturn('true');
        $this->assertTrue(condition: $this->backend->isAvailable());

    }//end testIsAvailableWhenFlagTrueAndClassExists()

    /**
     * Test that canHandle() returns true for DOCX MIME
     *
     * @return void
     */
    public function testCanHandleDocxMime(): void
    {
        $this->assertTrue(
            condition: $this->backend->canHandle(
                mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                extension: 'docx'
            )
        );

    }//end testCanHandleDocxMime()

    /**
     * Test that canHandle() returns true for ODT MIME
     *
     * @return void
     */
    public function testCanHandleOdtMime(): void
    {
        $this->assertTrue(
            condition: $this->backend->canHandle(
                mimeType: 'application/vnd.oasis.opendocument.text',
                extension: 'odt'
            )
        );

    }//end testCanHandleOdtMime()

    /**
     * Test that canHandle() returns true for RTF MIME
     *
     * @return void
     */
    public function testCanHandleRtfMime(): void
    {
        $this->assertTrue(
            condition: $this->backend->canHandle(mimeType: 'application/rtf', extension: 'rtf')
        );

    }//end testCanHandleRtfMime()

    /**
     * Test that canHandle() returns true for text/html MIME
     *
     * @return void
     */
    public function testCanHandleHtmlMime(): void
    {
        $this->assertTrue(
            condition: $this->backend->canHandle(mimeType: 'text/html', extension: 'html')
        );

    }//end testCanHandleHtmlMime()

    /**
     * Test that canHandle() returns true for .docx extension
     *
     * @return void
     */
    public function testCanHandleDocxExtension(): void
    {
        $this->assertTrue(
            condition: $this->backend->canHandle(mimeType: 'application/octet-stream', extension: 'docx')
        );

    }//end testCanHandleDocxExtension()

    /**
     * Test that canHandle() returns false for XLSX (spreadsheet format)
     *
     * @return void
     */
    public function testCannotHandleXlsxMime(): void
    {
        $this->assertFalse(
            condition: $this->backend->canHandle(
                mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                extension: 'xlsx'
            )
        );

    }//end testCannotHandleXlsxMime()

    /**
     * Test that canHandle() returns false for image/png
     *
     * @return void
     */
    public function testCannotHandleImageMime(): void
    {
        $this->assertFalse(
            condition: $this->backend->canHandle(mimeType: 'image/png', extension: 'png')
        );

    }//end testCannotHandleImageMime()

    /**
     * Test that canHandle() returns true for application/msword (legacy DOC)
     *
     * @return void
     */
    public function testCanHandleLegacyDocMime(): void
    {
        $this->assertTrue(
            condition: $this->backend->canHandle(mimeType: 'application/msword', extension: 'doc')
        );

    }//end testCanHandleLegacyDocMime()

    /**
     * Test that canHandle() returns true for text/rtf MIME
     *
     * @return void
     */
    public function testCanHandleTextRtfMime(): void
    {
        $this->assertTrue(
            condition: $this->backend->canHandle(mimeType: 'text/rtf', extension: 'rtf')
        );

    }//end testCanHandleTextRtfMime()


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

    }//end makeDocxBytes()


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

    }//end testConvertRequestsPdfAOutput()
}//end class

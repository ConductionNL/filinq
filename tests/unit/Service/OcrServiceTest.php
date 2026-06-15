<?php

/**
 * Unit tests for OcrService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\OcrService;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for OcrService
 *
 * Tests OCR detection logic and configuration methods.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress  PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class OcrServiceTest extends TestCase
{

    /**
     * The OcrService instance being tested
     *
     * @var OcrService
     */
    private OcrService $ocrService;

    /**
     * Mocked LoggerInterface for logging operations
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Mocked IAppConfig for app configuration
     *
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockConfig;

    /**
     * Mocked IRootFolder for file access
     *
     * @var IRootFolder|MockObject
     */
    private IRootFolder|MockObject $mockRootFolder;

    /**
     * Mocked IUserSession for user session
     *
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;


    /**
     * Set up test fixtures
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger      = $this->createMock(originalClassName: LoggerInterface::class);
        $this->mockConfig      = $this->createMock(originalClassName: IAppConfig::class);
        $this->mockRootFolder  = $this->createMock(originalClassName: IRootFolder::class);
        $this->mockUserSession = $this->createMock(originalClassName: IUserSession::class);

        $this->ocrService = new OcrService(
            logger: $this->mockLogger,
            config: $this->mockConfig,
            rootFolder: $this->mockRootFolder,
            userSession: $this->mockUserSession
        );

    }//end setUp()


    /**
     * Test needsOcr returns true for PNG image
     *
     * @return void
     */
    public function testNeedsOcrReturnsTrueForPngImage(): void
    {
        $result = $this->ocrService->needsOcr(mimeType: 'image/png');
        $this->assertTrue(condition: $result);

    }//end testNeedsOcrReturnsTrueForPngImage()


    /**
     * Test needsOcr returns true for JPEG image
     *
     * @return void
     */
    public function testNeedsOcrReturnsTrueForJpegImage(): void
    {
        $result = $this->ocrService->needsOcr(mimeType: 'image/jpeg');
        $this->assertTrue(condition: $result);

    }//end testNeedsOcrReturnsTrueForJpegImage()


    /**
     * Test needsOcr returns true for TIFF image
     *
     * @return void
     */
    public function testNeedsOcrReturnsTrueForTiffImage(): void
    {
        $result = $this->ocrService->needsOcr(mimeType: 'image/tiff');
        $this->assertTrue(condition: $result);

    }//end testNeedsOcrReturnsTrueForTiffImage()


    /**
     * Test needsOcr returns true for BMP image
     *
     * @return void
     */
    public function testNeedsOcrReturnsTrueForBmpImage(): void
    {
        $result = $this->ocrService->needsOcr(mimeType: 'image/bmp');
        $this->assertTrue(condition: $result);

    }//end testNeedsOcrReturnsTrueForBmpImage()


    /**
     * Test needsOcr returns true for GIF image
     *
     * @return void
     */
    public function testNeedsOcrReturnsTrueForGifImage(): void
    {
        $result = $this->ocrService->needsOcr(mimeType: 'image/gif');
        $this->assertTrue(condition: $result);

    }//end testNeedsOcrReturnsTrueForGifImage()


    /**
     * Test needsOcr returns true for PDF with no text
     *
     * @return void
     */
    public function testNeedsOcrReturnsTrueForPdfWithNoText(): void
    {
        $result = $this->ocrService->needsOcr(mimeType: 'application/pdf', existingText: null);
        $this->assertTrue(condition: $result);

    }//end testNeedsOcrReturnsTrueForPdfWithNoText()


    /**
     * Test needsOcr returns true for PDF with empty text
     *
     * @return void
     */
    public function testNeedsOcrReturnsTrueForPdfWithEmptyText(): void
    {
        $result = $this->ocrService->needsOcr(mimeType: 'application/pdf', existingText: '');
        $this->assertTrue(condition: $result);

    }//end testNeedsOcrReturnsTrueForPdfWithEmptyText()


    /**
     * Test needsOcr returns true for PDF with whitespace-only text
     *
     * @return void
     */
    public function testNeedsOcrReturnsTrueForPdfWithWhitespaceText(): void
    {
        $result = $this->ocrService->needsOcr(mimeType: 'application/pdf', existingText: '   ');
        $this->assertTrue(condition: $result);

    }//end testNeedsOcrReturnsTrueForPdfWithWhitespaceText()


    /**
     * Test needsOcr returns false for PDF with existing text
     *
     * @return void
     */
    public function testNeedsOcrReturnsFalseForPdfWithText(): void
    {
        $result = $this->ocrService->needsOcr(
            mimeType: 'application/pdf',
            existingText: 'This is some extracted text'
        );
        $this->assertFalse(condition: $result);

    }//end testNeedsOcrReturnsFalseForPdfWithText()


    /**
     * Test needsOcr returns false for Word document
     *
     * @return void
     */
    public function testNeedsOcrReturnsFalseForWordDocument(): void
    {
        $result = $this->ocrService->needsOcr(mimeType: 'application/msword');
        $this->assertFalse(condition: $result);

    }//end testNeedsOcrReturnsFalseForWordDocument()


    /**
     * Test needsOcr returns false for plain text
     *
     * @return void
     */
    public function testNeedsOcrReturnsFalseForPlainText(): void
    {
        $result = $this->ocrService->needsOcr(mimeType: 'text/plain');
        $this->assertFalse(condition: $result);

    }//end testNeedsOcrReturnsFalseForPlainText()


    /**
     * Test needsOcr returns false for DOCX
     *
     * @return void
     */
    public function testNeedsOcrReturnsFalseForDocx(): void
    {
        $result = $this->ocrService->needsOcr(
            mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
        $this->assertFalse(condition: $result);

    }//end testNeedsOcrReturnsFalseForDocx()


    /**
     * Test needsOcr returns false for HTML
     *
     * @return void
     */
    public function testNeedsOcrReturnsFalseForHtml(): void
    {
        $result = $this->ocrService->needsOcr(mimeType: 'text/html');
        $this->assertFalse(condition: $result);

    }//end testNeedsOcrReturnsFalseForHtml()


    /**
     * Test image MIME types always trigger OCR regardless of existing text
     *
     * @return void
     */
    public function testImageMimeTypesAlwaysTriggerOcr(): void
    {
        $result = $this->ocrService->needsOcr(mimeType: 'image/png', existingText: 'some text');
        $this->assertTrue(
            condition: $result,
            message: 'Images should always need OCR even with existing text'
        );

    }//end testImageMimeTypesAlwaysTriggerOcr()


    /**
     * Test isOcrEnabled returns true by default
     *
     * @return void
     */
    public function testIsOcrEnabledDefaultsToTrue(): void
    {
        $this->mockConfig->method('getValueString')
            ->with('docudesk', 'ocr_enabled', '1')
            ->willReturn('1');

        $result = $this->ocrService->isOcrEnabled();
        $this->assertTrue(condition: $result);

    }//end testIsOcrEnabledDefaultsToTrue()


    /**
     * Test isOcrEnabled returns false when disabled
     *
     * @return void
     */
    public function testIsOcrEnabledReturnsFalseWhenDisabled(): void
    {
        $this->mockConfig->method('getValueString')
            ->with('docudesk', 'ocr_enabled', '1')
            ->willReturn('0');

        $result = $this->ocrService->isOcrEnabled();
        $this->assertFalse(condition: $result);

    }//end testIsOcrEnabledReturnsFalseWhenDisabled()


    /**
     * Test getOcrLanguages returns default
     *
     * @return void
     */
    public function testGetOcrLanguagesReturnsDefault(): void
    {
        // Canonical key returns '' (unset) -> falls back to the legacy key, which
        // returns the DEFAULT_LANGUAGES default.
        $this->mockConfig->method('getValueString')
            ->willReturnMap(
                [
                    ['docudesk', 'ocr.default_languages', '', false, ''],
                    ['docudesk', 'ocr_languages', 'nld+eng', false, 'nld+eng'],
                ]
            );

        $result = $this->ocrService->getOcrLanguages();
        $this->assertSame(expected: 'nld+eng', actual: $result);

    }//end testGetOcrLanguagesReturnsDefault()


    /**
     * Test getOcrLanguages returns custom config
     *
     * @return void
     */
    public function testGetOcrLanguagesReturnsCustomConfig(): void
    {
        // Canonical key carries the admin override; legacy fallback never reached.
        $this->mockConfig->method('getValueString')
            ->willReturnMap(
                [
                    ['docudesk', 'ocr.default_languages', '', false, 'nld+eng+deu+fra'],
                    ['docudesk', 'ocr_languages', 'nld+eng', false, 'nld+eng'],
                ]
            );

        $result = $this->ocrService->getOcrLanguages();
        $this->assertSame(expected: 'nld+eng+deu+fra', actual: $result);

    }//end testGetOcrLanguagesReturnsCustomConfig()


    /**
     * Test getOcrDpi returns default
     *
     * @return void
     */
    public function testGetOcrDpiReturnsDefault(): void
    {
        // Canonical key returns '' (unset) -> falls back to the legacy key default.
        $this->mockConfig->method('getValueString')
            ->willReturnMap(
                [
                    ['docudesk', 'ocr.default_dpi', '', false, ''],
                    ['docudesk', 'ocr_dpi', '300', false, '300'],
                ]
            );

        $result = $this->ocrService->getOcrDpi();
        $this->assertSame(expected: 300, actual: $result);

    }//end testGetOcrDpiReturnsDefault()


    /**
     * Test getOcrDpi returns custom value
     *
     * @return void
     */
    public function testGetOcrDpiReturnsCustomValue(): void
    {
        // Canonical key carries the admin override; legacy fallback never reached.
        $this->mockConfig->method('getValueString')
            ->willReturnMap(
                [
                    ['docudesk', 'ocr.default_dpi', '', false, '400'],
                    ['docudesk', 'ocr_dpi', '300', false, '300'],
                ]
            );

        $result = $this->ocrService->getOcrDpi();
        $this->assertSame(expected: 400, actual: $result);

    }//end testGetOcrDpiReturnsCustomValue()


    /**
     * Test processFile returns not processed when OCR is disabled
     *
     * @return void
     */
    public function testProcessFileReturnsNotProcessedWhenDisabled(): void
    {
        $this->mockConfig->method('getValueString')
            ->willReturnCallback(
                function (string $app, string $key, string $default): string {
                    if ($key === 'ocr_enabled') {
                        return '0';
                    }

                    return $default;
                }
            );

        $result = $this->ocrService->processFile(fileId: 123);
        $this->assertFalse(condition: $result['ocrProcessed']);
        $this->assertSame(expected: '', actual: $result['text']);

    }//end testProcessFileReturnsNotProcessedWhenDisabled()


}//end class

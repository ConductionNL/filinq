<?php

/**
 * Unit tests for AnonymizationService
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

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AnonymizationService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationServiceTest extends TestCase
{


    /**
     * Check if the class can be loaded without parse errors
     *
     * @return void
     */
    private function requireClassOrSkip(): void
    {
        try {
            $file = __DIR__.'/../../../lib/Service/AnonymizationService.php';
            $code = php_strip_whitespace($file);
            if (empty($code) === true) {
                $this->markTestSkipped('AnonymizationService has parse errors.');
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('AnonymizationService has parse errors: '.$e->getMessage());
        }

    }//end requireClassOrSkip()


    /**
     * Test that the source file exists
     *
     * @return void
     */
    public function testSourceFileExists(): void
    {
        $this->assertFileExists(
            __DIR__.'/../../../lib/Service/AnonymizationService.php'
        );

    }//end testSourceFileExists()


    /**
     * Test file contains expected class declaration
     *
     * @return void
     */
    public function testFileContainsClassDeclaration(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('class AnonymizationService', $content);

    }//end testFileContainsClassDeclaration()


    /**
     * Test file contains expected methods
     *
     * @return void
     */
    public function testFileContainsExpectedMethods(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('function extractAndDetectEntities', $content);
        $this->assertStringContainsString('function anonymizeDocument', $content);

    }//end testFileContainsExpectedMethods()


    /**
     * anonymise-pdf-only-output-mode: AnonymizationService must accept an
     * `$outputFormat` argument on anonymizeDocument so the controller can
     * pass through the per-call gate, and its default is now 'pdf-only'.
     *
     * @return void
     */
    public function testAnonymizeDocumentAcceptsOutputFormatArgument(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        // Signature must include the outputFormat parameter with a
        // default of 'pdf-only' (the new privacy-correct default).
        $this->assertMatchesRegularExpression(
            '/function anonymizeDocument\([^)]*\$outputFormat\s*=\s*\'pdf-only\'[^)]*\)/s',
            $content,
            'anonymizeDocument must accept $outputFormat with a default of \'pdf-only\''
        );

    }//end testAnonymizeDocumentAcceptsOutputFormatArgument()


    /**
     * anonymise-pdf-only-output-mode: the PDF-conversion gate must fire for
     * BOTH 'pdf-only' and 'pdf' (both run the cascade), still guarded by the
     * "not already a PDF" mime check — so an already-a-PDF result is a no-op
     * for either mode (tasks 3.1 / 3.3).
     *
     * @return void
     */
    public function testConversionGateFiresForPdfOnlyAndPdf(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        $this->assertMatchesRegularExpression(
            '/in_array\(\s*\$outputFormat\s*,\s*\[\s*\'pdf-only\'\s*,\s*\'pdf\'\s*\]/s',
            $content,
            'the conversion gate must fire for both pdf-only and pdf'
        );
        $this->assertStringContainsString(
            "\$resultMime !== 'application/pdf'",
            $content,
            'the cascade must stay guarded by the not-already-a-PDF mime check (already-a-PDF no-op)'
        );

    }//end testConversionGateFiresForPdfOnlyAndPdf()


    /**
     * anonymise-pdf-only-output-mode: the native anonymised node must be
     * captured into $nativeIntermediate BEFORE $result is reassigned by
     * convertToPdf(), otherwise the reference to delete is lost (task 3.1).
     *
     * @return void
     */
    public function testNativeIntermediateCapturedBeforeReassignment(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        $capturePos = strpos($content, '$nativeIntermediate = $result;');
        $convertPos = strpos($content, '$result = $this->pdfConversion->convertToPdf($result);');

        $this->assertNotFalse($capturePos, 'native intermediate must be captured into $nativeIntermediate');
        $this->assertNotFalse($convertPos, 'convertToPdf must reassign $result');
        $this->assertLessThan(
            $convertPos,
            $capturePos,
            'native node must be captured BEFORE $result is reassigned to the converted PDF'
        );

    }//end testNativeIntermediateCapturedBeforeReassignment()


    /**
     * anonymise-pdf-only-output-mode: after a successful conversion, only
     * 'pdf-only' deletes the native intermediate, and that delete is
     * best-effort — wrapped in try/catch Throwable with a PII-free warning
     * that never re-throws (tasks 3.2 / 4.1 / 4.2). 'pdf' keeps the native
     * file (task 4.4).
     *
     * @return void
     */
    public function testPdfOnlyBestEffortDeletesNativeIntermediate(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        // The delete is gated on the pdf-only mode only (pdf keeps native).
        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*\$outputFormat\s*===\s*\'pdf-only\'\s*\)\s*\{\s*try\s*\{\s*\$nativeIntermediate->delete\(\);/s',
            $content,
            'only pdf-only deletes the captured native intermediate after a successful conversion'
        );

        // Best-effort: caught as Throwable and logged, never re-thrown.
        $this->assertMatchesRegularExpression(
            '/\$nativeIntermediate->delete\(\);\s*\}\s*catch\s*\(\s*Throwable\s+\$deleteError\s*\)/s',
            $content,
            'the native-intermediate delete must be best-effort (catch Throwable)'
        );
        $this->assertStringContainsString(
            'pdf-only: failed to delete native anonymised intermediate',
            $content,
            'a PII-free warning must be logged when the cleanup delete fails'
        );

    }//end testPdfOnlyBestEffortDeletesNativeIntermediate()


    /**
     * anonymise-output-as-pdf-by-default: AnonymizationService must
     * depend on PdfConversionService and catch ConversionFailedException
     * for the rollback path. Shape-level check; the actual cascade
     * behaviour is covered by PdfConversionServiceTest.
     *
     * @return void
     */
    public function testServiceWiresPdfConversionAndRollback(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        $this->assertStringContainsString(
            'PdfConversionService',
            $content,
            'AnonymizationService must wire PdfConversionService for the pdf-output path'
        );
        $this->assertStringContainsString(
            'ConversionFailedException',
            $content,
            'AnonymizationService must catch ConversionFailedException for the rollback path'
        );
        $this->assertStringContainsString(
            '$result->delete()',
            $content,
            'AnonymizationService must delete the un-converted intermediate when conversion fails'
        );

    }//end testServiceWiresPdfConversionAndRollback()


}//end class

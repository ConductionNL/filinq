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
     * anonymise-output-as-pdf-by-default: AnonymizationService must
     * accept an `$outputFormat` argument on anonymizeDocument so the
     * controller can pass through the per-call gate.
     *
     * @return void
     */
    public function testAnonymizeDocumentAcceptsOutputFormatArgument(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        // Signature must include the outputFormat parameter with a
        // default of 'pdf' (per design D6 / proposal "default pdf").
        $this->assertMatchesRegularExpression(
            '/function anonymizeDocument\([^)]*\$outputFormat\s*=\s*\'pdf\'[^)]*\)/s',
            $content,
            'anonymizeDocument must accept $outputFormat with a default of \'pdf\''
        );

    }//end testAnonymizeDocumentAcceptsOutputFormatArgument()


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

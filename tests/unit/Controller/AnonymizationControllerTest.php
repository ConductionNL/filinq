<?php

/**
 * Unit tests for AnonymizationController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AnonymizationController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationControllerTest extends TestCase
{


    /**
     * Test source file exists
     *
     * @return void
     */
    public function testSourceFileExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../lib/Controller/AnonymizationController.php'
        );

    }//end testSourceFileExists()


    /**
     * Test file contains class declaration
     *
     * @return void
     */
    public function testFileContainsClassDeclaration(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');
        $this->assertStringContainsString('class AnonymizationController', $content);

    }//end testFileContainsClassDeclaration()


    /**
     * Test file contains files method
     *
     * @return void
     */
    public function testFileContainsFilesMethod(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');
        $this->assertStringContainsString('function files()', $content);

    }//end testFileContainsFilesMethod()


    /**
     * Test file contains extract method
     *
     * @return void
     */
    public function testFileContainsExtractMethod(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');
        $this->assertStringContainsString('function extract(', $content);

    }//end testFileContainsExtractMethod()


    /**
     * Test file contains anonymize method
     *
     * @return void
     */
    public function testFileContainsAnonymizeMethod(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');
        $this->assertStringContainsString('function anonymize(', $content);

    }//end testFileContainsAnonymizeMethod()


    /**
     * anonymise-output-as-pdf-by-default: the controller must accept
     * the per-call `outputFormat` field and validate it against the
     * documented allow-list.
     *
     * @return void
     */
    public function testControllerAcceptsAndValidatesOutputFormat(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');

        $this->assertStringContainsString(
            "self::VALID_OUTPUT_FORMATS",
            $content,
            'Controller must expose a VALID_OUTPUT_FORMATS allow-list'
        );
        $this->assertMatchesRegularExpression(
            "/'pdf'\\s*,\\s*'preserve'/",
            $content,
            'VALID_OUTPUT_FORMATS must contain pdf and preserve'
        );
        $this->assertStringContainsString(
            'resolveOutputFormat',
            $content,
            'Controller must implement resolveOutputFormat helper'
        );

    }//end testControllerAcceptsAndValidatesOutputFormat()


    /**
     * anonymise-output-as-pdf-by-default: the controller must map
     * ConversionFailedException to an HTTP 422 response with the
     * structured body documented in design D5.
     *
     * @return void
     */
    public function testControllerSurfacesConversionFailureAsHttp422(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');

        $this->assertStringContainsString(
            'ConversionFailedException',
            $content,
            'Controller must catch ConversionFailedException'
        );
        $this->assertStringContainsString(
            "'conversionAttempts'",
            $content,
            'Controller must surface conversionAttempts on the 422 body'
        );
        $this->assertMatchesRegularExpression(
            '/new JSONResponse\b[\s\S]*?422\s*\);/',
            $content,
            'Controller must return 422 for ConversionFailedException'
        );

    }//end testControllerSurfacesConversionFailureAsHttp422()


    /**
     * anonymise-output-as-pdf-by-default: tenant default is read from
     * the documented IAppConfig key.
     *
     * @return void
     */
    public function testControllerReadsTenantDefaultOutputFormat(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');

        $this->assertStringContainsString(
            'docudesk.anonymisation.default_output_format',
            $content,
            'Controller must read tenant default via the documented IAppConfig key'
        );

    }//end testControllerReadsTenantDefaultOutputFormat()


}//end class

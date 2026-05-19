<?php

/**
 * Unit tests for OpenRegisterResolver
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
 * Unit tests for OpenRegisterResolver
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class OpenRegisterResolverTest extends TestCase
{


    /**
     * Test source file exists
     *
     * @return void
     */
    public function testSourceFileExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../lib/Service/OpenRegisterResolver.php'
        );

    }//end testSourceFileExists()


    /**
     * Test file contains class declaration
     *
     * @return void
     */
    public function testFileContainsClassDeclaration(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Service/OpenRegisterResolver.php');
        $this->assertStringContainsString('class OpenRegisterResolver', $content);

    }//end testFileContainsClassDeclaration()


    /**
     * Test file contains getRegisterAndSchema method
     *
     * @return void
     */
    public function testFileContainsGetRegisterAndSchemaMethod(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Service/OpenRegisterResolver.php');
        $this->assertStringContainsString('function getRegisterAndSchema()', $content);

    }//end testFileContainsGetRegisterAndSchemaMethod()


    /**
     * Test file contains validateNamespace method
     *
     * @return void
     */
    public function testFileContainsValidateNamespaceMethod(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Service/OpenRegisterResolver.php');
        $this->assertStringContainsString('function validateNamespace(', $content);

    }//end testFileContainsValidateNamespaceMethod()


    /**
     * Test file uses SettingsService dependency
     *
     * @return void
     */
    public function testFileDependsOnSettingsService(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Service/OpenRegisterResolver.php');
        $this->assertStringContainsString('SettingsService', $content);

    }//end testFileDependsOnSettingsService()


}//end class

<?php

/**
 * Unit tests for SettingsController
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
 * Unit tests for SettingsController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SettingsControllerTest extends TestCase
{


    /**
     * Test source file exists
     *
     * @return void
     */
    public function testSourceFileExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../lib/Controller/SettingsController.php'
        );

    }//end testSourceFileExists()


    /**
     * Test file contains class declaration
     *
     * @return void
     */
    public function testFileContainsClassDeclaration(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/SettingsController.php');
        $this->assertStringContainsString('class SettingsController', $content);

    }//end testFileContainsClassDeclaration()


    /**
     * Test file contains index method
     *
     * @return void
     */
    public function testFileContainsIndexMethod(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/SettingsController.php');
        $this->assertStringContainsString('function index()', $content);

    }//end testFileContainsIndexMethod()


    /**
     * Test file contains create method
     *
     * @return void
     */
    public function testFileContainsCreateMethod(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/SettingsController.php');
        $this->assertStringContainsString('function create()', $content);

    }//end testFileContainsCreateMethod()


}//end class

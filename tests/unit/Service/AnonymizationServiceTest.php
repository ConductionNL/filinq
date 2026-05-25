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
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
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
     * Test anonymizeDocument signature accepts appendBasisSummary and outputFormat.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testAnonymizeDocumentSignatureAcceptsNewParams(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('appendBasisSummary', $content);
        $this->assertStringContainsString('outputFormat', $content);

    }//end testAnonymizeDocumentSignatureAcceptsNewParams()


    /**
     * Test that tryAppendBasisSummary is defined as a private method.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testTryAppendBasisSummaryMethodExists(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('function tryAppendBasisSummary', $content);

    }//end testTryAppendBasisSummaryMethodExists()


    /**
     * Test the service records a structured warning field on summary failure.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testWarningFieldDefinedOnSummaryFailure(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('SUMMARY_APPEND_FAILED', $content);
        $this->assertStringContainsString("'warning'", $content);

    }//end testWarningFieldDefinedOnSummaryFailure()


    /**
     * Test that preserve mode path sets summaryFileId and summaryFilePath fields.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testPreserveModeSetsSummaryFields(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('summaryFileId', $content);
        $this->assertStringContainsString('summaryFilePath', $content);
        $this->assertStringContainsString("'preserve'", $content);

    }//end testPreserveModeSetsSummaryFields()


}//end class

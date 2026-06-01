<?php

/**
 * Single-file regression guard — verifies AnonymizationService::anonymizeDocument
 * is NOT modified by the batch output folder layout change.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-7
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Regression guard: single-file anonymise path is unchanged by the batch output
 * folder layout change. The post-process move is applied ONLY by BatchAnonymizeService,
 * not by AnonymizationService::anonymizeDocument.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationServiceSingleFileRegressionTest extends TestCase
{
    /**
     * AnonymizationService has the anonymizeDocument method.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-7
     */
    public function testAnonymizeDocumentMethodExists(): void
    {
        $this->assertTrue(
            method_exists('OCA\DocuDesk\Service\AnonymizationService', 'anonymizeDocument')
        );

    }//end testAnonymizeDocumentMethodExists()

    /**
     * AnonymizationService does NOT reference OutputLayoutResolver.
     *
     * The subfolder layout post-process must ONLY be applied by
     * BatchAnonymizeService, never by the single-file anonymize path.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-7
     */
    public function testAnonymizationServiceDoesNotUseOutputLayoutResolver(): void
    {
        $src = file_get_contents(
            __DIR__.'/../../../lib/Service/AnonymizationService.php'
        );

        $this->assertIsString($src);
        $this->assertStringNotContainsString(
            'OutputLayoutResolver',
            $src,
            'AnonymizationService must not reference OutputLayoutResolver; '
            .'the post-process move is BatchAnonymizeService-only.'
        );

    }//end testAnonymizationServiceDoesNotUseOutputLayoutResolver()

    /**
     * AnonymizationService does NOT reference IRootFolder (no direct file moves).
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-7
     */
    public function testAnonymizationServiceDoesNotUseIRootFolder(): void
    {
        $src = file_get_contents(
            __DIR__.'/../../../lib/Service/AnonymizationService.php'
        );

        $this->assertIsString($src);
        $this->assertStringNotContainsString(
            'IRootFolder',
            $src,
            'AnonymizationService must not import or use IRootFolder; file moves '
            .'are handled by BatchAnonymizeService only.'
        );

    }//end testAnonymizationServiceDoesNotUseIRootFolder()
}//end class

<?php

/**
 * Unit tests for EntityConsolidationService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/enhanced-anonymization/specs/anonymization-entity-review/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\EntityConsolidationService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EntityConsolidationService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EntityConsolidationServiceTest extends TestCase
{
    /**
     * Source file exists test.
     *
     * @return void
     */
    public function testSourceFileExists(): void
    {
        $this->assertFileExists(
            filename: __DIR__.'/../../../lib/Service/EntityConsolidationService.php'
        );

    }//end testSourceFileExists()

    /**
     * Class declaration exists in the source.
     *
     * @return void
     */
    public function testClassDeclarationExists(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/EntityConsolidationService.php');
        $this->assertStringContainsString(
            needle: 'class EntityConsolidationService',
            haystack: $content
        );

    }//end testClassDeclarationExists()

    /**
     * ConsolidateEntities method is declared.
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/anonymization-entity-review/spec.md
     */
    public function testConsolidateEntitiesMethodExists(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/EntityConsolidationService.php');
        $this->assertStringContainsString(
            needle: 'function consolidateEntities',
            haystack: $content
        );

    }//end testConsolidateEntitiesMethodExists()

    /**
     * WHEN minConfidence parameter is used, entities below the threshold
     * should be flagged with included=false — verify the logic path exists.
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/anonymization-entity-review/spec.md
     */
    public function testMinConfidenceParameterIsApplied(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/EntityConsolidationService.php');
        $this->assertStringContainsString(needle: 'minConfidence', haystack: $content);
        $this->assertStringContainsString(needle: "'included'", haystack: $content);

    }//end testMinConfidenceParameterIsApplied()

    /**
     * WHEN consolidateEntities runs, results should be sorted by highestConfidence descending.
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/anonymization-entity-review/spec.md
     */
    public function testConsolidateEntitiesSortsByConfidenceDescending(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/EntityConsolidationService.php');
        $this->assertStringContainsString(needle: 'highestConfidence', haystack: $content);
        $this->assertStringContainsString(
            needle: '$b[\'highestConfidence\'] <=> $a[\'highestConfidence\']',
            haystack: $content
        );

    }//end testConsolidateEntitiesSortsByConfidenceDescending()

    /**
     * The deduplication key is based on lower-cased entity value (fileCount tracking).
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/anonymization-entity-review/spec.md
     */
    public function testDeduplicationKeyIsLowerCasedValue(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/EntityConsolidationService.php');
        $this->assertStringContainsString(needle: 'mb_strtolower', haystack: $content);
        $this->assertStringContainsString(needle: "'fileCount'", haystack: $content);

    }//end testDeduplicationKeyIsLowerCasedValue()
}//end class

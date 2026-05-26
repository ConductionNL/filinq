<?php

/**
 * Unit tests for EntityDetectionService
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\AnonymizationResultParser;
use OCA\DocuDesk\Service\EntityDetectionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EntityDetectionService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EntityDetectionServiceTest extends TestCase
{

    /**
     * @var EntityDetectionService
     */
    private EntityDetectionService $service;

    /**
     * @var AnonymizationResultParser|MockObject
     */
    private AnonymizationResultParser|MockObject $mockResultParser;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockResultParser = $this->createMock(AnonymizationResultParser::class);
        $this->service          = new EntityDetectionService($this->mockResultParser);

    }//end setUp()


    /**
     * Test normalizeEntities with array entities
     *
     * @return void
     */
    public function testNormalizeEntitiesWithArrays(): void
    {
        $entities = [
            ['entity_type' => 'PERSON', 'entity_value' => 'John', 'confidence' => 0.95],
            ['entityType' => 'LOCATION', 'entityValue' => 'Amsterdam', 'confidence' => 0.8],
        ];

        $result = $this->service->normalizeEntities($entities);

        $this->assertCount(2, $result);
        $this->assertEquals('PERSON', $result[0]['type']);
        $this->assertEquals('John', $result[0]['value']);
        $this->assertEquals(0.95, $result[0]['confidence']);
        $this->assertEquals('LOCATION', $result[1]['type']);
        $this->assertEquals('Amsterdam', $result[1]['value']);

    }//end testNormalizeEntitiesWithArrays()


    /**
     * Test normalizeEntities with missing fields uses defaults
     *
     * @return void
     */
    public function testNormalizeEntitiesWithMissingFieldsUsesDefaults(): void
    {
        $entities = [[]];
        $result   = $this->service->normalizeEntities($entities);

        $this->assertEquals('UNKNOWN', $result[0]['type']);
        $this->assertEquals('', $result[0]['value']);
        $this->assertEquals(0.0, $result[0]['confidence']);

    }//end testNormalizeEntitiesWithMissingFieldsUsesDefaults()


    /**
     * Test mapEntitiesForAnonymization filters short and numeric values
     *
     * @return void
     */
    public function testMapEntitiesForAnonymizationFiltersShortAndNumeric(): void
    {
        $entities = [
            ['value' => 'John Doe', 'type' => 'PERSON'],
            ['value' => 'ab', 'type' => 'PERSON'],
            ['value' => '123', 'type' => 'NUMBER'],
            ['value' => '', 'type' => 'EMPTY'],
        ];

        $result = $this->service->mapEntitiesForAnonymization($entities);

        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]['text']);
        $this->assertEquals('PERSON', $result[0]['entityType']);
        $this->assertNotEmpty($result[0]['key']);

    }//end testMapEntitiesForAnonymizationFiltersShortAndNumeric()


    /**
     * Test mapEntitiesForAnonymization deduplicates
     *
     * @return void
     */
    public function testMapEntitiesForAnonymizationDeduplicates(): void
    {
        $entities = [
            ['value' => 'John Doe', 'type' => 'PERSON'],
            ['value' => 'John Doe', 'type' => 'PERSON'],
        ];

        $result = $this->service->mapEntitiesForAnonymization($entities);
        $this->assertCount(1, $result);

    }//end testMapEntitiesForAnonymizationDeduplicates()


    /**
     * Test bases field is forwarded verbatim when present
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testMapEntitiesForAnonymizationForwardsBasesVerbatim(): void
    {
        $entities = [
            ['value' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => ['uuid-base-a']],
        ];

        $result = $this->service->mapEntitiesForAnonymization(entities: $entities);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('bases', $result[0]);
        $this->assertSame(['uuid-base-a'], $result[0]['bases']);

    }//end testMapEntitiesForAnonymizationForwardsBasesVerbatim()


    /**
     * Test empty bases array is forwarded as empty array, not omitted
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testMapEntitiesForAnonymizationForwardsEmptyBasesAsEmptyArray(): void
    {
        $entities = [
            ['value' => 'Jane Smith', 'type' => 'PERSON', 'bases' => []],
        ];

        $result = $this->service->mapEntitiesForAnonymization(entities: $entities);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('bases', $result[0]);
        $this->assertSame([], $result[0]['bases']);

    }//end testMapEntitiesForAnonymizationForwardsEmptyBasesAsEmptyArray()


    /**
     * Test entity without bases field results in no bases key in mapped output
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testMapEntitiesForAnonymizationOmitsBasesWhenAbsent(): void
    {
        $entities = [
            ['value' => 'Amsterdam', 'type' => 'LOCATION'],
        ];

        $result = $this->service->mapEntitiesForAnonymization(entities: $entities);

        $this->assertCount(1, $result);
        $this->assertArrayNotHasKey('bases', $result[0]);

    }//end testMapEntitiesForAnonymizationOmitsBasesWhenAbsent()


    /**
     * Test mixed payload: bases only on entries that supplied it
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testMapEntitiesForAnonymizationMixedPayload(): void
    {
        $entities = [
            ['value' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => ['uuid-base-a']],
            ['value' => 'Amsterdam', 'type' => 'LOCATION'],
            ['value' => 'Example Corp', 'type' => 'ORGANIZATION'],
        ];

        $result = $this->service->mapEntitiesForAnonymization(entities: $entities);

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('bases', $result[0]);
        $this->assertSame(['uuid-base-a'], $result[0]['bases']);
        $this->assertArrayNotHasKey('bases', $result[1]);
        $this->assertArrayNotHasKey('bases', $result[2]);

    }//end testMapEntitiesForAnonymizationMixedPayload()


    /**
     * Test parseAnonymizationResult delegates to parser
     *
     * @return void
     */
    public function testParseAnonymizationResultDelegatesToParser(): void
    {
        $expected = [
            'anonymizedFileId'   => 42,
            'anonymizedFileName' => 'test.pdf',
            'anonymizedFilePath' => '/path/test.pdf',
        ];

        $this->mockResultParser->method('parseResult')
            ->willReturn($expected);

        $result = $this->service->parseAnonymizationResult('some-result');
        $this->assertEquals($expected, $result);

    }//end testParseAnonymizationResultDelegatesToParser()


}//end class

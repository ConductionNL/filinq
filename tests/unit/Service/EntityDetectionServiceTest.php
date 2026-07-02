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
     * Test mapEntitiesForAnonymization filters empty and too-short values, but
     * keeps numeric values — DocuDesk holds no opinion on numeric-ness.
     *
     * @return void
     */
    public function testMapEntitiesForAnonymizationFiltersShortAndEmpty(): void
    {
        $entities = [
            ['value' => 'John Doe', 'type' => 'PERSON'],
            ['value' => 'ab', 'type' => 'PERSON'],
            ['value' => '123', 'type' => 'NUMBER'],
            ['value' => '', 'type' => 'EMPTY'],
        ];

        $result = $this->service->mapEntitiesForAnonymization($entities);

        $texts = array_column($result, 'text');
        $this->assertCount(2, $result);
        $this->assertContains('John Doe', $texts);
        $this->assertContains('123', $texts, 'a numeric value is no longer filtered');
        $this->assertNotContains('ab', $texts, 'a too-short value is dropped');

    }//end testMapEntitiesForAnonymizationFiltersShortAndEmpty()


    /**
     * DocuDesk holds no opinion on numeric-ness: a numeric value is mapped for
     * redaction like any other, whatever type the recogniser assigned. Numbers
     * are frequently sensitive (a BSN, a phone number, a granted-benefit
     * amount), so the redaction decision is left to OpenRegister — DocuDesk
     * only drops empty and too-short values.
     *
     * @return void
     */
    public function testMapEntitiesForAnonymizationKeepsNumericValues(): void
    {
        $entities = [
            ['value' => '111222333', 'type' => 'SSN'],
            ['value' => '0612345678', 'type' => 'PHONE'],
            ['value' => '25000', 'type' => 'NUMBER'],
            ['value' => '2026', 'type' => 'CARDINAL'],
        ];

        $result = $this->service->mapEntitiesForAnonymization($entities);

        $texts = array_column($result, 'text');
        $this->assertContains('111222333', $texts, 'a BSN (SSN type) must be kept');
        $this->assertContains('0612345678', $texts, 'a numeric phone must be kept');
        $this->assertContains('25000', $texts, 'a numeric benefit amount must be kept');
        $this->assertContains('2026', $texts, 'a numeric value is kept regardless of type');

    }//end testMapEntitiesForAnonymizationKeepsNumericValues()


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

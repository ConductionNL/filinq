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
     * Test mapEntitiesForAnonymization keeps typed PII and drops only
     * single-character UNKNOWN noise + empty strings.
     *
     * Updated for #285: numeric typed PII (BSN, postcode, phone, …) is
     * NEVER dropped. Only empty values and ultra-short UNKNOWN tokens
     * are filtered.
     *
     * @return void
     */
    public function testMapEntitiesForAnonymizationKeepsTypedPiiAndDropsNoise(): void
    {
        $entities = [
            ['value' => 'John Doe', 'type' => 'PERSON'],
            ['value' => 'a',        'type' => 'UNKNOWN'],
            ['value' => '',         'type' => 'PERSON'],
        ];

        $result = $this->service->mapEntitiesForAnonymization($entities);

        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]['text']);
        $this->assertEquals('PERSON', $result[0]['entityType']);
        $this->assertNotEmpty($result[0]['key']);

    }//end testMapEntitiesForAnonymizationKeepsTypedPiiAndDropsNoise()


    /**
     * Test #285: a fully numeric BSN MUST be forwarded for redaction.
     *
     * Previously, `is_numeric($text) === true` silently dropped numeric
     * PII such as BSNs, bank/account numbers, postcodes, phone numbers
     * and case references, so they survived verbatim in the
     * "anonymized" output. This regression test asserts the bug is
     * fixed.
     *
     * @return void
     *
     * @spec issue #285 — numeric PII silently skipped by anonymization
     */
    public function testMapEntitiesForAnonymizationKeepsNumericBsnPii(): void
    {
        $entities = [
            ['value' => '123456782', 'type' => 'BSN'],
        ];

        $result = $this->service->mapEntitiesForAnonymization($entities);

        $this->assertCount(1, $result, 'Numeric BSN must be forwarded for redaction (#285).');
        $this->assertSame('123456782', $result[0]['text']);
        $this->assertSame('BSN', $result[0]['entityType']);

    }//end testMapEntitiesForAnonymizationKeepsNumericBsnPii()


    /**
     * Test #285: a Dutch postcode like "1234 AB" (numeric prefix, mixed
     * length) and a short typed token must be forwarded.
     *
     * @return void
     *
     * @spec issue #285 — numeric PII silently skipped by anonymization
     */
    public function testMapEntitiesForAnonymizationKeepsShortAndNumericTypedPii(): void
    {
        $entities = [
            ['value' => '06',         'type' => 'PHONE'],     // short numeric
            ['value' => '1234AB',     'type' => 'POSTCODE'],  // alphanumeric postcode
            ['value' => '0612345678', 'type' => 'PHONE'],     // long numeric phone
            ['value' => 'NL91ABNA0417164300', 'type' => 'IBAN'],
        ];

        $result = $this->service->mapEntitiesForAnonymization($entities);

        $this->assertCount(4, $result, 'All typed PII must be forwarded regardless of length/numericness (#285).');

        $texts = array_column($result, 'text');
        $this->assertContains('06', $texts);
        $this->assertContains('1234AB', $texts);
        $this->assertContains('0612345678', $texts);
        $this->assertContains('NL91ABNA0417164300', $texts);

    }//end testMapEntitiesForAnonymizationKeepsShortAndNumericTypedPii()


    /**
     * Test #285: UNKNOWN single-character noise tokens are still
     * dropped (the typed-PII rule must not become an opt-out for
     * actual noise reduction).
     *
     * @return void
     *
     * @spec issue #285 — numeric PII silently skipped by anonymization
     */
    public function testMapEntitiesForAnonymizationDropsUntypedSingleCharNoise(): void
    {
        $entities = [
            ['value' => 'a', 'type' => 'UNKNOWN'],
            ['value' => 'X', 'type' => 'UNKNOWN'],
            // Two-character UNKNOWN is kept (UNTYPED_MIN_LENGTH = 2).
            ['value' => 'ab', 'type' => 'UNKNOWN'],
        ];

        $result = $this->service->mapEntitiesForAnonymization($entities);

        $this->assertCount(1, $result);
        $this->assertSame('ab', $result[0]['text']);

    }//end testMapEntitiesForAnonymizationDropsUntypedSingleCharNoise()


    /**
     * Test #285: byte-length vs character-length difference for multibyte
     * input. A two-character multibyte string must be counted as 2 chars
     * (mb_strlen), not as N bytes (strlen).
     *
     * @return void
     *
     * @spec issue #285 — strlen byte-length vs mb_strlen char-length
     */
    public function testMapEntitiesForAnonymizationUsesCharacterLengthForUntypedTokens(): void
    {
        // 'é' is 2 bytes in UTF-8 but 1 character. A single accented
        // character is still noise for UNKNOWN type → dropped.
        $shortUnknown = [
            ['value' => 'é', 'type' => 'UNKNOWN'],
        ];
        $this->assertCount(0, $this->service->mapEntitiesForAnonymization($shortUnknown));

        // Two accented characters = 2 chars / 4 bytes → kept.
        $twoCharUnknown = [
            ['value' => 'éé', 'type' => 'UNKNOWN'],
        ];
        $this->assertCount(1, $this->service->mapEntitiesForAnonymization($twoCharUnknown));

    }//end testMapEntitiesForAnonymizationUsesCharacterLengthForUntypedTokens()


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

<?php

/**
 * Unit tests for DocumentTextExtractor
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

use OCA\DocuDesk\Service\DocumentTextExtractor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for DocumentTextExtractor
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentTextExtractorTest extends TestCase
{

    /**
     * @var DocumentTextExtractor
     */
    private DocumentTextExtractor $extractor;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->extractor  = new DocumentTextExtractor($this->mockLogger);

    }//end setUp()


    /**
     * Test extractTextContent returns content field
     *
     * @return void
     */
    public function testExtractTextContentFromContentField(): void
    {
        $result = $this->extractor->extractTextContent(['content' => 'Hello world']);
        $this->assertEquals('Hello world', $result);

    }//end testExtractTextContentFromContentField()


    /**
     * Test extractTextContent returns text field
     *
     * @return void
     */
    public function testExtractTextContentFromTextField(): void
    {
        $result = $this->extractor->extractTextContent(['text' => 'Some text']);
        $this->assertEquals('Some text', $result);

    }//end testExtractTextContentFromTextField()


    /**
     * Test extractTextContent returns description field
     *
     * @return void
     */
    public function testExtractTextContentFromDescriptionField(): void
    {
        $result = $this->extractor->extractTextContent(['description' => 'A description']);
        $this->assertEquals('A description', $result);

    }//end testExtractTextContentFromDescriptionField()


    /**
     * Test extractTextContent returns empty string for no text
     *
     * @return void
     */
    public function testExtractTextContentReturnsEmptyForNoText(): void
    {
        $result = $this->extractor->extractTextContent(['other' => 'data']);
        $this->assertEquals('', $result);

    }//end testExtractTextContentReturnsEmptyForNoText()


    /**
     * Test extractTextContent returns empty string for non-string content
     *
     * @return void
     */
    public function testExtractTextContentReturnsEmptyForNonString(): void
    {
        $result = $this->extractor->extractTextContent(['content' => 123]);
        $this->assertEquals('', $result);

    }//end testExtractTextContentReturnsEmptyForNonString()


    /**
     * Test normalizeDateFields normalizes valid dates
     *
     * @return void
     */
    public function testNormalizeDateFieldsNormalizesValidDates(): void
    {
        $result = $this->extractor->normalizeDateFields([
            'created'  => '2024-01-15',
            'modified' => '2024-06-20T10:30:00+00:00',
        ]);

        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('modified', $result);

    }//end testNormalizeDateFieldsNormalizesValidDates()


    /**
     * Test normalizeDateFields skips empty fields
     *
     * @return void
     */
    public function testNormalizeDateFieldsSkipsEmptyFields(): void
    {
        $result = $this->extractor->normalizeDateFields([
            'created' => '',
            'other'   => 'value',
        ]);

        $this->assertEmpty($result);

    }//end testNormalizeDateFieldsSkipsEmptyFields()


    /**
     * Test normalizeDateFields handles invalid dates gracefully
     *
     * @return void
     */
    public function testNormalizeDateFieldsHandlesInvalidDates(): void
    {
        $result = $this->extractor->normalizeDateFields([
            'created' => 'not-a-date',
        ]);

        // Invalid date string "not-a-date" will cause an exception, should be skipped.
        $this->assertArrayNotHasKey('created', $result);

    }//end testNormalizeDateFieldsHandlesInvalidDates()


}//end class

<?php

/**
 * Unit tests for TextAnalysisService
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

use OCA\DocuDesk\Service\LanguageClassifier;
use OCA\DocuDesk\Service\TextAnalysisService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TextAnalysisService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TextAnalysisServiceTest extends TestCase
{

    /**
     * The TextAnalysisService instance being tested
     *
     * @var TextAnalysisService
     */
    private TextAnalysisService $service;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $classifier    = new LanguageClassifier();
        $this->service = new TextAnalysisService($classifier);

    }//end setUp()


    /**
     * Test keyword extraction returns top keywords
     *
     * @return void
     */
    public function testExtractKeywordsReturnsTopKeywords(): void
    {
        $text = 'document processing document analysis document report analysis report';
        $result = $this->service->extractKeywords($text);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertContains('document', $result);

    }//end testExtractKeywordsReturnsTopKeywords()


    /**
     * Test keyword extraction removes stop words
     *
     * @return void
     */
    public function testExtractKeywordsRemovesStopWords(): void
    {
        $text = 'the document is a good report for the analysis of the data';
        $result = $this->service->extractKeywords($text);

        $this->assertNotContains('the', $result);
        $this->assertNotContains('is', $result);
        $this->assertNotContains('a', $result);

    }//end testExtractKeywordsRemovesStopWords()


    /**
     * Test keyword extraction limits to 10 results
     *
     * @return void
     */
    public function testExtractKeywordsLimitsToTen(): void
    {
        $text = 'one two three four five six seven eight nine ten eleven twelve thirteen';
        $result = $this->service->extractKeywords($text);

        $this->assertLessThanOrEqual(10, count($result));

    }//end testExtractKeywordsLimitsToTen()


    /**
     * Test document type standardization for known types
     *
     * @return void
     */
    public function testStandardizeDocumentTypeKnownTypes(): void
    {
        $this->assertEquals('word', $this->service->standardizeDocumentType('doc'));
        $this->assertEquals('word', $this->service->standardizeDocumentType('docx'));
        $this->assertEquals('spreadsheet', $this->service->standardizeDocumentType('xlsx'));
        $this->assertEquals('presentation', $this->service->standardizeDocumentType('pptx'));
        $this->assertEquals('pdf', $this->service->standardizeDocumentType('PDF'));
        $this->assertEquals('image', $this->service->standardizeDocumentType('jpg'));
        $this->assertEquals('text', $this->service->standardizeDocumentType('txt'));

    }//end testStandardizeDocumentTypeKnownTypes()


    /**
     * Test document type standardization returns original for unknown types
     *
     * @return void
     */
    public function testStandardizeDocumentTypeUnknown(): void
    {
        $this->assertEquals('unknown', $this->service->standardizeDocumentType('unknown'));
        $this->assertEquals('custom', $this->service->standardizeDocumentType('  custom  '));

    }//end testStandardizeDocumentTypeUnknown()


    /**
     * Test language detection delegates to LanguageClassifier
     *
     * @return void
     */
    public function testDetectLanguageDelegatesToClassifier(): void
    {
        $text = 'Dit is de tekst van het document en het bevat de woorden van de Nederlandse taal';
        $result = $this->service->detectLanguage($text);
        $this->assertEquals('nl', $result);

    }//end testDetectLanguageDelegatesToClassifier()


    /**
     * Test topic classification delegates to LanguageClassifier
     *
     * @return void
     */
    public function testClassifyTopicDelegatesToClassifier(): void
    {
        $text = 'The patient needs a diagnosis and medical treatment from the doctor for health';
        $result = $this->service->classifyTopic($text);
        $this->assertEquals('medical', $result);

    }//end testClassifyTopicDelegatesToClassifier()


    /**
     * Test word occurrence counting
     *
     * @return void
     */
    public function testCountWordOccurrences(): void
    {
        $text = ' hello world hello there hello again ';
        $count = $this->service->countWordOccurrences($text, ['hello']);
        $this->assertEquals(3, $count);

    }//end testCountWordOccurrences()


}//end class

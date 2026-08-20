<?php

/**
 * Unit tests for LanguageClassifier
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
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LanguageClassifier
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class LanguageClassifierTest extends TestCase {

	/**
	 * The LanguageClassifier instance being tested
	 *
	 * @var LanguageClassifier
	 */
	private LanguageClassifier $classifier;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->classifier = new LanguageClassifier();

	}//end setUp()

	/**
	 * Test Dutch language detection
	 *
	 * @return void
	 */
	public function testDetectDutchLanguage(): void {
		$text = 'Dit is de tekst van het document en het bevat de woorden van de Nederlandse taal met een paar van de meest voorkomende woorden';
		$result = $this->classifier->detectLanguage($text);
		$this->assertEquals('nl', $result);

	}//end testDetectDutchLanguage()

	/**
	 * Test English language detection
	 *
	 * @return void
	 */
	public function testDetectEnglishLanguage(): void {
		$text = 'The document is a report that have been written in the English language and it is a document of the company';
		$result = $this->classifier->detectLanguage($text);
		$this->assertEquals('en', $result);

	}//end testDetectEnglishLanguage()

	/**
	 * Test null returned for insufficient text
	 *
	 * @return void
	 */
	public function testDetectNullForInsufficientText(): void {
		$text = 'Hello world';
		$result = $this->classifier->detectLanguage($text);
		$this->assertNull($result);

	}//end testDetectNullForInsufficientText()

	/**
	 * Test topic classification for legal content
	 *
	 * @return void
	 */
	public function testClassifyLegalTopic(): void {
		$text = 'This contract is a legal agreement between the parties and the court shall judge any disputes under the law';
		$result = $this->classifier->classifyTopic($text);
		$this->assertEquals('legal', $result);

	}//end testClassifyLegalTopic()

	/**
	 * Test topic classification for financial content
	 *
	 * @return void
	 */
	public function testClassifyFinancialTopic(): void {
		$text = 'The invoice for the payment was sent and the budget shows the financial account balance with money';
		$result = $this->classifier->classifyTopic($text);
		$this->assertEquals('financial', $result);

	}//end testClassifyFinancialTopic()

	/**
	 * Test topic classification returns null for unclassifiable content
	 *
	 * @return void
	 */
	public function testClassifyTopicReturnsNullForUnknown(): void {
		$text = 'Lorem ipsum dolor sit amet';
		$result = $this->classifier->classifyTopic($text);
		$this->assertNull($result);

	}//end testClassifyTopicReturnsNullForUnknown()

}//end class

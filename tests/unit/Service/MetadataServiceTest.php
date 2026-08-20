<?php

/**
 * Unit tests for MetadataService
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
use OCA\DocuDesk\Service\MetadataService;
use OCA\DocuDesk\Service\TextAnalysisService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for MetadataService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MetadataServiceTest extends TestCase {

	/**
	 * @var MetadataService
	 */
	private MetadataService $service;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * @var ContainerInterface|MockObject
	 */
	private ContainerInterface|MockObject $mockContainer;

	/**
	 * @var IAppManager|MockObject
	 */
	private IAppManager|MockObject $mockAppManager;

	/**
	 * @var TextAnalysisService|MockObject
	 */
	private TextAnalysisService|MockObject $mockTextAnalysis;

	/**
	 * @var DocumentTextExtractor|MockObject
	 */
	private DocumentTextExtractor|MockObject $mockTextExtractor;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockContainer = $this->createMock(ContainerInterface::class);
		$this->mockAppManager = $this->createMock(IAppManager::class);
		$this->mockTextAnalysis = $this->createMock(TextAnalysisService::class);
		$this->mockTextExtractor = $this->createMock(DocumentTextExtractor::class);

		$this->service = new MetadataService(
			$this->mockLogger,
			$this->mockContainer,
			$this->mockAppManager,
			$this->mockTextAnalysis,
			$this->mockTextExtractor
		);

	}//end setUp()

	/**
	 * Test enhanceMetadata with text content
	 *
	 * @return void
	 */
	public function testEnhanceMetadataWithTextContent(): void {
		$this->mockTextExtractor->method('extractTextContent')
			->willReturn('Dit is een testtekst over privacy.');
		$this->mockTextExtractor->method('normalizeDateFields')
			->willReturn([]);

		$this->mockTextAnalysis->method('detectLanguage')
			->willReturn('nl');
		$this->mockTextAnalysis->method('extractKeywords')
			->willReturn(['privacy', 'test']);
		$this->mockTextAnalysis->method('classifyTopic')
			->willReturn('privacy');

		$result = $this->service->enhanceMetadata([
			'content' => 'Dit is een testtekst over privacy.',
		]);

		$this->assertArrayHasKey('language', $result);
		$this->assertEquals('nl', $result['language']);
		$this->assertArrayHasKey('keywords', $result);
		$this->assertArrayHasKey('topic', $result);

	}//end testEnhanceMetadataWithTextContent()

	/**
	 * Test enhanceMetadata with empty text
	 *
	 * @return void
	 */
	public function testEnhanceMetadataWithEmptyText(): void {
		$this->mockTextExtractor->method('extractTextContent')
			->willReturn('');
		$this->mockTextExtractor->method('normalizeDateFields')
			->willReturn([]);

		$result = $this->service->enhanceMetadata([]);
		$this->assertIsArray($result);

	}//end testEnhanceMetadataWithEmptyText()

	/**
	 * Test enhanceMetadata with documentType
	 *
	 * @return void
	 */
	public function testEnhanceMetadataWithDocumentType(): void {
		$this->mockTextExtractor->method('extractTextContent')
			->willReturn('');
		$this->mockTextExtractor->method('normalizeDateFields')
			->willReturn([]);

		$this->mockTextAnalysis->method('standardizeDocumentType')
			->with('besluit')
			->willReturn('decision');

		$result = $this->service->enhanceMetadata(['documentType' => 'besluit']);
		$this->assertEquals('decision', $result['documentType']);

	}//end testEnhanceMetadataWithDocumentType()

	/**
	 * Test enhanceMetadata does not override existing fields
	 *
	 * @return void
	 */
	public function testEnhanceMetadataDoesNotOverrideExisting(): void {
		$this->mockTextExtractor->method('extractTextContent')
			->willReturn('Some text');
		$this->mockTextExtractor->method('normalizeDateFields')
			->willReturn([]);

		$this->mockTextAnalysis->method('detectLanguage')
			->willReturn('en');

		$result = $this->service->enhanceMetadata([
			'content' => 'Some text',
			'language' => 'nl',
		]);

		// Should not override existing language.
		$this->assertArrayNotHasKey('language', $result);

	}//end testEnhanceMetadataDoesNotOverrideExisting()

}//end class

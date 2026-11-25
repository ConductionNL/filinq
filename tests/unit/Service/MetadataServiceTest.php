<?php

/**
 * Unit tests for MetadataService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentTextExtractor;
use OCA\Filinq\Service\MetadataService;
use OCA\Filinq\Service\TextAnalysisService;
use OCA\OpenRegister\Contract\ObjectEntityInterface;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCP\App\IAppManager;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for MetadataService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
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

	/**
	 * An object that is not readable is passed up as not-found without an error log.
	 *
	 * @return void
	 */
	public function testSaveEnrichedMetadataPassesUpAnUnreadableObjectWithoutLogging(): void {
		$objectService = $this->createMock(ObjectServiceInterface::class);
		$objectService->method('find')->willThrowException(new DoesNotExistException('not found in any magic table'));
		$this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);
		$this->mockContainer->method('get')->willReturn($objectService);
		$this->mockLogger->expects($this->never())->method('error');

		$this->expectException(DoesNotExistException::class);
		$this->service->saveEnrichedMetadata('obj-1', '1', '2', ['language' => 'nl']);

	}//end testSaveEnrichedMetadataPassesUpAnUnreadableObjectWithoutLogging()

	/**
	 * A find that returns nothing is a not-found as well.
	 *
	 * @return void
	 */
	public function testSaveEnrichedMetadataTreatsAMissingObjectAsNotFound(): void {
		$objectService = $this->createMock(ObjectServiceInterface::class);
		$objectService->method('find')->willReturn(null);
		$this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);
		$this->mockContainer->method('get')->willReturn($objectService);

		$this->expectException(DoesNotExistException::class);
		$this->service->saveEnrichedMetadata('obj-1', '1', '2', ['language' => 'nl']);

	}//end testSaveEnrichedMetadataTreatsAMissingObjectAsNotFound()
	/**
	 * Wire the container to an object service holding one stored object and a
	 * schema mapper answering with the given schema (or failing).
	 *
	 * @param array<string, mixed>      $stored     The stored object data.
	 * @param array<string, mixed>|null $properties The schema properties; null makes the mapper fail.
	 * @param array<string, mixed>      $saved      Receives the data passed to saveObject.
	 *
	 * @return void
	 */
	private function wireSave(array $stored, ?array $properties, array &$saved): void {
		$entity = $this->createMock(ObjectEntityInterface::class);
		$entity->method('getObject')->willReturn($stored);
		$objectService = $this->createMock(ObjectServiceInterface::class);
		$objectService->method('find')->willReturn($entity);
		$objectService->method('saveObject')->willReturnCallback(
			function (array $object) use (&$saved, $entity): object {
				$saved = $object;
				return $entity;
			}
		);
		$mapper = new class($properties) {
			/**
			 * Constructor.
			 *
			 * @param array<string, mixed>|null $properties The schema properties.
			 */
			public function __construct(private ?array $properties) {
			}

			/**
			 * The schema, or a failure when there is none.
			 *
			 * @param string $id The schema id.
			 *
			 * @return object
			 */
			public function find(string $id): object {
				if ($this->properties === null) {
					throw new \RuntimeException(message: 'schema ' . $id . ' not found');
				}

				return new class($this->properties) {
					/**
					 * Constructor.
					 *
					 * @param array<string, mixed> $properties The properties.
					 */
					public function __construct(private array $properties) {
					}

					/**
					 * The schema properties.
					 *
					 * @return array<string, mixed>
					 */
					public function getProperties(): array {
						return $this->properties;
					}
				};
			}
		};
		$this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);
		$this->mockContainer->method('get')->willReturnCallback(
			static fn (string $id): object => match (str_contains(haystack: $id, needle: 'SchemaMapper')) {
				true => $mapper,
				false => $objectService,
			}
		);

	}//end wireSave()

	/**
	 * A read-only or computed field a read returned is not written back, so
	 * OpenRegister does not refuse the save (a confirmed classification on an
	 * intake document carrying `daysLeft`).
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-inbound-auto-classification/tasks.md#2-4
	 */
	public function testReadOnlyAndComputedFieldsAreNotWrittenBack(): void {
		$saved = [];
		$this->wireSave(
			stored: ['channel' => 'scan', 'status' => 'received', 'daysLeft' => 3, 'age' => 12],
			properties: [
				'channel' => ['type' => 'string'],
				'status' => ['type' => 'string'],
				'daysLeft' => ['type' => 'number', 'readOnly' => true],
				'age' => ['type' => 'number', 'computed' => ['expression' => '{{ 1 }}']],
			],
			saved: $saved
		);

		$this->service->saveEnrichedMetadata('obj-1', '26', '65', ['documentType' => 'besluit']);

		$this->assertSame(expected: ['channel' => 'scan', 'status' => 'received', 'documentType' => 'besluit'], actual: $saved);

	}//end testReadOnlyAndComputedFieldsAreNotWrittenBack()

	/**
	 * When the schema cannot be read, the stored data is written back whole,
	 * as before.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-inbound-auto-classification/tasks.md#2-4
	 */
	public function testWithoutAReadableSchemaNothingIsLeftOut(): void {
		$saved = [];
		$this->wireSave(stored: ['channel' => 'scan', 'daysLeft' => 3], properties: null, saved: $saved);

		$this->service->saveEnrichedMetadata('obj-1', '26', '65', ['documentType' => 'brief']);

		$this->assertSame(expected: ['channel' => 'scan', 'daysLeft' => 3, 'documentType' => 'brief'], actual: $saved);

	}//end testWithoutAReadableSchemaNothingIsLeftOut()
}//end class

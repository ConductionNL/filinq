<?php

/**
 * Unit tests for AnonymizationService's custom-dictionary detection hook
 * (custom-dictionary-recognition, design.md §D3/§D4).
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use Exception;
use OCA\DocuDesk\Service\AnonymizationResultParser;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\CustomDictionaryDetectionRunner;
use OCA\DocuDesk\Service\CustomDictionaryMatchService;
use OCA\DocuDesk\Service\CustomDictionaryService;
use OCA\DocuDesk\Service\EntityDetectionService;
use OCA\DocuDesk\Service\LegalBasisProposalService;
use OCA\OpenRegister\Db\Chunk;
use OCA\OpenRegister\Db\ChunkMapper;
use OCA\OpenRegister\Db\EntityRelation;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCA\OpenRegister\Db\GdprEntity;
use OCA\OpenRegister\Db\GdprEntityMapper;
use OCA\OpenRegister\Service\TextExtractionService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * Tests for `extractAndDetectEntities()`'s custom-dictionary pass.
 *
 * Split into its own file mirroring the codebase's existing per-concern
 * split for AnonymizationService tests (Prohibition / OutputFormat /
 * PublicationClearance / Link each have their own file).
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
class AnonymizationServiceCustomDictionaryTest extends TestCase {
	use BuildsAnonymizationService;

	/**
	 * @var ContainerInterface|MockObject
	 */
	private ContainerInterface|MockObject $mockContainer;

	/**
	 * @var EntityRelationMapper|MockObject
	 */
	private EntityRelationMapper|MockObject $mockRelationMapper;

	/**
	 * @var LegalBasisProposalService|MockObject
	 */
	private LegalBasisProposalService|MockObject $mockGrondslag;

	/**
	 * Set up shared container wiring for OR's non-custom-dictionary services.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockContainer = $this->createMock(ContainerInterface::class);
		$this->mockRelationMapper = $this->createMock(EntityRelationMapper::class);
		$this->mockRelationMapper->method('findEntitiesForFile')->willReturn(
			[['entity_type' => 'PERSON', 'entity_value' => 'Jan Janssen', 'confidence' => 0.95]]
		);

		$this->mockGrondslag = $this->createMock(LegalBasisProposalService::class);
		$this->mockGrondslag->method('enrichEntitiesWithBases')->willReturnArgument(0);

	}//end setUp()

	/**
	 * Build the service under test with the given CustomDictionaryService
	 * double and entity-type whitelist.
	 *
	 * @param CustomDictionaryService $customDictionary The (mocked) dictionary CRUD/listing service.
	 * @param array<int, string>|null $whitelist Value `getEntityTypeWhitelist()` returns.
	 * @param ChunkMapper|null $chunkMapper Optional ChunkMapper double (registered on the container).
	 * @param GdprEntityMapper|null $gdprEntityMapper Optional GdprEntityMapper double (registered on the container).
	 *
	 * @return AnonymizationService
	 */
	private function makeService(
		CustomDictionaryService $customDictionary,
		?array $whitelist,
		?ChunkMapper $chunkMapper = null,
		?GdprEntityMapper $gdprEntityMapper = null,
	): AnonymizationService {
		$this->mockGrondslag->method('getEntityTypeWhitelist')->willReturn($whitelist);

		$mockExtractor = $this->createMock(TextExtractionService::class);

		$this->mockContainer->method('get')->willReturnCallback(
			function (string $class) use ($mockExtractor, $chunkMapper, $gdprEntityMapper) {
				if ($class === 'OCA\OpenRegister\Service\TextExtractionService') {
					return $mockExtractor;
				}

				if ($class === 'OCA\OpenRegister\Db\EntityRelationMapper') {
					return $this->mockRelationMapper;
				}

				if ($class === 'OCA\DocuDesk\Service\LegalBasisProposalService') {
					return $this->mockGrondslag;
				}

				if ($class === 'OCA\OpenRegister\Db\ChunkMapper' && $chunkMapper !== null) {
					return $chunkMapper;
				}

				if ($class === 'OCA\OpenRegister\Db\GdprEntityMapper' && $gdprEntityMapper !== null) {
					return $gdprEntityMapper;
				}

				if ($class === 'OCA\DocuDesk\Service\PolicyMatchService') {
					throw new Exception('PolicyMatchService not registered');
				}

				throw new Exception("Unknown service: $class");
			}
		);

		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);

		return $this->makeAnonymizationServiceFrom(
			[
				'logger' => new NullLogger(),
				'container' => $this->mockContainer,
				'appManager' => $appManager,
				'entityDetection' => new EntityDetectionService(new AnonymizationResultParser()),
				'dictionaryRunner' => new CustomDictionaryDetectionRunner(
					logger: new NullLogger(),
					container: $this->mockContainer,
					appManager: $appManager,
					customDictionary: $customDictionary,
					matcher: new CustomDictionaryMatchService()
				),
			]
		);

	}//end makeService()

	/**
	 * When the operator has disabled CUSTOM_DICTIONARY in the enabled-type
	 * whitelist, the pass is skipped entirely — the dictionary service is
	 * never even consulted (design.md §D4).
	 *
	 * @return void
	 */
	public function testDetectionSkippedWhenCustomDictionaryTypeDisabled(): void {
		$customDictionary = $this->createMock(CustomDictionaryService::class);
		$customDictionary->expects($this->never())->method('listActiveDictionariesForDetection');

		$service = $this->makeService(customDictionary: $customDictionary, whitelist: ['PERSON']);
		$result = $service->extractAndDetectEntities(fileId: 1);

		$this->assertNull($result['customDictionaryWarning']);
		$this->assertCount(1, $result['entities']);

	}//end testDetectionSkippedWhenCustomDictionaryTypeDisabled()

	/**
	 * A failure in the custom-dictionary pass is surfaced as a warning but
	 * OpenRegister's own detected entities are still returned (REQ-DDCDR-003
	 * scenario: "Matcher failure does not block OpenRegister detection").
	 *
	 * @return void
	 */
	public function testMatchFailureSurfacesWarningWithoutBlockingDetection(): void {
		$customDictionary = $this->createMock(CustomDictionaryService::class);
		$customDictionary->method('listActiveDictionariesForDetection')->willThrowException(
			new Exception('catalogue write path unavailable')
		);

		$service = $this->makeService(customDictionary: $customDictionary, whitelist: null);
		$result = $service->extractAndDetectEntities(fileId: 1);

		$this->assertIsString($result['customDictionaryWarning']);
		$this->assertStringContainsString('catalogue write path unavailable', $result['customDictionaryWarning']);
		// OpenRegister's own entity is still present — the failure never blocks detection.
		$this->assertCount(1, $result['entities']);
		$this->assertSame('PERSON', $result['entities'][0]['type']);

	}//end testMatchFailureSurfacesWarningWithoutBlockingDetection()

	/**
	 * A dictionary hit is written into the catalogue as a CUSTOM_DICTIONARY
	 * relation, and a re-run first clears the file's prior
	 * `custom_dictionary` relations (idempotency) without touching relations
	 * from other detection methods (REQ-DDCDR-003 scenarios).
	 *
	 * @return void
	 */
	public function testDictionaryHitWritesCatalogueAndClearsPriorRelationsOnly(): void {
		$customDictionary = $this->createMock(CustomDictionaryService::class);
		$customDictionary->method('listActiveDictionariesForDetection')->willReturn(
			[
				[
					'label' => 'Projectnamen',
					'matchMode' => 'caseInsensitive',
					'terms' => [['value' => 'Operatie Zilverreiger', 'label' => 'Projectnamen']],
				],
			]
		);

		$chunk = new Chunk();
		$chunk->setId(501);
		$chunk->setStartOffset(0);
		$chunk->setChunkIndex(0);
		$chunk->setTextContent('Het dossier over Operatie Zilverreiger is geheim.');

		$chunkMapper = $this->createMock(ChunkMapper::class);
		$chunkMapper->method('findBySource')->with('file', 1)->willReturn([$chunk]);

		$priorCustomDictionaryRelation = new EntityRelation();
		$priorCustomDictionaryRelation->setId(9001);
		$priorCustomDictionaryRelation->setDetectionMethod('custom_dictionary');

		$priorPresidioRelation = new EntityRelation();
		$priorPresidioRelation->setId(9002);
		$priorPresidioRelation->setDetectionMethod('presidio');

		$this->mockRelationMapper->method('findByFileId')->with(1)->willReturn(
			[$priorCustomDictionaryRelation, $priorPresidioRelation]
		);

		$deletedIds = [];
		$this->mockRelationMapper->method('delete')->willReturnCallback(
			static function ($entity) use (&$deletedIds) {
				$deletedIds[] = $entity->getId();
				return $entity;
			}
		);

		$insertedRows = null;
		$this->mockRelationMapper->method('insertBatch')->willReturnCallback(
			static function (array $rows) use (&$insertedRows): array {
				$insertedRows = $rows;
				return [];
			}
		);

		$gdprEntityMapper = $this->createMock(GdprEntityMapper::class);
		$gdprEntityMapper->method('findOneByValueAndType')->willReturn(null);
		$gdprEntityMapper->method('insert')->willReturnCallback(
			static function (GdprEntity $entity): GdprEntity {
				$entity->setId(777);
				return $entity;
			}
		);

		$service = $this->makeService(
			customDictionary: $customDictionary,
			whitelist: null,
			chunkMapper: $chunkMapper,
			gdprEntityMapper: $gdprEntityMapper
		);

		$result = $service->extractAndDetectEntities(fileId: 1);

		$this->assertNull($result['customDictionaryWarning']);

		// Idempotency: only the PRIOR custom_dictionary relation was cleared.
		$this->assertSame([9001], $deletedIds);

		// The catalogue write carries the expected CUSTOM_DICTIONARY shape.
		$this->assertNotNull($insertedRows);
		$this->assertCount(1, $insertedRows);
		$this->assertSame(777, $insertedRows[0]['entityId']);
		$this->assertSame(1, $insertedRows[0]['fileId']);
		$this->assertSame(501, $insertedRows[0]['chunkId']);
		$this->assertSame('custom_dictionary', $insertedRows[0]['detectionMethod']);
		$this->assertSame(1.0, $insertedRows[0]['confidence']);
		$this->assertSame('Projectnamen', $insertedRows[0]['context']);

		// Positions point at the literal match in the chunk text.
		$chunkText = $chunk->getTextContent();
		$found = substr(
			$chunkText,
			$insertedRows[0]['positionStart'],
			($insertedRows[0]['positionEnd'] - $insertedRows[0]['positionStart'])
		);
		$this->assertSame('Operatie Zilverreiger', $found);

	}//end testDictionaryHitWritesCatalogueAndClearsPriorRelationsOnly()
}//end class

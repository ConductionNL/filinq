<?php

/**
 * Tests for DetectionCatalogue: OpenRegister chunk reads and LLM entity writes.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Detection;
use DateTime;
use OCA\Filinq\Service\Detection\DetectionCatalogue;
use OCA\OpenRegister\Db\EntityRelation;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCA\OpenRegister\Db\GdprEntity;
use OCA\OpenRegister\Db\GdprEntityMapper;
use OCA\OpenRegister\Files\Db\Chunk;
use OCA\OpenRegister\Files\Db\ChunkMapper;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Detection\DetectionCatalogue
 */
class DetectionCatalogueTest extends TestCase {

	/**
	 * OpenRegister services by class name.
	 *
	 * @var array<string, object>
	 */
	private array $services = [];

	/**
	 * Mocked relation mapper.
	 *
	 * @var EntityRelationMapper&MockObject
	 */
	private EntityRelationMapper&MockObject $relations;

	/**
	 * Mocked catalogue entity mapper.
	 *
	 * @var GdprEntityMapper&MockObject
	 */
	private GdprEntityMapper&MockObject $entities;

	/**
	 * Wire the mappers.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->relations = $this->createMock(EntityRelationMapper::class);
		$this->entities  = $this->createMock(GdprEntityMapper::class);
		$this->services  = [
			'OCA\OpenRegister\Db\EntityRelationMapper' => $this->relations,
			'OCA\OpenRegister\Db\GdprEntityMapper'     => $this->entities,
		];
	}

	/**
	 * The catalogue with OpenRegister installed or not.
	 *
	 * @param bool $installed Whether OpenRegister is installed.
	 *
	 * @return DetectionCatalogue The catalogue.
	 */
	private function catalogue(bool $installed=true): DetectionCatalogue {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(fn (string $id): object => $this->services[$id]);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn($installed === true ? ['openregister', 'filinq'] : ['filinq']);

		return new DetectionCatalogue(container: $container, appManager: $apps);
	}

	/**
	 * A relation with a detection method.
	 *
	 * @param string $method The method.
	 *
	 * @return EntityRelation The relation.
	 */
	private function relation(string $method): EntityRelation {
		$relation = new EntityRelation();
		$relation->setDetectionMethod($method);
		return $relation;
	}

	/**
	 * Chunks come back with id, text and offset; blank chunks are skipped.
	 *
	 * @return void
	 */
	public function testChunksAreReadFromOpenRegister(): void {
		$chunks = [];
		foreach ([[1, 'Jan en Piet', 0], [2, "  \n", 11], [3, 'Kees', 20]] as [$id, $text, $offset]) {
			$chunk = new Chunk();
			$chunk->setId($id);
			$chunk->setTextContent($text);
			$chunk->setStartOffset($offset);
			$chunks[] = $chunk;
		}

		$mapper = $this->createMock(ChunkMapper::class);
		$mapper->expects($this->once())->method('findBySource')->with('file', 42)->willReturn($chunks);
		$this->services['OCA\OpenRegister\Files\Db\ChunkMapper'] = $mapper;

		$this->assertSame(
			[['id' => 1, 'text' => 'Jan en Piet', 'startOffset' => 0], ['id' => 3, 'text' => 'Kees', 'startOffset' => 20]],
			$this->catalogue()->chunks(fileId: 42)
		);
	}

	/**
	 * Replacing removes only earlier llm relations, reuses a known entity and creates a new one once.
	 *
	 * @return void
	 */
	public function testReplaceWritesLlmRelations(): void {
		$oldLlm = $this->relation(method: 'llm');
		$this->relations->method('findByFileId')->with(42)->willReturn([$this->relation(method: 'presidio'), $oldLlm, $this->relation(method: 'custom_dictionary')]);
		$this->relations->expects($this->once())->method('delete')->with($this->identicalTo($oldLlm));

		$known = new GdprEntity();
		$known->setId(5);
		$this->entities->method('findOneByValueAndType')->willReturnCallback(
			static fn (string $value, string $type): ?GdprEntity => ($value === 'Jan' && $type === 'PERSON' ? $known : null)
		);
		$created = [];
		$this->entities->expects($this->once())->method('insert')->willReturnCallback(
			static function (GdprEntity $entity) use (&$created): GdprEntity {
				$created[] = [$entity->getValue(), $entity->getType(), $entity->getCategory()];
				$entity->setId(9);
				return $entity;
			}
		);

		$rows = null;
		$this->relations->expects($this->once())->method('insertBatch')->willReturnCallback(
			static function (array $batch) use (&$rows): array {
				$rows = $batch;
				return [];
			}
		);

		$written = $this->catalogue()->replace(fileId: 42, entities: [
			['value' => 'Jan', 'type' => 'PERSON', 'category' => 'personal_data', 'positionStart' => 0, 'positionEnd' => 3, 'confidence' => 0.9, 'chunkId' => 1],
			['value' => 'Project X', 'type' => 'CODENAME', 'category' => 'contextual_data', 'positionStart' => null, 'positionEnd' => null, 'confidence' => 0.8, 'chunkId' => 1],
			['value' => 'Project X', 'type' => 'CODENAME', 'category' => 'contextual_data', 'positionStart' => 30, 'positionEnd' => 39, 'confidence' => 0.8, 'chunkId' => 2],
		]);

		$this->assertSame(3, $written);
		$this->assertSame([['Project X', 'CODENAME', 'contextual_data']], $created);
		$this->assertSame([5, 9, 9], array_column($rows, 'entityId'));
		$this->assertSame([0, null, 30], array_column($rows, 'positionStart'));
		$this->assertSame([1, 1, 2], array_column($rows, 'chunkId'));
		$this->assertSame(['llm', 'llm', 'llm'], array_column($rows, 'detectionMethod'));
		$this->assertSame([42, 42, 42], array_column($rows, 'fileId'));
		$this->assertFalse($rows[0]['skipAnonymization']);
		$this->assertInstanceOf(DateTime::class, $rows[0]['createdAt']);
	}

	/**
	 * Replacing with nothing only clears, and does not insert an empty batch.
	 *
	 * @return void
	 */
	public function testReplaceWithNothingInsertsNothing(): void {
		$this->relations->method('findByFileId')->willReturn([]);
		$this->relations->expects($this->never())->method('insertBatch');

		$this->assertSame(0, $this->catalogue()->replace(fileId: 42, entities: []));
	}

	/**
	 * Whether llm relations exist is read from the file's relations.
	 *
	 * @return void
	 */
	public function testHasLlmRelations(): void {
		$this->relations->method('findByFileId')->willReturnOnConsecutiveCalls(
			[$this->relation(method: 'presidio')],
			[$this->relation(method: 'presidio'), $this->relation(method: 'llm')]
		);
		$catalogue = $this->catalogue();

		$this->assertFalse($catalogue->hasLlmRelations(fileId: 42));
		$this->assertTrue($catalogue->hasLlmRelations(fileId: 42));
	}

	/**
	 * Without OpenRegister every read and write refuses.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegisterItRefuses(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenRegister is not available.');

		$this->catalogue(installed: false)->replace(fileId: 42, entities: []);
	}

	/**
	 * The active organisation's uuid, or null for none, an empty uuid or a failing service.
	 *
	 * @return void
	 */
	public function testActiveOrganisationUuid(): void {
		$organisation = static fn (?string $uuid): object => new class($uuid) {
			/**
			 * @param string|null $uuid The uuid, or null for no organisation.
			 */
			public function __construct(private ?string $uuid) {
			}

			/**
			 * @return object|null The organisation.
			 */
			public function getActiveOrganisation(): ?object {
				if ($this->uuid === null) {
					return null;
				}

				return new class($this->uuid) {
					/**
					 * @param string $uuid The uuid.
					 */
					public function __construct(private string $uuid) {
					}

					/**
					 * @return string The uuid.
					 */
					public function getUuid(): string {
						return $this->uuid;
					}
				};
			}
		};

		$this->services['OCA\OpenRegister\Service\OrganisationService'] = $organisation('org-a');
		$this->assertSame('org-a', $this->catalogue()->activeOrganisationUuid());

		$this->services['OCA\OpenRegister\Service\OrganisationService'] = $organisation('');
		$this->assertNull($this->catalogue()->activeOrganisationUuid());

		$this->services['OCA\OpenRegister\Service\OrganisationService'] = $organisation(null);
		$this->assertNull($this->catalogue()->activeOrganisationUuid());

		$this->assertNull($this->catalogue(installed: false)->activeOrganisationUuid());
	}
}

<?php

/**
 * Unit tests for SubjectErasureLocator.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SubjectErasure
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SubjectErasure;

use OCA\Filinq\Exception\SubjectErasureRefusedException;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureLocator;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Finding the files an erasure subject's identifiers occur in, through OpenRegister's entity catalogue.
 *
 * @covers \OCA\Filinq\Service\SubjectErasure\SubjectErasureLocator
 *
 * @uses \OCA\Filinq\Exception\SubjectErasureRefusedException
 */
class SubjectErasureLocatorTest extends TestCase {

	/**
	 * The container double.
	 *
	 * @var ContainerInterface&MockObject
	 */
	private ContainerInterface $container;

	/**
	 * The app manager double.
	 *
	 * @var IAppManager&MockObject
	 */
	private IAppManager $appManager;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->container = $this->createMock(ContainerInterface::class);
		$this->appManager = $this->createMock(IAppManager::class);
	}//end setUp()

	/**
	 * An entity double with an id.
	 *
	 * @param int $id The id.
	 *
	 * @return object
	 */
	private static function entity(int $id): object {
		return new class($id) {
			/**
			 * Constructor.
			 *
			 * @param int $id The id.
			 */
			public function __construct(private int $id) {
			}//end __construct()

			/**
			 * The id.
			 *
			 * @return int
			 */
			public function getId(): int {
				return $this->id;
			}//end getId()
		};
	}//end entity()

	/**
	 * A relation double pointing at a file.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return object
	 */
	private static function relation(int $fileId): object {
		return new class($fileId) {
			/**
			 * Constructor.
			 *
			 * @param int $fileId The file id.
			 */
			public function __construct(private int $fileId) {
			}//end __construct()

			/**
			 * The file id.
			 *
			 * @return int
			 */
			public function getFileId(): int {
				return $this->fileId;
			}//end getFileId()
		};
	}//end relation()

	/**
	 * Wire the two OpenRegister mappers into the container.
	 *
	 * @param object $entities  The entity mapper.
	 * @param object $relations The relation mapper.
	 *
	 * @return void
	 */
	private function mappers(object $entities, object $relations): void {
		$this->appManager->method('getInstalledApps')->willReturn(['files', 'openregister']);
		$this->container->method('get')->willReturnMap(
			[
				['OCA\OpenRegister\Db\GdprEntityMapper', $entities],
				['OCA\OpenRegister\Db\EntityRelationMapper', $relations],
			]
		);
	}//end mappers()

	/**
	 * Identifiers are trimmed, de-duplicated, and anything blank or not text is dropped.
	 *
	 * @return void
	 */
	public function testCleanTrimsAndDeduplicates(): void {
		$locator = new SubjectErasureLocator($this->container, $this->appManager);

		self::assertSame(
			['Jan Jansen', 'jan@example.org'],
			$locator->clean(identifiers: [' Jan Jansen ', '', '   ', 42, null, 'jan@example.org', 'Jan Jansen'])
		);
	}//end testCleanTrimsAndDeduplicates()

	/**
	 * Without OpenRegister the catalogue is refused as unavailable.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegisterTheCatalogueIsUnavailable(): void {
		$this->appManager->method('getInstalledApps')->willReturn(['files']);
		$this->container->expects(self::never())->method('get');

		try {
			(new SubjectErasureLocator($this->container, $this->appManager))->locate(identifiers: ['Jan']);
			self::fail('Expected the catalogue to be refused.');
		} catch (SubjectErasureRefusedException $refusal) {
			self::assertSame(SubjectErasureRefusedException::REASON_CATALOGUE_UNAVAILABLE, $refusal->getReason());
			self::assertSame('The entity catalogue could not be read: OpenRegister is not installed.', $refusal->getMessage());
		}
	}//end testWithoutOpenRegisterTheCatalogueIsUnavailable()

	/**
	 * A failing mapper is refused with its message.
	 *
	 * @return void
	 */
	public function testAFailingMapperIsRefused(): void {
		$entities = new class {
			/**
			 * Fail.
			 *
			 * @param string $value The value.
			 * @param string $type  The type.
			 *
			 * @return object|null
			 */
			public function findOneByValueAndType(string $value, string $type): ?object {
				throw new RuntimeException('table missing');
			}//end findOneByValueAndType()
		};
		$this->mappers(entities: $entities, relations: new \stdClass());

		$this->expectException(SubjectErasureRefusedException::class);
		$this->expectExceptionMessage('The entity catalogue could not be read: table missing');

		(new SubjectErasureLocator($this->container, $this->appManager))->locate(identifiers: ['Jan']);
	}//end testAFailingMapperIsRefused()

	/**
	 * Files are answered by id, counting every occurrence and each distinct value once.
	 *
	 * @return void
	 */
	public function testFilesAreCountedPerOccurrenceAndSortedById(): void {
		$entities = new class(self::entity(1), self::entity(2), self::entity(3)) {
			/**
			 * The lookups received, as "value|type".
			 *
			 * @var array<int, string>
			 */
			public array $asked = [];

			/**
			 * Constructor.
			 *
			 * @param object $person The PERSON entity of Jan.
			 * @param object $email  The EMAIL entity of the address.
			 * @param object $custom The CUSTOM_DICTIONARY entity of Jan.
			 */
			public function __construct(private object $person, private object $email, private object $custom) {
			}//end __construct()

			/**
			 * Find one entity.
			 *
			 * @param string $value The value.
			 * @param string $type  The type.
			 *
			 * @return object|null
			 */
			public function findOneByValueAndType(string $value, string $type): ?object {
				$this->asked[] = $value . '|' . $type;

				return match ($value . '|' . $type) {
					'Jan|PERSON' => $this->person,
					'jan@example.org|EMAIL' => $this->email,
					'Jan|CUSTOM_DICTIONARY' => $this->custom,
					default => null,
				};
			}//end findOneByValueAndType()
		};
		$relations = new class {
			/**
			 * Find relations by entity id.
			 *
			 * @param int $entityId The entity id.
			 *
			 * @return array<int, object>
			 */
			public function findByEntityId(int $entityId): array {
				return match ($entityId) {
					1 => [SubjectErasureLocatorTest::relationFor(30), SubjectErasureLocatorTest::relationFor(10)],
					2 => [SubjectErasureLocatorTest::relationFor(10), SubjectErasureLocatorTest::relationFor(0)],
					default => [SubjectErasureLocatorTest::relationFor(10)],
				};
			}//end findByEntityId()
		};
		$this->mappers(entities: $entities, relations: $relations);

		$files = (new SubjectErasureLocator($this->container, $this->appManager))->locate(identifiers: ['Jan', ' jan@example.org ', 'Jan']);

		self::assertSame(
			[
				['fileId' => 10, 'occurrences' => 3, 'values' => ['Jan', 'jan@example.org']],
				['fileId' => 30, 'occurrences' => 1, 'values' => ['Jan']],
			],
			$files
		);
		self::assertCount(2 * count(SubjectErasureLocator::TYPES), $entities->asked);
	}//end testFilesAreCountedPerOccurrenceAndSortedById()

	/**
	 * A relation double, reachable from the anonymous mapper.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return object
	 */
	public static function relationFor(int $fileId): object {
		return self::relation(fileId: $fileId);
	}//end relationFor()
}//end class

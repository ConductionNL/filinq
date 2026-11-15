<?php

/**
 * Unit tests for DossierPlaceholderRanker
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\OpenRegister\Files\Service\File {

	if (class_exists(PlaceholderIdTranslator::class) === false) {
		/**
		 * Test double for OpenRegister's static placeholder-id translator, which
		 * the unit bootstrap does not stub. It records the rows it is handed and
		 * answers the ranks a test configured.
		 *
		 * @category Tests
		 * @package  OCA\OpenRegister\Files\Service\File
		 * @author   Conduction B.V. <info@conduction.nl>
		 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
		 * @link     https://www.filinq.app
		 */
		class PlaceholderIdTranslator {

			/**
			 * The rows of the last call.
			 *
			 * @var array<mixed>|null
			 */
			public static ?array $lastRows = null;

			/**
			 * The ranks the next call answers.
			 *
			 * @var array<array-key, int>
			 */
			public static array $ranks = [];

			/**
			 * Record the rows and answer the configured ranks.
			 *
			 * @param array<mixed> $rows The entity rows.
			 *
			 * @return array<array-key, int>
			 */
			public static function rankByFirstAppearance(array $rows): array {
				self::$lastRows = $rows;
				return self::$ranks;
			}//end rankByFirstAppearance()
		}//end class
	}//end if
}

namespace OCA\Filinq\Tests\Unit\Service {

	use Exception;
	use OCA\Filinq\Service\DossierObjectRepository;
	use OCA\Filinq\Service\DossierPlaceholderRanker;
	use OCA\OpenRegister\Files\Service\File\PlaceholderIdTranslator;
	use OCP\App\IAppManager;
	use OCP\Files\File;
	use OCP\Files\Folder;
	use OCP\Files\IRootFolder;
	use OCP\Files\Node;
	use OCP\IUserSession;
	use PHPUnit\Framework\MockObject\MockObject;
	use PHPUnit\Framework\TestCase;
	use Psr\Container\ContainerInterface;
	use Psr\Log\LoggerInterface;

	/**
	 * The ranker walks a dossier's source files, hands OpenRegister's rows to
	 * its ranking helper and resolves each id's type, falling back to empty
	 * sets whenever OpenRegister cannot reproduce the ranking.
	 *
	 * @covers \OCA\Filinq\Service\DossierPlaceholderRanker
	 * @uses   \OCA\Filinq\Service\DossierObjectRepository
	 */
	class DossierPlaceholderRankerTest extends TestCase {

		/**
		 * Logger mock.
		 *
		 * @var LoggerInterface&MockObject
		 */
		private LoggerInterface&MockObject $logger;

		/**
		 * Reset the translator double and the logger.
		 *
		 * @return void
		 */
		protected function setUp(): void {
			parent::setUp();
			PlaceholderIdTranslator::$lastRows = null;
			PlaceholderIdTranslator::$ranks = [];
			$this->logger = $this->createMock(LoggerInterface::class);

		}//end setUp()

		/**
		 * A ranker whose real repository resolves the given mapper (or none).
		 *
		 * @param object|null $mapper The EntityRelationMapper double, or null for "OpenRegister absent".
		 *
		 * @return DossierPlaceholderRanker
		 */
		private function ranker(?object $mapper): DossierPlaceholderRanker {
			$appManager = $this->createMock(IAppManager::class);
			$container = $this->createMock(ContainerInterface::class);
			if ($mapper === null) {
				$appManager->method('getInstalledApps')->willReturn([]);
			} else {
				$appManager->method('getInstalledApps')->willReturn(['openregister']);
				$container->method('get')->with('OCA\OpenRegister\Db\EntityRelationMapper')->willReturn($mapper);
			}

			$repository = new DossierObjectRepository(
				$appManager,
				$container,
				$this->createMock(IRootFolder::class),
				$this->createMock(IUserSession::class),
				$this->createMock(LoggerInterface::class)
			);

			return new DossierPlaceholderRanker($repository, $this->logger);

		}//end ranker()

		/**
		 * A mapper double exposing both lookups OpenRegister provides.
		 *
		 * @param mixed $rows The batch rows, or an Exception to throw.
		 * @param array<int, mixed> $perFile Per-file entries (or an Exception) keyed by file id.
		 *
		 * @return object
		 */
		private function mapper(mixed $rows, array $perFile = []): object {
			return new class ($rows, $perFile) {
				/**
				 * File ids passed to the batch lookup.
				 *
				 * @var array<int, int>|null
				 */
				public ?array $batchFileIds = null;

				/**
				 * Constructor.
				 *
				 * @param mixed $rows Batch rows or an exception.
				 * @param array<int, mixed> $perFile Per-file entries or exceptions.
				 */
				public function __construct(private readonly mixed $rows, private readonly array $perFile) {
				}

				/**
				 * Batch lookup.
				 *
				 * @param array<int, int> $fileIds The file ids.
				 *
				 * @return array<mixed>
				 */
				public function findEntityIdsByValueForFiles(array $fileIds): array {
					$this->batchFileIds = $fileIds;
					if ($this->rows instanceof Exception) {
						throw $this->rows;
					}

					return $this->rows;
				}

				/**
				 * Per-file lookup.
				 *
				 * @param int $fileId The file id.
				 *
				 * @return array<mixed>
				 */
				public function findEntityIdsByValueForFile(int $fileId): array {
					$entries = ($this->perFile[$fileId] ?? []);
					if ($entries instanceof Exception) {
						throw $entries;
					}

					return $entries;
				}
			};

		}//end mapper()

		/**
		 * A file node with the given id.
		 *
		 * @param int $id The id.
		 *
		 * @return File&MockObject
		 */
		private function file(int $id): File&MockObject {
			$file = $this->createMock(File::class);
			$file->method('getId')->willReturn($id);
			return $file;

		}//end file()

		/**
		 * A folder node with a name and a listing.
		 *
		 * @param string $name The name.
		 * @param array<int, Node> $children The listing.
		 *
		 * @return Folder&MockObject
		 */
		private function folder(string $name, array $children): Folder&MockObject {
			$folder = $this->createMock(Folder::class);
			$folder->method('getName')->willReturn($name);
			$folder->method('getDirectoryListing')->willReturn($children);
			return $folder;

		}//end folder()

		/**
		 * The descendant files are collected recursively, output folders and
		 * non-file nodes are skipped, and duplicates are dropped.
		 *
		 * @return void
		 */
		public function testCollectFileIdsWalksSourceFoldersOnly(): void {
			$root = $this->folder(
				'dossier',
				[
					$this->file(1),
					$this->folder('sub', [$this->file(2), $this->folder('deeper', [$this->file(3), $this->file(1)])]),
					$this->folder('anonymised', [$this->file(90)]),
					$this->folder('anonymized', [$this->file(91)]),
					$this->folder('redacted', [$this->file(92)]),
					$this->createMock(Node::class),
				]
			);

			$this->assertSame([1, 2, 3], $this->ranker(null)->collectFileIds($root));

		}//end testCollectFileIdsWalksSourceFoldersOnly()

		/**
		 * A failing listing is logged and the ids gathered so far are kept.
		 *
		 * @return void
		 */
		public function testCollectFileIdsLogsAnEnumerationFailure(): void {
			$root = $this->createMock(Folder::class);
			$root->method('getDirectoryListing')->willThrowException(new Exception('storage offline'));
			$this->logger->expects($this->once())->method('warning')->with(
				'LegalBasesSummaryService: dossier file enumeration failed',
				['error' => 'storage offline']
			);

			$this->assertSame([], $this->ranker(null)->collectFileIds($root));

		}//end testCollectFileIdsLogsAnEnumerationFailure()

		/**
		 * A well-formed placeholder sorts by type then by number.
		 *
		 * @return void
		 */
		public function testSortKeySplitsTypeAndNumber(): void {
			$ranker = $this->ranker(null);

			$this->assertSame(['DATUM', 6], $ranker->sortKey('[DATUM: 6]'));
			$this->assertSame(['NAAM', 11], $ranker->sortKey('[NAAM:11] '));
			$this->assertSame(-1, ($ranker->sortKey('[NAAM: 2]') <=> $ranker->sortKey('[NAAM: 10]')));

		}//end testSortKeySplitsTypeAndNumber()

		/**
		 * A placeholder of another shape sorts last, by its raw string.
		 *
		 * @return void
		 */
		public function testSortKeyPutsOtherShapesLast(): void {
			$ranker = $this->ranker(null);

			$this->assertSame(['[NAAM: x]', PHP_INT_MAX], $ranker->sortKey('[NAAM: x]'));
			$this->assertSame(['plain', PHP_INT_MAX], $ranker->sortKey('plain'));

		}//end testSortKeyPutsOtherShapesLast()

		/**
		 * Without OpenRegister the ranking is empty and the folder is not walked.
		 *
		 * @return void
		 */
		public function testRankIsEmptyWithoutOpenRegister(): void {
			$folder = $this->createMock(Folder::class);
			$folder->expects($this->never())->method('getDirectoryListing');

			$this->assertSame(['ranks' => [], 'types' => []], $this->ranker(null)->rank($folder));

		}//end testRankIsEmptyWithoutOpenRegister()

		/**
		 * A mapper too old to offer the batch lookup yields an empty ranking.
		 *
		 * @return void
		 */
		public function testRankIsEmptyForAnOldMapper(): void {
			$oldMapper = new class {
				/**
				 * Only the per-file lookup.
				 *
				 * @param int $fileId The file id.
				 *
				 * @return array<mixed>
				 */
				public function findEntityIdsByValueForFile(int $fileId): array {
					return [];
				}
			};
			$folder = $this->createMock(Folder::class);
			$folder->expects($this->never())->method('getDirectoryListing');

			$this->assertSame(['ranks' => [], 'types' => []], $this->ranker($oldMapper)->rank($folder));

		}//end testRankIsEmptyForAnOldMapper()

		/**
		 * A folder without source files yields an empty ranking without a lookup.
		 *
		 * @return void
		 */
		public function testRankIsEmptyForAFolderWithoutFiles(): void {
			$mapper = $this->mapper([['id' => 1]]);

			$result = $this->ranker($mapper)->rank($this->folder('dossier', [$this->folder('redacted', [$this->file(5)])]));

			$this->assertSame(['ranks' => [], 'types' => []], $result);
			$this->assertNull($mapper->batchFileIds);

		}//end testRankIsEmptyForAFolderWithoutFiles()

		/**
		 * A failing batch lookup is logged and falls back to empty sets.
		 *
		 * @return void
		 */
		public function testRankFallsBackWhenTheLookupFails(): void {
			$mapper = $this->mapper(new Exception('query failed'));
			$this->logger->expects($this->once())->method('warning')->with(
				'LegalBasesSummaryService: dossier placeholder recompute failed; falling back to global ids',
				['error' => 'query failed']
			);

			$result = $this->ranker($mapper)->rank($this->folder('dossier', [$this->file(4)]));

			$this->assertSame(['ranks' => [], 'types' => []], $result);
			$this->assertNull(PlaceholderIdTranslator::$lastRows);

		}//end testRankFallsBackWhenTheLookupFails()

		/**
		 * An empty ranking from OpenRegister yields empty sets.
		 *
		 * @return void
		 */
		public function testRankIsEmptyWhenTheTranslatorRanksNothing(): void {
			$rows = [['entity_id' => 'e1', 'file_id' => 4, 'position_start' => 0]];
			$mapper = $this->mapper($rows, [4 => [['id' => 'e1', 'type' => 'NAAM']]]);

			$result = $this->ranker($mapper)->rank($this->folder('dossier', [$this->file(4)]));

			$this->assertSame(['ranks' => [], 'types' => []], $result);
			$this->assertSame($rows, PlaceholderIdTranslator::$lastRows);

		}//end testRankIsEmptyWhenTheTranslatorRanksNothing()

		/**
		 * The batch rows go to OpenRegister's ranking, and each id's type is
		 * resolved per file, skipping a file whose lookup fails.
		 *
		 * @return void
		 */
		public function testRankReturnsRanksAndTypes(): void {
			$rows = [
				['entity_id' => 'e2', 'file_id' => 4, 'position_start' => 3],
				['entity_id' => 'e1', 'file_id' => 4, 'position_start' => 9],
			];
			$mapper = $this->mapper(
				$rows,
				[
					4 => [['id' => 'e2', 'type' => 'DATUM'], ['id' => 'e1', 'type' => 'NAAM']],
					5 => new Exception('file gone'),
					6 => [['id' => 7], ['type' => 'BSN']],
				]
			);
			PlaceholderIdTranslator::$ranks = ['e2' => 1, 'e1' => 2];

			$result = $this->ranker($mapper)->rank(
				$this->folder('dossier', [$this->file(4), $this->file(5), $this->folder('sub', [$this->file(6)])])
			);

			$this->assertSame([4, 5, 6], $mapper->batchFileIds);
			$this->assertSame($rows, PlaceholderIdTranslator::$lastRows);
			$this->assertSame(
				[
					'ranks' => ['e2' => 1, 'e1' => 2],
					'types' => ['e2' => 'DATUM', 'e1' => 'NAAM', '7' => '', '' => 'BSN'],
				],
				$result
			);

		}//end testRankReturnsRanksAndTypes()
	}//end class
}

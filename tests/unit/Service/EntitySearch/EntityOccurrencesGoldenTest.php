<?php

/**
 * Golden answers of EntityOccurrences::forUser over a mixed set of occurrence rows.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\EntitySearch
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\EntitySearch;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\EntitySearch\EntityOccurrences;
use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * The whole answer for three callers, compared with JSON recorded from the
 * implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\EntitySearch\EntityOccurrences
 */
class EntityOccurrencesGoldenTest extends TestCase {

	/**
	 * How often OpenRegister was asked for dossiers.
	 *
	 * @var int
	 */
	private int $dossierReads = 0;

	/**
	 * The occurrence rows: unsorted files, a repeated file, files that are
	 * unreadable, not a file or missing, an object and two e-mail rows.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function relations(): array {
		return [
			['fileId' => 30, 'confidence' => 0.5, 'anonymized' => 1, 'detectionMethod' => 'regex'],
			['fileId' => 11, 'confidence' => 0.97, 'anonymized' => false, 'detectionMethod' => 'presidio'],
			['fileId' => 12, 'confidence' => 0.9],
			['fileId' => 11],
			['fileId' => 13, 'confidence' => '0.4'],
			['fileId' => 14],
			['objectId' => 5],
			['fileId' => '0', 'objectId' => '0'],
			['fileId' => -3],
			['objectId' => 6, 'fileId' => 0],
		];
	}//end relations()

	/**
	 * Each caller's answer, with how often dossiers were read.
	 *
	 * @return array<string, array{0: string, 1: array<int, array<string, mixed>>, 2: string}>
	 */
	public static function cases(): array {
		return [
			'reader' => ['petra', [], 'reader.json'],
			'no user folder' => ['ghost', [], 'no-user-folder.json'],
			'nothing found' => ['petra', ['empty' => true], 'nothing.json'],
		];
	}//end cases()

	/**
	 * The answer matches the recorded JSON.
	 *
	 * @param string               $userId  The caller.
	 * @param array<string, mixed> $options Case options.
	 * @param string               $golden  The recorded answer's file name.
	 *
	 * @dataProvider cases
	 *
	 * @return void
	 */
	public function testAnswerMatchesTheRecordedOne(string $userId, array $options, string $golden): void {
		$relations = $this->relations();
		if (($options['empty'] ?? false) === true) {
			$relations = [];
		}

		$answer = $this->occurrences()->forUser(relations: $relations, userId: $userId);
		$actual = (string)json_encode(['answer' => $answer, 'dossierReads' => $this->dossierReads], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . "\n";

		$path = __DIR__ . '/golden/occurrences-' . $golden;
		if (getenv('GOLDEN_RECORD') === '1') {
			file_put_contents($path, $actual);
		}

		$this->assertSame((string)file_get_contents($path), $actual);
	}//end testAnswerMatchesTheRecordedOne()

	/**
	 * The service over in-memory files, links, dossiers and risk levels.
	 *
	 * Tree: 900 (dossier A) / 901 / 30.pdf; 11.pdf and 12.pdf sit in 800, which
	 * is no dossier, under a root whose parent throws.
	 *
	 * @return EntityOccurrences
	 */
	private function occurrences(): EntityOccurrences {
		$root = $this->createMock(Folder::class);
		$root->method('getId')->willReturn(1);
		$root->method('getParent')->willThrowException(new RuntimeException('root'));
		$plain = $this->folder(800, $root);
		$dossier = $this->folder(900, $root);
		$sub = $this->folder(901, $dossier);

		$files = [
			30 => $this->file('/petra/files/A/sub/dertig.pdf', $sub, true),
			11 => $this->file('/petra/files/plain/elf.pdf', $plain, true),
			12 => $this->file('/petra/files/plain/twaalf.pdf', $plain, false),
			13 => $this->createMock(Folder::class),
		];

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getFirstNodeById')->willReturnCallback(
			static function (int $id) use ($files) {
				if ($id === 14) {
					throw new RuntimeException('gone');
				}

				return $files[$id] ?? null;
			}
		);
		$userFolder->method('getRelativePath')->willReturnCallback(
			static fn (string $path): ?string => str_replace('/petra/files', '', $path)
		);

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturnCallback(
			static function (string $userId) use ($userFolder): Folder {
				if ($userId === 'ghost') {
					throw new RuntimeException('no such user');
				}

				return $userFolder;
			}
		);

		$links = $this->createMock(AnonymizationLinkReader::class);
		$links->method('forSource')->willReturnCallback(
			static fn (int $id): ?array => [11 => ['anonymizedFileId' => 30], 12 => ['anonymizedFileId' => 0]][$id] ?? null
		);
		$links->method('forAnonymized')->willReturnCallback(
			static fn (int $id): ?array => [30 => ['sourceFileId' => 12]][$id] ?? null
		);

		$objects = $this->createMock(ObjectService::class);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			function (): array {
				$this->dossierReads++;
				$object = new class {
					/**
					 * @return array<string, mixed>
					 */
					public function jsonSerialize(): array {
						return ['@self' => ['folder' => 900, 'id' => 'dossier-a'], 'name' => 'Dossier A'];
					}
				};
				return [
					$object,
					['@self' => ['folder' => 0, 'id' => 'no-folder'], 'name' => 'Zonder map'],
					['@self' => ['folder' => 950], 'id' => 'dossier-b', 'name' => 'Dossier B'],
				];
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$risk = new class {
			public function getRiskLevel(int $fileId): string {
				if ($fileId === 30) {
					return '';
				}

				return 'high';
			}
		};
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($risk);

		return new EntityOccurrences($rootFolder, $links, $resolver, $container);
	}//end occurrences()

	/**
	 * A folder double with an id and a parent.
	 *
	 * @param int    $id     The folder id.
	 * @param Folder $parent Its parent.
	 *
	 * @return Folder
	 */
	private function folder(int $id, Folder $parent): Folder {
		$folder = $this->createMock(Folder::class);
		$folder->method('getId')->willReturn($id);
		$folder->method('getParent')->willReturn($parent);
		return $folder;
	}//end folder()

	/**
	 * A file double.
	 *
	 * @param string $path     Its absolute path.
	 * @param Folder $parent   Its folder.
	 * @param bool   $readable Whether the caller may read it.
	 *
	 * @return File
	 */
	private function file(string $path, Folder $parent, bool $readable): File {
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn(basename($path));
		$file->method('getPath')->willReturn($path);
		$file->method('getParent')->willReturn($parent);
		$file->method('isReadable')->willReturn($readable);
		return $file;
	}//end file()
}//end class

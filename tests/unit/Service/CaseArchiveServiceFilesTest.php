<?php

/**
 * A bundle of chosen files: the manifest over file ids, the preflight, the
 * archive written into a nested folder with its manifest, and the job whose
 * refused write stops the bundle (share-document-links task 2.4).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-share-document-links/tasks.md#task-2.4
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\CaseArchiveService;
use OCA\Filinq\Service\CaseArchiveWriter;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FlatFileListService;
use OCA\Filinq\Service\ReadableFileResolver;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\ITempManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use ZipArchive;

/**
 * @covers \OCA\Filinq\Service\CaseArchiveService
 * @covers \OCA\Filinq\Service\CaseArchiveWriter
 */
class CaseArchiveServiceFilesTest extends TestCase {

	/**
	 * The files the requester can read: id => [name, content].
	 *
	 * @var array<int, array{0: string, 1: string}>
	 */
	private array $readable = [];

	/**
	 * Jobs the fake OpenRegister stored.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $jobs = [];

	/**
	 * Folders created, as path segments in order.
	 *
	 * @var array<int, string>
	 */
	private array $createdFolders = [];

	/**
	 * The archive's name and bytes, once written.
	 *
	 * @var array{0: string, 1: string}|null
	 */
	private ?array $archive = null;

	private bool $archiveDeleted = false;

	private bool $refuseJob = false;

	private int $ceiling = CaseArchiveService::DEFAULT_CEILING;

	private function service(): CaseArchiveService {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('clara');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$files = $this->createMock(ReadableFileResolver::class);
		$files->method('node')->willReturnCallback(
			function (int $fileId): ?File {
				if (isset($this->readable[$fileId]) === false) {
					return null;
				}

				[$name, $content] = $this->readable[$fileId];
				$file = $this->createMock(File::class);
				$file->method('getName')->willReturn($name);
				$file->method('getSize')->willReturn(strlen($content));
				$file->method('getEtag')->willReturn('etag-' . $fileId);
				$file->method('fopen')->willReturnCallback(
					static function () use ($content) {
						$stream = fopen('php://memory', 'w+b');
						fwrite($stream, $content);
						rewind($stream);
						return $stream;
					}
				);

				return $file;
			}
		);

		$objects = $this->createMock(ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object): array {
				if ($this->refuseJob === true) {
					throw new RuntimeException('Forbidden: create on archiveJob');
				}

				$this->jobs[] = $object;
				return $object + ['uuid' => 'job-' . count($this->jobs)];
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueInt')->willReturnCallback(fn (): int => $this->ceiling);

		$temp = $this->createMock(ITempManager::class);
		$temp->method('getTemporaryFile')->willReturnCallback(
			static fn (string $postFix = ''): string => (string) tempnam(sys_get_temp_dir(), 'fqshare') . $postFix
		);

		$writer = new CaseArchiveWriter(
			rootFolder: $this->rootFolder(),
			tempManager: $temp,
			logger: $this->createMock(LoggerInterface::class),
			files: $files
		);

		return new CaseArchiveService(
			files: $this->createMock(FlatFileListService::class),
			objectResolver: $resolver,
			userSession: $session,
			logger: $this->createMock(LoggerInterface::class),
			writer: $writer,
			appConfig: $appConfig,
			readable: $files
		);
	}

	/**
	 * A Files root where every folder starts empty and records what is made in it.
	 *
	 * @return IRootFolder The root.
	 */
	private function rootFolder(): IRootFolder {
		$archive = $this->createMock(File::class);
		$archive->method('getId')->willReturn(9001);
		$archive->method('getName')->willReturnCallback(fn (): string => (string) ($this->archive[0] ?? ''));
		$archive->method('getPath')->willReturn('/clara/files/DocuDesk/Shared bundles/x.zip');
		$archive->method('putContent')->willReturnCallback(
			function ($data): void {
				$this->archive[1] = is_resource($data) === true ? (string) stream_get_contents($data) : (string) $data;
			}
		);
		$archive->method('delete')->willReturnCallback(function (): void {
			$this->archiveDeleted = true;
		});

		$leaf = $this->createMock(Folder::class);
		$leaf->method('nodeExists')->willReturn(false);
		$leaf->method('getNonExistingName')->willReturnArgument(0);
		$leaf->method('newFile')->willReturnCallback(
			function (string $name) use ($archive): File {
				$this->archive = [$name, ''];
				return $archive;
			}
		);

		$middle = $this->createMock(Folder::class);
		$middle->method('nodeExists')->willReturn(false);
		$middle->method('newFolder')->willReturnCallback(
			function (string $name) use ($leaf): Folder {
				$this->createdFolders[] = $name;
				return $leaf;
			}
		);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('nodeExists')->willReturn(false);
		$userFolder->method('newFolder')->willReturnCallback(
			function (string $name) use ($middle): Folder {
				$this->createdFolders[] = $name;
				return $middle;
			}
		);
		$userFolder->method('getRelativePath')->willReturn('/DocuDesk/Shared bundles/x.zip');
		$userFolder->method('getById')->willReturnCallback(
			static fn (int $fileId): array => ($fileId === 9001 ? [$archive] : [])
		);

		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($userFolder);

		return $root;
	}

	/**
	 * The entries of the archive written, name => bytes.
	 *
	 * @return array<string, string> The entries.
	 */
	private function entries(): array {
		$this->assertNotNull($this->archive, 'an archive was written');
		$path = (string) tempnam(sys_get_temp_dir(), 'fqshareread');
		file_put_contents($path, $this->archive[1]);
		$zip = new ZipArchive();
		$this->assertTrue($zip->open($path) === true);
		$entries = [];
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$entries[(string) $zip->getNameIndex($i)] = (string) $zip->getFromIndex($i);
		}

		$zip->close();
		unlink($path);

		return $entries;
	}

	public function testTheManifestTakesTheChosenFilesInNameOrderUpToTheCeiling(): void {
		$this->readable = [5 => ['c.pdf', 'cccc'], 3 => ['a.pdf', 'aaa'], 4 => ['b.pdf', 'bbbbbb']];

		$manifest = $this->service()->manifestForFiles(fileIds: [5, '3', 4, 3, 66, 0], ceiling: 10);

		$this->assertSame(
			[['fileId' => 3, 'name' => 'a.pdf', 'record' => '', 'size' => 3, 'version' => 'etag-3'], ['fileId' => 4, 'name' => 'b.pdf', 'record' => '', 'size' => 6, 'version' => 'etag-4']],
			$manifest['included']
		);
		$this->assertSame(
			[[5, 'c.pdf', CaseArchiveService::REASON_CEILING], [66, '', CaseArchiveService::REASON_PERMISSION]],
			array_map(static fn (array $row): array => [$row['fileId'], $row['name'], $row['reason']], $manifest['excluded'])
		);
		$this->assertSame(9, $manifest['bytes']);
	}

	public function testThePreflightSaysTheBundleGoesOverTheCeilingBeforeAnythingIsBuilt(): void {
		$this->readable = [1 => ['a.pdf', str_repeat('a', 600)], 2 => ['b.pdf', str_repeat('b', 600)]];

		$preflight = $this->service()->preflightFiles(fileIds: [1, 2], ceiling: 1000);

		$this->assertSame([2, 1200, 1000, true], [$preflight['files'], $preflight['bytes'], $preflight['ceilingBytes'], $preflight['exceedsCeiling']]);
		$this->assertSame('b.pdf', $preflight['excluded'][0]['name'], 'the preflight names what would be left out');
		$this->assertNull($this->archive, 'nothing was built');
		$this->assertSame([], $this->jobs);
	}

	public function testTheArchiveHoldsTheChosenFilesAndTheManifestAndIsRecordedWithItsFileId(): void {
		$this->readable = [7 => ['besluit.pdf', 'BESLUIT'], 8 => ['advies.pdf', 'ADVIES']];

		$built = $this->service()->buildFromFiles(fileIds: [7, 8, 9], folderPath: 'DocuDesk/Shared bundles', name: 'Bezwaar 2026/031');

		$this->assertSame(['DocuDesk', 'Shared bundles'], $this->createdFolders);
		$this->assertSame('Bezwaar 2026-031.zip', $this->archive[0]);
		$entries = $this->entries();
		$this->assertSame(['advies.pdf', 'besluit.pdf', 'manifest.json'], array_keys($entries));
		$this->assertSame('BESLUIT', $entries['besluit.pdf']);
		$inside = json_decode($entries['manifest.json'], true);
		$this->assertSame(['kind' => 'files', 'name' => 'Bezwaar 2026/031', 'fileIds' => [8, 7]], $inside['subject']);
		$this->assertSame([9], array_column($inside['excluded'], 'fileId'));

		$this->assertCount(1, $this->jobs);
		$this->assertSame([9001, 'completed', 2, 1, 'clara'], [$this->jobs[0]['fileId'], $this->jobs[0]['status'], $this->jobs[0]['includedCount'], $this->jobs[0]['excludedCount'], $this->jobs[0]['requestedBy']]);
		$this->assertSame('job-1', $built['job']['uuid']);
		$this->assertSame(9001, $built['archive']['fileId']);
	}

	public function testARefusedJobWriteDeletesTheArchiveAndRaises(): void {
		$this->readable = [7 => ['besluit.pdf', 'BESLUIT']];
		$this->refuseJob = true;

		try {
			$this->service()->buildFromFiles(fileIds: [7, 8], folderPath: 'DocuDesk/Shared bundles', name: 'Bundel');
			$this->fail('A refused job write must stop the bundle');
		} catch (RuntimeException $e) {
			$this->assertSame([500, 'The archive job could not be recorded'], [$e->getCode(), $e->getMessage()]);
		}

		$this->assertTrue($this->archiveDeleted, 'the unrecorded archive was deleted again');
	}

	public function testABundleWithNothingToHoldIsRefusedBeforeAnythingIsWritten(): void {
		try {
			$this->service()->buildFromFiles(fileIds: [7, 8], folderPath: 'DocuDesk/Shared bundles', name: 'Bundel');
			$this->fail('Nothing readable, no bundle');
		} catch (RuntimeException $e) {
			$this->assertSame(400, $e->getCode());
		}

		$this->assertSame([null, []], [$this->archive, $this->jobs]);
	}

	public function testAnArchiveIsDeletedByItsFileIdAndAMissingOneIsNoError(): void {
		$this->service();
		$writer = new CaseArchiveWriter(
			rootFolder: $this->rootFolder(),
			tempManager: $this->createMock(ITempManager::class),
			logger: $this->createMock(LoggerInterface::class),
			files: $this->createMock(ReadableFileResolver::class)
		);

		$this->assertSame([false, false], [$writer->discardById('clara', 1234), $writer->discardById('', 9001)]);
		$this->assertFalse($this->archiveDeleted);
		$this->assertTrue($writer->discardById('clara', 9001));
		$this->assertTrue($this->archiveDeleted);
	}
}

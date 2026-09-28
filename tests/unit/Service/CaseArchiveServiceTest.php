<?php

/**
 * Unit tests for CaseArchiveService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\CaseArchiveService;
use OCA\Filinq\Service\CaseArchiveWriter;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FlatFileListService;
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
 * Asserts that nothing is dropped silently: a file over the ceiling and a file
 * the person may not read are both in the manifest, with their reason.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CaseArchiveServiceTest extends TestCase {

	/**
	 * Jobs the fake OpenRegister was asked to store.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * The case every test bundles.
	 *
	 * @var array<string, string>
	 */
	private array $domain = ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'];

	/**
	 * Contents of the files the fake Files area holds, by file id.
	 *
	 * @var array<int, string>
	 */
	private array $contents = [];

	/**
	 * The bytes written to the archive file, once written.
	 *
	 * @var string|null
	 */
	private ?string $archiveBytes = null;

	/**
	 * Whether the archive file was deleted again.
	 *
	 * @var bool
	 */
	private bool $archiveDeleted = false;

	/**
	 * The administered ceiling the fake app config answers with.
	 *
	 * @var int
	 */
	private int $administeredCeiling = CaseArchiveService::DEFAULT_CEILING;

	/**
	 * Whether the fake OpenRegister refuses the job write.
	 *
	 * @var bool
	 */
	private bool $refuseJobWrite = false;

	/**
	 * Build the service over a flat list and the records behind it.
	 *
	 * @param array<int, array<string, mixed>> $rows The readable files.
	 * @param array<int, array<string, mixed>> $records Every record of the object, readable or not.
	 *
	 * @return CaseArchiveService The service under test.
	 */
	private function service(array $rows, array $records): CaseArchiveService {
		$files = $this->createMock(FlatFileListService::class);
		$files->method('listFor')->willReturn(
			['results' => $rows, 'total' => count($rows), 'page' => 1, 'pages' => 1]
		);
		$files->method('recordsFor')->willReturn($records);

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				if ($this->refuseJobWrite === true) {
					throw new RuntimeException('Forbidden: create on archiveJob');
				}

				$object = ($arguments['object'] ?? $arguments[0] ?? []);
				$this->written[] = $object;

				return $object;
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueInt')->willReturnCallback(fn (): int => $this->administeredCeiling);

		$temp = $this->createMock(ITempManager::class);
		$temp->method('getTemporaryFile')->willReturnCallback(
			static fn (string $postFix = ''): string => (string) tempnam(sys_get_temp_dir(), 'fq1210').$postFix
		);

		// The real writer over a fake Files area: the archive this test reads
		// back is the one the production code wrote.
		$writer = new CaseArchiveWriter($this->rootFolder(), $temp, $this->createMock(LoggerInterface::class));

		return new CaseArchiveService(
			$files,
			$resolver,
			$session,
			$this->createMock(LoggerInterface::class),
			$writer,
			$appConfig
		);

	}//end service()

	/**
	 * A fake Files area: the files in $this->contents, and a bundle folder
	 * whose new file records the bytes it is given.
	 *
	 * @return IRootFolder The root folder double.
	 */
	private function rootFolder(): IRootFolder {
		$archive = $this->createMock(File::class);
		$archive->method('getId')->willReturn(9001);
		$archive->method('getName')->willReturn('zaak-zaak-1.zip');
		$archive->method('getPath')->willReturn('/anna/files/Case bundles/zaak-zaak-1.zip');
		$archive->method('putContent')->willReturnCallback(
			function ($data): void {
				$this->archiveBytes = is_resource($data) === true ? (string) stream_get_contents($data) : (string) $data;
			}
		);
		$archive->method('delete')->willReturnCallback(function (): void {
			$this->archiveDeleted = true;
		});

		$bundles = $this->createMock(Folder::class);
		$bundles->method('getNonExistingName')->willReturnArgument(0);
		$bundles->method('newFile')->willReturn($archive);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('nodeExists')->willReturn(false);
		$userFolder->method('newFolder')->willReturn($bundles);
		$userFolder->method('getRelativePath')->willReturn('/Case bundles/zaak-zaak-1.zip');
		$userFolder->method('getById')->willReturnCallback(
			function (int $fileId): array {
				if (isset($this->contents[$fileId]) === false) {
					return [];
				}

				$file = $this->createMock(File::class);
				$content = $this->contents[$fileId];
				$file->method('fopen')->willReturnCallback(
					static function () use ($content) {
						$stream = fopen('php://memory', 'w+b');
						fwrite($stream, $content);
						rewind($stream);

						return $stream;
					}
				);

				return [$file];
			}
		);

		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($userFolder);

		return $root;

	}//end rootFolder()

	/**
	 * The entries of the archive that was written, name => bytes.
	 *
	 * @return array<string, string> The entries.
	 */
	private function archiveEntries(): array {
		$this->assertNotNull($this->archiveBytes, 'an archive file was written');
		$path = (string) tempnam(sys_get_temp_dir(), 'fq1210read');
		file_put_contents($path, $this->archiveBytes);
		$zip = new ZipArchive();
		$this->assertTrue($zip->open($path) === true, 'the archive is a readable zip');
		$entries = [];
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$name = (string) $zip->getNameIndex($i);
			$entries[$name] = (string) $zip->getFromIndex($i);
		}

		$zip->close();
		unlink($path);

		return $entries;

	}//end archiveEntries()

	/**
	 * One row of the flat list.
	 *
	 * @param int $fileId The file id.
	 * @param string $name The file name.
	 * @param int $size Its size in bytes.
	 *
	 * @return array<string, mixed> The row.
	 */
	private function row(int $fileId, string $name, int $size): array {
		return [
			'fileId' => $fileId,
			'name' => $name,
			'size' => $size,
			'record' => ['uuid' => 'record-' . $fileId, 'name' => $name, 'status' => 'draft'],
		];

	}//end row()

	/**
	 * Twenty readable files make one bundle of twenty, with a manifest.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testOneBundleForTheBezwaarcommissie(): void {
		$rows = [];
		$records = [];
		for ($index = 1; $index <= 20; $index++) {
			$rows[] = $this->row(fileId: (4700 + $index), name: sprintf('stuk-%02d.pdf', $index), size: 1024);
			$records[] = ['uuid' => 'record-' . (4700 + $index), 'fileId' => (4700 + $index)];
		}

		$manifest = $this->service(rows: $rows, records: $records)->manifestFor(domain: $this->domain);

		$this->assertCount(20, $manifest['included']);
		$this->assertSame([], $manifest['excluded']);
		$this->assertSame((20 * 1024), $manifest['bytes']);

	}//end testOneBundleForTheBezwaarcommissie()

	/**
	 * A file over the ceiling is named in the manifest, not dropped.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testNothingIsDroppedSilentlyWhenTheCeilingIsReached(): void {
		$rows = [
			$this->row(fileId: 1, name: 'a.pdf', size: 600),
			$this->row(fileId: 2, name: 'b.pdf', size: 600),
		];
		$records = [['uuid' => 'record-1', 'fileId' => 1], ['uuid' => 'record-2', 'fileId' => 2]];

		$manifest = $this->service(rows: $rows, records: $records)->manifestFor(
			domain: $this->domain,
			ceiling: 1000
		);

		$this->assertCount(1, $manifest['included']);
		$this->assertCount(1, $manifest['excluded']);
		$this->assertSame(CaseArchiveService::REASON_CEILING, $manifest['excluded'][0]['reason']);
		$this->assertSame('b.pdf', $manifest['excluded'][0]['name'], 'The manifest names WHICH file is missing.');

	}//end testNothingIsDroppedSilentlyWhenTheCeilingIsReached()

	/**
	 * A file the person may not read is in the manifest as a permission exclusion.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testAFileTheUserMayNotReadIsRecordedAsSuch(): void {
		$rows = [$this->row(fileId: 1, name: 'zichtbaar.pdf', size: 100)];
		$records = [
			['uuid' => 'record-1', 'fileId' => 1],
			['uuid' => 'record-2', 'fileId' => 2, 'documentName' => 'geheim.pdf'],
		];

		$manifest = $this->service(rows: $rows, records: $records)->manifestFor(domain: $this->domain);

		$this->assertCount(1, $manifest['included']);
		$this->assertCount(1, $manifest['excluded']);
		$this->assertSame(CaseArchiveService::REASON_PERMISSION, $manifest['excluded'][0]['reason']);
		$this->assertSame('geheim.pdf', $manifest['excluded'][0]['name']);

	}//end testAFileTheUserMayNotReadIsRecordedAsSuch()

	/**
	 * The preflight says the bundle is too big BEFORE the job starts.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testThePreflightWarnsBeforeTheJobStarts(): void {
		$rows = [
			$this->row(fileId: 1, name: 'a.pdf', size: 600),
			$this->row(fileId: 2, name: 'b.pdf', size: 600),
		];

		$preflight = $this->service(rows: $rows, records: [])->preflight(
			domain: $this->domain,
			ceiling: 1000
		);

		$this->assertTrue($preflight['exceedsCeiling']);
		$this->assertSame(2, $preflight['files']);
		$this->assertSame(1200, $preflight['bytes']);

	}//end testThePreflightWarnsBeforeTheJobStarts()

	/**
	 * A bundle that fits does not warn.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testABundleThatFitsDoesNotWarn(): void {
		$preflight = $this->service(
			rows: [$this->row(fileId: 1, name: 'a.pdf', size: 100)],
			records: []
		)->preflight(domain: $this->domain, ceiling: 1000);

		$this->assertFalse($preflight['exceedsCeiling']);

	}//end testABundleThatFitsDoesNotWarn()

	/**
	 * The job records who asked, the ceiling, and both counts.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testTheJobRecordsWhatWasHandedOver(): void {
		$service = $this->service(
			rows: [$this->row(fileId: 1, name: 'a.pdf', size: 100)],
			records: [['uuid' => 'record-1', 'fileId' => 1], ['uuid' => 'record-2', 'fileId' => 2]]
		);

		$manifest = $service->manifestFor(domain: $this->domain);
		$job = $service->record(domain: $this->domain, manifest: $manifest, ceiling: 1000, fileId: 9001);

		$this->assertSame('anna', $job['requestedBy']);
		$this->assertSame(1000, $job['ceilingBytes']);
		$this->assertSame(1, $job['includedCount']);
		$this->assertSame(1, $job['excludedCount']);
		$this->assertCount(1, $this->written);
		$this->assertSame('completed', $this->written[0]['status']);

	}//end testTheJobRecordsWhatWasHandedOver()

	/**
	 * Issue #1210: building a bundle writes ONE archive holding the files and
	 * a manifest, and the job records that archive's file id as completed.
	 *
	 * @return void
	 */
	public function testBuildWritesOneArchiveWithTheFilesAndAManifest(): void {
		$this->contents = [1 => 'besluit', 2 => 'bezwaar'];
		$service = $this->service(
			rows: [$this->row(fileId: 1, name: 'besluit.pdf', size: 7), $this->row(fileId: 2, name: 'bezwaar.pdf', size: 7)],
			records: [['uuid' => 'record-1', 'fileId' => 1], ['uuid' => 'record-2', 'fileId' => 2]]
		);

		$result = $service->build(domain: $this->domain);

		$entries = $this->archiveEntries();
		$this->assertSame(['besluit.pdf', 'bezwaar.pdf', 'manifest.json'], array_keys($entries));
		$this->assertSame('besluit', $entries['besluit.pdf']);
		$manifest = json_decode($entries['manifest.json'], true);
		$this->assertCount(2, $manifest['included']);
		$this->assertSame([], $manifest['excluded']);
		$this->assertSame(9001, $result['archive']['fileId']);
		$this->assertCount(1, $this->written);
		$this->assertSame(9001, $this->written[0]['fileId']);
		$this->assertSame('completed', $this->written[0]['status']);

	}//end testBuildWritesOneArchiveWithTheFilesAndAManifest()

	/**
	 * Issue #1210: a file that cannot be read when the archive is written is
	 * listed as left out, in the archive's manifest and in the job.
	 *
	 * @return void
	 */
	public function testAFileThatCannotBeReadIsListedAsMissing(): void {
		$this->contents = [1 => 'besluit'];
		$service = $this->service(
			rows: [$this->row(fileId: 1, name: 'besluit.pdf', size: 7), $this->row(fileId: 2, name: 'gone.pdf', size: 5)],
			records: [['uuid' => 'record-1', 'fileId' => 1], ['uuid' => 'record-2', 'fileId' => 2]]
		);

		$result = $service->build(domain: $this->domain);

		$entries = $this->archiveEntries();
		$this->assertArrayNotHasKey('gone.pdf', $entries);
		$this->assertSame(CaseArchiveService::REASON_MISSING, $result['excluded'][0]['reason']);
		$this->assertSame(1, $this->written[0]['excludedCount']);

	}//end testAFileThatCannotBeReadIsListedAsMissing()

	/**
	 * Issue #1210: a refused job write is not swallowed; the call fails and
	 * the archive is removed, so no unrecorded bundle is handed over.
	 *
	 * @return void
	 */
	public function testARefusedJobWriteRaisesAndRemovesTheArchive(): void {
		$this->contents = [1 => 'besluit'];
		$this->refuseJobWrite = true;
		$service = $this->service(
			rows: [$this->row(fileId: 1, name: 'besluit.pdf', size: 7)],
			records: [['uuid' => 'record-1', 'fileId' => 1]]
		);

		try {
			$service->build(domain: $this->domain);
			$this->fail('a refused job write must stop the bundle');
		} catch (RuntimeException $e) {
			$this->assertSame('The archive job could not be recorded', $e->getMessage());
		}

		$this->assertTrue($this->archiveDeleted, 'the unrecorded archive is removed again');

	}//end testARefusedJobWriteRaisesAndRemovesTheArchive()

	/**
	 * Issue #1210: record() no longer logs a refused write and carries on.
	 *
	 * @return void
	 */
	public function testRecordRaisesWhenTheJobCannotBeStored(): void {
		$this->refuseJobWrite = true;
		$service = $this->service(rows: [], records: []);

		$this->expectException(RuntimeException::class);
		$service->record(domain: $this->domain, manifest: ['included' => [], 'excluded' => [], 'bytes' => 0], ceiling: 1000, fileId: 9001);

	}//end testRecordRaisesWhenTheJobCannotBeStored()

	/**
	 * Issue #1210: a job without an archive is not recorded as completed.
	 *
	 * @return void
	 */
	public function testAJobWithoutAnArchiveIsNotCompleted(): void {
		$service = $this->service(rows: [], records: []);

		$service->record(domain: $this->domain, manifest: ['included' => [], 'excluded' => [], 'bytes' => 0], ceiling: 1000);

		$this->assertSame('failed', $this->written[0]['status']);

	}//end testAJobWithoutAnArchiveIsNotCompleted()

	/**
	 * Issue #1210: the ceiling is the administered one; a caller may lower
	 * it but not raise it.
	 *
	 * @return void
	 */
	public function testTheCeilingIsAdministeredAndACallerCanOnlyLowerIt(): void {
		$this->administeredCeiling = 5000;
		$service = $this->service(rows: [], records: []);

		$this->assertSame(5000, $service->ceiling());
		$this->assertSame(5000, $service->ceiling(requested: 999999999));
		$this->assertSame(1000, $service->ceiling(requested: 1000));

	}//end testTheCeilingIsAdministeredAndACallerCanOnlyLowerIt()
}//end class

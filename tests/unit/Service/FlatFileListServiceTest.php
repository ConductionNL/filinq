<?php

/**
 * Unit tests for FlatFileListService
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
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\ContentSearchClient;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\FlatFileListService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\ReadableFileResolver;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Asserts that every file on every record of a case is in the list, that each
 * row names the record it belongs to, and that a file the reader cannot see is
 * left out rather than listed as a name with nothing behind it.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FlatFileListServiceTest extends TestCase {

	/**
	 * Build the service over records and the files behind them.
	 *
	 * @param array<int, array<string, mixed>> $records The stored document records.
	 * @param array<int, string> $files File id to file name, for the files this user can see.
	 * @param array<int, array<string, mixed>> $dossiers The stored dossiers.
	 * @param array<int, string> $objectFiles File id to file name, for files in an object's folder this user may read through the object.
	 * @param array<int, int> $parents File id to the id of the folder it is in.
	 * @param ContentSearchClient|null $content The content query, for the content mode.
	 *
	 * @return FlatFileListService The service under test.
	 */
	private function service(
		array $records,
		array $files,
		array $dossiers=[],
		array $objectFiles=[],
		array $parents=[],
		?ContentSearchClient $content=null
	): FlatFileListService {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug) use ($records, $dossiers): array {
				if ($schemaSlug === 'dossier') {
					return $dossiers;
				}

				return $records;
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturnCallback(
			function (int $fileId) use ($files, $parents): array {
				if (isset($files[$fileId]) === false) {
					return [];
				}

				$parent = $this->createMock(originalClassName: Folder::class);
				$parent->method('getId')->willReturn($parents[$fileId] ?? 1);
				$file = $this->createMock(File::class);
				$file->method('getParent')->willReturn($parent);
				$file->method('getName')->willReturn($files[$fileId]);
				$file->method('getPath')->willReturn('/anna/files/Zaken/' . $files[$fileId]);
				$file->method('getSize')->willReturn(1024);
				$file->method('getMTime')->willReturn(1789000000);
				$file->method('getMimetype')->willReturn('application/pdf');

				return [$file];
			}
		);

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturn($userFolder);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$scope = new class($objectFiles) {
			/**
			 * @param array<int, string> $files The object files this user may read.
			 */
			public function __construct(private array $files) {
			}

			/**
			 * @param int $fileId The file id.
			 *
			 * @return bool Whether the object read rule admits this user.
			 */
			public function mayReadFile(int $fileId): bool {
				return isset($this->files[$fileId]);
			}
		};
		$fileService = new class($objectFiles, $this) {
			/**
			 * @param array<int, string> $files The object files.
			 * @param FlatFileListServiceTest $test The test, to build file doubles.
			 */
			public function __construct(private array $files, private FlatFileListServiceTest $test) {
			}

			/**
			 * @param int $fileId The file id.
			 *
			 * @return File|null The file.
			 */
			public function getFileById(int $fileId): ?File {
				if (isset($this->files[$fileId]) === false) {
					return null;
				}

				return $this->test->objectFile(name: $this->files[$fileId]);
			}
		};
		$locator = $this->createMock(originalClassName: OpenRegisterServiceLocator::class);
		$locator->method('get')->willReturnCallback(
			static fn (string $className): mixed => match ($className) {
				ReadableFileResolver::OR_READ_SCOPE => $scope,
				ReadableFileResolver::OR_FILE_SERVICE => $fileService,
			}
		);

		return new FlatFileListService(
			new FinalDocumentRepository($resolver, $this->createMock(LoggerInterface::class)),
			new ReadableFileResolver(
				rootFolder: $rootFolder,
				userSession: $session,
				locator: $locator,
				logger: $this->createMock(originalClassName: LoggerInterface::class)
			),
			$content,
		);

	}//end service()

	/**
	 * A file double in an object's folder, in the openregister account's home.
	 *
	 * @param string $name The file name.
	 *
	 * @return File The file.
	 */
	public function objectFile(string $name): File {
		$file = $this->createMock(originalClassName: File::class);
		$file->method('getName')->willReturn($name);
		$file->method('getPath')->willReturn('/openregister/files/Open Registers/Filinq Register/dossier-1/' . $name);
		$file->method('getSize')->willReturn(2048);
		$file->method('getMTime')->willReturn(1789000000);
		$file->method('getMimetype')->willReturn('application/pdf');

		return $file;

	}//end objectFile()

	/**
	 * One record on the case, for a file id.
	 *
	 * @param string $uuid The record uuid.
	 * @param int $fileId The file id.
	 *
	 * @return array<string, mixed> The record.
	 */
	private function record(string $uuid, int $fileId): array {
		return [
			'uuid' => $uuid,
			'fileId' => $fileId,
			'documentName' => 'Record ' . $uuid,
			'status' => 'draft',
			'domains' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']],
		];

	}//end record()

	/**
	 * The jurist finds every attachment, each naming its record.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testEveryFileOnTheCaseIsListedWithItsRecord(): void {
		$records = [];
		$files = [];
		for ($index = 1; $index <= 12; $index++) {
			$records[] = $this->record(uuid: 'record-' . $index, fileId: (4700 + $index));
			$files[(4700 + $index)] = sprintf('bijlage-%02d.pdf', $index);
		}

		$page = $this->service(records: $records, files: $files)->listFor(
			domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'],
			limit: 50
		);

		$this->assertSame(12, $page['total']);
		$this->assertCount(12, $page['results']);
		$this->assertSame('bijlage-01.pdf', $page['results'][0]['name']);
		$this->assertSame('Record record-1', $page['results'][0]['record']['name']);

	}//end testEveryFileOnTheCaseIsListedWithItsRecord()

	/**
	 * A record on another case is not on this list.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testARecordOnAnotherCaseIsNotListed(): void {
		$elsewhere = $this->record(uuid: 'record-2', fileId: 4712);
		$elsewhere['domains'] = [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-9']];

		$page = $this->service(
			records: [$this->record(uuid: 'record-1', fileId: 4711), $elsewhere],
			files: [4711 => 'hier.pdf', 4712 => 'elders.pdf']
		)->listFor(domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']);

		$this->assertSame(1, $page['total']);
		$this->assertSame('hier.pdf', $page['results'][0]['name']);

	}//end testARecordOnAnotherCaseIsNotListed()

	/**
	 * A file this reader cannot see is left out, not listed as a bare name.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testAFileTheReaderCannotSeeIsLeftOut(): void {
		$page = $this->service(
			records: [$this->record(uuid: 'record-1', fileId: 4711), $this->record(uuid: 'record-2', fileId: 4712)],
			files: [4711 => 'zichtbaar.pdf']
		)->listFor(domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']);

		$this->assertSame(1, $page['total']);

	}//end testAFileTheReaderCannotSeeIsLeftOut()

	/**
	 * The list pages, and says how many pages there are.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testTheListPages(): void {
		$records = [];
		$files = [];
		for ($index = 1; $index <= 10; $index++) {
			$records[] = $this->record(uuid: 'record-' . $index, fileId: (4700 + $index));
			$files[(4700 + $index)] = sprintf('bijlage-%02d.pdf', $index);
		}

		$service = $this->service(records: $records, files: $files);
		$domain = ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'];

		$first = $service->listFor(domain: $domain, page: 1, limit: 4);
		$last = $service->listFor(domain: $domain, page: 3, limit: 4);

		$this->assertSame(3, $first['pages']);
		$this->assertCount(4, $first['results']);
		$this->assertCount(2, $last['results']);

	}//end testTheListPages()

	/**
	 * The list filters on the file name.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testTheListFiltersOnTheFileName(): void {
		$page = $this->service(
			records: [$this->record(uuid: 'record-1', fileId: 4711), $this->record(uuid: 'record-2', fileId: 4712)],
			files: [4711 => 'hoorzitting.pdf', 4712 => 'besluit.pdf']
		)->listFor(domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'], search: 'HOOR');

		$this->assertSame(1, $page['total']);
		$this->assertSame('hoorzitting.pdf', $page['results'][0]['name']);

	}//end testTheListFiltersOnTheFileName()

	/**
	 * A file in a dossier's folder names that dossier; a loose file names none.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testAFileNamesTheDossierItIsIn(): void {
		$dossier = [
			'uuid' => 'dossier-1',
			'name' => 'Fietspad Lindelaan',
			'bases' => [],
			'documents' => ['4713'],
			'@self' => ['files' => [4711, 4712]],
		];
		$page = $this->service(
			records: [
				$this->record(uuid: 'record-1', fileId: 4711),
				$this->record(uuid: 'record-3', fileId: 4713),
				$this->record(uuid: 'record-4', fileId: 4714),
			],
			files: [4711 => 'advies.pdf', 4713 => 'raadsvoorstel.pdf', 4714 => 'los.pdf'],
			dossiers: [$dossier]
		)->listFor(domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']);

		$byName = array_column(array: $page['results'], column_key: 'dossier', index_key: 'name');
		$named  = ['id' => 'dossier-1', 'name' => 'Fietspad Lindelaan'];
		$this->assertSame(expected: $named, actual: $byName['advies.pdf']);
		$this->assertSame(expected: $named, actual: $byName['raadsvoorstel.pdf']);
		$this->assertNull(actual: $byName['los.pdf']);

	}//end testAFileNamesTheDossierItIsIn()

	/**
	 * A dossier read from the register carries its folder but no file list
	 * (OpenRegister fills `@self.files` only when it renders an object), so a
	 * file in that folder still names the dossier.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testAFileInADossiersFolderNamesItWithoutAFileList(): void {
		$dossier = [
			'uuid' => 'dossier-1',
			'name' => 'Fietspad Lindelaan',
			'@self' => ['files' => [], 'folder' => 900],
		];
		$page = $this->service(
			records: [
				$this->record(uuid: 'record-1', fileId: 4711),
				$this->record(uuid: 'record-4', fileId: 4714),
			],
			files: [4711 => 'advies.pdf', 4714 => 'los.pdf'],
			dossiers: [$dossier],
			parents: [4711 => 900, 4714 => 901]
		)->listFor(domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']);

		$byName = array_column(array: $page['results'], column_key: 'dossier', index_key: 'name');
		$this->assertSame(expected: ['id' => 'dossier-1', 'name' => 'Fietspad Lindelaan'], actual: $byName['advies.pdf']);
		$this->assertNull(actual: $byName['los.pdf']);

	}//end testAFileInADossiersFolderNamesItWithoutAFileList()

	/**
	 * A file in the folder of an object the reader may read is listed, though
	 * it is not in the reader's own Files.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-register/spec.md
	 */
	public function testAFileInTheFolderOfAReadableObjectIsListed(): void {
		$page = $this->service(
			records: [$this->record(uuid: 'record-1', fileId: 21064), $this->record(uuid: 'record-2', fileId: 21065)],
			files: [],
			objectFiles: [21064 => '02-advies-verlichting-fietspad.pdf']
		)->listFor(domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']);

		$this->assertSame(expected: 1, actual: $page['total']);
		$this->assertSame(expected: '02-advies-verlichting-fietspad.pdf', actual: $page['results'][0]['name']);
		$this->assertSame(expected: 'record-1', actual: $page['results'][0]['record']['uuid']);

	}//end testAFileInTheFolderOfAReadableObjectIsListed()
	/**
	 * Four records on the case, one of which has "lindenlaan" in its name.
	 *
	 * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>} The records and their files.
	 */
	private function caseWithFourFiles(): array {
		$records = [];
		$files   = [
			4801 => 'besluit.pdf',
			4802 => 'brief.pdf',
			4803 => 'lindenlaan-situatie.pdf',
			4804 => 'notitie.pdf',
		];
		foreach (array_keys($files) as $fileId) {
			$records[] = $this->record(uuid: 'record-' . $fileId, fileId: $fileId);
		}

		return [$records, $files];

	}//end caseWithFourFiles()

	/**
	 * In content mode, name matches come first, then content matches by rank with their snippet.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function testContentModeListsNameMatchesThenContentMatchesByRank(): void {
		[$records, $files] = $this->caseWithFourFiles();
		$content = $this->createMock(originalClassName: ContentSearchClient::class);
		$content->expects($this->once())->method(constraint: 'search')
			->with('Lindenlaan', [4801, 4802, 4803, 4804])
			->willReturn(
				[
					'available' => true,
					'hits' => [
						['fileId' => 4802, 'rank' => 0.9, 'snippet' => 'aan de <mark>Lindenlaan</mark>'],
						['fileId' => 4803, 'rank' => 0.8, 'snippet' => 'already a name match'],
						['fileId' => 4801, 'rank' => 0.3, 'snippet' => 'de <mark>Lindenlaan</mark> 4'],
					],
					'unread' => [4804],
				]
			);

		$page = $this->service(records: $records, files: $files, content: $content)->searchContent(
			domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'],
			search: 'Lindenlaan'
		);

		$this->assertSame(expected: ['lindenlaan-situatie.pdf', 'brief.pdf', 'besluit.pdf'], actual: array_column($page['results'], 'name'));
		$this->assertSame(expected: ['name', 'content', 'content'], actual: array_column($page['results'], 'match'));
		$this->assertArrayNotHasKey(key: 'snippet', array: $page['results'][0]);
		$this->assertSame(expected: 'aan de <mark>Lindenlaan</mark>', actual: $page['results'][1]['snippet']);
		$this->assertSame(expected: 3, actual: $page['total']);
		$this->assertSame(expected: ['available' => true, 'unread' => 1, 'unreadFileIds' => [4804]], actual: $page['content']);

	}//end testContentModeListsNameMatchesThenContentMatchesByRank()

	/**
	 * A file the query does not return is not listed as a content match, and a hit outside the list never appears.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function testAFileTheQueryLeavesOutNeverAppears(): void {
		[$records, $files] = $this->caseWithFourFiles();
		$content = $this->createMock(originalClassName: ContentSearchClient::class);
		$content->method('search')->willReturn(
			[
				'available' => true,
				'hits' => [['fileId' => 9999, 'rank' => 1.0, 'snippet' => 'a file of another case']],
				'unread' => [],
			]
		);

		$page = $this->service(records: $records, files: $files, content: $content)->searchContent(
			domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'],
			search: 'kapvergunning'
		);

		$this->assertSame(expected: [], actual: $page['results']);
		$this->assertSame(expected: 0, actual: $page['total']);
		$this->assertSame(expected: ['available' => true, 'unread' => 0, 'unreadFileIds' => []], actual: $page['content']);

	}//end testAFileTheQueryLeavesOutNeverAppears()

	/**
	 * Without the content query the content mode keeps the name search and says it is not available.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function testContentModeWithoutTheQueryKeepsTheNameSearch(): void {
		[$records, $files] = $this->caseWithFourFiles();

		$page = $this->service(records: $records, files: $files)->searchContent(
			domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'],
			search: 'lindenlaan'
		);

		$this->assertSame(expected: ['lindenlaan-situatie.pdf'], actual: array_column($page['results'], 'name'));
		$this->assertSame(expected: ['available' => false, 'unread' => 0, 'unreadFileIds' => []], actual: $page['content']);

	}//end testContentModeWithoutTheQueryKeepsTheNameSearch()

	/**
	 * The name mode asks no content query and carries no content block.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function testNameModeAsksNoContentQuery(): void {
		[$records, $files] = $this->caseWithFourFiles();
		$content = $this->createMock(originalClassName: ContentSearchClient::class);
		$content->expects($this->never())->method(constraint: 'search');

		$page = $this->service(records: $records, files: $files, content: $content)->listFor(
			domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'],
			search: 'brief'
		);

		$this->assertSame(expected: ['brief.pdf'], actual: array_column($page['results'], 'name'));
		$this->assertArrayNotHasKey(key: 'content', array: $page);

	}//end testNameModeAsksNoContentQuery()
}//end class

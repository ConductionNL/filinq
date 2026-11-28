<?php

/**
 * A template import is queued with its archive stored, and running it turns
 * each office file into a template and each fragments/ file into a fragment,
 * skipping and reporting what it cannot import.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

use Exception;
use OCA\Filinq\Exception\OfficeSourceRefusedException;
use OCA\Filinq\Kernel\Contracts\OpenRegisterResolverInterface;
use OCA\Filinq\Kernel\JobQueueInterface;
use OCA\Filinq\Service\OfficeTemplate\OfficeSourceStore;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateService;
use OCA\Filinq\Service\OfficeTemplate\TemplateImportFiles;
use OCA\Filinq\Service\OfficeTemplate\TemplateImportService;
use OCA\Filinq\Service\OfficeTemplate\TemplatesRegisterObjects;
use OCA\Filinq\Service\OfficeTemplate\TextFragmentService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/OfficeFixtures.php';

/**
 * @covers \OCA\Filinq\Service\OfficeTemplate\TemplateImportService
 * @uses   \OCA\Filinq\Service\OfficeTemplate\TemplateImportFiles
 * @uses   \OCA\Filinq\Exception\OfficeSourceRefusedException
 */
class TemplateImportServiceTest extends TestCase {

	private TemplatesRegisterObjects&MockObject $objects;

	private OfficeTemplateService&MockObject $templates;

	private TextFragmentService&MockObject $fragments;

	private OfficeSourceStore&MockObject $store;

	private JobQueueInterface&MockObject $jobs;

	private IRootFolder&MockObject $root;

	/**
	 * The job states saved, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * Build the doubles; saving a job echoes it with an id.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(TemplatesRegisterObjects::class);
		$this->objects->method('save')->willReturnCallback(
			function (string $schema, array $data, ?string $id=null): array {
				$this->saved[] = ['schema' => $schema, 'id' => $id] + $data;
				return ['id' => ($id ?? 'job-1')] + $data;
			}
		);
		$this->templates = $this->createMock(OfficeTemplateService::class);
		$this->fragments = $this->createMock(TextFragmentService::class);
		$this->store = $this->createMock(OfficeSourceStore::class);
		$this->jobs = $this->createMock(JobQueueInterface::class);
		$this->root = $this->createMock(IRootFolder::class);
	}//end setUp()

	/**
	 * Starting stores the archive, saves a queued job counting its files and
	 * queues the run as the starter.
	 *
	 * @return void
	 */
	public function testStartingStoresTheArchiveAndQueuesTheRun(): void {
		$zip = OfficeFixtures::package(entries: ['a.docx' => 'x', 'map/' => '', '__MACOSX/a.docx' => 'x', 'map/.DS_Store' => 'x', 'map/b.odt' => 'y']);
		$this->store->expects($this->once())->method('store')->with($zip, 'zip')->willReturn(['fileId' => 31, 'contentHash' => 'h']);
		$this->jobs->expects($this->once())->method('add')->with(JobQueueInterface::TEMPLATE_IMPORT, ['jobId' => 'job-1', 'userId' => 'anne']);

		$job = $this->service()->start(fields: ['namespace' => 'filinq', 'boundSchema' => 'dossier', 'boundRegister' => ' '], archive: $zip, userId: 'anne');

		$this->assertSame(
			['id' => 'job-1', 'status' => 'queued', 'totalFiles' => 2, 'imported' => 0, 'failed' => 0, 'skipped' => 0, 'startedBy' => 'anne', 'namespace' => 'filinq', 'report' => [], 'boundSchema' => 'dossier', 'archiveFileId' => 31],
			$job
		);
		$this->assertSame('templateImportJob', $this->saved[0]['schema']);
	}//end testStartingStoresTheArchiveAndQueuesTheRun()

	/**
	 * A start without namespace, or with neither archive nor folder, is
	 * refused and queues nothing.
	 *
	 * @return void
	 */
	public function testAStartWithoutItsInputsIsRefused(): void {
		$this->jobs->expects($this->never())->method('add');
		foreach ([[['path' => 'x'], null, 'Namespace is required'], [['namespace' => 'filinq'], null, 'A ZIP file or a folder path is required'], [['namespace' => 'filinq'], 'not a zip', 'The file is not a ZIP archive.']] as [$fields, $archive, $message]) {
			try {
				$this->service()->start(fields: $fields, archive: $archive, userId: 'anne');
				$this->fail('Accepted: '.$message);
			} catch (Exception $e) {
				$this->assertSame([$message, 400], [$e->getMessage(), $e->getCode()]);
			}
		}
	}//end testAStartWithoutItsInputsIsRefused()

	/**
	 * Three templates and two fragments import with a report row each; a
	 * corrupt file fails with its reason and the rest goes on; other files
	 * are skipped.
	 *
	 * @return void
	 */
	public function testARunImportsTemplatesAndFragmentsAndReportsEachFile(): void {
		$zip = OfficeFixtures::package(entries: [
			'beschikkingen/parkeervergunning.docx' => 'docx-1',
			'brieven/ontvangst.odt' => 'odt-1',
			'kapot.docx' => 'corrupt',
			'fragments/Ondertekening burgemeester.txt' => "Hoogachtend,\r\nde burgemeester",
			'fragments/kop.html' => '<p>Gemeente <b>Demostad</b></p><p>Afdeling &amp; team</p>',
			'leesmij.pdf' => '%PDF',
		]);
		$this->objects->method('get')->willReturn(['id' => 'job-1', 'status' => 'queued', 'namespace' => 'filinq', 'boundSchema' => 'dossier', 'startedBy' => 'anne', 'archiveFileId' => 31]);
		$this->store->method('read')->with(31)->willReturn($zip);
		$created = [];
		$this->templates->method('create')->willReturnCallback(
			static function (array $fields, string $fileName, string $bytes) use (&$created): array {
				if ($bytes === 'corrupt') {
					throw new OfficeSourceRefusedException(message: 'The file could not be read as a DOCX document.', reason: OfficeSourceRefusedException::REASON_TYPE);
				}

				$created[] = [$fields, $fileName];
				return [
					'template' => ['id' => 't-'.count($created), 'mergeFields' => [['path' => 'a'], ['path' => 'b']]],
					'converted' => str_ends_with($fileName, '.odt'),
					'tagReport' => ['unknown' => ($bytes === 'odt-1' ? ['naam_aanvrager'] : [])],
				];
			}
		);
		$fragments = [];
		$this->fragments->method('create')->willReturnCallback(
			static function (array $data) use (&$fragments): array {
				$fragments[] = $data;
				return ['id' => 'f-'.count($fragments), 'slug' => $data['slug']];
			}
		);

		$job = $this->service()->run(jobId: 'job-1');

		$this->assertSame(['completed', 6, 4, 1, 1], [$job['status'], $job['totalFiles'], $job['imported'], $job['failed'], $job['skipped']]);
		$this->assertSame(
			[
				[['name' => 'parkeervergunning', 'namespace' => 'filinq', 'boundSchema' => 'dossier', 'category' => 'beschikkingen'], 'parkeervergunning.docx'],
				[['name' => 'ontvangst', 'namespace' => 'filinq', 'boundSchema' => 'dossier', 'category' => 'brieven'], 'ontvangst.odt'],
			],
			$created
		);
		$this->assertSame(
			[
				['name' => 'Ondertekening burgemeester', 'slug' => 'ondertekening-burgemeester', 'content' => "Hoogachtend,\nde burgemeester", 'namespace' => 'filinq'],
				['name' => 'kop', 'slug' => 'kop', 'content' => "Gemeente Demostad\nAfdeling & team", 'namespace' => 'filinq'],
			],
			$fragments
		);
		$byFile = array_column($job['report'], null, 'file');
		$this->assertSame(['file' => 'brieven/ontvangst.odt', 'status' => 'imported', 'kind' => 'template', 'id' => 't-2', 'tags' => 2, 'unknownTags' => 1, 'unknown' => ['naam_aanvrager'], 'converted' => true], $byFile['brieven/ontvangst.odt']);
		$this->assertSame(['file' => 'kapot.docx', 'status' => 'failed', 'reason' => 'The file could not be read as a DOCX document.'], $byFile['kapot.docx']);
		$this->assertSame('skipped', $byFile['leesmij.pdf']['status']);
		$this->assertSame('fragment', $byFile['fragments/kop.html']['kind']);
		$this->assertSame('running', $this->saved[0]['status']);
		$this->assertSame('job-1', $this->saved[0]['id']);
		$this->assertArrayHasKey('finishedAt', $job);
	}//end testARunImportsTemplatesAndFragmentsAndReportsEachFile()

	/**
	 * A finished job is not run again.
	 *
	 * @return void
	 */
	public function testAFinishedJobIsNotRunAgain(): void {
		$this->objects->method('get')->willReturn(['id' => 'job-1', 'status' => 'completed', 'imported' => 3]);
		$this->templates->expects($this->never())->method('create');

		$this->assertSame(['id' => 'job-1', 'status' => 'completed', 'imported' => 3], $this->service()->run(jobId: 'job-1'));
		$this->assertSame([], $this->saved);
	}//end testAFinishedJobIsNotRunAgain()

	/**
	 * A job whose archive is gone fails with the reason in its report.
	 *
	 * @return void
	 */
	public function testAJobWhoseArchiveIsGoneFails(): void {
		$this->objects->method('get')->willReturn(['id' => 'job-1', 'status' => 'queued', 'namespace' => 'filinq', 'archiveFileId' => 31]);
		$this->store->method('read')->willReturn(null);

		$job = $this->service()->run(jobId: 'job-1');

		$this->assertSame('failed', $job['status']);
		$this->assertSame('The import source could not be read: the stored archive is gone', $job['report'][0]['reason']);
	}//end testAJobWhoseArchiveIsGoneFails()

	/**
	 * A folder in the starter's Files imports like an archive, subfolders
	 * included.
	 *
	 * @return void
	 */
	public function testAFolderImportsLikeAnArchive(): void {
		$doc = $this->createMock(File::class);
		$doc->method('getName')->willReturn('besluit.docx');
		$doc->method('getContent')->willReturn('docx');
		$sub = $this->createMock(Folder::class);
		$sub->method('getName')->willReturn('besluiten');
		$sub->method('getDirectoryListing')->willReturn([$doc]);
		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willReturn([$sub]);
		$home = $this->createMock(Folder::class);
		$home->method('get')->with('Sjablonen')->willReturn($folder);
		$this->root->method('getUserFolder')->with('anne')->willReturn($home);
		$this->objects->method('get')->willReturn(['id' => 'job-1', 'status' => 'queued', 'namespace' => 'filinq', 'startedBy' => 'anne', 'sourcePath' => 'Sjablonen']);
		$this->templates->expects($this->once())->method('create')
			->with(['name' => 'besluit', 'namespace' => 'filinq', 'category' => 'besluiten'], 'besluit.docx', 'docx')
			->willReturn(['template' => ['id' => 't-1', 'mergeFields' => []], 'converted' => false, 'tagReport' => ['unknown' => []]]);

		$job = $this->service()->run(jobId: 'job-1');

		$this->assertSame(['completed', 1, 1], [$job['status'], $job['totalFiles'], $job['imported']]);
	}//end testAFolderImportsLikeAnArchive()

	/**
	 * The service over the doubles.
	 *
	 * @return TemplateImportService
	 */
	private function service(): TemplateImportService {
		return new TemplateImportService(
			objects: $this->objects,
			templates: $this->templates,
			fragments: $this->fragments,
			files: new TemplateImportFiles(store: $this->store, rootFolder: $this->root),
			jobs: $this->jobs,
			resolver: $this->createMock(OpenRegisterResolverInterface::class)
		);
	}//end service()
}//end class

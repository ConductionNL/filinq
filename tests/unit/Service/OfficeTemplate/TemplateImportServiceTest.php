<?php

/**
 * TemplateImportService tests
 *
 * A ZIP of templates and fragments imports as a job whose state is a
 * `templateImportJob` object (validated against the real fragment), a
 * corrupt file is reported and skipped, and only the starter reads the job.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

require_once __DIR__ . '/OfficeTemplateDoubles.php';

use OCA\Filinq\BackgroundJob\TemplateImportJob;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRefused;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateService;
use OCA\Filinq\Service\OfficeTemplate\TemplateImportService;
use OCA\Filinq\Service\OfficeTemplate\TemplateObjectRepository;
use OCP\BackgroundJob\IJobList;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\OfficeTemplate\TemplateImportService
 * @covers \OCA\Filinq\Service\OfficeTemplate\TemplateObjectRepository
 * @covers \OCA\Filinq\BackgroundJob\TemplateImportJob
 */
class TemplateImportServiceTest extends TestCase {

	private OfficeObjectStore $objects;

	private MemorySourceStore $store;

	/**
	 * Queued jobs: [class, argument].
	 *
	 * @var array<int, array{0: string, 1: mixed}>
	 */
	private array $queued = [];

	/**
	 * Files the template double was handed.
	 *
	 * @var string[]
	 */
	private array $created = [];

	/**
	 * Users the session acted as, in order (null = reset).
	 *
	 * @var array<int, string|null>
	 */
	private array $actedAs = [];

	protected function setUp(): void {
		$this->objects = new OfficeObjectStore();
		$this->store = new MemorySourceStore();
		$this->queued = [];
		$this->created = [];
		$this->actedAs = [];
	}

	private function service(): TemplateImportService {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$office = $this->createMock(OfficeTemplateService::class);
		$office->method('createFromUpload')->willReturnCallback(function (string $fileName, string $bytes, array $meta): array {
			if ($fileName === 'kapot.docx') {
				throw new OfficeTemplateRefused(message: 'The document is damaged: its main part is not valid XML.', reason: 'corrupt');
			}

			$this->created[] = $fileName . '|' . ($meta['category'] ?? '') . '|' . $meta['name'];

			return [
				'template' => ['id' => 'tpl-' . count($this->created), 'mergeFields' => ['a', 'b', 'c']],
				'tagReport' => ['unknown' => ['naam_aanvrager']],
				'converted' => false,
			];
		});
		$jobs = $this->createMock(IJobList::class);
		$jobs->method('add')->willReturnCallback(function ($job, $argument = null): void {
			$this->queued[] = [$job, $argument];
		});
		$user = $this->createMock(IUser::class);
		$users = $this->createMock(IUserManager::class);
		$users->method('get')->willReturnCallback(static fn (string $uid): ?IUser => ($uid === 'demo-communicatie' ? $user : null));
		$session = $this->createMock(IUserSession::class);
		$session->method('setVolatileActiveUser')->willReturnCallback(function (?IUser $acting): void {
			$this->actedAs[] = ($acting === null ? null : 'demo-communicatie');
		});

		return new TemplateImportService(
			officeTemplates: $office,
			objects: new TemplateObjectRepository(objectResolver: $resolver),
			store: $this->store,
			jobList: $jobs,
			users: $users,
			session: $session
		);
	}

	private function estate(): string {
		return OfficeFixtures::zip([
			'beschikkingen/beschikking-parkeervergunning.docx' => OfficeFixtures::bytes('beschikking-parkeervergunning.docx'),
			'brieven/brief-ontvangstbevestiging.docx' => OfficeFixtures::bytes('brief-ontvangstbevestiging.docx'),
			'kapot.docx' => OfficeFixtures::bytes('corrupt.docx'),
			'fragments/ondertekening-burgemeester.txt' => "Hoogachtend,\nde burgemeester van Demostad,",
			'fragments/Slot Brief.html' => '<p>Met vriendelijke groet,</p><p>Team Vergunningen</p>',
			'__MACOSX/._kapot.docx' => 'junk',
			'leesmij.pdf' => '%PDF',
		]);
	}

	public function testZipOfHouseStyleTemplatesImportsWithAReport(): void {
		$service = $this->service();
		$job = $service->start(archiveName: 'huisstijl.zip', bytes: $this->estate(), meta: ['namespace' => 'filinq', 'boundSchema' => 'dossier'], userId: 'demo-communicatie');

		$this->assertSame('queued', $job['status']);
		$this->assertSame(5, $job['totalFiles']);
		$this->assertSame([TemplateImportJob::class, ['jobId' => $job['uuid']]], $this->queued[0]);

		$done = $service->run(jobId: $job['uuid']);

		$this->assertSame('completed', $done['status']);
		$this->assertSame(4, $done['imported']);
		$this->assertSame(1, $done['failed']);
		$this->assertSame(['demo-communicatie', null], $this->actedAs, 'the job acts as its starter and lets go');
		$this->assertSame(
			['beschikking-parkeervergunning.docx|beschikkingen|Beschikking parkeervergunning', 'brief-ontvangstbevestiging.docx|brieven|Brief ontvangstbevestiging'],
			$this->created
		);
		$fragments = array_values($this->objects->of('textFragment'));
		$this->assertSame(['ondertekening-burgemeester', 'slot-brief'], array_column($fragments, 'slug'));
		$this->assertStringContainsString("Met vriendelijke groet,\nTeam Vergunningen", $fragments[1]['content']);
		$rows = array_column($done['report'], null, 'file');
		$this->assertSame(['naam_aanvrager'], $rows['brieven/brief-ontvangstbevestiging.docx']['unknownTags']);
		$this->assertSame(3, $rows['brieven/brief-ontvangstbevestiging.docx']['tags']);
		$this->assertSame([], $this->store->files, 'the stored ZIP is removed when the job is done');
		$this->assertNull($done['archiveFileId']);
	}

	public function testACorruptFileDoesNotAbortTheImport(): void {
		$service = $this->service();
		$job = $service->start(archiveName: 'huisstijl.zip', bytes: $this->estate(), meta: ['namespace' => 'filinq'], userId: 'demo-communicatie');
		$rows = array_column($service->run(jobId: $job['uuid'])['report'], null, 'file');

		$this->assertSame('failed', $rows['kapot.docx']['status']);
		$this->assertStringContainsString('damaged', $rows['kapot.docx']['reason']);
		$this->assertSame('imported', $rows['fragments/ondertekening-burgemeester.txt']['status']);
	}

	public function testOnlyTheStarterReadsTheJob(): void {
		$service = $this->service();
		$job = $service->start(archiveName: 'huisstijl.zip', bytes: $this->estate(), meta: ['namespace' => 'filinq'], userId: 'demo-communicatie');

		$this->assertSame('queued', $service->status(jobId: $job['uuid'], userId: 'demo-communicatie')['status']);
		$this->expectException(OfficeTemplateRefused::class);
		$this->expectExceptionCode(404);
		$service->status(jobId: $job['uuid'], userId: 'mallory');
	}

	public function testAnUploadThatIsNotAZipIsRefused(): void {
		$this->expectException(OfficeTemplateRefused::class);
		$this->expectExceptionCode(422);
		$this->service()->start(archiveName: 'x.zip', bytes: '%PDF-1.7', meta: ['namespace' => 'filinq'], userId: 'demo-communicatie');
	}

	public function testAJobWhoseStarterIsGoneFails(): void {
		$service = $this->service();
		$job = $service->start(archiveName: 'huisstijl.zip', bytes: $this->estate(), meta: ['namespace' => 'filinq'], userId: 'vertrokken');
		$done = $service->run(jobId: $job['uuid']);

		$this->assertSame('failed', $done['status']);
		$this->assertSame([], $this->created);
	}
}

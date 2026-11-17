<?php

/**
 * The PDF/UA attach check, over the real check, the veraPDF stand-in, an
 * in-memory register and an in-memory system tag store: one verdict tag per
 * file, `pdfua-niet-gecontroleerd` and no report when there is no verdict.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-every-pdf-attached-to-an-openregister-object-is-checked-for-pdfua-req-ddpum-006
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\PdfUaAttachCheckJob;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService;
use OCA\Filinq\Service\VeraPdf\AccessibilityGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use OCA\Filinq\Service\VeraPdf\PdfUaVerdictTagger;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCA\Filinq\Tests\Unit\Service\SubjectErasure\SubjectErasureDoubles;
use OCA\Filinq\Tests\Unit\Service\VeraPdf\VeraPdfDoubles;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;

/**
 * @covers \OCA\Filinq\BackgroundJob\PdfUaAttachCheckJob
 * @covers \OCA\Filinq\Service\VeraPdf\PdfUaVerdictTagger
 * @uses   \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService
 * @uses   \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository
 * @uses   \OCA\Filinq\Service\VeraPdf\AccessibilityGuidance
 */
class PdfUaAttachCheckJobTest extends TestCase {
	use SubjectErasureDoubles;
	use VeraPdfDoubles;

	/**
	 * System tags by name => id.
	 *
	 * @var array<string, string>
	 */
	private array $systemTags = [];

	/**
	 * Tag ids assigned per file id.
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $assigned = [];

	/**
	 * The visibility each tag was created with: name => [visible, assignable].
	 *
	 * @var array<string, array{0: bool, 1: bool}>
	 */
	private array $created = [];

	protected function tearDown(): void {
		putenv('FAKE_VERAPDF_REPORT');
		foreach ($this->tempFiles as $path) {
			@unlink($path);
		}

		parent::tearDown();

	}//end tearDown()

	/**
	 * An in-memory system tag store.
	 *
	 * @return PdfUaVerdictTagger The real tagger over it.
	 */
	private function tagger(): PdfUaVerdictTagger {
		$tagOf = function (string $name): ISystemTag {
			$tag = $this->createMock(ISystemTag::class);
			$tag->method('getId')->willReturn($this->systemTags[$name]);
			$tag->method('getName')->willReturn($name);

			return $tag;
		};
		$manager = $this->createMock(ISystemTagManager::class);
		$manager->method('getTag')->willReturnCallback(
			function (string $name) use ($tagOf): ISystemTag {
				if (isset($this->systemTags[$name]) === false) {
					throw new TagNotFoundException($name);
				}

				return $tagOf($name);
			}
		);
		$manager->method('createTag')->willReturnCallback(
			function (string $name, bool $visible, bool $assignable) use ($tagOf): ISystemTag {
				$this->systemTags[$name] = (string) (count($this->systemTags) + 1);
				$this->created[$name] = [$visible, $assignable];

				return $tagOf($name);
			}
		);
		$mapper = $this->createMock(ISystemTagObjectMapper::class);
		$mapper->method('assignTags')->willReturnCallback(
			function (string $objId, string $type, $tagIds): void {
				$this->assertSame('files', $type);
				$this->assigned[$objId] = array_values(array_unique(array_merge($this->assigned[$objId] ?? [], (array) $tagIds)));
			}
		);
		$mapper->method('unassignTags')->willReturnCallback(
			function (string $objId, string $type, $tagIds): void {
				$this->assertSame('files', $type);
				$this->assigned[$objId] = array_values(array_diff($this->assigned[$objId] ?? [], (array) $tagIds));
			}
		);

		return new PdfUaVerdictTagger($manager, $mapper);

	}//end tagger()

	/**
	 * The tag names on a file.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return array<int, string> The names.
	 */
	private function tagsOn(int $fileId): array {
		$names = array_flip($this->systemTags);
		$on = array_map(static fn (string $id): string => $names[$id], $this->assigned[(string) $fileId] ?? []);
		sort($on);

		return $on;

	}//end tagsOn()

	/**
	 * Run the job once for a file.
	 *
	 * @param int                                  $fileId The file id.
	 * @param AccessibilityConformanceService|null $check  The check to use instead of the real one.
	 *
	 * @return void
	 */
	private function runJob(int $fileId, ?AccessibilityConformanceService $check = null): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($fileId);
		$file->method('getMimetype')->willReturn('application/pdf');
		$file->method('getContent')->willReturn("%PDF-1.7\n<< /Creator (Word) >>");
		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturnCallback(static fn (int $id): ?File => ($id === $fileId ? $file : null));
		$check ??= new AccessibilityConformanceService(
			$this->veraPdf(),
			new AccessibilityGuidance(new ConformanceGuidance()),
			new AccessibilityConformanceReportRepository(new DocumentObjectServiceResolver($this->container(), $this->apps())),
			$this->clock()
		);

		$job = new PdfUaAttachCheckJob($this->createMock(ITimeFactory::class), $check, $this->tagger(), $root, new NullLogger());
		(new ReflectionMethod($job, 'run'))->invoke($job, ['fileId' => $fileId]);

	}//end runJob()

	/**
	 * The stored PDF/UA reports.
	 *
	 * @return array<int, array<string, mixed>> The reports.
	 */
	private function storedReports(): array {
		return array_values($this->rows[AccessibilityConformanceReportRepository::SCHEMA] ?? []);

	}//end storedReports()

	public function testACompliantFileIsTaggedConform(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfua1-conformant.json');

		$this->runJob(fileId: 812);

		$this->assertSame([PdfUaVerdictTagger::CONFORM], $this->tagsOn(fileId: 812));
		$this->assertTrue($this->storedReports()[0]['compliant']);
		$this->assertSame(AccessibilityConformanceService::TRIGGER_ATTACH, $this->storedReports()[0]['trigger']);
		$this->assertSame([true, false], $this->created[PdfUaVerdictTagger::CONFORM], 'Visible, but only the server assigns it.');

	}//end testACompliantFileIsTaggedConform()

	public function testANonCompliantFileIsTaggedNietConform(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfua1-not-conformant.json');

		$this->runJob(fileId: 812);

		$this->assertSame([PdfUaVerdictTagger::NOT_CONFORM], $this->tagsOn(fileId: 812));
		$this->assertFalse($this->storedReports()[0]['compliant']);

	}//end testANonCompliantFileIsTaggedNietConform()

	public function testNoValidatorTagsNotChecked(): void {
		$this->config[VeraPdfService::CFG_BINARY_PATH] = '/nonexistent/verapdf';

		$this->runJob(fileId: 812);

		$this->assertSame([PdfUaVerdictTagger::NOT_CHECKED], $this->tagsOn(fileId: 812));
		$this->assertSame([], $this->storedReports(), 'No compliant report, no report at all.');

	}//end testNoValidatorTagsNotChecked()

	public function testOutputItCannotReadTagsNotChecked(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfa3b-rendered.json');

		$this->runJob(fileId: 812);

		$this->assertSame([PdfUaVerdictTagger::NOT_CHECKED], $this->tagsOn(fileId: 812));
		$this->assertSame([], $this->storedReports());

	}//end testOutputItCannotReadTagsNotChecked()

	public function testTheTagsAreExclusive(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfua1-not-conformant.json');
		$this->runJob(fileId: 812);
		putenv('FAKE_VERAPDF_REPORT=pdfua1-conformant.json');
		$this->runJob(fileId: 812);

		$this->assertSame([PdfUaVerdictTagger::CONFORM], $this->tagsOn(fileId: 812));
		$this->assertCount(1, $this->storedReports(), 'A rerun updates the one report.');

		$this->config[VeraPdfService::CFG_BINARY_PATH] = '/nonexistent/verapdf';
		$this->runJob(fileId: 812);

		$this->assertSame([PdfUaVerdictTagger::NOT_CHECKED], $this->tagsOn(fileId: 812));

	}//end testTheTagsAreExclusive()

	public function testAFileThatIsGoneIsLeftAlone(): void {
		$check = $this->createMock(AccessibilityConformanceService::class);
		$check->expects($this->never())->method('checkFile');
		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturn(null);

		$job = new PdfUaAttachCheckJob($this->createMock(ITimeFactory::class), $check, $this->tagger(), $root, new NullLogger());
		(new ReflectionMethod($job, 'run'))->invoke($job, ['fileId' => 812]);

		$this->assertSame([], $this->assigned);

	}//end testAFileThatIsGoneIsLeftAlone()

	public function testAnUnknownVerdictIsRefused(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->tagger()->tag(fileId: 812, verdict: 'pdfua-ok');

	}//end testAnUnknownVerdictIsRefused()

	public function testTheJobIsInTheQueue(): void {
		$jobList = $this->createMock(IJobList::class);
		$jobList->expects($this->once())->method('add')->with(PdfUaAttachCheckJob::class, ['fileId' => 812]);

		(new \OCA\Filinq\BackgroundJob\JobQueue($jobList))->add(job: \OCA\Filinq\Kernel\JobQueueInterface::PDFUA_ATTACH_CHECK, argument: ['fileId' => 812]);

	}//end testTheJobIsInTheQueue()
}//end class

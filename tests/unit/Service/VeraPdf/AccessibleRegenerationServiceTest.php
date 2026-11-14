<?php

/**
 * Regenerating a generated PDF as PDF/UA, over the real check, the veraPDF
 * stand-in, an in-memory register holding the generation audit entries and
 * a recording tag store: a passing copy becomes a new version and is tagged
 * conform, a failing copy is discarded, an imported PDF is refused.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use OCA\Filinq\Exception\AccessibleRegenerationRefused;
use OCA\Filinq\Exception\VeraPdfException;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService;
use OCA\Filinq\Service\VeraPdf\AccessibilityGuidance;
use OCA\Filinq\Service\VeraPdf\AccessibleRegenerationService;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use OCA\Filinq\Service\VeraPdf\PdfUaVerdictTagger;
use OCA\Filinq\Tests\Unit\Service\SubjectErasure\SubjectErasureDoubles;
use OCP\Files\File;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\VeraPdf\AccessibleRegenerationService
 * @covers \OCA\Filinq\Exception\AccessibleRegenerationRefused
 * @uses   \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService
 * @uses   \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository
 * @uses   \OCA\Filinq\Service\VeraPdf\PdfUaVerdictTagger
 */
class AccessibleRegenerationServiceTest extends TestCase {
	use SubjectErasureDoubles;
	use VeraPdfDoubles;

	private const ORIGINAL = "%PDF-1.7\n<< /Creator (mPDF) >> original";

	private const COPY = "%PDF-1.7\n<< /StructTreeRoot 4 0 R >> accessible copy";

	/**
	 * The file's content, as putContent left it.
	 */
	private string $content = self::ORIGINAL;

	/**
	 * The generateDocument calls.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $generations = [];

	/**
	 * The verdict tags assigned, as [fileId, tag ids].
	 *
	 * @var array<int, array{0: string, 1: array<int, string>}>
	 */
	private array $assigned = [];

	protected function tearDown(): void {
		putenv('FAKE_VERAPDF_REPORT');
		foreach ($this->tempFiles as $path) {
			@unlink($path);
		}

		parent::tearDown();

	}//end tearDown()

	/**
	 * The register OpenRegister would be.
	 *
	 * @return DocumentObjectServiceResolver The resolver.
	 */
	private function register(): DocumentObjectServiceResolver {
		return new DocumentObjectServiceResolver($this->container(), $this->apps());

	}//end register()

	/**
	 * Record that Filinq generated file 812 from a template.
	 *
	 * @param array<string, mixed> $entry Fields over the defaults.
	 *
	 * @return void
	 */
	private function generated(array $entry = []): void {
		$this->register()->resolve()->saveObject(
			object: $entry + [
				'templateId' => 'tpl-besluit',
				'templateVersion' => 3,
				'templateName' => 'Besluit',
				'dataRefs' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'z-1']],
				'format' => 'pdf',
				'status' => 'generated',
				'fileId' => 812,
			],
			register: 'filinq',
			schema: 'generatedDocument'
		);

	}//end generated()

	/**
	 * The service over the stand-in, the register and doubles at the edges.
	 *
	 * @param string $renders What the renderer returns as content.
	 *
	 * @return AccessibleRegenerationService The service.
	 */
	private function service(string $renders = self::COPY): AccessibleRegenerationService {
		$documents = $this->createMock(DocumentServiceInterface::class);
		$documents->method('generateDocument')->willReturnCallback(
			function (string $templateId, array $dataRefs, array $options = []) use ($renders): array {
				$this->generations[] = ['templateId' => $templateId, 'dataRefs' => $dataRefs, 'options' => $options];

				return ['content' => $renders, 'format' => 'pdf'];
			}
		);
		$tags = $this->createMock(ISystemTagManager::class);
		$tags->method('getTag')->willReturnCallback(
			function (string $name): ISystemTag {
				$tag = $this->createMock(ISystemTag::class);
				$tag->method('getId')->willReturn($name);

				return $tag;
			}
		);
		$objects = $this->createMock(ISystemTagObjectMapper::class);
		$objects->method('assignTags')->willReturnCallback(
			function (string $objId, string $type, $ids): void {
				$this->assigned[] = [$objId, (array) $ids];
			}
		);
		$check = new AccessibilityConformanceService(
			$this->veraPdf(),
			new AccessibilityGuidance(new ConformanceGuidance()),
			new AccessibilityConformanceReportRepository($this->register()),
			$this->clock()
		);

		return new AccessibleRegenerationService($this->register(), $documents, $check, new PdfUaVerdictTagger($tags, $objects));

	}//end service()

	/**
	 * File 812, whose content putContent replaces.
	 *
	 * @return File The file.
	 */
	private function file(): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(812);
		$file->method('getMimetype')->willReturn('application/pdf');
		$file->method('getContent')->willReturnCallback(fn (): string => $this->content);
		$file->method('putContent')->willReturnCallback(
			function ($data): void {
				$this->content = (string) $data;
			}
		);

		return $file;

	}//end file()

	/**
	 * The stored PDF/UA reports.
	 *
	 * @return array<int, array<string, mixed>> The reports.
	 */
	private function storedReports(): array {
		return array_values($this->rows[AccessibilityConformanceReportRepository::SCHEMA] ?? []);

	}//end storedReports()

	public function testACompliantCopyReplacesTheOriginal(): void {
		$this->generated();
		putenv('FAKE_VERAPDF_REPORT=pdfua1-conformant.json');

		$result = $this->service()->regenerate(file: $this->file(), userId: 'petra');

		$this->assertTrue($result['replaced']);
		$this->assertSame(self::COPY, $this->content, 'The copy is the new version.');
		$this->assertSame(
			[['templateId' => 'tpl-besluit', 'dataRefs' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'z-1']], 'options' => ['format' => 'pdf', 'userId' => 'petra', 'pdfOptions' => ['accessible' => true], 'templateVersion' => 3]]],
			$this->generations,
			'The same template version over the same data, through the tagged path.'
		);
		$this->assertTrue($result['report']['compliant']);
		$this->assertSame(AccessibilityConformanceService::TRIGGER_GENERATION, $this->storedReports()[0]['trigger']);
		$this->assertSame([['812', [PdfUaVerdictTagger::CONFORM]]], $this->assigned);

	}//end testACompliantCopyReplacesTheOriginal()

	public function testAFailingCopyIsDiscarded(): void {
		$this->generated();
		putenv('FAKE_VERAPDF_REPORT=pdfua1-not-conformant.json');

		$result = $this->service()->regenerate(file: $this->file(), userId: 'petra');

		$this->assertFalse($result['replaced']);
		$this->assertFalse($result['report']['compliant'], 'The failed verdict is shown.');
		$this->assertSame(2, $result['report']['failedCheckCount']);
		$this->assertSame(self::ORIGINAL, $this->content, 'The original and its version are unchanged.');
		$this->assertSame([], $this->storedReports(), 'The copy\'s verdict is not stored as the file\'s.');
		$this->assertSame([], $this->assigned);

	}//end testAFailingCopyIsDiscarded()

	public function testAnImportedPdfIsRefused(): void {
		try {
			$this->service()->regenerate(file: $this->file(), userId: 'petra');
			$this->fail('A PDF Filinq did not generate must be refused.');
		} catch (AccessibleRegenerationRefused $e) {
			$this->assertSame(AccessibleRegenerationService::NOT_GENERATED, $e->getReason());
		}

		$this->assertSame([], $this->generations, 'Nothing is rendered.');
		$this->assertSame(self::ORIGINAL, $this->content);

	}//end testAnImportedPdfIsRefused()

	public function testOnlyASuccessfulPdfGenerationIsProvenance(): void {
		$this->generated(entry: ['status' => 'failed']);
		$this->generated(entry: ['format' => 'docx']);
		$this->generated(entry: ['fileId' => 813]);
		$this->generated(entry: ['templateId' => ' ']);

		$this->assertNull($this->service()->provenanceOf(fileId: 812));
		$this->assertNull($this->service()->provenanceOf(fileId: 0));
		$this->assertSame('tpl-besluit', $this->service()->provenanceOf(fileId: 813)['templateId']);

	}//end testOnlyASuccessfulPdfGenerationIsProvenance()

	public function testAnUnknownTemplateVersionRendersTheCurrentOne(): void {
		$this->generated(entry: ['templateVersion' => null]);
		putenv('FAKE_VERAPDF_REPORT=pdfua1-conformant.json');

		$this->service()->regenerate(file: $this->file(), userId: 'petra');

		$this->assertArrayNotHasKey('templateVersion', $this->generations[0]['options']);

	}//end testAnUnknownTemplateVersionRendersTheCurrentOne()

	public function testARenderThatIsNotAPdfChangesNothing(): void {
		$this->generated();

		try {
			$this->service(renders: '<html>')->regenerate(file: $this->file(), userId: 'petra');
			$this->fail('A render that is no PDF must not be stored.');
		} catch (RuntimeException $e) {
			$this->assertNotInstanceOf(AccessibleRegenerationRefused::class, $e);
		}

		$this->assertSame(self::ORIGINAL, $this->content);

	}//end testARenderThatIsNotAPdfChangesNothing()

	public function testNoVerdictOnTheCopyChangesNothing(): void {
		$this->generated();
		putenv('FAKE_VERAPDF_REPORT=pdfa3b-rendered.json');

		$this->expectException(VeraPdfException::class);
		try {
			$this->service()->regenerate(file: $this->file(), userId: 'petra');
		} finally {
			$this->assertSame(self::ORIGINAL, $this->content);
		}

	}//end testNoVerdictOnTheCopyChangesNothing()
}//end class

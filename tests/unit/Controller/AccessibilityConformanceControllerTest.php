<?php

/**
 * POST api/validation/accessibility/{fileId}, over the real check, the
 * recorded veraPDF stand-in and an in-memory register.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\AccessibilityConformanceController;
use OCA\Filinq\Kernel\Contracts\ConformanceServiceInterface;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService;
use OCA\Filinq\Service\VeraPdf\AccessibilityGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\ConformanceService;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCA\Filinq\Tests\Unit\Service\SubjectErasure\SubjectErasureDoubles;
use OCA\Filinq\Tests\Unit\Service\VeraPdf\VeraPdfDoubles;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The UA reports the stand-in answers with are hand-written in veraPDF's
 * JSON shape (tests/fixtures/verapdf/pdfua1-*.json), not recorded.
 *
 * @covers \OCA\Filinq\Controller\AccessibilityConformanceController
 * @covers \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService
 * @covers \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository
 */
class AccessibilityConformanceControllerTest extends TestCase {
	use SubjectErasureDoubles;
	use VeraPdfDoubles;

	/**
	 * The caller's own files: file id => [mime, bytes].
	 *
	 * @var array<int, array{0: string, 1: string}>
	 */
	private array $own = [
		42 => ['application/pdf', "%PDF-1.7\n<< /Creator (Word) >>\n<< /StructTreeRoot 4 0 R >>"],
		43 => ['text/plain', 'tekst'],
	];

	protected function tearDown(): void {
		putenv('FAKE_VERAPDF_REPORT');
		foreach ($this->tempFiles as $path) {
			@unlink($path);
		}

		parent::tearDown();

	}//end tearDown()

	/**
	 * The register OpenRegister would be, shared by both checks.
	 *
	 * @return DocumentObjectServiceResolver The resolver.
	 */
	private function register(): DocumentObjectServiceResolver {
		return new DocumentObjectServiceResolver($this->container(), $this->apps());

	}//end register()

	/**
	 * The PDF/UA check over the stand-in and the register.
	 *
	 * @return AccessibilityConformanceService The service.
	 */
	private function service(): AccessibilityConformanceService {
		return new AccessibilityConformanceService(
			$this->veraPdf(),
			new AccessibilityGuidance(new ConformanceGuidance()),
			new AccessibilityConformanceReportRepository($this->register()),
			$this->clock()
		);

	}//end service()

	/**
	 * Alice's file with this id, when she has one.
	 *
	 * @param int $id The file id.
	 *
	 * @return array<int, File> The file, or none.
	 */
	private function filesById(int $id): array {
		if (isset($this->own[$id]) === false) {
			return [];
		}

		[$mime, $bytes] = $this->own[$id];
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getMimetype')->willReturn($mime);
		$file->method('getContent')->willReturn($bytes);

		return [$file];

	}//end filesById()

	/**
	 * The controller for alice.
	 *
	 * @param AccessibilityConformanceService|null $service The service to use instead of the real one.
	 *
	 * @return AccessibilityConformanceController The controller.
	 */
	private function controller(?AccessibilityConformanceService $service = null): AccessibilityConformanceController {
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(fn (int $id): array => $this->filesById(id: $id));
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('alice')->willReturn($folder);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new AccessibilityConformanceController($this->createMock(IRequest::class), $service ?? $this->service(), $root, $session, $l10n, new NullLogger());

	}//end controller()

	/**
	 * The stored accessibility reports.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function storedReports(): array {
		return array_values($this->rows[AccessibilityConformanceReportRepository::SCHEMA] ?? []);

	}//end storedReports()

	public function testAFailingCheckIsStoredWithItsChecksAndGuidance(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfua1-not-conformant.json');

		$response = $this->controller()->check(fileId: 42);

		$this->assertSame(200, $response->getStatus());
		$report = $response->getData()['report'];
		$this->assertTrue($response->getData()['validated']);
		$this->assertSame(42, $report['fileId']);
		$this->assertSame('ua1', $report['flavour']);
		$this->assertFalse($report['compliant']);
		$this->assertSame(2, $report['failedCheckCount']);
		$this->assertSame(
			[
				['clause' => '7.1', 'testNumber' => 3, 'checkpoint' => 'ISO14289-1_7.1-3'],
				['clause' => '7.3', 'testNumber' => 1, 'checkpoint' => 'ISO14289-1_7.3-1'],
			],
			$report['failedChecks']
		);
		$this->assertSame(AccessibilityGuidance::REAUTHOR_FROM_SOURCE, $report['guidance']);
		$this->assertSame('veraPDF 1.30.2', $report['validatorVersion']);
		$this->assertSame(AccessibilityConformanceService::TRIGGER_MANUAL, $report['trigger']);
		$this->assertStringNotContainsString('StructTreeRoot', (string) json_encode($report), 'No content from the document.');
		$this->assertCount(1, $this->storedReports());

	}//end testAFailingCheckIsStoredWithItsChecksAndGuidance()

	public function testARerunUpdatesTheSameReport(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfua1-not-conformant.json');
		$this->controller()->check(fileId: 42);
		putenv('FAKE_VERAPDF_REPORT=pdfua1-conformant.json');

		$report = $this->controller()->check(fileId: 42)->getData()['report'];

		$this->assertTrue($report['compliant']);
		$this->assertSame(AccessibilityGuidance::NONE, $report['guidance']);
		$this->assertCount(1, $this->storedReports());
		$this->assertSame(array_diff_key($report, ['id' => true]), array_diff_key((array) $this->service()->reportFor(fileId: 42), ['id' => true]));

	}//end testARerunUpdatesTheSameReport()

	public function testThePdfAReportIsLeftAlone(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfa3b-rendered.json');
		$pdfa = new ConformanceService($this->veraPdf(), new ConformanceGuidance(), new ConformanceReportRepository($this->register()), $this->clock());
		$file = $this->filesById(id: 42)[0];
		$pdfaReport = $pdfa->checkFile(file: $file, trigger: ConformanceServiceInterface::TRIGGER_MANUAL);

		putenv('FAKE_VERAPDF_REPORT=pdfua1-not-conformant.json');
		$this->controller()->check(fileId: 42);

		$this->assertSame(['file'], array_keys($pdfa->reportsFor(fileId: 42)));
		$this->assertSame(array_diff_key($pdfaReport, ['id' => true]), array_diff_key($pdfa->reportsFor(fileId: 42)['file'], ['id' => true]));
		$this->assertTrue($pdfaReport['compliant']);
		$this->assertFalse($this->service()->reportFor(fileId: 42)['compliant']);

	}//end testThePdfAReportIsLeftAlone()

	public function testAFileTheCallerCannotOpenIs404AndNothingIsChecked(): void {
		foreach ([99, 0] as $id) {
			$response = $this->controller()->check(fileId: $id);
			$this->assertSame(404, $response->getStatus());
			$this->assertSame(['error' => 'File not found'], $response->getData());
		}

		$this->assertSame([], $this->storedReports());

	}//end testAFileTheCallerCannotOpenIs404AndNothingIsChecked()

	public function testWithoutACallerEveryFileIs404(): void {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);
		$root = $this->createMock(IRootFolder::class);
		$root->expects($this->never())->method('getUserFolder');
		$controller = new AccessibilityConformanceController($this->createMock(IRequest::class), $this->service(), $root, $session, $this->createMock(IL10N::class), new NullLogger());

		$this->assertSame(404, $controller->check(fileId: 42)->getStatus());

	}//end testWithoutACallerEveryFileIs404()

	public function testANonPdfIsRefused(): void {
		$response = $this->controller()->check(fileId: 43);

		$this->assertSame(400, $response->getStatus());
		$this->assertSame([], $this->storedReports());

	}//end testANonPdfIsRefused()

	public function testNoValidatorIsNotValidatedAndStoresNothing(): void {
		$this->config[VeraPdfService::CFG_BINARY_PATH] = '/nonexistent/verapdf';

		$response = $this->controller()->check(fileId: 42);

		$this->assertSame(503, $response->getStatus());
		$this->assertSame(
			[
				'validated' => false,
				'reason' => 'unavailable',
				'error' => 'The PDF/UA validator is not installed on the server, so this document was not checked for PDF/UA conformance.',
			],
			$response->getData()
		);
		$this->assertArrayNotHasKey('compliant', $response->getData());
		$this->assertFalse($this->service()->isAvailable());
		$this->assertSame([], $this->storedReports());

	}//end testNoValidatorIsNotValidatedAndStoresNothing()

	public function testAPdfAVerdictIsNeverStoredAsAPdfUaOne(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfa3b-rendered.json');

		$response = $this->controller()->check(fileId: 42);

		$this->assertSame(503, $response->getStatus());
		$this->assertSame('The PDF/UA validator could not read this document, so it was not checked for PDF/UA conformance.', $response->getData()['error']);
		$this->assertSame([], $this->storedReports());

	}//end testAPdfAVerdictIsNeverStoredAsAPdfUaOne()

	public function testAReportThatCannotBeStoredIs500(): void {
		$service = $this->createMock(AccessibilityConformanceService::class);
		$service->method('checkFile')->willThrowException(new RuntimeException('store down'));

		$response = $this->controller(service: $service)->check(fileId: 42);

		$this->assertSame(500, $response->getStatus());
		$this->assertSame('The check ran, but its report could not be stored. Try again later.', $response->getData()['error']);

	}//end testAReportThatCannotBeStoredIs500()
}//end class

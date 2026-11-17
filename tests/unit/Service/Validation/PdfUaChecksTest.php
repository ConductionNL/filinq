<?php

/**
 * Document validation's validator-backed PDF/UA checks, through the real
 * validation service, the real PDF/UA check and the veraPDF stand-in: off by
 * default, a finding with Matterhorn references beside the heuristic ones,
 * "not validated" when veraPDF cannot answer, and a blocking severity that
 * fails the verdict the intake gate reads.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Validation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/document-validation-checks/spec.md#requirement-the-check-catalogue-must-include-validator-backed-pdfua-accessibility-checks-req-ddpum-005
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Validation;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentValidationService;
use OCA\Filinq\Service\Validation\PdfUaChecks;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService;
use OCA\Filinq\Service\VeraPdf\AccessibilityGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCA\Filinq\Tests\Unit\Service\SubjectErasure\SubjectErasureDoubles;
use OCA\Filinq\Tests\Unit\Service\VeraPdf\VeraPdfDoubles;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Validation\PdfUaChecks
 * @covers \OCA\Filinq\Service\DocumentValidationService
 * @uses   \OCA\Filinq\Service\Validation\AccessibilityChecks
 * @uses   \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService
 */
class PdfUaChecksTest extends TestCase {
	use SubjectErasureDoubles;
	use VeraPdfDoubles;

	/**
	 * Passes every heuristic: tagged, marked, language, title, PDF/UA identifier.
	 */
	private const LOOKS_TAGGED = "%PDF-1.7\n<< /Type /Catalog /StructTreeRoot 5 0 R /MarkInfo << /Marked true >> /Lang (nl-NL) >>\n"
		. "<< /Title (Besluit) >>\n<pdfuaid:part>1</pdfuaid:part>";

	protected function tearDown(): void {
		putenv('FAKE_VERAPDF_REPORT');
		foreach ($this->tempFiles as $path) {
			@unlink($path);
		}

		parent::tearDown();

	}//end tearDown()

	/**
	 * The real PDF/UA check over the stand-in and an in-memory register.
	 *
	 * @return AccessibilityConformanceService The check.
	 */
	private function pdfUa(): AccessibilityConformanceService {
		return new AccessibilityConformanceService(
			$this->veraPdf(),
			new AccessibilityGuidance(new ConformanceGuidance()),
			new AccessibilityConformanceReportRepository(new DocumentObjectServiceResolver($this->container(), $this->apps())),
			$this->clock()
		);

	}//end pdfUa()

	/**
	 * The real validation service with the real PDF/UA check.
	 *
	 * @param AccessibilityConformanceService|null $pdfUa The check to use instead of the real one.
	 *
	 * @return DocumentValidationService The service.
	 */
	private function validation(?AccessibilityConformanceService $pdfUa = null): DocumentValidationService {
		return new DocumentValidationService(new NullLogger(), $this->appConfig(), null, $pdfUa ?? $this->pdfUa());

	}//end validation()

	/**
	 * Set the default profile's severities.
	 *
	 * @param array<string, string> $severities Check id => severity.
	 *
	 * @return void
	 */
	private function profile(array $severities): void {
		$this->config['validation.profiles'] = (string) json_encode(['default' => ['severities' => $severities]]);

	}//end profile()

	/**
	 * An uploaded file.
	 *
	 * @param string $bytes The content.
	 * @param string $mime  The MIME type.
	 *
	 * @return File The file.
	 */
	private function upload(string $bytes = self::LOOKS_TAGGED, string $mime = 'application/pdf'): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(42);
		$file->method('getName')->willReturn('besluit.pdf');
		$file->method('getMimeType')->willReturn($mime);
		$file->method('getContent')->willReturn($bytes);

		return $file;

	}//end upload()

	/**
	 * The accessibility findings of a result, keyed by check id.
	 *
	 * @param array<string, mixed> $result The validation result.
	 *
	 * @return array<string, array<string, mixed>> The findings.
	 */
	private function accessibility(array $result): array {
		$findings = array_filter($result['validationFindings'], static fn (array $f): bool => $f['category'] === 'accessibility');

		return array_column($findings, null, 'checkId');

	}//end accessibility()

	/**
	 * The stored PDF/UA reports.
	 *
	 * @return array<int, array<string, mixed>> The reports.
	 */
	private function storedReports(): array {
		return array_values($this->rows[AccessibilityConformanceReportRepository::SCHEMA] ?? []);

	}//end storedReports()

	public function testShippedDefaultsLeaveTheValidatorChecksOff(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfua1-not-conformant.json');

		$result = $this->validation()->validate(file: $this->upload(bytes: "%PDF-1.7\n<< /Type /Catalog >>"));

		$ids = array_keys($this->accessibility(result: $result));
		$this->assertSame(['pdf-not-tagged', 'pdf-language-missing', 'pdf-title-missing'], $ids, 'Only the heuristic findings.');
		$this->assertSame([], $this->storedReports(), 'veraPDF is not run.');

	}//end testShippedDefaultsLeaveTheValidatorChecksOff()

	public function testANonConformantPdfFiresTheFindingBesideTheHeuristics(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfua1-not-conformant.json');
		$this->profile(severities: [DocumentValidationService::CHECK_PDFUA_CONFORMANCE => 'warning']);

		$findings = $this->accessibility(result: $this->validation()->validate(file: $this->upload(bytes: "%PDF-1.7\n<< /Type /Catalog >>")));

		$this->assertSame(['pdf-not-tagged', 'pdf-language-missing', 'pdf-title-missing', 'pdfua-conformance-failed'], array_keys($findings));
		$finding = $findings['pdfua-conformance-failed'];
		$this->assertSame('warning', $finding['severity']);
		$this->assertSame(PdfUaChecks::SOURCE_VALIDATOR, $finding['source']);
		$this->assertSame(['flavour' => '1', 'failedCheckCount' => 2, 'checks' => 'ISO14289-1_7.1-3, ISO14289-1_7.3-1'], $finding['params']);
		$this->assertSame(AccessibilityGuidance::REAUTHOR_FROM_SOURCE, $finding['guidance']);
		$this->assertSame('heuristic', $findings['pdf-not-tagged']['source']);
		$this->assertSame(AccessibilityConformanceService::TRIGGER_VALIDATION, $this->storedReports()[0]['trigger']);

	}//end testANonConformantPdfFiresTheFindingBesideTheHeuristics()

	public function testAHeuristicPassWithAValidatorFailIsAFail(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfua1-not-conformant.json');
		$this->profile(severities: [DocumentValidationService::CHECK_PDFUA_CONFORMANCE => 'blocking']);

		$result = $this->validation()->validate(file: $this->upload());

		$this->assertSame(['pdfua-conformance-failed'], array_keys($this->accessibility(result: $result)), 'The heuristics pass.');
		$this->assertSame('failed', $result['validationStatus'], 'The blocking verdict the intake gate answers 422 on.');

	}//end testAHeuristicPassWithAValidatorFailIsAFail()

	public function testAConformantPdfGivesNoValidatorFinding(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfua1-conformant.json');
		$this->profile(severities: [DocumentValidationService::CHECK_PDFUA_CONFORMANCE => 'blocking']);

		$result = $this->validation()->validate(file: $this->upload());

		$this->assertSame([], $this->accessibility(result: $result));
		$this->assertNotSame('failed', $result['validationStatus'], 'A conformant PDF does not fail the blocking check.');
		$this->assertTrue($this->storedReports()[0]['compliant']);

	}//end testAConformantPdfGivesNoValidatorFinding()

	public function testAMissingValidatorIsAnExplicitWarning(): void {
		$this->profile(severities: [DocumentValidationService::CHECK_PDFUA_CONFORMANCE => 'blocking']);
		$this->config[VeraPdfService::CFG_BINARY_PATH] = '/nonexistent/verapdf';

		$result = $this->validation()->validate(file: $this->upload());
		$findings = $this->accessibility(result: $result);

		$this->assertSame(['accessibility-validator-unavailable'], array_keys($findings));
		$this->assertSame('warning', $findings['accessibility-validator-unavailable']['severity'], 'Not validated is never escalated.');
		$this->assertSame([], $findings['accessibility-validator-unavailable']['params']);
		$this->assertSame('warnings', $result['validationStatus']);
		$this->assertSame([], $this->storedReports(), 'No verdict is made up.');

	}//end testAMissingValidatorIsAnExplicitWarning()

	public function testWithoutTheCheckWiredInItIsNotValidated(): void {
		$this->profile(severities: [DocumentValidationService::CHECK_PDFUA_CONFORMANCE => 'warning']);
		$service = new DocumentValidationService(new NullLogger(), $this->appConfig());

		$findings = $this->accessibility(result: $service->validate(file: $this->upload()));

		$this->assertSame(['accessibility-validator-unavailable'], array_keys($findings));

	}//end testWithoutTheCheckWiredInItIsNotValidated()

	public function testAValidatorThatGivesNoVerdictIsNotValidated(): void {
		$this->profile(severities: [DocumentValidationService::CHECK_PDFUA_CONFORMANCE => 'warning']);
		$check = $this->createMock(AccessibilityConformanceService::class);
		$check->method('isAvailable')->willReturn(true);
		$check->expects($this->once())->method('checkFile')->willThrowException(new RuntimeException('store down'));

		$findings = $this->accessibility(result: $this->validation(pdfUa: $check)->validate(file: $this->upload()));

		$this->assertSame(['accessibility-validator-unavailable'], array_keys($findings));

	}//end testAValidatorThatGivesNoVerdictIsNotValidated()

	public function testANonPdfIsNotSentToTheValidator(): void {
		$this->profile(severities: [DocumentValidationService::CHECK_PDFUA_CONFORMANCE => 'warning']);
		$check = $this->createMock(AccessibilityConformanceService::class);
		$check->expects($this->never())->method('checkFile');

		$result = $this->validation(pdfUa: $check)->validate(file: $this->upload(bytes: 'Een notitie.', mime: 'text/plain'));

		$this->assertSame([], $this->accessibility(result: $result));

	}//end testANonPdfIsNotSentToTheValidator()
}//end class

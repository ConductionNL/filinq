<?php

/**
 * Document validation's archival checks, through the real validation
 * service and the recorded veraPDF: off by default, a finding per failure
 * with references only, "not checked" when veraPDF cannot answer, and a
 * blocking severity that fails the verdict the intake gate reads.
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
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentValidationService;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\ConformanceService;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCA\Filinq\Tests\Unit\Service\SubjectErasure\SubjectErasureDoubles;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ArchivalChecksTest extends TestCase {
	use SubjectErasureDoubles;
	use VeraPdfDoubles;

	/**
	 * The real validation service with the real conformance check.
	 *
	 * @return DocumentValidationService The service.
	 */
	private function validation(): DocumentValidationService {
		$conformance = new ConformanceService(
			$this->veraPdf(),
			new ConformanceGuidance(),
			new ConformanceReportRepository(new DocumentObjectServiceResolver($this->container(), $this->apps())),
			$this->clock()
		);

		return new DocumentValidationService(new NullLogger(), $this->appConfig(), $conformance);

	}//end validation()

	/**
	 * Set the default profile's archival severities.
	 *
	 * @param array<string, string> $severities Check id => severity.
	 *
	 * @return void
	 */
	private function archival(array $severities): void {
		$this->config['validation.profiles'] = (string) json_encode(['default' => ['severities' => $severities]]);

	}//end archival()

	/**
	 * A fixture as an uploaded PDF.
	 *
	 * @param string $fixture The fixture.
	 *
	 * @return File The file.
	 */
	private function upload(string $fixture): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(7);
		$file->method('getName')->willReturn($fixture . '.pdf');
		$file->method('getMimeType')->willReturn('application/pdf');
		$file->method('getContent')->willReturn($this->pdf(name: $fixture));

		return $file;

	}//end upload()

	/**
	 * The archival findings of a result.
	 *
	 * @param array<string, mixed> $result The validation result.
	 *
	 * @return array<int, array<string, mixed>> The findings.
	 */
	private function archivalFindings(array $result): array {
		return array_values(array_filter($result['validationFindings'], static fn (array $f): bool => $f['category'] === 'archival'));

	}//end archivalFindings()

	/**
	 * Shipped defaults: no archival finding of any kind, and veraPDF is not
	 * run, even on a PDF that fails it.
	 *
	 * @return void
	 */
	public function testShippedDefaultsLeaveArchivalChecksOff(): void {
		$result = $this->validation()->validate(file: $this->upload(fixture: 'pdfa3b-claimed-not-conformant'));

		$this->assertSame([], $this->archivalFindings(result: $result));
		$this->assertSame([], $this->rows[ConformanceReportRepository::SCHEMA] ?? []);
		foreach ($result['validationFindings'] as $finding) {
			$this->assertNotSame('archival', $finding['category']);
		}

	}//end testShippedDefaultsLeaveArchivalChecksOff()

	/**
	 * Switched on: a non-conformant PDF gets the conformance finding with
	 * its level and rule references, and the report is kept.
	 *
	 * @return void
	 */
	public function testANonConformantPdfFiresTheArchivalFinding(): void {
		$this->archival(severities: [DocumentValidationService::CHECK_PDFA_CONFORMANCE => 'warning']);

		$findings = $this->archivalFindings(result: $this->validation()->validate(file: $this->upload(fixture: 'pdfa3b-claimed-not-conformant')));

		$this->assertCount(1, $findings);
		$this->assertSame('pdfa-conformance-failed', $findings[0]['checkId']);
		$this->assertSame('warning', $findings[0]['severity']);
		$this->assertSame(['flavour' => '3b', 'failedRuleCount' => 2, 'rules' => 'ISO19005-3_6.2.4.3-2, ISO19005-3_6.1.3-1'], $findings[0]['params']);
		$this->assertSame(ConformanceGuidance::RULE_REFERENCES, $findings[0]['guidance']);
		$this->assertSame('validation', array_values($this->rows[ConformanceReportRepository::SCHEMA])[0]['trigger']);

	}//end testANonConformantPdfFiresTheArchivalFinding()

	/**
	 * The font check names the fonts, beside the conformance finding.
	 *
	 * @return void
	 */
	public function testTheFontCheckNamesTheFonts(): void {
		$this->archival(severities: [DocumentValidationService::CHECK_PDFA_CONFORMANCE => 'warning', DocumentValidationService::CHECK_PDFA_FONTS => 'warning']);

		$findings = array_column($this->archivalFindings(result: $this->validation()->validate(file: $this->upload(fixture: 'pdfa3b-imported-unembedded-font'))), null, 'checkId');

		$this->assertSame(['pdfa-conformance-failed', 'pdfa-font-not-embedded'], array_keys($findings));
		$this->assertSame(['fonts' => 'Helvetica'], $findings['pdfa-font-not-embedded']['params']);
		$this->assertSame(ConformanceGuidance::RECONVERT_FROM_SOURCE, $findings['pdfa-font-not-embedded']['guidance']);

	}//end testTheFontCheckNamesTheFonts()

	/**
	 * Switched on without a validator: an explicit "not checked" warning,
	 * and no conformance or font finding made up.
	 *
	 * @return void
	 */
	public function testAMissingValidatorIsAnExplicitFinding(): void {
		$this->archival(severities: [DocumentValidationService::CHECK_PDFA_CONFORMANCE => 'blocking', DocumentValidationService::CHECK_PDFA_FONTS => 'blocking']);
		$this->config[VeraPdfService::CFG_BINARY_PATH] = '/nonexistent/verapdf';

		$result = $this->validation()->validate(file: $this->upload(fixture: 'pdfa3b-claimed-not-conformant'));
		$findings = $this->archivalFindings(result: $result);

		$this->assertCount(1, $findings);
		$this->assertSame('archival-validator-unavailable', $findings[0]['checkId']);
		$this->assertSame('warning', $findings[0]['severity'], 'Not checked is never escalated to blocking.');
		$this->assertNotSame('failed', $result['validationStatus']);

	}//end testAMissingValidatorIsAnExplicitFinding()

	/**
	 * Escalated to blocking, a non-conformant PDF fails the verdict the
	 * intake gate answers 422 on; a conformant one passes it.
	 *
	 * @return void
	 */
	public function testABlockingConformanceCheckFailsTheVerdict(): void {
		$this->archival(severities: [DocumentValidationService::CHECK_PDFA_CONFORMANCE => 'blocking']);

		$this->assertSame('failed', $this->validation()->validate(file: $this->upload(fixture: 'pdfa3b-claimed-not-conformant'))['validationStatus']);
		$this->assertSame([], $this->archivalFindings(result: $this->validation()->validate(file: $this->upload(fixture: 'pdfa3b-rendered'))));

	}//end testABlockingConformanceCheckFailsTheVerdict()

	/**
	 * Archival checks look at PDFs only.
	 *
	 * @return void
	 */
	public function testANonPdfIsNotSentToTheValidator(): void {
		$this->archival(severities: [DocumentValidationService::CHECK_PDFA_CONFORMANCE => 'warning']);
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn('notitie.txt');
		$file->method('getMimeType')->willReturn('text/plain');
		$file->method('getContent')->willReturn('Een notitie.');

		$this->assertSame([], $this->archivalFindings(result: $this->validation()->validate(file: $file)));

	}//end testANonPdfIsNotSentToTheValidator()
}//end class

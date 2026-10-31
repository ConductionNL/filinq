<?php

/**
 * A veraPDF verdict becomes one conformanceReport per file: the register
 * takes it, a second check updates it, the advice fits the failure, and a
 * validator that cannot answer stores nothing.
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

use OCA\Filinq\Exception\VeraPdfException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\ConformanceService;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCA\Filinq\Tests\Unit\Service\SubjectErasure\SubjectErasureDoubles;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;

class ConformanceServiceTest extends TestCase {
	use SubjectErasureDoubles;
	use VeraPdfDoubles;

	/**
	 * The real service over the in-memory register and the recorded veraPDF.
	 *
	 * @return ConformanceService The service.
	 */
	private function conformance(): ConformanceService {
		return new ConformanceService(
			$this->veraPdf(),
			new ConformanceGuidance(),
			new ConformanceReportRepository(new DocumentObjectServiceResolver($this->container(), $this->apps())),
			$this->clock()
		);

	}//end conformance()

	/**
	 * A stored PDF with the fixture's bytes.
	 *
	 * @param int    $fileId  The file id.
	 * @param string $fixture The fixture.
	 *
	 * @return File The file.
	 */
	private function stored(int $fileId, string $fixture): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($fileId);
		$file->method('getContent')->willReturnCallback(fn (): string => $this->pdf(name: $fixture));

		return $file;

	}//end stored()

	/**
	 * The stored conformance reports.
	 *
	 * @return array<string, array<string, mixed>> The rows by uuid.
	 */
	private function reports(): array {
		return ($this->rows[ConformanceReportRepository::SCHEMA] ?? []);

	}//end reports()

	/**
	 * An uploaded PDF failing on a font: the report names the font, advises
	 * converting again from the source, and the register takes it.
	 *
	 * @return void
	 */
	public function testAnImportedFontFailureIsStoredWithTheHonestAdvice(): void {
		$report = $this->conformance()->checkFile(file: $this->stored(fileId: 42, fixture: 'pdfa3b-imported-unembedded-font'), trigger: ConformanceService::TRIGGER_MANUAL);

		$this->assertSame(['Helvetica'], $report['fontsNotEmbedded']);
		$this->assertSame(ConformanceGuidance::RECONVERT_FROM_SOURCE, $report['guidance']);
		$this->assertSame('file', $report['subject']);
		$this->assertSame('manual', $report['trigger']);
		$this->assertSame(hash('sha256', $this->pdf(name: 'pdfa3b-imported-unembedded-font')), $report['checksumSha256']);
		$this->assertCount(1, $this->reports());
		$this->assertValidAgainstSchema(schema: 'conformanceReport', payload: array_values($this->reports())[0]);

	}//end testAnImportedFontFailureIsStoredWithTheHonestAdvice()

	/**
	 * Checking again updates the same report; nothing piles up.
	 *
	 * @return void
	 */
	public function testCheckingAgainUpdatesTheSameReport(): void {
		$service = $this->conformance();
		$first = $service->checkFile(file: $this->stored(fileId: 42, fixture: 'pdfa3b-imported-unembedded-font'), trigger: ConformanceService::TRIGGER_MANUAL);

		$second = $service->checkFile(file: $this->stored(fileId: 42, fixture: 'pdfa3b-rendered'), trigger: ConformanceService::TRIGGER_VALIDATION);

		$this->assertSame($first['uuid'], $second['uuid']);
		$this->assertCount(1, $this->reports());
		$this->assertTrue(array_values($this->reports())[0]['compliant']);
		$this->assertSame(['file' => $second['uuid']], array_map(static fn (array $r): string => $r['uuid'], $service->reportsFor(fileId: 42)));

	}//end testCheckingAgainUpdatesTheSameReport()

	/**
	 * A conversion's verdict is kept beside the file's own, not over it.
	 *
	 * @return void
	 */
	public function testAConversionVerdictHasItsOwnRow(): void {
		$service = $this->conformance();
		$service->checkFile(file: $this->stored(fileId: 42, fixture: 'pdfa3b-claimed-not-conformant'), trigger: ConformanceService::TRIGGER_MANUAL);

		$conversion = $service->checkConversion(bytes: $this->pdf(name: 'pdfa3b-imported-unembedded-font'), origin: ConformanceGuidance::ORIGIN_IMPORTED, sourceFileId: 42);

		$this->assertTrue($conversion['stored']);
		$reports = $service->reportsFor(fileId: 42);
		$this->assertSame(['file', 'conversionOutput'], array_keys($reports));
		$this->assertSame(ConformanceGuidance::RULE_REFERENCES, $reports['file']['guidance']);
		$this->assertSame('conversion', $reports['conversionOutput']['trigger']);
		$this->assertSame(ConformanceGuidance::RECONVERT_FROM_SOURCE, $reports['conversionOutput']['guidance']);
		$this->assertValidAgainstSchema(schema: 'conformanceReport', payload: $reports['conversionOutput']);

	}//end testAConversionVerdictHasItsOwnRow()

	/**
	 * Rendered output with no source file is checked but not stored.
	 *
	 * @return void
	 */
	public function testRenderedOutputWithoutASourceIsNotStored(): void {
		$report = $this->conformance()->checkConversion(bytes: $this->pdf(name: 'pdfa3b-rendered'), origin: ConformanceGuidance::ORIGIN_RENDERED, sourceFileId: null);

		$this->assertTrue($report['compliant']);
		$this->assertFalse($report['stored']);
		$this->assertSame([], $this->reports());

	}//end testRenderedOutputWithoutASourceIsNotStored()

	/**
	 * No validator, no report: nothing is stored as compliant or otherwise.
	 *
	 * @return void
	 */
	public function testNoValidatorStoresNothing(): void {
		$this->config[VeraPdfService::CFG_BINARY_PATH] = '/nonexistent/verapdf';

		try {
			$this->conformance()->checkFile(file: $this->stored(fileId: 42, fixture: 'pdfa3b-rendered'), trigger: ConformanceService::TRIGGER_MANUAL);
			$this->fail('A report came back without a validator.');
		} catch (VeraPdfException $e) {
			$this->assertSame(VeraPdfException::REASON_UNAVAILABLE, $e->getReason());
		}

		$this->assertSame([], $this->reports());

	}//end testNoValidatorStoresNothing()

	/**
	 * The advice follows the failure and where the pages came from.
	 *
	 * @return void
	 */
	public function testTheAdviceFitsTheFailure(): void {
		$guidance = new ConformanceGuidance();
		$veraPdf = $this->veraPdf();
		$fontFailure = $veraPdf->validateBytes(bytes: $this->pdf(name: 'pdfa3b-imported-unembedded-font'));
		$ruleFailure = $veraPdf->validateBytes(bytes: $this->pdf(name: 'pdfa3b-claimed-not-conformant'));
		$pass = $veraPdf->validateBytes(bytes: $this->pdf(name: 'pdfa3b-rendered'));

		$this->assertSame(ConformanceGuidance::REGENERATE, $guidance->for(verdict: $fontFailure, origin: ConformanceGuidance::ORIGIN_RENDERED));
		$this->assertSame(ConformanceGuidance::RECONVERT_FROM_SOURCE, $guidance->for(verdict: $fontFailure, origin: ConformanceGuidance::ORIGIN_IMPORTED));
		$this->assertSame(ConformanceGuidance::RULE_REFERENCES, $guidance->for(verdict: $ruleFailure, origin: ConformanceGuidance::ORIGIN_RENDERED));
		$this->assertSame(ConformanceGuidance::NONE, $guidance->for(verdict: $pass, origin: ConformanceGuidance::ORIGIN_IMPORTED));

		// A PDF Filinq wrote reads as rendered, unless its failing font sits on an imported page.
		$this->assertSame(ConformanceGuidance::ORIGIN_RENDERED, $guidance->originOf(bytes: $this->pdf(name: 'pdfa3b-rendered'), verdict: $pass));
		$this->assertSame(ConformanceGuidance::ORIGIN_IMPORTED, $guidance->originOf(bytes: $this->pdf(name: 'pdfa3b-imported-unembedded-font'), verdict: $fontFailure));
		$this->assertSame(ConformanceGuidance::ORIGIN_IMPORTED, $guidance->originOf(bytes: $this->pdf(name: 'pdfa3b-claimed-not-conformant'), verdict: $ruleFailure));

	}//end testTheAdviceFitsTheFailure()
}//end class

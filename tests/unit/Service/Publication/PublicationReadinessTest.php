<?php

/**
 * Publication readiness counts a PDF/UA verdict that did not pass: a reason
 * naming the file, a warning by default, a stopped hand-off at blocking, and
 * a missing report counted as not passed. Over the real PDF/UA readiness
 * check, the real report store on an in-memory register, and the real
 * validation profile resolution.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-publication-readiness-counts-a-pdfua-verdict-that-did-not-pass-req-ddpum-007
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Publication;

use DateTimeImmutable;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentValidationService;
use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\Publication\PdfUaReadinessCheck;
use OCA\Filinq\Service\Publication\PublicationReadiness;
use OCA\Filinq\Service\Publication\PublicationStore;
use OCA\Filinq\Service\Redaction\RedactionReviewMarkRepository;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService;
use OCA\Filinq\Service\VeraPdf\AccessibilityGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use OCA\Filinq\Tests\Unit\Service\SubjectErasure\SubjectErasureDoubles;
use OCA\Filinq\Tests\Unit\Service\VeraPdf\VeraPdfDoubles;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Publication\PublicationReadiness
 * @covers \OCA\Filinq\Service\Publication\PdfUaReadinessCheck
 * @uses   \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService
 * @uses   \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository
 * @uses   \OCA\Filinq\Service\DocumentValidationService
 */
class PublicationReadinessTest extends TestCase {
	use SubjectErasureDoubles;
	use VeraPdfDoubles;

	/**
	 * The document (a PDF) and its redacted copy (a PDF).
	 */
	private const DOCUMENT = '701';

	private const COPY = '702';

	/**
	 * MIME types of the files the root folder knows.
	 *
	 * @var array<int, string>
	 */
	private array $mimes = [701 => 'application/pdf', 702 => 'application/pdf'];

	protected function tearDown(): void {
		putenv('FAKE_VERAPDF_REPORT');
		foreach ($this->tempFiles as $path) {
			@unlink($path);
		}

		parent::tearDown();

	}//end tearDown()

	/**
	 * The real PDF/UA report store and check.
	 *
	 * @return AccessibilityConformanceService The check.
	 */
	private function conformance(): AccessibilityConformanceService {
		return new AccessibilityConformanceService(
			$this->veraPdf(),
			new AccessibilityGuidance(new ConformanceGuidance()),
			new AccessibilityConformanceReportRepository(new DocumentObjectServiceResolver($this->container(), $this->apps())),
			$this->clock()
		);

	}//end conformance()

	/**
	 * Store a PDF/UA report for a file through the real check.
	 *
	 * @param int    $fileId The file.
	 * @param string $report The stand-in's report fixture.
	 *
	 * @return void
	 */
	private function checked(int $fileId, string $report): void {
		putenv('FAKE_VERAPDF_REPORT=' . $report);
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($fileId);
		$file->method('getContent')->willReturn('%PDF-1.7 ' . $fileId);
		$this->conformance()->checkFile(file: $file, trigger: AccessibilityConformanceService::TRIGGER_ATTACH);

	}//end checked()

	/**
	 * A readiness whose consent, prohibition and review checks all pass, with the PDF/UA check.
	 *
	 * @param AccessibilityConformanceService|null $conformance The report store to use instead of the real one.
	 *
	 * @return PublicationReadiness The readiness.
	 */
	private function readiness(?AccessibilityConformanceService $conformance = null, ?array $withheld = null): PublicationReadiness {
		$consents = $this->createMock(ConsentService::class);
		$consents->method('isDocumentConsentClear')->willReturn(['clear' => true, 'reasons' => []]);
		$consents->method('getConsentsByDocument')->willReturn([]);
		$consentConfig = $this->createMock(ConsentCrudService::class);
		$consentConfig->method('getConsentConfig')->willReturn(['register' => 'filinq', 'schema' => 'consent']);
		$marks = $this->createMock(RedactionReviewMarkRepository::class);
		$marks->method('findFor')->willReturn(['by' => 'petra']);
		$store = $this->createMock(PublicationStore::class);
		$store->method('findRedactedCopy')->willReturn(self::COPY);
		$store->method('findWithheldLink')->willReturn($withheld);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturnCallback(
			function (int $id): ?File {
				if (isset($this->mimes[$id]) === false) {
					return null;
				}

				$file = $this->createMock(File::class);
				$file->method('getMimetype')->willReturn($this->mimes[$id]);

				return $file;
			}
		);
		$check = new PdfUaReadinessCheck(
			$conformance ?? $this->conformance(),
			$root,
			new DocumentValidationService(new NullLogger(), $this->appConfig())
		);

		return new PublicationReadiness($consents, $consentConfig, $this->createMock(PolicyMatchService::class), $marks, $store, null, $check);

	}//end readiness()

	/**
	 * Evaluate a Woo record for the document.
	 *
	 * @param PublicationReadiness|null $readiness The readiness to use.
	 *
	 * @return array<string, mixed> The evaluated record.
	 */
	private function evaluate(?PublicationReadiness $readiness = null): array {
		return ($readiness ?? $this->readiness())->evaluate(record: ['documentFileRef' => self::DOCUMENT], now: new DateTimeImmutable('2026-11-14T12:00:00+00:00'));

	}//end evaluate()

	/**
	 * Set the default profile's severity of the PDF/UA conformance check.
	 *
	 * @param string $severity The severity.
	 *
	 * @return void
	 */
	private function severity(string $severity): void {
		$this->config['validation.profiles'] = (string) json_encode(['default' => ['severities' => [DocumentValidationService::CHECK_PDFUA_CONFORMANCE => $severity]]]);

	}//end severity()

	/**
	 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
	 */
	public function testAWithheldDocumentIsNotForPublication(): void {
		$this->checked(fileId: 701, report: 'pdfua1-conformant.json');
		$this->checked(fileId: 702, report: 'pdfua1-conformant.json');
		$readiness = $this->readiness(withheld: ['sourceFileId' => 701, 'withheld' => true, 'withheldBases' => ['5.1.1.c']]);

		$record = $readiness->evaluate(record: ['documentFileRef' => self::DOCUMENT], now: new DateTimeImmutable('2026-11-14T12:00:00+00:00'));

		$this->assertTrue($record['withheld']);
		$this->assertSame(['Document 701 is withheld in full on 5.1.1.c, so it is not for publication'], $record['readinessReasons']);
		$this->assertTrue($record['entitiesReviewed'] && $record['consentClear'] && $record['prohibitionsClear'], 'Every other check passes.');
		$this->assertFalse($readiness->isReady(record: $record));

	}//end testAWithheldDocumentIsNotForPublication()

	public function testADocumentNotWithheldIsMarkedSo(): void {
		$this->checked(fileId: 701, report: 'pdfua1-conformant.json');
		$this->checked(fileId: 702, report: 'pdfua1-conformant.json');

		$record = $this->evaluate();

		$this->assertArrayNotHasKey('withheld', $record, 'A record that is not withheld keeps its shape.');
		$this->assertTrue($this->readiness()->isReady(record: $record));

	}//end testADocumentNotWithheldIsMarkedSo()

	public function testAFailedPdfUaVerdictIsAReason(): void {
		$this->checked(fileId: 701, report: 'pdfua1-conformant.json');
		$this->checked(fileId: 702, report: 'pdfua1-not-conformant.json');

		$record = $this->evaluate();

		$this->assertSame(['PDF 702 does not conform to PDF/UA'], $record['readinessReasons']);
		$this->assertSame(PdfUaReadinessCheck::GATE_WARNING, $record['pdfUaGate']);
		$this->assertTrue($this->readiness()->isReady(record: $record), 'At the default severity it only warns.');

	}//end testAFailedPdfUaVerdictIsAReason()

	public function testABlockingSeverityStopsTheHandOff(): void {
		$this->severity(severity: 'blocking');
		$this->checked(fileId: 701, report: 'pdfua1-not-conformant.json');
		$this->checked(fileId: 702, report: 'pdfua1-not-conformant.json');

		$record = $this->evaluate();

		$this->assertSame(['PDF 702 does not conform to PDF/UA', 'PDF 701 does not conform to PDF/UA'], $record['readinessReasons']);
		$this->assertSame(PdfUaReadinessCheck::GATE_BLOCKING, $record['pdfUaGate']);
		$this->assertFalse($this->readiness()->isReady(record: $record));

	}//end testABlockingSeverityStopsTheHandOff()

	public function testAMissingReportCountsAsNotPassed(): void {
		$this->severity(severity: 'blocking');
		$this->checked(fileId: 702, report: 'pdfua1-conformant.json');

		$record = $this->evaluate();

		$this->assertSame(['PDF 701 has not been checked against PDF/UA'], $record['readinessReasons']);
		$this->assertFalse($this->readiness()->isReady(record: $record));

	}//end testAMissingReportCountsAsNotPassed()

	public function testCompliantPdfsAreClear(): void {
		$this->severity(severity: 'blocking');
		$this->checked(fileId: 701, report: 'pdfua1-conformant.json');
		$this->checked(fileId: 702, report: 'pdfua1-conformant.json');

		$record = $this->evaluate();

		$this->assertSame([], $record['readinessReasons']);
		$this->assertSame(PdfUaReadinessCheck::GATE_CLEAR, $record['pdfUaGate']);
		$this->assertTrue($this->readiness()->isReady(record: $record));

	}//end testCompliantPdfsAreClear()

	public function testAFileThatIsNotAPdfIsNotAsked(): void {
		$this->mimes = [701 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 702 => 'application/pdf'];
		$this->checked(fileId: 702, report: 'pdfua1-conformant.json');

		$this->assertSame([], $this->evaluate()['readinessReasons']);

	}//end testAFileThatIsNotAPdfIsNotAsked()

	public function testAReportThatCannotBeReadCountsAsNotPassed(): void {
		$conformance = $this->createMock(AccessibilityConformanceService::class);
		$conformance->method('reportFor')->willThrowException(new RuntimeException('register down'));

		$record = $this->evaluate(readiness: $this->readiness(conformance: $conformance));

		$this->assertSame(['PDF 702 has not been checked against PDF/UA', 'PDF 701 has not been checked against PDF/UA'], $record['readinessReasons']);

	}//end testAReportThatCannotBeReadCountsAsNotPassed()

	public function testWithoutTheCheckTheRecordIsNotHeldOnPdfUa(): void {
		$record = ['entitiesReviewed' => true, 'consentClear' => true, 'prohibitionsClear' => true];

		$this->assertTrue(PdfUaReadinessCheck::lets(gate: PdfUaReadinessCheck::GATE_WARNING));
		$this->assertFalse(PdfUaReadinessCheck::lets(gate: PdfUaReadinessCheck::GATE_BLOCKING));
		$this->assertTrue($this->readiness()->isReady(record: $record), 'A record evaluated before the check existed keeps no gate.');

	}//end testWithoutTheCheckTheRecordIsNotHeldOnPdfUa()
}//end class

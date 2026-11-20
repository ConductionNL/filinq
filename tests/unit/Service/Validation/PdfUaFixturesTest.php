<?php

/**
 * The two PDF/UA fixtures that show the quick checks are not a conformance
 * verdict: a PDF that passes every heuristic yet fails PDF/UA-1 (Matterhorn),
 * and one that veraPDF finds conformant. Both run through the real validation
 * service, the real PDF/UA check and the veraPDF stand-in, which answers them
 * with the reports a real veraPDF 1.28.2 printed for the same bytes.
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
 * @spec openspec/changes/pdfua-verapdf-matterhorn/tasks.md#task-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Validation;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentValidationService;
use OCA\Filinq\Service\Validation\PdfUaChecks;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceState;
use OCA\Filinq\Service\VeraPdf\AccessibilityGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use OCA\Filinq\Tests\Unit\Service\SubjectErasure\SubjectErasureDoubles;
use OCA\Filinq\Tests\Unit\Service\VeraPdf\VeraPdfDoubles;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Fixtures under tests/sample-documents/pdfua:
 * - heuristics-pass-matterhorn-fail.pdf: hand-built; marked, a structure tree,
 *   a language, a title and the PDF/UA identifier, but one paragraph drawn
 *   outside any tag, a Figure without alternative text and a font that is not
 *   embedded.
 * - pdfua1-conformant.pdf: LibreOffice Writer's PDF/UA export of a short
 *   fictional decision (embedded DejaVu Sans, nl-NL, a title).
 *
 * @covers \OCA\Filinq\Service\Validation\PdfUaChecks
 * @covers \OCA\Filinq\Service\DocumentValidationService
 * @covers \OCA\Filinq\Service\VeraPdf\VeraPdfService
 * @covers \OCA\Filinq\Service\VeraPdf\VeraPdfReportParser
 * @covers \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService
 * @covers \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceState
 * @uses   \OCA\Filinq\Service\Validation\AccessibilityChecks
 * @uses   \OCA\Filinq\Service\VeraPdf\VeraPdfProcess
 * @uses   \OCA\Filinq\Service\VeraPdf\AccessibilityGuidance
 * @uses   \OCA\Filinq\Service\VeraPdf\ConformanceGuidance
 * @uses   \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class PdfUaFixturesTest extends TestCase {
	use SubjectErasureDoubles;
	use VeraPdfDoubles;

	private const FIXTURES = __DIR__ . '/../../../sample-documents/pdfua/';

	private const MATTERHORN_FAIL = 'heuristics-pass-matterhorn-fail.pdf';

	private const CONFORMANT = 'pdfua1-conformant.pdf';

	protected function tearDown(): void {
		foreach ($this->tempFiles as $path) {
			@unlink($path);
		}

		parent::tearDown();

	}//end tearDown()

	/**
	 * A fixture's bytes.
	 *
	 * @param string $fixture The fixture file name.
	 *
	 * @return string The bytes.
	 */
	private function bytes(string $fixture): string {
		return (string) file_get_contents(self::FIXTURES . $fixture);

	}//end bytes()

	/**
	 * A fixture as an uploaded file.
	 *
	 * @param string $fixture The fixture file name.
	 *
	 * @return File The file.
	 */
	private function upload(string $fixture): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(42);
		$file->method('getName')->willReturn($fixture);
		$file->method('getMimeType')->willReturn('application/pdf');
		$file->method('getContent')->willReturn($this->bytes(fixture: $fixture));

		return $file;

	}//end upload()

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
	 * Validate a fixture, with the validator-backed check at the given severity
	 * or, with null, as shipped (off).
	 *
	 * @param string      $fixture  The fixture file name.
	 * @param string|null $severity The severity of pdfua-conformance-failed.
	 *
	 * @return array<string, array<string, mixed>> The accessibility findings, keyed by check id.
	 */
	private function accessibility(string $fixture, ?string $severity = null): array {
		if ($severity !== null) {
			$this->config['validation.profiles'] = (string) json_encode(['default' => ['severities' => [DocumentValidationService::CHECK_PDFUA_CONFORMANCE => $severity]]]);
		}

		$service = new DocumentValidationService(new NullLogger(), $this->appConfig(), null, $this->pdfUa());
		$result = $service->validate(file: $this->upload(fixture: $fixture));
		$findings = array_filter($result['validationFindings'], static fn (array $f): bool => $f['category'] === 'accessibility');

		return array_column($findings, null, 'checkId');

	}//end accessibility()

	public function testTheMatterhornFailFixturePassesEveryQuickCheck(): void {
		$this->assertSame([], $this->accessibility(fixture: self::MATTERHORN_FAIL));

	}//end testTheMatterhornFailFixturePassesEveryQuickCheck()

	public function testTheConformantFixturePassesEveryQuickCheck(): void {
		$this->assertSame([], $this->accessibility(fixture: self::CONFORMANT));

	}//end testTheConformantFixturePassesEveryQuickCheck()

	public function testVeraPdfFindsTheMatterhornFailFixtureNotConformant(): void {
		$verdict = $this->veraPdf()->validateUaBytes(bytes: $this->bytes(fixture: self::MATTERHORN_FAIL));

		$this->assertSame('ua1', $verdict['flavour']);
		$this->assertFalse($verdict['compliant']);
		$this->assertSame(3, $verdict['failedRuleCount']);
		$this->assertSame(
			['ISO14289-1_7.21.4.1-1', 'ISO14289-1_7.3-1', 'ISO14289-1_7.1-3'],
			array_column($verdict['failedRules'], 'ruleId'),
			'A font that is not embedded, a Figure without alternative text, content outside the tags.'
		);

	}//end testVeraPdfFindsTheMatterhornFailFixtureNotConformant()

	public function testVeraPdfFindsTheConformantFixtureConformant(): void {
		$verdict = $this->veraPdf()->validateUaBytes(bytes: $this->bytes(fixture: self::CONFORMANT));

		$this->assertSame('ua1', $verdict['flavour']);
		$this->assertTrue($verdict['compliant']);
		$this->assertSame(0, $verdict['failedRuleCount']);
		$this->assertSame([], $verdict['failedRules']);

	}//end testVeraPdfFindsTheConformantFixtureConformant()

	public function testPassingTheQuickChecksIsNoConformanceVerdict(): void {
		$findings = $this->accessibility(fixture: self::MATTERHORN_FAIL, severity: 'warning');

		$this->assertSame(['pdfua-conformance-failed'], array_keys($findings), 'Only the validator speaks.');
		$this->assertSame(PdfUaChecks::SOURCE_VALIDATOR, $findings['pdfua-conformance-failed']['source']);
		$this->assertSame(3, $findings['pdfua-conformance-failed']['params']['failedCheckCount']);

	}//end testPassingTheQuickChecksIsNoConformanceVerdict()

	public function testTheConformantFixtureGivesNoValidatorFinding(): void {
		$this->assertSame([], $this->accessibility(fixture: self::CONFORMANT, severity: 'blocking'));

	}//end testTheConformantFixtureGivesNoValidatorFinding()

	public function testTheValidatorVerdictOverridesTheQuickChecksPass(): void {
		$report = $this->pdfUa()->checkBytes(bytes: $this->bytes(fixture: self::MATTERHORN_FAIL), fileId: 42, trigger: AccessibilityConformanceService::TRIGGER_MANUAL);

		$state = (new AccessibilityConformanceState())->resolve(report: $report, heuristicFindings: [], validatorAvailable: true);

		$this->assertSame(AccessibilityConformanceState::SOURCE_VALIDATOR, $state['source']);
		$this->assertSame(AccessibilityConformanceState::STATE_NON_CONFORMANT, $state['state'], 'Not "looks tagged".');
		$this->assertSame(
			[['clause' => '7.21.4.1', 'testNumber' => 1], ['clause' => '7.3', 'testNumber' => 1], ['clause' => '7.1', 'testNumber' => 3]],
			array_map(static fn (array $c): array => ['clause' => $c['clause'], 'testNumber' => $c['testNumber']], $report['failedChecks'])
		);

	}//end testTheValidatorVerdictOverridesTheQuickChecksPass()
}//end class

<?php

/**
 * veraPDF as a probed local binary: the verdicts it gives on real PDFs,
 * and that nothing it fails to give is ever read as compliant.
 *
 * The binary is tests/fixtures/verapdf/fake-verapdf, which replays what
 * veraPDF 1.30.2 printed for the same fixture bytes, so the parser reads
 * real output. Set VERAPDF_BINARY to run the last test against a real one.
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
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;

class VeraPdfServiceTest extends TestCase {
	use VeraPdfDoubles;

	private string $argvLog = '';

	protected function setUp(): void {
		parent::setUp();
		$this->argvLog = (string) tempnam(sys_get_temp_dir(), 'filinq-verapdf-argv-');
		putenv('FAKE_VERAPDF_ARGV=' . $this->argvLog);

	}//end setUp()

	protected function tearDown(): void {
		putenv('FAKE_VERAPDF_ARGV');
		putenv('FAKE_VERAPDF_SLEEP');
		@unlink($this->argvLog);
		parent::tearDown();

	}//end tearDown()

	/**
	 * The argument lists the binary was started with.
	 *
	 * @return array<int, array<int, string>> One list per run.
	 */
	private function runs(): array {
		return array_map(static fn (string $line): array => json_decode($line, true), array_filter(explode("\n", (string) file_get_contents($this->argvLog))));

	}//end runs()

	/**
	 * The error a validation raises.
	 *
	 * @param callable $action The validation.
	 *
	 * @return VeraPdfException The error.
	 */
	private function failure(callable $action): VeraPdfException {
		try {
			$action();
		} catch (VeraPdfException $e) {
			return $e;
		}

		$this->fail('A verdict came back where none should.');

	}//end failure()

	/**
	 * Installed: the status names the probed version.
	 *
	 * @return void
	 */
	public function testTheStatusNamesTheProbedVersion(): void {
		$status = $this->veraPdf()->status();

		$this->assertSame(['enabled' => true, 'available' => true, 'version' => 'veraPDF 1.30.2', 'binaryPath' => $this->fakeBinary()], $status);

	}//end testTheStatusNamesTheProbedVersion()

	/**
	 * Absent binary: unavailable, and a validation says so instead of
	 * answering.
	 *
	 * @return void
	 */
	public function testAnAbsentBinaryIsUnavailableAndGivesNoVerdict(): void {
		$this->config[VeraPdfService::CFG_BINARY_PATH] = '/nonexistent/verapdf';
		$service = $this->veraPdf();

		$this->assertFalse($service->status()['available']);
		$this->assertSame(VeraPdfException::REASON_UNAVAILABLE, $this->failure(fn () => $service->validateBytes(bytes: $this->pdf(name: 'pdfa3b-rendered')))->getReason());

	}//end testAnAbsentBinaryIsUnavailableAndGivesNoVerdict()

	/**
	 * Switched off: the binary is not even started.
	 *
	 * @return void
	 */
	public function testSwitchedOffStartsNothing(): void {
		$this->config[VeraPdfService::CFG_ENABLED] = 'false';
		$service = $this->veraPdf();

		$this->assertSame(['enabled' => false, 'available' => false, 'version' => ''], array_slice($service->status(), 0, 3));
		$this->assertSame(VeraPdfException::REASON_UNAVAILABLE, $this->failure(fn () => $service->validateBytes(bytes: $this->pdf(name: 'pdfa3b-rendered')))->getReason());
		$this->assertSame([], $this->runs());

	}//end testSwitchedOffStartsNothing()

	/**
	 * A PDF that claims PDF/A-3B but breaks colour and trailer rules fails,
	 * with the rules by clause and nothing from the document.
	 *
	 * @return void
	 */
	public function testAMarkerClaimingFileFailsRealValidation(): void {
		$verdict = $this->veraPdf()->validateBytes(bytes: $this->pdf(name: 'pdfa3b-claimed-not-conformant'));

		$this->assertFalse($verdict['compliant']);
		$this->assertSame('3b', $verdict['flavour']);
		$this->assertSame(2, $verdict['failedRuleCount']);
		$this->assertSame(['ISO19005-3_6.2.4.3-2', 'ISO19005-3_6.1.3-1'], array_column($verdict['failedRules'], 'ruleId'));
		$this->assertSame(['ruleId', 'specification', 'clause', 'testNumber', 'checksFailed'], array_keys($verdict['failedRules'][0]));
		$this->assertSame([], $verdict['fontsNotEmbedded']);
		$this->assertFalse($verdict['onlyFontRulesFailed']);
		$this->assertSame('veraPDF 1.30.2', $verdict['validatorVersion']);
		$this->assertStringNotContainsString('DeviceRGB', (string) json_encode($verdict), 'Rule descriptions stay behind.');

	}//end testAMarkerClaimingFileFailsRealValidation()

	/**
	 * An uploaded PDF wrapped by convertExistingPdf fails on the one font it
	 * never embedded, and the font sits on an imported page.
	 *
	 * @return void
	 */
	public function testAWrappedImportFailsOnItsFontByName(): void {
		$verdict = $this->veraPdf()->validateBytes(bytes: $this->pdf(name: 'pdfa3b-imported-unembedded-font'));

		$this->assertFalse($verdict['compliant']);
		$this->assertSame(['Helvetica'], $verdict['fontsNotEmbedded']);
		$this->assertTrue($verdict['fontsInImportedPages']);
		$this->assertTrue($verdict['onlyFontRulesFailed']);
		$this->assertSame('6.2.11.4.1', $verdict['failedRules'][0]['clause']);

	}//end testAWrappedImportFailsOnItsFontByName()

	/**
	 * What Filinq renders itself passes.
	 *
	 * @return void
	 */
	public function testARenderedPdfA3bPasses(): void {
		$verdict = $this->veraPdf()->validateBytes(bytes: $this->pdf(name: 'pdfa3b-rendered'));

		$this->assertTrue($verdict['compliant']);
		$this->assertSame(0, $verdict['failedRuleCount']);
		$this->assertGreaterThanOrEqual(0, $verdict['elapsedMs']);

	}//end testARenderedPdfA3bPasses()

	/**
	 * Without a requested level the document's own claim is validated, 3b
	 * when it claims none; a requested level is passed on only when it is
	 * one; the temporary copy is removed.
	 *
	 * @return void
	 */
	public function testTheClaimIsValidatedUnlessALevelIsRequested(): void {
		$service = $this->veraPdf();
		$service->validateBytes(bytes: $this->pdf(name: 'pdfa3b-rendered'));
		$service->validateBytes(bytes: $this->pdf(name: 'pdfa3b-rendered'), flavour: '2b');
		$service->validateBytes(bytes: $this->pdf(name: 'pdfa3b-rendered'), flavour: '2b; rm -rf /');

		$runs = array_values(array_filter($this->runs(), static fn (array $run): bool => $run !== ['--version']));
		$this->assertSame(['--flavour', '0', '--defaultflavour', '3b'], array_slice($runs[0], 4, 4));
		$this->assertSame(['--flavour', '2b'], array_slice($runs[1], 4, 2));
		$this->assertSame(['--flavour', '0', '--defaultflavour', '3b'], array_slice($runs[2], 4, 4));
		foreach ($this->tempFiles as $path) {
			$this->assertFileDoesNotExist($path);
		}

	}//end testTheClaimIsValidatedUnlessALevelIsRequested()

	/**
	 * A run past the budget is stopped and is an error, not a pass.
	 *
	 * @return void
	 */
	public function testARunPastTheBudgetIsAnErrorNotAPass(): void {
		$this->config[VeraPdfService::CFG_MAX_SECONDS] = '1';
		$service = $this->veraPdf();
		$this->assertTrue($service->isAvailable());
		putenv('FAKE_VERAPDF_SLEEP=5');

		$started = microtime(true);
		$error = $this->failure(fn () => $service->validateBytes(bytes: $this->pdf(name: 'pdfa3b-rendered')));

		$this->assertSame(VeraPdfException::REASON_TIMEOUT, $error->getReason());
		$this->assertLessThan(3.0, (microtime(true) - $started));

	}//end testARunPastTheBudgetIsAnErrorNotAPass()

	/**
	 * A document veraPDF cannot parse is an error, not a verdict.
	 *
	 * @return void
	 */
	public function testAnUnreadableDocumentIsAnError(): void {
		$error = $this->failure(fn () => $this->veraPdf()->validateBytes(bytes: '%PDF-1.7 truncated'));

		$this->assertSame(VeraPdfException::REASON_FAILED, $error->getReason());
		$this->assertStringContainsString("Couldn't parse stream", $error->getMessage());
		$this->assertStringContainsString('exit code 7', $error->getMessage());

	}//end testAnUnreadableDocumentIsAnError()

	/**
	 * A stored file is read and validated.
	 *
	 * @return void
	 */
	public function testAStoredFileIsValidated(): void {
		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn($this->pdf(name: 'pdfa3b-imported-unembedded-font'));

		$this->assertSame(['Helvetica'], $this->veraPdf()->validate(file: $file)['fontsNotEmbedded']);

		$broken = $this->createMock(File::class);
		$broken->method('getContent')->willThrowException(new \RuntimeException('storage gone'));
		$this->assertSame(VeraPdfException::REASON_FAILED, $this->failure(fn () => $this->veraPdf()->validate(file: $broken))->getReason());

	}//end testAStoredFileIsValidated()

	/**
	 * Against a real veraPDF, when VERAPDF_BINARY names one: the three
	 * fixtures give the verdicts the recordings hold.
	 *
	 * @return void
	 */
	public function testARealVeraPdfAgreesWithTheRecordings(): void {
		$binary = getenv('VERAPDF_BINARY');
		if ($binary === false || $binary === '') {
			$this->markTestSkipped('VERAPDF_BINARY is not set.');
		}

		$this->config[VeraPdfService::CFG_BINARY_PATH] = $binary;
		$this->config[VeraPdfService::CFG_MAX_SECONDS] = '120';
		$service = $this->veraPdf();

		$this->assertTrue($service->validateBytes(bytes: $this->pdf(name: 'pdfa3b-rendered'))['compliant']);
		$this->assertSame(['Helvetica'], $service->validateBytes(bytes: $this->pdf(name: 'pdfa3b-imported-unembedded-font'))['fontsNotEmbedded']);
		$this->assertSame(2, $service->validateBytes(bytes: $this->pdf(name: 'pdfa3b-claimed-not-conformant'))['failedRuleCount']);

	}//end testARealVeraPdfAgreesWithTheRecordings()
}//end class

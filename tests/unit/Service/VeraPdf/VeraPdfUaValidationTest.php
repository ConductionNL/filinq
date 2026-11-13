<?php

/**
 * PDF/UA-1 validation through the shared veraPDF service, over the recorded veraPDF stand-in.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use OCA\Filinq\Exception\VeraPdfException;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;

/**
 * The UA reports under tests/fixtures/verapdf/pdfua1-*.json are hand-written
 * in veraPDF 1.30's JSON shape (profile "PDF/UA-1 validation profile", rules
 * of ISO 14289-1), not recorded from a real run.
 *
 * @covers \OCA\Filinq\Service\VeraPdf\VeraPdfService
 * @covers \OCA\Filinq\Service\VeraPdf\VeraPdfReportParser
 * @uses   \OCA\Filinq\Service\VeraPdf\VeraPdfProcess
 * @uses   \OCA\Filinq\Exception\VeraPdfException
 */
class VeraPdfUaValidationTest extends TestCase {
	use VeraPdfDoubles;

	private string $argvLog = '';

	protected function setUp(): void {
		parent::setUp();
		$this->argvLog = (string) tempnam(sys_get_temp_dir(), 'filinq-verapdf-argv-');
		putenv('FAKE_VERAPDF_ARGV=' . $this->argvLog);

	}//end setUp()

	protected function tearDown(): void {
		putenv('FAKE_VERAPDF_ARGV');
		putenv('FAKE_VERAPDF_REPORT');
		@unlink($this->argvLog);
		foreach ($this->tempFiles as $path) {
			@unlink($path);
		}

		parent::tearDown();

	}//end tearDown()

	/**
	 * The arguments of the last validation run.
	 *
	 * @return array<int, string>
	 */
	private function lastRun(): array {
		$lines = array_values(array_filter(explode("\n", (string) file_get_contents($this->argvLog))));

		return (array) json_decode((string) end($lines), true);

	}//end lastRun()

	public function testAFailingDocumentIsUa1NonCompliantWithItsClauses(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfua1-not-conformant.json');

		$verdict = $this->veraPdf()->validateUaBytes(bytes: '%PDF-1.7 not recorded');

		$this->assertSame('ua1', $verdict['flavour']);
		$this->assertFalse($verdict['compliant']);
		$this->assertSame(2, $verdict['failedRuleCount']);
		$this->assertSame(['ISO14289-1_7.1-3', 'ISO14289-1_7.3-1'], array_column($verdict['failedRules'], 'ruleId'));
		$this->assertSame(['7.1', '7.3'], array_column($verdict['failedRules'], 'clause'));
		$this->assertSame('veraPDF 1.30.2', $verdict['validatorVersion']);
		$this->assertStringNotContainsString('Figure tags', (string) json_encode($verdict), 'Rule descriptions stay behind.');

		$argv = $this->lastRun();
		$this->assertSame(['--flavour', 'ua1'], array_slice($argv, -3, 2), 'The same binary, asked for the ua1 profile.');
		$this->assertSame(['--format', 'json'], array_slice($argv, 0, 2));

	}//end testAFailingDocumentIsUa1NonCompliantWithItsClauses()

	public function testAStoredFileIsValidatedAgainstUa1(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfua1-conformant.json');
		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn('%PDF-1.7 tagged');

		$verdict = $this->veraPdf()->validateUa(file: $file);

		$this->assertSame('ua1', $verdict['flavour']);
		$this->assertTrue($verdict['compliant']);
		$this->assertSame([], $verdict['failedRules']);

	}//end testAStoredFileIsValidatedAgainstUa1()

	public function testAReportForAnotherProfileIsNeverAUaVerdict(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfa3b-rendered.json');

		try {
			$this->veraPdf()->validateUaBytes(bytes: '%PDF-1.7 not recorded');
			$this->fail('A PDF/A verdict must not pass for a PDF/UA one.');
		} catch (VeraPdfException $e) {
			$this->assertSame(VeraPdfException::REASON_FAILED, $e->getReason());
		}

	}//end testAReportForAnotherProfileIsNeverAUaVerdict()

	public function testWithoutTheValidatorThereIsNoVerdict(): void {
		$this->config[VeraPdfService::CFG_BINARY_PATH] = '/nonexistent/verapdf';

		try {
			$this->veraPdf()->validateUaBytes(bytes: '%PDF-1.7');
			$this->fail('No validator must give no verdict.');
		} catch (VeraPdfException $e) {
			$this->assertSame(VeraPdfException::REASON_UNAVAILABLE, $e->getReason());
		}

	}//end testWithoutTheValidatorThereIsNoVerdict()

	public function testAnUnreadableFileIsAnError(): void {
		$file = $this->createMock(File::class);
		$file->method('getContent')->willThrowException(new \RuntimeException('locked'));

		$this->expectException(VeraPdfException::class);
		$this->veraPdf()->validateUa(file: $file);

	}//end testAnUnreadableFileIsAnError()
}//end class

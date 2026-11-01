<?php

/**
 * The exact command lines OcrService and TesseractCommand hand to the shell.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\OcrService;
use OCA\Filinq\Service\TesseractCommand;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * exec() is replaced in the OCA\Filinq\Service namespace, so every case runs in
 * its own process: the replacement cannot be taken back once defined.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @covers \OCA\Filinq\Service\TesseractCommand
 * @covers \OCA\Filinq\Service\OcrService
 */
class ShellArgReviewTest extends TestCase {

	/**
	 * Load the exec() stand-in, answering one hOCR word of confidence 80.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/Fixtures/tesseract-exec-stub.php';
		$GLOBALS['tesseractExecStub'] = ['commands' => [], 'output' => ["<span title='bbox 1 2 3 4; x_wconf 80'>w</span>"], 'returnCode' => 0];
	}//end setUp()

	/**
	 * Hostile file names and languages.
	 *
	 * @return array<string, array{0: string, 1: string, 2: int}>
	 */
	public static function hostile(): array {
		return [
			'spaces' => ['/tmp/my scan.png', 'nld+eng', 300],
			'single quote' => ["/tmp/o'brien.png", 'nld', 300],
			'double quote' => ['/tmp/a"b.png', 'nld', 300],
			'semicolon' => ['/tmp/x.png; rm -rf ~', 'nld', 300],
			'subshell' => ['/tmp/$(id).png', 'nld', 300],
			'backticks' => ['/tmp/`id`.png', 'nld', 300],
			'leading dash' => ['-c', 'nld', 300],
			'leading dash option' => ['--user-words=/etc/passwd', 'nld', 300],
			'newline' => ["/tmp/a\nid.png", 'nld', 300],
			'hostile languages' => ['/tmp/x.png', "nld'; id; '", 72],
		];
	}//end hostile()

	/**
	 * The confidence probe quotes the file and the languages, byte for byte.
	 *
	 * @param string $path The image path.
	 * @param string $lang The languages.
	 * @param int $dpi The DPI.
	 *
	 * @dataProvider hostile
	 *
	 * @return void
	 */
	public function testExactCommandLine(string $path, string $lang, int $dpi): void {
		$conf = (new TesseractCommand(logger: new NullLogger()))->confidence(filePath: $path, languages: $lang, dpi: $dpi);

		$expected = 'tesseract ' . escapeshellarg($path) . ' stdout -l ' . escapeshellarg($lang) . ' --dpi ' . $dpi . ' -c hocr_font_info=0 hocr 2>/dev/null';
		$this->assertSame([$expected], $GLOBALS['tesseractExecStub']['commands']);
		$this->assertSame(80.0, $conf);
		// Literal pins, independent of escapeshellarg itself.
		$literal = [
			'/tmp/$(id).png' => "tesseract '/tmp/\$(id).png' stdout -l 'nld' --dpi 300 -c hocr_font_info=0 hocr 2>/dev/null",
			"/tmp/o'brien.png" => "tesseract '/tmp/o'\\''brien.png' stdout -l 'nld' --dpi 300 -c hocr_font_info=0 hocr 2>/dev/null",
			'-c' => "tesseract '-c' stdout -l 'nld' --dpi 300 -c hocr_font_info=0 hocr 2>/dev/null",
		];
		if (isset($literal[$path]) === true) {
			$this->assertSame($literal[$path], $GLOBALS['tesseractExecStub']['commands'][0]);
		}
	}//end testExactCommandLine()

	/**
	 * A failing probe reports no confidence, even when it printed word confidences.
	 *
	 * @return void
	 */
	public function testAFailingProbeHasNoConfidence(): void {
		$GLOBALS['tesseractExecStub']['returnCode'] = 1;

		$conf = (new TesseractCommand(logger: new NullLogger()))->confidence(filePath: '/tmp/x.png', languages: 'nld', dpi: 300);

		$this->assertSame(0.0, $conf);
		$this->assertCount(1, $GLOBALS['tesseractExecStub']['commands']);
	}//end testAFailingProbeHasNoConfidence()

	/**
	 * The availability and version checks ask `tesseract --version`.
	 *
	 * @return void
	 */
	public function testVersionCommands(): void {
		$GLOBALS['tesseractExecStub']['output'] = ['tesseract 5.3.0', ' leptonica-1.82.0'];
		$service = new OcrService(new NullLogger(), $this->createMock(IAppConfig::class), $this->createMock(IRootFolder::class), $this->createMock(IUserSession::class));

		$this->assertTrue($service->isTesseractAvailable());
		$this->assertSame('tesseract 5.3.0', $service->getTesseractVersion());
		$this->assertSame(['tesseract --version 2>&1', 'tesseract --version 2>&1'], $GLOBALS['tesseractExecStub']['commands']);
	}//end testVersionCommands()

	/**
	 * Recognising an image probes its confidence with the configured languages and DPI.
	 *
	 * The recognition itself runs through the TesseractOCR library, which finds a
	 * stand-in `tesseract` script on PATH.
	 *
	 * @return void
	 */
	public function testTheConfiguredLanguagesReachTheConfidenceProbe(): void {
		$bin = sys_get_temp_dir() . '/filinq-fake-tesseract-' . uniqid();
		mkdir($bin);
		file_put_contents($bin . '/tesseract', "#!/bin/sh\ncase \"\$1\" in --version) echo 'tesseract 5.3.0'; exit 0;; --list-langs) printf 'List\\ndeu\\nfra\\n'; exit 0;; esac\nprintf 'Hallo wereld' > \"\$2.txt\"\n");
		chmod($bin . '/tesseract', 0700);
		putenv('PATH=' . $bin . ':' . getenv('PATH'));

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => match ($key) {
				'ocr.default_languages' => 'deu+fra',
				'ocr.default_dpi' => '150',
				default => $default,
			}
		);
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn('scan.png');
		$file->method('getMimeType')->willReturn('image/png');
		$file->method('getContent')->willReturn('png bytes');

		$service = new OcrService(new NullLogger(), $config, $this->createMock(IRootFolder::class), $this->createMock(IUserSession::class));
		try {
			$result = $service->processNode(file: $file);
		} finally {
			unlink($bin . '/tesseract');
			rmdir($bin);
		}

		$commands = $GLOBALS['tesseractExecStub']['commands'];
		$this->assertSame('tesseract --version 2>&1', $commands[0]);
		$this->assertCount(2, $commands);
		$this->assertMatchesRegularExpression("#^tesseract '[^']*/filinq_ocr_[^']*\\.png' stdout -l 'deu\\+fra' --dpi 150 -c hocr_font_info=0 hocr 2>/dev/null$#", $commands[1]);
		$this->assertSame(['text' => 'Hallo wereld', 'confidence' => 80.0, 'languages' => 'deu+fra', 'dpi' => 150], $result);

	}//end testTheConfiguredLanguagesReachTheConfidenceProbe()
}//end class

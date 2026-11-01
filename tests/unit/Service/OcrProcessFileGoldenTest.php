<?php

/**
 * Golden test for OcrService::processFile: when it runs OCR on the acting user's file, and what it answers.
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

use Exception;
use OCA\Filinq\Service\OcrService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Every setting, session and file through processFile(), compared with JSON
 * recorded from the implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\OcrService
 */
class OcrProcessFileGoldenTest extends TestCase {

	/**
	 * The scenario: OCR setting, tesseract present, session user, the file (mime, or 'folder'/'none'), and what OCR reads ('fails' or text).
	 *
	 * @return array<string, array{0: string, 1: bool, 2: string|null, 3: string, 4: string}>
	 */
	public static function scenarios(): array {
		return [
			'an image with text' => ['1', true, 'anna', 'image/png', 'Besluit'],
			'an image without text' => ['1', true, 'anna', 'image/png', ''],
			'a pdf' => ['1', true, 'anna', 'application/pdf', 'Tekst'],
			'a text file' => ['1', true, 'anna', 'text/plain', 'Tekst'],
			'OCR read fails' => ['1', true, 'anna', 'image/jpeg', 'fails'],
			'no such file' => ['1', true, 'anna', 'none', 'Tekst'],
			'a folder' => ['1', true, 'anna', 'folder', 'Tekst'],
			'nobody logged in' => ['1', true, null, 'image/png', 'Tekst'],
			'another user' => ['1', true, 'bert', 'image/png', 'Tekst'],
			'OCR switched off' => ['0', true, 'anna', 'image/png', 'Tekst'],
			'no tesseract' => ['1', false, 'anna', 'image/png', 'Tekst'],
		];
	}//end scenarios()

	/**
	 * The answer, the folders asked for, the OCR runs and the log match the recorded ones.
	 *
	 * @param string $enabled The ocr_enabled setting.
	 * @param bool $tesseract Whether tesseract is installed.
	 * @param string|null $user The session user.
	 * @param string $file The file's mime type, or 'folder' / 'none'.
	 * @param string $read What OCR reads, or 'fails'.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testProcessFile(string $enabled, bool $tesseract, ?string $user, string $file, string $read): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line.
			 *
			 * @param mixed $level The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed> $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($key === 'ocr_enabled' ? $enabled : $default)
		);

		$session = $this->createMock(IUserSession::class);
		$sessionUser = null;
		if ($user !== null) {
			$sessionUser = $this->createMock(IUser::class);
			$sessionUser->method('getUID')->willReturn($user);
		}

		$session->method('getUser')->willReturn($sessionUser);

		// Only anna's folder holds file 7, so a lookup for anyone else finds nothing.
		$folders = [];
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturnCallback(function (string $uid) use (&$folders, $file): Folder {
			$folders[] = $uid;
			$folder = $this->createMock(Folder::class);
			$nodes = [];
			if ($uid === 'anna' && $file === 'folder') {
				$nodes = [$this->createMock(Folder::class)];
			} elseif ($uid === 'anna' && $file !== 'none') {
				$node = $this->createMock(File::class);
				$node->method('getMimeType')->willReturn($file);
				$nodes = [$node];
			}

			$folder->method('getById')->willReturnCallback(static fn (int $id): array => ($id === 7 ? $nodes : []));
			return $folder;
		});

		$runs = 0;
		$service = $this->getMockBuilder(OcrService::class)
			->setConstructorArgs([$logger, $config, $root, $session])
			->onlyMethods(['isTesseractAvailable', 'processNode'])
			->getMock();
		$service->method('isTesseractAvailable')->willReturn($tesseract);
		$service->method('processNode')->willReturnCallback(static function (File $node) use (&$runs, $read): array {
			$runs++;
			if ($read === 'fails') {
				throw new Exception('tesseract crashed');
			}

			return ['text' => $read, 'confidence' => 87.5];
		});

		$trace = ['answer' => $service->processFile(7), 'folders' => $folders, 'runs' => $runs, 'logs' => $logger->lines];

		$path = __DIR__ . '/golden/ocr-process-file.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, json_decode((string)json_encode($trace, JSON_PRESERVE_ZERO_FRACTION), true));
	}//end testProcessFile()
}//end class

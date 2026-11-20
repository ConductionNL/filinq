<?php

/**
 * Tests for OfficeImageFiller (office-charts-and-images REQ-DDTCH-008).
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Charts\TemplateImageResolver;
use OCA\Filinq\Service\Office\OfficeImageFiller;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\ITempManager;
use OCP\IUser;
use OCP\IUserSession;
use PhpOffice\PhpWord\TemplateProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * An `${image:key}` placeholder becomes the generating user's image, and a
 * file the user cannot read becomes the marker text plus a warning.
 *
 * @covers \OCA\Filinq\Service\Office\OfficeImageFiller
 * @uses   \OCA\Filinq\Service\Charts\TemplateImageResolver
 */
class OfficeTemplateImageTest extends TestCase {

	private const FIXTURE = __DIR__ . '/../../sample-documents/chart-template.docx';

	/**
	 * A 1x1 transparent PNG.
	 */
	private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

	private IRootFolder&MockObject $rootFolder;
	private Folder&MockObject $userFolder;
	private ITempManager&MockObject $tempManager;

	/**
	 * Temp files written by a test.
	 *
	 * @var string[]
	 */
	private array $tempFiles = [];

	protected function setUp(): void {
		$this->rootFolder  = $this->createMock(IRootFolder::class);
		$this->userFolder  = $this->createMock(Folder::class);
		$this->tempManager = $this->createMock(ITempManager::class);
		$this->tempManager->method('getTemporaryFile')->willReturnCallback(function (): string {
			$path = (string)tempnam(sys_get_temp_dir(), 'filinq-img');
			$this->tempFiles[] = $path;
			return $path;
		});
		$this->rootFolder->method('getUserFolder')->with('alice')->willReturn($this->userFolder);
	}

	protected function tearDown(): void {
		foreach ($this->tempFiles as $file) {
			@unlink($file);
		}

		$this->tempFiles = [];
	}

	/**
	 * A PNG the user can read lands in word/media at the placeholder, at the declared size.
	 *
	 * @return void
	 */
	public function testReadablePngLandsInMedia(): void {
		$this->userFolder->expects($this->once())->method('getFirstNodeById')->with(42)
			->willReturn($this->fileWith(bytes: (string)base64_decode(self::PNG)));

		$processor = new TemplateProcessor(self::FIXTURE);
		$warnings  = $this->filler()->fill(
			processor: $processor,
			images: ['logo' => ['fileId' => 42, 'width' => 80, 'height' => 80]]
		);

		$this->assertSame([], $warnings);
		$this->assertNotContains('image:logo', $processor->getVariables());

		$parts = $this->savedParts(processor: $processor);
		$media = array_filter($parts, static fn (string $name): bool => str_starts_with($name, 'word/media/'), ARRAY_FILTER_USE_KEY);
		$this->assertCount(1, $media);
		$this->assertSame(base64_decode(self::PNG), array_values($media)[0]);
		$this->assertStringEndsWith('.png', array_keys($media)[0]);
		$this->assertStringContainsString('<v:imagedata r:id="rId', $parts['word/document.xml']);
		$this->assertStringContainsString('width:80px;height:80px', $parts['word/document.xml']);
		$this->assertStringNotContainsString('filinqImage', $parts['word/document.xml']);
	}

	/**
	 * A bare file id is accepted as the context entry.
	 *
	 * @return void
	 */
	public function testABareFileIdIsAccepted(): void {
		$this->userFolder->method('getFirstNodeById')->with(42)
			->willReturn($this->fileWith(bytes: (string)base64_decode(self::PNG)));

		$processor = new TemplateProcessor(self::FIXTURE);
		$this->assertSame([], $this->filler()->fill(processor: $processor, images: ['logo' => '42']));
		$this->assertNotContains('image:logo', $processor->getVariables());
	}

	/**
	 * An unreadable id leaves the marker text and a translated warning, no media part.
	 *
	 * @return void
	 */
	public function testUnreadableIdLeavesMarkerAndWarning(): void {
		$this->userFolder->method('getFirstNodeById')->with(7)->willReturn(null);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static fn (string $text, array $parameters = []): string => vsprintf($text, $parameters)
		);

		$processor = new TemplateProcessor(self::FIXTURE);
		$warnings  = $this->filler(l10n: $l10n)->fill(processor: $processor, images: ['logo' => 7]);

		$this->assertSame(['image unavailable: not found or no access'], $warnings);
		$parts = $this->savedParts(processor: $processor);
		$this->assertStringContainsString('Logo: [image unavailable: not found or no access]', $parts['word/document.xml']);
		$this->assertSame([], array_filter(array_keys($parts), static fn (string $name): bool => str_starts_with($name, 'word/media/')));
	}

	/**
	 * A missing context entry reads nothing and is a marker.
	 *
	 * @return void
	 */
	public function testMissingEntryReadsNothing(): void {
		$this->userFolder->expects($this->never())->method('getFirstNodeById');

		$processor = new TemplateProcessor(self::FIXTURE);
		$warnings  = $this->filler()->fill(processor: $processor, images: []);

		$this->assertSame(['image unavailable: not found or no access'], $warnings);
	}

	/**
	 * A WebP the resolver accepts cannot go into a DOCX and is a marker, not a broken part.
	 *
	 * @return void
	 */
	public function testWebpIsAMarker(): void {
		$webp = 'RIFF' . pack('V', 26) . 'WEBPVP8L' . pack('V', 13) . "\x2f\x00\x00\x00\x10\x07\x10\x11\x11\x88\x88\xfe\x07\x00";
		$this->userFolder->method('getFirstNodeById')->willReturn($this->fileWith(bytes: $webp));

		$processor = new TemplateProcessor(self::FIXTURE);
		$warnings  = $this->filler()->fill(processor: $processor, images: ['logo' => 5]);

		$this->assertSame(['image unavailable: not a raster image'], $warnings);
	}

	/**
	 * Build the filler with the real resolver over mocked storage.
	 *
	 * @param IL10N|null $l10n Optional translator.
	 *
	 * @return OfficeImageFiller
	 */
	private function filler(?IL10N $l10n = null): OfficeImageFiller {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueInt')->willReturnCallback(static fn (string $app, string $key, int $default) => $default);

		return new OfficeImageFiller(
			resolver: new TemplateImageResolver($this->rootFolder, $session, $config),
			tempManager: $this->tempManager,
			l10n: $l10n
		);
	}

	/**
	 * A readable file mock with the given bytes.
	 *
	 * @param string $bytes The content.
	 *
	 * @return File&MockObject
	 */
	private function fileWith(string $bytes): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getSize')->willReturn(strlen($bytes));
		$file->method('isReadable')->willReturn(true);
		$file->method('getContent')->willReturn($bytes);
		return $file;
	}

	/**
	 * Save the processor and return every part, keyed by zip path.
	 *
	 * @param TemplateProcessor $processor The filled template.
	 *
	 * @return array<string, string>
	 */
	private function savedParts(TemplateProcessor $processor): array {
		$path = (string)tempnam(sys_get_temp_dir(), 'filinq-docx');
		$this->tempFiles[] = $path;
		$processor->saveAs($path);

		$zip = new ZipArchive();
		$this->assertTrue($zip->open($path));
		$parts = [];
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$name = (string)$zip->getNameIndex($i);
			$parts[$name] = (string)$zip->getFromName($name);
		}

		$zip->close();
		return $parts;
	}
}

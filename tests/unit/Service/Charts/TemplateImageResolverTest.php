<?php

/**
 * Tests for TemplateImageResolver (template-charts REQ-DDTCH-006).
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Charts;

use OCA\Filinq\Service\Charts\TemplateImageResolver;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The resolver reads a file only through the generating user's own folder,
 * accepts raster images only and enforces the size cap.
 */
class TemplateImageResolverTest extends TestCase {

	/**
	 * A 1x1 transparent PNG.
	 */
	private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

	private IRootFolder&MockObject $rootFolder;
	private IUserSession&MockObject $userSession;
	private IAppConfig&MockObject $appConfig;
	private Folder&MockObject $userFolder;

	protected function setUp(): void {
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->userFolder = $this->createMock(Folder::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);
		$this->appConfig->method('getValueInt')->willReturnCallback(
			static fn (string $app, string $key, int $default) => $default
		);
	}

	private function resolver(): TemplateImageResolver {
		return new TemplateImageResolver($this->rootFolder, $this->userSession, $this->appConfig);
	}

	private function fileWith(string $bytes, ?int $size = null): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getSize')->willReturn($size ?? strlen($bytes));
		$file->method('isReadable')->willReturn(true);
		$file->method('getContent')->willReturn($bytes);
		return $file;
	}

	public function testReadableRasterImageBecomesADataUri(): void {
		$this->rootFolder->expects($this->once())->method('getUserFolder')->with('alice')->willReturn($this->userFolder);
		$this->userFolder->method('getFirstNodeById')->with(42)->willReturn($this->fileWith(base64_decode(self::PNG)));

		$result = $this->resolver()->resolve(fileId: 42);

		$this->assertNull($result['reason']);
		$this->assertSame('data:image/png;base64,' . self::PNG, $result['src']);
	}

	public function testAFileOutsideTheUsersFolderIsUnavailable(): void {
		$this->rootFolder->method('getUserFolder')->willReturn($this->userFolder);
		$this->userFolder->method('getFirstNodeById')->willReturn(null);

		$result = $this->resolver()->resolve(fileId: 7);

		$this->assertNull($result['src']);
		$this->assertSame('not found or no access', $result['reason']);
	}

	public function testAnUnreadableFileGivesTheSameReasonAndNoBytes(): void {
		$file = $this->createMock(File::class);
		$file->method('getSize')->willReturn(10);
		$file->method('isReadable')->willReturn(true);
		$file->method('getContent')->willThrowException(new NotPermittedException('no'));
		$this->rootFolder->method('getUserFolder')->willReturn($this->userFolder);
		$this->userFolder->method('getFirstNodeById')->willReturn($file);

		$result = $this->resolver()->resolve(fileId: 7);

		$this->assertNull($result['src']);
		$this->assertSame('not found or no access', $result['reason']);
	}

	public function testAnSvgFileIsRejectedEvenWhenNamedPng(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
		$this->rootFolder->method('getUserFolder')->willReturn($this->userFolder);
		$this->userFolder->method('getFirstNodeById')->willReturn($this->fileWith($svg));

		$result = $this->resolver()->resolve(fileId: 3);

		$this->assertNull($result['src']);
		$this->assertSame('not a raster image', $result['reason']);
	}

	public function testAnImageOverTheCapIsRejectedBeforeItIsRead(): void {
		$file = $this->createMock(File::class);
		$file->method('getSize')->willReturn(5 * 1024 * 1024 + 1);
		$file->method('isReadable')->willReturn(true);
		$file->expects($this->never())->method('getContent');
		$this->rootFolder->method('getUserFolder')->willReturn($this->userFolder);
		$this->userFolder->method('getFirstNodeById')->willReturn($file);

		$result = $this->resolver()->resolve(fileId: 3);

		$this->assertNull($result['src']);
		$this->assertSame('larger than %s bytes', $result['reason']);
		$this->assertSame([5242880], $result['parameters']);
	}

	public function testNoSignedInUserReadsNothing(): void {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);
		$this->rootFolder->expects($this->never())->method('getUserFolder');

		$result = (new TemplateImageResolver($this->rootFolder, $session, $this->appConfig))->resolve(fileId: 3);

		$this->assertNull($result['src']);
		$this->assertSame('not found or no access', $result['reason']);
	}

	public function testANonNumericIdIsUnavailable(): void {
		$this->rootFolder->expects($this->never())->method('getUserFolder');

		$result = $this->resolver()->resolve(fileId: '../etc/passwd');

		$this->assertSame('not found or no access', $result['reason']);
	}
}

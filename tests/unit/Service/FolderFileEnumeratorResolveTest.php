<?php

/**
 * Unit tests for FolderFileEnumerator::resolveFolder
 *
 * Pins which node a folder reference resolves to and every refusal, with
 * its message and code, before the resolution was split into steps.
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
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-4
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\ConfidentialityLabelService;
use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCA\Filinq\Service\FolderFileEnumerator;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * Tests for resolving a folder from an id or a path.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class FolderFileEnumeratorResolveTest extends TestCase {

	/**
	 * The enumerator under test.
	 *
	 * @return FolderFileEnumerator The enumerator.
	 */
	private function enumerator(): FolderFileEnumerator {
		return new FolderFileEnumerator(
			$this->createMock(OutputLayoutResolver::class),
			$this->createMock(ConfidentialityLabelService::class),
			$this->createMock(IAppConfig::class)
		);

	}//end enumerator()

	/**
	 * A folder node with the given permissions.
	 *
	 * @param int $permissions Its permission bits.
	 *
	 * @return Folder The folder.
	 */
	private function folder(int $permissions = Constants::PERMISSION_ALL): Folder {
		$folder = $this->createMock(Folder::class);
		$folder->method('getPermissions')->willReturn($permissions);
		return $folder;

	}//end folder()

	/**
	 * The refusals: id, path, message, code.
	 *
	 * @return array<string, array{0: int|null, 1: string|null, 2: string, 3: int}> Name => case.
	 */
	public static function refusals(): array {
		return [
			'neither' => [null, null, 'Either folderId or folderPath must be provided', 400],
			'an empty path only' => [null, '', 'Either folderId or folderPath must be provided', 400],
			'both' => [5, '/Docs', 'Provide only one of folderId or folderPath', 400],
			'an unknown id' => [99, null, 'Folder not found', 404],
			'an unknown path' => [null, '/Missing', 'Folder not found', 404],
			'a file by path' => [null, '/note.txt', 'Path is not a folder', 400],
		];
	}//end refusals()

	/**
	 * Each refusal keeps its message and code.
	 *
	 * @param int|null    $folderId   The id given.
	 * @param string|null $folderPath The path given.
	 * @param string      $message    The expected message.
	 * @param int         $code       The expected code.
	 *
	 * @return void
	 *
	 * @dataProvider refusals
	 */
	public function testRefusal(?int $folderId, ?string $folderPath, string $message, int $code): void {
		$user = $this->createMock(Folder::class);
		$user->method('getById')->willReturn([]);
		$user->method('get')->willReturnCallback(
			function (string $path): mixed {
				if ($path === '/note.txt') {
					return $this->createMock(File::class);
				}

				throw new NotFoundException('nope');
			}
		);

		try {
			$this->enumerator()->resolveFolder($folderId, $folderPath, $user);
			$this->fail('expected a refusal');
		} catch (Exception $e) {
			$this->assertSame([$message, $code], [$e->getMessage(), $e->getCode()]);
			if ($message === 'Folder not found' && $folderPath !== null) {
				$this->assertInstanceOf(NotFoundException::class, $e->getPrevious());
			}
		}

	}//end testRefusal()

	/**
	 * By path, the user folder's node comes back.
	 *
	 * @return void
	 */
	public function testByPath(): void {
		$target = $this->folder();
		$user = $this->createMock(Folder::class);
		$user->expects($this->never())->method('getById');
		$user->expects($this->once())->method('get')->with('/Docs')->willReturn($target);

		$this->assertSame($target, $this->enumerator()->resolveFolder(null, '/Docs', $user));

	}//end testByPath()

	/**
	 * By id, a writable mount wins over a read-only one listed first.
	 *
	 * @return void
	 */
	public function testByIdPrefersAWritableMount(): void {
		$readOnly = $this->folder(Constants::PERMISSION_READ);
		$writable = $this->folder(Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE);
		$user = $this->createMock(Folder::class);
		$user->expects($this->once())->method('getById')->with(7)->willReturn([$readOnly, $writable]);
		$user->expects($this->never())->method('get');

		$this->assertSame($writable, $this->enumerator()->resolveFolder(7, '', $user));

	}//end testByIdPrefersAWritableMount()

	/**
	 * By id, without a writable mount the first node comes back.
	 *
	 * @return void
	 */
	public function testByIdFallsBackToTheFirstMount(): void {
		$first = $this->folder(Constants::PERMISSION_READ);
		$second = $this->folder(Constants::PERMISSION_READ);
		$user = $this->createMock(Folder::class);
		$user->method('getById')->willReturn([$first, $second]);

		$this->assertSame($first, $this->enumerator()->resolveFolder(7, null, $user));

	}//end testByIdFallsBackToTheFirstMount()
}//end class

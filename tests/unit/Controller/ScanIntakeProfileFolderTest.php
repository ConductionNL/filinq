<?php

/**
 * A batch dropped in a profile's watched folder is cut with that profile.
 *
 * The Node API answers an absolute path (`/<uid>/files/Scans/Post/batch.pdf`)
 * while a profile stores the folder as the user sees it (`Scans/Post`). This
 * runs the controller against the real ScanProfileService so the two meet as
 * they do in production, not through a mocked `forPath()`.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\ScanIntakeController;
use OCA\Filinq\Controller\Support\ScanSetupServices;
use OCA\Filinq\Kernel\Contracts\ScanBatchServiceInterface;
use OCA\Filinq\Kernel\Contracts\SeparatorSheetServiceInterface;
use OCA\Filinq\Service\ScanProfileService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The watched folder decides the profile for a split without a named one.
 *
 * @covers \OCA\Filinq\Controller\ScanIntakeController
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ScanIntakeProfileFolderTest extends TestCase {

	/**
	 * The profile the splitter was handed, or null when it was not reached.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $cutWith = null;

	/**
	 * A controller over a batch at the given node path, with one profile watching `Scans/Post`.
	 *
	 * @param string $nodePath The absolute path the Node API answers for the batch.
	 *
	 * @return ScanIntakeController
	 */
	private function controller(string $nodePath): ScanIntakeController {
		$parent = $this->createMock(originalClassName: Folder::class);
		$parent->expects(matcher: $this->any())->method(constraint: 'getPath')->willReturn(value: dirname(path: $nodePath));

		$file = $this->createMock(originalClassName: File::class);
		$file->expects(matcher: $this->any())->method(constraint: 'getPath')->willReturn(value: $nodePath);
		$file->expects(matcher: $this->any())->method(constraint: 'getId')->willReturn(value: 42);
		$file->expects(matcher: $this->any())->method(constraint: 'getParent')->willReturn(value: $parent);

		$userFolder = $this->createMock(originalClassName: Folder::class);
		$userFolder->expects(matcher: $this->any())->method(constraint: 'getById')->willReturn(value: [$file]);
		$root = $this->createMock(originalClassName: IRootFolder::class);
		$root->expects(matcher: $this->any())->method(constraint: 'getUserFolder')->willReturn(value: $userFolder);

		$user = $this->createMock(originalClassName: IUser::class);
		$user->expects(matcher: $this->any())->method(constraint: 'getUID')->willReturn(value: 'fenna');
		$session = $this->createMock(originalClassName: IUserSession::class);
		$session->expects(matcher: $this->any())->method(constraint: 'getUser')->willReturn(value: $user);

		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->expects(matcher: $this->any())->method(constraint: 'getValueString')->willReturn(value: 
			(string) json_encode(value: [['id' => 'post', 'watchedFolder' => 'Scans/Post']])
		);
		$profiles = new ScanProfileService(config: $config, logger: $this->createMock(originalClassName: LoggerInterface::class));

		$batches = $this->createMock(originalClassName: ScanBatchServiceInterface::class);
		$batches->expects(matcher: $this->any())->method(constraint: 'take')->willReturnCallback(
			function (File $file, array $profile): array {
				$this->cutWith = $profile;

				return ['batch' => 'batch-1', 'segments' => 2];
			}
		);

		return new ScanIntakeController(
			request: $this->createMock(originalClassName: IRequest::class),
			setup: new ScanSetupServices(sheets: $this->createMock(originalClassName: SeparatorSheetServiceInterface::class), profiles: $profiles),
			batches: $batches,
			rootFolder: $root,
			userSession: $session
		);
	}//end controller()

	/**
	 * A batch in the watched folder is cut with that folder's profile.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testABatchInTheWatchedFolderIsCutWithItsProfile(): void {
		$response = $this->controller(nodePath: '/fenna/files/Scans/Post/batch.pdf')->split(fileId: 42);

		$this->assertSame(expected: 200, actual: $response->getStatus());
		$this->assertSame(expected: 'post', actual: $this->cutWith['id'] ?? null);
	}//end testABatchInTheWatchedFolderIsCutWithItsProfile()

	/**
	 * A batch outside every watched folder is still refused, and nothing is cut.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testABatchOutsideEveryWatchedFolderIsRefused(): void {
		$response = $this->controller(nodePath: '/fenna/files/Documents/batch.pdf')->split(fileId: 42);

		$this->assertSame(expected: 400, actual: $response->getStatus());
		$this->assertNull(actual: $this->cutWith);
	}//end testABatchOutsideEveryWatchedFolderIsRefused()

	/**
	 * Another user's `files` segment in the path is not stripped: only the caller's own root is.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testOnlyTheCallersOwnFilesRootIsStripped(): void {
		$response = $this->controller(nodePath: '/daan/files/Scans/Post/batch.pdf')->split(fileId: 42);

		$this->assertSame(expected: 400, actual: $response->getStatus());
		$this->assertNull(actual: $this->cutWith);
	}//end testOnlyTheCallersOwnFilesRootIsStripped()
}//end class

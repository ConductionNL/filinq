<?php

/**
 * Unit tests for DocumentVersionService.
 *
 * Covers the document-versions capability: listing a readable document's
 * Nextcloud file versions (newest-first, current distinguished), rejecting a
 * non-readable document, requiring write access to restore, and degrading
 * gracefully to a `versions-unavailable` notice when files_versions is disabled.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\ComparisonException;
use OCA\Filinq\Service\DocumentVersionService;
use OCP\App\IAppManager;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for DocumentVersionService.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentVersionServiceTest extends TestCase {

	/**
	 * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $logger;

	/**
	 * @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $rootFolder;

	/**
	 * @var IUserSession|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $userSession;

	/**
	 * @var IAppManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $appManager;

	/**
	 * @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $container;

	/**
	 * Set up shared mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->container = $this->createMock(ContainerInterface::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);

	}//end setUp()

	/**
	 * Build the service under test.
	 *
	 * @return DocumentVersionService
	 */
	private function buildService(): DocumentVersionService {
		return new DocumentVersionService(
			logger: $this->logger,
			rootFolder: $this->rootFolder,
			userSession: $this->userSession,
			appManager: $this->appManager,
			container: $this->container
		);

	}//end buildService()

	/**
	 * Build a File mock with the given permissions and current-file metadata.
	 *
	 * @param int $permissions The NC permission bitmask.
	 *
	 * @return File|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function mockFile(int $permissions) {
		$file = $this->createMock(File::class);
		$file->method('getPermissions')->willReturn($permissions);
		$file->method('getMTime')->willReturn(2000);
		$file->method('getSize')->willReturn(1234);
		$file->method('getOwner')->willReturn(null);

		return $file;
	}//end mockFile()

	/**
	 * Wire the user folder to return the given nodes for getById().
	 *
	 * @param array<int, mixed> $nodes The nodes to return.
	 *
	 * @return void
	 */
	private function wireFolder(array $nodes): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturn($nodes);
		$this->rootFolder->method('getUserFolder')->willReturn($folder);

	}//end wireFolder()

	/**
	 * Build an IVersion-like object exposing the accessors the service reads.
	 *
	 * @param int $timestamp The version timestamp.
	 * @param int $size The version size.
	 *
	 * @return object
	 */
	private function mockVersion(int $timestamp, int $size): object {
		return new class($timestamp, $size) {
			/**
			 * @param int $ts The timestamp.
			 * @param int $sz The size.
			 */
			public function __construct(
				private int $ts,
				private int $sz,
			) {
			}

			/**
			 * @return int
			 */
			public function getTimestamp(): int {
				return $this->ts;
			}

			/**
			 * @return int
			 */
			public function getSize(): int {
				return $this->sz;
			}
		};

	}//end mockVersion()

	/**
	 * Versions are listed newest-first with the current version distinguished.
	 *
	 * @return void
	 */
	public function testListVersionsNewestFirstWithCurrentMarked(): void {
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->wireFolder([$this->mockFile(Constants::PERMISSION_READ)]);

		$versionManager = new class($this->mockVersion(1000, 10), $this->mockVersion(1500, 20)) {

			/**
			 * @param object $a Older version.
			 * @param object $b Newer prior version.
			 */
			public function __construct(
				private object $a,
				private object $b,
			) {
			}

			/**
			 * @param mixed $user The user.
			 * @param mixed $file The file.
			 *
			 * @return array<int, object>
			 */
			public function getVersionsForFile($user, $file): array {
				return [$this->a, $this->b];
			}
		};
		$this->container->method('get')->willReturn($versionManager);

		$result = $this->buildService()->listVersions(fileId: 42);

		$this->assertCount(3, $result, 'Current + two prior versions.');
		$this->assertSame(2000, $result[0]['timestamp'], 'Current (mtime 2000) is newest.');
		$this->assertTrue($result[0]['isCurrent']);
		$this->assertSame(1500, $result[1]['timestamp']);
		$this->assertSame(1000, $result[2]['timestamp']);
		$this->assertFalse($result[2]['isCurrent']);

	}//end testListVersionsNewestFirstWithCurrentMarked()

	/**
	 * Listing a document the caller cannot read is rejected (404) — no disclosure.
	 *
	 * @return void
	 */
	public function testListVersionsRejectsUnreadableDocument(): void {
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		// getById returns nothing (no access / not found are indistinguishable).
		$this->wireFolder([]);

		$this->expectException(ComparisonException::class);

		try {
			$this->buildService()->listVersions(fileId: 42);
		} catch (ComparisonException $e) {
			$this->assertSame(404, $e->getStatusCode());
			throw $e;
		}

	}//end testListVersionsRejectsUnreadableDocument()

	/**
	 * files_versions disabled yields the graceful versions-unavailable notice (422).
	 *
	 * @return void
	 */
	public function testListVersionsGracefulWhenFilesVersionsDisabled(): void {
		$this->appManager->method('isEnabledForUser')->willReturn(false);
		$this->wireFolder([$this->mockFile(Constants::PERMISSION_READ)]);

		try {
			$this->buildService()->listVersions(fileId: 42);
			$this->fail('Expected ComparisonException');
		} catch (ComparisonException $e) {
			$this->assertSame(422, $e->getStatusCode());
			$this->assertSame('versions-unavailable', $e->getReason());
		}

	}//end testListVersionsGracefulWhenFilesVersionsDisabled()

	/**
	 * Restore is rejected for a read-only caller (no write permission → 404).
	 *
	 * @return void
	 */
	public function testRestoreRejectedForReadOnlyCaller(): void {
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		// Read-only file: PERMISSION_READ without PERMISSION_UPDATE.
		$this->wireFolder([$this->mockFile(Constants::PERMISSION_READ)]);

		try {
			$this->buildService()->restoreVersion(fileId: 42, versionTimestamp: 1000);
			$this->fail('Expected ComparisonException');
		} catch (ComparisonException $e) {
			$this->assertSame(404, $e->getStatusCode());
		}

	}//end testRestoreRejectedForReadOnlyCaller()

	/**
	 * Restore delegates to IVersionManager::rollback for a writer.
	 *
	 * @return void
	 */
	public function testRestoreRollsBackForWriter(): void {
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->wireFolder([$this->mockFile(Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE)]);

		$version = $this->mockVersion(1000, 10);
		$rolledBack = false;
		$versionManager = new class($version, $rolledBack) {

			/**
			 * @var bool
			 */
			public bool $rolled = false;

			/**
			 * @param object $v The version.
			 * @param bool $r Unused seed.
			 */
			public function __construct(
				private object $v,
				bool $r,
			) {
			}

			/**
			 * @param mixed $user The user.
			 * @param mixed $file The file.
			 *
			 * @return array<int, object>
			 */
			public function getVersionsForFile($user, $file): array {
				return [$this->v];
			}

			/**
			 * @param mixed $version The version to roll back.
			 *
			 * @return void
			 */
			public function rollback($version): void {
				$this->rolled = true;
			}
		};
		$this->container->method('get')->willReturn($versionManager);

		$this->buildService()->restoreVersion(fileId: 42, versionTimestamp: 1000);

		$this->assertTrue($versionManager->rolled, 'rollback() must be invoked for a writer.');

	}//end testRestoreRollsBackForWriter()
}//end class

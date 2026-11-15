<?php

/**
 * Unit tests for DocumentVersionService: reading a version, file access and version authors
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\ComparisonException;
use OCA\Filinq\Service\DocumentVersionService;
use OCA\Filinq\Service\FinalDocumentService;
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
use RuntimeException;

/**
 * Who may read which version, what a read answers, and whose name a version carries.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentVersionReadTest extends TestCase {

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $logger;

	/**
	 * The root folder double.
	 *
	 * @var IRootFolder&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $rootFolder;

	/**
	 * The container double, answering the version manager.
	 *
	 * @var ContainerInterface&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $container;

	/**
	 * The app manager double.
	 *
	 * @var IAppManager&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $appManager;

	/**
	 * Set up the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);

	}//end setUp()

	/**
	 * The service, for a signed-in user or nobody.
	 *
	 * @param bool $signedIn Whether a user is signed in.
	 *
	 * @return DocumentVersionService
	 */
	private function service(bool $signedIn = true): DocumentVersionService {
		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('alice');
		}

		$session->method('getUser')->willReturn($user);

		return new DocumentVersionService(
			logger: $this->logger,
			rootFolder: $this->rootFolder,
			userSession: $session,
			appManager: $this->appManager,
			container: $this->container,
			finalDocuments: $this->createMock(FinalDocumentService::class)
		);

	}//end service()

	/**
	 * A file with the given permissions and content.
	 *
	 * @param int $permissions The permission bitmask.
	 *
	 * @return File&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function file(int $permissions = Constants::PERMISSION_ALL) {
		$file = $this->createMock(File::class);
		$file->method('getPermissions')->willReturn($permissions);
		$file->method('getContent')->willReturn('current bytes');
		$file->method('getMTime')->willReturn(2000);
		$file->method('getSize')->willReturn(5);

		return $file;

	}//end file()

	/**
	 * Let the user folder answer the given nodes for every file id but 7, so the id guard is what refuses 0 and below.
	 *
	 * @param array<int, mixed> $nodes The nodes.
	 *
	 * @return void
	 */
	private function nodes(array $nodes): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(
			static function (int $id) use ($nodes): array {
				if ($id === 7) {
					return [];
				}

				return $nodes;
			}
		);
		$this->rootFolder->method('getUserFolder')->with('alice')->willReturn($folder);

	}//end nodes()

	/**
	 * A version manager over the given versions; `read` answers per timestamp.
	 *
	 * @param array<int, object>      $versions The versions.
	 * @param array<int, mixed>       $contents What read() answers per timestamp (a Throwable is thrown).
	 * @param RuntimeException|null   $listing  Thrown by getVersionsForFile when set.
	 *
	 * @return object
	 */
	private function versionManager(array $versions, array $contents = [], ?RuntimeException $listing = null): object {
		return new class($versions, $contents, $listing) {
			/**
			 * The timestamps read, in order.
			 *
			 * @var array<int, int>
			 */
			public array $reads = [];

			/**
			 * Constructor.
			 *
			 * @param array<int, object>    $versions The versions.
			 * @param array<int, mixed>     $contents What read() answers.
			 * @param RuntimeException|null $listing  The listing failure.
			 */
			public function __construct(private array $versions, private array $contents, private ?RuntimeException $listing) {
			}

			/**
			 * The versions.
			 *
			 * @param mixed $user The user.
			 * @param mixed $file The file.
			 *
			 * @return array<int, object>
			 */
			public function getVersionsForFile($user, $file): array {
				if ($this->listing !== null) {
					throw $this->listing;
				}

				return $this->versions;
			}

			/**
			 * The bytes of a version.
			 *
			 * @param object $version The version.
			 *
			 * @return mixed
			 */
			public function read($version) {
				$this->reads[] = spl_object_id($version);
				$content = $this->contents[$version->getTimestamp()] ?? '';
				if ($content instanceof \Throwable) {
					throw $content;
				}

				return $content;
			}
		};

	}//end versionManager()

	/**
	 * A version with only a timestamp.
	 *
	 * @param int $timestamp The timestamp.
	 *
	 * @return object
	 */
	private function version(int $timestamp): object {
		return new class($timestamp) {
			/**
			 * Constructor.
			 *
			 * @param int $ts The timestamp.
			 */
			public function __construct(private int $ts) {
			}

			/**
			 * The timestamp.
			 *
			 * @return int
			 */
			public function getTimestamp(): int {
				return $this->ts;
			}
		};

	}//end version()

	/**
	 * Assert that the call fails with the given status, reason and message.
	 *
	 * @param callable $call     The call.
	 * @param string   $expected "status reason message".
	 *
	 * @return void
	 */
	private function assertRefused(callable $call, string $expected): void {
		try {
			$call();
			$this->fail('Expected a ComparisonException: ' . $expected);
		} catch (ComparisonException $e) {
			$this->assertSame($expected, $e->getStatusCode() . ' ' . $e->getReason() . ' ' . $e->getMessage());
		}

	}//end assertRefused()

	/**
	 * The current version is readable with read permission, and refused as not found otherwise.
	 *
	 * @return void
	 */
	public function testFileAccessAllowedAndRefused(): void {
		$notFound = '404 not-found Document not found.';

		$this->nodes([$this->file(Constants::PERMISSION_READ)]);
		$this->assertSame('current bytes', $this->service()->readVersion(42, 0));
		$this->assertRefused(fn () => $this->service()->readVersion(7, 0), $notFound);
		$this->assertRefused(fn () => $this->service()->readVersion(0, 0), $notFound);
		$this->assertRefused(fn () => $this->service()->readVersion(-1, 0), $notFound);
		$this->assertRefused(fn () => $this->service(false)->readVersion(42, 0), $notFound);

	}//end testFileAccessAllowedAndRefused()

	/**
	 * A node that is not a file, or a file without read permission, is not found.
	 *
	 * @return void
	 */
	public function testFoldersAndUnreadableFilesAreNotFound(): void {
		$this->nodes([$this->createMock(Folder::class), $this->file()]);
		$this->assertRefused(fn () => $this->service()->readVersion(42, 0), '404 not-found Document not found.');

		$this->setUp();
		$this->nodes([$this->file(Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE)]);
		$this->assertRefused(fn () => $this->service()->readVersion(42, 0), '404 not-found Document not found.');

	}//end testFoldersAndUnreadableFilesAreNotFound()

	/**
	 * Restoring needs update permission: read alone is not found, update goes on.
	 *
	 * @return void
	 */
	public function testRestoreNeedsUpdatePermission(): void {
		$this->nodes([$this->file(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE)]);
		$this->assertRefused(fn () => $this->service()->restoreVersion(42, 1000), '404 not-found Document not found.');

		$this->setUp();
		$this->nodes([$this->file(Constants::PERMISSION_UPDATE)]);
		$this->container->method('get')->willReturn($this->versionManager([]));
		$this->assertRefused(fn () => $this->service()->restoreVersion(42, 1000), '404 not-found version');

	}//end testRestoreNeedsUpdatePermission()

	/**
	 * An unreadable current file is reported as a missing version.
	 *
	 * @return void
	 */
	public function testAnUnreadableCurrentFileIsAMissingVersion(): void {
		$file = $this->createMock(File::class);
		$file->method('getPermissions')->willReturn(Constants::PERMISSION_ALL);
		$file->method('getContent')->willThrowException(new RuntimeException('locked'));
		$this->nodes([$file]);

		$this->assertRefused(fn () => $this->service()->readVersion(42, 0), '404 not-found version');

	}//end testAnUnreadableCurrentFileIsAMissingVersion()

	/**
	 * A stored version is read by timestamp, as a string or from a stream; the first match wins.
	 *
	 * @return void
	 */
	public function testAStoredVersionIsReadByTimestamp(): void {
		$this->nodes([$this->file()]);
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, 'streamed bytes');
		rewind($stream);
		$first = $this->version(1000);
		$versions = [$this->version(500), $first, $this->version(1000), $this->version(1500)];
		$manager = $this->versionManager($versions, [500 => 'old', 1000 => 'thousand', 1500 => $stream]);
		$this->container->method('get')->willReturn($manager);

		$this->assertSame('thousand', $this->service()->readVersion(42, 1000));
		$this->assertSame([spl_object_id($first)], $manager->reads);
		$this->assertSame('streamed bytes', $this->service()->readVersion(42, 1500));

	}//end testAStoredVersionIsReadByTimestamp()

	/**
	 * An unknown timestamp, a failing listing or a failing read is a missing version; the failures are logged.
	 *
	 * @return void
	 */
	public function testMissingAndFailingVersionsAreNotFound(): void {
		$this->nodes([$this->file()]);
		$this->container->method('get')->willReturnOnConsecutiveCalls(
			$this->versionManager([$this->version(1000)]),
			$this->versionManager([], [], new RuntimeException('listing broke')),
			$this->versionManager([$this->version(1000)], [1000 => new RuntimeException('read broke')])
		);
		$this->logger->expects($this->exactly(2))->method('debug')->willReturnCallback(
			function (string $message, array $context): void {
				$this->assertSame('Version read failed', $message);
				$this->assertSame(42, $context['fileId']);
				$this->assertContains($context['exception'], ['listing broke', 'read broke']);
			}
		);

		$this->assertRefused(fn () => $this->service()->readVersion(42, 999), '404 not-found version');
		$this->assertRefused(fn () => $this->service()->readVersion(42, 1000), '404 not-found version');
		$this->assertRefused(fn () => $this->service()->readVersion(42, 1000), '404 not-found version');

	}//end testMissingAndFailingVersionsAreNotFound()

	/**
	 * Without files_versions, or without a version manager, a stored version is unavailable.
	 *
	 * @return void
	 */
	public function testVersionsUnavailable(): void {
		$this->nodes([$this->file()]);
		$this->container->method('get')->willThrowException(new RuntimeException('no manager'));
		$this->assertRefused(fn () => $this->service()->readVersion(42, 1000), '422 versions-unavailable version manager unavailable');

		$this->setUp();
		$this->nodes([$this->file()]);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('isEnabledForUser')->with('files_versions')->willReturn(false);
		$this->appManager = $apps;
		$this->assertRefused(fn () => $this->service()->readVersion(42, 1000), '422 versions-unavailable files_versions disabled');
		$this->assertSame('current bytes', $this->service()->readVersion(42, 0));

	}//end testVersionsUnavailable()

	/**
	 * A version's author is its own author, else its user's id, else the owner of the file.
	 *
	 * @return void
	 */
	public function testVersionAuthors(): void {
		$owner = $this->createMock(IUser::class);
		$owner->method('getUID')->willReturn('owner');
		$file = $this->file();
		$file->method('getOwner')->willReturn($owner);
		$this->nodes([$file]);

		$withUid = new class {
			/**
			 * The id.
			 *
			 * @return string
			 */
			public function getUID(): string {
				return 'carol';
			}
		};
		$versions = [
			new class {
				/**
				 * The timestamp.
				 *
				 * @return int
				 */
				public function getTimestamp(): int {
					return 10;
				}

				/**
				 * The author.
				 *
				 * @return string
				 */
				public function getAuthor(): string {
					return 'bob';
				}
			},
			new class($withUid) {
				/**
				 * Constructor.
				 *
				 * @param object $user The user.
				 */
				public function __construct(private object $user) {
				}

				/**
				 * The timestamp.
				 *
				 * @return int
				 */
				public function getTimestamp(): int {
					return 9;
				}

				/**
				 * The author.
				 *
				 * @return string
				 */
				public function getAuthor(): string {
					return '';
				}

				/**
				 * The user.
				 *
				 * @return object
				 */
				public function getUser(): object {
					return $this->user;
				}
			},
			new class {
				/**
				 * The timestamp.
				 *
				 * @return int
				 */
				public function getTimestamp(): int {
					return 8;
				}

				/**
				 * No author.
				 *
				 * @return string|null
				 */
				public function getAuthor(): ?string {
					return null;
				}

				/**
				 * No user.
				 *
				 * @return object|null
				 */
				public function getUser(): ?object {
					return null;
				}
			},
			new class {
				/**
				 * The timestamp.
				 *
				 * @return int
				 */
				public function getTimestamp(): int {
					return 7;
				}

				/**
				 * A user without an id.
				 *
				 * @return object
				 */
				public function getUser(): object {
					return new \stdClass();
				}
			},
			new class {
				/**
				 * The timestamp.
				 *
				 * @return int
				 */
				public function getTimestamp(): int {
					return 6;
				}
			},
		];
		$this->container->method('get')->willReturn($this->versionManager($versions));

		$authors = array_column($this->service()->listVersions(42), 'author', 'timestamp');
		$this->assertSame([2000 => 'owner', 10 => 'bob', 9 => 'carol', 8 => 'owner', 7 => 'owner', 6 => 'owner'], $authors);

	}//end testVersionAuthors()
}//end class

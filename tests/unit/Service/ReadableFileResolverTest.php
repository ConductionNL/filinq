<?php

/**
 * Unit tests for ReadableFileResolver
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
 * @spec openspec/specs/document-register/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\ReadableFileResolver;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts that a file is resolved through the reader's own Files first, and
 * otherwise only through the read right on the object that owns it.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ReadableFileResolverTest extends TestCase {

	/**
	 * The file ids OpenRegister was asked to open.
	 *
	 * @var array<int, int>
	 */
	private array $opened = [];

	/**
	 * A file double with a name.
	 *
	 * @param string $name The file name.
	 *
	 * @return File The file.
	 */
	private function file(string $name): File {
		$file = $this->createMock(originalClassName: File::class);
		$file->method('getName')->willReturn($name);

		return $file;

	}//end file()

	/**
	 * Build the resolver.
	 *
	 * @param array<int, string> $home File id to name, for files in the reader's own Files.
	 * @param array<int, string> $objectFiles File id to name, for files in an object's folder.
	 * @param array<int, bool> $mayRead File id to the object read rule's verdict.
	 * @param bool $withOpenRegister Whether OpenRegister is installed.
	 * @param bool $signedIn Whether a reader is signed in.
	 *
	 * @return ReadableFileResolver The resolver under test.
	 */
	private function resolver(
		array $home,
		array $objectFiles=[],
		array $mayRead=[],
		bool $withOpenRegister=true,
		bool $signedIn=true,
	): ReadableFileResolver {
		$userFolder = $this->createMock(originalClassName: Folder::class);
		$userFolder->method('getById')->willReturnCallback(
			function (int $fileId) use ($home): array {
				if (isset($home[$fileId]) === false) {
					return [];
				}

				return [$this->file(name: $home[$fileId])];
			}
		);
		$rootFolder = $this->createMock(originalClassName: IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturn($userFolder);

		$session = $this->createMock(originalClassName: IUserSession::class);
		if ($signedIn === true) {
			$user = $this->createMock(originalClassName: IUser::class);
			$user->method('getUID')->willReturn('pieter');
			$session->method('getUser')->willReturn($user);
		}

		$scope = new class($mayRead) {
			/**
			 * @param array<int, bool> $mayRead The verdicts.
			 */
			public function __construct(private array $mayRead) {
			}

			/**
			 * @param int $fileId The file id.
			 *
			 * @return bool The verdict.
			 */
			public function mayReadFile(int $fileId): bool {
				return ($this->mayRead[$fileId] ?? false);
			}
		};
		$fileService = new class($objectFiles, $this) {
			/**
			 * @param array<int, string> $files The object files.
			 * @param ReadableFileResolverTest $test The test, to record what was opened.
			 */
			public function __construct(private array $files, private ReadableFileResolverTest $test) {
			}

			/**
			 * @param int $fileId The file id.
			 *
			 * @return File|null The file.
			 */
			public function getFileById(int $fileId): ?File {
				return $this->test->open(fileId: $fileId, name: ($this->files[$fileId] ?? null));
			}
		};

		$locator = $this->createMock(originalClassName: OpenRegisterServiceLocator::class);
		$locator->method('get')->willReturnCallback(
			static function (string $className) use ($withOpenRegister, $scope, $fileService): mixed {
				if ($withOpenRegister === false) {
					throw new RuntimeException($className . ' is not available.');
				}

				return match ($className) {
					ReadableFileResolver::OR_READ_SCOPE => $scope,
					ReadableFileResolver::OR_FILE_SERVICE => $fileService,
				};
			}
		);

		return new ReadableFileResolver(
			rootFolder: $rootFolder,
			userSession: $session,
			locator: $locator,
			logger: $this->createMock(originalClassName: LoggerInterface::class)
		);

	}//end resolver()

	/**
	 * Record that OpenRegister was asked to open a file, and answer with it.
	 *
	 * @param int $fileId The file id.
	 * @param string|null $name The file name, or null when there is no such file.
	 *
	 * @return File|null The file.
	 */
	public function open(int $fileId, ?string $name): ?File {
		$this->opened[] = $fileId;

		if ($name === null) {
			return null;
		}

		return $this->file(name: $name);

	}//end open()

	/**
	 * A file in the reader's own Files resolves there, without OpenRegister.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-register/spec.md
	 */
	public function testAFileInTheReadersOwnFilesResolvesThere(): void {
		$node = $this->resolver(home: [4711 => 'eigen.pdf'])->node(fileId: 4711);

		$this->assertSame(expected: 'eigen.pdf', actual: $node?->getName());
		$this->assertSame(expected: [], actual: $this->opened);

	}//end testAFileInTheReadersOwnFilesResolvesThere()

	/**
	 * A file in the folder of an object the reader may read resolves through that object.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-register/spec.md
	 */
	public function testAnObjectsFileResolvesWhenTheReaderMayReadTheObject(): void {
		$node = $this->resolver(
			home: [],
			objectFiles: [21064 => '02-advies-verlichting-fietspad.pdf'],
			mayRead: [21064 => true]
		)->node(fileId: 21064);

		$this->assertSame(expected: '02-advies-verlichting-fietspad.pdf', actual: $node?->getName());

	}//end testAnObjectsFileResolvesWhenTheReaderMayReadTheObject()

	/**
	 * An object's file the reader may not read is never opened.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-register/spec.md
	 */
	public function testAnObjectsFileTheReaderMayNotReadIsNeverOpened(): void {
		$node = $this->resolver(
			home: [],
			objectFiles: [21064 => '02-advies-verlichting-fietspad.pdf'],
			mayRead: [21064 => false]
		)->node(fileId: 21064);

		$this->assertNull(actual: $node);
		$this->assertSame(expected: [], actual: $this->opened);

	}//end testAnObjectsFileTheReaderMayNotReadIsNeverOpened()

	/**
	 * Without OpenRegister only the reader's own Files count.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-register/spec.md
	 */
	public function testWithoutOpenRegisterOnlyTheReadersOwnFilesCount(): void {
		$node = $this->resolver(
			home: [],
			objectFiles: [21064 => 'advies.pdf'],
			mayRead: [21064 => true],
			withOpenRegister: false
		)->node(fileId: 21064);

		$this->assertNull(actual: $node);

	}//end testWithoutOpenRegisterOnlyTheReadersOwnFilesCount()

	/**
	 * Nobody signed in reads nothing, and an id that is not a file id resolves to nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-register/spec.md
	 */
	public function testNoReaderOrNoFileIdResolvesToNothing(): void {
		$anonymous = $this->resolver(home: [4711 => 'eigen.pdf'], mayRead: [4711 => true], signedIn: false);
		$this->assertNull(actual: $anonymous->node(fileId: 4711));

		$this->assertNull(actual: $this->resolver(home: [])->node(fileId: 0));
		$this->assertSame(expected: [], actual: $this->opened);

	}//end testNoReaderOrNoFileIdResolvesToNothing()
}//end class

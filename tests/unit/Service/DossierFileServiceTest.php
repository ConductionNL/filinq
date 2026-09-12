<?php

/**
 * Unit tests for DossierFileService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DossierFileService;
use OCA\Filinq\Service\DossierObjectRepository;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The filesystem half of the dossier surface.
 *
 * THE PROPERTY EVERY CASE HERE PINS: a user can move, rename or delete the
 * files behind a dossier at any moment, and none of that is an error the
 * surface may fail on. Each read degrades to `null`, `[]` or a readable
 * warning, and the ONE thing that throws is a name that cannot be honoured at
 * all. A method that started raising instead would take the whole dossier page
 * down for a file somebody dragged into another folder.
 *
 * phpcs:disable CustomSniffs.Functions.NamedParameters
 *
 * @covers \OCA\Filinq\Service\DossierFileService
 */
class DossierFileServiceTest extends TestCase {

	/**
	 * OpenRegister access, used only to resolve a folder reference.
	 *
	 * @var DossierObjectRepository&MockObject
	 */
	private DossierObjectRepository&MockObject $repository;

	/**
	 * Nextcloud filesystem root.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * The session.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $userSession;

	/**
	 * Warnings the service logged.
	 *
	 * @var array<int, string>
	 */
	private array $warnings = [];

	/**
	 * The subject.
	 *
	 * @var DossierFileService
	 */
	private DossierFileService $service;

	/**
	 * Build the subject over doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository = $this->createMock(DossierObjectRepository::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);

		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('warning')->willReturnCallback(
			function (string $message): void {
				$this->warnings[] = $message;
			}
		);

		$this->service = new DossierFileService(
			$this->repository,
			$this->rootFolder,
			$this->userSession,
			$logger,
		);

	}//end setUp()

	// ------------------------------------------------------------------
	// safeFolderName
	// ------------------------------------------------------------------

	/**
	 * A name is made safe to write to disk, and a name with nothing left in it
	 * still lands somewhere the operator can find it.
	 *
	 * Separators become dashes BEFORE the empty check, so "///" is the
	 * perfectly valid folder "---" rather than the fallback. Worth pinning:
	 * the obvious reading of the method is the other one, and the comment in
	 * it said so until this test disagreed.
	 *
	 * @return void
	 */
	public function testASlashInADossierNameNeverBecomesAPathSegment(): void {
		$this->assertSame('Woo 2026-001 - deel 2', $this->service->safeFolderName('Woo 2026-001 / deel 2'));
		$this->assertSame('a-b', $this->service->safeFolderName('a\\b'));
		$this->assertSame('---', $this->service->safeFolderName('///'));
		$this->assertSame('Dossier', $this->service->safeFolderName('   '));
		$this->assertSame('Dossier', $this->service->safeFolderName(''));

	}//end testASlashInADossierNameNeverBecomesAPathSegment()

	// ------------------------------------------------------------------
	// homeFolder
	// ------------------------------------------------------------------

	/**
	 * The reference is read from `@self.folder` first.
	 *
	 * @return void
	 */
	public function testTheFolderReferenceIsReadFromTheSelfBlock(): void {
		$folder = $this->createMock(Folder::class);
		$this->repository->expects(self::once())
			->method('resolveDossierFolder')
			->with(4242)
			->willReturn($folder);

		$this->assertSame(
			$folder,
			$this->service->homeFolder((object)[], ['@self' => ['folder' => 4242]])
		);

	}//end testTheFolderReferenceIsReadFromTheSelfBlock()

	/**
	 * A payload without one asks the OBJECT, which is where an entity loaded
	 * straight from OpenRegister carries it.
	 *
	 * @return void
	 */
	public function testAPayloadWithoutAReferenceAsksTheObject(): void {
		$folder = $this->createMock(Folder::class);
		$this->repository->method('resolveDossierFolder')->with(77)->willReturn($folder);

		$object = new class {

			/**
			 * @return int The folder node id.
			 */
			public function getFolder(): int {
				return 77;
			}
		};

		$this->assertSame($folder, $this->service->homeFolder($object, []));

	}//end testAPayloadWithoutAReferenceAsksTheObject()

	/**
	 * No reference anywhere is null, not an exception: a dossier may simply
	 * not have a folder yet.
	 *
	 * @return void
	 */
	public function testADossierWithNoBoundFolderAnswersNull(): void {
		$this->repository->expects(self::never())->method('resolveDossierFolder');

		$this->assertNull($this->service->homeFolder((object)[], []));
		$this->assertNull($this->service->homeFolder((object)[], ['@self' => ['folder' => '']]));

	}//end testADossierWithNoBoundFolderAnswersNull()

	/**
	 * 🔴 A folder the caller cannot read, or that has been deleted, is null
	 * rather than a 500. The resolver raises for both, and the dossier page
	 * still has an index to render.
	 *
	 * @return void
	 */
	public function testAnUnresolvableFolderAnswersNullRatherThanRaising(): void {
		$this->repository->method('resolveDossierFolder')
			->willThrowException(new RuntimeException('folder node id 4242 not found'));

		$this->assertNull($this->service->homeFolder((object)[], ['@self' => ['folder' => 4242]]));

	}//end testAnUnresolvableFolderAnswersNullRatherThanRaising()

	// ------------------------------------------------------------------
	// renameHomeFolder
	// ------------------------------------------------------------------

	/**
	 * A dossier with no bound folder renames without a warning: there was
	 * nothing to keep in sync.
	 *
	 * @return void
	 */
	public function testRenamingADossierWithoutAFolderWarnsAboutNothing(): void {
		$this->assertSame('', $this->service->renameHomeFolder((object)[], [], 'New name'));

	}//end testRenamingADossierWithoutAFolderWarnsAboutNothing()

	/**
	 * A folder already carrying the safe name is left alone, and no move is
	 * attempted.
	 *
	 * @return void
	 */
	public function testAFolderAlreadyNamedCorrectlyIsNotMoved(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getName')->willReturn('Woo 2026-001');
		$folder->expects(self::never())->method('move');
		$this->repository->method('resolveDossierFolder')->willReturn($folder);

		$this->assertSame(
			'',
			$this->service->renameHomeFolder((object)[], ['@self' => ['folder' => 1]], 'Woo 2026-001')
		);

	}//end testAFolderAlreadyNamedCorrectlyIsNotMoved()

	/**
	 * The rename moves the folder beside itself under the new name.
	 *
	 * @return void
	 */
	public function testARenameMovesTheFolderToTheNewName(): void {
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(false);
		$parent->method('getPath')->willReturn('/alice/files/Filinq');

		$folder = $this->createMock(Folder::class);
		$folder->method('getName')->willReturn('Woo 2026-001');
		$folder->method('getParent')->willReturn($parent);
		$folder->expects(self::once())->method('move')->with('/alice/files/Filinq/Woo 2026-002');
		$this->repository->method('resolveDossierFolder')->willReturn($folder);

		$this->assertSame(
			'',
			$this->service->renameHomeFolder((object)[], ['@self' => ['folder' => 1]], 'Woo 2026-002')
		);

	}//end testARenameMovesTheFolderToTheNewName()

	/**
	 * 🔴 NEVER MERGE, NEVER OVERWRITE. A folder already sitting at the new
	 * name means somebody else's documents are there, so the move is refused
	 * and the operator is told. The object rename still stands, which is why
	 * this is a warning rather than a failure.
	 *
	 * @return void
	 */
	public function testAnOccupiedTargetNameRefusesTheMoveAndSaysSo(): void {
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(true);

		$folder = $this->createMock(Folder::class);
		$folder->method('getName')->willReturn('Woo 2026-001');
		$folder->method('getParent')->willReturn($parent);
		$folder->expects(self::never())->method('move');
		$this->repository->method('resolveDossierFolder')->willReturn($folder);

		$this->assertSame(
			'The folder was not renamed: "Woo 2026-002" already exists here.',
			$this->service->renameHomeFolder((object)[], ['@self' => ['folder' => 1]], 'Woo 2026-002')
		);

	}//end testAnOccupiedTargetNameRefusesTheMoveAndSaysSo()

	/**
	 * A caller without permission gets a sentence about permission, not the
	 * raw exception: it is the one cause an operator can act on themselves.
	 *
	 * @return void
	 */
	public function testAPermissionRefusalIsNamedAsOne(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getName')->willReturn('Woo 2026-001');
		$folder->method('getParent')->willThrowException(new NotPermittedException());
		$this->repository->method('resolveDossierFolder')->willReturn($folder);

		$this->assertSame(
			'The folder was not renamed: you do not have permission to rename it.',
			$this->service->renameHomeFolder((object)[], ['@self' => ['folder' => 1]], 'Woo 2026-002')
		);
		$this->assertSame([], $this->warnings, 'a permission refusal is expected, not a fault to log');

	}//end testAPermissionRefusalIsNamedAsOne()

	/**
	 * Any other failure is carried into the warning AND logged, so a cause
	 * nobody anticipated is still visible on both sides.
	 *
	 * @return void
	 */
	public function testAnyOtherRenameFailureIsSurfacedAndLogged(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getName')->willReturn('Woo 2026-001');
		$folder->method('getParent')->willThrowException(new RuntimeException('storage is read only'));
		$this->repository->method('resolveDossierFolder')->willReturn($folder);

		$this->assertSame(
			'The folder was not renamed: storage is read only',
			$this->service->renameHomeFolder((object)[], ['@self' => ['folder' => 1]], 'Woo 2026-002')
		);
		$this->assertCount(1, $this->warnings);

	}//end testAnyOtherRenameFailureIsSurfacedAndLogged()

	// ------------------------------------------------------------------
	// enumerateFolder
	// ------------------------------------------------------------------

	/**
	 * The listing is files only. A subfolder is not a document, and rendering
	 * one as a row gives the operator something they cannot open.
	 *
	 * @return void
	 */
	public function testTheListingCarriesFilesAndNotFolders(): void {
		$file = $this->createMock(File::class);
		$file->method('getType')->willReturn(FileInfo::TYPE_FILE);

		$sub = $this->createMock(Folder::class);
		$sub->method('getType')->willReturn(FileInfo::TYPE_FOLDER);

		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willReturn([$sub, $file]);

		$this->assertSame([$file], $this->service->enumerateFolder($folder));

	}//end testTheListingCarriesFilesAndNotFolders()

	/**
	 * A folder that cannot be listed is an empty list plus a log line, never
	 * an exception: the dossier's other blocks still render.
	 *
	 * @return void
	 */
	public function testAnUnlistableFolderIsEmptyAndLogged(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willThrowException(new RuntimeException('gone'));

		$this->assertSame([], $this->service->enumerateFolder($folder));
		$this->assertCount(1, $this->warnings);

	}//end testAnUnlistableFolderIsEmptyAndLogged()

	// ------------------------------------------------------------------
	// nodeFor and isInFolder
	// ------------------------------------------------------------------

	/**
	 * A file id resolves under the CALLER's own view, so a file they cannot
	 * read is absent to them.
	 *
	 * @return void
	 */
	public function testAFileIdResolvesUnderTheCallersOwnView(): void {
		$file = $this->createMock(File::class);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->with(42)->willReturn([$file]);
		$this->rootFolder->method('getUserFolder')->with('alice')->willReturn($userFolder);

		$this->assertSame($file, $this->service->nodeFor(42));

	}//end testAFileIdResolvesUnderTheCallersOwnView()

	/**
	 * An id nothing answers to is null, and so is a lookup that raises.
	 *
	 * @return void
	 */
	public function testAnUnresolvableFileIdIsNull(): void {
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturn([]);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->assertNull($this->service->nodeFor(42));

	}//end testAnUnresolvableFileIdIsNull()

	/**
	 * A failing lookup is null too, rather than a 500 on the dossier page.
	 *
	 * @return void
	 */
	public function testAFailingLookupIsNullRatherThanRaising(): void {
		$this->rootFolder->method('getUserFolder')->willThrowException(new RuntimeException('no such user'));

		$this->assertNull($this->service->nodeFor(42));

	}//end testAFailingLookupIsNullRatherThanRaising()

	/**
	 * Membership is decided on the path, and a SIBLING whose name starts with
	 * the folder's own is outside it. Comparing without the separator would
	 * count `/alice/files/Filinq/Woo-2` as inside `/alice/files/Filinq/Woo`.
	 *
	 * @return void
	 */
	public function testASiblingWithASharedPrefixIsNotInsideTheFolder(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn('/alice/files/Filinq/Woo');

		$inside = $this->createMock(File::class);
		$inside->method('getPath')->willReturn('/alice/files/Filinq/Woo/report.docx');

		$sibling = $this->createMock(File::class);
		$sibling->method('getPath')->willReturn('/alice/files/Filinq/Woo-2/report.docx');

		$this->assertTrue($this->service->isInFolder($inside, $folder));
		$this->assertFalse($this->service->isInFolder($sibling, $folder));

	}//end testASiblingWithASharedPrefixIsNotInsideTheFolder()

	/**
	 * A node whose path cannot be read is not inside anything, rather than
	 * taking the caller down with it.
	 *
	 * @return void
	 */
	public function testANodeWithoutAReadablePathIsNotInside(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn('/alice/files/Filinq/Woo');

		$node = $this->createMock(File::class);
		$node->method('getPath')->willThrowException(new RuntimeException('gone'));

		$this->assertFalse($this->service->isInFolder($node, $folder));

	}//end testANodeWithoutAReadablePathIsNotInside()

	// ------------------------------------------------------------------
	// trash
	// ------------------------------------------------------------------

	/**
	 * Removal routes through delete(), which Nextcloud sends to the trashbin
	 * when files_trashbin is enabled. That is what makes it recoverable, and
	 * it is why this is not an unlink.
	 *
	 * @return void
	 */
	public function testTrashingDeletesThroughNextcloudSoItIsRecoverable(): void {
		$file = $this->createMock(File::class);
		$file->expects(self::once())->method('delete');

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturn([$file]);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->service->trash(42);

	}//end testTrashingDeletesThroughNextcloudSoItIsRecoverable()

	/**
	 * A file that is already gone is nothing to do, silently.
	 *
	 * @return void
	 */
	public function testTrashingAnAbsentFileDoesNothing(): void {
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturn([]);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->service->trash(42);

		$this->assertSame([], $this->warnings);

	}//end testTrashingAnAbsentFileDoesNothing()

	/**
	 * 🔴 A removal that fails is LOGGED, not swallowed. The caller has
	 * already unlinked the reference by this point, so a silent failure
	 * leaves a file on disk that no dossier admits to holding.
	 *
	 * @return void
	 */
	public function testAFailedRemovalIsLogged(): void {
		$file = $this->createMock(File::class);
		$file->method('delete')->willThrowException(new NotPermittedException());

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturn([$file]);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->service->trash(42);

		$this->assertCount(1, $this->warnings);

	}//end testAFailedRemovalIsLogged()

	// ------------------------------------------------------------------
	// createHomeFolder
	// ------------------------------------------------------------------

	/**
	 * With no session there is no user folder to create anything in, and the
	 * refusal says so rather than raising further down.
	 *
	 * @return void
	 */
	public function testCreatingAFolderWithoutASessionIsRefused(): void {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);

		$service = new DossierFileService(
			$this->repository,
			$this->rootFolder,
			$session,
			$this->createMock(LoggerInterface::class),
		);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(401);

		$service->createHomeFolder('Woo 2026-001');

	}//end testCreatingAFolderWithoutASessionIsRefused()

}//end class

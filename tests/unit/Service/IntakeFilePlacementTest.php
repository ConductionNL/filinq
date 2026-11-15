<?php

/**
 * Unit tests for IntakeFilePlacement
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeFilePlacement;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * An assigned intake file moves into the folder of its record when that record
 * owns one the clerk can see; every other outcome answers null and never throws.
 *
 * @covers \OCA\Filinq\Service\IntakeFilePlacement
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class IntakeFilePlacementTest extends TestCase {

	/**
	 * The OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objectService;

	/**
	 * The file tree.
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
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The clerk's files root.
	 *
	 * @var Folder&MockObject
	 */
	private Folder&MockObject $userFolder;

	/**
	 * Set up the boundaries.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->objectService = $this->createMock(ObjectService::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->userFolder = $this->createMock(Folder::class);

	}//end setUp()

	/**
	 * Build the placement over a real resolver; OpenRegister installed or not.
	 *
	 * @param bool $openRegister Whether OpenRegister is installed.
	 *
	 * @return IntakeFilePlacement
	 */
	private function placement(bool $openRegister = true): IntakeFilePlacement {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn($openRegister === true ? ['openregister'] : []);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\OpenRegister\Service\ObjectService')->willReturn($this->objectService);

		return new IntakeFilePlacement(
			new DocumentObjectServiceResolver($container, $appManager),
			$this->rootFolder,
			$this->userSession,
			$this->logger
		);

	}//end placement()

	/**
	 * Log in a clerk with uid `clerk`.
	 *
	 * @return void
	 */
	private function loggedIn(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('clerk');
		$this->userSession->method('getUser')->willReturn($user);

	}//end loggedIn()

	/**
	 * The record `case-1` in `filinq`/`case` carries the given payload.
	 *
	 * @param mixed $record The record as OpenRegister returns it.
	 *
	 * @return void
	 */
	private function recordIs(mixed $record): void {
		$this->objectService->expects($this->once())
			->method('find')
			->with('case-1', 'filinq', 'case')
			->willReturn($record);

	}//end recordIs()

	/**
	 * A file node named `scan.pdf` that expects to move to the given destination.
	 *
	 * @param string $destination The expected destination.
	 *
	 * @return Node&MockObject
	 */
	private function fileMovingTo(string $destination): Node&MockObject {
		$node = $this->createMock(Node::class);
		$node->method('getName')->willReturn('scan.pdf');
		$node->expects($this->once())->method('move')->with($destination)->willReturn($node);

		return $node;

	}//end fileMovingTo()

	/**
	 * The file moves into the record's `@self.folder`, trimmed of slashes.
	 *
	 * @return void
	 */
	public function testMovesTheFileIntoTheRecordsSelfFolder(): void {
		$this->loggedIn();
		$this->recordIs(['@self' => ['folder' => '/Zaken/Z-1/ ']]);

		$node = $this->fileMovingTo('/clerk/files/Zaken/Z-1/scan.pdf');
		$this->rootFolder->expects($this->once())->method('getUserFolder')->with('clerk')->willReturn($this->userFolder);
		$this->userFolder->expects($this->once())->method('getById')->with(42)->willReturn([$node]);
		$this->userFolder->expects($this->once())->method('nodeExists')->with('Zaken/Z-1')->willReturn(true);
		$this->userFolder->method('getPath')->willReturn('/clerk/files');

		$this->assertSame('/clerk/files/Zaken/Z-1/scan.pdf', $this->placement()->place(42, 'filinq', 'case', 'case-1'));

	}//end testMovesTheFileIntoTheRecordsSelfFolder()

	/**
	 * A legacy record's plain `folder`, read through jsonSerialize, is honoured too.
	 *
	 * @return void
	 */
	public function testReadsALegacyPlainFolderFromASerialisableRecord(): void {
		$this->loggedIn();
		$record = new class () {
			/**
			 * The legacy payload.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['folder' => 'Archief'];
			}//end jsonSerialize()
		};
		$this->recordIs($record);

		$node = $this->fileMovingTo('/clerk/files/Archief/scan.pdf');
		$this->rootFolder->method('getUserFolder')->willReturn($this->userFolder);
		$this->userFolder->method('getById')->willReturn([$node]);
		$this->userFolder->method('nodeExists')->willReturn(true);
		$this->userFolder->method('getPath')->willReturn('/clerk/files');

		$this->assertSame('/clerk/files/Archief/scan.pdf', $this->placement()->place(7, 'filinq', 'case', 'case-1'));

	}//end testReadsALegacyPlainFolderFromASerialisableRecord()

	/**
	 * No file id means nothing to move; nobody is asked anything.
	 *
	 * @return void
	 */
	public function testNoFileMeansNothingMoves(): void {
		$this->userSession->expects($this->never())->method('getUser');
		$this->objectService->expects($this->never())->method('find');

		$this->assertNull($this->placement()->place(0, 'filinq', 'case', 'case-1'));

	}//end testNoFileMeansNothingMoves()

	/**
	 * Without a session user nothing moves.
	 *
	 * @return void
	 */
	public function testNoSessionUserMeansNothingMoves(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->objectService->expects($this->never())->method('find');

		$this->assertNull($this->placement()->place(5, 'filinq', 'case', 'case-1'));

	}//end testNoSessionUserMeansNothingMoves()

	/**
	 * A record that cannot be read is logged and nothing moves.
	 *
	 * @return void
	 */
	public function testAnUnreadableRecordIsLoggedAndNothingMoves(): void {
		$this->loggedIn();
		$this->rootFolder->expects($this->never())->method('getUserFolder');
		$this->logger->expects($this->once())
			->method('warning')
			->with(
				'[IntakeFilePlacement] could not read the target record',
				$this->callback(
					static fn (array $context): bool => $context['id'] === 'case-1'
						&& $context['error'] === 'OpenRegister service is not available.'
				)
			);

		$this->assertNull($this->placement(openRegister: false)->place(5, 'filinq', 'case', 'case-1'));

	}//end testAnUnreadableRecordIsLoggedAndNothingMoves()

	/**
	 * A record that does not exist means nothing moves.
	 *
	 * @return void
	 */
	public function testAMissingRecordMeansNothingMoves(): void {
		$this->loggedIn();
		$this->recordIs(null);
		$this->rootFolder->expects($this->never())->method('getUserFolder');

		$this->assertNull($this->placement()->place(5, 'filinq', 'case', 'case-1'));

	}//end testAMissingRecordMeansNothingMoves()

	/**
	 * Records that name no folder (empty, slashes only, non-string, not an array) leave the file.
	 *
	 * @return void
	 */
	public function testRecordsWithoutAFolderLeaveTheFile(): void {
		$this->loggedIn();
		$this->objectService->expects($this->exactly(4))
			->method('find')
			->willReturnOnConsecutiveCalls(
				['@self' => ['folder' => ' / ']],
				['folder' => 12],
				'not-a-record',
				[]
			);
		$this->rootFolder->expects($this->never())->method('getUserFolder');

		$placement = $this->placement();
		for ($i = 0; $i < 4; $i++) {
			$this->assertNull($placement->place(5, 'filinq', 'case', 'case-1'));
		}

	}//end testRecordsWithoutAFolderLeaveTheFile()

	/**
	 * A file id the clerk cannot see leaves nothing to move.
	 *
	 * @return void
	 */
	public function testAnUnknownFileIdMovesNothing(): void {
		$this->loggedIn();
		$this->recordIs(['@self' => ['folder' => 'Zaken']]);
		$this->rootFolder->method('getUserFolder')->willReturn($this->userFolder);
		$this->userFolder->expects($this->once())->method('getById')->with(9)->willReturn([]);
		$this->userFolder->expects($this->never())->method('nodeExists');

		$this->assertNull($this->placement()->place(9, 'filinq', 'case', 'case-1'));

	}//end testAnUnknownFileIdMovesNothing()

	/**
	 * A folder the clerk cannot see is logged at info and the file stays put.
	 *
	 * @return void
	 */
	public function testAnInvisibleFolderIsLoggedAndTheFileStays(): void {
		$this->loggedIn();
		$this->recordIs(['@self' => ['folder' => 'Geheim']]);

		$node = $this->createMock(Node::class);
		$node->expects($this->never())->method('move');
		$this->rootFolder->method('getUserFolder')->willReturn($this->userFolder);
		$this->userFolder->method('getById')->willReturn([$node]);
		$this->userFolder->expects($this->once())->method('nodeExists')->with('Geheim')->willReturn(false);
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'[IntakeFilePlacement] the record has a folder the clerk cannot see, leaving the file where it is',
				$this->callback(static fn (array $context): bool => $context['folder'] === 'Geheim' && $context['fileId'] === 3)
			);

		$this->assertNull($this->placement()->place(3, 'filinq', 'case', 'case-1'));

	}//end testAnInvisibleFolderIsLoggedAndTheFileStays()

	/**
	 * A failing move is logged as a warning and answered with null.
	 *
	 * @return void
	 */
	public function testAFailingMoveIsLoggedAndAnsweredWithNull(): void {
		$this->loggedIn();
		$this->recordIs(['@self' => ['folder' => 'Zaken']]);

		$node = $this->createMock(Node::class);
		$node->method('getName')->willReturn('scan.pdf');
		$node->method('move')->willThrowException(new RuntimeException('locked'));
		$this->rootFolder->method('getUserFolder')->willReturn($this->userFolder);
		$this->userFolder->method('getById')->willReturn([$node]);
		$this->userFolder->method('nodeExists')->willReturn(true);
		$this->userFolder->method('getPath')->willReturn('/clerk/files');
		$this->logger->expects($this->once())
			->method('warning')
			->with(
				'[IntakeFilePlacement] could not move the assigned file, the assignment itself stands',
				$this->callback(static fn (array $context): bool => $context['fileId'] === 4 && $context['error'] === 'locked')
			);

		$this->assertNull($this->placement()->place(4, 'filinq', 'case', 'case-1'));

	}//end testAFailingMoveIsLoggedAndAnsweredWithNull()
}//end class

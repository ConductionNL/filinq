<?php

/**
 * Unit tests for attaching staged bridge documents to a dossier
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/zgw-document-bridge
 */

declare(strict_types=1);

// phpcs:disable PEAR.Commenting.FunctionComment.Missing -- arrange/act/assert PHPUnit conventions.

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Service\BridgeDossierPickUp;
use OCA\Filinq\Service\BridgeService;
use OCA\Filinq\Service\DossierFileService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The scenario of REQ-DDZGW-006: two staged documents of ZAAK-2026-0042 into "Woo-verzoek 2026-021".
 *
 * @covers \OCA\Filinq\Service\BridgeDossierPickUp
 */
final class BridgeDossierPickUpTest extends TestCase {

	/**
	 * The external documents by uuid.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $documents = [];

	/**
	 * The staged files by file id.
	 *
	 * @var array<int, File&MockObject>
	 */
	private array $files = [];

	/**
	 * The paths copied to, in order.
	 *
	 * @var list<string>
	 */
	private array $copies = [];

	/**
	 * The documents recorded as picked up: uuid => dossierRef.
	 *
	 * @var array<string, string>
	 */
	private array $pickedUp = [];

	/**
	 * Dossier copy file id recorded per document uuid.
	 *
	 * @var array<string, int>
	 */
	private array $pickedUpCopies = [];

	private BridgeService&MockObject $bridge;

	private DossierFileService&MockObject $dossiers;

	protected function setUp(): void {
		$this->documents = [
			'doc-1' => ['uuid' => 'doc-1', 'processingStatus' => 'staged', 'stagedFileRef' => 101, 'filename' => 'besluit.pdf'],
			'doc-2' => ['uuid' => 'doc-2', 'processingStatus' => 'staged', 'stagedFileRef' => 102, 'filename' => ''],
			'doc-3' => ['uuid' => 'doc-3', 'processingStatus' => 'in_processing', 'stagedFileRef' => 103, 'filename' => 'x.pdf'],
			'doc-4' => ['uuid' => 'doc-4', 'processingStatus' => 'staged', 'stagedFileRef' => 999, 'filename' => 'gone.pdf'],
		];
		foreach ([101 => 'besluit.pdf', 102 => 'brief.pdf', 103 => 'x.pdf'] as $id => $name) {
			$file = $this->createMock(File::class);
			$file->method('getName')->willReturn($name);
			$file->method('copy')->willReturnCallback(function (string $path) use ($id): File {
				$this->copies[] = $path;
				$copy = $this->createMock(File::class);
				$copy->method('getId')->willReturn(($id + 1000));
				return $copy;
			});
			$this->files[$id] = $file;
		}

		$this->bridge = $this->createMock(BridgeService::class);
		$this->bridge->method('find')->willReturnCallback(function (string $id): array {
			if (isset($this->documents[$id]) === false) {
				throw new RuntimeException('External document not found.', 404);
			}

			return $this->documents[$id];
		});
		$this->bridge->method('pickedUp')->willReturnCallback(function (array $document, string $dossierRef, int $dossierFileRef = 0): array {
			$this->pickedUp[(string)$document['uuid']] = $dossierRef;
			$this->pickedUpCopies[(string)$document['uuid']] = $dossierFileRef;
			return array_merge($document, ['processingStatus' => 'in_processing', 'dossierRef' => $dossierRef]);
		});

		$this->dossiers = $this->createMock(DossierFileService::class);
	}//end setUp()

	private function pickUp(?Folder $folder = null, ?LoggerInterface $logger = null): BridgeDossierPickUp {
		if ($folder === null) {
			$folder = $this->createMock(Folder::class);
			$folder->method('getPath')->willReturn('/operator/files/Woo-verzoek 2026-021');
			$folder->method('getNonExistingName')->willReturnCallback(static fn (string $name): string => $name === 'besluit.pdf' ? 'besluit (2).pdf' : $name);
		}

		$this->dossiers->method('folderOfDossier')->with('dossier-21')->willReturn($folder);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturnCallback(function (int $id): array {
			if ($id === 500) {
				throw new RuntimeException('storage unavailable');
			}

			return isset($this->files[$id]) === true ? [$this->files[$id]] : [];
		});
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('operator')->willReturn($userFolder);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('operator');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new BridgeDossierPickUp(
			bridge: $this->bridge,
			dossiers: $this->dossiers,
			rootFolder: $root,
			session: $session,
			logger: ($logger ?? $this->createMock(LoggerInterface::class))
		);
	}//end pickUp()

	public function testBothStagedDocumentsAreCopiedIntoTheDossierFolderAndRecorded(): void {
		$attached = $this->pickUp()->attach(dossierUuid: 'dossier-21', documentIds: ['doc-1', 'doc-2', 'doc-1']);

		$this->assertSame(
			['/operator/files/Woo-verzoek 2026-021/besluit (2).pdf', '/operator/files/Woo-verzoek 2026-021/brief.pdf'],
			$this->copies
		);
		$this->assertSame(['doc-1' => 'dossier-21', 'doc-2' => 'dossier-21'], $this->pickedUp);
		$this->assertSame(['in_processing', 'in_processing'], array_column($attached, 'processingStatus'));
		$this->assertSame([1101, 1102], array_column($attached, 'copiedFileId'));
		$this->assertSame(expected: ['doc-1' => 1101, 'doc-2' => 1102], actual: $this->pickedUpCopies);
	}//end testBothStagedDocumentsAreCopiedIntoTheDossierFolderAndRecorded()

	public function testADocumentThatIsNotStagedRefusesThePickUpBeforeAnythingIsCopied(): void {
		try {
			$this->pickUp()->attach(dossierUuid: 'dossier-21', documentIds: ['doc-1', 'doc-3']);
			$this->fail('A document already in processing was attached.');
		} catch (InvalidArgumentException $e) {
			$this->assertSame('Document doc-3 is not staged, so it cannot be attached.', $e->getMessage());
		}

		$this->assertSame([], $this->copies);
		$this->assertSame([], $this->pickedUp);
	}//end testADocumentThatIsNotStagedRefusesThePickUpBeforeAnythingIsCopied()

	public function testAStagedFileTheOperatorCannotReadRefusesThePickUp(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The staged file of document doc-4 cannot be read.');

		try {
			$this->pickUp()->attach(dossierUuid: 'dossier-21', documentIds: ['doc-1', 'doc-4']);
		} finally {
			$this->assertSame([], $this->copies);
		}
	}//end testAStagedFileTheOperatorCannotReadRefusesThePickUp()

	public function testADossierWithoutAFolderIsNotFound(): void {
		$this->dossiers->method('folderOfDossier')->willThrowException(new RuntimeException('no dossier'));
		$pickUp = new BridgeDossierPickUp(
			bridge: $this->bridge,
			dossiers: $this->dossiers,
			rootFolder: $this->createMock(IRootFolder::class),
			session: $this->createMock(IUserSession::class),
			logger: $this->createMock(LoggerInterface::class)
		);

		try {
			$pickUp->attach(dossierUuid: 'missing', documentIds: ['doc-1']);
			$this->fail('A missing dossier was accepted.');
		} catch (RuntimeException $e) {
			$this->assertSame(404, $e->getCode());
		}

		$this->assertSame([], $this->pickedUp);
	}//end testADossierWithoutAFolderIsNotFound()

	public function testNamingNoDocumentIsRefused(): void {
		$this->expectException(InvalidArgumentException::class);

		$this->pickUp()->attach(dossierUuid: 'dossier-21', documentIds: ['', ' ']);
	}//end testNamingNoDocumentIsRefused()

	/**
	 * A file lookup that throws counts as a file the operator cannot read: nothing is copied.
	 *
	 * @return void
	 */
	public function testAThrowingFileLookupRefusesThePickUp(): void {
		$this->documents['doc-5'] = ['uuid' => 'doc-5', 'processingStatus' => 'staged', 'stagedFileRef' => 500, 'filename' => 'y.pdf'];

		try {
			$this->pickUp()->attach(dossierUuid: 'dossier-21', documentIds: ['doc-5']);
			$this->fail('A file whose lookup threw was picked up.');
		} catch (InvalidArgumentException $e) {
			$this->assertSame('The staged file of document doc-5 cannot be read.', $e->getMessage());
		}

		$this->assertSame([], $this->copies);
		$this->assertSame([], $this->pickedUp);
	}//end testAThrowingFileLookupRefusesThePickUp()

	/**
	 * A copy whose recording fails is logged with the copy's id, and the failure reaches the caller.
	 *
	 * @return void
	 */
	public function testACopyThatCannotBeRecordedIsLoggedAndRethrown(): void {
		$bridge = $this->createMock(BridgeService::class);
		$bridge->method('find')->willReturnCallback(fn (string $id): array => $this->documents[$id]);
		$bridge->method('pickedUp')->willThrowException(new InvalidArgumentException('A processed document cannot be moved to in_processing.'));
		$this->bridge = $bridge;

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			'[BridgeDossierPickUp] copied but not recorded',
			['document' => 'doc-1', 'copy' => 1101, 'exception' => 'A processed document cannot be moved to in_processing.']
		);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('A processed document cannot be moved to in_processing.');
		try {
			$this->pickUp(logger: $logger)->attach(dossierUuid: 'dossier-21', documentIds: ['doc-1', 'doc-2']);
		} finally {
			$this->assertSame(['/operator/files/Woo-verzoek 2026-021/besluit (2).pdf'], $this->copies);
		}
	}//end testACopyThatCannotBeRecordedIsLoggedAndRethrown()

	/**
	 * The pick-up copies the staged original and never writes to it, so its content hash stays what the bridge recorded.
	 *
	 * @return void
	 */
	public function testTheStagedOriginalIsOnlyCopiedNeverWritten(): void {
		foreach ($this->files as $file) {
			$file->expects($this->never())->method('putContent');
			$file->expects($this->never())->method('delete');
			$file->expects($this->never())->method('move');
		}

		$this->pickUp()->attach(dossierUuid: 'dossier-21', documentIds: ['doc-1', 'doc-2']);

		$this->assertCount(2, $this->copies);
	}//end testTheStagedOriginalIsOnlyCopiedNeverWritten()

}//end class

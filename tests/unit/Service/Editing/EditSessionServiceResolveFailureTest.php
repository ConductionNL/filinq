<?php

/**
 * Unit tests for EditSessionService when the user's folder cannot be opened.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\AgentArtefactMarker;
use OCA\Filinq\Service\Editing\DocumentCodecs;
use OCA\Filinq\Service\Editing\DocumentGuard;
use OCA\Filinq\Service\Editing\EditSessionService;
use OCA\Filinq\Service\Editing\GuardedWriter;
use OCA\Filinq\Service\Editing\PackageCodec;
use OCA\Filinq\Service\Editing\PackagePartIo;
use OCA\Filinq\Service\Editing\PresentationCodec;
use OCA\Filinq\Service\Editing\SpreadsheetCodec;
use OCP\Files\IRootFolder;
use OCP\Files\Lock\ILockManager;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A storage failure while resolving the file is reported with the file id and the cause.
 *
 * @covers \OCA\Filinq\Service\Editing\EditSessionService
 * @uses   \OCA\Filinq\Service\Editing\AgentFileRequest
 * @uses   \OCA\Filinq\Service\Editing\DocumentCodecs
 * @uses   \OCA\Filinq\Service\Editing\GuardedWriter
 * @uses   \OCA\Filinq\Service\Editing\PackageCodec
 * @uses   \OCA\Filinq\Service\Editing\PackagePartIo
 * @uses   \OCA\Filinq\Service\Editing\PresentationCodec
 * @uses   \OCA\Filinq\Service\Editing\SpreadsheetCodec
 */
class EditSessionServiceResolveFailureTest extends TestCase {

	/**
	 * The storage exception is wrapped, keeping it as the previous exception.
	 *
	 * @return void
	 */
	public function testAnUnopenableUserFolderIsWrapped(): void {
		$cause = new NotPermittedException('storage offline');
		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->expects($this->once())->method('getUserFolder')->with('alice')->willThrowException($cause);

		$service = new EditSessionService(
			$rootFolder,
			new DocumentCodecs(new PackageCodec(), new SpreadsheetCodec(new PackagePartIo()), new PresentationCodec(new PackagePartIo())),
			new GuardedWriter(
				$this->createMock(ILockManager::class),
				$this->createMock(AgentArtefactMarker::class),
				$this->createMock(LoggerInterface::class),
				$rootFolder
			),
			$this->createMock(DocumentGuard::class),
			$this->createMock(IAppConfig::class)
		);

		try {
			$service->readMetadataForAgent(uid: 'alice', fileId: 42);
			$this->fail('An unopenable folder must be refused.');
		} catch (RuntimeException $e) {
			$this->assertSame('Could not open file 42: storage offline', $e->getMessage());
			$this->assertSame($cause, $e->getPrevious());
		}
	}//end testAnUnopenableUserFolderIsWrapped()
}//end class

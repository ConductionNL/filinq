<?php

/**
 * Unit tests for BridgeSourceBadges (zgw-document-bridge REQ-DDZGW-009).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BridgeRecordReader;
use OCA\Filinq\Service\BridgeSourceBadges;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\SessionUserFile;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Files the caller can open get the vendor of their bridge source, in two
 * queries per page; guessed file ids and files without a record get nothing.
 *
 * @covers \OCA\Filinq\Service\BridgeSourceBadges
 * @uses   \OCA\Filinq\Service\SessionUserFile
 * @uses   \OCA\Filinq\Service\BridgeRecordReader
 * @uses   \OCA\Filinq\Service\StoredRecordFields
 */
class BridgeSourceBadgesTest extends TestCase {

	/**
	 * The object service double.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The badges over a caller who can open files 10, 11 and 12.
	 *
	 * @return BridgeSourceBadges
	 */
	private function badges(): BridgeSourceBadges {
		$this->objects = $this->createMock(ObjectService::class);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);

		// SessionUserFile is final: a real one over the caller's own folder.
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('clerk');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(fn (int $id): array => in_array($id, [10, 11, 12], true) ? [$this->createMock(File::class)] : []);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('clerk')->willReturn($folder);

		return new BridgeSourceBadges(
			bridge: new BridgeRecordReader(objectResolver: $resolver),
			files: new SessionUserFile(rootFolder: $root, userSession: $session)
		);
	}//end badges()

	/**
	 * Each readable bridge file gets its source's vendor, from one record query and one source query, as the server.
	 *
	 * @return void
	 */
	public function testReadableBridgeFilesGetTheirVendor(): void {
		$badges = $this->badges();
		$calls  = [];
		$this->objects->expects($this->exactly(2))->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug, array $filters, bool $_rbac) use (&$calls): array {
				$calls[] = [$registerSlug, $schemaSlug, $filters, $_rbac];
				return match ($schemaSlug) {
					'externalDocument' => [
						['object' => ['stagedFileRef' => 10, 'sourceId' => 's-1'], '@self' => ['uuid' => 'd-1']],
						['stagedFileRef' => 11, 'sourceId' => 's-2', 'uuid' => 'd-2'],
						['stagedFileRef' => 99, 'sourceId' => 's-1', 'uuid' => 'd-3'],
					],
					'bridgeSource' => [
						['uuid' => 's-1', 'vendor' => 'zaaksysteem.nl'],
						['uuid' => 's-2', 'vendor' => ''],
					],
				};
			}
		);

		$this->assertSame([10 => 'zaaksysteem.nl'], $badges->forFiles(fileIds: ['10', 11, 12, 10, 404, 0, 'x']));
		$this->assertSame(
			[
				['filinq', 'externalDocument', ['stagedFileRef' => [10, 11, 12]], false],
				['filinq', 'bridgeSource', [], false],
			],
			$calls
		);
	}//end testReadableBridgeFilesGetTheirVendor()

	/**
	 * No readable file, or no bridge record, answers nothing and skips the source query.
	 *
	 * @return void
	 */
	public function testNothingToBadgeAsksNoMoreThanNeeded(): void {
		$badges = $this->badges();
		$this->objects->expects($this->never())->method('searchObjectsBySlug');
		$this->assertSame([], $badges->forFiles(fileIds: [404, 405]));

		$badges = $this->badges();
		$this->objects->expects($this->once())->method('searchObjectsBySlug')->willReturn(0);
		$this->assertSame([], $badges->forFiles(fileIds: [10]));
	}//end testNothingToBadgeAsksNoMoreThanNeeded()

	/**
	 * A listing of more than MAX_FILES ids is cut to the first MAX_FILES.
	 *
	 * @return void
	 */
	public function testALongListIsCut(): void {
		$badges = $this->badges();
		$this->objects->expects($this->never())->method('searchObjectsBySlug');

		$this->assertSame([], $badges->forFiles(fileIds: array_merge(range(1000, 1000 + BridgeSourceBadges::MAX_FILES - 1), [10])));
	}//end testALongListIsCut()
}//end class

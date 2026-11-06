<?php

/**
 * Group access to a domain folder, through Nextcloud group shares.
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
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\NextcloudDomainFolderGateway;
use OCP\Constants;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for NextcloudDomainFolderGateway.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class NextcloudDomainFolderGatewayTest extends TestCase {

	/**
	 * The folder node.
	 *
	 * @var Folder&MockObject
	 */
	private Folder&MockObject $folder;

	/**
	 * The share manager.
	 *
	 * @var IManager&MockObject
	 */
	private IManager&MockObject $shares;

	/**
	 * The gateway under test.
	 *
	 * @var NextcloudDomainFolderGateway
	 */
	private NextcloudDomainFolderGateway $gateway;

	/**
	 * Wire the folder at Filinq/d1 in alice's storage.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->folder = $this->createMock(Folder::class);
		$userFolder   = $this->createMock(Folder::class);
		$userFolder->method('get')->with('Filinq/d1')->willReturn($this->folder);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('alice')->willReturn($userFolder);
		$this->shares  = $this->createMock(IManager::class);
		$this->gateway = new NextcloudDomainFolderGateway(rootFolder: $root, shareManager: $this->shares);

	}//end setUp()

	/**
	 * A group share on the folder.
	 *
	 * @param string $group The group.
	 *
	 * @return IShare&MockObject The share.
	 */
	private function share(string $group): IShare&MockObject {
		$share = $this->createMock(IShare::class);
		$share->method('getSharedWith')->willReturn($group);

		return $share;

	}//end share()

	/**
	 * The groups with access are the group shares on the folder, once each.
	 *
	 * @return void
	 */
	public function testGroupsWithAccessReadsTheGroupShares(): void {
		$this->shares->expects($this->once())
			->method('getSharesBy')
			->with('alice', IShare::TYPE_GROUP, $this->folder, true, -1)
			->willReturn([$this->share('a'), $this->share('b'), $this->share('a')]);

		$this->assertSame(['a', 'b'], $this->gateway->groupsWithAccess(path: 'Filinq/d1', owner: 'alice'));

	}//end testGroupsWithAccessReadsTheGroupShares()

	/**
	 * Granting creates a group share owned by the folder's owner.
	 *
	 * @return void
	 */
	public function testGrantCreatesAGroupShare(): void {
		$share = $this->createMock(IShare::class);
		foreach (['setNode', 'setShareType', 'setSharedWith', 'setSharedBy', 'setShareOwner', 'setPermissions'] as $setter) {
			$share->method($setter)->willReturnSelf();
		}

		$share->expects($this->once())->method('setNode')->with($this->folder);
		$share->expects($this->once())->method('setShareType')->with(IShare::TYPE_GROUP);
		$share->expects($this->once())->method('setSharedWith')->with('readers');
		$share->expects($this->once())->method('setShareOwner')->with('alice');
		$share->expects($this->once())->method('setPermissions')->with(Constants::PERMISSION_ALL);
		$this->shares->method('newShare')->willReturn($share);
		$this->shares->expects($this->once())->method('createShare')->with($share)->willReturn($share);

		$this->gateway->grant(path: 'Filinq/d1', owner: 'alice', group: 'readers');

	}//end testGrantCreatesAGroupShare()

	/**
	 * Revoking deletes only that group's shares.
	 *
	 * @return void
	 */
	public function testRevokeDeletesOnlyThatGroupsShares(): void {
		$keep = $this->share('keep');
		$drop = $this->share('drop');
		$this->shares->method('getSharesBy')->willReturn([$keep, $drop]);
		$this->shares->expects($this->once())->method('deleteShare')->with($drop);

		$this->gateway->revoke(path: 'Filinq/d1', owner: 'alice', group: 'drop');

	}//end testRevokeDeletesOnlyThatGroupsShares()

	/**
	 * A missing folder is thrown, so the service reports it as refused.
	 *
	 * @return void
	 */
	public function testAMissingFolderIsThrown(): void {
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('get')->willThrowException(new NotFoundException('gone'));
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($userFolder);
		$gateway = new NextcloudDomainFolderGateway(rootFolder: $root, shareManager: $this->shares);

		$this->expectException(NotFoundException::class);
		$gateway->groupsWithAccess(path: 'Filinq/missing', owner: 'alice');

	}//end testAMissingFolderIsThrown()
}//end class

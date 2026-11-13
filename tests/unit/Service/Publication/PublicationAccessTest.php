<?php

/**
 * Unit tests for PublicationAccess
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Publication
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Publication;

use OCA\Filinq\Service\Publication\PublicationAccess;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IAppConfig;
use OCP\IGroupManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Seeing a document needs it in the user's own files (or admin); acting on its
 * publication also needs membership of a configured publisher group.
 *
 * @covers \OCA\Filinq\Service\Publication\PublicationAccess
 */
class PublicationAccessTest extends TestCase {

	/**
	 * The root folder.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * The group manager.
	 *
	 * @var IGroupManager&MockObject
	 */
	private IGroupManager&MockObject $groups;

	/**
	 * The app config.
	 *
	 * @var IAppConfig&MockObject
	 */
	private IAppConfig&MockObject $appConfig;

	/**
	 * Set up: alice sees file 5, bob's storage is broken, nobody is admin.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->rootFolder->method('getUserFolder')->willReturnCallback(function (string $uid): Folder {
			if ($uid === 'bob') {
				throw new RuntimeException('no storage');
			}

			$folder = $this->createMock(Folder::class);
			$folder->method('getById')->willReturnCallback(
				fn (int $id): array => ($uid === 'alice' && $id === 5) ? [$this->createMock(Node::class)] : []
			);

			return $folder;
		});

		$this->groups    = $this->createMock(IGroupManager::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

	}//end setUp()

	/**
	 * The access check under test, with the given publisher group config.
	 *
	 * @param string $groupsJson The stored publisher groups.
	 *
	 * @return PublicationAccess
	 */
	private function access(string $groupsJson = '["filinq-publishers"]'): PublicationAccess {
		$this->appConfig->method('getValueString')
			->with('filinq', 'publication_publisher_groups', '["filinq-publishers"]')
			->willReturn($groupsJson);

		return new PublicationAccess($this->rootFolder, $this->groups, $this->appConfig);

	}//end access()

	/**
	 * Seeing follows the user's own files; no user, or broken storage, sees nothing.
	 *
	 * @return void
	 */
	public function testMaySeeFollowsTheUsersFiles(): void {
		$this->groups->method('isAdmin')->willReturn(false);
		$access = $this->access();

		$this->assertTrue($access->maySee('alice', '5'));
		$this->assertFalse($access->maySee('alice', '6'));
		$this->assertFalse($access->maySee('bob', '5'));
		$this->assertFalse($access->maySee('', '5'));

	}//end testMaySeeFollowsTheUsersFiles()

	/**
	 * An admin sees and acts on everything without a file lookup.
	 *
	 * @return void
	 */
	public function testAdminsSeeAndActOnEverything(): void {
		$this->groups->method('isAdmin')->with('root')->willReturn(true);
		$this->rootFolder->expects($this->never())->method('getUserFolder');
		$access = $this->access();

		$this->assertTrue($access->maySee('root', '99'));
		$this->assertTrue($access->mayAct('root', '99'));

	}//end testAdminsSeeAndActOnEverything()

	/**
	 * Acting needs a publisher group and sight of the file.
	 *
	 * @return void
	 */
	public function testMayActNeedsPublisherGroupAndSight(): void {
		$this->groups->method('isAdmin')->willReturn(false);
		$this->groups->method('isInGroup')->willReturnCallback(
			static fn (string $uid, string $group): bool => $uid === 'alice' && $group === 'woo-team'
		);
		$access = $this->access('["", 7, "filinq-publishers", "woo-team"]');

		$this->assertTrue($access->mayAct('alice', '5'));
		$this->assertFalse($access->mayAct('alice', '6'));
		$this->assertFalse($access->mayAct('carol', '5'));
		$this->assertFalse($access->mayAct('', '5'));

	}//end testMayActNeedsPublisherGroupAndSight()

	/**
	 * A publisher group setting that is not a JSON list grants nobody.
	 *
	 * @return void
	 */
	public function testAnUnreadableGroupSettingGrantsNobody(): void {
		$this->groups->method('isAdmin')->willReturn(false);
		$this->groups->expects($this->never())->method('isInGroup');

		$this->assertFalse($this->access('not json')->mayAct('alice', '5'));

	}//end testAnUnreadableGroupSettingGrantsNobody()
}//end class

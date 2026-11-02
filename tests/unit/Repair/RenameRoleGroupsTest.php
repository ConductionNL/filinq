<?php

/**
 * Unit tests for RenameRoleGroups.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude Tests for a one-off data migration.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Repair;

use OCA\Filinq\Repair\RenameRoleGroups;
use OCP\IAppConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;

/**
 * The role group rename copies members, rewrites settings and deletes nothing.
 */
class RenameRoleGroupsTest extends TestCase {

	/**
	 * Members per group id, as the fake group manager holds them.
	 *
	 * @var array<string, list<string>>
	 */
	private array $members = [];

	/**
	 * App settings, as the fake app config holds them.
	 *
	 * @var array<string, string>
	 */
	private array $settings = [];

	/**
	 * Group ids the step created.
	 *
	 * @var list<string>
	 */
	private array $created = [];

	/**
	 * The step over in-memory groups and settings.
	 *
	 * @return RenameRoleGroups
	 */
	private function step(): RenameRoleGroups {
		$groups = $this->createMock(originalClassName: IGroupManager::class);
		$groups->method('get')->willReturnCallback(fn (string $gid): ?IGroup => isset($this->members[$gid]) ? $this->group(gid: $gid) : null);
		$groups->method('createGroup')->willReturnCallback(
			function (string $gid): IGroup {
				$this->created[] = $gid;
				$this->members[$gid] = [];
				return $this->group(gid: $gid);
			}
		);

		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('hasKey')->willReturnCallback(fn (string $app, string $key): bool => isset($this->settings[$key]));
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => ($this->settings[$key] ?? $default)
		);
		$config->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->settings[$key] = $value;
				return true;
			}
		);

		return new RenameRoleGroups(groupManager: $groups, appConfig: $config);

	}//end step()

	/**
	 * A group double backed by $this->members.
	 *
	 * @param string $gid The group id.
	 *
	 * @return IGroup
	 */
	private function group(string $gid): IGroup {
		$group = $this->createMock(originalClassName: IGroup::class);
		$group->method('getUsers')->willReturnCallback(
			fn (): array => array_map(
				function (string $uid): IUser {
					$user = $this->createMock(originalClassName: IUser::class);
					$user->method('getUID')->willReturn($uid);
					return $user;
				},
				$this->members[$gid]
			)
		);
		$group->method('inGroup')->willReturnCallback(fn (IUser $user): bool => in_array(needle: $user->getUID(), haystack: $this->members[$gid], strict: true));
		$group->method('addUser')->willReturnCallback(
			function (IUser $user) use ($gid): void {
				$this->members[$gid][] = $user->getUID();
			}
		);
		$group->expects($this->never())->method('removeUser');
		$group->expects($this->never())->method('delete');

		return $group;

	}//end group()

	/**
	 * Members move across, settings are rewritten, the old groups stay.
	 *
	 * @return void
	 */
	public function testMembersAreCopiedAndNothingIsDeleted(): void {
		$this->members = ['docudesk-policy-admins' => ['anna', 'bob'], 'filinq-policy-admins' => ['bob']];
		$this->settings = ['subject_erasure_groups' => '["docudesk-privacy-officer","docudesk-policy-admins","auditors"]'];

		$this->step()->run(output: $this->createMock(originalClassName: IOutput::class));

		$this->assertSame(expected: ['bob', 'anna'], actual: $this->members['filinq-policy-admins']);
		$this->assertSame(expected: ['anna', 'bob'], actual: $this->members['docudesk-policy-admins']);
		$this->assertContains(needle: 'filinq-signing-admins', haystack: $this->created);
		$this->assertNotContains(needle: 'filinq-policy-admins', haystack: $this->created);
		$this->assertSame(
			expected: ['filinq-privacy-officer', 'filinq-policy-admins', 'auditors'],
			actual: json_decode(json: $this->settings['subject_erasure_groups'], associative: true)
		);

	}//end testMembersAreCopiedAndNothingIsDeleted()

	/**
	 * The step runs once: a member taken out of a new group is not put back.
	 *
	 * @return void
	 */
	public function testItRunsOnce(): void {
		$this->members = ['docudesk-policy-admins' => ['anna']];
		$step = $this->step();
		$step->run(output: $this->createMock(originalClassName: IOutput::class));
		$this->members['filinq-policy-admins'] = [];

		$step->run(output: $this->createMock(originalClassName: IOutput::class));

		$this->assertSame(expected: [], actual: $this->members['filinq-policy-admins']);
		$this->assertSame(expected: '1', actual: $this->settings[RenameRoleGroups::DONE_KEY]);

	}//end testItRunsOnce()
}//end class

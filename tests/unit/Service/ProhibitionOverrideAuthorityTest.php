<?php

/**
 * Unit tests for ProhibitionOverrideAuthority.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\ProhibitionOverrideAuthority;
use OCP\IAppConfig;
use OCP\IGroupManager;
use PHPUnit\Framework\TestCase;

/**
 * Who may release a publish-never match.
 */
class ProhibitionOverrideAuthorityTest extends TestCase {

	/**
	 * The authority over a fixed setting and group membership.
	 *
	 * @param string|null        $setting  The raw app setting, or null for the default.
	 * @param array<int, string> $memberOf The user's groups.
	 * @param bool               $isAdmin  Whether the user is an admin.
	 *
	 * @return ProhibitionOverrideAuthority
	 */
	private function authority(?string $setting, array $memberOf, bool $isAdmin = false): ProhibitionOverrideAuthority {
		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($setting ?? $default)
		);
		$groups = $this->createMock(originalClassName: IGroupManager::class);
		$groups->method('isAdmin')->willReturn($isAdmin);
		$groups->method('isInGroup')->willReturnCallback(
			static fn (string $uid, string $group): bool => in_array(needle: $group, haystack: $memberOf, strict: true)
		);

		return new ProhibitionOverrideAuthority(appConfig: $config, groupManager: $groups);

	}//end authority()

	/**
	 * A policy admin may override by default; an ordinary editor may not.
	 *
	 * @return void
	 */
	public function testThePolicyAdminsOverrideByDefault(): void {
		$this->assertTrue(condition: $this->authority(setting: null, memberOf: ['docudesk-policy-admins'])->mayOverride(userId: 'anna'));
		$this->assertFalse(condition: $this->authority(setting: null, memberOf: ['staff'])->mayOverride(userId: 'bob'));
		$this->assertFalse(condition: $this->authority(setting: null, memberOf: [])->mayOverride(userId: ''));

	}//end testThePolicyAdminsOverrideByDefault()

	/**
	 * The setting names the groups, and a broken setting leaves admins only.
	 *
	 * @return void
	 */
	public function testTheSettingNamesTheGroupsAndABrokenOneLeavesAdminsOnly(): void {
		$this->assertTrue(condition: $this->authority(setting: '["woo-team"]', memberOf: ['woo-team'])->mayOverride(userId: 'anna'));
		$this->assertFalse(condition: $this->authority(setting: 'not json', memberOf: ['docudesk-policy-admins'])->mayOverride(userId: 'anna'));
		$this->assertTrue(condition: $this->authority(setting: 'not json', memberOf: [], isAdmin: true)->mayOverride(userId: 'admin'));

	}//end testTheSettingNamesTheGroupsAndABrokenOneLeavesAdminsOnly()
}//end class

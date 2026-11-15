<?php

/**
 * Who EntitySearchGate lets search.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\EntitySearch
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\EntitySearch;

use OCA\Filinq\Exception\EntitySearchRefusedException;
use OCA\Filinq\Service\EntitySearch\EntitySearchGate;
use OCP\IAppConfig;
use OCP\IGroupManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Admins and configured group members search; an unreadable configuration
 * refuses everyone, admins included.
 *
 * @covers \OCA\Filinq\Service\EntitySearch\EntitySearchGate
 * @uses   \OCA\Filinq\Exception\EntitySearchRefusedException
 * @uses   \OCA\Filinq\Service\AdmitsConfiguredGroups
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EntitySearchGateTest extends TestCase {

	/**
	 * The (key, default) pairs the configuration was read with.
	 *
	 * @var array<int, array<int, string>>
	 */
	private array $reads = [];

	/**
	 * A gate over a stored configuration value and group memberships.
	 *
	 * @param string|RuntimeException $stored The stored value, or what reading it throws.
	 * @param array<int, string>      $admins The admin users.
	 * @param array<string, string[]> $member Group => its users.
	 *
	 * @return EntitySearchGate The gate.
	 */
	private function gate(string|RuntimeException $stored, array $admins = [], array $member = []): EntitySearchGate {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($stored): string {
				$this->reads[] = [$app, $key, $default];
				if ($stored instanceof RuntimeException) {
					throw $stored;
				}

				return $stored;
			}
		);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturnCallback(static fn (string $userId): bool => in_array($userId, $admins, true));
		$groups->method('isInGroup')->willReturnCallback(static fn (string $userId, string $group): bool => in_array($userId, $member[$group] ?? [], true));

		return new EntitySearchGate($config, $groups);

	}//end gate()

	/**
	 * The refusal reason of a call, or null when it went through.
	 *
	 * @param callable $call The call.
	 *
	 * @return string|null The reason.
	 */
	private static function reason(callable $call): ?string {
		try {
			$call();
		} catch (EntitySearchRefusedException $e) {
			return $e->getReason();
		}

		return null;

	}//end reason()

	/**
	 * A member of a configured group searches; the groups are trimmed and the
	 * filinq key is read with an empty list as default.
	 *
	 * @return void
	 */
	public function testAMemberOfAConfiguredGroupMaySearch(): void {
		$gate = $this->gate(stored: '["  privacy  ", ""]', member: ['privacy' => ['petra']]);

		$this->assertNull(self::reason(static fn () => $gate->assertMaySearch(userId: 'petra')));
		$this->assertSame([['filinq', 'entity_search.allowed_groups', '[]']], $this->reads);
	}//end testAMemberOfAConfiguredGroupMaySearch()

	/**
	 * An admin searches without being in a group; with nothing configured
	 * only admins do.
	 *
	 * @return void
	 */
	public function testWithNoGroupsOnlyAdminsSearch(): void {
		$gate = $this->gate(stored: '[]', admins: ['root'], member: ['privacy' => ['petra']]);

		$this->assertNull(self::reason(static fn () => $gate->assertMaySearch(userId: 'root')));
		$this->assertSame(EntitySearchRefusedException::REASON_NOT_ALLOWED, self::reason(static fn () => $gate->assertMaySearch(userId: 'petra')));
	}//end testWithNoGroupsOnlyAdminsSearch()

	/**
	 * Someone in neither an admin nor a configured group is refused.
	 *
	 * @return void
	 */
	public function testANonMemberIsRefused(): void {
		$gate = $this->gate(stored: '["privacy"]', member: ['privacy' => ['petra'], 'other' => ['bob']]);

		try {
			$gate->assertMaySearch(userId: 'bob');
			$this->fail('A non-member was let through.');
		} catch (EntitySearchRefusedException $e) {
			$this->assertSame(EntitySearchRefusedException::REASON_NOT_ALLOWED, $e->getReason());
			$this->assertSame('This user is neither an admin nor in an entity search group.', $e->getMessage());
		}
	}//end testANonMemberIsRefused()

	/**
	 * An empty user id is refused before any membership is looked at.
	 *
	 * @return void
	 */
	public function testAnAnonymousCallerIsRefused(): void {
		$gate = $this->gate(stored: '["privacy"]', admins: [''], member: ['privacy' => ['']]);

		try {
			$gate->assertMaySearch(userId: '');
			$this->fail('An anonymous caller was let through.');
		} catch (EntitySearchRefusedException $e) {
			$this->assertSame(EntitySearchRefusedException::REASON_NOT_ALLOWED, $e->getReason());
			$this->assertSame('Only a signed-in user may search.', $e->getMessage());
		}
	}//end testAnAnonymousCallerIsRefused()

	/**
	 * Configuration that cannot be read or is not a list of strings refuses
	 * everyone, an admin included.
	 *
	 * @return void
	 */
	public function testUnreadableConfigurationRefusesEvenAnAdmin(): void {
		foreach ([new RuntimeException('db down'), 'not json', '{"a":"privacy"}', '"privacy"', '["privacy", 3]'] as $stored) {
			$gate = $this->gate(stored: $stored, admins: ['root'], member: ['privacy' => ['root']]);
			$this->assertSame(
				EntitySearchRefusedException::REASON_CONFIG_UNREADABLE,
				self::reason(static fn () => $gate->assertMaySearch(userId: 'root')),
				'Stored: ' . ($stored instanceof RuntimeException ? 'a throw' : $stored)
			);
		}
	}//end testUnreadableConfigurationRefusesEvenAnAdmin()

	/**
	 * Only a non-empty admin user id is an admin.
	 *
	 * @return void
	 */
	public function testIsAdmin(): void {
		$gate = $this->gate(stored: '[]', admins: ['root', '']);

		$this->assertTrue($gate->isAdmin(userId: 'root'));
		$this->assertFalse($gate->isAdmin(userId: 'petra'));
		$this->assertFalse($gate->isAdmin(userId: ''));
	}//end testIsAdmin()
}//end class

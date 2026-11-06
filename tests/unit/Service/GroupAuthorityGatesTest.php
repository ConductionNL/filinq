<?php

/**
 * Unit tests for the four gates that admit admins and members of configured groups.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\EntitySearch\EntitySearchGate;
use OCA\Filinq\Service\LegalHold\LegalHoldAuthority;
use OCA\Filinq\Service\Pseudonymisation\PseudonymRestoreGate;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureAuthority;
use OCP\IAppConfig;
use OCP\IGroupManager;
use PHPUnit\Framework\TestCase;

/**
 * Pins who each gate admits, for every shape of the group setting.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\EntitySearch\EntitySearchGate
 * @covers \OCA\Filinq\Service\LegalHold\LegalHoldAuthority
 * @covers \OCA\Filinq\Service\SubjectErasure\SubjectErasureAuthority
 * @covers \OCA\Filinq\Service\Pseudonymisation\PseudonymRestoreGate
 * @uses   \OCA\Filinq\Exception\EntitySearchRefusedException
 * @uses   \OCA\Filinq\Exception\LegalHoldRefusedException
 * @uses   \OCA\Filinq\Exception\SubjectErasureRefusedException
 * @uses   \OCA\Filinq\Exception\PseudonymRestoreRefusedException
 */
class GroupAuthorityGatesTest extends TestCase {

	/**
	 * Gate, assert method, config key, default the gate passes to the config.
	 *
	 * @return array<string, array{0: class-string, 1: string, 2: string, 3: string}>
	 */
	public static function gates(): array {
		return [
			'entity search' => [EntitySearchGate::class, 'assertMaySearch', 'entity_search.allowed_groups', '[]'],
			'legal hold' => [LegalHoldAuthority::class, 'assertAuthority', 'legal_hold_authority_groups', '[]'],
			'subject erasure' => [SubjectErasureAuthority::class, 'assertMayErase', 'subject_erasure_groups', '["filinq-privacy-officer","filinq-policy-admins"]'],
			'pseudonym restore' => [PseudonymRestoreGate::class, 'assertMayRestore', 'pseudonymisation_restore_allowed_groups', '[]'],
		];
	}

	/**
	 * Run one gate and describe the outcome as 'ok' or 'Class|reason|message'.
	 *
	 * @param array{0: class-string, 1: string, 2: string, 3: string} $gate The gate row.
	 * @param array{0: string, 1: string} $case Raw setting and user id.
	 *
	 * @return string The outcome.
	 */
	private function outcome(array $gate, array $case): string {
		[$class, $method, $key, $default] = $gate;
		[$raw, $userId] = $case;
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $k, string $d = ''): string => ($app === 'filinq' && $k === $key && $d === $default) ? $raw : 'UNEXPECTED'
		);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturnCallback(static fn (string $uid): bool => $uid === 'root');
		$groups->method('isInGroup')->willReturnCallback(static fn (string $uid, string $gid): bool => ($uid === 'ann' && $gid === 'clerks') || ($uid === 'jo' && $gid === 'clerks'));

		try {
			(new $class($config, $groups))->$method(userId: $userId);
		} catch (\Exception $e) {
			return substr(strrchr(get_class($e), '\\'), 1) . '|' . $e->getReason() . '|' . $e->getMessage();
		}
		return 'ok';
	}

	/**
	 * @dataProvider gates
	 *
	 * @param class-string $class The gate.
	 * @param string $method The assert method.
	 * @param string $key The config key.
	 * @param string $default The config default.
	 */
	public function testAdmitsAsBefore(string $class, string $method, string $key, string $default): void {
		$gate = [$class, $method, $key, $default];
		$cases = [
			'member' => ['["clerks"]', 'ann'],
			'member of the first of two groups' => ['["clerks", "judges"]', 'ann'],
			'member of the second of two groups' => ['["judges", "clerks"]', 'ann'],
			'second group member' => ['["judges", "clerks"]', 'jo'],
			'member, padded id and blank entry' => ['["  clerks  ", ""]', 'ann'],
			'member, id in other case' => ['["Clerks"]', 'ann'],
			'member, id with inner space' => ['["cl erks"]', 'ann'],
			'admin, no groups' => ['[]', 'root'],
			'admin, not a member' => ['["judges"]', 'root'],
			'admin, unreadable list' => ['{"a":1}', 'root'],
			'outsider' => ['["clerks"]', 'bob'],
			'member, empty list' => ['[]', 'ann'],
			'member, only blank entries' => ['["", "  "]', 'ann'],
			'member, empty setting' => ['', 'ann'],
			'no user' => ['["clerks"]', ''],
			'no user, unreadable list' => ['nope', ''],
			'not json' => ['nope', 'ann'],
			'object, not a list' => ['{"a":"clerks"}', 'ann'],
			'scalar' => ['"clerks"', 'ann'],
			'null' => ['null', 'ann'],
			'non-string entry' => ['["clerks", 3]', 'ann'],
			'nested list' => ['[["clerks"]]', 'ann'],
		];
		$seen = [];
		foreach ($cases as $name => $case) {
			$seen[$name] = $this->outcome(gate: $gate, case: $case);
		}

		$admitted = array_keys(array_filter($seen, static fn (string $outcome): bool => $outcome === 'ok'));
		$this->assertSame(
			['member', 'member of the first of two groups', 'member of the second of two groups', 'second group member', 'member, padded id and blank entry', 'admin, no groups', 'admin, not a member'],
			$admitted
		);
		$reasons = array_map(static fn (string $outcome): string => (explode('|', $outcome)[1] ?? $outcome), $seen);
		$denied = array_diff_key($reasons, array_flip($admitted));
		$this->assertSame(
			[
				'member, id in other case' => 'not_allowed',
				'member, id with inner space' => 'not_allowed',
				'admin, unreadable list' => 'config_unreadable',
				'outsider' => 'not_allowed',
				'member, empty list' => 'not_allowed',
				'member, only blank entries' => 'not_allowed',
				'member, empty setting' => 'config_unreadable',
				'no user' => 'not_allowed',
				'no user, unreadable list' => 'config_unreadable',
				'not json' => 'config_unreadable',
				'object, not a list' => 'config_unreadable',
				'scalar' => 'config_unreadable',
				'null' => 'config_unreadable',
				'non-string entry' => 'config_unreadable',
				'nested list' => 'config_unreadable',
			],
			$denied
		);
		$this->assertSame(self::PINS[$class], sha1(json_encode($seen)), json_encode($seen, JSON_PRETTY_PRINT));
	}

	/**
	 * sha1 of each gate's whole outcome table: exception class, reason and message per case.
	 */
	private const PINS = [
		EntitySearchGate::class => '6d9d44b0b6a51e43b42ddb95c273fcccb03256fc',
		LegalHoldAuthority::class => 'e0dd7306379087fe39fa48b7e9e7c160443586fa',
		SubjectErasureAuthority::class => 'f17ad4f023553400f3a5e8b191e5fe111d93f720',
		PseudonymRestoreGate::class => 'b2050b7b677ae2db4dd39865178e4b4e4ed209d5',
	];
}

<?php

/**
 * Unit tests for ActingUser.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Support
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://conduction.nl
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Support;

use OCA\Filinq\Service\Support\ActingUser;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Background work runs as the user who asked for it.
 *
 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-a-large-archive-drains-in-resumable-units-req-mai-004
 */
#[CoversClass(ActingUser::class)]
final class ActingUserTest extends TestCase {

	/**
	 * The session as a list of every active user it was given.
	 *
	 * @param array<int, IUser|null> $active Collects the users.
	 * @param IUser|null             $before The user active before.
	 *
	 * @return IUserSession
	 */
	private function session(array &$active, ?IUser $before): IUserSession {
		$session = $this->createMock(originalClassName: IUserSession::class);
		$session->method('getUser')->willReturn($before);
		$session->method('setVolatileActiveUser')->willReturnCallback(
			function (?IUser $user) use (&$active): void {
				$active[] = $user;
			}
		);

		return $session;
	}//end session()

	/**
	 * The work runs as the user and the user before is restored, also when the work throws.
	 *
	 * @return void
	 */
	public function testTheWorkRunsAsTheUserAndThePreviousOneIsRestored(): void {
		$anna   = $this->createMock(originalClassName: IUser::class);
		$before = $this->createMock(originalClassName: IUser::class);
		$users  = $this->createMock(originalClassName: IUserManager::class);
		$users->method('get')->willReturn($anna);
		$active = [];
		$acting = new ActingUser(users: $users, session: $this->session(active: $active, before: $before));

		$this->assertSame(expected: 'done', actual: $acting->run(userId: 'anna', operation: static fn (): string => 'done'));
		try {
			$acting->run(userId: 'anna', operation: static function (): void {
				throw new RuntimeException(message: 'register down');
			});
			$this->fail(message: 'The failure must reach the caller.');
		} catch (RuntimeException $e) {
			$this->assertSame(expected: 'register down', actual: $e->getMessage());
		}

		$this->assertSame(expected: [$anna, $before, $anna, $before], actual: $active);
	}//end testTheWorkRunsAsTheUserAndThePreviousOneIsRestored()

	/**
	 * Without a known user the work runs as it is, and the session is left alone.
	 *
	 * @return void
	 */
	public function testWithoutAKnownUserTheSessionIsLeftAlone(): void {
		$users = $this->createMock(originalClassName: IUserManager::class);
		$users->method('get')->willReturn(null);
		$active = [];
		$acting = new ActingUser(users: $users, session: $this->session(active: $active, before: null));

		$this->assertSame(expected: 1, actual: $acting->run(userId: 'gone', operation: static fn (): int => 1));
		$this->assertSame(expected: 2, actual: $acting->run(userId: '', operation: static fn (): int => 2));
		$this->assertSame(expected: [], actual: $active);
	}//end testWithoutAKnownUserTheSessionIsLeftAlone()
}//end class

<?php

/**
 * Unit tests for PolicyRuleScope
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

use OCA\Filinq\Service\PolicyRuleScope;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The organisation whose policy rules apply.
 *
 * @covers \OCA\Filinq\Service\PolicyRuleScope
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md#requirement-shared-nothing-isolation-across-all-filinq-object-families-req-ddmth-003
 */
class PolicyRuleScopeTest extends TestCase {

	/**
	 * The scope with a signed-in user (or none) and an organisation service answering $answer.
	 *
	 * @param bool  $signedIn Whether a user is signed in.
	 * @param mixed $answer   What getActiveOrganisation() returns, or a Throwable it throws.
	 *
	 * @return PolicyRuleScope
	 */
	private function scope(bool $signedIn, mixed $answer): PolicyRuleScope {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($signedIn === true ? $this->createMock(IUser::class) : null);

		$organisations = new class($answer) {
			/**
			 * @param mixed $answer What to answer.
			 */
			public function __construct(private readonly mixed $answer) {
			}

			/**
			 * @return mixed
			 */
			public function getActiveOrganisation(): mixed {
				if ($this->answer instanceof \Throwable) {
					throw $this->answer;
				}

				return $this->answer;
			}
		};
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\OpenRegister\Service\OrganisationService')->willReturn($organisations);

		return new PolicyRuleScope(userSession: $session, container: $container, logger: new NullLogger());
	}//end scope()

	/**
	 * A user's active organisation is the scope.
	 *
	 * @return void
	 */
	public function testAUsersActiveOrganisationIsTheScope(): void {
		$organisation = new class {
			/**
			 * @return string
			 */
			public function getUuid(): string {
				return ' org-a ';
			}
		};

		self::assertSame('org-a', $this->scope(signedIn: true, answer: $organisation)->organisation());
	}//end testAUsersActiveOrganisationIsTheScope()

	/**
	 * A user whose organisation cannot be resolved has the empty scope: no rules.
	 *
	 * @return void
	 */
	public function testAnUnresolvableOrganisationIsTheEmptyScope(): void {
		self::assertSame('', $this->scope(signedIn: true, answer: null)->organisation());
		self::assertSame('', $this->scope(signedIn: true, answer: new RuntimeException('down'))->organisation());
	}//end testAnUnresolvableOrganisationIsTheEmptyScope()

	/**
	 * Without a user session there is no scope: every organisation's rules apply.
	 *
	 * @return void
	 */
	public function testNoSessionMeansEveryOrganisation(): void {
		self::assertNull($this->scope(signedIn: false, answer: new RuntimeException('never asked'))->organisation());
	}//end testNoSessionMeansEveryOrganisation()
}//end class

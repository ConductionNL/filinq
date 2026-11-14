<?php

/**
 * RBAC matrix tests for PolicyCrudService::requirePolicyPermission()
 *
 * Pins the two group gates behind every policy endpoint, per surface and per
 * action: a member of the surface's own group passes, an admin passes, a
 * signed-in outsider and an anonymous caller are refused with the exact
 * message, membership of the other surface's group does not count, and an
 * unknown surface is an error rather than a silent pass.
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
 * @spec openspec/changes/filinq-policy-controller-test-coverage/tasks.md#a-1-policycrudservicetestphp-m
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\PolicyCrudService;
use OCA\Filinq\Service\SettingsService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for the per-surface permission gates of PolicyCrudService.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\PolicyCrudService
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PolicyCrudServiceTest extends TestCase {

	/**
	 * Mocked group manager.
	 *
	 * @var IGroupManager&MockObject
	 */
	private IGroupManager&MockObject $groupManager;

	/**
	 * Mocked user session.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $userSession;

	/**
	 * Set up the boundary mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession  = $this->createMock(IUserSession::class);

	}//end setUp()

	/**
	 * Build the service under test.
	 *
	 * @return PolicyCrudService The service.
	 */
	private function service(): PolicyCrudService {
		return new PolicyCrudService(
			$this->createMock(SettingsService::class),
			$this->createMock(ConsentService::class),
			$this->groupManager,
			$this->userSession,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * Sign in a non-admin user who belongs to exactly the given groups.
	 *
	 * @param list<string> $groups The groups the user is a member of.
	 * @param bool         $admin  Whether the user is an instance admin.
	 *
	 * @return void
	 */
	private function signIn(array $groups, bool $admin=false): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('officer');
		$this->userSession->method('getUser')->willReturn($user);
		$this->groupManager->method('isAdmin')->with('officer')->willReturn($admin);
		$this->groupManager->method('isInGroup')->willReturnCallback(
			static fn (string $uid, string $group): bool => $uid === 'officer' && in_array($group, $groups, true)
		);

	}//end signIn()

	/**
	 * Every surface crossed with every action, with the surface's own group and label.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
	 */
	public static function surfaceActionProvider(): array {
		$surfaces = [
			'prohibition'     => [
				PolicyCrudService::SURFACE_PROHIBITION,
				PolicyCrudService::PROHIBITION_GROUP,
				PolicyCrudService::STANDING_CONSENT_GROUP,
				'Prohibition',
			],
			'standingConsent' => [
				PolicyCrudService::SURFACE_STANDING_CONSENT,
				PolicyCrudService::STANDING_CONSENT_GROUP,
				PolicyCrudService::PROHIBITION_GROUP,
				'Standing-consent',
			],
		];

		$cases = [];
		foreach ($surfaces as $name => [$surface, $ownGroup, $otherGroup, $label]) {
			foreach (['read', 'create', 'update', 'delete'] as $action) {
				$cases[$name.' '.$action] = [$surface, $action, $ownGroup, $otherGroup, $label];
			}
		}

		return $cases;

	}//end surfaceActionProvider()

	/**
	 * A member of the surface's own group passes for every action.
	 *
	 * @param string $surface    The policy surface.
	 * @param string $action     The action.
	 * @param string $ownGroup   The surface's group.
	 * @param string $otherGroup The other surface's group.
	 * @param string $label      The surface label in messages.
	 *
	 * @return void
	 *
	 * @dataProvider surfaceActionProvider
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function testMemberOfTheSurfaceGroupPasses(
		string $surface,
		string $action,
		string $ownGroup,
		string $otherGroup,
		string $label
	): void {
		$this->signIn(groups: [$ownGroup]);
		$this->groupManager->expects($this->once())
			->method('isInGroup')
			->with('officer', $ownGroup);

		$this->service()->requirePolicyPermission($surface, $action);

	}//end testMemberOfTheSurfaceGroupPasses()

	/**
	 * A signed-in outsider is refused with a message naming the action and the group.
	 *
	 * @param string $surface    The policy surface.
	 * @param string $action     The action.
	 * @param string $ownGroup   The surface's group.
	 * @param string $otherGroup The other surface's group.
	 * @param string $label      The surface label in messages.
	 *
	 * @return void
	 *
	 * @dataProvider surfaceActionProvider
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function testOutsiderIsRefused(
		string $surface,
		string $action,
		string $ownGroup,
		string $otherGroup,
		string $label
	): void {
		$this->signIn(groups: []);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage(sprintf('%s %s requires membership in the "%s" group.', $label, $action, $ownGroup));

		$this->service()->requirePolicyPermission($surface, $action);

	}//end testOutsiderIsRefused()

	/**
	 * Membership of the other surface's group does not open this surface.
	 *
	 * @param string $surface    The policy surface.
	 * @param string $action     The action.
	 * @param string $ownGroup   The surface's group.
	 * @param string $otherGroup The other surface's group.
	 * @param string $label      The surface label in messages.
	 *
	 * @return void
	 *
	 * @dataProvider surfaceActionProvider
	 */
	public function testTheOtherSurfacesGroupDoesNotCount(
		string $surface,
		string $action,
		string $ownGroup,
		string $otherGroup,
		string $label
	): void {
		$this->signIn(groups: [$otherGroup]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage(sprintf('%s %s requires membership in the "%s" group.', $label, $action, $ownGroup));

		$this->service()->requirePolicyPermission($surface, $action);

	}//end testTheOtherSurfacesGroupDoesNotCount()

	/**
	 * An anonymous caller is refused before any group is consulted.
	 *
	 * @param string $surface    The policy surface.
	 * @param string $action     The action.
	 * @param string $ownGroup   The surface's group.
	 * @param string $otherGroup The other surface's group.
	 * @param string $label      The surface label in messages.
	 *
	 * @return void
	 *
	 * @dataProvider surfaceActionProvider
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function testAnonymousCallerIsRefused(
		string $surface,
		string $action,
		string $ownGroup,
		string $otherGroup,
		string $label
	): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->groupManager->expects($this->never())->method('isAdmin');
		$this->groupManager->expects($this->never())->method('isInGroup');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage(sprintf('%s %s requires an authenticated user.', $label, $action));

		$this->service()->requirePolicyPermission($surface, $action);

	}//end testAnonymousCallerIsRefused()

	/**
	 * An instance admin passes without a group lookup.
	 *
	 * @param string $surface    The policy surface.
	 * @param string $action     The action.
	 * @param string $ownGroup   The surface's group.
	 * @param string $otherGroup The other surface's group.
	 * @param string $label      The surface label in messages.
	 *
	 * @return void
	 *
	 * @dataProvider surfaceActionProvider
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function testAdminPassesWithoutAGroupLookup(
		string $surface,
		string $action,
		string $ownGroup,
		string $otherGroup,
		string $label
	): void {
		$this->signIn(groups: [], admin: true);
		$this->groupManager->expects($this->never())->method('isInGroup');

		$this->service()->requirePolicyPermission($surface, $action);

	}//end testAdminPassesWithoutAGroupLookup()

	/**
	 * An unknown surface throws InvalidArgumentException, before the session is read.
	 *
	 * @return void
	 */
	public function testUnknownSurfaceIsAnError(): void {
		$this->userSession->expects($this->never())->method('getUser');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown policy surface: consent');

		$this->service()->requirePolicyPermission('consent', 'read');

	}//end testUnknownSurfaceIsAnError()
}//end class

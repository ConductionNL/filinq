<?php

/**
 * Unit tests for CustomDictionaryCapabilitiesController.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/custom-dictionary-recognition/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\CustomDictionaryCapabilitiesController;
use OCA\Filinq\Service\CustomDictionaryAccessGate;
use OCP\App\IAppManager;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * The capabilities answer follows the maintainer role.
 */
class CustomDictionaryCapabilitiesControllerTest extends TestCase {

	/**
	 * The controller over a real gate for one user.
	 *
	 * @param bool $maintainer Whether the user is in the maintainer group.
	 * @param bool $admin      Whether the user is an admin.
	 *
	 * @return CustomDictionaryCapabilitiesController
	 */
	private function controller(bool $maintainer, bool $admin = false): CustomDictionaryCapabilitiesController {
		$user = $this->createMock(originalClassName: IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(originalClassName: IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$groups = $this->createMock(originalClassName: IGroupManager::class);
		$groups->method('isAdmin')->willReturn($admin);
		$groups->method('isInGroup')->willReturnCallback(
			static fn (string $uid, string $group): bool => $maintainer === true && $group === CustomDictionaryAccessGate::MAINTAINER_GROUP
		);

		$gate = new CustomDictionaryAccessGate(
			container: $this->createMock(originalClassName: ContainerInterface::class),
			appManager: $this->createMock(originalClassName: IAppManager::class),
			userSession: $session,
			logger: new NullLogger(),
			groupManager: $groups
		);

		return new CustomDictionaryCapabilitiesController(
			appName: 'filinq',
			request: $this->createMock(originalClassName: IRequest::class),
			gate: $gate
		);

	}//end controller()

	/**
	 * A maintainer or an admin may change dictionaries; anybody else may not.
	 *
	 * @return void
	 */
	public function testCanMaintainFollowsTheRole(): void {
		$this->assertSame(expected: ['canMaintain' => true], actual: $this->controller(maintainer: true)->show()->getData());
		$this->assertSame(expected: ['canMaintain' => true], actual: $this->controller(maintainer: false, admin: true)->show()->getData());
		$this->assertSame(expected: ['canMaintain' => false], actual: $this->controller(maintainer: false)->show()->getData());

	}//end testCanMaintainFollowsTheRole()
}//end class

<?php

/**
 * Golden tests for the guards in front of ConsentController's record actions
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
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

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\ConsentController;
use OCA\Filinq\Service\ConsentCrudService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Pins, for index, show, update and byDocument: nobody signed in is refused
 * with 401 before the configuration is read; a missing configuration is a 400;
 * and the list actions scope to the caller unless the caller is a consent
 * officer.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConsentControllerGuardGoldenTest extends TestCase {

	/**
	 * The consent store.
	 *
	 * @var ConsentCrudService|MockObject
	 */
	private ConsentCrudService|MockObject $crud;

	/**
	 * Group membership, for the officer check.
	 *
	 * @var IGroupManager|MockObject
	 */
	private IGroupManager|MockObject $groups;

	/**
	 * Set up the consent store and group manager.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->crud   = $this->createMock(ConsentCrudService::class);
		$this->groups = $this->createMock(IGroupManager::class);

	}//end setUp()

	/**
	 * Each guarded action.
	 *
	 * @return array<string,array{0:string}>
	 */
	public static function actions(): array {
		return [
			'index'      => ['index'],
			'show'       => ['show'],
			'update'     => ['update'],
			'byDocument' => ['byDocument'],
		];

	}//end actions()

	/**
	 * Refused: nobody signed in answers 401 and never reads the configuration.
	 *
	 * @param string $action The action under test.
	 *
	 * @return void
	 */
	#[DataProvider('actions')]
	public function testNobodySignedInIsRefusedFirst(string $action): void {
		$this->crud->expects($this->never())->method('getConsentConfig');

		$response = $this->call($this->controller(null), $action);

		$this->assertSame(401, $response->getStatus());
		$this->assertSame('Not authenticated', $response->getData()['error'] ?? $response->getData()['message'] ?? null);

	}//end testNobodySignedInIsRefusedFirst()

	/**
	 * Refused: a signed-in caller without a configured register gets the 400.
	 *
	 * @param string $action The action under test.
	 *
	 * @return void
	 */
	#[DataProvider('actions')]
	public function testMissingConfigurationIs400(string $action): void {
		$this->crud->expects($this->once())->method('getConsentConfig')->willReturn(null);

		$response = $this->call($this->controller('owner'), $action);

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'PublicationConsent register/schema not configured'], $response->getData());

	}//end testMissingConfigurationIs400()

	/**
	 * Allowed, scoped: a caller who is no officer lists only their own records.
	 *
	 * @return void
	 */
	public function testListsAreScopedToTheCaller(): void {
		$this->crud->method('getConsentConfig')->willReturn(['register' => 'r', 'schema' => 's']);
		$this->groups->method('isAdmin')->willReturn(false);
		$this->groups->method('isInGroup')->willReturn(false);
		$this->crud->expects($this->once())->method('listConsents')->with('r', 's', 'owner')->willReturn([['id' => 1]]);
		$this->crud->expects($this->once())->method('getConsentsByDocument')->with('doc', 'r', 's', 'owner')->willReturn([['id' => 2]]);

		$controller = $this->controller('owner');
		$this->assertSame([['id' => 1]], $controller->index()->getData());
		$this->assertSame([['id' => 2]], $controller->byDocument('doc')->getData());

	}//end testListsAreScopedToTheCaller()

	/**
	 * Allowed, unscoped: an administrator lists every record.
	 *
	 * @return void
	 */
	public function testListsAreUnscopedForAnOfficer(): void {
		$this->crud->method('getConsentConfig')->willReturn(['register' => 'r', 'schema' => 's']);
		$this->groups->method('isAdmin')->with('boss')->willReturn(true);
		$this->crud->expects($this->once())->method('listConsents')->with('r', 's', null)->willReturn([]);
		$this->crud->expects($this->once())->method('getConsentsByDocument')->with('doc', 'r', 's', null)->willReturn([]);

		$controller = $this->controller('boss');
		$this->assertSame(200, $controller->index()->getStatus());
		$this->assertSame(200, $controller->byDocument('doc')->getStatus());

	}//end testListsAreUnscopedForAnOfficer()

	/**
	 * The controller with this user signed in, or nobody.
	 *
	 * @param string|null $uid The signed-in user, or null.
	 *
	 * @return ConsentController
	 */
	private function controller(?string $uid): ConsentController {
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $params = []): string => vsprintf($text, $params));

		return new ConsentController('filinq', $this->createMock(IRequest::class), $this->createMock(LoggerInterface::class), $this->crud, $l10n, $session, $this->groups);

	}//end controller()

	/**
	 * Call an action with a placeholder id where it takes one.
	 *
	 * @param ConsentController $controller The controller.
	 * @param string            $action     The action.
	 *
	 * @return JSONResponse
	 */
	private function call(ConsentController $controller, string $action): JSONResponse {
		if ($action === 'index') {
			return $controller->index();
		}

		return $controller->{$action}('id-1');

	}//end call()

}//end class

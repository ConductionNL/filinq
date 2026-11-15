<?php

/**
 * Golden tests for the permission guard in front of the prohibition and
 * standing consent actions
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

use Exception;
use OCA\Filinq\Controller\PolicyController;
use OCA\Filinq\Controller\StandingConsentController;
use OCA\Filinq\Kernel\Contracts\PolicyCrudServiceInterface;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Pins, for every prohibition and standing consent action: nobody signed in
 * is refused before any permission is asked; a refused permission stops the
 * action before its operation; an allowed one is asked for the action's
 * surface and verb before the operation runs.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PolicyPermissionGuardGoldenTest extends TestCase {

	/**
	 * The policy store.
	 *
	 * @var PolicyCrudServiceInterface|MockObject
	 */
	private PolicyCrudServiceInterface|MockObject $crud;

	/**
	 * Set up the policy store.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->crud = $this->createMock(PolicyCrudServiceInterface::class);

	}//end setUp()

	/**
	 * Every guarded action: controller, action, surface, verb and the operation behind it.
	 *
	 * @return array<string,array{0:array{0:class-string,1:string,2:string,3:string,4:string}}>
	 */
	public static function actions(): array {
		$p = PolicyCrudServiceInterface::SURFACE_PROHIBITION;
		$s = PolicyCrudServiceInterface::SURFACE_STANDING_CONSENT;
		$rows = [
			[PolicyController::class, 'indexProhibitions', $p, 'read', 'listProhibitions'],
			[PolicyController::class, 'showProhibition', $p, 'read', 'getProhibition'],
			[PolicyController::class, 'createProhibition', $p, 'create', 'createProhibition'],
			[PolicyController::class, 'updateProhibition', $p, 'update', 'updateProhibition'],
			[PolicyController::class, 'deleteProhibition', $p, 'delete', 'deleteProhibition'],
			[StandingConsentController::class, 'index', $s, 'read', 'listStandingConsents'],
			[StandingConsentController::class, 'show', $s, 'read', 'getStandingConsent'],
			[StandingConsentController::class, 'create', $s, 'create', 'createStandingConsent'],
			[StandingConsentController::class, 'update', $s, 'update', 'updateStandingConsent'],
			[StandingConsentController::class, 'destroy', $s, 'delete', 'deleteStandingConsent'],
		];

		$cases = [];
		foreach ($rows as $row) {
			$cases[$row[1]] = [$row];
		}

		return $cases;

	}//end actions()

	/**
	 * Refused: nobody signed in is a 401; no permission is asked, nothing runs.
	 *
	 * @param array{0:class-string,1:string,2:string,3:string,4:string} $row The action.
	 *
	 * @return void
	 */
	#[DataProvider('actions')]
	public function testNobodySignedInIsRefusedFirst(array $row): void {
		$this->crud->expects($this->never())->method('requirePolicyPermission');
		$this->crud->expects($this->never())->method($row[4]);

		$this->assertSame(401, $this->call($row, false)->getStatus());

	}//end testNobodySignedInIsRefusedFirst()

	/**
	 * Refused: a denied permission stops the action before its operation.
	 *
	 * @param array{0:class-string,1:string,2:string,3:string,4:string} $row The action.
	 *
	 * @return void
	 */
	#[DataProvider('actions')]
	public function testDeniedPermissionStopsTheOperation(array $row): void {
		$this->crud->expects($this->once())->method('requirePolicyPermission')
			->with(surface: $row[2], action: $row[3])
			->willThrowException(new Exception('denied'));
		$this->crud->expects($this->never())->method($row[4]);

		$this->assertSame(500, $this->call($row, true)->getStatus());

	}//end testDeniedPermissionStopsTheOperation()

	/**
	 * Allowed: the permission for the action's surface and verb is asked before the operation runs.
	 *
	 * @param array{0:class-string,1:string,2:string,3:string,4:string} $row The action.
	 *
	 * @return void
	 */
	#[DataProvider('actions')]
	public function testAllowedPermissionPrecedesTheOperation(array $row): void {
		$calls = [];
		$this->crud->expects($this->once())->method('requirePolicyPermission')
			->with(surface: $row[2], action: $row[3])
			->willReturnCallback(static function () use (&$calls): void {
				$calls[] = 'permission';
			});
		$this->crud->method($row[4])->willReturnCallback(static function () use (&$calls): mixed {
			$calls[] = 'operation';
			return ['uuid' => 'x'];
		});

		$this->call($row, true);

		$this->assertSame('permission', $calls[0] ?? null);
		$this->assertContains('operation', $calls);

	}//end testAllowedPermissionPrecedesTheOperation()

	/**
	 * Call the action with a signed-in user or nobody.
	 *
	 * @param array{0:class-string,1:string,2:string,3:string,4:string} $row      The action.
	 * @param bool                                                     $signedIn Whether a user is signed in.
	 *
	 * @return JSONResponse
	 */
	private function call(array $row, bool $signedIn): JSONResponse {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($signedIn === true ? $this->createMock(IUser::class) : null);
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn(['name' => 'n']);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $params = []): string => vsprintf($text, $params));

		$controller = new $row[0]('filinq', $request, $this->createMock(LoggerInterface::class), $this->crud, $l10n, $session);
		$action = $row[1];
		if (in_array($action, ['indexProhibitions', 'createProhibition', 'index', 'create'], true) === true) {
			return $controller->{$action}();
		}

		return $controller->{$action}('id-1');

	}//end call()

}//end class

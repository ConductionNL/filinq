<?php

/**
 * Characterization tests for ConsentController::update()
 *
 * Pins which user the ownership and officer checks run for, what reaches the
 * consent service, and how each failure is answered.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use InvalidArgumentException;
use OCA\Filinq\Controller\ConsentController;
use OCA\Filinq\Kernel\Contracts\ConsentCrudServiceInterface;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the consent update endpoint.
 *
 * @covers \OCA\Filinq\Controller\ConsentController
 */
class ConsentControllerUpdateTraceTest extends TestCase {

	private const CONFIG = ['register' => 'filinq', 'schema' => 'consent'];

	private ConsentCrudServiceInterface&MockObject $crud;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->crud = $this->createMock(ConsentCrudServiceInterface::class);
		$this->crud->method('getConsentConfig')->willReturn(self::CONFIG);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * An officer's update reaches the service with the request body and the SESSION user,
	 * whatever user the body names.
	 *
	 * @return void
	 */
	public function testOfficerUpdateCarriesTheSessionUser(): void {
		$params = ['status' => 'granted', 'userId' => 'mallory', 'requestedBy' => 'mallory'];
		$this->crud->method('getConsent')->with('c-1', 'filinq', 'consent')->willReturn(['id' => 'c-1', 'owner' => 'someone']);
		$this->crud->expects($this->once())->method('updateConsentStatus')
			->with('c-1', 'filinq', 'consent', $params, $this->callback(static fn (IUser $user): bool => $user->getUID() === 'officer'))
			->willReturn(['id' => 'c-1', 'status' => 'granted']);

		$response = $this->controller(uid: 'officer', params: $params)->update('c-1');

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['id' => 'c-1', 'status' => 'granted'], $response->getData());

	}//end testOfficerUpdateCarriesTheSessionUser()

	/**
	 * A body naming the record's owner or an officer does not make a stranger either.
	 *
	 * @return void
	 */
	public function testForgedUserIdDoesNotPassTheOwnershipCheck(): void {
		$this->crud->method('getConsent')->willReturn(['id' => 'c-1', 'owner' => 'owner']);
		$this->crud->expects($this->never())->method('updateConsentStatus');

		$response = $this->controller(uid: 'stranger', params: ['userId' => 'owner', 'requestedBy' => 'officer'])->update('c-1');

		$this->assertSame(404, $response->getStatus());
		$this->assertSame(['error' => 'Consent record not found'], $response->getData());

	}//end testForgedUserIdDoesNotPassTheOwnershipCheck()

	/**
	 * The owner who is no officer is refused with 403; a missing record is 404.
	 *
	 * @return void
	 */
	public function testOwnerWithoutOfficerRoleAndMissingRecord(): void {
		$this->crud->method('getConsent')->willReturnCallback(static fn (string $id): ?array => ($id === 'c-1' ? ['@self' => ['owner' => ['id' => 'owner']]] : null));
		$this->crud->expects($this->never())->method('updateConsentStatus');

		$response = $this->controller(uid: 'owner', params: ['userId' => 'officer'])->update('c-1');
		$this->assertSame(403, $response->getStatus());
		$this->assertSame(['error' => 'Only a consent officer can record the outcome of a consent request.'], $response->getData());

		$response = $this->controller(uid: 'officer', params: [])->update('c-missing');
		$this->assertSame(404, $response->getStatus());

	}//end testOwnerWithoutOfficerRoleAndMissingRecord()

	/**
	 * No session answers 401; no configuration answers 400.
	 *
	 * @return void
	 */
	public function testNoSessionAndNoConfiguration(): void {
		$this->crud->expects($this->never())->method('getConsent');
		$this->assertSame(401, $this->controller(uid: null, params: [])->update('c-1')->getStatus());

		$crud = $this->createMock(ConsentCrudServiceInterface::class);
		$crud->method('getConsentConfig')->willReturn(null);
		$this->crud = $crud;
		$response = $this->controller(uid: 'officer', params: [])->update('c-1');
		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'PublicationConsent register/schema not configured'], $response->getData());

	}//end testNoSessionAndNoConfiguration()

	/**
	 * Each failure of the service is answered by its kind.
	 *
	 * @return void
	 */
	public function testEachFailureIsAnsweredByItsKind(): void {
		$cases = [
			[new InvalidArgumentException('no move from granted to pending'), 400, ['error' => 'no move from granted to pending']],
			[new OCSForbiddenException('no'), 403, ['error' => 'You are not allowed to revoke or modify standing consents.']],
			[new Exception('conflict', 409), 409, ['error' => 'Failed to update consent']],
			[new Exception('boom'), 500, ['error' => 'Failed to update consent']],
		];

		foreach ($cases as [$failure, $status, $body]) {
			$this->setUp();
			$this->crud->method('getConsent')->willReturn(['id' => 'c-1', 'owner' => 'officer']);
			$this->crud->method('updateConsentStatus')->willThrowException($failure);
			if ($status >= 409) {
				$this->logger->expects($this->once())->method('error')
					->with('Failed to update consent' . $failure->getMessage(), ['exception' => $failure]);
			}

			$response = $this->controller(uid: 'officer', params: [])->update('c-1');

			$this->assertSame($status, $response->getStatus(), $failure::class);
			$this->assertSame($body, $response->getData(), $failure::class);
		}

	}//end testEachFailureIsAnsweredByItsKind()

	/**
	 * The controller for session user $uid; only `officer` is in an officer group.
	 *
	 * @param string|null $uid The session user, or null for none.
	 * @param array<string, mixed> $params The request body.
	 *
	 * @return ConsentController The controller.
	 */
	private function controller(?string $uid, array $params): ConsentController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($params);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);

		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturn(false);
		$groups->method('isInGroup')->willReturnCallback(static fn (string $who, string $group): bool => ($who === 'officer' && $group === 'filinq-woo-officers'));

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $args = []): string => vsprintf($text, $args));

		return new ConsentController(
			appName: 'filinq',
			request: $request,
			logger: $this->logger,
			crudService: $this->crud,
			l10n: $l10n,
			userSession: $session,
			groupManager: $groups,
		);

	}//end controller()
}//end class

<?php

/**
 * The registry decision endpoint: a signed-in person's decision reaches the
 * service and comes back as the saved document, a refusal keeps its status,
 * and an anonymous caller is turned away before anything runs.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\IntakeController;
use OCA\Filinq\Controller\Support\IntakeHandling;
use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Service\Intake\IntakeNotificationReach;
use OCA\Filinq\Service\Intake\RegistrySuggestionDecision;
use OCA\Filinq\Service\IntakeDetachmentService;
use OCA\Filinq\Service\IntakeRoutingService;
use OCA\Filinq\Service\IntakeService;
use OCA\Filinq\Service\PartySuggestionService;
use OCP\AppFramework\Http;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Controller\IntakeController
 * @uses   \OCA\Filinq\Controller\Support\IntakeHandling
 * @uses   \OCA\Filinq\Exception\IntakeRefusedException
 * @uses   \OCA\Filinq\Service\Intake\IntakeNotificationReach
 */
class IntakeRegistryDecisionControllerTest extends TestCase {

	/**
	 * The controller over the given decision service.
	 *
	 * @param RegistrySuggestionDecision|null $registry The decision service, or null when none is wired.
	 * @param bool                            $signedIn Whether somebody is signed in.
	 *
	 * @return IntakeController The controller under test.
	 */
	private function controller(?RegistrySuggestionDecision $registry, bool $signedIn = true): IntakeController {
		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('anna');
		}

		$session->method('getUser')->willReturn($user);

		return new IntakeController(
			$this->createMock(IRequest::class),
			new IntakeHandling(
				intake: $this->createMock(IntakeService::class),
				detachment: $this->createMock(IntakeDetachmentService::class),
				routing: $this->createMock(IntakeRoutingService::class),
				registry: $registry
			),
			$this->createMock(PartySuggestionService::class),
			$session,
			new IntakeNotificationReach(),
			$this->createMock(IGroupManager::class)
		);

	}//end controller()

	/**
	 * The decision and its corrections reach the service; the saved document comes back.
	 *
	 * @return void
	 */
	public function testADecisionReachesTheService(): void {
		$registry = $this->createMock(RegistrySuggestionDecision::class);
		$registry->expects($this->once())->method('decide')
			->with('intake-1', 0, 'accepted', ['address' => 'Voorbeeldstraat 3'])
			->willReturn(['uuid' => 'intake-1', 'registrySuggestion' => [['written' => true]]]);

		$response = $this->controller(registry: $registry)->decideRegistry(uuid: 'intake-1', index: 0, decision: 'accepted', fields: ['address' => 'Voorbeeldstraat 3']);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['uuid' => 'intake-1', 'registrySuggestion' => [['written' => true]]], $response->getData());

	}//end testADecisionReachesTheService()

	/**
	 * A refusal keeps its status and message.
	 *
	 * @return void
	 */
	public function testARefusalKeepsItsStatus(): void {
		$registry = $this->createMock(RegistrySuggestionDecision::class);
		$registry->method('decide')->willThrowException(new IntakeRefusedException(message: 'This registry suggestion was already decided.', status: 409));

		$response = $this->controller(registry: $registry)->decideRegistry(uuid: 'intake-1', index: 0, decision: 'dismissed');

		$this->assertSame(409, $response->getStatus());
		$this->assertSame(['error' => 'This registry suggestion was already decided.'], $response->getData());

	}//end testARefusalKeepsItsStatus()

	/**
	 * An anonymous caller is refused, and without the service the endpoint says it is unavailable.
	 *
	 * @return void
	 */
	public function testAnonymousAndUnwiredAreRefused(): void {
		$registry = $this->createMock(RegistrySuggestionDecision::class);
		$registry->expects($this->never())->method('decide');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $this->controller(registry: $registry, signedIn: false)->decideRegistry(uuid: 'intake-1', index: 0, decision: 'accepted')->getStatus());
		$this->assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $this->controller(registry: null)->decideRegistry(uuid: 'intake-1', index: 0, decision: 'accepted')->getStatus());

	}//end testAnonymousAndUnwiredAreRefused()
}//end class

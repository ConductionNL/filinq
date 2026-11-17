<?php

/**
 * Unit tests for GroundsController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\GroundsController;
use OCA\Filinq\Service\Grounds\RefusalGroundsResolver;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * GET /api/grounds answers the resolver, to any signed-in user, read only.
 */
#[CoversClass(GroundsController::class)]
class GroundsControllerTest extends TestCase {

	/**
	 * The resolver.
	 *
	 * @var RefusalGroundsResolver&MockObject
	 */
	private RefusalGroundsResolver&MockObject $grounds;

	/**
	 * The session.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $session;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Set up the collaborators.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->grounds = $this->createMock(RefusalGroundsResolver::class);
		$this->session = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The controller under test.
	 *
	 * @return GroundsController
	 */
	private function controller(): GroundsController {
		return new GroundsController($this->createMock(IRequest::class), $this->grounds, $this->session, $this->logger);

	}//end controller()

	/**
	 * The route resolves to the method, and the method answers the resolver unchanged.
	 *
	 * @return void
	 */
	public function testTheEndpointAnswersTheResolver(): void {
		$routes = require __DIR__ . '/../../../appinfo/routes.php';
		// Served at api/grounds and at its /api/v1/ twin (ApiPathAliasesTest).
		$route = array_values(array_filter($routes['routes'], static fn (array $r): bool => $r['name'] === 'grounds#index' && $r['url'] === 'api/grounds'));
		$this->assertCount(1, $route);
		$this->assertSame('GET', $route[0]['verb']);
		$method = explode('#', $route[0]['name'])[1];
		$this->assertNotEmpty((new ReflectionMethod(GroundsController::class, $method))->getAttributes(NoAdminRequired::class));

		$list = ['source' => 'snapshot', 'version' => '1.0.0', 'grounds' => [['code' => '5.1.2.e', 'label' => 'Privacy']]];
		$this->session->method('getUser')->willReturn($this->createMock(IUser::class));
		$this->grounds->expects($this->once())->method('list')->willReturn($list);

		$response = $this->controller()->$method();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame($list, $response->getData());

	}//end testTheEndpointAnswersTheResolver()

	/**
	 * Without a signed-in user nothing is read.
	 *
	 * @return void
	 */
	public function testWithoutAUserTheEndpointRefuses(): void {
		$this->session->method('getUser')->willReturn(null);
		$this->grounds->expects($this->never())->method('list');

		$response = $this->controller()->index();

		$this->assertSame(401, $response->getStatus());

	}//end testWithoutAUserTheEndpointRefuses()

	/**
	 * A list that cannot be read answers 503, never an empty list.
	 *
	 * @return void
	 */
	public function testAnUnreadableListAnswersUnavailable(): void {
		$this->session->method('getUser')->willReturn($this->createMock(IUser::class));
		$this->grounds->method('list')->willThrowException(new RuntimeException('The Woo refusal grounds snapshot cannot be read.'));
		$this->logger->expects($this->once())->method('error');

		$response = $this->controller()->index();

		$this->assertSame(503, $response->getStatus());
		$this->assertSame(['error' => 'The Woo refusal grounds cannot be read.'], $response->getData());

	}//end testAnUnreadableListAnswersUnavailable()
}//end class

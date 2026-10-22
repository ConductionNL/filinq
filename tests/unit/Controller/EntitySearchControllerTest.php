<?php

/**
 * The entity search routes answer each refusal with its own status and a neutral body.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\EntitySearchController;
use OCA\Filinq\Exception\EntitySearchRefusedException;
use OCA\Filinq\Service\EntitySearch\EntitySearchService;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;
use RuntimeException;

/**
 * EntitySearchController over a service double that refuses or answers.
 */
class EntitySearchControllerTest extends TestCase {

	/**
	 * The controller over a service that throws $refusal, or answers.
	 *
	 * @param \Throwable|null      $failure What every service call throws, null to answer.
	 * @param array<string, mixed> $params  The request parameters.
	 * @param array<int, mixed>    $calls   Receives the calls the service got.
	 *
	 * @return EntitySearchController The controller.
	 */
	private function controller(?\Throwable $failure, array $params = [], array &$calls = []): EntitySearchController {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('petra');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(static fn (string $key, $default = null) => ($params[$key] ?? $default));
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$service = $this->createMock(EntitySearchService::class);
		foreach (['access', 'search', 'detail'] as $method) {
			$service->method($method)->willReturnCallback(
				static function (...$args) use ($failure, $method, &$calls): array {
					$calls[] = [$method, $args];
					if ($failure !== null) {
						throw $failure;
					}

					return ['ok' => $method];
				}
			);
		}

		return new EntitySearchController('filinq', $request, $service, $session, $l10n, new NullLogger());

	}//end controller()

	/**
	 * Each refusal maps to its status on every route, with the reason and no data.
	 *
	 * @return void
	 */
	public function testEveryRefusalHasItsStatusOnEveryRoute(): void {
		$expected = [
			EntitySearchRefusedException::REASON_NOT_ALLOWED => 403,
			EntitySearchRefusedException::REASON_CONFIG_UNREADABLE => 403,
			EntitySearchRefusedException::REASON_NOT_FOUND => 404,
			EntitySearchRefusedException::REASON_INVALID => 400,
			EntitySearchRefusedException::REASON_CATALOGUE_UNAVAILABLE => 503,
			EntitySearchRefusedException::REASON_LOG_UNAVAILABLE => 503,
		];
		foreach ($expected as $reason => $status) {
			$controller = $this->controller(failure: new EntitySearchRefusedException(reason: $reason, message: 'internal detail'));
			foreach ([$controller->access(), $controller->index(), $controller->show(entityUuid: 'e-1')] as $response) {
				$this->assertSame($status, $response->getStatus(), $reason);
				$this->assertSame($reason, $response->getData()['reason']);
				$this->assertStringNotContainsString('internal detail', (string) json_encode($response->getData()));
			}
		}

	}//end testEveryRefusalHasItsStatusOnEveryRoute()

	/**
	 * An unexpected failure is 500 and does not echo the error.
	 *
	 * @return void
	 */
	public function testAnUnexpectedFailureIs500WithoutDetail(): void {
		$response = $this->controller(failure: new RuntimeException('SQLSTATE secret'))->index();

		$this->assertSame(500, $response->getStatus());
		$this->assertStringNotContainsString('SQLSTATE', (string) json_encode($response->getData()));

	}//end testAnUnexpectedFailureIs500WithoutDetail()

	/**
	 * The search passes the caller and the parameters through, and answers 200.
	 *
	 * @return void
	 */
	public function testTheSearchPassesTheCallerAndParameters(): void {
		$calls = [];
		$response = $this->controller(failure: null, params: ['query' => 'vries', 'type' => 'PERSON', 'limit' => '10', 'offset' => '20'], calls: $calls)->index();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['search', ['petra', 'vries', 'PERSON', '', 10, 20]], [$calls[0][0], array_values($calls[0][1])]);

	}//end testTheSearchPassesTheCallerAndParameters()

	/**
	 * Every route declares its auth posture: open to signed-in users, gated in the service.
	 *
	 * @return void
	 */
	public function testEveryRouteIsNoAdminRequiredAndNotPublic(): void {
		foreach (['access', 'index', 'show'] as $method) {
			$attributes = array_map(static fn ($a): string => $a->getName(), (new ReflectionMethod(EntitySearchController::class, $method))->getAttributes());
			$this->assertContains('OCP\AppFramework\Http\Attribute\NoAdminRequired', $attributes, $method);
			$this->assertNotContains('OCP\AppFramework\Http\Attribute\PublicPage', $attributes, $method);
		}

	}//end testEveryRouteIsNoAdminRequiredAndNotPublic()
}//end class

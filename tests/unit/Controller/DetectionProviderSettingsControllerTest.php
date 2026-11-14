<?php

/**
 * Tests for DetectionProviderSettingsController: admin-only, fail-closed.
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
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use InvalidArgumentException;
use OCA\Filinq\Controller\DetectionProviderSettingsController;
use OCA\Filinq\Service\Detection\DetectionProviderConfig;
use OCA\Filinq\Service\Detection\LlmDetectionProvider;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * @covers \OCA\Filinq\Controller\DetectionProviderSettingsController
 */
class DetectionProviderSettingsControllerTest extends TestCase {

	/**
	 * Mocked request.
	 *
	 * @var IRequest&MockObject
	 */
	private IRequest&MockObject $request;

	/**
	 * Mocked config.
	 *
	 * @var DetectionProviderConfig&MockObject
	 */
	private DetectionProviderConfig&MockObject $config;

	/**
	 * Mocked LLM provider.
	 *
	 * @var LlmDetectionProvider&MockObject
	 */
	private LlmDetectionProvider&MockObject $llm;

	/**
	 * Set up the mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);
		$this->config  = $this->createMock(DetectionProviderConfig::class);
		$this->llm     = $this->createMock(LlmDetectionProvider::class);
	}

	/**
	 * The controller for a caller.
	 *
	 * @param string|null $caller The uid, or null when signed out.
	 * @param bool        $admin  Whether the caller is an admin.
	 *
	 * @return DetectionProviderSettingsController The controller.
	 */
	private function controller(?string $caller='admin', bool $admin=true): DetectionProviderSettingsController {
		$user = null;
		if ($caller !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($caller);
		}

		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturnCallback(static fn (string $uid): bool => $uid === $caller && $admin);

		return new DetectionProviderSettingsController(
			request: $this->request,
			config: $this->config,
			llmProvider: $this->llm,
			userSession: $session,
			groupManager: $groups
		);
	}

	/**
	 * Every action, invoked on a controller.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function actionProvider(): array {
		return ['index' => ['index'], 'update' => ['update'], 'test' => ['test']];
	}

	/**
	 * A non-admin is refused 403 and nothing is read, written or probed.
	 *
	 * @param string $action The action.
	 *
	 * @return void
	 *
	 * @dataProvider actionProvider
	 */
	public function testNonAdminIsForbidden(string $action): void {
		$this->config->expects($this->never())->method('toArray');
		$this->config->expects($this->never())->method('update');
		$this->llm->expects($this->never())->method('testConnection');

		$response = $this->controller(caller: 'clerk', admin: false)->$action();

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		$this->assertSame(['error' => 'Only an administrator may change detection settings.'], $response->getData());
	}

	/**
	 * A signed-out caller is refused 401.
	 *
	 * @param string $action The action.
	 *
	 * @return void
	 *
	 * @dataProvider actionProvider
	 */
	public function testSignedOutIsUnauthorised(string $action): void {
		$this->config->expects($this->never())->method('update');

		$response = $this->controller(caller: null)->$action();

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}

	/**
	 * Every action carries the admin-setting attribute.
	 *
	 * @param string $action The action.
	 *
	 * @return void
	 *
	 * @dataProvider actionProvider
	 */
	public function testEveryActionIsAdminGatedByAttribute(string $action): void {
		$attributes = (new ReflectionMethod(DetectionProviderSettingsController::class, $action))->getAttributes(AuthorizedAdminSetting::class);

		$this->assertCount(1, $attributes);
	}

	/**
	 * An admin reads the settings.
	 *
	 * @return void
	 */
	public function testAdminReadsTheSettings(): void {
		$this->config->method('toArray')->willReturn(['providers' => ['org-a' => 'llm']]);

		$response = $this->controller()->index();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['providers' => ['org-a' => 'llm']], $response->getData());
	}

	/**
	 * An admin's update is passed through and the stored settings come back.
	 *
	 * @return void
	 */
	public function testAdminUpdatesTheSettings(): void {
		$params = ['organisation' => 'org-a', 'provider' => 'llm'];
		$this->request->method('getParams')->willReturn($params);
		$this->config->expects($this->once())->method('update')->with($params);
		$this->config->method('toArray')->willReturn(['providers' => ['org-a' => 'llm']]);

		$response = $this->controller()->update();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['providers' => ['org-a' => 'llm']], $response->getData());
	}

	/**
	 * Invalid input is answered 400 with the reason.
	 *
	 * @return void
	 */
	public function testInvalidInputIsBadRequest(): void {
		$this->request->method('getParams')->willReturn(['provider' => 'cloud']);
		$this->config->method('update')->willThrowException(new InvalidArgumentException('Unknown detection provider.'));

		$response = $this->controller()->update();

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'Unknown detection provider.'], $response->getData());
	}

	/**
	 * The connectivity test answers the provider's verdict.
	 *
	 * @return void
	 */
	public function testConnectivityTest(): void {
		$this->llm->expects($this->once())->method('testConnection')->willReturn(['available' => false, 'reason' => 'not-local']);

		$response = $this->controller()->test();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['available' => false, 'reason' => 'not-local'], $response->getData());
	}
}

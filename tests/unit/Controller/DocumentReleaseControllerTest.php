<?php

/**
 * Document Release Controller Test
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\DocumentReleaseController;
use OCA\Filinq\Exception\DomainLinkRefusedException;
use OCA\Filinq\Service\DocumentReleaseService;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The release routes pass the record and the requester on, and answer a refusal with 403.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
#[CoversClass(DocumentReleaseController::class)]
#[UsesClass(DomainLinkRefusedException::class)]
class DocumentReleaseControllerTest extends TestCase {

	/**
	 * A release is handed the record, the requester and the kind, and answered with 200.
	 *
	 * @return void
	 */
	public function testAReleaseIsPassedOn(): void {
		$service = $this->createMock(DocumentReleaseService::class);
		$service->expects($this->once())->method('release')->with(
			'doc-1',
			['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'],
			'contact-7',
			'decision'
		)->willReturn(['uuid' => 'rel-1']);

		$response = $this->controller(
			service: $service,
			uid: 'anna',
			params: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1', 'contactRef' => 'contact-7', 'kind' => 'decision']
		)->release(uuid: 'doc-1');

		$this->assertSame(expected: 200, actual: $response->getStatus());
		$this->assertSame(expected: ['uuid' => 'rel-1'], actual: $response->getData());

	}//end testAReleaseIsPassedOn()

	/**
	 * A caller without write rights on the record gets 403, a bad request 400.
	 *
	 * @return void
	 */
	public function testRefusalsKeepTheirStatus(): void {
		$service = $this->createMock(DocumentReleaseService::class);
		$service->method('withdraw')->willReturnCallback(
			static function (string $releaseId): array {
				if ($releaseId === 'rel-foreign') {
					throw new DomainLinkRefusedException('You may not change the documents of that record.');
				}

				throw new RuntimeException('There is no release with that id.');
			}
		);
		$controller = $this->controller(service: $service, uid: 'anna');

		$refused = $controller->withdraw(releaseId: 'rel-foreign');
		$this->assertSame(expected: 403, actual: $refused->getStatus());
		$this->assertSame(expected: ['error' => 'You may not change the documents of that record.'], actual: $refused->getData());
		$this->assertSame(expected: 400, actual: $controller->withdraw(releaseId: 'rel-unknown')->getStatus());

	}//end testRefusalsKeepTheirStatus()

	/**
	 * Without a session nothing is released.
	 *
	 * @return void
	 */
	public function testNobodySignedInIsRefused(): void {
		$service = $this->createMock(DocumentReleaseService::class);
		$service->expects($this->never())->method('release');

		$response = $this->controller(
			service: $service,
			uid: null,
			params: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1', 'contactRef' => 'contact-7']
		)->release(uuid: 'doc-1');

		$this->assertSame(expected: 401, actual: $response->getStatus());

	}//end testNobodySignedInIsRefused()

	/**
	 * The controller with this caller, or nobody.
	 *
	 * @param DocumentReleaseService $service The release double.
	 * @param string|null            $uid     The caller, or null.
	 * @param array<string, mixed>   $params  The request body.
	 *
	 * @return DocumentReleaseController The controller.
	 */
	private function controller(DocumentReleaseService $service, ?string $uid, array $params = []): DocumentReleaseController {
		$session = $this->createMock(IUserSession::class);
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
			$session->method('getUser')->willReturn($user);
		}

		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, $default = null) => ($params[$key] ?? $default)
		);

		return new DocumentReleaseController(
			appName: 'filinq',
			request: $request,
			releases: $service,
			userSession: $session
		);

	}//end controller()
}//end class

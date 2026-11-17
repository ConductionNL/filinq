<?php

/**
 * Document Copy Controller Test
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\DocumentCopyController;
use OCA\Filinq\Exception\DomainLinkRefusedException;
use OCA\Filinq\Service\DocumentCopyService;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The copy route passes both records on and answers a refused check with 403.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
#[CoversClass(DocumentCopyController::class)]
#[UsesClass(DomainLinkRefusedException::class)]
class DocumentCopyControllerTest extends TestCase {

	/**
	 * A copy is handed the file and both records, and answered with 200.
	 *
	 * @return void
	 */
	public function testACopyIsPassedOn(): void {
		$service = $this->createMock(DocumentCopyService::class);
		$service->expects($this->once())->method('copy')->with(
			11,
			['register' => 'zaken', 'schema' => 'zaak', 'id' => 'case-a'],
			['register' => 'zaken', 'schema' => 'zaak', 'id' => 'case-b']
		)->willReturn(['fileId' => 99]);

		$response = $this->controller(
			service: $service,
			uid: 'anna',
			params: [
				'fileId'         => '11',
				'sourceRegister' => 'zaken',
				'sourceSchema'   => 'zaak',
				'sourceId'       => 'case-a',
				'register'       => 'zaken',
				'schema'         => 'zaak',
				'id'             => 'case-b',
			]
		)->copy();

		$this->assertSame(expected: 200, actual: $response->getStatus());
		$this->assertSame(expected: ['fileId' => 99], actual: $response->getData());

	}//end testACopyIsPassedOn()

	/**
	 * A refused check answers 403, and nobody signed in answers 401 without asking the service.
	 *
	 * @return void
	 */
	public function testRefusals(): void {
		$service = $this->createMock(DocumentCopyService::class);
		$service->method('copy')->willThrowException(new DomainLinkRefusedException('You may not change the documents of that record.'));

		$refused = $this->controller(service: $service, uid: 'anna', params: ['fileId' => 11])->copy();
		$this->assertSame(expected: 403, actual: $refused->getStatus());

		$nobody = $this->createMock(DocumentCopyService::class);
		$nobody->expects($this->never())->method('copy');
		$this->assertSame(expected: 401, actual: $this->controller(service: $nobody, uid: null, params: ['fileId' => 11])->copy()->getStatus());

	}//end testRefusals()

	/**
	 * The controller with this caller, or nobody.
	 *
	 * @param DocumentCopyService $service The copy double.
	 * @param string|null         $uid     The caller, or null.
	 * @param array<string, mixed> $params  The request body.
	 *
	 * @return DocumentCopyController The controller.
	 */
	private function controller(DocumentCopyService $service, ?string $uid, array $params = []): DocumentCopyController {
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

		return new DocumentCopyController(
			appName: 'filinq',
			request: $request,
			copies: $service,
			userSession: $session
		);

	}//end controller()
}//end class

<?php
/**
 * Unit tests for CaseSystemFileStatusController: the batched "In the case
 * system" lookup of the My Documents listing, and what it leaves out.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\CaseSystemFileStatusController;
use OCA\Filinq\Kernel\Contracts\AnonymizeRequestServiceInterface;
use OCA\Filinq\Service\CaseSystemDeliveredFiles;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(CaseSystemFileStatusController::class)]
class CaseSystemFileStatusControllerTest extends TestCase {

	private CaseSystemDeliveredFiles&MockObject $deliveries;

	private AnonymizeRequestServiceInterface&MockObject $access;

	private IUserSession&MockObject $session;

	protected function setUp(): void {
		parent::setUp();
		$this->deliveries = $this->createMock(CaseSystemDeliveredFiles::class);
		$this->access     = $this->createMock(AnonymizeRequestServiceInterface::class);
		$this->session    = $this->createMock(IUserSession::class);

	}//end setUp()

	private function controller(): CaseSystemFileStatusController {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $args=[]): string => vsprintf($text, $args));

		return new CaseSystemFileStatusController(
			'filinq',
			$this->createMock(IRequest::class),
			$this->deliveries,
			$this->access,
			$this->session,
			$l10n
		);

	}//end controller()

	public function testOnlyFilesTheCallerCanOpenAreLookedUp(): void {
		$this->session->method('getUser')->willReturn($this->createMock(IUser::class));
		$this->access->method('verifyFileAccess')->willReturnCallback(
			static fn (int $fileId): ?array => $fileId === 8 ? ['error' => 'No access'] : null
		);
		$this->deliveries->expects($this->once())->method('deliveredFileIds')
			->with([5, 9])
			->willReturn([9]);

		$response = $this->controller()->index('5,8,9,5,,x');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['delivered' => [9]], $response->getData());

	}//end testOnlyFilesTheCallerCanOpenAreLookedUp()

	public function testNobodySignedInIsRefused(): void {
		$this->session->method('getUser')->willReturn(null);
		$this->deliveries->expects($this->never())->method('deliveredFileIds');

		$response = $this->controller()->index('5');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testNobodySignedInIsRefused()

	public function testMoreThanAPageOfIdsIsRefused(): void {
		$this->session->method('getUser')->willReturn($this->createMock(IUser::class));
		$this->deliveries->expects($this->never())->method('deliveredFileIds');

		$response = $this->controller()->index(implode(',', range(1, 101)));

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'Ask about at most 100 documents at a time.'], $response->getData());

	}//end testMoreThanAPageOfIdsIsRefused()

	public function testTheLookupIsOpenToEverySignedInUser(): void {
		$attributes = (new ReflectionMethod(CaseSystemFileStatusController::class, 'index'))->getAttributes(NoAdminRequired::class);

		$this->assertCount(1, $attributes);

	}//end testTheLookupIsOpenToEverySignedInUser()
}//end class

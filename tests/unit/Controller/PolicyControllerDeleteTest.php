<?php

/**
 * Unit tests for PolicyController's prohibition-delete status mapping.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\PolicyController;
use OCA\DocuDesk\Service\PolicyCrudService;
use OCA\OpenRegister\Exception\ArchivalImmutableException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * `DELETE /api/policy/prohibitions/{id}` can never succeed.
 *
 * `publicationProhibition` declares `x-openregister-archival` (retention P10Y)
 * in `lib/Settings/docudesk_register.json`, and OpenRegister refuses every
 * user-driven delete on an archival schema — rows expire only via
 * `ArchivalRetentionTask`. The endpoint therefore answered HTTP 500 for a
 * condition that is permanent and fully known in advance, which is how the
 * Newman suite first surfaced it (`expected [200, 204, 404] to include 500`).
 *
 * These tests pin the honest answer: HTTP 409 Conflict with a body that names
 * retention, distinguishable from the 403 the permission gate raises. Verified
 * to FAIL (500) without the `ArchivalImmutableException` catch in
 * `PolicyController::deleteProhibition()`.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PolicyControllerDeleteTest extends TestCase {

	/**
	 * CRUD service double.
	 *
	 * @var PolicyCrudService|MockObject
	 */
	private PolicyCrudService|MockObject $mockCrudService;

	/**
	 * The controller under test.
	 *
	 * @var PolicyController
	 */
	private PolicyController $controller;

	/**
	 * Wire the controller over an authenticated session.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$mockRequest = $this->createMock(originalClassName: IRequest::class);
		$mockLogger = $this->createMock(originalClassName: LoggerInterface::class);
		$mockL10n = $this->createMock(originalClassName: IL10N::class);
		$mockL10n->method('t')->willReturnCallback(
			static function (string $text, array $params = []): string {
				return vsprintf($text, $params);
			}
		);

		$this->mockCrudService = $this->createMock(originalClassName: PolicyCrudService::class);

		$user = $this->createMock(originalClassName: IUser::class);
		$user->method('getUID')->willReturn('admin');
		$mockUserSession = $this->createMock(originalClassName: IUserSession::class);
		$mockUserSession->method('getUser')->willReturn($user);

		$this->controller = new PolicyController(
			'docudesk',
			$mockRequest,
			$mockLogger,
			$this->mockCrudService,
			$mockL10n,
			$mockUserSession
		);

	}//end setUp()

	/**
	 * An archival-immutable refusal answers 409, not 500.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/entity-publication-policies/spec.md
	 */
	public function testDeleteAnswers409WhenTheSchemaDeclaresArchivalRetention(): void {
		$this->mockCrudService->method('deleteProhibition')
			->willThrowException(
				new ArchivalImmutableException('publicationProhibition', 'delete')
			);

		$result = $this->controller->deleteProhibition('prohibition-uuid-1');

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertSame(
			expected: 409,
			actual: $result->getStatus(),
			message: 'A permanently impossible delete must not be reported as a server error.'
		);

		$body = $result->getData();
		$this->assertSame('SCHEMA_ARCHIVAL_IMMUTABLE', $body['error'] ?? null);
		$this->assertSame('publicationProhibition', $body['schema'] ?? null);
		$this->assertSame('delete', $body['operation'] ?? null);
		$this->assertSame('prohibition-uuid-1', $body['id'] ?? null);
		$this->assertStringContainsString(
			needle: 'retention',
			haystack: strtolower((string)($body['message'] ?? '')),
			message: 'The 409 body must name retention as the reason.'
		);

	}//end testDeleteAnswers409WhenTheSchemaDeclaresArchivalRetention()

	/**
	 * A genuine failure is still reported as 500.
	 *
	 * Positive control: without it, a controller that answered 409 for every
	 * exception would pass the test above for the wrong reason.
	 *
	 * @return void
	 */
	public function testDeleteStillAnswers500OnAnUnrelatedFailure(): void {
		$this->mockCrudService->method('deleteProhibition')
			->willThrowException(new \RuntimeException('database is on fire'));

		$result = $this->controller->deleteProhibition('prohibition-uuid-1');

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertSame(expected: 500, actual: $result->getStatus());

	}//end testDeleteStillAnswers500OnAnUnrelatedFailure()
}//end class

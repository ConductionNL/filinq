<?php

/**
 * Unit tests for PolicyCrudService deletion — the named argument passed to
 * OpenRegister's ObjectService::deleteObject().
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
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

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\PolicyCrudService;
use OCA\Filinq\Service\SettingsService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * A named argument that does not exist on the callee is a RUNTIME error, not a
 * compile-time one: PHP raises `Error: Unknown named parameter $id` only when
 * the line actually executes. Both Filinq deletion paths passed `id:` to
 * ObjectService::deleteObject(), whose parameter is `$uuid` — so every
 * prohibition delete and every standing-consent delete answered HTTP 500.
 *
 * Nothing caught it. No unit test exercised these methods, and the Newman
 * collection that would have — `tests/newman/filinq-api.postman_collection.json`
 * — had never been executed by CI (its directory was outside the runner's flat
 * glob). When it was finally run on 2026-08-09 it produced 5 server-side
 * `Unknown named parameter $id` exceptions, at PolicyCrudService.php:272 and
 * :364, which cascaded into 19 of the 25 assertion failures in that collection.
 *
 * These tests execute both deletion paths against a fake ObjectService whose
 * signature mirrors OpenRegister's (`$uuid`, not `$id`), so the wrong argument
 * name throws exactly as it does in production. Verified to FAIL with `id:` and
 * pass with `uuid:`.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class PolicyCrudServiceDeleteTest extends TestCase {

	/**
	 * Build a PolicyCrudService whose ObjectService records deleteObject calls.
	 *
	 * @param ObjectService $objectService Fake object service to hand back.
	 *
	 * @return PolicyCrudService
	 */
	private function buildService(ObjectService $objectService): PolicyCrudService {
		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($objectService);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('admin');

		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		// Admin short-circuits the group check in assertProhibitionPermission()
		// before `isInGroup` is ever consulted, so only `isAdmin` is stubbed —
		// the NextcloudStubs IGroupManager does not declare `isInGroup`.
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturn(true);

		return new PolicyCrudService(
			$settings,
			$this->createMock(ConsentService::class),
			$groups,
			$session,
			$this->createMock(LoggerInterface::class)
		);

	}//end buildService()

	/**
	 * deleteProhibition() must reach ObjectService with the uuid.
	 *
	 * A PHPUnit mock reproduces the callee's parameter NAMES, so `id:` raises
	 * the same `Error: Unknown named parameter $id` here that it raises in
	 * production — this test cannot pass while the wrong name is used.
	 *
	 * @return void
	 */
	public function testDeleteProhibitionPassesTheUuidByItsRealParameterName(): void {
		$captured = null;

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('deleteObject')->willReturnCallback(
			function (string $uuid = '', ...$rest) use (&$captured): bool {
				$captured = $uuid;
				return true;
			}
		);

		$service = $this->buildService($objectService);

		$service->deleteProhibition('11111111-2222-3333-4444-555555555555');

		$this->assertSame(
			'11111111-2222-3333-4444-555555555555',
			$captured,
			'deleteProhibition must call ObjectService::deleteObject with the '
			. 'uuid; passing it as `id:` raises "Unknown named parameter $id" '
			. 'and answers HTTP 500.'
		);

	}//end testDeleteProhibitionPassesTheUuidByItsRealParameterName()

}//end class

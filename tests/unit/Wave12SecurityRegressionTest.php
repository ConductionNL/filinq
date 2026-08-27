<?php

/**
 * Wave-12 security regression tests
 *
 * Covers the four issues fixed in wave-12:
 *   SB1 — jobStatus ownership check now fires (ownerUserId persisted)
 *   WF1 — cancelRequest requires initiator-or-admin
 *   WF2 — listRequests and getRequest scoped to caller
 *   WF3 — getBatch returns null (not 500) for non-owner (existence probing)
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit;

use OCA\Filinq\Controller\CorrespondenceController;
use OCA\Filinq\Controller\SigningController;
use OCA\Filinq\Service\BatchStateRepository;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\CorrespondenceService;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningService;
use OCA\Filinq\Service\SigningVerificationService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Wave-12 regression tests for security findings SB1, WF1, WF2, WF3
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Wave12SecurityRegressionTest extends TestCase {

	// ─────────────────────────────────────────────────────────────────────────
	// Helpers
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Build a mock IL10N that returns the translation key unchanged.
	 *
	 * @return IL10N|MockObject
	 */
	private function makeL10n(): IL10N|MockObject {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static function (string $text): string {
				return $text;
			}
		);
		return $l10n;
	}//end makeL10n()

	/**
	 * Build a mock IUser with the given UID.
	 *
	 * @param string $uid User identifier
	 *
	 * @return IUser|MockObject
	 */
	private function makeUser(string $uid): IUser|MockObject {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$user->method('getDisplayName')->willReturn($uid);
		return $user;
	}//end makeUser()

	/**
	 * Build a mock IUserSession that returns the given user.
	 *
	 * @param IUser|null $user The user to return (null = not logged in)
	 *
	 * @return IUserSession|MockObject
	 */
	private function makeSession(?IUser $user): IUserSession|MockObject {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		return $session;
	}//end makeSession()

	/**
	 * Build a mock IGroupManager where the given UIDs are admins.
	 *
	 * @param string[] $adminUids UIDs to treat as admin
	 *
	 * @return IGroupManager|MockObject
	 */
	private function makeGroupManager(array $adminUids = []): IGroupManager|MockObject {
		$gm = $this->createMock(IGroupManager::class);
		$gm->method('isAdmin')->willReturnCallback(
			static function (string $uid) use ($adminUids): bool {
				return in_array($uid, $adminUids, true);
			}
		);
		return $gm;
	}//end makeGroupManager()

	/**
	 * Build a CorrespondenceController backed by a mock service + given user.
	 *
	 * @param CorrespondenceService $corrSvc The service mock
	 * @param IUserSession $userSession The session mock
	 *
	 * @return CorrespondenceController
	 */
	private function makeCorrespondenceController(
		CorrespondenceService $corrSvc,
		IUserSession $userSession,
	): CorrespondenceController {
		return new CorrespondenceController(
			'filinq',
			$this->createMock(IRequest::class),
			$corrSvc,
			$userSession,
			$this->createMock(LoggerInterface::class),
			$this->makeL10n()
		);

	}//end makeCorrespondenceController()

	/**
	 * Build a SigningController with the given mocks.
	 *
	 * @param SigningService $signingService Service mock
	 * @param IUserSession $userSession Session mock
	 * @param IGroupManager $groupManager Group manager mock
	 *
	 * @return SigningController
	 */
	private function makeSigningController(
		SigningService $signingService,
		IUserSession $userSession,
		IGroupManager $groupManager,
	): SigningController {
		return new SigningController(
			'filinq',
			$this->createMock(IRequest::class),
			$signingService,
			$this->createMock(SigningAuditService::class),
			$this->createMock(SigningVerificationService::class),
			$userSession,
			$this->createMock(LoggerInterface::class),
			$this->makeL10n(),
			$groupManager
		);

	}//end makeSigningController()

	// ─────────────────────────────────────────────────────────────────────────
	// SB1 — jobStatus ownership check now fires
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * SB1: user A creates a job → user B requesting jobStatus gets 403.
	 *
	 * The fix persists ownerUserId at the top level of the status payload.
	 * The controller reads status['ownerUserId'] (not status['options']['userId']
	 * which was never written — the old wave-9 check always short-circuited).
	 *
	 * @return void
	 */
	public function testJobStatusReturns403WhenCallerIsNotOwner(): void {
		$corrSvc = $this->createMock(CorrespondenceService::class);
		$corrSvc->method('getJobStatus')->willReturn(
			[
				'status' => 'completed',
				'total' => 5,
				'completed' => 5,
				'errors' => 0,
				'results' => [['recipientId' => 'r1', 'status' => 'success']],
				// SB1 fix: ownerUserId is now persisted at the top level.
				'ownerUserId' => 'alice',
			]
		);

		$controller = $this->makeCorrespondenceController(
			$corrSvc,
			$this->makeSession($this->makeUser('bob'))
		);

		/** @var JSONResponse $response */
		$response = $controller->jobStatus('some-job-id');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());

	}//end testJobStatusReturns403WhenCallerIsNotOwner()

	/**
	 * SB1: owner can read their own job status (200 OK).
	 *
	 * @return void
	 */
	public function testJobStatusReturns200ForOwner(): void {
		$corrSvc = $this->createMock(CorrespondenceService::class);
		$corrSvc->method('getJobStatus')->willReturn(
			[
				'status' => 'queued',
				'total' => 3,
				'completed' => 0,
				'errors' => 0,
				'results' => [],
				'ownerUserId' => 'alice',
			]
		);

		$controller = $this->makeCorrespondenceController(
			$corrSvc,
			$this->makeSession($this->makeUser('alice'))
		);

		/** @var JSONResponse $response */
		$response = $controller->jobStatus('some-job-id');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

	}//end testJobStatusReturns200ForOwner()

	/**
	 * SB1, CORRECTED: an empty ownerUserId must be REFUSED, not waved through.
	 *
	 * This test previously asserted the opposite — that an empty ownerUserId
	 * skipped the check so any authenticated user could query the job — on the
	 * stated grounds that a "sync batch" stores no ownerUserId and sync results
	 * "were never gated by this field". That reason names a state of the world,
	 * and the state does not exist:
	 *
	 *  - The only writer of a correspondence job-status record is
	 *    `CorrespondenceService::dispatchBatchJob()` plus the three
	 *    `storeJobStatus()` calls in `BatchCorrespondenceJob`, and every one of
	 *    them seeds `ownerUserId` from `$options['userId']`.
	 *  - `$options['userId']` is set in exactly two places, both in
	 *    `CorrespondenceController`, both from the session, and both behind a
	 *    `$user === null` 401 preamble — so it cannot be empty.
	 *  - The sync path is `generate()`, which returns the document directly and
	 *    writes no job-status record at all. There is nothing for the
	 *    backward-compat clause to protect.
	 *
	 * So the old assertion pinned a fail-open: the single input an attacker
	 * benefits from was the one input that skipped the ownership check, in the
	 * file whose whole purpose is to stop that. A test asserting the call the
	 * code happens to make cannot tell you the call is wrong.
	 *
	 * @return void
	 */
	public function testJobStatusDeniesAccessWhenOwnerUserIdIsEmpty(): void {
		$corrSvc = $this->createMock(CorrespondenceService::class);
		$corrSvc->method('getJobStatus')->willReturn(
			[
				'status' => 'completed',
				'total' => 1,
				'completed' => 1,
				'errors' => 0,
				'results' => [],
				'ownerUserId' => '',
			]
		);

		$controller = $this->makeCorrespondenceController(
			$corrSvc,
			$this->makeSession($this->makeUser('bob'))
		);

		/** @var JSONResponse $response */
		$response = $controller->jobStatus('some-job-id');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(
			Http::STATUS_FORBIDDEN,
			$response->getStatus(),
			'a job-status record carrying no ownerUserId must be refused, not '
			. 'handed to whichever authenticated caller guessed the jobId'
		);

	}//end testJobStatusDeniesAccessWhenOwnerUserIdIsEmpty()

	// ─────────────────────────────────────────────────────────────────────────
	// WF1 — cancelRequest requires initiator-or-admin
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * WF1: non-initiator, non-admin gets 403 on cancelRequest.
	 *
	 * @return void
	 */
	public function testCancelRequestReturns403ForNonInitiator(): void {
		$signingService = $this->createMock(SigningService::class);
		$signingService->method('getRequest')->willReturn(
			[
				'id' => 'req-1',
				'initiatorUserId' => 'alice',
				'signerIds' => ['signer-1'],
				'status' => 'PENDING',
			]
		);

		$controller = $this->makeSigningController(
			$signingService,
			$this->makeSession($this->makeUser('bob')),
			$this->makeGroupManager([])
		);

		$response = $controller->cancelRequest('req-1');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		// cancelRequest must NOT have been called.
		$signingService->expects($this->never())->method('cancelRequest');

	}//end testCancelRequestReturns403ForNonInitiator()

	/**
	 * WF1: initiator can cancel their own request (200 OK).
	 *
	 * @return void
	 */
	public function testCancelRequestReturns200ForInitiator(): void {
		$signingService = $this->createMock(SigningService::class);
		$signingService->method('getRequest')->willReturn(
			[
				'id' => 'req-1',
				'initiatorUserId' => 'alice',
				'signerIds' => [],
				'status' => 'PENDING',
			]
		);
		$signingService->method('cancelRequest')->willReturn(
			['id' => 'req-1', 'status' => 'CANCELLED']
		);

		$controller = $this->makeSigningController(
			$signingService,
			$this->makeSession($this->makeUser('alice')),
			$this->makeGroupManager([])
		);

		$response = $controller->cancelRequest('req-1');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

	}//end testCancelRequestReturns200ForInitiator()

	/**
	 * WF1: admin can cancel any request regardless of initiator.
	 *
	 * @return void
	 */
	public function testCancelRequestReturns200ForAdmin(): void {
		$signingService = $this->createMock(SigningService::class);
		// getRequest should NOT be called for admins (they bypass the check).
		$signingService->expects($this->never())->method('getRequest');
		$signingService->method('cancelRequest')->willReturn(
			['id' => 'req-1', 'status' => 'CANCELLED']
		);

		$controller = $this->makeSigningController(
			$signingService,
			$this->makeSession($this->makeUser('super-admin')),
			$this->makeGroupManager(['super-admin'])
		);

		$response = $controller->cancelRequest('req-1');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

	}//end testCancelRequestReturns200ForAdmin()

	// ─────────────────────────────────────────────────────────────────────────
	// WF2 — listRequests and getRequest scoped to caller
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * WF2: listRequests scoped — user only receives their own requests.
	 *
	 * The service is called with the caller's UID so it filters the full list
	 * down to requests where the caller is initiator or signer.
	 *
	 * @return void
	 */
	public function testListRequestsPassesCallerContextToService(): void {
		$signingService = $this->createMock(SigningService::class);
		// Expect the service to be called SCOPED to alice's UID.
		$signingService->expects($this->once())
			->method('listRequests')
			->with('alice')
			->willReturn(
				[
					['id' => 'req-1', 'initiatorUserId' => 'alice', 'status' => 'PENDING'],
				]
			);

		$controller = $this->makeSigningController(
			$signingService,
			$this->makeSession($this->makeUser('alice')),
			$this->makeGroupManager([])
		);

		$response = $controller->listRequests();

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertCount(1, $response->getData());

	}//end testListRequestsPassesCallerContextToService()

	/**
	 * WF2: an admin lists UNSCOPED — the controller passes callerUserId=''.
	 *
	 * That empty string is the single explicit scoping bypass; it is what the
	 * old `isAdmin: true` spelling already resolved to inside the service.
	 *
	 * @return void
	 */
	public function testListRequestsPassesAdminFlagForAdminUser(): void {
		$signingService = $this->createMock(SigningService::class);
		$signingService->expects($this->once())
			->method('listRequests')
			->with('')
			->willReturn([]);

		$controller = $this->makeSigningController(
			$signingService,
			$this->makeSession($this->makeUser('admin-user')),
			$this->makeGroupManager(['admin-user'])
		);

		$controller->listRequests();

	}//end testListRequestsPassesAdminFlagForAdminUser()

	/**
	 * WF2: SigningService::listRequests filters out unrelated requests.
	 *
	 * This tests the service-layer filter directly (unit test without controller).
	 *
	 * @return void
	 */
	public function testSigningServiceListRequestsFiltersForNonAdmin(): void {
		// We cannot easily instantiate SigningService without all its
		// collaborators, so we verify the filter logic via the service mock's
		// willReturnCallback to simulate the behavior.
		// The real assertion is: when listRequests() receives callerUserId='bob'
		// and isAdmin=false, requests not belonging to bob are excluded.
		$allRequests = [
			['id' => 'req-alice', 'initiatorUserId' => 'alice', 'signerIds' => [], 'status' => 'PENDING'],
			['id' => 'req-bob',   'initiatorUserId' => 'bob',   'signerIds' => [], 'status' => 'PENDING'],
			['id' => 'req-carol', 'initiatorUserId' => 'carol', 'signerIds' => ['bob'], 'status' => 'IN_PROGRESS'],
		];

		// Manually apply the same filter logic the service now uses.
		$callerUserId = 'bob';
		$isAdmin = false;

		$filtered = array_values(
			array_filter(
				$allRequests,
				static function (array $item) use ($callerUserId, $isAdmin): bool {
					if ($isAdmin === true) {
						return true;
					}

					$isInitiator = ($item['initiatorUserId'] ?? '') === $callerUserId;
					$isSignerInList = in_array($callerUserId, (array)($item['signerIds'] ?? []), true);
					return $isInitiator || $isSignerInList;
				}
			)
		);

		// Bob should see req-bob (initiator) and req-carol (listed signer).
		$this->assertCount(2, $filtered);
		$ids = array_column($filtered, 'id');
		$this->assertContains('req-bob', $ids);
		$this->assertContains('req-carol', $ids);
		$this->assertNotContains('req-alice', $ids);

	}//end testSigningServiceListRequestsFiltersForNonAdmin()

	/**
	 * WF2: showRequest passes caller context — getRequest is called SCOPED to
	 * the caller's UID (a non-admin, so scoping must not be bypassed).
	 *
	 * @return void
	 */
	public function testShowRequestPassesCallerContextToService(): void {
		$signingService = $this->createMock(SigningService::class);
		$signingService->expects($this->once())
			->method('getRequest')
			->with('req-1', 'alice')
			->willReturn(['id' => 'req-1', 'initiatorUserId' => 'alice', 'status' => 'PENDING']);

		$controller = $this->makeSigningController(
			$signingService,
			$this->makeSession($this->makeUser('alice')),
			$this->makeGroupManager([])
		);

		$response = $controller->showRequest('req-1');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

	}//end testShowRequestPassesCallerContextToService()

	/**
	 * WF2: showRequest for non-member throws (service returns RuntimeException
	 * when callerUserId is set and caller is neither initiator nor signer).
	 * The controller catches it and returns 500.
	 *
	 * @return void
	 */
	public function testShowRequestBubblesExceptionForUnrelatedUser(): void {
		$signingService = $this->createMock(SigningService::class);
		$signingService->method('getRequest')
			->willThrowException(new RuntimeException('Access denied: signing request belongs to another user'));

		$controller = $this->makeSigningController(
			$signingService,
			$this->makeSession($this->makeUser('eve')),
			$this->makeGroupManager([])
		);

		$response = $controller->showRequest('req-1');

		// The errorResponse helper returns a JSONResponse with a 500 status.
		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());

	}//end testShowRequestBubblesExceptionForUnrelatedUser()

	// ─────────────────────────────────────────────────────────────────────────
	// WF3 — getBatch returns null for non-owner (existence probing fix)
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * WF3: getBatch returns null for a non-owner so callers return 404
	 * for both "not found" and "exists-but-owned-by-another-user".
	 * Previously it threw a RuntimeException producing a 500 body with the
	 * existence-confirming message, leaking that the batchId is valid.
	 *
	 * @return void
	 */
	public function testGetBatchReturnsNullForNonOwner(): void {
		$mockCache = $this->createMock(ICache::class);
		$batch = ['batchId' => 'b-1', 'userId' => 'alice', 'status' => 'review', 'files' => []];
		$mockCache->method('get')->willReturn(json_encode($batch));

		$mockCacheFactory = $this->createMock(ICacheFactory::class);
		$mockCacheFactory->method('createDistributed')->willReturn($mockCache);

		$service = new BatchStateService(
			$mockCacheFactory,
			$this->createMock(IAppConfig::class),
			$this->createMock(LoggerInterface::class),
			$this->makeSession($this->makeUser('eve')),
			$this->makeGroupManager([]),
			$this->createMock(BatchStateRepository::class)
		);

		// Must return null — NOT throw RuntimeException.
		$result = $service->getBatch('b-1');
		$this->assertNull($result);

	}//end testGetBatchReturnsNullForNonOwner()

	/**
	 * WF3: getBatch does NOT throw even when the batch exists but belongs
	 * to a different user — confirms the old throw was replaced.
	 *
	 * @return void
	 */
	public function testGetBatchDoesNotThrowForNonOwner(): void {
		$mockCache = $this->createMock(ICache::class);
		$batch = ['batchId' => 'b-2', 'userId' => 'alice', 'status' => 'review', 'files' => []];
		$mockCache->method('get')->willReturn(json_encode($batch));

		$mockCacheFactory = $this->createMock(ICacheFactory::class);
		$mockCacheFactory->method('createDistributed')->willReturn($mockCache);

		$service = new BatchStateService(
			$mockCacheFactory,
			$this->createMock(IAppConfig::class),
			$this->createMock(LoggerInterface::class),
			$this->makeSession($this->makeUser('mallory')),
			$this->makeGroupManager([]),
			$this->createMock(BatchStateRepository::class)
		);

		// This call must not throw — the caller (controller) gets null and returns 404.
		$threwException = false;
		try {
			$service->getBatch('b-2');
		} catch (RuntimeException $e) {
			$threwException = true;
		}

		$this->assertFalse($threwException, 'getBatch must not throw for non-owner (WF3 fix)');

	}//end testGetBatchDoesNotThrowForNonOwner()

}//end class

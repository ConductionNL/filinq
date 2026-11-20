<?php

/**
 * Unit tests for the batch listings and the state version of BatchStateService and BatchStateRepository.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-durable-batch-state-as-or-objects-req-ddras-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BatchStateRepository;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\OpenRegisterAvailabilityService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Who sees which batches, which batches the coordinator picks up, and the state version.
 *
 * @covers \OCA\Filinq\Service\BatchStateService
 * @covers \OCA\Filinq\Service\BatchStateRepository
 */
class BatchStateListingTest extends TestCase {

	/**
	 * Repository double.
	 *
	 * @var BatchStateRepository&MockObject
	 */
	private BatchStateRepository&MockObject $repository;

	/**
	 * Cache double.
	 *
	 * @var ICache&MockObject
	 */
	private ICache&MockObject $cache;

	/**
	 * Session double.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $session;

	/**
	 * Group manager double.
	 *
	 * @var IGroupManager&MockObject
	 */
	private IGroupManager&MockObject $groups;

	/**
	 * Subject.
	 *
	 * @var BatchStateService
	 */
	private BatchStateService $service;

	/**
	 * Build the subject.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->repository = $this->createMock(BatchStateRepository::class);
		$this->cache = $this->createMock(ICache::class);
		$this->session = $this->createMock(IUserSession::class);
		$this->groups = $this->createMock(IGroupManager::class);
		$factory = $this->createMock(ICacheFactory::class);
		$factory->method('createDistributed')->willReturn($this->cache);
		$this->service = new BatchStateService(
			cacheFactory: $factory,
			appConfig: $this->createMock(IAppConfig::class),
			logger: $this->createMock(LoggerInterface::class),
			userSession: $this->session,
			groupManager: $this->groups,
			repository: $this->repository
		);

	}//end setUp()

	/**
	 * Log a user in.
	 *
	 * @param string $uid     The user id.
	 * @param bool   $isAdmin Whether the user is an admin.
	 *
	 * @return void
	 */
	private function loginAs(string $uid, bool $isAdmin): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$this->session->method('getUser')->willReturn($user);
		$this->groups->method('isAdmin')->with($uid)->willReturn($isAdmin);

	}//end loginAs()

	/**
	 * A user lists only their own batches, filtered in the query and again on the result.
	 *
	 * @return void
	 */
	public function testAUserListsOnlyTheirOwnBatches(): void {
		$this->loginAs(uid: 'alice', isAdmin: false);
		$this->repository->expects($this->once())->method('findAll')->with(['userId' => 'alice'])->willReturn(
			[['batchId' => 'a', 'userId' => 'alice'], ['batchId' => 'b', 'userId' => 'bob']]
		);

		$this->assertSame([['batchId' => 'a', 'userId' => 'alice']], $this->service->listBatches());

	}//end testAUserListsOnlyTheirOwnBatches()

	/**
	 * An admin lists every batch.
	 *
	 * @return void
	 */
	public function testAnAdminListsEveryBatch(): void {
		$this->loginAs(uid: 'root', isAdmin: true);
		$rows = [['batchId' => 'a', 'userId' => 'alice'], ['batchId' => 'b', 'userId' => 'bob']];
		$this->repository->expects($this->once())->method('findAll')->with([])->willReturn($rows);

		$this->assertSame($rows, $this->service->listBatches());

	}//end testAnAdminListsEveryBatch()

	/**
	 * Without a session nothing is listed.
	 *
	 * @return void
	 */
	public function testNoSessionListsNothing(): void {
		$this->session->method('getUser')->willReturn(null);
		$this->repository->expects($this->never())->method('findAll');

		$this->assertSame([], $this->service->listBatches());

	}//end testNoSessionListsNothing()

	/**
	 * The coordinator gets the background batches with work left, and nothing else.
	 *
	 * @return void
	 */
	public function testTheCoordinatorGetsActiveBackgroundBatchesOnly(): void {
		$this->repository->method('findAll')->with(['background' => true])->willReturn(
			[
				['batchId' => '1', 'background' => true, 'status' => 'extracting'],
				['batchId' => '2', 'background' => true, 'status' => 'review'],
				['batchId' => '3', 'background' => true, 'status' => 'anonymizing'],
				['batchId' => '4', 'background' => false, 'status' => 'anonymizing'],
				['batchId' => '5', 'background' => true, 'status' => 'cancelling'],
				['batchId' => '6', 'background' => true, 'status' => 'completed'],
			]
		);

		$this->assertSame(['1', '3', '5'], array_column($this->service->listActiveBackground(), 'batchId'));

	}//end testTheCoordinatorGetsActiveBackgroundBatchesOnly()

	/**
	 * Every write raises the state version and drops the cached copy before the store is written.
	 *
	 * @return void
	 */
	public function testAWriteRaisesTheVersionAndInvalidatesTheCache(): void {
		$calls = [];
		$this->cache->method('remove')->willReturnCallback(
			static function (string $key) use (&$calls): bool {
				$calls[] = 'remove ' . $key;
				return true;
			}
		);
		$this->repository->method('save')->willReturnCallback(
			static function (string $batchId, array $batch) use (&$calls): void {
				$calls[] = 'save v' . $batch['stateVersion'];
			}
		);
		$this->cache->method('set')->willReturnCallback(
			static function (string $key, mixed $value) use (&$calls): bool {
				$calls[] = 'set v' . json_decode((string)$value, true)['stateVersion'];
				return true;
			}
		);

		$this->service->updateBatch('b-1', ['batchId' => 'b-1', 'status' => 'extracting', 'stateVersion' => 4, 'files' => []]);

		$this->assertSame(['remove filinq_batch_b-1', 'save v5', 'set v5'], $calls);

	}//end testAWriteRaisesTheVersionAndInvalidatesTheCache()

	/**
	 * The repository lists in creation order, maps `documents` back to `files`, and caps the read.
	 *
	 * @return void
	 */
	public function testTheRepositoryListsInCreationOrder(): void {
		$objects = $this->createMock(ObjectService::class);
		$objects->expects($this->once())->method('searchObjectsBySlug')
			->with('filinq', 'anonymizationBatch', ['userId' => 'alice', '_limit' => 500], false, false)
			->willReturn(
				[
					['batchId' => 'late', 'createdAt' => 20, 'documents' => [['fileId' => 2]]],
					['batchId' => 'early', 'createdAt' => 10, 'documents' => []],
					['createdAt' => 5],
				]
			);
		$availability = $this->createMock(OpenRegisterAvailabilityService::class);
		$availability->method('isInstalled')->willReturn(true);
		$availability->method('getObjectService')->willReturn($objects);
		$repository = new BatchStateRepository(openRegister: $availability, logger: $this->createMock(LoggerInterface::class));

		$rows = $repository->findAll(filters: ['userId' => 'alice']);

		$this->assertSame(['early', 'late'], array_column($rows, 'batchId'));
		$this->assertSame([['fileId' => 2]], $rows[1]['files']);
		$this->assertArrayNotHasKey('documents', $rows[1]);

	}//end testTheRepositoryListsInCreationOrder()

	/**
	 * Without OpenRegister the repository refuses to list rather than answering "no batches".
	 *
	 * @return void
	 */
	public function testTheRepositoryRefusesToListWithoutOpenRegister(): void {
		$availability = $this->createMock(OpenRegisterAvailabilityService::class);
		$availability->method('isInstalled')->willReturn(false);
		$repository = new BatchStateRepository(openRegister: $availability, logger: $this->createMock(LoggerInterface::class));

		$this->expectException(RuntimeException::class);
		$repository->findAll();

	}//end testTheRepositoryRefusesToListWithoutOpenRegister()
}//end class

<?php

/**
 * Unit tests for MergeJobRepository
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

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use ArrayIterator;
use OCA\Filinq\Exception\MergeJobStoreUnreadableException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\MergeJobRepository;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Merge jobs are stored in the filinq/mergeJob schema, read back with their
 * uuid, and an unreadable store is never mistaken for an empty one.
 */
#[CoversClass(MergeJobRepository::class)]
#[UsesClass(MergeJobStoreUnreadableException::class)]
class MergeJobRepositoryTest extends TestCase {

	/**
	 * The OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService $objects;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * The subject.
	 *
	 * @var MergeJobRepository
	 */
	private MergeJobRepository $repository;

	/**
	 * Build the subject over a mocked object service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(ObjectService::class);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->repository = new MergeJobRepository(objectResolver: $resolver, logger: $this->logger);
	}//end setUp()

	/**
	 * Create drops any caller-supplied uuid and returns the stored payload with its uuid.
	 *
	 * @return void
	 */
	public function testCreateStoresWithoutAUuid(): void {
		$this->objects->expects($this->once())
			->method('saveObject')
			->with(['status' => 'queued', 'inputs' => [1, 2]], 'filinq', 'mergeJob')
			->willReturn(['uuid' => 'new-1', 'object' => ['status' => 'queued', 'inputs' => [1, 2]]]);

		$job = $this->repository->create(job: ['status' => 'queued', 'inputs' => [1, 2], 'uuid' => 'forged']);

		$this->assertSame(['status' => 'queued', 'inputs' => [1, 2], 'uuid' => 'new-1'], $job);
	}//end testCreateStoresWithoutAUuid()

	/**
	 * Save passes the uuid through, and falls back to it when the store returns none.
	 *
	 * @return void
	 */
	public function testSaveUpdatesTheNamedJob(): void {
		$this->objects->expects($this->once())
			->method('saveObject')
			->with(['status' => 'done'], 'filinq', 'mergeJob', 'job-7')
			->willReturn(['status' => 'done']);

		$job = $this->repository->save(job: ['status' => 'done'], uuid: 'job-7');

		$this->assertSame(['status' => 'done', 'uuid' => 'job-7'], $job);
	}//end testSaveUpdatesTheNamedJob()

	/**
	 * Save with an empty uuid creates a new job instead.
	 *
	 * @return void
	 */
	public function testSaveWithoutAUuidCreates(): void {
		$this->objects->expects($this->once())
			->method('saveObject')
			->with(['status' => 'queued'], 'filinq', 'mergeJob')
			->willReturn(['@self' => ['id' => 'self-1'], 'status' => 'queued']);

		$job = $this->repository->save(job: ['status' => 'queued'], uuid: '');

		$this->assertSame(['@self' => ['id' => 'self-1'], 'status' => 'queued', 'uuid' => 'self-1'], $job);
	}//end testSaveWithoutAUuidCreates()

	/**
	 * A store failure while writing is wrapped with the cause.
	 *
	 * @return void
	 */
	public function testAWriteFailureIsWrapped(): void {
		$cause = new \RuntimeException('db down');
		$this->objects->method('saveObject')->willThrowException($cause);

		try {
			$this->repository->create(job: ['status' => 'queued']);
			$this->fail('Expected a RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('Could not store the merge job: db down', $e->getMessage());
			$this->assertSame($cause, $e->getPrevious());
		}
	}//end testAWriteFailureIsWrapped()

	/**
	 * Find with an empty uuid answers null without asking the store.
	 *
	 * @return void
	 */
	public function testFindWithAnEmptyUuidIsNull(): void {
		$this->objects->expects($this->never())->method('find');

		$this->assertNull($this->repository->find(uuid: ''));
	}//end testFindWithAnEmptyUuidIsNull()

	/**
	 * Find reads the job from the merge schema and keeps @self.
	 *
	 * @return void
	 */
	public function testFindReturnsTheStoredJob(): void {
		$entity = new class {
			public function jsonSerialize(): array {
				return ['@self' => ['id' => 'job-1'], 'status' => 'running'];
			}
		};
		$this->objects->expects($this->once())
			->method('find')
			->with('job-1', 'filinq', 'mergeJob')
			->willReturn($entity);

		$this->assertSame(
			['@self' => ['id' => 'job-1'], 'status' => 'running', 'uuid' => 'job-1'],
			$this->repository->find(uuid: 'job-1')
		);
	}//end testFindReturnsTheStoredJob()

	/**
	 * A missing job is null.
	 *
	 * @return void
	 */
	public function testFindOfAMissingJobIsNull(): void {
		$this->objects->method('find')->willReturn(null);

		$this->assertNull($this->repository->find(uuid: 'nope'));
	}//end testFindOfAMissingJobIsNull()

	/**
	 * A failed read raises the unreadable-store exception and logs a warning.
	 *
	 * @return void
	 */
	public function testAFailedReadIsNotAMissingJob(): void {
		$this->objects->method('find')->willThrowException(new \RuntimeException('timeout'));
		$this->logger->expects($this->once())
			->method('warning')
			->with('[MergeJobRepository] could not read one merge job', $this->callback(
				static fn (array $context): bool => $context['uuid'] === 'job-2' && $context['error'] === 'timeout'
			));

		$this->expectException(MergeJobStoreUnreadableException::class);
		$this->expectExceptionMessage('The merge job could not be read: timeout');
		$this->repository->find(uuid: 'job-2');
	}//end testAFailedReadIsNotAMissingJob()

	/**
	 * The queue is read by slug with the queued filter, one job per row.
	 *
	 * @return void
	 */
	public function testFindQueuedReadsTheQueueBySlug(): void {
		$this->objects->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'mergeJob', ['status' => 'queued'])
			->willReturn([
				['uuid' => 'a', 'object' => ['status' => 'queued']],
				['id' => 'b', 'status' => 'queued'],
			]);
		$this->objects->expects($this->never())->method('searchObjects');

		$this->assertSame(
			[['status' => 'queued', 'uuid' => 'a'], ['id' => 'b', 'status' => 'queued', 'uuid' => 'b']],
			$this->repository->findQueued()
		);
	}//end testFindQueuedReadsTheQueueBySlug()

	/**
	 * A non-array search answer is an empty queue.
	 *
	 * @return void
	 */
	public function testANonArrayQueueAnswerIsEmpty(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn(null);

		$this->assertSame([], $this->repository->findQueued());
	}//end testANonArrayQueueAnswerIsEmpty()

	/**
	 * An unreadable queue raises instead of reporting an empty one.
	 *
	 * @return void
	 */
	public function testAnUnreadableQueueIsNotEmpty(): void {
		$cause = new \RuntimeException('no register');
		$this->objects->method('searchObjectsBySlug')->willThrowException($cause);
		$this->logger->expects($this->once())->method('warning')
			->with('[MergeJobRepository] could not read the merge queue', $this->anything());

		try {
			$this->repository->findQueued();
			$this->fail('Expected MergeJobStoreUnreadableException');
		} catch (MergeJobStoreUnreadableException $e) {
			$this->assertSame('The merge queue could not be read: no register', $e->getMessage());
			$this->assertSame($cause, $e->getPrevious());
		}
	}//end testAnUnreadableQueueIsNotEmpty()
}//end class

<?php

/**
 * Unit tests for DocumentReviewRepository.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Review
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
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Review\DocumentReviewRepository;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Reads, writes and deletes `documentReview` objects.
 *
 * @covers \OCA\Filinq\Service\Review\DocumentReviewRepository
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentReviewRepositoryTest extends TestCase {

	/**
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The repository over the doubles.
	 *
	 * @return DocumentReviewRepository
	 */
	private function repository(): DocumentReviewRepository {
		$this->objects = $this->createMock(ObjectService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new DocumentReviewRepository(
			objectResolver: new DocumentObjectServiceResolver(container: $container, appManager: $apps),
			logger: $this->logger
		);
	}//end repository()

	/**
	 * The lookup filters by fileId, skips other files' rows and returns the latest check.
	 *
	 * @return void
	 */
	public function testFindForReturnsTheLatestMarkOfTheFile(): void {
		$repository = $this->repository();
		$this->objects->expects($this->once())->method('searchObjectsBySlug')
			->with('filinq', 'documentReview', ['fileId' => 7])
			->willReturn(
				[
					['id' => 'a', 'fileId' => 7, 'checkedOn' => '2026-11-01T10:00:00+00:00'],
					['id' => 'b', 'fileId' => 7, 'checkedOn' => '2026-11-02T10:00:00+00:00'],
					['id' => 'c', 'fileId' => 8, 'checkedOn' => '2026-12-01T10:00:00+00:00'],
				]
			);

		$mark = $repository->findFor(fileId: 7);

		$this->assertSame('b', $mark['uuid']);
		$this->assertSame('2026-11-02T10:00:00+00:00', $mark['checkedOn']);
	}//end testFindForReturnsTheLatestMarkOfTheFile()

	/**
	 * No rows, a non-array answer or a failing read all mean no mark.
	 *
	 * @return void
	 */
	public function testFindForReturnsNullWhenThereIsNoMark(): void {
		$repository = $this->repository();
		$this->objects->method('searchObjectsBySlug')->willReturnOnConsecutiveCalls([], 0, $this->throwException(new RuntimeException('down')));
		$this->logger->expects($this->once())->method('warning');

		$this->assertNull($repository->findFor(fileId: 7));
		$this->assertNull($repository->findFor(fileId: 7));
		$this->assertNull($repository->findFor(fileId: 7));
	}//end testFindForReturnsNullWhenThereIsNoMark()

	/**
	 * Save writes to the documentReview schema with RBAC on and returns the stored fields.
	 *
	 * @return void
	 */
	public function testSave(): void {
		$repository = $this->repository();
		$this->objects->expects($this->once())->method('saveObject')
			->with(['fileId' => 7], 'filinq', 'documentReview')
			->willReturn(['id' => 'u-1', 'object' => ['fileId' => 7]]);

		$this->assertSame(['fileId' => 7, 'uuid' => 'u-1'], $repository->save(mark: ['fileId' => 7]));
	}//end testSave()

	/**
	 * A failing write is reported, not swallowed.
	 *
	 * @return void
	 */
	public function testSaveFailureThrows(): void {
		$repository = $this->repository();
		$this->objects->method('saveObject')->willThrowException(new RuntimeException('forbidden'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The review mark could not be recorded: forbidden');
		$repository->save(mark: ['fileId' => 7]);
	}//end testSaveFailureThrows()

	/**
	 * Delete removes by uuid past RBAC; a refusal or a failure throws.
	 *
	 * @return void
	 */
	public function testDelete(): void {
		$repository = $this->repository();
		$this->objects->expects($this->exactly(3))->method('deleteObject')
			->with('u-1', 'filinq', 'documentReview', false)
			->willReturnOnConsecutiveCalls(true, false, $this->throwException(new RuntimeException('gone')));

		$repository->delete(uuid: 'u-1');

		try {
			$repository->delete(uuid: 'u-1');
			$this->fail('a refused delete must throw');
		} catch (RuntimeException $e) {
			$this->assertSame('OpenRegister did not remove the review mark u-1.', $e->getMessage());
		}

		$this->expectExceptionMessage('The review mark could not be removed: gone');
		$repository->delete(uuid: 'u-1');
	}//end testDelete()
}//end class

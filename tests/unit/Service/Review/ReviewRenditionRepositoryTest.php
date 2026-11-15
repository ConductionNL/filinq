<?php

/**
 * Unit tests for ReviewRenditionRepository.
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
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Review\ReviewRenditionRepository;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Reads and replaces the `reviewRendition` object of a source file.
 *
 * @covers \OCA\Filinq\Service\Review\ReviewRenditionRepository
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ReviewRenditionRepositoryTest extends TestCase {

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
	 * @return ReviewRenditionRepository
	 */
	private function repository(): ReviewRenditionRepository {
		$this->objects = $this->createMock(ObjectService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new ReviewRenditionRepository(
			objectResolver: new DocumentObjectServiceResolver(container: $container, appManager: $apps),
			logger: $this->logger
		);
	}//end repository()

	/**
	 * The lookup filters by source and answers that source's row only.
	 *
	 * @return void
	 */
	public function testFindForAnswersTheSourcesRendition(): void {
		$repository = $this->repository();
		$this->objects->expects($this->once())->method('searchObjectsBySlug')
			->with('filinq', 'reviewRendition', ['sourceFileId' => 7])
			->willReturn(
				[
					['id' => 'other', 'sourceFileId' => 8, 'sha256' => 'x'],
					['id' => 'mine', 'sourceFileId' => 7, 'sha256' => 'a1'],
				]
			);

		$this->assertSame(['id' => 'mine', 'sourceFileId' => 7, 'sha256' => 'a1', 'uuid' => 'mine'], $repository->findFor(sourceFileId: 7));
	}//end testFindForAnswersTheSourcesRendition()

	/**
	 * No row, or a failing read, answers null; the failure is logged.
	 *
	 * @return void
	 */
	public function testFindForAnswersNullWithoutARendition(): void {
		$repository = $this->repository();
		$this->objects->method('searchObjectsBySlug')->willReturnOnConsecutiveCalls([], $this->throwException(new RuntimeException('down')));
		$this->logger->expects($this->once())->method('warning');

		$this->assertNull($repository->findFor(sourceFileId: 7));
		$this->assertNull($repository->findFor(sourceFileId: 7));
	}//end testFindForAnswersNullWithoutARendition()

	/**
	 * A first rendition is created past RBAC with the system as owner.
	 *
	 * @return void
	 */
	public function testSaveForCreatesServerOnly(): void {
		$repository = $this->repository();
		$this->objects->method('searchObjectsBySlug')->willReturn([]);
		$this->objects->expects($this->once())->method('saveObject')
			->with(
				['sourceFileId' => 7, 'fileId' => 9, 'sha256' => 'a1'],
				$this->callback(callback: static fn (ObjectSaveOptions $options): bool => $options->target->register === 'filinq'
						&& $options->target->schema === 'reviewRendition'
						&& $options->uuid === null
						&& $options->actor->unowned === true
				),
				false
			)
			->willReturn(['id' => 'r-1', 'object' => ['sourceFileId' => 7, 'fileId' => 9, 'sha256' => 'a1']]);

		$this->assertSame(
			['sourceFileId' => 7, 'fileId' => 9, 'sha256' => 'a1', 'uuid' => 'r-1'],
			$repository->saveFor(sourceFileId: 7, rendition: ['fileId' => 9, 'sha256' => 'a1', 'uuid' => 'stale'])
		);
	}//end testSaveForCreatesServerOnly()

	/**
	 * A later rendition replaces the stored one by its uuid.
	 *
	 * @return void
	 */
	public function testSaveForReplacesTheStoredRendition(): void {
		$repository = $this->repository();
		$this->objects->method('searchObjectsBySlug')->willReturn([['id' => 'r-1', 'sourceFileId' => 7, 'sha256' => 'a1']]);
		$this->objects->expects($this->once())->method('saveObject')
			->with(
				['sourceFileId' => 7, 'sha256' => 'b2'],
				$this->callback(callback: static fn (ObjectSaveOptions $options): bool => $options->target->register === 'filinq'
						&& $options->target->schema === 'reviewRendition'
						&& $options->uuid === 'r-1'
						&& $options->actor->unowned === true
				),
				false
			)
			->willReturn(['id' => 'r-1', 'object' => ['sourceFileId' => 7, 'sha256' => 'b2']]);

		$this->assertSame('b2', $repository->saveFor(sourceFileId: 7, rendition: ['sha256' => 'b2'])['sha256']);
	}//end testSaveForReplacesTheStoredRendition()

	/**
	 * A refused write is reported, not swallowed.
	 *
	 * @return void
	 */
	public function testSaveForFailureThrows(): void {
		$repository = $this->repository();
		$this->objects->method('searchObjectsBySlug')->willReturn([]);
		$this->objects->method('saveObject')->willThrowException(new RuntimeException('invalid'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The review rendition could not be recorded: invalid');
		$repository->saveFor(sourceFileId: 7, rendition: ['sha256' => 'a1']);
	}//end testSaveForFailureThrows()
}//end class

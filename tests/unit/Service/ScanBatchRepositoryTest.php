<?php

/**
 * Unit tests for ScanBatchRepository
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use JsonSerializable;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\ScanBatchRepository;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The repository writes and reads `scanBatch` rows in the `filinq` register
 * through OpenRegister's ObjectService, and turns read failures into "none".
 *
 * @covers \OCA\Filinq\Service\ScanBatchRepository
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class ScanBatchRepositoryTest extends TestCase {

	/**
	 * OpenRegister's object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objectService;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Build the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objectService = $this->createMock(ObjectService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * A repository whose resolver finds OpenRegister installed, or not.
	 *
	 * @param bool $installed Whether OpenRegister is installed.
	 *
	 * @return ScanBatchRepository The repository.
	 */
	private function repository(bool $installed = true): ScanBatchRepository {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn($installed === true ? ['openregister'] : ['files']);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\OpenRegister\Service\ObjectService')->willReturn($this->objectService);

		return new ScanBatchRepository(
			objectResolver: new DocumentObjectServiceResolver(container: $container, appManager: $appManager),
			logger: $this->logger
		);

	}//end repository()

	/**
	 * A new batch is created without a uuid argument, and its own uuid is dropped.
	 *
	 * @return void
	 */
	public function testSaveCreatesWithoutUuid(): void {
		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(['file' => 12, 'status' => 'pending'], 'filinq', 'scanBatch', null)
			->willReturn(['@self' => ['id' => 'b-1'], 'file' => 12, 'status' => 'pending']);

		$stored = $this->repository()->save(['uuid' => 'stale', 'file' => 12, 'status' => 'pending']);

		$this->assertSame(
			['@self' => ['id' => 'b-1'], 'file' => 12, 'status' => 'pending', 'uuid' => 'b-1'],
			$stored
		);

	}//end testSaveCreatesWithoutUuid()

	/**
	 * An update passes the uuid and reads the payload under `object`.
	 *
	 * @return void
	 */
	public function testSaveUpdatesUnderTheGivenUuid(): void {
		$entity = new class implements JsonSerializable {
			/**
			 * The serialised row.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['object' => ['status' => 'split', 'uuid' => 'b-2'], 'id' => 99];
			}//end jsonSerialize()
		};

		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(['status' => 'split'], 'filinq', 'scanBatch', 'b-2')
			->willReturn($entity);

		$this->assertSame(['status' => 'split', 'uuid' => 'b-2'], $this->repository()->save(['status' => 'split'], 'b-2'));

	}//end testSaveUpdatesUnderTheGivenUuid()

	/**
	 * A stored row without any id keeps the uuid it was written under.
	 *
	 * @return void
	 */
	public function testSaveFallsBackToTheGivenUuid(): void {
		$this->objectService->expects($this->once())->method('saveObject')->willReturn(['status' => 'done']);

		$this->assertSame(['status' => 'done', 'uuid' => 'b-3'], $this->repository()->save(['status' => 'done'], 'b-3'));

	}//end testSaveFallsBackToTheGivenUuid()

	/**
	 * A create whose result carries no array has no uuid to fall back on.
	 *
	 * @return void
	 */
	public function testSaveOfANonArrayResultWithoutUuidIsEmpty(): void {
		$this->objectService->expects($this->once())->method('saveObject')->willReturn('nonsense');

		$this->assertSame([], $this->repository()->save(['status' => 'done']));

	}//end testSaveOfANonArrayResultWithoutUuidIsEmpty()

	/**
	 * A failing write is rethrown with its cause.
	 *
	 * @return void
	 */
	public function testSaveFailureIsRethrown(): void {
		$cause = new RuntimeException('db down');
		$this->objectService->expects($this->once())->method('saveObject')->willThrowException($cause);

		try {
			$this->repository()->save(['status' => 'x']);
			$this->fail('Expected a RuntimeException.');
		} catch (RuntimeException $e) {
			$this->assertSame('Could not store the scan batch: db down', $e->getMessage());
			$this->assertSame($cause, $e->getPrevious());
		}

	}//end testSaveFailureIsRethrown()

	/**
	 * Without OpenRegister the write fails too.
	 *
	 * @return void
	 */
	public function testSaveWithoutOpenRegisterFails(): void {
		$this->objectService->expects($this->never())->method('saveObject');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Could not store the scan batch: OpenRegister service is not available.');

		$this->repository(installed: false)->save(['status' => 'x']);

	}//end testSaveWithoutOpenRegisterFails()

	/**
	 * An empty uuid is never looked up.
	 *
	 * @return void
	 */
	public function testFindEmptyUuidIsNull(): void {
		$this->objectService->expects($this->never())->method('find');

		$this->assertNull($this->repository()->find(''));

	}//end testFindEmptyUuidIsNull()

	/**
	 * A found batch keeps `@self` and gets its uuid.
	 *
	 * @return void
	 */
	public function testFindReturnsTheBatch(): void {
		$this->objectService->expects($this->once())
			->method('find')
			->with('b-4', 'filinq', 'scanBatch')
			->willReturn(['@self' => ['id' => 'b-4'], 'status' => 'pending']);

		$this->assertSame(
			['@self' => ['id' => 'b-4'], 'status' => 'pending', 'uuid' => 'b-4'],
			$this->repository()->find('b-4')
		);

	}//end testFindReturnsTheBatch()

	/**
	 * An unknown uuid is null.
	 *
	 * @return void
	 */
	public function testFindUnknownIsNull(): void {
		$this->objectService->expects($this->once())->method('find')->willReturn(null);
		$this->logger->expects($this->never())->method('warning');

		$this->assertNull($this->repository()->find('b-5'));

	}//end testFindUnknownIsNull()

	/**
	 * A failing read is logged and answered with null.
	 *
	 * @return void
	 */
	public function testFindFailureIsLoggedAndNull(): void {
		$this->objectService->expects($this->once())->method('find')->willThrowException(new RuntimeException('gone'));
		$this->logger->expects($this->once())
			->method('warning')
			->with(
				'[ScanBatchRepository] could not read one scan batch',
				$this->callback(
					static fn (array $context): bool => $context['uuid'] === 'b-6' && $context['error'] === 'gone'
				)
			);

		$this->assertNull($this->repository()->find('b-6'));

	}//end testFindFailureIsLoggedAndNull()

	/**
	 * A file id that is not positive is never looked up.
	 *
	 * @return void
	 */
	public function testFindByFileRejectsNonPositiveIds(): void {
		$this->objectService->expects($this->never())->method('searchObjectsBySlug');

		$this->assertNull($this->repository()->findByFile(0));
		$this->assertNull($this->repository()->findByFile(-3));

	}//end testFindByFileRejectsNonPositiveIds()

	/**
	 * The batch of a file is searched by slug and the first row is returned.
	 *
	 * @return void
	 */
	public function testFindByFileReturnsTheFirstRow(): void {
		$this->objectService->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'scanBatch', ['file' => 42])
			->willReturn([['id' => 'b-7', 'file' => 42], ['id' => 'b-8', 'file' => 42]]);

		$this->assertSame(['id' => 'b-7', 'file' => 42, 'uuid' => 'b-7'], $this->repository()->findByFile(42));

	}//end testFindByFileReturnsTheFirstRow()

	/**
	 * No rows, or a count instead of rows, mean the file is no batch.
	 *
	 * @return void
	 */
	public function testFindByFileWithoutRowsIsNull(): void {
		$this->objectService->expects($this->exactly(2))
			->method('searchObjectsBySlug')
			->willReturnOnConsecutiveCalls([], 3);

		$repository = $this->repository();
		$this->assertNull($repository->findByFile(42));
		$this->assertNull($repository->findByFile(42));

	}//end testFindByFileWithoutRowsIsNull()

	/**
	 * A failing search is logged and answered with null.
	 *
	 * @return void
	 */
	public function testFindByFileFailureIsLoggedAndNull(): void {
		$this->objectService->expects($this->once())
			->method('searchObjectsBySlug')
			->willThrowException(new RuntimeException('search broke'));
		$this->logger->expects($this->once())
			->method('warning')
			->with(
				'[ScanBatchRepository] could not read the batch of one file',
				$this->callback(
					static fn (array $context): bool => $context['fileId'] === 42 && $context['error'] === 'search broke'
				)
			);

		$this->assertNull($this->repository()->findByFile(42));

	}//end testFindByFileFailureIsLoggedAndNull()
}//end class

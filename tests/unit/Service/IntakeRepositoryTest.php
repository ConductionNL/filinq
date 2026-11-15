<?php

/**
 * Unit tests for IntakeRepository
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The intake inbox is read by slug with filters, guards empty keys, treats a
 * missing schema as an empty inbox and reports failed reads and writes.
 *
 * @covers \OCA\Filinq\Service\IntakeRepository
 * @uses   \OCA\Filinq\Service\StoredRecordFields
 * @uses   \OCA\Filinq\Service\ReadsStoredObjectRows
 */
class IntakeRepositoryTest extends TestCase {

	/**
	 * The fake OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objectService;

	/**
	 * The repository's logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The repository under test.
	 *
	 * @var IntakeRepository
	 */
	private IntakeRepository $repository;

	/**
	 * Set up the repository over a mocked object service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->objectService = $this->createMock(ObjectService::class);
		$this->logger        = $this->createMock(LoggerInterface::class);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objectService);

		$this->repository = new IntakeRepository($resolver, $this->logger);

	}//end setUp()

	/**
	 * Expect one search with the given filters, answering with the given result.
	 *
	 * @param array $filters The expected filters.
	 * @param mixed $result  The result.
	 *
	 * @return void
	 */
	private function expectSearch(array $filters, mixed $result): void {
		$this->objectService->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'intakeDocument', $filters)
			->willReturn($result);

	}//end expectSearch()

	/**
	 * Waiting documents are those with status received, normalised with a uuid.
	 *
	 * @return void
	 */
	public function testFindWaitingSearchesReceived(): void {
		$this->expectSearch(['status' => 'received'], [['title' => 'Brief', '@self' => ['uuid' => 'u-1']]]);

		$rows = $this->repository->findWaiting();

		$this->assertCount(1, $rows);
		$this->assertSame('u-1', $rows[0]['uuid']);
		$this->assertSame('Brief', $rows[0]['title']);

	}//end testFindWaitingSearchesReceived()

	/**
	 * Empty keys short-circuit without a search.
	 *
	 * @return void
	 */
	public function testEmptyKeysDoNotSearch(): void {
		$this->objectService->expects($this->never())->method('searchObjectsBySlug');
		$this->objectService->expects($this->never())->method('find');

		$this->assertSame([], $this->repository->findArrivedWith(''));
		$this->assertNull($this->repository->findByFile(0));
		$this->assertNull($this->repository->findBySourceRef(''));
		$this->assertNull($this->repository->findByUuid(''));

	}//end testEmptyKeysDoNotSearch()

	/**
	 * A source ref with no match is null; a non-array result is no rows.
	 *
	 * @return void
	 */
	public function testNoMatchIsNull(): void {
		$this->expectSearch(['sourceRef' => 'msg-1'], 0);

		$this->assertNull($this->repository->findBySourceRef('msg-1'));

	}//end testNoMatchIsNull()

	/**
	 * A file lookup returns the first row.
	 *
	 * @return void
	 */
	public function testFindByFileReturnsTheFirstRow(): void {
		$this->expectSearch(['file' => 12], [['uuid' => 'u-1', 'file' => 12], ['uuid' => 'u-2', 'file' => 12]]);

		$this->assertSame('u-1', $this->repository->findByFile(12)['uuid']);

	}//end testFindByFileReturnsTheFirstRow()

	/**
	 * A missing schema is an empty inbox, logged as a warning.
	 *
	 * @return void
	 */
	public function testAMissingSchemaIsAnEmptyInbox(): void {
		$this->objectService->method('searchObjectsBySlug')->willThrowException(new DoesNotExistException('no schema'));
		$this->logger->expects($this->once())
			->method('warning')
			->with('[IntakeRepository] no intakeDocument schema on this instance, so the inbox is empty');

		$this->assertSame([], $this->repository->findByStatus('assigned'));

	}//end testAMissingSchemaIsAnEmptyInbox()

	/**
	 * Any other read failure is raised.
	 *
	 * @return void
	 */
	public function testAFailedReadIsRaised(): void {
		$this->objectService->method('searchObjectsBySlug')->willThrowException(new Exception('db down'));
		$this->logger->expects($this->once())
			->method('warning')
			->with('[IntakeRepository] could not read the intake inbox');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Could not read the intake inbox: db down');

		$this->repository->findWaiting();

	}//end testAFailedReadIsRaised()

	/**
	 * A uuid lookup normalises the row, and is null when unknown or unreadable.
	 *
	 * @return void
	 */
	public function testFindByUuid(): void {
		$this->objectService->method('find')->willReturnCallback(
			static function (string $id, string $register, string $schema): ?array {
				TestCase::assertSame('filinq', $register);
				TestCase::assertSame('intakeDocument', $schema);
				if ($id === 'boom') {
					throw new Exception('timeout');
				}

				return $id === 'u-1' ? ['id' => 'u-1', 'title' => 'Brief'] : null;
			}
		);
		$this->logger->expects($this->once())
			->method('warning')
			->with('[IntakeRepository] could not read one intake document');

		$this->assertSame('u-1', $this->repository->findByUuid('u-1')['uuid']);
		$this->assertNull($this->repository->findByUuid('u-404'));
		$this->assertNull($this->repository->findByUuid('boom'));

	}//end testFindByUuid()

	/**
	 * Saving passes the uuid as an argument, not a field, and fills it back in.
	 *
	 * @return void
	 */
	public function testSaveKeepsTheUuidOutOfThePayload(): void {
		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(['title' => 'Brief'], 'filinq', 'intakeDocument', 'u-1')
			->willReturn(['title' => 'Brief']);

		$this->assertSame(
			['title' => 'Brief', 'uuid' => 'u-1'],
			$this->repository->save(['uuid' => 'stale', 'title' => 'Brief'], 'u-1')
		);

	}//end testSaveKeepsTheUuidOutOfThePayload()

	/**
	 * A failed write is logged and raised.
	 *
	 * @return void
	 */
	public function testAFailedWriteIsRaised(): void {
		$this->objectService->method('saveObject')->willThrowException(new Exception('refused'));
		$this->logger->expects($this->once())
			->method('error')
			->with('[IntakeRepository] could not store an intake document');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Could not store the intake document: refused');

		$this->repository->save(['title' => 'Brief']);

	}//end testAFailedWriteIsRaised()
}//end class

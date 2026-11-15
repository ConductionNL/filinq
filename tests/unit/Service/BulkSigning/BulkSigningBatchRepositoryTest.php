<?php

/**
 * Unit tests for the bulk-send batch repository
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\BulkSigning
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service\BulkSigning;

use OCA\Filinq\Service\BulkSigning\BulkSigningBatchRepository;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * The repository writes to the intake register, reads back the stored
 * payload, and wraps every OpenRegister failure in its own message.
 */
#[CoversClass(BulkSigningBatchRepository::class)]
#[UsesClass(DocumentObjectServiceResolver::class)]
class BulkSigningBatchRepositoryTest extends TestCase {

	/**
	 * OpenRegister's object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService $objects;

	/**
	 * Build the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(ObjectService::class);

	}//end setUp()

	/**
	 * The repository over the mocked object service.
	 *
	 * @return BulkSigningBatchRepository
	 */
	private function repository(): BulkSigningBatchRepository {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new BulkSigningBatchRepository(new DocumentObjectServiceResolver($container, $apps));

	}//end repository()

	/**
	 * An update goes to the intake register under the batch schema and the given uuid,
	 * without the payload's own `uuid` field.
	 *
	 * @return void
	 */
	public function testSaveWritesToTheSigningRegisterUnderTheGivenUuid(): void {
		$this->objects->expects($this->once())->method('saveObject')
			->with(
				['title' => 'Verklaring', 'status' => 'ready'],
				IntakeRepository::REGISTER,
				'bulkSigningBatch',
				'batch-7'
			)
			->willReturn(['title' => 'Verklaring', 'status' => 'ready', 'uuid' => 'batch-7']);

		$stored = $this->repository()->save(batch: ['uuid' => 'stale', 'title' => 'Verklaring', 'status' => 'ready'], uuid: 'batch-7');

		$this->assertSame(['title' => 'Verklaring', 'status' => 'ready', 'uuid' => 'batch-7'], $stored);

	}//end testSaveWritesToTheSigningRegisterUnderTheGivenUuid()

	/**
	 * A new batch (no uuid, or an empty one) is created without a uuid argument.
	 *
	 * @return void
	 */
	public function testSaveWithoutAUuidCreatesANewObject(): void {
		$calls = [];
		$this->objects->method('saveObject')->willReturnCallback(
			function (...$args) use (&$calls): array {
				$calls[] = $args;
				return ['object' => ['title' => 'New'], '@self' => ['id' => 'fresh-1']];
			}
		);

		$repository = $this->repository();
		$first = $repository->save(batch: ['title' => 'New']);
		$second = $repository->save(batch: ['title' => 'New'], uuid: '');

		$this->assertSame(['title' => 'New', 'uuid' => 'fresh-1'], $first);
		$this->assertSame(['title' => 'New', 'uuid' => 'fresh-1'], $second);
		$this->assertCount(2, $calls);
		foreach ($calls as $args) {
			$this->assertNull($args[3] ?? null);
		}

	}//end testSaveWithoutAUuidCreatesANewObject()

	/**
	 * When the store answers without any id, the uuid that was asked for is kept.
	 *
	 * @return void
	 */
	public function testSaveKeepsTheRequestedUuidWhenTheStoreReturnsNone(): void {
		$this->objects->method('saveObject')->willReturn(['title' => 'Kept']);

		$stored = $this->repository()->save(batch: ['title' => 'Kept'], uuid: 'batch-9');

		$this->assertSame(['title' => 'Kept', 'uuid' => 'batch-9'], $stored);

	}//end testSaveKeepsTheRequestedUuidWhenTheStoreReturnsNone()

	/**
	 * A refused write surfaces as a RuntimeException naming the bulk send, with the cause attached.
	 *
	 * @return void
	 */
	public function testSaveWrapsAStoreFailure(): void {
		$cause = new RuntimeException('schema validation failed');
		$this->objects->method('saveObject')->willThrowException($cause);

		try {
			$this->repository()->save(batch: ['title' => 'x'], uuid: 'batch-1');
			$this->fail('Expected a RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('Could not store the bulk send: schema validation failed', $e->getMessage());
			$this->assertSame($cause, $e->getPrevious());
		}

	}//end testSaveWrapsAStoreFailure()

	/**
	 * An empty uuid never reaches OpenRegister.
	 *
	 * @return void
	 */
	public function testFindWithAnEmptyUuidReturnsNullWithoutAsking(): void {
		$this->objects->expects($this->never())->method('find');

		$this->assertNull($this->repository()->find(uuid: ''));

	}//end testFindWithAnEmptyUuidReturnsNullWithoutAsking()

	/**
	 * An absent batch reads as null.
	 *
	 * @return void
	 */
	public function testFindReturnsNullForAnAbsentBatch(): void {
		$this->objects->expects($this->once())->method('find')
			->with('batch-404', IntakeRepository::REGISTER, 'bulkSigningBatch')
			->willReturn(null);

		$this->assertNull($this->repository()->find(uuid: 'batch-404'));

	}//end testFindReturnsNullForAnAbsentBatch()

	/**
	 * A found batch is its stored payload, without `@self`, with its uuid.
	 *
	 * @return void
	 */
	public function testFindReturnsTheStoredPayload(): void {
		$this->objects->method('find')->willReturn(
			['status' => 'creating', '@self' => ['id' => 'batch-3', 'register' => 'filinq']]
		);

		$this->assertSame(['status' => 'creating', 'uuid' => 'batch-3'], $this->repository()->find(uuid: 'batch-3'));

	}//end testFindReturnsTheStoredPayload()

	/**
	 * A failing read is wrapped, never swallowed.
	 *
	 * @return void
	 */
	public function testFindWrapsAReadFailure(): void {
		$this->objects->method('find')->willThrowException(new RuntimeException('db down'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The bulk send could not be read: db down');

		$this->repository()->find(uuid: 'batch-1');

	}//end testFindWrapsAReadFailure()

	/**
	 * An initiator's list is filtered by the search AND by the repository's own owner check.
	 *
	 * @return void
	 */
	public function testListKeepsOnlyTheInitiatorsBatchesEvenIfTheSearchLeaks(): void {
		$this->objects->expects($this->once())->method('searchObjectsBySlug')
			->with(IntakeRepository::REGISTER, 'bulkSigningBatch', ['createdBy' => 'alice'])
			->willReturn(
				[
					['uuid' => 'a1', 'createdBy' => 'alice', 'createdAt' => '2026-01-01T10:00:00+00:00'],
					['uuid' => 'b1', 'createdBy' => 'bob', 'createdAt' => '2026-03-01T10:00:00+00:00'],
					['uuid' => 'a2', 'createdBy' => 'alice', 'createdAt' => '2026-02-01T10:00:00+00:00'],
				]
			);

		$batches = $this->repository()->list(createdBy: 'alice');

		$this->assertSame(['a2', 'a1'], array_column($batches, 'uuid'));

	}//end testListKeepsOnlyTheInitiatorsBatchesEvenIfTheSearchLeaks()

	/**
	 * An unfiltered list asks without filters and returns every batch, newest first.
	 *
	 * @return void
	 */
	public function testListWithoutAnOwnerReturnsEveryBatchNewestFirst(): void {
		$this->objects->expects($this->once())->method('searchObjectsBySlug')
			->with(IntakeRepository::REGISTER, 'bulkSigningBatch', [])
			->willReturn(
				[
					['uuid' => 'old', 'createdBy' => 'alice', 'createdAt' => '2025-12-31T23:59:59+00:00'],
					['uuid' => 'undated', 'createdBy' => 'carol'],
					['uuid' => 'new', 'createdBy' => 'bob', 'createdAt' => '2026-05-01T08:00:00+00:00'],
				]
			);

		$batches = $this->repository()->list(createdBy: null);

		$this->assertSame(['new', 'old', 'undated'], array_column($batches, 'uuid'));

	}//end testListWithoutAnOwnerReturnsEveryBatchNewestFirst()

	/**
	 * A failing search is wrapped.
	 *
	 * @return void
	 */
	public function testListWrapsASearchFailure(): void {
		$this->objects->method('searchObjectsBySlug')->willThrowException(new RuntimeException('timeout'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The bulk sends could not be read: timeout');

		$this->repository()->list(createdBy: 'alice');

	}//end testListWrapsASearchFailure()
}//end class

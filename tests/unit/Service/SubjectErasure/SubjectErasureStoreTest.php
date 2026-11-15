<?php

/**
 * Unit tests for SubjectErasureStore.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SubjectErasure
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SubjectErasure;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureStore;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Reading, finding and saving erasure rows in the register.
 *
 * @covers \OCA\Filinq\Service\SubjectErasure\SubjectErasureStore
 * @uses   \OCA\Filinq\Service\ReadsStoredObjectRows
 */
class SubjectErasureStoreTest extends TestCase {

	/**
	 * The object service double.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * Set up the double.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(ObjectService::class);
	}//end setUp()

	/**
	 * Build the store.
	 *
	 * @return SubjectErasureStore
	 */
	private function store(): SubjectErasureStore {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		return new SubjectErasureStore(objectResolver: $resolver);
	}//end store()

	/**
	 * Rows are re-filtered locally and rows without a uuid are dropped.
	 *
	 * @return void
	 */
	public function testSearchRefiltersAndNeedsAUuid(): void {
		$this->objects->expects($this->once())->method('searchObjectsBySlug')
			->with('filinq', SubjectErasureStore::REQUEST, ['status' => 'open'], false)
			->willReturn(
				[
					['uuid' => 'a', 'status' => 'open'],
					['uuid' => 'b', 'status' => 'closed'],
					['status' => 'open'],
					['object' => ['status' => 'open'], '@self' => ['id' => 'c']],
				]
			);

		$rows = $this->store()->search(schema: SubjectErasureStore::REQUEST, filters: ['status' => 'open']);

		$this->assertSame([['uuid' => 'a', 'status' => 'open'], ['status' => 'open', 'uuid' => 'c']], $rows);
	}//end testSearchRefiltersAndNeedsAUuid()

	/**
	 * An unreadable register is reported, not swallowed.
	 *
	 * @return void
	 */
	public function testSearchFailureIsRaised(): void {
		$this->objects->method('searchObjectsBySlug')->willThrowException(new \Exception('timeout'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The erasure register could not be read: timeout');

		$this->store()->search(schema: SubjectErasureStore::CERTIFICATE);
	}//end testSearchFailureIsRaised()

	/**
	 * Find matches the uuid; an empty or unknown uuid gives null.
	 *
	 * @return void
	 */
	public function testFind(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn([['uuid' => 'x', 'n' => 1], ['uuid' => 'y', 'n' => 2]]);
		$store = $this->store();

		$this->assertSame(['uuid' => 'y', 'n' => 2], $store->find(schema: SubjectErasureStore::REQUEST, uuid: 'y'));
		$this->assertNull($store->find(schema: SubjectErasureStore::REQUEST, uuid: 'z'));
		$this->assertNull($store->find(schema: SubjectErasureStore::REQUEST, uuid: ''));
	}//end testFind()

	/**
	 * Saving strips identity keys, updates by uuid and merges what was stored.
	 *
	 * @return void
	 */
	public function testSaveUpdatesByUuidAndMergesTheStoredRow(): void {
		$stored = $this->createMock(ObjectEntity::class);
		$stored->method('getObject')->willReturn(['status' => 'done', 'extra' => 1]);
		$stored->method('getUuid')->willReturn('u-1');
		$this->objects->expects($this->once())->method('saveObject')
			->with(['status' => 'open', 'note' => 'n'], 'filinq', SubjectErasureStore::REQUEST, 'u-1', false, true, true)
			->willReturn($stored);

		$saved = $this->store()->save(
			schema: SubjectErasureStore::REQUEST,
			row: ['uuid' => 'u-1', '@self' => ['id' => 'u-1'], 'id' => 5, 'status' => 'open', 'note' => 'n']
		);

		$this->assertSame(['status' => 'done', 'note' => 'n', 'extra' => 1, 'uuid' => 'u-1'], $saved);
	}//end testSaveUpdatesByUuidAndMergesTheStoredRow()

	/**
	 * A new row is created without a uuid; a stored row without a uuid is refused.
	 *
	 * @return void
	 */
	public function testSaveRefusesAStoredRowWithoutUuid(): void {
		$this->objects->expects($this->once())->method('saveObject')
			->with(['status' => 'new'], 'filinq', SubjectErasureStore::CERTIFICATE, null)
			->willReturn(['status' => 'new']);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenRegister stored the erasureCertificate without a uuid.');

		$this->store()->save(schema: SubjectErasureStore::CERTIFICATE, row: ['status' => 'new']);
	}//end testSaveRefusesAStoredRowWithoutUuid()

	/**
	 * A failing save names the schema and the cause.
	 *
	 * @return void
	 */
	public function testSaveFailureIsRaised(): void {
		$this->objects->method('saveObject')->willThrowException(new \Exception('validation'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The subjectErasureRequest could not be saved: validation');

		$this->store()->save(schema: SubjectErasureStore::REQUEST, row: []);
	}//end testSaveFailureIsRaised()

	/**
	 * The stored request entity is returned; empty uuid, lookup failure or a non-entity give null.
	 *
	 * @return void
	 */
	public function testStoredRequest(): void {
		$entity = $this->createMock(ObjectEntity::class);
		$this->objects->method('find')->willReturnCallback(
			static function (string $id, string $register, string $schema, bool $_rbac, bool $_multitenancy) use ($entity) {
				if ($register !== 'filinq' || $schema !== SubjectErasureStore::REQUEST || $_rbac !== false || $_multitenancy !== false) {
					return null;
				}

				return match ($id) {
					'found' => $entity,
					'broken' => throw new \Exception('db'),
					default => ['uuid' => $id],
				};
			}
		);
		$store = $this->store();

		$this->assertSame($entity, $store->storedRequest(uuid: 'found'));
		$this->assertNull($store->storedRequest(uuid: 'broken'));
		$this->assertNull($store->storedRequest(uuid: 'array'));
		$this->assertNull($store->storedRequest(uuid: ''));
	}//end testStoredRequest()
}//end class

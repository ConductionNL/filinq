<?php

/**
 * Unit tests for WaarmerkStore.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Waarmerk;

use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Waarmerk\WaarmerkStore;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The write-once waarmerk records.
 *
 * @covers \OCA\Filinq\Service\Waarmerk\WaarmerkStore
 * @uses   \OCA\Filinq\Service\ReadsStoredObjectRows
 * @uses   \OCA\Filinq\Exception\WaarmerkRefusedException
 */
class WaarmerkStoreTest extends TestCase {

	/**
	 * The object service double.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * Build the store.
	 *
	 * @return WaarmerkStore
	 */
	private function store(): WaarmerkStore {
		$this->objects = $this->createMock(ObjectService::class);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		return new WaarmerkStore(objectResolver: $resolver);
	}//end store()

	/**
	 * A stored seal.
	 *
	 * @return array<string, mixed>
	 */
	private function seal(): array {
		return [
			'uuid' => 'w-1', 'documentHash' => str_repeat('a', 64), 'cmsSignature' => 'CMS', 'verificationCode' => 'code-1',
			'sealedAt' => '2026-11-14T10:00:00+00:00', 'sealedBy' => 'alice', 'status' => 'active',
		];
	}//end seal()

	/**
	 * create() writes a new object, unowned and past the cascade, and returns it with its uuid.
	 *
	 * @return void
	 */
	public function testCreate(): void {
		$store = $this->store();
		$row = $this->seal();
		unset($row['uuid']);
		$this->objects->expects($this->once())->method('saveObject')
			->with($row, 'filinq', 'waarmerk', null, false, true, true)
			->willReturn(['uuid' => 'new-1'] + $row);

		$stored = $store->create(row: $row);
		$this->assertSame('new-1', $stored['uuid']);
		$this->assertSame('active', $stored['status']);
	}//end testCreate()

	/**
	 * create() never overwrites: a row that names an object is refused before any write.
	 *
	 * @return void
	 */
	public function testCreateRefusesAnExistingSeal(): void {
		$store = $this->store();
		$this->objects->expects($this->never())->method('saveObject');
		foreach (['uuid', 'id', '@self'] as $field) {
			try {
				$store->create(row: [$field => 'x', 'status' => 'active']);
				$this->fail($field . ': expected a refusal');
			} catch (WaarmerkRefusedException $e) {
				$this->assertSame(409, $e->getStatus());
			}
		}
	}//end testCreateRefusesAnExistingSeal()

	/**
	 * revoke() writes the same evidence fields byte-identical plus the revocation fields.
	 *
	 * @return void
	 */
	public function testRevokeChangesOnlyTheRevocationFields(): void {
		$store = $this->store();
		$seal = $this->seal();
		$revocation = ['status' => 'revoked', 'revokedAt' => '2026-11-15T10:00:00+00:00', 'revokedBy' => 'alice', 'revocationReason' => 'withdrawn'];
		$expected = array_merge($seal, $revocation);
		unset($expected['uuid']);
		$this->objects->expects($this->once())->method('saveObject')
			->with($expected, 'filinq', 'waarmerk', 'w-1', false, true, true)
			->willReturn(['uuid' => 'w-1'] + $expected);

		$revoked = $store->revoke(seal: $seal, revocation: $revocation);
		foreach (['documentHash', 'cmsSignature', 'verificationCode', 'sealedAt'] as $field) {
			$this->assertSame($seal[$field], $revoked[$field], $field);
		}

		$this->assertSame('revoked', $revoked['status']);
	}//end testRevokeChangesOnlyTheRevocationFields()

	/**
	 * Any other field, or a second revocation, is refused without a write.
	 *
	 * @return void
	 */
	public function testRevokeRefusesEvidenceChangesAndUndo(): void {
		$store = $this->store();
		$this->objects->expects($this->never())->method('saveObject');
		foreach (['documentHash', 'cmsSignature', 'verificationCode', 'sealedAt'] as $field) {
			try {
				$store->revoke(seal: $this->seal(), revocation: ['status' => 'revoked', $field => 'forged']);
				$this->fail($field . ': expected a refusal');
			} catch (WaarmerkRefusedException $e) {
				$this->assertSame(409, $e->getStatus());
			}
		}

		$this->expectException(WaarmerkRefusedException::class);
		$this->expectExceptionMessage('cannot be undone');
		$store->revoke(seal: ['status' => 'revoked'] + $this->seal(), revocation: ['status' => 'active']);
	}//end testRevokeRefusesEvidenceChangesAndUndo()

	/**
	 * findByCode() matches the exact code only; find() by uuid; empty input reads nothing.
	 *
	 * @return void
	 */
	public function testLookups(): void {
		$store = $this->store();
		$this->objects->method('searchObjectsBySlug')->willReturnCallback(
			fn (string $registerSlug, string $schemaSlug, array $filters): array => array_values(
				array_filter(
					[$this->seal(), ['uuid' => 'w-2', 'verificationCode' => 'code-10', 'status' => 'active'], ['verificationCode' => 'code-1']],
					fn (array $row): bool => isset($filters['verificationCode']) === false || str_starts_with((string) ($row['verificationCode'] ?? ''), (string) $filters['verificationCode'])
				)
			)
		);

		$this->assertSame('w-1', $store->findByCode(code: 'code-1')['uuid']);
		$this->assertNull($store->findByCode(code: 'code-'));
		$this->assertNull($store->findByCode(code: ''));
		$this->assertSame('w-2', $store->find(uuid: 'w-2')['uuid']);
		$this->assertNull($store->find(uuid: 'nope'));
		$this->assertNull($store->find(uuid: ''));
	}//end testLookups()

	/**
	 * A register failure is a 503, on read and on write.
	 *
	 * @return void
	 */
	public function testRegisterFailures(): void {
		$store = $this->store();
		$this->objects->method('searchObjectsBySlug')->willThrowException(new RuntimeException('db'));
		$this->objects->method('saveObject')->willReturn(['status' => 'active']);

		try {
			$store->search(filters: []);
			$this->fail('expected a refusal');
		} catch (WaarmerkRefusedException $e) {
			$this->assertSame(503, $e->getStatus());
		}

		$this->expectExceptionMessage('without a uuid');
		$store->create(row: ['status' => 'active']);
	}//end testRegisterFailures()
}//end class

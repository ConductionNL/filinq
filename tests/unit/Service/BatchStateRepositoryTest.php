<?php

/**
 * Unit tests for BatchStateRepository
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\BatchStateRepository;
use OCA\DocuDesk\Service\OpenRegisterAvailabilityService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The OpenRegister persistence primitives behind BatchStateService.
 *
 * The OpenRegister handle here is a real in-memory fake, not a PHPUnit mock: a
 * mock cannot observe named arguments — it reports its own parameter defaults —
 * so `expects()->with()` would pass against code that sent the wrong register,
 * schema or uuid. Everything below asserts on what was actually stored.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchStateRepositoryTest extends TestCase {

	/**
	 * The in-memory OpenRegister store.
	 *
	 * @var InMemoryObjectServiceFake
	 */
	private InMemoryObjectServiceFake $store;

	/**
	 * The repository under test.
	 *
	 * @var BatchStateRepository
	 */
	private BatchStateRepository $repository;

	/**
	 * Set up the repository with OpenRegister available.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->store = new InMemoryObjectServiceFake();
		$this->repository = $this->buildRepository(available: true);
	}//end setUp()

	/**
	 * Build a repository against the shared store.
	 *
	 * @param bool $available Whether OpenRegister reports itself installed.
	 *
	 * @return BatchStateRepository The repository.
	 */
	private function buildRepository(bool $available): BatchStateRepository {
		$availability = $this->createMock(OpenRegisterAvailabilityService::class);
		$availability->method('isInstalled')->willReturn($available);
		$availability->method('getObjectService')->willReturn($this->store);

		return new BatchStateRepository(
			openRegister: $availability,
			logger: $this->createMock(LoggerInterface::class)
		);
	}//end buildRepository()

	/**
	 * The write lands in the `document` register under `anonymizationBatch`,
	 * keyed by the batch id, with RBAC and multitenancy bypassed.
	 *
	 * ⚠️ `_rbac: false` is load-bearing and asserted here on purpose. The
	 * schema now declares an authorization cascade (register v7.9.0), and an
	 * OpenRegister cascade DENIES any action it does not name. DocuDesk's own
	 * batch writes must not be subject to it — ownership is enforced in
	 * BatchStateService::getBatch() instead — so if this flag ever flips, the
	 * whole batch flow fails closed for every user who is not in
	 * docudesk-policy-admins, and this test is what says so.
	 *
	 * @return void
	 */
	public function testSaveTargetsTheDeclaredRegisterSchemaAndUuid(): void {
		$this->repository->save(
			batchId: 'batch-1',
			batch: ['batchId' => 'batch-1', 'userId' => 'alice', 'files' => []]
		);

		$this->assertCount(1, $this->store->saveCalls);
		$call = $this->store->saveCalls[0];

		$this->assertSame('document', $call['register']);
		$this->assertSame('anonymizationBatch', $call['schema']);
		$this->assertSame('batch-1', $call['uuid']);
		$this->assertFalse($call['_rbac']);
		$this->assertFalse($call['_multitenancy']);
	}//end testSaveTargetsTheDeclaredRegisterSchemaAndUuid()

	/**
	 * `files` is stored as `documents`, and nothing is stored under `files`.
	 *
	 * `files` is an ObjectEntity column that OpenRegister surfaces under
	 * `@self.files`; a data property of the same name is a collision waiting to
	 * happen, so the repository renames it. If that rename is ever dropped this
	 * test says so, rather than the batch quietly losing its file list.
	 *
	 * @return void
	 */
	public function testFilesAreStoredUnderTheDocumentsProperty(): void {
		$this->repository->save(
			batchId: 'batch-1',
			batch: [
				'batchId' => 'batch-1',
				'userId' => 'alice',
				'files' => [['fileId' => 9, 'fileName' => 'a.txt', 'status' => 'uploaded']],
			]
		);

		$stored = $this->store->saveCalls[0]['object'];

		$this->assertArrayNotHasKey('files', $stored);
		$this->assertArrayHasKey('documents', $stored);
		$this->assertSame(9, $stored['documents'][0]['fileId']);
		$this->assertSame('a.txt', $stored['documents'][0]['fileName']);
	}//end testFilesAreStoredUnderTheDocumentsProperty()

	/**
	 * A saved batch reads back with the content it was given.
	 *
	 * @return void
	 */
	public function testASavedBatchRoundTrips(): void {
		$batch = [
			'batchId' => 'batch-1',
			'userId' => 'alice',
			'status' => 'review',
			'createdAt' => 1750000000,
			'sourceType' => 'folder',
			'folderId' => 42,
			'folderPath' => '/Zaakdossiers',
			'files' => [
				['fileId' => 9, 'fileName' => 'a.txt', 'status' => 'extracted', 'entityCount' => 3],
			],
		];

		$this->repository->save(batchId: 'batch-1', batch: $batch);
		$loaded = $this->repository->find(batchId: 'batch-1');

		$this->assertIsArray($loaded);
		$this->assertSame('review', $loaded['status']);
		$this->assertSame(42, $loaded['folderId']);
		$this->assertSame('/Zaakdossiers', $loaded['folderPath']);
		$this->assertSame(1750000000, $loaded['createdAt']);
		$this->assertSame($batch['files'], $loaded['files']);
	}//end testASavedBatchRoundTrips()

	/**
	 * An update overwrites the record under the same identifier rather than
	 * creating a second one.
	 *
	 * @return void
	 */
	public function testSavingTwiceUpdatesTheSameRecord(): void {
		$this->repository->save(
			batchId: 'batch-1',
			batch: ['batchId' => 'batch-1', 'status' => 'uploading', 'files' => []]
		);
		$this->repository->save(
			batchId: 'batch-1',
			batch: ['batchId' => 'batch-1', 'status' => 'review', 'files' => []]
		);

		$this->assertCount(1, $this->store->stored);
		$this->assertSame('review', $this->repository->find(batchId: 'batch-1')['status']);
	}//end testSavingTwiceUpdatesTheSameRecord()

	/**
	 * A miss is null — OpenRegister raises DoesNotExistException for an unknown
	 * identifier, and that is not an error condition here.
	 *
	 * @return void
	 */
	public function testFindReturnsNullForAnUnknownBatch(): void {
		$this->assertNull($this->repository->find(batchId: 'nope'));
	}//end testFindReturnsNullForAnUnknownBatch()

	/**
	 * The serialisation envelope OpenRegister adds is stripped on the way out.
	 *
	 * @return void
	 */
	public function testTheOpenRegisterEnvelopeIsStrippedOnRead(): void {
		$this->repository->save(
			batchId: 'batch-1',
			batch: ['batchId' => 'batch-1', 'files' => []]
		);

		$loaded = $this->repository->find(batchId: 'batch-1');

		$this->assertArrayNotHasKey('@self', $loaded);
		$this->assertArrayNotHasKey('id', $loaded);
		$this->assertArrayNotHasKey('documents', $loaded);
	}//end testTheOpenRegisterEnvelopeIsStrippedOnRead()

	/**
	 * A delete removes the record.
	 *
	 * @return void
	 */
	public function testDeleteRemovesTheRecord(): void {
		$this->repository->save(batchId: 'batch-1', batch: ['batchId' => 'batch-1', 'files' => []]);
		$this->repository->delete(batchId: 'batch-1');

		$this->assertNull($this->repository->find(batchId: 'batch-1'));
	}//end testDeleteRemovesTheRecord()

	/**
	 * A write with OpenRegister unavailable throws, and stores nothing.
	 *
	 * @return void
	 */
	public function testSaveThrowsWhenOpenRegisterIsUnavailable(): void {
		$repository = $this->buildRepository(available: false);

		try {
			$repository->save(batchId: 'batch-1', batch: ['batchId' => 'batch-1', 'files' => []]);
			$this->fail('save() reported success while OpenRegister was unavailable.');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('OpenRegister is not available', $e->getMessage());
		}

		$this->assertSame([], $this->store->stored);
	}//end testSaveThrowsWhenOpenRegisterIsUnavailable()

	/**
	 * A read with OpenRegister unavailable throws rather than answering "no
	 * such batch".
	 *
	 * "The store is unreachable" and "the batch does not exist" must not look
	 * the same to a caller: the first is an outage, the second is a 404.
	 *
	 * @return void
	 */
	public function testFindThrowsWhenOpenRegisterIsUnavailable(): void {
		$repository = $this->buildRepository(available: false);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenRegister is not available');

		$repository->find(batchId: 'batch-1');
	}//end testFindThrowsWhenOpenRegisterIsUnavailable()

	/**
	 * isAvailable() reflects the OpenRegister probe.
	 *
	 * @return void
	 */
	public function testIsAvailableReflectsTheOpenRegisterProbe(): void {
		$this->assertTrue($this->repository->isAvailable());
		$this->assertFalse($this->buildRepository(available: false)->isAvailable());
	}//end testIsAvailableReflectsTheOpenRegisterProbe()
}//end class

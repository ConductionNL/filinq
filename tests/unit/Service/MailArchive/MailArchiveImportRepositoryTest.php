<?php

/**
 * Unit tests for MailArchiveImportRepository
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\MailArchive
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

namespace OCA\Filinq\Tests\Unit\Service\MailArchive;

require_once __DIR__ . '/MailArchiveImportDoubles.php';

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\MailArchive\MailArchiveImportRepository;
use PHPUnit\Framework\TestCase;

/**
 * Imports are stored past the cascade and the job takes the one importing longest.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
 */
class MailArchiveImportRepositoryTest extends TestCase {

	/**
	 * The in-memory register.
	 *
	 * @var MailArchiveObjectStore
	 */
	private MailArchiveObjectStore $store;

	/**
	 * The repository under test.
	 *
	 * @var MailArchiveImportRepository
	 */
	private MailArchiveImportRepository $repository;

	/**
	 * Build the repository.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->store = new MailArchiveObjectStore();
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->store);
		$this->repository = new MailArchiveImportRepository(objectResolver: $resolver);

	}//end setUp()

	/**
	 * One import row.
	 *
	 * @param string $status    The status.
	 * @param string $startedAt When it started.
	 *
	 * @return array<string, mixed> The row.
	 */
	private static function row(string $status, string $startedAt): array {
		return ['file' => 1, 'status' => $status, 'requestedBy' => 'archivaris', 'startedAt' => $startedAt];

	}//end row()

	/**
	 * A save gets its uuid, an update keeps it, and every write and read skips the cascade.
	 *
	 * @return void
	 */
	public function testSavesAndReadsBypassTheCascade(): void {
		$saved = $this->repository->save(record: self::row(status: 'received', startedAt: '2026-11-14T08:00:00+00:00') + ['uuid' => 'ignored']);
		$this->assertSame('import-1', $saved['uuid']);
		$this->assertArrayNotHasKey('uuid', $this->store->rows['import-1']);

		$updated = $this->repository->save(record: ['status' => 'mapping'] + $saved, uuid: 'import-1');
		$this->assertSame('import-1', $updated['uuid']);
		$this->assertSame('mapping', $this->repository->find(uuid: 'import-1')['status']);
		$this->assertNull($this->repository->find(uuid: 'import-404'));

		$this->assertSame([false, false], array_column($this->store->saves, 1));
		$this->assertSame([false, false], array_column($this->store->saves, 2));
		$this->assertSame([false, false], $this->store->readRbac);

	}//end testSavesAndReadsBypassTheCascade()

	/**
	 * The job's import is the importing one that started first; none when nothing imports.
	 *
	 * @return void
	 */
	public function testTheOldestImportingImportIsNext(): void {
		$this->assertNull($this->repository->oldestImporting());

		$this->repository->save(record: self::row(status: 'importing', startedAt: '2026-11-14T10:00:00+00:00'));
		$this->repository->save(record: self::row(status: 'mapping', startedAt: '2026-11-14T07:00:00+00:00'));
		$this->repository->save(record: self::row(status: 'importing', startedAt: '2026-11-14T09:00:00+00:00'));

		$this->assertSame('import-3', $this->repository->oldestImporting()['uuid']);
		$this->assertSame(['import-1', 'import-3'], array_column($this->repository->search(filters: ['status' => 'importing']), 'uuid'));

	}//end testTheOldestImportingImportIsNext()
}//end class

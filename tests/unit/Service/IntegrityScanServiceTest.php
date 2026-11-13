<?php

/**
 * Unit tests for IntegrityScanService: missing, changed and orphaned files
 * across the collection, a clean run, and a scan that writes nothing else.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/whole-collection-integrity-scan/tasks.md#task-1-the-scan-service-finds-the-three-kinds
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\IntegrityScanService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\IntegrityScanService
 * @uses   \OCA\Filinq\Service\ReadsStoredObjectRows
 */
class IntegrityScanServiceTest extends TestCase {

	/** @var array<int, array<string, mixed>> */
	private array $saved = [];

	/** @var FinalDocumentService&MockObject */
	private FinalDocumentService&MockObject $final;

	/**
	 * A scan over these rows per schema, with these file ids on storage.
	 *
	 * @param array<string, array<int, array<string, mixed>>> $rows     Rows by schema.
	 * @param array<int, int>                                 $existing The file ids that resolve.
	 * @param array<int, int>                                 $changed  The final files whose checksum no longer matches.
	 *
	 * @return IntegrityScanService
	 */
	private function scan(array $rows, array $existing, array $changed = []): IntegrityScanService {
		$objects = $this->getMockBuilder(ObjectService::class)->disableOriginalConstructor()
			->onlyMethods(['searchObjectsBySlug', 'saveObject'])->getMock();
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			static fn (string $registerSlug, string $schemaSlug): array => ($rows[$schemaSlug] ?? [])
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object, mixed $register = null, mixed $schema = null) {
				$this->saved[] = ['schema' => $schema, 'object' => $object];
				return $object;
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturnCallback(
			fn (int $id) => (in_array($id, $existing, true) === true ? $this->createMock(File::class) : null)
		);

		$this->final = $this->createMock(FinalDocumentService::class);
		$this->final->method('isFinal')->willReturnCallback(static fn (array $record): bool => ($record['status'] ?? '') === 'final');
		$this->final->method('verifyChecksum')->willReturnCallback(
			static fn (int $fileId): array => ['final' => true, 'matches' => in_array($fileId, $changed, true) === false]
		);

		return new IntegrityScanService($resolver, $root, $this->final, $this->createMock(LoggerInterface::class));
	}

	public function testEachKindIsFoundAndNamesItsRecord(): void {
		$run = $this->scan(
			[
				'dossier' => [['uuid' => 'd-1', 'name' => 'Lindelaan 12', 'documents' => ['11', '12', '']]],
				'documentVersion' => [
					['uuid' => 'v-1', 'status' => 'final', 'fileId' => 21, 'documentName' => 'besluit.pdf'],
					['uuid' => 'v-2', 'status' => 'draft', 'fileId' => 99],
				],
				'anonymizationLink' => [
					['uuid' => 'a-1', 'sourceFileId' => 31, 'anonymizedFileId' => 32, 'anonymizedFileName' => 'brief (geanonimiseerd).pdf'],
					['uuid' => 'a-2', 'sourceFileId' => 41, 'anonymizedFileId' => 42],
				],
			],
			existing: [11, 21, 32, 41],
			changed: [21]
		)->run('command');

		$this->assertSame(
			[
				['kind' => 'missing', 'schema' => 'dossier', 'record' => 'd-1', 'name' => 'Lindelaan 12', 'fileId' => 12],
				['kind' => 'changed', 'schema' => 'documentVersion', 'record' => 'v-1', 'name' => 'besluit.pdf', 'fileId' => 21],
				['kind' => 'orphaned', 'schema' => 'anonymizationLink', 'record' => 'a-1', 'name' => 'brief (geanonimiseerd).pdf', 'fileId' => 32],
				['kind' => 'missing', 'schema' => 'anonymizationLink', 'record' => 'a-2', 'name' => '', 'fileId' => 42],
			],
			$run['findings']
		);
		$this->assertSame([2, 1, 1, 4], [$run['missing'], $run['changed'], $run['orphaned'], $run['checked']], 'a draft version is not checked');
		$this->assertSame('command', $run['trigger']);
	}

	public function testACleanCollectionIsStoredWithZeroCounts(): void {
		$run = $this->scan(
			['dossier' => [['uuid' => 'd-1', 'documents' => [11]]], 'documentVersion' => [['uuid' => 'v-1', 'status' => 'final', 'fileId' => 21]]],
			existing: [11, 21]
		)->run('nightly');

		$this->assertSame([0, 0, 0, 2, []], [$run['missing'], $run['changed'], $run['orphaned'], $run['checked'], $run['findings']]);
		$this->assertCount(1, $this->saved);
		$this->assertSame('integrityScan', $this->saved[0]['schema']);
		$this->assertSame($run, $this->saved[0]['object']);
		$this->assertNotSame('', $run['startedAt']);
	}

	public function testTheScanWritesNothingButItsRun(): void {
		$this->scan(
			['documentVersion' => [['uuid' => 'v-1', 'status' => 'final', 'fileId' => 21]], 'anonymizationLink' => [['uuid' => 'a-1', 'sourceFileId' => 1, 'anonymizedFileId' => 2]]],
			existing: [21, 2],
			changed: [21]
		)->run('admin');

		$this->assertSame(['integrityScan'], array_column($this->saved, 'schema'));
	}

	public function testAChecksumThatCannotBeComparedCountsAsChanged(): void {
		$objects = $this->getMockBuilder(ObjectService::class)->disableOriginalConstructor()
			->onlyMethods(['searchObjectsBySlug', 'saveObject'])->getMock();
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			static fn (string $registerSlug, string $schemaSlug): array => ($schemaSlug === 'documentVersion' ? [['uuid' => 'v-1', 'status' => 'final', 'fileId' => 21]] : [])
		);
		$objects->method('saveObject')->willReturnArgument(0);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturn($this->createMock(File::class));
		$broken = $this->createMock(FinalDocumentService::class);
		$broken->method('isFinal')->willReturn(true);
		$broken->method('verifyChecksum')->willThrowException(new RuntimeException('storage offline'));

		$run = (new IntegrityScanService($resolver, $root, $broken, $this->createMock(LoggerInterface::class)))->run('nightly');

		$this->assertSame(1, $run['changed']);
	}
}

<?php

/**
 * Unit tests for BridgeService (zgw-document-bridge D3, D5).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/zgw-document-bridge
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use OCA\Filinq\Service\BridgeService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Health is computed against the clock; filinq's transitions save the whole
 * object and refuse a document in the wrong state.
 *
 * @covers \OCA\Filinq\Service\BridgeService
 * @uses   \OCA\Filinq\Service\StoredRecordFields
 */
class BridgeServiceTest extends TestCase {

	private ObjectService&MockObject $objects;

	/**
	 * The service at 13 November 2026, 12:00 UTC.
	 *
	 * @return BridgeService
	 */
	private function service(): BridgeService {
		$this->objects = $this->createMock(ObjectService::class);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new DateTime('2026-11-13T12:00:00+00:00'));

		return new BridgeService($resolver, $time);
	}//end service()

	/**
	 * Inactive wins, then a failed last run, then the 24-hour window.
	 *
	 * @return void
	 */
	public function testHealth(): void {
		$now = new DateTimeImmutable('2026-11-13T12:00:00+00:00');

		$this->assertSame('inactive', BridgeService::health(['active' => false, 'lastSyncStatus' => 'error'], $now));
		$this->assertSame('failing', BridgeService::health(['active' => true, 'lastSyncStatus' => 'error', 'lastSyncAt' => '2026-11-13T11:00:00+00:00'], $now));
		$this->assertSame('fresh', BridgeService::health(['active' => true, 'lastSyncStatus' => 'success', 'lastSyncAt' => '2026-11-12T12:00:01+00:00'], $now));
		$this->assertSame('stale', BridgeService::health(['active' => true, 'lastSyncStatus' => 'success', 'lastSyncAt' => '2026-11-12T12:00:00+00:00'], $now));
		$this->assertSame('stale', BridgeService::health(['lastSyncStatus' => 'success'], $now));
		$this->assertSame('stale', BridgeService::health(['lastSyncAt' => 'not a date'], $now));
	}//end testHealth()

	/**
	 * The sources come from the bridge schema, each with its health at the clock's now.
	 *
	 * @return void
	 */
	public function testSourcesCarryTheirHealth(): void {
		$service = $this->service();
		$this->objects->expects($this->once())->method('searchObjectsBySlug')->with('filinq', 'bridgeSource', [])->willReturn(
			[
				['uuid' => 's-1', 'name' => 'Rx', 'active' => true, 'lastSyncStatus' => 'success', 'lastSyncAt' => '2026-11-13T06:00:00+00:00'],
				['uuid' => 's-2', 'name' => 'Djuma', 'active' => true, 'lastSyncStatus' => 'success', 'lastSyncAt' => '2026-11-10T06:00:00+00:00'],
			]
		);

		$this->assertSame(['fresh', 'stale'], array_column($service->sources(), 'health'));
	}//end testSourcesCarryTheirHealth()

	/**
	 * Staged documents are read with the staged filter, narrowed to a source when one is named.
	 *
	 * @return void
	 */
	public function testStagedDocumentsAreFilteredBySource(): void {
		$service = $this->service();
		$this->objects->expects($this->exactly(2))->method('searchObjectsBySlug')->willReturnCallback(
			static fn (string $register, string $schema, array $filters): array => [['uuid' => 'd-' . count($filters), 'schema' => $schema, 'filters' => $filters]]
		);

		$this->assertSame([['uuid' => 'd-1', 'schema' => 'externalDocument', 'filters' => ['processingStatus' => 'staged']]], $service->stagedDocuments());
		$this->assertSame(['processingStatus' => 'staged', 'sourceId' => 's-1'], $service->stagedDocuments('s-1')[0]['filters']);
	}//end testStagedDocumentsAreFilteredBySource()

	/**
	 * Releasing saves the whole document as ready for write-back, keeping every other field.
	 *
	 * @return void
	 */
	public function testReleaseSavesTheWholeObject(): void {
		$service = $this->service();
		$this->objects->method('find')->with('d-1', 'filinq', 'externalDocument')->willReturn(
			['uuid' => 'd-1', 'title' => 'Besluit', 'contentHash' => 'abc', 'processingStatus' => 'processed', 'resultFileRef' => 42]
		);
		$this->objects->expects($this->once())->method('saveObject')->with(
			['title' => 'Besluit', 'contentHash' => 'abc', 'processingStatus' => 'ready_for_writeback', 'resultFileRef' => 42],
			'filinq',
			'externalDocument',
			'd-1'
		)->willReturn(['uuid' => 'd-1', 'processingStatus' => 'ready_for_writeback']);

		$this->assertSame(['uuid' => 'd-1', 'processingStatus' => 'ready_for_writeback'], $service->release('d-1'));
	}//end testReleaseSavesTheWholeObject()

	/**
	 * A document without its result file, or not yet processed, is not released.
	 *
	 * @return void
	 */
	public function testReleaseRefusesWithoutAResultOrInTheWrongState(): void {
		$service = $this->service();
		$this->objects->method('find')->willReturnOnConsecutiveCalls(
			['uuid' => 'd-1', 'processingStatus' => 'processed'],
			['uuid' => 'd-2', 'processingStatus' => 'staged', 'resultFileRef' => 42]
		);
		$this->objects->expects($this->never())->method('saveObject');

		try {
			$service->release('d-1');
			$this->fail('released without a result file');
		} catch (InvalidArgumentException $e) {
			$this->assertSame('A document can only be released with its redacted result file.', $e->getMessage());
		}

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('A staged document cannot be moved to ready_for_writeback.');
		$service->release('d-2');
	}//end testReleaseRefusesWithoutAResultOrInTheWrongState()

	/**
	 * A failed write-back is retried with its error cleared; anything else is refused.
	 *
	 * @return void
	 */
	public function testRetry(): void {
		$service = $this->service();
		$this->objects->method('find')->willReturnOnConsecutiveCalls(
			['uuid' => 'd-1', 'processingStatus' => 'writeback_failed', 'writeBackError' => '409 from the DRC'],
			['uuid' => 'd-2', 'processingStatus' => 'written_back']
		);
		$this->objects->expects($this->once())->method('saveObject')->with(
			['processingStatus' => 'ready_for_writeback', 'writeBackError' => null],
			'filinq',
			'externalDocument',
			'd-1'
		)->willReturn(['uuid' => 'd-1']);

		$service->retry('d-1');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('A written_back document cannot be moved to ready_for_writeback.');
		$service->retry('d-2');
	}//end testRetry()

	/**
	 * An unknown document is not found.
	 *
	 * @return void
	 */
	public function testAnUnknownDocumentIsNotFound(): void {
		$service = $this->service();
		$this->objects->method('find')->willReturn(null);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(404);
		$service->retry('gone');
	}//end testAnUnknownDocumentIsNotFound()
}//end class

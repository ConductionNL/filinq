<?php

/**
 * Unit tests for the background path of BatchAnonymizeService: enqueue, the stored request,
 * the per-file step with lock-miss retries, and the resume skip keyed on anonymizationLink.
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
 * @spec openspec/changes/redaction-at-scale/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\BatchAnonymizeRequest;
use OCA\Filinq\Service\BatchAnonymizeService;
use OCA\Filinq\Service\BatchBackgroundPath;
use OCA\Filinq\Service\BatchRequestService;
use OCA\Filinq\Service\BatchRouting;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\Conversion\OutputLayoutMover;
use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use OCP\IAppConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * What the anonymize step does with a batch above the synchronous cap, and with a resumed one.
 *
 * @covers \OCA\Filinq\Service\BatchAnonymizeService
 * @covers \OCA\Filinq\Service\BatchBackgroundPath
 * @covers \OCA\Filinq\Service\BatchRequestService::resolveHttpStatus
 * @uses   \OCA\Filinq\Service\BatchAnonymizeRun
 * @uses   \OCA\Filinq\Service\BatchAnonymizeRequest
 * @uses   \OCA\Filinq\Service\AnonymizeOptions
 * @uses   \OCA\Filinq\Service\BatchRequestService
 * @uses   \OCA\Filinq\Service\UnredactedEntitiesValidator
 */
class BatchAnonymizeServiceBackgroundTest extends TestCase {

	/**
	 * Single-document anonymization double.
	 *
	 * @var AnonymizationService&MockObject
	 */
	private AnonymizationService&MockObject $anon;

	/**
	 * Batch store double.
	 *
	 * @var BatchStateService&MockObject
	 */
	private BatchStateService&MockObject $state;

	/**
	 * Routing double.
	 *
	 * @var BatchRouting&MockObject
	 */
	private BatchRouting&MockObject $routing;

	/**
	 * Link reader double.
	 *
	 * @var AnonymizationLinkReader&MockObject
	 */
	private AnonymizationLinkReader&MockObject $links;

	/**
	 * Subject.
	 *
	 * @var BatchAnonymizeService
	 */
	private BatchAnonymizeService $service;

	/**
	 * Build the subject.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->anon = $this->createMock(AnonymizationService::class);
		$this->state = $this->createMock(BatchStateService::class);
		$this->routing = $this->createMock(BatchRouting::class);
		$this->links = $this->createMock(AnonymizationLinkReader::class);
		$mover = $this->createMock(OutputLayoutMover::class);
		$mover->method('relocate')->willReturnCallback(
			static fn (string $userId, int $anonymizedFileId, string $legacyPath): array => ['path' => $legacyPath, 'warning' => null]
		);
		$this->service = new BatchAnonymizeService(
			anonService: $this->anon,
			stateService: $this->state,
			layoutMover: $mover,
			qaSampler: null,
			appConfig: $this->createMock(IAppConfig::class),
			background: new BatchBackgroundPath(stateService: $this->state, routing: $this->routing, linkReader: $this->links)
		);

	}//end setUp()

	/**
	 * A batch of extracted files.
	 *
	 * @param int                  $count Number of files.
	 * @param array<string, mixed> $extra Extra batch fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function batch(int $count, array $extra=[]): array {
		$files = [];
		for ($i = 1; $i <= $count; $i++) {
			$files[] = ['fileId' => $i, 'status' => 'extracted'];
		}

		return array_merge(['batchId' => 'b-1', 'userId' => 'u', 'status' => 'review', 'files' => $files], $extra);

	}//end batch()

	/**
	 * Above the synchronous cap nothing is anonymized in-request; the request is stored and 202 answered.
	 *
	 * @return void
	 */
	public function testALargeBatchIsEnqueuedWithItsRequest(): void {
		$entities = [['text' => 'Jan', 'type' => 'person']];
		$this->state->method('getBatch')->willReturn(self::batch(count: 480));
		$this->routing->method('anonymizesInBackground')->with(480)->willReturn(true);
		$this->anon->expects($this->never())->method('anonymizeDocument');
		$written = null;
		$this->state->expects($this->once())->method('updateBatch')->willReturnCallback(
			static function (string $batchId, array $batch) use (&$written): void {
				$written = $batch;
			}
		);

		$result = $this->service->anonymizeBatch(batchId: 'b-1', entities: $entities, outputFormat: 'pdf');

		$this->assertTrue($result['background']);
		$this->assertSame('anonymizing', $result['batchStatus']);
		$this->assertSame(480, $result['totalFiles']);
		$this->assertSame('/apps/filinq/api/anonymization/batch/b-1/status', $result['statusLocation']);
		$this->assertSame('anonymizing', $written['status']);
		$this->assertTrue($written['background']);
		$this->assertSame($entities, $written['anonymizeRequest']['entities']);
		$this->assertSame('pdf', $written['anonymizeRequest']['callOptions']['outputFormat']);
		$this->assertSame(202, (new BatchRequestService(l10n: $this->createMock(IL10N::class)))->resolveHttpStatus(result: $result));

	}//end testALargeBatchIsEnqueuedWithItsRequest()

	/**
	 * At or below the cap the pre-change synchronous run and response shape stay.
	 *
	 * @return void
	 */
	public function testASmallBatchIsStillAnonymizedInRequest(): void {
		$this->state->method('getBatch')->willReturn(self::batch(count: 2));
		$this->routing->method('anonymizesInBackground')->willReturn(false);
		$this->anon->expects($this->exactly(2))->method('anonymizeDocument')->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 9]);

		$result = $this->service->anonymizeBatch(batchId: 'b-1', entities: [['text' => 'Jan']]);

		$this->assertArrayNotHasKey('background', $result);
		$this->assertSame('completed', $result['batchStatus']);
		$this->assertSame(2, $result['processedFiles']);

	}//end testASmallBatchIsStillAnonymizedInRequest()

	/**
	 * The stored request comes back with defaults filled in; a batch without one has none.
	 *
	 * @return void
	 */
	public function testTheStoredRequestIsRebuiltWithDefaults(): void {
		$path = new BatchBackgroundPath(stateService: $this->state, routing: $this->routing, linkReader: $this->links);
		$request = $path->storedRequest(batch: ['anonymizeRequest' => ['entities' => [['text' => 'A']], 'callOptions' => ['scope' => 'document']]]);

		$this->assertInstanceOf(BatchAnonymizeRequest::class, $request);
		$this->assertSame([['text' => 'A']], $request->entities);
		$this->assertSame(
			['appendBasisSummary' => false, 'unredactedEntities' => [], 'outputFormat' => 'pdf-only', 'scope' => 'document'],
			$request->callOptions
		);
		$this->assertNull($path->storedRequest(batch: []));
		$this->assertNull($path->storedRequest(batch: ['anonymizeRequest' => ['callOptions' => []]]));

	}//end testTheStoredRequestIsRebuiltWithDefaults()

	/**
	 * One file of a background batch gets the same per-file step as the in-request path.
	 *
	 * @return void
	 */
	public function testAnonymizeAtRunsThePerFileStep(): void {
		$this->anon->expects($this->once())->method('anonymizeDocument')->willReturn(['replacementCount' => 3, 'anonymizedFileId' => 41]);
		$request = new BatchAnonymizeRequest(
			entities: [['text' => 'A']],
			callOptions: ['appendBasisSummary' => false, 'unredactedEntities' => [], 'outputFormat' => 'pdf', 'scope' => 'dossier']
		);

		$batch = $this->service->anonymizeAt(batch: self::batch(count: 2), index: 1, request: $request);

		$this->assertSame('anonymized', $batch['files'][1]['status']);
		$this->assertSame(3, $batch['files'][1]['replacementCount']);
		$this->assertSame('extracted', $batch['files'][0]['status']);

	}//end testAnonymizeAtRunsThePerFileStep()

	/**
	 * Only a background batch carrying its request goes straight on after extraction.
	 *
	 * @return void
	 */
	public function testOnlyABackgroundBatchWithItsRequestContinuesAfterExtraction(): void {
		$path = new BatchBackgroundPath(stateService: $this->state, routing: $this->routing, linkReader: $this->links);

		$this->assertTrue($path->continuesAfterExtraction(batch: ['background' => true, 'anonymizeRequest' => ['entities' => []]]));
		$this->assertFalse($path->continuesAfterExtraction(batch: ['background' => true]));
		$this->assertFalse($path->continuesAfterExtraction(batch: ['anonymizeRequest' => ['entities' => []]]));

	}//end testOnlyABackgroundBatchWithItsRequestContinuesAfterExtraction()

	/**
	 * In a resumed batch a file whose copy was made after the batch started is taken from its link.
	 *
	 * @return void
	 */
	public function testAResumedFileWithAFreshLinkIsNotAnonymizedAgain(): void {
		$started = 1784189400;
		$this->state->method('getBatch')->willReturn(self::batch(count: 1, extra: ['startedAt' => $started, 'resumedAt' => ($started + 600)]));
		$this->routing->method('anonymizesInBackground')->willReturn(false);
		$this->links->method('forSource')->with(1)->willReturn(
			['anonymizedFileId' => 77, 'anonymizedFilePath' => '/d/anonymised/1.pdf', 'replacementCount' => 4, 'anonymizedAt' => date('c', ($started + 60))]
		);
		$this->anon->expects($this->never())->method('anonymizeDocument');

		$result = $this->service->anonymizeBatch(batchId: 'b-1', entities: [['text' => 'Jan']]);

		$this->assertSame(1, $result['processedFiles']);

	}//end testAResumedFileWithAFreshLinkIsNotAnonymizedAgain()

	/**
	 * A link older than the batch, or a batch that was never resumed, is no reason to skip.
	 *
	 * @return void
	 */
	public function testAnOldLinkOrAFirstRunStillAnonymizes(): void {
		$started = 1784189400;
		$this->routing->method('anonymizesInBackground')->willReturn(false);
		$this->links->method('forSource')->willReturn(['anonymizedFileId' => 77, 'anonymizedAt' => date('c', ($started - 3600))]);
		$this->anon->expects($this->exactly(2))->method('anonymizeDocument')->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 9]);
		$this->state->method('getBatch')->willReturnOnConsecutiveCalls(
			self::batch(count: 1, extra: ['startedAt' => $started, 'resumedAt' => ($started + 600)]),
			self::batch(count: 1, extra: ['startedAt' => $started]),
			self::batch(count: 1, extra: ['startedAt' => $started]),
			self::batch(count: 1, extra: ['startedAt' => $started])
		);

		$this->service->anonymizeBatch(batchId: 'b-1', entities: [['text' => 'Jan']]);
		$this->service->anonymizeBatch(batchId: 'b-1', entities: [['text' => 'Jan']]);

	}//end testAnOldLinkOrAFirstRunStillAnonymizes()
}//end class

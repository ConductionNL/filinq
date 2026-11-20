<?php

/**
 * Unit tests for BatchQaOutcomes and the QA lines of the batch report.
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
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-sampling-qa-routed-to-human-review-req-ddras-007
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BatchQaOutcomes;
use OCA\Filinq\Service\BatchReportService;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A sample's outcome comes from its documentReview, and the report carries it.
 *
 * @covers \OCA\Filinq\Service\BatchQaOutcomes
 * @covers \OCA\Filinq\Service\BatchReportService
 * @uses   \OCA\Filinq\Service\BatchReportAggregator
 */
class BatchQaOutcomesTest extends TestCase {

	/**
	 * ObjectService double.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * Subject.
	 *
	 * @var BatchQaOutcomes
	 */
	private BatchQaOutcomes $outcomes;

	/**
	 * Build the subject.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(ObjectService::class);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$this->outcomes = new BatchQaOutcomes(objectResolver: $resolver, logger: $this->createMock(LoggerInterface::class));

	}//end setUp()

	/**
	 * Reviews per file id: none, without additions, with additions.
	 *
	 * @return void
	 */
	private function reviews(): void {
		$this->objects->method('searchObjectsBySlug')->willReturnCallback(
			static function (string $register, string $schema, array $filters): array {
				return match ($filters['fileId']) {
					2 => [['fileId' => 2, 'manualEntityCount' => 0]],
					3 => [['fileId' => 3, 'manualEntityCount' => 2]],
					default => [],
				};
			}
		);

	}//end reviews()

	/**
	 * No review is pending, a review without additions confirmed, one with additions corrected.
	 *
	 * @return void
	 */
	public function testTheOutcomeFollowsTheReview(): void {
		$this->reviews();

		$this->assertSame('pending', $this->outcomes->outcomeFor(fileId: 1));
		$this->assertSame('confirmed', $this->outcomes->outcomeFor(fileId: 2));
		$this->assertSame('corrected', $this->outcomes->outcomeFor(fileId: 3));
		$this->assertNull($this->outcomes->outcomeFor(fileId: 0));

	}//end testTheOutcomeFollowsTheReview()

	/**
	 * The review is looked up in the filinq register by source file id.
	 *
	 * @return void
	 */
	public function testTheReviewIsLookedUpByFileId(): void {
		$this->objects->expects($this->once())->method('searchObjectsBySlug')
			->with('filinq', 'documentReview', ['fileId' => 7])
			->willReturn([]);

		$this->outcomes->outcomeFor(fileId: 7);

	}//end testTheReviewIsLookedUpByFileId()

	/**
	 * Only sampled files are refreshed; a failed read keeps the stored outcome.
	 *
	 * @return void
	 */
	public function testRefreshTouchesSampledFilesOnly(): void {
		$this->objects->method('searchObjectsBySlug')->willReturnCallback(
			static function (string $register, string $schema, array $filters): array {
				if ($filters['fileId'] === 4) {
					throw new RuntimeException('down');
				}

				return [['manualEntityCount' => 1]];
			}
		);
		$batch = [
			'files' => [
				['fileId' => 1, 'status' => 'anonymized'],
				['fileId' => 3, 'status' => 'anonymized', 'qaSampled' => true, 'qaOutcome' => 'pending'],
				['fileId' => 4, 'status' => 'anonymized', 'qaSampled' => true, 'qaOutcome' => 'pending'],
			],
		];

		$refreshed = $this->outcomes->refresh(batch: $batch);

		$this->assertArrayNotHasKey('qaOutcome', $refreshed['files'][0]);
		$this->assertSame('corrected', $refreshed['files'][1]['qaOutcome']);
		$this->assertSame('pending', $refreshed['files'][2]['qaOutcome']);

	}//end testRefreshTouchesSampledFilesOnly()

	/**
	 * The report carries each sample's current outcome, and says when sampling was off.
	 *
	 * @return void
	 */
	public function testTheReportCarriesTheOutcomesAndTheDisabledSampling(): void {
		$this->reviews();
		$state = $this->createMock(BatchStateService::class);
		$state->method('getBatch')->willReturnOnConsecutiveCalls(
			[
				'batchId' => 'b', 'status' => 'completed', 'createdAt' => 0, 'qaSampleRate' => 5,
				'files' => [['fileId' => 2, 'fileName' => 'a.pdf', 'status' => 'anonymized', 'qaSampled' => true, 'qaOutcome' => 'pending']],
			],
			['batchId' => 'b', 'status' => 'completed', 'createdAt' => 0, 'qaSampleRate' => 0, 'files' => [['fileId' => 2, 'fileName' => 'a.pdf', 'status' => 'anonymized']]]
		);
		$report = new BatchReportService(stateService: $state, qaOutcomes: $this->outcomes);

		$sampled = $report->generateReport(batchId: 'b');
		$this->assertStringContainsString("qaSample,2,confirmed\n", $sampled);
		$this->assertStringNotContainsString('qaSampling', $sampled);

		$disabled = $report->generateReport(batchId: 'b');
		$this->assertStringContainsString("summary,qaSampling,disabled\n", $disabled);

	}//end testTheReportCarriesTheOutcomesAndTheDisabledSampling()
}//end class

<?php

/**
 * Unit tests for the per-document checked gate on both anonymize commit paths.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\AnonymizationController;
use OCA\Filinq\Controller\BatchAnonymizationController;
use OCA\Filinq\Controller\Support\AnonymizationServices;
use OCA\Filinq\Kernel\Contracts\AnonymizationProfileServiceInterface;
use OCA\Filinq\Kernel\Contracts\AnonymizeRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchAnonymizeServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchExtractionServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchReportServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchUploadServiceInterface;
use OCA\Filinq\Kernel\Contracts\EntityConsolidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\FolderBatchServiceInterface;
use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\FileListingService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\Review\DocumentReviewRepository;
use OCA\Filinq\Service\Review\DocumentReviewService;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The checked gate refuses, reports or lets through, on the single and the batch path.
 *
 * @covers \OCA\Filinq\Controller\AnonymizationController
 * @covers \OCA\Filinq\Controller\BatchAnonymizationController
 * @uses   \OCA\Filinq\Controller\Support\AnonymizationServices
 * @uses   \OCA\Filinq\Controller\Support\OutputFormatChoice
 * @uses   \OCA\Filinq\Kernel\AnonymizeCall
 * @uses   \OCA\Filinq\Controller\Support\CheckedGateReport
 * @uses   \OCA\Filinq\Service\Review\DocumentReviewService
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CheckedGateTest extends TestCase {

	/**
	 * A gate verdict.
	 *
	 * @param bool   $passed    Whether every file holds a mark.
	 * @param string $mode      The gate mode.
	 * @param list<array{fileId: int, fileName: string}> $unchecked The unchecked files.
	 *
	 * @return array{passed: bool, mode: string, uncheckedFiles: list<array{fileId: int, fileName: string}>}
	 */
	private static function verdict(bool $passed, string $mode, array $unchecked = []): array {
		return ['passed' => $passed, 'mode' => $mode, 'uncheckedFiles' => $unchecked];
	}//end verdict()

	/**
	 * A translator that returns the source string.
	 *
	 * @return IL10N&MockObject
	 */
	private function l10n(): IL10N&MockObject {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		return $l10n;
	}//end l10n()

	/**
	 * A request carrying an explicit output format.
	 *
	 * @return IRequest&MockObject
	 */
	private function request(): IRequest&MockObject {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn(['outputFormat' => 'pdf-only']);
		return $request;
	}//end request()

	/**
	 * The single-file controller over a request service that allows everything.
	 *
	 * @param DocumentReviewService|null $reviews The gate.
	 * @param bool                       $runs    Whether the anonymisation must run.
	 *
	 * @return AnonymizationController The controller.
	 */
	private function single(?DocumentReviewService $reviews, bool $runs): AnonymizationController {
		$requests = $this->createMock(AnonymizeRequestServiceInterface::class);
		$requests->method('requireAuthenticated')->willReturn(null);
		$requests->method('validateBody')->willReturn(['error' => null, 'request' => ['unredactedEntities' => []]]);
		$requests->method('verifyFileAccess')->willReturn(null);
		$requests->method('checkUnredactedProhibitions')->willReturn(null);
		$requests->method('currentUserId')->willReturn('w.devries');
		$requests->expects($runs === true ? $this->once() : $this->never())->method('executeAnonymize')
			->willReturn(['body' => ['anonymizedFileId' => 99], 'status' => 200]);

		return new AnonymizationController(
			request: $this->request(),
			logger: $this->createMock(LoggerInterface::class),
			services: new AnonymizationServices(
				anonymization: $this->createMock(AnonymizationService::class),
				files: $this->createMock(FileListingService::class)
			),
			l10n: $this->l10n(),
			appConfig: $this->createMock(IAppConfig::class),
			anonymizeRequest: $requests,
			reviews: $reviews
		);
	}//end single()

	/**
	 * A gate double answering the given verdict and expecting the given files.
	 *
	 * @param array<string, mixed> $verdict The verdict.
	 * @param array<int, array<string, mixed>> $files The files the gate must be asked about.
	 *
	 * @return DocumentReviewService&MockObject The gate.
	 */
	private function gate(array $verdict, array $files): DocumentReviewService&MockObject {
		$l10n = $this->l10n();
		$reviews = $this->getMockBuilder(DocumentReviewService::class)
			->setConstructorArgs(
				[
					$this->createMock(DocumentReviewRepository::class),
					$this->createMock(OpenRegisterServiceLocator::class),
					$this->createMock(IAppConfig::class),
					$this->createMock(LoggerInterface::class),
					$l10n,
				]
			)
			->onlyMethods(['gate'])
			->getMock();
		$reviews->expects($this->once())->method('gate')->with($files)->willReturn($verdict);
		return $reviews;
	}//end gate()

	/**
	 * Enforced and unchecked: 409 `document_not_reviewed`, and no run.
	 *
	 * @return void
	 */
	public function testAnUncheckedDocumentIsRefused(): void {
		$unchecked = [['fileId' => 42, 'fileName' => '']];
		$response = $this->single(
			reviews: $this->gate(verdict: self::verdict(passed: false, mode: 'enforced', unchecked: $unchecked), files: [['fileId' => 42]]),
			runs: false
		)->anonymize(fileId: 42);

		$this->assertSame(409, $response->getStatus());
		$this->assertSame('document_not_reviewed', $response->getData()['reason']);
		$this->assertSame($unchecked, $response->getData()['uncheckedFiles']);
	}//end testAnUncheckedDocumentIsRefused()

	/**
	 * Enforced and checked: the run proceeds and its answer is unchanged.
	 *
	 * @return void
	 */
	public function testACheckedDocumentProceedsUnchanged(): void {
		$response = $this->single(
			reviews: $this->gate(verdict: self::verdict(passed: true, mode: 'enforced'), files: [['fileId' => 42]]),
			runs: true
		)->anonymize(fileId: 42);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['anonymizedFileId' => 99], $response->getData());
	}//end testACheckedDocumentProceedsUnchanged()

	/**
	 * Advisory and unchecked: the run proceeds and the answer carries the verdict.
	 *
	 * @return void
	 */
	public function testAdvisoryModeProceedsWithTheVerdict(): void {
		$unchecked = [['fileId' => 42, 'fileName' => '']];
		$response = $this->single(
			reviews: $this->gate(verdict: self::verdict(passed: false, mode: 'advisory', unchecked: $unchecked), files: [['fileId' => 42]]),
			runs: true
		)->anonymize(fileId: 42);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(99, $response->getData()['anonymizedFileId']);
		$this->assertSame(['passed' => false, 'uncheckedFiles' => $unchecked], $response->getData()['checkedGate']);
	}//end testAdvisoryModeProceedsWithTheVerdict()

	/**
	 * The batch controller over doubles that allow everything.
	 *
	 * @param DocumentReviewService|null $reviews The gate.
	 * @param array<string, mixed>|null  $batch   The batch the state service holds.
	 * @param bool                       $runs    Whether the batch must run.
	 *
	 * @return BatchAnonymizationController The controller.
	 */
	private function batch(?DocumentReviewService $reviews, ?array $batch, bool $runs): BatchAnonymizationController {
		$state = $this->createMock(BatchStateServiceInterface::class);
		$state->method('getBatch')->with('b-1')->willReturn($batch);
		$anon = $this->createMock(BatchAnonymizeServiceInterface::class);
		$anon->expects($runs === true ? $this->once() : $this->never())->method('anonymizeBatch')
			->willReturn(['batchId' => 'b-1', 'processedFiles' => 2]);
		$requests = $this->createMock(BatchRequestServiceInterface::class);
		$requests->method('validateBody')->willReturn(
			['error' => null, 'request' => ['entities' => [], 'unredactedEntities' => [], 'appendBasisSummary' => false, 'hasStrayBases' => false]]
		);
		$requests->method('validateUnredactedEntries')->willReturn(null);
		$requests->method('resolveScope')->willReturn('dossier');
		$requests->method('resolveHttpStatus')->willReturn(200);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($this->createMock(IUser::class));

		return new BatchAnonymizationController(
			'filinq',
			$this->request(),
			$this->createMock(LoggerInterface::class),
			$state,
			$this->createMock(BatchUploadServiceInterface::class),
			$this->createMock(BatchExtractionServiceInterface::class),
			$anon,
			$this->createMock(BatchReportServiceInterface::class),
			$this->createMock(EntityConsolidationServiceInterface::class),
			$this->createMock(AnonymizationProfileServiceInterface::class),
			$this->createMock(FolderBatchServiceInterface::class),
			$this->l10n(),
			$this->createMock(IAppConfig::class),
			$session,
			$requests,
			$reviews
		);
	}//end batch()

	/**
	 * Three files, one in error.
	 *
	 * @var array<string, mixed>
	 */
	private const BATCH = [
		'files' => [
			['fileId' => 1, 'fileName' => 'a.pdf', 'status' => 'extracted'],
			['fileId' => '2', 'fileName' => 'b.pdf', 'status' => 'extracted'],
			['fileId' => 3, 'fileName' => 'c.pdf', 'status' => 'error'],
		],
	];

	/**
	 * Enforced with one unchecked file: 409 listing it, and nothing runs.
	 *
	 * @return void
	 */
	public function testTheBatchIsRefusedAtomically(): void {
		$unchecked = [['fileId' => 2, 'fileName' => 'b.pdf']];
		$gate = $this->gate(
			verdict: self::verdict(passed: false, mode: 'enforced', unchecked: $unchecked),
			files: [['fileId' => 1, 'fileName' => 'a.pdf'], ['fileId' => 2, 'fileName' => 'b.pdf']]
		);

		$response = $this->batch(reviews: $gate, batch: self::BATCH, runs: false)->batchAnonymize(batchId: 'b-1');

		$this->assertSame(409, $response->getStatus());
		$this->assertSame('documents_not_reviewed', $response->getData()['reason']);
		$this->assertSame($unchecked, $response->getData()['uncheckedFiles']);
	}//end testTheBatchIsRefusedAtomically()

	/**
	 * Enforced and all checked: the batch runs as before, without a verdict in the answer.
	 *
	 * @return void
	 */
	public function testAFullyCheckedBatchProceeds(): void {
		$gate = $this->gate(verdict: self::verdict(passed: true, mode: 'enforced'), files: [['fileId' => 1, 'fileName' => 'a.pdf'], ['fileId' => 2, 'fileName' => 'b.pdf']]);

		$response = $this->batch(reviews: $gate, batch: self::BATCH, runs: true)->batchAnonymize(batchId: 'b-1');

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['batchId' => 'b-1', 'processedFiles' => 2], $response->getData());
	}//end testAFullyCheckedBatchProceeds()

	/**
	 * Advisory: the batch runs and the answer carries the verdict.
	 *
	 * @return void
	 */
	public function testAnAdvisoryBatchReportsTheVerdict(): void {
		$unchecked = [['fileId' => 2, 'fileName' => 'b.pdf']];
		$gate = $this->gate(
			verdict: self::verdict(passed: false, mode: 'advisory', unchecked: $unchecked),
			files: [['fileId' => 1, 'fileName' => 'a.pdf'], ['fileId' => 2, 'fileName' => 'b.pdf']]
		);

		$response = $this->batch(reviews: $gate, batch: self::BATCH, runs: true)->batchAnonymize(batchId: 'b-1');

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['passed' => false, 'uncheckedFiles' => $unchecked], $response->getData()['checkedGate']);
	}//end testAnAdvisoryBatchReportsTheVerdict()

	/**
	 * An unknown batch has no files to judge and is left to the run, which answers for it.
	 *
	 * @return void
	 */
	public function testAnUnknownBatchIsLeftToTheRun(): void {
		$reviews = $this->gate(verdict: self::verdict(passed: true, mode: 'enforced'), files: []);

		$response = $this->batch(reviews: $reviews, batch: null, runs: true)->batchAnonymize(batchId: 'b-1');

		$this->assertSame(200, $response->getStatus());
		$this->assertArrayNotHasKey('checkedGate', $response->getData());
	}//end testAnUnknownBatchIsLeftToTheRun()

	/**
	 * The refusal names the problem in the reviewer's words.
	 *
	 * @return void
	 */
	public function testTheRefusalsCarryTheirMessages(): void {
		$single = $this->single(
			reviews: $this->gate(verdict: self::verdict(passed: false, mode: 'enforced', unchecked: [['fileId' => 42, 'fileName' => '']]), files: [['fileId' => 42]]),
			runs: false
		)->anonymize(fileId: 42);
		$batch = $this->batch(
			reviews: $this->gate(verdict: self::verdict(passed: false, mode: 'enforced', unchecked: [['fileId' => 1, 'fileName' => 'a.pdf']]), files: [['fileId' => 1, 'fileName' => 'a.pdf'], ['fileId' => 2, 'fileName' => 'b.pdf']]),
			batch: self::BATCH,
			runs: false
		)->batchAnonymize(batchId: 'b-1');

		$this->assertStringContainsString('has not been marked as reviewed', $single->getData()['error']);
		$this->assertStringContainsString('Nothing was anonymised.', $batch->getData()['error']);
	}//end testTheRefusalsCarryTheirMessages()
}//end class

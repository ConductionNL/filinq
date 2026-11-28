<?php

/**
 * Unit tests for the batch commit path against the review rendition.
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
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\BatchAnonymizationController;
use OCA\Filinq\Kernel\Contracts\AnonymizationProfileServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchAnonymizeServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchExtractionServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchReportServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchUploadServiceInterface;
use OCA\Filinq\Kernel\Contracts\EntityConsolidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\FolderBatchServiceInterface;
use OCA\Filinq\Service\Review\DocumentReviewService;
use OCA\Filinq\Tests\Unit\Support\BuildsInMemoryReviewGate;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The batch commit refuses a batch whose check was made on another rendition, through the real gate.
 *
 * @covers \OCA\Filinq\Controller\BatchAnonymizationController
 * @uses   \OCA\Filinq\Controller\Support\CheckedGateReport
 * @uses   \OCA\Filinq\Service\Review\DocumentReviewService
 * @uses   \OCA\Filinq\Service\Review\CertaintyThresholds
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchAnonymizationControllerTest extends TestCase {
	use BuildsInMemoryReviewGate;

	/**
	 * Two extracted files.
	 *
	 * @var array<string, mixed>
	 */
	private const BATCH = [
		'files' => [
			['fileId' => 1, 'fileName' => 'a.docx', 'status' => 'extracted'],
			['fileId' => 2, 'fileName' => 'b.pdf', 'status' => 'extracted'],
		],
	];

	/**
	 * The batch controller over doubles that allow everything but the gate.
	 *
	 * @param DocumentReviewService $reviews The gate.
	 * @param bool                  $runs    Whether the batch must run.
	 *
	 * @return BatchAnonymizationController The controller.
	 */
	private function controller(DocumentReviewService $reviews, bool $runs): BatchAnonymizationController {
		$state = $this->createMock(BatchStateServiceInterface::class);
		$state->method('getBatch')->with('b-1')->willReturn(self::BATCH);
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
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn(['outputFormat' => 'pdf-only']);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new BatchAnonymizationController(
			'filinq',
			$request,
			$this->createMock(LoggerInterface::class),
			$state,
			$this->createMock(BatchUploadServiceInterface::class),
			$this->createMock(BatchExtractionServiceInterface::class),
			$anon,
			$this->createMock(BatchReportServiceInterface::class),
			$this->createMock(EntityConsolidationServiceInterface::class),
			$this->createMock(AnonymizationProfileServiceInterface::class),
			$this->createMock(FolderBatchServiceInterface::class),
			$l10n,
			$this->createMock(IAppConfig::class),
			$session,
			$requests,
			$reviews
		);
	}//end controller()

	/**
	 * Both files checked on their renditions run; a new rendition for one refuses the whole batch.
	 *
	 * @return void
	 */
	public function testTheBatchPathHonoursTheRendition(): void {
		$this->gateRelations[1] = [['id' => 11, 'entityId' => 101, 'skipAnonymization' => false, 'confidence' => 0.99, 'detectionMethod' => 'presidio']];
		$this->gateRelations[2] = [['id' => 12, 'entityId' => 102, 'skipAnonymization' => false, 'confidence' => 0.99, 'detectionMethod' => 'presidio']];
		$this->gateRenditions[1] = ['sourceFileId' => 1, 'fileId' => 5, 'sha256' => 'a1', 'kind' => 'converted', 'textLayer' => 'native'];
		$this->gateRenditions[2] = ['sourceFileId' => 2, 'fileId' => 2, 'sha256' => 'b1', 'kind' => 'native-pdf', 'textLayer' => 'native'];
		$reviews = $this->inMemoryReviewGate();
		$reviews->check(fileId: 1, userId: 'w.devries');
		$reviews->check(fileId: 2, userId: 'w.devries');

		$this->assertSame(200, $this->controller(reviews: $reviews, runs: true)->batchAnonymize(batchId: 'b-1')->getStatus());

		$this->gateRenditions[1]['sha256'] = 'a2';
		$response = $this->controller(reviews: $reviews, runs: false)->batchAnonymize(batchId: 'b-1');

		$this->assertSame(409, $response->getStatus());
		$this->assertSame('documents_not_reviewed', $response->getData()['reason']);
		$this->assertSame([['fileId' => 1, 'fileName' => 'a.docx']], $response->getData()['uncheckedFiles']);
	}//end testTheBatchPathHonoursTheRendition()
}//end class

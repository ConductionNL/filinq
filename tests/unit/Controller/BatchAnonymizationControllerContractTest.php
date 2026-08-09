<?php

/**
 * Wire-contract tests for the batch-anonymization HTTP surface
 *
 * Covers `batchAnonymization#batchUpload` (POST api/anonymization/batch/upload),
 * `batchAnonymization#batchExtract` (POST .../batch/{batchId}/extract),
 * `batchAnonymization#batchStatus` (GET .../batch/{batchId}/status),
 * `batchAnonymization#batchReport` (GET .../batch/{batchId}/report) and
 * `batchAnonymization#getProfiles` (GET api/anonymization/profiles).
 *
 * Each endpoint is asserted on the status code and body shape it documents,
 * on its 401 anonymous rejection, and on the failure mapping the controller
 * promises (400 for a rejected upload, the exception's own HTTP code for a
 * coded failure, 500 otherwise). The report endpoint is additionally asserted
 * to be a CSV *download*, not a JSON envelope.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-completion-report
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\BatchAnonymizationController;
use OCA\DocuDesk\Service\BatchAnonymizeService;
use OCA\DocuDesk\Service\BatchExtractionService;
use OCA\DocuDesk\Service\BatchReportService;
use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\BatchUploadService;
use OCA\DocuDesk\Service\EntityConsolidationService;
use OCA\DocuDesk\Service\FolderBatchService;
use OCA\DocuDesk\Service\WooProfileService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for the five batch endpoints that had no contract test.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress                                 PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BatchAnonymizationControllerContractTest extends TestCase
{

    /**
     * Mocked request.
     *
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $request;

    /**
     * Mocked batch state store.
     *
     * @var BatchStateService|MockObject
     */
    private BatchStateService|MockObject $stateService;

    /**
     * Mocked upload service.
     *
     * @var BatchUploadService|MockObject
     */
    private BatchUploadService|MockObject $uploadService;

    /**
     * Mocked extraction service.
     *
     * @var BatchExtractionService|MockObject
     */
    private BatchExtractionService|MockObject $extractService;

    /**
     * Mocked report service.
     *
     * @var BatchReportService|MockObject
     */
    private BatchReportService|MockObject $reportService;

    /**
     * Mocked WOO profile service.
     *
     * @var WooProfileService|MockObject
     */
    private WooProfileService|MockObject $profileService;

    /**
     * Mocked localisation.
     *
     * @var IL10N|MockObject
     */
    private IL10N|MockObject $l10n;

    /**
     * Controller under test, with an authenticated session.
     *
     * @var BatchAnonymizationController
     */
    private BatchAnonymizationController $controller;


    /**
     * Set up an authenticated controller.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->request        = $this->createMock(IRequest::class);
        $this->stateService   = $this->createMock(BatchStateService::class);
        $this->uploadService  = $this->createMock(BatchUploadService::class);
        $this->extractService = $this->createMock(BatchExtractionService::class);
        $this->reportService  = $this->createMock(BatchReportService::class);
        $this->profileService = $this->createMock(WooProfileService::class);
        $this->l10n           = $this->createMock(IL10N::class);
        $this->l10n->method('t')->willReturnCallback(
            static function (string $text): string {
                return $text;
            }
        );

        $this->controller = $this->buildController($this->authenticatedSession());

    }//end setUp()


    /**
     * Build the controller for a given session.
     *
     * @param IUserSession $session The session the controller should see.
     *
     * @return BatchAnonymizationController The controller under test.
     */
    private function buildController(IUserSession $session): BatchAnonymizationController
    {
        return new BatchAnonymizationController(
            'docudesk',
            $this->request,
            $this->createMock(LoggerInterface::class),
            $this->stateService,
            $this->uploadService,
            $this->extractService,
            $this->createMock(BatchAnonymizeService::class),
            $this->reportService,
            $this->createMock(EntityConsolidationService::class),
            $this->profileService,
            $this->createMock(FolderBatchService::class),
            $this->l10n,
            $this->createMock(IAppConfig::class),
            $session
        );

    }//end buildController()


    /**
     * Build a session with a logged-in user.
     *
     * @return IUserSession The authenticated session.
     */
    private function authenticatedSession(): IUserSession
    {
        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('alice');
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn($user);

        return $session;

    }//end authenticatedSession()


    /**
     * Build a session with no logged-in user.
     *
     * @return IUserSession The anonymous session.
     */
    private function anonymousSession(): IUserSession
    {
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn(null);

        return $session;

    }//end anonymousSession()


    /**
     * A multi-file upload answers 200 with `{batchId, fileCount, files}`, and
     * `fileCount` is derived from the stored batch, not from the request.
     *
     * @return void
     */
    public function testBatchUploadReturnsBatchMetadata(): void
    {
        $files = [['name' => 'a.pdf'], ['name' => 'b.pdf']];

        $this->uploadService->method('collectFiles')->willReturn($files);
        $this->uploadService->method('getUserId')->willReturn('alice');
        $this->stateService->method('getMaxFiles')->willReturn(50);
        $this->uploadService->expects($this->once())
            ->method('processBatchUpload')
            ->with('alice', $files)
            ->willReturn(
                [
                    'batchId' => 'batch-1',
                    'files'   => [
                        ['fileId' => 1, 'name' => 'a.pdf', 'status' => 'pending'],
                        ['fileId' => 2, 'name' => 'b.pdf', 'status' => 'pending'],
                    ],
                ]
            );

        $response = $this->controller->batchUpload();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertSame('batch-1', $data['batchId']);
        $this->assertSame(2, $data['fileCount']);
        $this->assertCount(2, $data['files']);

    }//end testBatchUploadReturnsBatchMetadata()


    /**
     * An upload with no files answers 400 and nothing is persisted.
     *
     * @return void
     */
    public function testBatchUploadRejectsEmptyUpload(): void
    {
        $this->uploadService->method('collectFiles')->willReturn([]);
        $this->uploadService->expects($this->never())->method('processBatchUpload');

        $response = $this->controller->batchUpload();

        $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertSame(['error' => 'No files uploaded'], $response->getData());

    }//end testBatchUploadRejectsEmptyUpload()


    /**
     * An upload above the configured maximum answers 400 before any file is
     * written — the size limit is enforced server-side, not only in the UI.
     *
     * @return void
     */
    public function testBatchUploadRejectsOversizedBatch(): void
    {
        $this->uploadService->method('collectFiles')->willReturn(
            [['name' => 'a.pdf'], ['name' => 'b.pdf'], ['name' => 'c.pdf']]
        );
        $this->stateService->method('getMaxFiles')->willReturn(2);
        $this->uploadService->expects($this->never())->method('processBatchUpload');

        $response = $this->controller->batchUpload();

        $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertSame(['error' => 'Batch size exceeds maximum'], $response->getData());

    }//end testBatchUploadRejectsOversizedBatch()


    /**
     * An anonymous caller cannot create a batch.
     *
     * @return void
     */
    public function testBatchUploadRejectsAnonymousCaller(): void
    {
        $this->uploadService->expects($this->never())->method('processBatchUpload');

        $response = $this->buildController($this->anonymousSession())->batchUpload();

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
        $this->assertSame(['error' => 'Not authenticated'], $response->getData());

    }//end testBatchUploadRejectsAnonymousCaller()


    /**
     * Extracting the next file answers 200 with the service's per-file result
     * verbatim, so the UI can drive the sequential loop from `remaining`.
     *
     * @return void
     */
    public function testBatchExtractReturnsPerFileResult(): void
    {
        $result = [
            'fileId'      => 1,
            'status'      => 'extracted',
            'entityCount' => 4,
            'remaining'   => 1,
        ];

        $this->extractService->expects($this->once())
            ->method('extractNext')
            ->with('batch-1')
            ->willReturn($result);

        $response = $this->controller->batchExtract('batch-1');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame($result, $response->getData());

    }//end testBatchExtractReturnsPerFileResult()


    /**
     * An unknown batch surfaces the service's own HTTP code (404) rather than
     * a blanket 500.
     *
     * @return void
     */
    public function testBatchExtractSurfacesCodedFailureStatus(): void
    {
        $this->extractService->method('extractNext')
            ->willThrowException(new RuntimeException('Batch not found', 404));

        $response = $this->controller->batchExtract('missing');

        $this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertArrayHasKey('error', $response->getData());

    }//end testBatchExtractSurfacesCodedFailureStatus()


    /**
     * An uncoded backend failure is normalised to 500 — an exception code of 0
     * must never become the HTTP status.
     *
     * @return void
     */
    public function testBatchExtractNormalisesUncodedFailureTo500(): void
    {
        $this->extractService->method('extractNext')
            ->willThrowException(new RuntimeException('presidio unreachable'));

        $response = $this->controller->batchExtract('batch-1');

        $this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());

    }//end testBatchExtractNormalisesUncodedFailureTo500()


    /**
     * An anonymous caller cannot advance a batch.
     *
     * @return void
     */
    public function testBatchExtractRejectsAnonymousCaller(): void
    {
        $this->extractService->expects($this->never())->method('extractNext');

        $response = $this->buildController($this->anonymousSession())->batchExtract('batch-1');

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

    }//end testBatchExtractRejectsAnonymousCaller()


    /**
     * Status answers 200 with the documented snapshot, and the aggregate
     * fields are computed from the stored per-file records: two of four files
     * in a terminal state is 50% progress and 7 entities.
     *
     * @return void
     */
    public function testBatchStatusReturnsAggregatedSnapshot(): void
    {
        $this->stateService->method('getBatch')->with('batch-1')->willReturn(
            [
                'batchId' => 'batch-1',
                'status'  => 'extracting',
                'files'   => [
                    ['fileId' => 1, 'status' => 'extracted', 'entityCount' => 3],
                    ['fileId' => 2, 'status' => 'error', 'entityCount' => 4],
                    ['fileId' => 3, 'status' => 'pending', 'entityCount' => 0],
                    ['fileId' => 4, 'status' => 'pending', 'entityCount' => 0],
                ],
            ]
        );

        $response = $this->controller->batchStatus('batch-1');

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertSame('batch-1', $data['batchId']);
        $this->assertSame('extracting', $data['batchStatus']);
        $this->assertSame(7, $data['totalEntities']);
        $this->assertSame(50.0, $data['progress']);
        $this->assertSame(4, $data['totalFiles']);
        $this->assertCount(4, $data['files']);

    }//end testBatchStatusReturnsAggregatedSnapshot()


    /**
     * An unknown batch answers 404 with an error body, never an empty snapshot
     * that the UI would render as "0 files, done".
     *
     * @return void
     */
    public function testBatchStatusReturnsNotFoundForUnknownBatch(): void
    {
        $this->stateService->method('getBatch')->willReturn(null);

        $response = $this->controller->batchStatus('missing');

        $this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertSame(['error' => 'Batch not found'], $response->getData());

    }//end testBatchStatusReturnsNotFoundForUnknownBatch()


    /**
     * An anonymous caller cannot poll batch status.
     *
     * @return void
     */
    public function testBatchStatusRejectsAnonymousCaller(): void
    {
        $this->stateService->expects($this->never())->method('getBatch');

        $response = $this->buildController($this->anonymousSession())->batchStatus('batch-1');

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

    }//end testBatchStatusRejectsAnonymousCaller()


    /**
     * The report endpoint answers with a CSV download whose filename carries
     * the batch id — not a JSON envelope the browser would render as text.
     *
     * @return void
     */
    public function testBatchReportReturnsCsvDownload(): void
    {
        $csv = "file,entities\na.pdf,3\n";

        $this->reportService->expects($this->once())
            ->method('generateReport')
            ->with('batch-1')
            ->willReturn($csv);

        $response = $this->controller->batchReport('batch-1');

        $this->assertInstanceOf(DataDownloadResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame($csv, $response->getData());
        $this->assertSame(
            'attachment; filename="anonymization-report-batch-1.csv"',
            $response->getHeaders()['Content-Disposition']
        );

    }//end testBatchReportReturnsCsvDownload()


    /**
     * A failing report answers a JSON error with the exception's HTTP code —
     * the client must not receive a half-written CSV.
     *
     * @return void
     */
    public function testBatchReportSurfacesFailureAsJsonError(): void
    {
        $this->reportService->method('generateReport')
            ->willThrowException(new RuntimeException('Batch not found', 404));

        $response = $this->controller->batchReport('missing');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertArrayHasKey('error', $response->getData());

    }//end testBatchReportSurfacesFailureAsJsonError()


    /**
     * An anonymous caller cannot download a batch report.
     *
     * @return void
     */
    public function testBatchReportRejectsAnonymousCaller(): void
    {
        $this->reportService->expects($this->never())->method('generateReport');

        $response = $this->buildController($this->anonymousSession())->batchReport('batch-1');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

    }//end testBatchReportRejectsAnonymousCaller()


    /**
     * The profiles endpoint answers 200 with the stored WOO profile — the
     * `anonymize` / `keep` entity-type arrays the review UI pre-selects from.
     *
     * @return void
     */
    public function testGetProfilesReturnsActiveProfile(): void
    {
        $profile = [
            'anonymize' => ['PERSON', 'BSN', 'EMAIL'],
            'keep'      => ['ORGANIZATION'],
        ];

        $this->profileService->expects($this->once())
            ->method('getProfile')
            ->willReturn($profile);

        $response = $this->controller->getProfiles();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame($profile, $response->getData());

    }//end testGetProfilesReturnsActiveProfile()


    /**
     * An anonymous caller cannot read the instance profile.
     *
     * @return void
     */
    public function testGetProfilesRejectsAnonymousCaller(): void
    {
        $this->profileService->expects($this->never())->method('getProfile');

        $response = $this->buildController($this->anonymousSession())->getProfiles();

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
        $this->assertSame(['error' => 'Not authenticated'], $response->getData());

    }//end testGetProfilesRejectsAnonymousCaller()


}//end class

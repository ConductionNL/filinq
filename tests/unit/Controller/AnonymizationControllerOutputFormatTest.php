<?php

/**
 * Unit tests for AnonymizationController and BatchAnonymizationController
 * outputFormat validation, tenant default resolution, and HTTP 422/207 outcomes.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-8
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\AnonymizationController;
use OCA\DocuDesk\Controller\BatchAnonymizationController;
use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\BatchAnonymizeService;
use OCA\DocuDesk\Service\BatchExtractionService;
use OCA\DocuDesk\Service\BatchReportService;
use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\BatchUploadService;
use OCA\DocuDesk\Service\EntityConsolidationService;
use OCA\DocuDesk\Service\FileListingService;
use OCA\DocuDesk\Service\FolderBatchService;
use OCA\DocuDesk\Service\WooProfileService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for outputFormat validation and conversion failure handling in controllers
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress                                 PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AnonymizationControllerOutputFormatTest extends TestCase
{

    /**
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * @var AnonymizationService|MockObject
     */
    private AnonymizationService|MockObject $mockAnonService;

    /**
     * @var IL10N|MockObject
     */
    private IL10N|MockObject $mockL10n;

    /**
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * @var IRootFolder|MockObject
     */
    private IRootFolder|MockObject $mockRootFolder;

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockAppConfig;

    /**
     * @var AnonymizationController
     */
    private AnonymizationController $controller;

    /**
     * Set up controller under test with mocked dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest     = $this->createMock(IRequest::class);
        $this->mockAnonService = $this->createMock(AnonymizationService::class);
        $this->mockL10n        = $this->createMock(IL10N::class);
        $this->mockL10n->method('t')->willReturnCallback(
            static function (string $text, array $args=[]): string {
                if (empty($args) === false) {
                    return vsprintf($text, $args);
                }

                return $text;
            }
        );

        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('test-user');
        $this->mockUserSession = $this->createMock(IUserSession::class);
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $mockFile   = $this->createMock(File::class);
        $mockFolder = $this->createMock(Folder::class);
        $mockFolder->method('getById')->willReturn([$mockFile]);
        $this->mockRootFolder = $this->createMock(IRootFolder::class);
        $this->mockRootFolder->method('getUserFolder')->willReturn($mockFolder);

        $this->mockAppConfig = $this->createMock(IAppConfig::class);
        $this->mockAppConfig->method('getValueString')->willReturn('pdf');

        $this->controller = $this->buildController();

    }//end setUp()

    // -----------------------------------------------------------------------
    // Task 1: outputFormat validation — per-document controller
    // -----------------------------------------------------------------------

    /**
     * An invalid outputFormat value returns HTTP 400 before any service call.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-1
     */
    public function testReturns400ForInvalidOutputFormat(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'     => [['type' => 'PERSON', 'text' => 'Test']],
                'outputFormat' => 'rtf',
            ]
        );

        $this->mockAnonService->expects($this->never())->method('anonymizeDocument');

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(400, $response->getStatus());
        $data = $response->getData();
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('allowedValues', $data);

    }//end testReturns400ForInvalidOutputFormat()

    /**
     * An uppercase 'PDF' value is rejected (lowercase-only strict enum).
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-1
     */
    public function testReturns400ForUppercaseOutputFormat(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'     => [['type' => 'PERSON', 'text' => 'Test']],
                'outputFormat' => 'PDF',
            ]
        );

        $response = $this->controller->anonymize(fileId: 1);
        $this->assertSame(400, $response->getStatus());

    }//end testReturns400ForUppercaseOutputFormat()

    /**
     * 'Preserve' (capital P) is rejected — only lowercase 'preserve' is valid.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-1
     */
    public function testReturns400ForTitleCasePreserve(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'     => [['type' => 'PERSON', 'text' => 'Test']],
                'outputFormat' => 'Preserve',
            ]
        );

        $response = $this->controller->anonymize(fileId: 1);
        $this->assertSame(400, $response->getStatus());

    }//end testReturns400ForTitleCasePreserve()

    /**
     * 'pdf' (lowercase) is accepted and the service is called.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-1
     */
    public function testAcceptsPdfLowercase(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'     => [['type' => 'PERSON', 'text' => 'Test']],
                'outputFormat' => 'pdf',
            ]
        );

        $this->mockAnonService->expects($this->once())
            ->method('anonymizeDocument')
            ->with(
                fileId: 1,
                entities: $this->anything(),
                appendBasisSummary: false,
                outputFormat: 'pdf'
            )
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'x']);

        $response = $this->controller->anonymize(fileId: 1);
        $this->assertSame(200, $response->getStatus());

    }//end testAcceptsPdfLowercase()

    /**
     * 'preserve' (lowercase) is accepted and forwarded to the service.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-1
     */
    public function testAcceptsPreserveLowercase(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'     => [['type' => 'PERSON', 'text' => 'Test']],
                'outputFormat' => 'preserve',
            ]
        );

        $this->mockAnonService->expects($this->once())
            ->method('anonymizeDocument')
            ->with(
                fileId: 2,
                entities: $this->anything(),
                appendBasisSummary: false,
                outputFormat: 'preserve'
            )
            ->willReturn(['replacementCount' => 0, 'anonymizedFileId' => 'y']);

        $response = $this->controller->anonymize(fileId: 2);
        $this->assertSame(200, $response->getStatus());

    }//end testAcceptsPreserveLowercase()

    // -----------------------------------------------------------------------
    // Task 2: Tenant default resolution
    // -----------------------------------------------------------------------

    /**
     * When outputFormat is absent, the tenant default from IAppConfig is used.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-2
     */
    public function testTenantDefaultPreserveAppliedWhenOutputFormatAbsent(): void
    {
        // Tenant has set default to 'preserve'.
        $this->mockAppConfig = $this->createMock(IAppConfig::class);
        $this->mockAppConfig->method('getValueString')->willReturn('preserve');

        $this->controller = $this->buildController();

        $this->mockRequest->method('getParams')->willReturn(
            ['entities' => [['type' => 'PERSON', 'text' => 'Test']]]
        );

        $this->mockAnonService->expects($this->once())
            ->method('anonymizeDocument')
            ->with(
                fileId: 5,
                entities: $this->anything(),
                appendBasisSummary: false,
                outputFormat: 'preserve'
            )
            ->willReturn(['replacementCount' => 0, 'anonymizedFileId' => 'z']);

        $response = $this->controller->anonymize(fileId: 5);
        $this->assertSame(200, $response->getStatus());

    }//end testTenantDefaultPreserveAppliedWhenOutputFormatAbsent()

    /**
     * When no outputFormat is provided and no tenant default is set, 'pdf' is used.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-2
     */
    public function testHardDefaultIsPdfWhenNoPerCallAndNoTenantDefault(): void
    {
        $this->mockAppConfig = $this->createMock(IAppConfig::class);
        // getValueString returns its $default param when key is unset — simulate with 'pdf'.
        $this->mockAppConfig->method('getValueString')->willReturn('pdf');

        $this->controller = $this->buildController();

        $this->mockRequest->method('getParams')->willReturn(
            ['entities' => [['type' => 'LOCATION', 'text' => 'Amsterdam']]]
        );

        $this->mockAnonService->expects($this->once())
            ->method('anonymizeDocument')
            ->with(
                fileId: 6,
                entities: $this->anything(),
                appendBasisSummary: false,
                outputFormat: 'pdf'
            )
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'a']);

        $response = $this->controller->anonymize(fileId: 6);
        $this->assertSame(200, $response->getStatus());

    }//end testHardDefaultIsPdfWhenNoPerCallAndNoTenantDefault()

    /**
     * Per-call outputFormat overrides the tenant default.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-2
     */
    public function testPerCallOutputFormatOverridesTenantDefault(): void
    {
        // Tenant default is 'preserve' but per-call is 'pdf'.
        $this->mockAppConfig = $this->createMock(IAppConfig::class);
        $this->mockAppConfig->method('getValueString')->willReturn('preserve');

        $this->controller = $this->buildController();

        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'     => [['type' => 'PERSON', 'text' => 'Test']],
                'outputFormat' => 'pdf',
            ]
        );

        $this->mockAnonService->expects($this->once())
            ->method('anonymizeDocument')
            ->with(
                fileId: 7,
                entities: $this->anything(),
                appendBasisSummary: false,
                outputFormat: 'pdf'
            )
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'b']);

        $response = $this->controller->anonymize(fileId: 7);
        $this->assertSame(200, $response->getStatus());

    }//end testPerCallOutputFormatOverridesTenantDefault()

    // -----------------------------------------------------------------------
    // Task 4: ConversionFailedException → HTTP 422
    // -----------------------------------------------------------------------

    /**
     * ConversionFailedException from the service surfaces as HTTP 422 with
     * the structured conversionAttempts body.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-8
     */
    public function testReturns422WithStructuredBodyOnConversionFailure(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'     => [['type' => 'PERSON', 'text' => 'Test']],
                'outputFormat' => 'pdf',
            ]
        );

        $attempts = [
            ['backend' => 'office_app', 'available' => false, 'supports' => true, 'reason' => 'Not installed'],
            ['backend' => 'libreoffice_headless', 'available' => false, 'supports' => true, 'reason' => 'Binary missing'],
        ];

        $this->mockAnonService->method('anonymizeDocument')
            ->willThrowException(new ConversionFailedException(attempts: $attempts));

        $response = $this->controller->anonymize(fileId: 3);
        $data     = $response->getData();

        $this->assertSame(422, $response->getStatus());
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('conversionAttempts', $data);
        $this->assertSame($attempts, $data['conversionAttempts']);
        $this->assertSame('pdf', $data['outputFormat']);
        $this->assertArrayHasKey('fallback', $data);

    }//end testReturns422WithStructuredBodyOnConversionFailure()

    /**
     * When outputFormat is 'preserve', ConversionFailedException is never thrown
     * and the response is HTTP 200.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testPreservePathReturns200WithNoConversionAttempt(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'     => [['type' => 'PERSON', 'text' => 'Test']],
                'outputFormat' => 'preserve',
            ]
        );

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'p']);

        $response = $this->controller->anonymize(fileId: 4);
        $this->assertSame(200, $response->getStatus());

    }//end testPreservePathReturns200WithNoConversionAttempt()

    // -----------------------------------------------------------------------
    // Task 5: Batch controller HTTP 207 / 422 / 200 per per-file outcomes
    // -----------------------------------------------------------------------

    /**
     * Batch where all files succeeded returns HTTP 200.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-5
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-8
     */
    public function testBatchReturns200WhenAllFilesSucceed(): void
    {
        [$mockBatchService, $batchController] = $this->buildBatchController();

        $mockRequest = $this->createMock(IRequest::class);
        $mockRequest->method('getParams')->willReturn(
            ['entities' => [['type' => 'PERSON', 'text' => 'Test']]]
        );
        // phpcs:disable CustomSn.Functions.NamedParameters
        $batchController = new BatchAnonymizationController(
            'docudesk',
            $mockRequest,
            $this->createMock(LoggerInterface::class),
            $this->createMock(BatchStateService::class),
            $this->createMock(BatchUploadService::class),
            $this->createMock(BatchExtractionService::class),
            $mockBatchService,
            $this->createMock(BatchReportService::class),
            $this->createMock(EntityConsolidationService::class),
            $this->createMock(WooProfileService::class),
            $this->createMock(FolderBatchService::class),
            $this->mockL10n,
            $this->mockUserSession,
            $this->mockAppConfig
        );
        // phpcs:enable

        $mockBatchService->method('anonymizeBatch')->willReturn(
            [
                'batchId'               => 'b1',
                'batchStatus'           => 'completed',
                'processedFiles'        => 3,
                'skippedFiles'          => [],
                'conversionFailures'    => 0,
                'conversionFailedFiles' => [],
                'totalFiles'            => 3,
            ]
        );

        $response = $batchController->batchAnonymize(batchId: 'b1');
        $this->assertSame(200, $response->getStatus());

    }//end testBatchReturns200WhenAllFilesSucceed()

    /**
     * Batch where some files succeeded and some failed conversion returns HTTP 207.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-5
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-8
     */
    public function testBatchReturns207WhenSomeFilesFailConversion(): void
    {
        [$mockBatchService, $batchController] = $this->buildBatchController();

        $mockBatchService->method('anonymizeBatch')->willReturn(
            [
                'batchId'               => 'b2',
                'batchStatus'           => 'completed',
                'processedFiles'        => 2,
                'skippedFiles'          => [],
                'conversionFailures'    => 1,
                'conversionFailedFiles' => [
                    ['fileId' => 'f3', 'attempts' => [['backend' => 'mpdf', 'available' => false, 'supports' => false, 'reason' => 'XLSX unsupported']]],
                ],
                'totalFiles'            => 3,
            ]
        );

        $response = $batchController->batchAnonymize(batchId: 'b2');
        $this->assertSame(Http::STATUS_MULTI_STATUS, $response->getStatus());

    }//end testBatchReturns207WhenSomeFilesFailConversion()

    /**
     * Batch where all files failed conversion returns HTTP 422.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-5
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-8
     */
    public function testBatchReturns422WhenAllFilesFailConversion(): void
    {
        [$mockBatchService, $batchController] = $this->buildBatchController();

        $mockBatchService->method('anonymizeBatch')->willReturn(
            [
                'batchId'               => 'b3',
                'batchStatus'           => 'completed',
                'processedFiles'        => 0,
                'skippedFiles'          => [],
                'conversionFailures'    => 2,
                'conversionFailedFiles' => [
                    ['fileId' => 'f1', 'attempts' => []],
                    ['fileId' => 'f2', 'attempts' => []],
                ],
                'totalFiles'            => 2,
            ]
        );

        $response = $batchController->batchAnonymize(batchId: 'b3');
        $this->assertSame(422, $response->getStatus());

    }//end testBatchReturns422WhenAllFilesFailConversion()

    /**
     * Batch outputFormat validation rejects invalid values with HTTP 400.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-1
     */
    public function testBatchReturns400ForInvalidOutputFormat(): void
    {
        [$mockBatchService, $batchController] = $this->buildBatchController(outputFormat: 'docx');

        $mockBatchService->expects($this->never())->method('anonymizeBatch');

        $response = $batchController->batchAnonymize(batchId: 'b4');
        $this->assertSame(400, $response->getStatus());

    }//end testBatchReturns400ForInvalidOutputFormat()

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Instantiate AnonymizationController with mocked deps.
     *
     * @return AnonymizationController
     */
    private function buildController(): AnonymizationController
    {
        // phpcs:disable CustomSn.Functions.NamedParameters
        return new AnonymizationController(
            'docudesk',
            $this->mockRequest,
            $this->createMock(LoggerInterface::class),
            $this->mockAnonService,
            $this->createMock(FileListingService::class),
            $this->mockL10n,
            $this->mockUserSession,
            $this->mockRootFolder,
            $this->mockAppConfig
        );
        // phpcs:enable

    }//end buildController()

    /**
     * Instantiate BatchAnonymizationController with mocked deps.
     *
     * @param string $outputFormat outputFormat to put in the request params
     *
     * @return array{0: BatchAnonymizeService&MockObject, 1: BatchAnonymizationController}
     */
    private function buildBatchController(string $outputFormat='pdf'): array
    {
        $mockBatchService = $this->createMock(BatchAnonymizeService::class);

        $mockRequest = $this->createMock(IRequest::class);
        $mockRequest->method('getParams')->willReturn(
            [
                'entities'     => [['type' => 'PERSON', 'text' => 'Test']],
                'outputFormat' => $outputFormat,
            ]
        );

        // phpcs:disable CustomSn.Functions.NamedParameters
        $controller = new BatchAnonymizationController(
            'docudesk',
            $mockRequest,
            $this->createMock(LoggerInterface::class),
            $this->createMock(BatchStateService::class),
            $this->createMock(BatchUploadService::class),
            $this->createMock(BatchExtractionService::class),
            $mockBatchService,
            $this->createMock(BatchReportService::class),
            $this->createMock(EntityConsolidationService::class),
            $this->createMock(WooProfileService::class),
            $this->createMock(FolderBatchService::class),
            $this->mockL10n,
            $this->mockUserSession,
            $this->mockAppConfig
        );
        // phpcs:enable

        return [$mockBatchService, $controller];

    }//end buildBatchController()
}//end class

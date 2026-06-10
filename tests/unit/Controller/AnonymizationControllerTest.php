<?php

/**
 * Unit tests for AnonymizationController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-7
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\AnonymizationController;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\FileListingService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for AnonymizationController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AnonymizationControllerTest extends TestCase
{

    /**
     * Mocked IRequest
     *
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * Mocked LoggerInterface
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Mocked AnonymizationService
     *
     * @var AnonymizationService|MockObject
     */
    private AnonymizationService|MockObject $mockAnonService;

    /**
     * Mocked FileListingService
     *
     * @var FileListingService|MockObject
     */
    private FileListingService|MockObject $mockFileService;

    /**
     * Mocked IL10N
     *
     * @var IL10N|MockObject
     */
    private IL10N|MockObject $mockL10n;

    /**
     * Mocked IUserSession
     *
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * Mocked IRootFolder
     *
     * @var IRootFolder|MockObject
     */
    private IRootFolder|MockObject $mockRootFolder;

    /**
     * Controller under test
     *
     * @var AnonymizationController
     */
    private AnonymizationController $controller;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest     = $this->createMock(IRequest::class);
        $this->mockLogger      = $this->createMock(LoggerInterface::class);
        $this->mockAnonService = $this->createMock(AnonymizationService::class);
        $this->mockFileService = $this->createMock(FileListingService::class);
        $this->mockL10n        = $this->createMock(IL10N::class);

        $this->mockL10n->method('t')->willReturnCallback(
            static function (string $text): string {
                return $text;
            }
        );

        $mockUser              = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('test-user');
        $this->mockUserSession = $this->createMock(IUserSession::class);
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        // Default root-folder stub: file access always succeeds.
        $mockFile           = $this->createMock(File::class);
        $mockFolder         = $this->createMock(Folder::class);
        $mockFolder->method('getById')->willReturn([$mockFile]);
        $this->mockRootFolder = $this->createMock(IRootFolder::class);
        $this->mockRootFolder->method('getUserFolder')->willReturn($mockFolder);

        $this->controller = new AnonymizationController(
            appName: 'docudesk',
            request: $this->mockRequest,
            logger: $this->mockLogger,
            anonymizationService: $this->mockAnonService,
            fileListingService: $this->mockFileService,
            l10n: $this->mockL10n,
            appConfig: $this->createMock(\OCP\IAppConfig::class),
            userSession: $this->mockUserSession,
            rootFolder: $this->mockRootFolder
        );

    }//end setUp()


    /**
     * Test source file exists
     *
     * @return void
     */
    public function testSourceFileExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../lib/Controller/AnonymizationController.php'
        );

    }//end testSourceFileExists()


    /**
     * Test file contains class declaration
     *
     * @return void
     */
    public function testFileContainsClassDeclaration(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');
        $this->assertStringContainsString('class AnonymizationController', $content);

    }//end testFileContainsClassDeclaration()


    /**
     * Test file contains files method
     *
     * @return void
     */
    public function testFileContainsFilesMethod(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');
        $this->assertStringContainsString('function files()', $content);

    }//end testFileContainsFilesMethod()


    /**
     * Test file contains extract method
     *
     * @return void
     */
    public function testFileContainsExtractMethod(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');
        $this->assertStringContainsString('function extract(', $content);

    }//end testFileContainsExtractMethod()


    /**
     * Test file contains anonymize method
     *
     * @return void
     */
    public function testFileContainsAnonymizeMethod(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');
        $this->assertStringContainsString('function anonymize(', $content);

    }//end testFileContainsAnonymizeMethod()


    /**
     * anonymise-output-as-pdf-by-default: the controller must accept
     * the per-call `outputFormat` field and validate it against the
     * documented allow-list.
     *
     * @return void
     */
    public function testControllerAcceptsAndValidatesOutputFormat(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');

        $this->assertStringContainsString(
            "self::VALID_OUTPUT_FORMATS",
            $content,
            'Controller must expose a VALID_OUTPUT_FORMATS allow-list'
        );
        $this->assertMatchesRegularExpression(
            "/'pdf'\\s*,\\s*'preserve'/",
            $content,
            'VALID_OUTPUT_FORMATS must contain pdf and preserve'
        );
        $this->assertStringContainsString(
            'resolveOutputFormat',
            $content,
            'Controller must implement resolveOutputFormat helper'
        );

    }//end testControllerAcceptsAndValidatesOutputFormat()


    /**
     * anonymise-output-as-pdf-by-default: the controller must map
     * ConversionFailedException to an HTTP 422 response with the
     * structured body documented in design D5.
     *
     * @return void
     */
    public function testControllerSurfacesConversionFailureAsHttp422(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');

        $this->assertStringContainsString(
            'ConversionFailedException',
            $content,
            'Controller must catch ConversionFailedException'
        );
        $this->assertStringContainsString(
            "'conversionAttempts'",
            $content,
            'Controller must surface conversionAttempts on the 422 body'
        );
        $this->assertMatchesRegularExpression(
            '/new JSONResponse\b[\s\S]*?422\s*\);/',
            $content,
            'Controller must return 422 for ConversionFailedException'
        );

    }//end testControllerSurfacesConversionFailureAsHttp422()


    /**
     * anonymise-output-as-pdf-by-default: tenant default is read from
     * the documented IAppConfig key.
     *
     * @return void
     */
    public function testControllerReadsTenantDefaultOutputFormat(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/AnonymizationController.php');

        $this->assertStringContainsString(
            'docudesk.anonymisation.default_output_format',
            $content,
            'Controller must read tenant default via the documented IAppConfig key'
        );

    }//end testControllerReadsTenantDefaultOutputFormat()


    /**
     * extract returns 404 when the file does not belong to the calling user
     * (security finding C3 — file IDOR).
     *
     * @return void
     */
    public function testExtractReturns404WhenFileNotOwnedByUser(): void
    {
        // Build a fresh controller whose root-folder reports no matching files.
        $emptyFolder = $this->createMock(Folder::class);
        $emptyFolder->method('getById')->willReturn([]);
        $emptyRootFolder = $this->createMock(IRootFolder::class);
        $emptyRootFolder->method('getUserFolder')->willReturn($emptyFolder);

        $controller = new AnonymizationController(
            appName: 'docudesk',
            request: $this->mockRequest,
            logger: $this->mockLogger,
            anonymizationService: $this->mockAnonService,
            fileListingService: $this->mockFileService,
            l10n: $this->mockL10n,
            appConfig: $this->createMock(\OCP\IAppConfig::class),
            userSession: $this->mockUserSession,
            rootFolder: $emptyRootFolder
        );

        $response = $controller->extract(fileId: 999);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(404, $response->getStatus());

    }//end testExtractReturns404WhenFileNotOwnedByUser()


    /**
     * extract() degrades gracefully to a clean JSON error when the NER backend
     * is unreachable, instead of letting the Throwable escape as an unhandled
     * 500 (white-screen / stack trace).
     *
     * Regression lock for the anonymisation Throwable graceful-degrade fix: the
     * controller wraps extractAndDetectEntities() in catch (\Throwable) and
     * returns a structured JSONResponse with an 'error' payload. A plain
     * RuntimeException (the shape thrown when the presidio/NER sidecar is
     * absent) carries code 0, so the controller must map it to HTTP 500 — not
     * crash and not mistakenly surface code 0 as the HTTP status.
     *
     * @return void
     */
    public function testExtractDegradesGracefullyWhenNerBackendUnavailable(): void
    {
        // The default controller has file access granted; make the service
        // throw as if the NER backend is unreachable.
        $this->mockAnonService->method('extractAndDetectEntities')
            ->willThrowException(new \RuntimeException('Connection refused: presidio backend unavailable'));

        $response = $this->controller->extract(fileId: 123);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(500, $response->getStatus());

        $data = $response->getData();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Failed to extract and detect entities', $data['error']);

    }//end testExtractDegradesGracefullyWhenNerBackendUnavailable()


    /**
     * files() degrades gracefully when the underlying listing throws an
     * HTTP-coded exception (e.g. OpenRegister unavailable, code 503): the
     * controller must surface that exact status, not a blanket 500, and never
     * let the Throwable escape.
     *
     * @return void
     */
    public function testFilesPropagatesHttpCodedExceptionStatus(): void
    {
        $this->mockFileService->method('listProcessedFiles')
            ->willThrowException(new \RuntimeException('Register backend unavailable', 503));

        $response = $this->controller->files();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(503, $response->getStatus());

        $data = $response->getData();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);

    }//end testFilesPropagatesHttpCodedExceptionStatus()


    /**
     * anonymize returns 404 when the file does not belong to the calling user
     * (security finding C3 — file IDOR).
     *
     * @return void
     */
    public function testAnonymizeReturns404WhenFileNotOwnedByUser(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            ['entities' => [['text' => 'Test', 'type' => 'PERSON']]]
        );

        // Build a fresh controller whose root-folder reports no matching files.
        $emptyFolder = $this->createMock(Folder::class);
        $emptyFolder->method('getById')->willReturn([]);
        $emptyRootFolder = $this->createMock(IRootFolder::class);
        $emptyRootFolder->method('getUserFolder')->willReturn($emptyFolder);

        $controller = new AnonymizationController(
            appName: 'docudesk',
            request: $this->mockRequest,
            logger: $this->mockLogger,
            anonymizationService: $this->mockAnonService,
            fileListingService: $this->mockFileService,
            l10n: $this->mockL10n,
            appConfig: $this->createMock(\OCP\IAppConfig::class),
            userSession: $this->mockUserSession,
            rootFolder: $emptyRootFolder
        );

        $response = $controller->anonymize(fileId: 999);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(404, $response->getStatus());

    }//end testAnonymizeReturns404WhenFileNotOwnedByUser()


    /**
     * Stray non-array `bases` field on a payload entry is silently ignored — succeeds.
     *
     * Per spec.md (post-explore-mode rework 2026-05-12): DocuDesk MUST ignore any
     * `bases` field that erroneously appears on incoming payload entries (do NOT 400).
     * Bases are set per-relation via OR's own PATCH endpoint, not validated here.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testAnonymizeIgnoresStrayNonArrayBases(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities' => [
                    ['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => 'not-an-array'],
                ],
            ]
        );

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'x']);

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());
        $data = $response->getData();
        $this->assertArrayHasKey('ignoredFields', $data);
        $this->assertContains('bases', $data['ignoredFields']);

    }//end testAnonymizeIgnoresStrayNonArrayBases()


    /**
     * Stray non-string `bases` entries on a payload entry are silently ignored — succeeds.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testAnonymizeIgnoresStrayNonStringBasesEntries(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities' => [
                    ['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => [42, 'uuid-b']],
                ],
            ]
        );

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'x']);

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());
        $data = $response->getData();
        $this->assertArrayHasKey('ignoredFields', $data);
        $this->assertContains('bases', $data['ignoredFields']);

    }//end testAnonymizeIgnoresStrayNonStringBasesEntries()


    /**
     * Test anonymize succeeds when bases is a valid array of strings
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testAnonymizeSucceedsWhenBasesIsValidStringArray(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities' => [
                    ['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => ['uuid-a', 'uuid-b']],
                ],
            ]
        );

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'x']);

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());

    }//end testAnonymizeSucceedsWhenBasesIsValidStringArray()


    /**
     * Test anonymize succeeds when bases is an empty array
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testAnonymizeSucceedsWhenBasesIsEmptyArray(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities' => [
                    ['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => []],
                ],
            ]
        );

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'x']);

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());

    }//end testAnonymizeSucceedsWhenBasesIsEmptyArray()


    /**
     * Test anonymize succeeds when entities have no bases field (backward-compat)
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testAnonymizeSucceedsWhenBasesAbsent(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities' => [
                    ['text' => 'Amsterdam', 'type' => 'LOCATION'],
                ],
            ]
        );

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'x']);

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());

    }//end testAnonymizeSucceedsWhenBasesAbsent()


    /**
     * Flag defaults to false: no summary work is triggered.
     *
     * When appendBasisSummary is not in the request, anonymizeDocument is called
     * with appendBasisSummary=false and outputFormat='pdf'.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-7
     */
    public function testAnonymizeFlagDefaultsToFalse(): void
    {
        $entities = [['type' => 'PERSON', 'text' => 'John']];

        $this->mockRequest->method('getParams')->willReturn(['entities' => $entities]);

        $this->mockAnonService->expects($this->once())
            ->method('anonymizeDocument')
            ->with(
                fileId: 42,
                entities: $entities,
                appendBasisSummary: false,
                outputFormat: 'pdf'
            )
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'file-42']);

        $response = $this->controller->anonymize(42);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());

    }//end testAnonymizeFlagDefaultsToFalse()


    /**
     * Flag true with PDF output invokes the append path.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-7
     */
    public function testAnonymizeFlagTrueWithPdfMode(): void
    {
        $entities = [['type' => 'PERSON', 'text' => 'Jane']];

        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'           => $entities,
                'appendBasisSummary' => true,
                'outputFormat'       => 'pdf',
            ]
        );

        $this->mockAnonService->expects($this->once())
            ->method('anonymizeDocument')
            ->with(
                fileId: 10,
                entities: $entities,
                appendBasisSummary: true,
                outputFormat: 'pdf'
            )
            ->willReturn(['replacementCount' => 2, 'anonymizedFileId' => 'file-10']);

        $response = $this->controller->anonymize(10);

        $this->assertSame(200, $response->getStatus());

    }//end testAnonymizeFlagTrueWithPdfMode()


    /**
     * Flag true with preserve mode invokes the separate-PDF path.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-7
     */
    public function testAnonymizeFlagTrueWithPreserveMode(): void
    {
        $entities = [['type' => 'LOCATION', 'text' => 'Amsterdam']];

        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'           => $entities,
                'appendBasisSummary' => true,
                'outputFormat'       => 'preserve',
            ]
        );

        $this->mockAnonService->expects($this->once())
            ->method('anonymizeDocument')
            ->with(
                fileId: 20,
                entities: $entities,
                appendBasisSummary: true,
                outputFormat: 'preserve'
            )
            ->willReturn(
                [
                    'replacementCount' => 1,
                    'anonymizedFileId' => 'file-20',
                    'summaryFileId'    => 'summary-20',
                    'summaryFilePath'  => '/DocuDesk/doc_grondslagen.pdf',
                ]
            );

        $response = $this->controller->anonymize(20);
        $data     = $response->getData();

        $this->assertSame(200, $response->getStatus());
        $this->assertArrayHasKey('summaryFileId', $data);

    }//end testAnonymizeFlagTrueWithPreserveMode()


    /**
     * Summary rendering exception surfaces as warning field with HTTP 200.
     *
     * The service handles the exception internally; the controller receives
     * a result with a 'warning' key and returns HTTP 200.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-7
     */
    public function testAnonymizeRenderingExceptionSurfacesAsWarning(): void
    {
        $entities = [['type' => 'PERSON', 'text' => 'Test']];

        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'           => $entities,
                'appendBasisSummary' => true,
            ]
        );

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(
                [
                    'replacementCount' => 1,
                    'anonymizedFileId' => 'file-30',
                    'warning'          => [
                        'code'    => 'SUMMARY_APPEND_FAILED',
                        'message' => 'Basis summary could not be appended.',
                    ],
                ]
            );

        $response = $this->controller->anonymize(30);
        $data     = $response->getData();

        $this->assertSame(200, $response->getStatus());
        $this->assertArrayHasKey('warning', $data);
        $this->assertSame('SUMMARY_APPEND_FAILED', $data['warning']['code']);

    }//end testAnonymizeRenderingExceptionSurfacesAsWarning()


    /**
     * Payload validation rejects non-boolean appendBasisSummary.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-7
     */
    public function testAnonymizeRejectsNonBooleanFlag(): void
    {
        $entities = [['type' => 'PERSON', 'text' => 'Test']];

        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'           => $entities,
                'appendBasisSummary' => 'yes',
            ]
        );

        $this->mockAnonService->expects($this->never())->method('anonymizeDocument');

        $response = $this->controller->anonymize(40);

        $this->assertSame(400, $response->getStatus());
        $data = $response->getData();
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('appendBasisSummary', $data['error']);

    }//end testAnonymizeRejectsNonBooleanFlag()


}//end class

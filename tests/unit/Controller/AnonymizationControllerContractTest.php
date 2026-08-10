<?php

/**
 * Wire-contract tests for AnonymizationController::upload() and updateRelation()
 *
 * Covers `anonymization#upload` (POST api/anonymization/upload) and
 * `anonymization#updateRelation` (PATCH api/anonymization/relations/{id}).
 *
 * `upload()` is asserted on the multipart contract itself: a missing file and
 * a failed multipart transfer are 400s, a real temporary file is read from
 * disk and handed to the listing service byte-for-byte, and an anonymous
 * caller is refused with 401 before any write.
 *
 * `updateRelation()` is asserted on the prohibition-gate contract: a
 * non-boolean `skipAnonymization` is a 400, an allowed decision is forwarded
 * with its `force` / `bases` arguments and answered 200, a
 * prohibition-blocked skip surfaces the service's 422 body verbatim (so the UI
 * can show `threshold` / `prohibitionMatch`), and an anonymous caller is
 * refused with 401 before the decision is applied.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\AnonymizationController;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\FileListingService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
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
use RuntimeException;

/**
 * Tests for the anonymization upload and relation-decision endpoints.
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
class AnonymizationControllerContractTest extends TestCase
{

    /**
     * Mocked request.
     *
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $request;

    /**
     * Mocked anonymization service.
     *
     * @var AnonymizationService|MockObject
     */
    private AnonymizationService|MockObject $anonService;

    /**
     * Mocked file listing service.
     *
     * @var FileListingService|MockObject
     */
    private FileListingService|MockObject $fileService;

    /**
     * Mocked localisation.
     *
     * @var IL10N|MockObject
     */
    private IL10N|MockObject $l10n;

    /**
     * Controller under test, with an authenticated session.
     *
     * @var AnonymizationController
     */
    private AnonymizationController $controller;

    /**
     * Temporary files created by a test, removed in tearDown().
     *
     * @var string[]
     */
    private array $tempFiles = [];


    /**
     * Set up an authenticated controller.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->request     = $this->createMock(IRequest::class);
        $this->anonService = $this->createMock(AnonymizationService::class);
        $this->fileService = $this->createMock(FileListingService::class);
        $this->l10n        = $this->createMock(IL10N::class);
        $this->l10n->method('t')->willReturnCallback(
            static function (string $text, array $params=[]): string {
                if ($params === []) {
                    return $text;
                }

                return vsprintf($text, $params);
            }
        );

        $this->controller = $this->buildController($this->authenticatedSession());

    }//end setUp()


    /**
     * Remove any temporary upload files.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path) === true) {
                unlink($path);
            }
        }

        $this->tempFiles = [];

        parent::tearDown();

    }//end tearDown()


    /**
     * Build the controller for a given session.
     *
     * @param IUserSession $session The session the controller should see.
     *
     * @return AnonymizationController The controller under test.
     */
    private function buildController(IUserSession $session): AnonymizationController
    {
        $folder = $this->createMock(Folder::class);
        $folder->method('getById')->willReturn([]);
        $rootFolder = $this->createMock(IRootFolder::class);
        $rootFolder->method('getUserFolder')->willReturn($folder);

        return new AnonymizationController(
            appName: 'docudesk',
            request: $this->request,
            logger: $this->createMock(LoggerInterface::class),
            anonymizationService: $this->anonService,
            fileListingService: $this->fileService,
            l10n: $this->l10n,
            appConfig: $this->createMock(IAppConfig::class),
            userSession: $session,
            rootFolder: $rootFolder
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
     * Create a real temporary file standing in for PHP's multipart tmp file.
     *
     * @param string $contents The bytes the "uploaded" file should hold.
     *
     * @return string The temporary file path.
     */
    private function makeUploadTempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dd-upload-');
        $this->assertIsString($path);
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;

    }//end makeUploadTempFile()


    /**
     * A well-formed multipart upload is read off disk and handed to the
     * listing service with its original filename and exact bytes; the
     * service's result is returned as-is with HTTP 200.
     *
     * @return void
     */
    public function testUploadStoresFileAndReturnsServiceResult(): void
    {
        $contents = "%PDF-1.4 fake document bytes\n";
        $tmpPath  = $this->makeUploadTempFile($contents);

        $this->request->method('getUploadedFile')->with('file')->willReturn(
            [
                'name'     => 'contract.pdf',
                'type'     => 'application/pdf',
                'tmp_name' => $tmpPath,
                'error'    => UPLOAD_ERR_OK,
                'size'     => strlen($contents),
            ]
        );

        $this->fileService->expects($this->once())
            ->method('uploadFile')
            ->with('contract.pdf', $contents)
            ->willReturn(['fileId' => 42, 'name' => 'contract.pdf']);

        $response = $this->controller->upload();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame(['fileId' => 42, 'name' => 'contract.pdf'], $response->getData());

    }//end testUploadStoresFileAndReturnsServiceResult()


    /**
     * A request without a `file` part answers 400 and stores nothing.
     *
     * @return void
     */
    public function testUploadRejectsMissingFile(): void
    {
        $this->request->method('getUploadedFile')->willReturn(null);
        $this->fileService->expects($this->never())->method('uploadFile');

        $response = $this->controller->upload();

        $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertSame(['error' => 'No file uploaded'], $response->getData());

    }//end testUploadRejectsMissingFile()


    /**
     * A multipart transfer that PHP flagged as failed answers 400 with the
     * error code echoed back, and nothing is written.
     *
     * @return void
     */
    public function testUploadRejectsFailedMultipartTransfer(): void
    {
        $this->request->method('getUploadedFile')->willReturn(
            [
                'name'     => 'huge.pdf',
                'type'     => 'application/pdf',
                'tmp_name' => '',
                'error'    => UPLOAD_ERR_INI_SIZE,
                'size'     => 0,
            ]
        );
        $this->fileService->expects($this->never())->method('uploadFile');

        $response = $this->controller->upload();

        $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString(
            (string) UPLOAD_ERR_INI_SIZE,
            $response->getData()['error']
        );

    }//end testUploadRejectsFailedMultipartTransfer()


    /**
     * A storage failure surfaces the exception's own HTTP code (e.g. 507 quota
     * exceeded) instead of a blanket 500.
     *
     * @return void
     */
    public function testUploadSurfacesCodedStorageFailure(): void
    {
        $tmpPath = $this->makeUploadTempFile('bytes');

        $this->request->method('getUploadedFile')->willReturn(
            [
                'name'     => 'contract.pdf',
                'type'     => 'application/pdf',
                'tmp_name' => $tmpPath,
                'error'    => UPLOAD_ERR_OK,
                'size'     => 5,
            ]
        );
        $this->fileService->method('uploadFile')
            ->willThrowException(new RuntimeException('Quota exceeded', 507));

        $response = $this->controller->upload();

        $this->assertSame(507, $response->getStatus());
        $this->assertArrayHasKey('error', $response->getData());

    }//end testUploadSurfacesCodedStorageFailure()


    /**
     * An anonymous caller cannot upload into someone else's DocuDesk folder.
     *
     * @return void
     */
    public function testUploadRejectsAnonymousCaller(): void
    {
        $this->fileService->expects($this->never())->method('uploadFile');

        $response = $this->buildController($this->anonymousSession())->upload();

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
        $this->assertSame(['error' => 'Not authenticated'], $response->getData());

    }//end testUploadRejectsAnonymousCaller()


    /**
     * An allowed include/skip decision is forwarded with its parsed arguments
     * and answered with the service's own status and body.
     *
     * @return void
     */
    public function testUpdateRelationForwardsDecision(): void
    {
        $this->request->method('getParams')->willReturn(
            [
                'skipAnonymization' => true,
                'bases'             => ['basis-a', 'basis-b'],
                'force'             => 'true',
            ]
        );

        $this->anonService->expects($this->once())
            ->method('applyRelationSkipDecision')
            ->with(
                relationId: 77,
                skip: true,
                bases: ['basis-a', 'basis-b'],
                force: true
            )
            ->willReturn(
                [
                    'status' => 200,
                    'body'   => ['id' => 77, 'skipAnonymization' => true],
                ]
            );

        $response = $this->controller->updateRelation(77);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame(['id' => 77, 'skipAnonymization' => true], $response->getData());

    }//end testUpdateRelationForwardsDecision()


    /**
     * An omitted `skipAnonymization` defaults to an include decision
     * (`skip: false`) with no bases and no force.
     *
     * @return void
     */
    public function testUpdateRelationDefaultsToIncludeDecision(): void
    {
        $this->request->method('getParams')->willReturn([]);

        $this->anonService->expects($this->once())
            ->method('applyRelationSkipDecision')
            ->with(
                relationId: 5,
                skip: false,
                bases: null,
                force: false
            )
            ->willReturn(
                [
                    'status' => 200,
                    'body'   => ['id' => 5, 'skipAnonymization' => false],
                ]
            );

        $response = $this->controller->updateRelation(5);

        $this->assertSame(Http::STATUS_OK, $response->getStatus());

    }//end testUpdateRelationDefaultsToIncludeDecision()


    /**
     * A non-boolean `skipAnonymization` is a client error: 400, and the
     * decision never reaches the prohibition policy.
     *
     * @return void
     */
    public function testUpdateRelationRejectsNonBooleanSkipFlag(): void
    {
        $this->request->method('getParams')->willReturn(['skipAnonymization' => 'yes']);

        $this->anonService->expects($this->never())->method('applyRelationSkipDecision');

        $response = $this->controller->updateRelation(77);

        $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString('skipAnonymization', $response->getData()['error']);

    }//end testUpdateRelationRejectsNonBooleanSkipFlag()


    /**
     * A prohibition-blocked skip surfaces the policy's 422 body verbatim, so
     * the review UI can render `threshold` and `prohibitionMatch`.
     *
     * @return void
     */
    public function testUpdateRelationSurfacesProhibitionBlock(): void
    {
        $this->request->method('getParams')->willReturn(['skipAnonymization' => true]);

        $blocked = [
            'error'            => 'Entity is covered by a publication prohibition',
            'threshold'        => 0.8,
            'prohibitionMatch' => ['id' => 'proh-1', 'confidence' => 0.93],
        ];

        $this->anonService->method('applyRelationSkipDecision')->willReturn(
            [
                'status' => 422,
                'body'   => $blocked,
            ]
        );

        $response = $this->controller->updateRelation(77);

        $this->assertSame(422, $response->getStatus());
        $this->assertSame($blocked, $response->getData());

    }//end testUpdateRelationSurfacesProhibitionBlock()


    /**
     * A backend failure is contained as a 500 error envelope — the Throwable
     * must not escape, and the caller must not read it as a stored decision.
     *
     * @return void
     */
    public function testUpdateRelationContainsBackendFailure(): void
    {
        $this->request->method('getParams')->willReturn(['skipAnonymization' => false]);

        $this->anonService->method('applyRelationSkipDecision')
            ->willThrowException(new RuntimeException('OpenRegister unreachable'));

        $response = $this->controller->updateRelation(77);

        $this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
        $this->assertSame(
            ['error' => 'Failed to update entity relation decision'],
            $response->getData()
        );

    }//end testUpdateRelationContainsBackendFailure()


    /**
     * An anonymous caller cannot record a relation decision.
     *
     * @return void
     */
    public function testUpdateRelationRejectsAnonymousCaller(): void
    {
        $this->anonService->expects($this->never())->method('applyRelationSkipDecision');

        $response = $this->buildController($this->anonymousSession())->updateRelation(77);

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
        $this->assertSame(['error' => 'Not authenticated'], $response->getData());

    }//end testUpdateRelationRejectsAnonymousCaller()


}//end class

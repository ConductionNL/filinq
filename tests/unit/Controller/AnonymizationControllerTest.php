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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\AnonymizationController;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\FileListingService;
use OCP\AppFramework\Http\JSONResponse;
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
        $this->mockAnonService = $this->createMock(AnonymizationService::class);
        $this->mockL10n        = $this->createMock(IL10N::class);
        $this->mockL10n->method('t')->willReturnCallback(fn($s) => $s);

        $mockUser                = $this->createMock(IUser::class);
        $this->mockUserSession   = $this->createMock(IUserSession::class);
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $this->controller = new AnonymizationController(
            appName: 'docudesk',
            request: $this->mockRequest,
            logger: $this->createMock(LoggerInterface::class),
            anonymizationService: $this->mockAnonService,
            fileListingService: $this->createMock(FileListingService::class),
            l10n: $this->mockL10n,
            userSession: $this->mockUserSession
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
     * Test anonymize returns 400 when bases is not an array
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testAnonymizeReturns400WhenBasesIsNotArray(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities' => [
                    ['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => 'not-an-array'],
                ],
            ]
        );

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(400, $response->getStatus());

    }//end testAnonymizeReturns400WhenBasesIsNotArray()


    /**
     * Test anonymize returns 400 when bases contains a non-string entry
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testAnonymizeReturns400WhenBasesContainsNonString(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities' => [
                    ['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => [42, 'uuid-b']],
                ],
            ]
        );

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(400, $response->getStatus());

    }//end testAnonymizeReturns400WhenBasesContainsNonString()


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


}//end class

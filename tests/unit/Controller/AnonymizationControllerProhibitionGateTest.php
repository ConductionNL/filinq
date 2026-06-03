<?php

/**
 * Unit tests for AnonymizationController — prohibition gate
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\AnonymizationController;
use OCA\DocuDesk\Exception\ProhibitionGateException;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\FileListingService;
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
 * Unit tests for the prohibition gate path in AnonymizationController.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
 */
class AnonymizationControllerProhibitionGateTest extends TestCase
{

    /**
     * Mocked IRequest.
     *
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * Mocked LoggerInterface.
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Mocked AnonymizationService.
     *
     * @var AnonymizationService|MockObject
     */
    private AnonymizationService|MockObject $mockAnonService;

    /**
     * Mocked IL10N.
     *
     * @var IL10N|MockObject
     */
    private IL10N|MockObject $mockL10n;

    /**
     * Mocked IUserSession.
     *
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * Mocked IRootFolder.
     *
     * @var IRootFolder|MockObject
     */
    private IRootFolder|MockObject $mockRootFolder;

    /**
     * Controller under test.
     *
     * @var AnonymizationController
     */
    private AnonymizationController $controller;

    /**
     * Set up test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest     = $this->createMock(IRequest::class);
        $this->mockLogger      = $this->createMock(LoggerInterface::class);
        $this->mockAnonService = $this->createMock(AnonymizationService::class);
        $this->mockL10n        = $this->createMock(IL10N::class);
        $this->mockL10n->method('t')->willReturnCallback(static fn (string $s): string => $s);

        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('test-user');
        $this->mockUserSession = $this->createMock(IUserSession::class);
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $mockFile   = $this->createMock(File::class);
        $mockFolder = $this->createMock(Folder::class);
        $mockFolder->method('getById')->willReturn([$mockFile]);
        $this->mockRootFolder = $this->createMock(IRootFolder::class);
        $this->mockRootFolder->method('getUserFolder')->willReturn($mockFolder);

        $this->controller = new AnonymizationController(
            appName: 'docudesk',
            request: $this->mockRequest,
            logger: $this->mockLogger,
            anonymizationService: $this->mockAnonService,
            fileListingService: $this->createMock(FileListingService::class),
            l10n: $this->mockL10n,
            appConfig: $this->createMock(IAppConfig::class),
            userSession: $this->mockUserSession,
            rootFolder: $this->mockRootFolder
        );

    }//end setUp()

    /**
     * 422 response shape when gate fires with missing prohibition matches.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
     */
    public function testAnonymizeReturns422WithStructuredBodyWhenGateFires(): void
    {
        $missing = [
            [
                'entityId'   => 42,
                'entityName' => 'Pieter Jansen',
                'ruleId'     => 'R-PROHIBIT-1',
                'ruleName'   => 'Politiemedewerker undercover (Jansen)',
                'confidence' => 0.91,
            ],
        ];

        $this->mockRequest->method('getParams')->willReturn(
            ['entities' => [['value' => 'Other', 'type' => 'PERSON']]]
        );

        $this->mockAnonService->method('anonymizeDocument')
            ->willThrowException(
                    new ProhibitionGateException(
                missingProhibitionMatches: $missing,
                rejectedOverrides: []
            )
                    );

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(422, $response->getStatus());

        $data = $response->getData();
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('missingProhibitionMatches', $data);
        $this->assertArrayHasKey('rejectedOverrides', $data);
        $this->assertCount(1, $data['missingProhibitionMatches']);
        $this->assertSame(42, $data['missingProhibitionMatches'][0]['entityId']);
        $this->assertSame('Pieter Jansen', $data['missingProhibitionMatches'][0]['entityName']);

    }//end testAnonymizeReturns422WithStructuredBodyWhenGateFires()

    /**
     * acknowledgedOverrides is accepted on the first request (no special retry flag needed).
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
     */
    public function testAnonymizeAcceptsAcknowledgedOverridesOnFirstRequest(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'              => [['value' => 'Some Entity', 'type' => 'PERSON']],
                'acknowledgedOverrides' => [
                    ['ruleId' => 'R-X', 'entityId' => 7, 'reason' => 'false positive'],
                ],
            ]
        );

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'out']);

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());

    }//end testAnonymizeAcceptsAcknowledgedOverridesOnFirstRequest()

    /**
     * Rejected overrides are surfaced in the 422 body alongside missingProhibitionMatches.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
     */
    public function testAnonymizeReturns422WithRejectedOverridesBody(): void
    {
        $rejected = [
            [
                'ruleId'   => 'R-X',
                'entityId' => 7,
                'reason'   => 'override not allowed for high-confidence matches',
            ],
        ];

        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'              => [['value' => 'Some Entity', 'type' => 'PERSON']],
                'acknowledgedOverrides' => [['ruleId' => 'R-X', 'entityId' => 7]],
            ]
        );

        $this->mockAnonService->method('anonymizeDocument')
            ->willThrowException(
                    new ProhibitionGateException(
                missingProhibitionMatches: [],
                rejectedOverrides: $rejected
            )
                    );

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(422, $response->getStatus());

        $data = $response->getData();
        $this->assertArrayHasKey('rejectedOverrides', $data);
        $this->assertCount(1, $data['rejectedOverrides']);
        $this->assertSame('R-X', $data['rejectedOverrides'][0]['ruleId']);

    }//end testAnonymizeReturns422WithRejectedOverridesBody()

    /**
     * acknowledgedOverrides validation: missing ruleId → 400.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
     */
    public function testAnonymizeReturns400WhenOverrideMissingRuleId(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'              => [['value' => 'Some Entity', 'type' => 'PERSON']],
                'acknowledgedOverrides' => [
                    ['entityId' => 7],
                ],
            ]
        );

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(400, $response->getStatus());

        $data = $response->getData();
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('ruleId', $data['error']);

    }//end testAnonymizeReturns400WhenOverrideMissingRuleId()

    /**
     * acknowledgedOverrides validation: non-integer entityId → 400.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
     */
    public function testAnonymizeReturns400WhenOverrideEntityIdIsNotInteger(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'              => [['value' => 'Some Entity', 'type' => 'PERSON']],
                'acknowledgedOverrides' => [
                    ['ruleId' => 'R-X', 'entityId' => 'not-an-int'],
                ],
            ]
        );

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(400, $response->getStatus());

    }//end testAnonymizeReturns400WhenOverrideEntityIdIsNotInteger()

    /**
     * Empty acknowledgedOverrides is treated the same as absent — no error.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
     */
    public function testAnonymizeWithEmptyAcknowledgedOverridesSucceeds(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'              => [['value' => 'Some Entity', 'type' => 'PERSON']],
                'acknowledgedOverrides' => [],
            ]
        );

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'out']);

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());

    }//end testAnonymizeWithEmptyAcknowledgedOverridesSucceeds()

    /**
     * Non-array acknowledgedOverrides → 400.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
     */
    public function testAnonymizeReturns400WhenAcknowledgedOverridesIsNotArray(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'              => [['value' => 'Some Entity', 'type' => 'PERSON']],
                'acknowledgedOverrides' => 'not-an-array',
            ]
        );

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(400, $response->getStatus());

    }//end testAnonymizeReturns400WhenAcknowledgedOverridesIsNotArray()
}//end class

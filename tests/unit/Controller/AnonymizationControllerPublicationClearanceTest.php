<?php

/**
 * Unit tests for AnonymizationController — publication-clearance-anonymise-payload change
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-6
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
 * Tests for the unredactedEntities[] extension on the anonymize endpoint
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-6
 */
class AnonymizationControllerPublicationClearanceTest extends TestCase
{

    /**
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var AnonymizationService|MockObject
     */
    private AnonymizationService|MockObject $mockAnonService;

    /**
     * @var FileListingService|MockObject
     */
    private FileListingService|MockObject $mockFileService;

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
     * @var AnonymizationController
     */
    private AnonymizationController $controller;


    /**
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
            fileListingService: $this->mockFileService,
            l10n: $this->mockL10n,
            userSession: $this->mockUserSession,
            rootFolder: $this->mockRootFolder
        );

    }//end setUp()


    /**
     * Backward-compat: omitting unredactedEntities produces no createdConsents field.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-6
     */
    public function testAnonymizeWithoutUnredactedEntitiesProducesNoCreatedConsents(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            ['entities' => [['text' => 'Jan', 'type' => 'PERSON']]]
        );

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'f-1']);

        $response = $this->controller->anonymize(fileId: 1);
        $data     = $response->getData();

        $this->assertSame(200, $response->getStatus());
        $this->assertArrayNotHasKey('createdConsents', $data);

    }//end testAnonymizeWithoutUnredactedEntitiesProducesNoCreatedConsents()


    /**
     * unredactedEntities that is not an array returns 400.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
     */
    public function testAnonymizeReturns400WhenUnredactedEntitiesIsNotArray(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'           => [['text' => 'Jan', 'type' => 'PERSON']],
                'unredactedEntities' => 'not-an-array',
            ]
        );

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertSame(400, $response->getStatus());

    }//end testAnonymizeReturns400WhenUnredactedEntitiesIsNotArray()


    /**
     * Missing required entityId returns 400.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
     */
    public function testAnonymizeReturns400WhenEntityIdMissing(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'           => [['text' => 'Jan', 'type' => 'PERSON']],
                'unredactedEntities' => [
                    ['entityText' => 'Piet', 'entityType' => 'PERSON', 'publicationBases' => ['basis1']],
                ],
            ]
        );

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertSame(400, $response->getStatus());
        $data = $response->getData();
        $this->assertArrayHasKey('error', $data);

    }//end testAnonymizeReturns400WhenEntityIdMissing()


    /**
     * Missing required entityText returns 400.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
     */
    public function testAnonymizeReturns400WhenEntityTextMissing(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'           => [['text' => 'Jan', 'type' => 'PERSON']],
                'unredactedEntities' => [
                    ['entityId' => 1, 'entityType' => 'PERSON', 'publicationBases' => ['basis1']],
                ],
            ]
        );

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertSame(400, $response->getStatus());

    }//end testAnonymizeReturns400WhenEntityTextMissing()


    /**
     * Empty publicationBases array returns 400.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
     */
    public function testAnonymizeReturns400WhenPublicationBasesEmpty(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'           => [['text' => 'Jan', 'type' => 'PERSON']],
                'unredactedEntities' => [
                    [
                        'entityId'         => 1,
                        'entityText'       => 'Piet',
                        'entityType'       => 'PERSON',
                        'publicationBases' => [],
                    ],
                ],
            ]
        );

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertSame(400, $response->getStatus());

    }//end testAnonymizeReturns400WhenPublicationBasesEmpty()


    /**
     * Prohibition match on an unredacted entity returns 422 with structured body.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
     */
    public function testAnonymizeReturns422WhenProhibitionMatches(): void
    {
        $unredacted = [
            [
                'entityId'         => 5,
                'entityText'       => 'Jan Jansen',
                'entityType'       => 'PERSON',
                'publicationBases' => ['basis-a'],
            ],
        ];

        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'           => [['text' => 'Other', 'type' => 'LOCATION']],
                'unredactedEntities' => $unredacted,
            ]
        );

        $this->mockAnonService->method('checkUnredactedProhibitions')
            ->willReturn(
                [
                    [
                        'entityId'   => 5,
                        'entityText' => 'Jan Jansen',
                        'ruleId'     => 'rule-1',
                        'ruleName'   => 'Prohibition Rule A',
                    ],
                ]
            );

        $this->mockAnonService->expects($this->never())->method('anonymizeDocument');

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertSame(422, $response->getStatus());
        $data = $response->getData();
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('prohibitedEntries', $data);
        $this->assertCount(1, $data['prohibitedEntries']);

    }//end testAnonymizeReturns422WhenProhibitionMatches()


    /**
     * Success path: valid unredactedEntities with no prohibition match returns 200
     * and the createdConsents[] field is populated.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
     */
    public function testAnonymizeReturns200WithCreatedConsentsWhenUnredactedEntitiesValid(): void
    {
        $unredacted = [
            [
                'entityId'         => 7,
                'entityText'       => 'Maria de Vries',
                'entityType'       => 'PERSON',
                'publicationBases' => ['basis-woo'],
            ],
        ];

        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'           => [['text' => 'Other', 'type' => 'LOCATION']],
                'unredactedEntities' => $unredacted,
            ]
        );

        $this->mockAnonService->method('checkUnredactedProhibitions')->willReturn([]);

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(
                [
                    'replacementCount' => 1,
                    'anonymizedFileId' => 'anon-42',
                    'createdConsents'  => [
                        [
                            'entityId'      => 7,
                            'entityText'    => 'Maria de Vries',
                            'consentId'     => 'consent-uuid-1',
                            'consentStatus' => 'pending',
                            'action'        => 'created',
                        ],
                    ],
                ]
            );

        $response = $this->controller->anonymize(fileId: 1);
        $data     = $response->getData();

        $this->assertSame(200, $response->getStatus());
        $this->assertArrayHasKey('createdConsents', $data);
        $this->assertCount(1, $data['createdConsents']);
        $this->assertSame('created', $data['createdConsents'][0]['action']);

    }//end testAnonymizeReturns200WithCreatedConsentsWhenUnredactedEntitiesValid()


    /**
     * Backward-compat: request without unredactedEntities passes unredactedEntities=[] to service.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-6
     */
    public function testAnonymizePassesEmptyUnredactedEntitiesToServiceWhenAbsent(): void
    {
        $entities = [['text' => 'Jan', 'type' => 'PERSON']];

        $this->mockRequest->method('getParams')->willReturn(
            ['entities' => $entities]
        );

        $this->mockAnonService->expects($this->once())
            ->method('anonymizeDocument')
            ->with(
                fileId: 10,
                entities: $entities,
                appendBasisSummary: false,
                outputFormat: 'pdf',
                unredactedEntities: []
            )
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'f-10']);

        $response = $this->controller->anonymize(fileId: 10);

        $this->assertSame(200, $response->getStatus());

    }//end testAnonymizePassesEmptyUnredactedEntitiesToServiceWhenAbsent()


    /**
     * Optional contactEmail and contactAddress fields are accepted without error.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
     */
    public function testAnonymizeAcceptsOptionalContactFields(): void
    {
        $unredacted = [
            [
                'entityId'         => 3,
                'entityText'       => 'Klaas Klever',
                'entityType'       => 'PERSON',
                'publicationBases' => ['woo-art-1'],
                'contactEmail'     => 'k.klever@example.nl',
                'contactAddress'   => 'Dorpsstraat 12',
            ],
        ];

        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities'           => [['text' => 'Other', 'type' => 'LOCATION']],
                'unredactedEntities' => $unredacted,
            ]
        );

        $this->mockAnonService->method('checkUnredactedProhibitions')->willReturn([]);
        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 0, 'anonymizedFileId' => 'f-3', 'createdConsents' => []]);

        $response = $this->controller->anonymize(fileId: 1);

        $this->assertSame(200, $response->getStatus());

    }//end testAnonymizeAcceptsOptionalContactFields(): void

    // phpcs:ignore Squiz.WhiteSpace.FunctionClosingBraceSpace
}//end class

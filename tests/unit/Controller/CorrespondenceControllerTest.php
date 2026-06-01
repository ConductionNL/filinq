<?php

/**
 * Unit tests for CorrespondenceController
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
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use Exception;
use OCA\DocuDesk\Controller\CorrespondenceController;
use OCA\DocuDesk\Service\CorrespondenceService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for CorrespondenceController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CorrespondenceControllerTest extends TestCase
{

    /**
     * @var CorrespondenceController
     */
    private CorrespondenceController $controller;

    /**
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * @var CorrespondenceService|MockObject
     */
    private CorrespondenceService|MockObject $mockCorrSvc;

    /**
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var IL10N|MockObject
     */
    private IL10N|MockObject $mockL10n;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest     = $this->createMock(IRequest::class);
        $this->mockCorrSvc     = $this->createMock(CorrespondenceService::class);
        $this->mockUserSession = $this->createMock(IUserSession::class);
        $this->mockLogger      = $this->createMock(LoggerInterface::class);
        $this->mockL10n        = $this->createMock(IL10N::class);

        $this->mockL10n->method('t')->willReturnCallback(fn($t) => $t);

        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('testuser');
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $this->controller = new CorrespondenceController(
            appName: 'docudesk',
            request: $this->mockRequest,
            corrSvc: $this->mockCorrSvc,
            userSession: $this->mockUserSession,
            logger: $this->mockLogger,
            l10n: $this->mockL10n,
        );

    }//end setUp()

    /**
     * Test generate returns 401 when not authenticated.
     *
     * @return void
     */
    public function testGenerateReturns401WhenNotAuthenticated(): void
    {
        $mockSession = $this->createMock(IUserSession::class);
        $mockSession->method('getUser')->willReturn(null);

        $controller = new CorrespondenceController(
            appName: 'docudesk',
            request: $this->mockRequest,
            corrSvc: $this->mockCorrSvc,
            userSession: $mockSession,
            logger: $this->mockLogger,
            l10n: $this->mockL10n,
        );

        $result = $controller->generate();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertSame(401, $result->getStatus());

    }//end testGenerateReturns401WhenNotAuthenticated()

    /**
     * Test generate returns 400 when templateId missing.
     *
     * @return void
     */
    public function testGenerateReturns400WhenTemplateIdMissing(): void
    {
        $this->mockRequest->method('getParam')->willReturn(null);

        $result = $this->controller->generate();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertSame(400, $result->getStatus());

    }//end testGenerateReturns400WhenTemplateIdMissing()

    /**
     * Test generate returns 400 when dataRefs missing.
     *
     * @return void
     */
    public function testGenerateReturns400WhenDataRefsMissing(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                    [
                        ['templateId', null, 'tmpl-uuid'],
                        ['dataRefs', [], null],
                        ['options', [], []],
                        ['filename', 'correspondence.pdf', 'out.pdf'],
                    ]
                    );

        $result = $this->controller->generate();

        $this->assertSame(400, $result->getStatus());

    }//end testGenerateReturns400WhenDataRefsMissing()

    /**
     * Test jobStatus returns 404 for unknown job.
     *
     * @return void
     */
    public function testJobStatusReturns404ForUnknownJob(): void
    {
        $this->mockCorrSvc->method('getJobStatus')->willReturn(null);

        $result = $this->controller->jobStatus('unknown-job-id');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertSame(404, $result->getStatus());

    }//end testJobStatusReturns404ForUnknownJob()
}//end class

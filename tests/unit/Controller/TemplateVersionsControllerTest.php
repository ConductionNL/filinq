<?php

/**
 * Wire-contract tests for TemplateVersionsController::diffVersions()
 *
 * Covers `GET api/templates/{id}/versions/diff`
 * (`templateVersions#diffVersions`): the documented `{from, to}` success body,
 * the 401 anonymous rejection, the 400 raised when either version UUID is
 * missing, and the pass-through of the caller's `from` / `to` query params to
 * the version service.
 *
 * The real TemplateRequestHandler is used (it needs only a logger) so the
 * exception-to-status mapping under test is the shipped one, not a stub.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/template-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use Exception;
use OCA\DocuDesk\Controller\TemplateRequestHandler;
use OCA\DocuDesk\Controller\TemplateVersionsController;
use OCA\DocuDesk\Service\TemplateService;
use OCA\DocuDesk\Service\TemplateVersionService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for the template version-diff endpoint.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TemplateVersionsControllerTest extends TestCase
{

    /**
     * Mocked request.
     *
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $request;

    /**
     * Mocked version service.
     *
     * @var TemplateVersionService|MockObject
     */
    private TemplateVersionService|MockObject $versionService;


    /**
     * Set up the shared mocks.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->request        = $this->createMock(IRequest::class);
        $this->versionService = $this->createMock(TemplateVersionService::class);

    }//end setUp()


    /**
     * Build the controller for a given session.
     *
     * @param IUserSession $session The session the controller should see.
     *
     * @return TemplateVersionsController The controller under test.
     */
    private function buildController(IUserSession $session): TemplateVersionsController
    {
        return new TemplateVersionsController(
            'docudesk',
            $this->request,
            $this->createMock(TemplateService::class),
            new TemplateRequestHandler($this->createMock(LoggerInterface::class)),
            $this->versionService,
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
     * A diff of two known versions answers 200 with both version objects under
     * `from` and `to`, resolved purely from the query params.
     *
     * @return void
     */
    public function testDiffVersionsReturnsBothVersions(): void
    {
        $this->request->method('getParam')->willReturnMap(
            [
                ['from', '', 'ver-1'],
                ['to', '', 'ver-2'],
            ]
        );

        $diff = [
            'from' => ['id' => 'ver-1', 'content' => 'old'],
            'to'   => ['id' => 'ver-2', 'content' => 'new'],
        ];

        $this->versionService->expects($this->once())
            ->method('getDiff')
            ->with('ver-1', 'ver-2')
            ->willReturn($diff);

        $response = $this->buildController($this->authenticatedSession())->diffVersions();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame($diff, $response->getData());

    }//end testDiffVersionsReturnsBothVersions()


    /**
     * An anonymous caller is refused with 401 and no version is read.
     *
     * @return void
     */
    public function testDiffVersionsRejectsAnonymousCaller(): void
    {
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn(null);

        $this->versionService->expects($this->never())->method('getDiff');

        $response = $this->buildController($session)->diffVersions();

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
        $this->assertSame(['error' => 'Not authenticated'], $response->getData());

    }//end testDiffVersionsRejectsAnonymousCaller()


    /**
     * A missing `to` param is a client error: 400 with an explanatory message,
     * and the service is never called with a half-empty pair.
     *
     * @return void
     */
    public function testDiffVersionsRequiresBothVersionIds(): void
    {
        $this->request->method('getParam')->willReturnMap(
            [
                ['from', '', 'ver-1'],
                ['to', '', ''],
            ]
        );

        $this->versionService->expects($this->never())->method('getDiff');

        $response = $this->buildController($this->authenticatedSession())->diffVersions();

        $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString('required', $response->getData()['error']);

    }//end testDiffVersionsRequiresBothVersionIds()


    /**
     * A version UUID that does not resolve surfaces the service's own coded
     * exception as that status, not a blanket 500.
     *
     * @return void
     */
    public function testDiffVersionsSurfacesServiceStatusCode(): void
    {
        $this->request->method('getParam')->willReturnMap(
            [
                ['from', '', 'ver-1'],
                ['to', '', 'ver-missing'],
            ]
        );

        $this->versionService->method('getDiff')
            ->willThrowException(new Exception('Version not found', 404));

        $response = $this->buildController($this->authenticatedSession())->diffVersions();

        $this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertSame(['error' => 'Version not found'], $response->getData());

    }//end testDiffVersionsSurfacesServiceStatusCode()


}//end class

<?php

/**
 * Unit tests for SigningController — finding #288 regression coverage.
 *
 * Asserts that `listRequests()` no longer swallows arbitrary
 * `\Throwable` (and in particular `\Exception`) as an empty-list
 * `notConfigured: true` success — only the narrow `\Error` /
 * missing-method case is treated as deployment drift, real
 * infrastructure failures propagate to the framework's 500 handler
 * so monitoring sees them.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\SigningController;
use OCA\DocuDesk\Exception\RegisterNotConfiguredException;
use OCA\DocuDesk\Service\SigningAuditService;
use OCA\DocuDesk\Service\SigningService;
use OCA\DocuDesk\Service\SigningVerificationService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for SigningController::listRequests() error handling
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningControllerTest extends TestCase
{

    /**
     * @var SigningController
     */
    private SigningController $controller;

    /**
     * @var SigningService|MockObject
     */
    private SigningService|MockObject $signingService;

    /**
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $userSession;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $logger;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $request             = $this->createMock(IRequest::class);
        $this->signingService = $this->createMock(SigningService::class);
        $auditService        = $this->createMock(SigningAuditService::class);
        $verificationService = $this->createMock(SigningVerificationService::class);
        $this->userSession   = $this->createMock(IUserSession::class);
        $this->logger        = $this->createMock(LoggerInterface::class);
        $l10n                = $this->createMock(IL10N::class);
        $l10n->method('t')->willReturnCallback(
            function ($text, $params = []) {
                return is_array($params) === true ? vsprintf($text, $params) : $text;
            }
        );

        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('alice');
        $this->userSession->method('getUser')->willReturn($user);

        $this->controller = new SigningController(
            'docudesk',
            $request,
            $this->signingService,
            $auditService,
            $verificationService,
            $this->userSession,
            $this->logger,
            $l10n
        );

    }//end setUp()


    /**
     * `RegisterNotConfiguredException` continues to return an empty list
     * with `notConfigured: true` — that's the genuine "not configured yet"
     * UI state we explicitly keep calm.
     *
     * @return void
     */
    public function testListRequestsReturnsEmptyOnRegisterNotConfigured(): void
    {
        $this->signingService->method('listRequests')
            ->willThrowException(new RegisterNotConfiguredException('signing register/schema not configured'));

        $response = $this->controller->listRequests();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertSame([], $data['results']);
        $this->assertTrue($data['notConfigured']);

    }//end testListRequestsReturnsEmptyOnRegisterNotConfigured()


    /**
     * A real `\Exception` from a runtime failure (e.g. a DB outage rethrown
     * as `RuntimeException`) MUST propagate — finding #288 was that the
     * previous broad `catch (\Throwable)` masked these as "no requests".
     *
     * @return void
     */
    public function testListRequestsLetsRuntimeExceptionPropagate(): void
    {
        $this->signingService->method('listRequests')
            ->willThrowException(new RuntimeException('OpenRegister DB outage'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenRegister DB outage');

        $this->controller->listRequests();

    }//end testListRequestsLetsRuntimeExceptionPropagate()


    /**
     * The narrow OR-sidecar-lag fallback still triggers for `\Error`
     * (e.g. calling a method that doesn't exist on the deployed OR build).
     * Logged at ERROR level (not warning), still returns an empty list so
     * the page renders while the sidecar catches up.
     *
     * @return void
     */
    public function testListRequestsCatchesErrorAsNotConfigured(): void
    {
        $this->signingService->method('listRequests')
            ->willThrowException(new \Error('Call to undefined method ObjectService::getObjects()'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Likely a missing OpenRegister method'),
                $this->callback(
                    function ($context): bool {
                        return is_array($context) === true && isset($context['exception']);
                    }
                )
            );

        $response = $this->controller->listRequests();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertSame([], $data['results']);
        $this->assertTrue($data['notConfigured']);

    }//end testListRequestsCatchesErrorAsNotConfigured()


    /**
     * Successful list passes through unchanged.
     *
     * @return void
     */
    public function testListRequestsReturnsServiceResultOnSuccess(): void
    {
        $expected = [['id' => 'req-1', 'status' => 'PENDING']];
        $this->signingService->method('listRequests')->willReturn($expected);

        $response = $this->controller->listRequests();

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame($expected, $response->getData());

    }//end testListRequestsReturnsServiceResultOnSuccess()


}//end class

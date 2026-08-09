<?php

/**
 * Wire-contract tests for SigningController::getAudit()
 *
 * Covers `GET api/signing/requests/{id}/audit` (`signing#getAudit`): the
 * 200 audit-trail body, the 401 anonymous rejection, and the access rule that
 * makes this endpoint safe to expose — a non-admin caller who is neither the
 * initiator nor a listed signer gets a plain 404, identical to the answer for
 * a request that does not exist, and the audit trail is never read for them.
 *
 * An admin skips the per-request scoping and reads the trail directly.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-signing/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\SigningController;
use OCA\DocuDesk\Service\SigningAuditService;
use OCA\DocuDesk\Service\SigningService;
use OCA\DocuDesk\Service\SigningVerificationService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for the signing audit-trail endpoint.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningControllerAuditTest extends TestCase
{

    /**
     * Mocked signing service.
     *
     * @var SigningService|MockObject
     */
    private SigningService|MockObject $signingService;

    /**
     * Mocked audit service.
     *
     * @var SigningAuditService|MockObject
     */
    private SigningAuditService|MockObject $auditService;

    /**
     * Mocked localisation.
     *
     * @var IL10N|MockObject
     */
    private IL10N|MockObject $l10n;


    /**
     * Set up the shared mocks.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->signingService = $this->createMock(SigningService::class);
        $this->auditService   = $this->createMock(SigningAuditService::class);
        $this->l10n           = $this->createMock(IL10N::class);
        $this->l10n->method('t')->willReturnCallback(
            static function (string $text): string {
                return $text;
            }
        );

    }//end setUp()


    /**
     * Build the controller for a given caller.
     *
     * @param string|null $uid     The caller's UID, or null for an anonymous session.
     * @param bool        $isAdmin Whether the caller is an instance admin.
     *
     * @return SigningController The controller under test.
     */
    private function buildController(?string $uid, bool $isAdmin=false): SigningController
    {
        $session = $this->createMock(IUserSession::class);
        if ($uid === null) {
            $session->method('getUser')->willReturn(null);
        } else {
            $user = $this->createMock(IUser::class);
            $user->method('getUID')->willReturn($uid);
            $session->method('getUser')->willReturn($user);
        }

        $groupManager = $this->createMock(IGroupManager::class);
        $groupManager->method('isAdmin')->willReturn($isAdmin);

        return new SigningController(
            'docudesk',
            $this->createMock(IRequest::class),
            $this->signingService,
            $this->auditService,
            $this->createMock(SigningVerificationService::class),
            $session,
            $this->createMock(LoggerInterface::class),
            $this->l10n,
            $groupManager
        );

    }//end buildController()


    /**
     * A participant reads the trail: the request is first resolved scoped to
     * the caller's own UID, then the audit trail is returned with HTTP 200.
     *
     * @return void
     */
    public function testGetAuditReturnsTrailForParticipant(): void
    {
        $trail = [
            ['action' => 'docudesk.signing.created', 'actor' => 'alice'],
            ['action' => 'docudesk.signing.signed', 'actor' => 'bob'],
        ];

        $this->signingService->expects($this->once())
            ->method('getRequest')
            ->with('req-1', 'alice')
            ->willReturn(['id' => 'req-1']);
        $this->auditService->expects($this->once())
            ->method('getAuditTrail')
            ->with('req-1')
            ->willReturn($trail);

        $response = $this->buildController('alice')->getAudit('req-1');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame($trail, $response->getData());

    }//end testGetAuditReturnsTrailForParticipant()


    /**
     * An unrelated caller gets 404 and the audit trail — which carries IP
     * addresses and user identifiers — is never read.
     *
     * @return void
     */
    public function testGetAuditHidesTrailFromUnrelatedCaller(): void
    {
        $this->signingService->method('getRequest')->willReturn(null);
        $this->auditService->expects($this->never())->method('getAuditTrail');

        $response = $this->buildController('mallory')->getAudit('req-1');

        $this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertSame(['error' => 'Signing request not found'], $response->getData());

    }//end testGetAuditHidesTrailFromUnrelatedCaller()


    /**
     * An admin reads any trail without the per-request scoping step.
     *
     * @return void
     */
    public function testGetAuditAllowsAdminWithoutScopedLookup(): void
    {
        $this->signingService->expects($this->never())->method('getRequest');
        $this->auditService->expects($this->once())
            ->method('getAuditTrail')
            ->with('req-1')
            ->willReturn([['action' => 'docudesk.signing.created', 'actor' => 'alice']]);

        $response = $this->buildController('admin', true)->getAudit('req-1');

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertCount(1, $response->getData());

    }//end testGetAuditAllowsAdminWithoutScopedLookup()


    /**
     * An anonymous caller is refused with 401 before any lookup.
     *
     * @return void
     */
    public function testGetAuditRejectsAnonymousCaller(): void
    {
        $this->signingService->expects($this->never())->method('getRequest');
        $this->auditService->expects($this->never())->method('getAuditTrail');

        $response = $this->buildController(null)->getAudit('req-1');

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
        $this->assertSame(['error' => 'Not authenticated'], $response->getData());

    }//end testGetAuditRejectsAnonymousCaller()


    /**
     * A backend failure answers 500 with a generic body — the exception text
     * must never reach the client, since distinct messages would confirm
     * whether a request ID exists (docudesk#100).
     *
     * @return void
     */
    public function testGetAuditDoesNotLeakExceptionText(): void
    {
        $this->signingService->method('getRequest')->willReturn(['id' => 'req-1']);
        $this->auditService->method('getAuditTrail')
            ->willThrowException(new RuntimeException('Access denied: belongs to another user'));

        $response = $this->buildController('alice')->getAudit('req-1');

        $this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
        $body = json_encode($response->getData());
        $this->assertIsString($body);
        $this->assertStringNotContainsString('another user', $body);

    }//end testGetAuditDoesNotLeakExceptionText()


}//end class

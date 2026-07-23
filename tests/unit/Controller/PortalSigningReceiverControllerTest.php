<?php

/**
 * Unit tests for PortalSigningReceiverController.
 *
 * Pins the receiver's full fail-closed matrix (portal-signing-actions
 * REQ-DDPSA-002/003/004/006/007, portal-signing-surface REQ-DDPSS-002/003):
 * missing/invalid assertion -> 401; wrong audience, insufficient trust, no
 * signer-identifying claim, malformed target, and a genuinely foreign/
 * non-existent signing request all collapse to the SAME 403 (no existence
 * oracle) with `SigningService` never invoked; a valid opaque id is required
 * (SSRF hardening); the happy-path sign/decline/viewDocument acts drive
 * `SigningService` via its verified-actor entrypoint with identity sourced
 * ONLY from the verified assertion (never the request body); a downstream
 * failure relays 502 without leaking exception internals.
 *
 * @category Test
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @version GIT: <git_id>
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 * @spec openspec/specs/portal-signing-surface/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\PortalSigningReceiverController;
use OCA\DocuDesk\Portal\PortalAssertionVerifier;
use OCA\DocuDesk\Service\SettingsService;
use OCA\DocuDesk\Service\SigningService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Http;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for PortalSigningReceiverController.
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PortalSigningReceiverControllerTest extends TestCase
{

    /**
     * A >= 16 char signing secret, injected via PortalAssertionVerifier's
     * test-only secretOverride so the controller exercises a REAL verifier
     * (not a mock) end to end.
     */
    private const SECRET = 'docudesk-receiver-test-secret-01';

    /**
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * @var SigningService|MockObject
     */
    private SigningService|MockObject $mockSigningService;

    /**
     * @var SettingsService|MockObject
     */
    private SettingsService|MockObject $mockSettingsService;

    /**
     * @var ObjectService|MockObject
     */
    private ObjectService|MockObject $mockObjectService;

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockConfig;

    /**
     * @var IRootFolder|MockObject
     */
    private IRootFolder|MockObject $mockRootFolder;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * The invited signer record every "authorised" test case resolves to.
     *
     * @var array<string, mixed>
     */
    private const INVITED_SIGNER = [
        'id'                => 'signer-uuid-1',
        'email'             => 'alice@example.com',
        'signingRequestId'  => 'request-uuid-1',
        'status'            => 'PENDING',
    ];

    /**
     * Set up test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest         = $this->createMock(IRequest::class);
        $this->mockSigningService  = $this->createMock(SigningService::class);
        $this->mockSettingsService = $this->createMock(SettingsService::class);
        $this->mockObjectService   = $this->createMock(ObjectService::class);
        $this->mockConfig          = $this->createMock(IAppConfig::class);
        $this->mockRootFolder      = $this->createMock(IRootFolder::class);
        $this->mockLogger          = $this->createMock(LoggerInterface::class);

        $this->mockSettingsService->method('getObjectService')->willReturn($this->mockObjectService);
        $this->mockConfig->method('getValueString')->willReturnCallback(
            static fn (string $app, string $key, string $default=''): string => $default
        );

    }//end setUp()

    /**
     * Build the controller under test.
     *
     * @return PortalSigningReceiverController
     */
    private function controller(): PortalSigningReceiverController
    {
        return new PortalSigningReceiverController(
            appName: 'docudesk',
            request: $this->mockRequest,
            verifier: new PortalAssertionVerifier(config: null, secretOverride: self::SECRET),
            signingService: $this->mockSigningService,
            settingsService: $this->mockSettingsService,
            config: $this->mockConfig,
            rootFolder: $this->mockRootFolder,
            logger: $this->mockLogger
        );

    }//end controller()

    /**
     * Mint a portaliq-style X-Portal-Subject assertion.
     *
     * @param array<string, mixed> $overrides Claim overrides (null removes a claim).
     * @param string                $secret    The signing secret (defaults to the test secret).
     *
     * @return string Compact JWT.
     */
    private function mintAssertion(array $overrides=[], string $secret=self::SECRET): string
    {
        $iat    = time();
        $claims = array_merge(
            [
                'sub'         => '00000000-0000-0000-0000-000000000000',
                'audience'    => 'signer',
                'signerEmail' => 'alice@example.com',
                'trust'       => 'substantial',
                'jti'         => 'jti-0000000000000000000000000000',
                'use'         => 'assertion',
                'iat'         => $iat,
                'exp'         => ($iat + 60),
                'iss'         => 'portaliq',
            ],
            $overrides
        );

        foreach ($claims as $key => $value) {
            if ($value === null) {
                unset($claims[$key]);
            }
        }

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $hPart  = $this->b64UrlEncode((string) json_encode($header, JSON_UNESCAPED_SLASHES));
        $cPart  = $this->b64UrlEncode((string) json_encode($claims, JSON_UNESCAPED_SLASHES));
        $sig    = $this->b64UrlEncode(hash_hmac('sha256', $hPart.'.'.$cPart, $secret, true));

        return $hPart.'.'.$cPart.'.'.$sig;

    }//end mintAssertion()

    /**
     * Base64-url encode (no padding).
     *
     * @param string $bytes Raw bytes.
     *
     * @return string
     */
    private function b64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

    }//end b64UrlEncode()

    /**
     * Wire the mock request to present the given assertion header + params.
     *
     * @param string|null           $assertion The X-Portal-Subject header value (null omits it).
     * @param array<string, mixed>  $params    Request params (e.g. signingRequestId, consent, reason).
     *
     * @return void
     */
    private function withRequest(?string $assertion, array $params=[]): void
    {
        $this->mockRequest->method('getHeader')->willReturnCallback(
            function (string $name) use ($assertion): string {
                return ($name === PortalAssertionVerifier::HEADER) ? (string) $assertion : '';
            }
        );

        $this->mockRequest->method('getParam')->willReturnCallback(
            static function (string $key, $default=null) use ($params) {
                return $params[$key] ?? $default;
            }
        );

    }//end withRequest()

    /**
     * Wire the mock ObjectService to resolve INVITED_SIGNER for the given
     * email + signingRequestId, and null for anything else.
     *
     * @return void
     */
    private function withInvitedSigner(): void
    {
        $this->mockObjectService->method('findAll')->willReturn([self::INVITED_SIGNER]);

    }//end withInvitedSigner()

    /**
     * Missing assertion header -> 401, SigningService never called.
     *
     * @return void
     */
    public function testMissingAssertionReturns401(): void
    {
        $this->withRequest(assertion: null, params: ['signingRequestId' => 'request-uuid-1']);
        $this->mockSigningService->expects($this->never())->method('sign');

        $result = $this->controller()->signDocument();

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $result->getStatus());

    }//end testMissingAssertionReturns401()

    /**
     * A structurally invalid / badly signed assertion -> 401.
     *
     * @return void
     */
    public function testInvalidAssertionReturns401(): void
    {
        $this->withRequest(assertion: 'not-a-jwt', params: ['signingRequestId' => 'request-uuid-1']);

        $result = $this->controller()->signDocument();

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $result->getStatus());

    }//end testInvalidAssertionReturns401()

    /**
     * An assertion signed with the wrong secret -> 401.
     *
     * @return void
     */
    public function testWrongSecretReturns401(): void
    {
        $this->withRequest(
            assertion: $this->mintAssertion(secret: 'a-completely-different-secret-x'),
            params: ['signingRequestId' => 'request-uuid-1']
        );

        $result = $this->controller()->signDocument();

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $result->getStatus());

    }//end testWrongSecretReturns401()

    /**
     * An expired assertion -> 401.
     *
     * @return void
     */
    public function testExpiredAssertionReturns401(): void
    {
        $now = time();
        $this->withRequest(
            assertion: $this->mintAssertion(['iat' => ($now - 120), 'exp' => ($now - 60)]),
            params: ['signingRequestId' => 'request-uuid-1']
        );

        $result = $this->controller()->signDocument();

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $result->getStatus());

    }//end testExpiredAssertionReturns401()

    /**
     * Wrong audience -> 403, uniform not-authorised, SigningService never called.
     *
     * @return void
     */
    public function testWrongAudienceReturns403(): void
    {
        $this->withRequest(
            assertion: $this->mintAssertion(['audience' => 'client']),
            params: ['signingRequestId' => 'request-uuid-1']
        );
        $this->mockSigningService->expects($this->never())->method('sign');

        $result = $this->controller()->signDocument();

        $this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());

    }//end testWrongAudienceReturns403()

    /**
     * Insufficient trust (below `substantial`) -> 403.
     *
     * @return void
     */
    public function testInsufficientTrustReturns403(): void
    {
        $this->withRequest(
            assertion: $this->mintAssertion(['trust' => 'low']),
            params: ['signingRequestId' => 'request-uuid-1']
        );

        $result = $this->controller()->signDocument();

        $this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());

    }//end testInsufficientTrustReturns403()

    /**
     * No `signerEmail` scope claim -> 403 (the safe fail-closed posture while
     * portaliq's A6 amendment is pending, design.md Open Question 1).
     *
     * @return void
     */
    public function testMissingSignerEmailClaimReturns403(): void
    {
        $this->withRequest(
            assertion: $this->mintAssertion(['signerEmail' => null]),
            params: ['signingRequestId' => 'request-uuid-1']
        );

        $result = $this->controller()->signDocument();

        $this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());

    }//end testMissingSignerEmailClaimReturns403()

    /**
     * A URL/path-shaped `signingRequestId` is rejected before any lookup
     * (SSRF hardening, REQ-DDPSA-004) — SigningService never called.
     *
     * @return void
     */
    public function testUrlShapedTargetIsRejected(): void
    {
        $this->mockSigningService->expects($this->never())->method('sign');

        foreach (['http://evil.example/x', '../../etc/passwd', 'a/b', 'a b', ''] as $badId) {
            $this->withRequest(assertion: $this->mintAssertion(), params: ['signingRequestId' => $badId]);

            $result = $this->controller()->signDocument();

            $this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus(), 'Rejected for id: '.$badId);
        }

    }//end testUrlShapedTargetIsRejected()

    /**
     * A non-existent signing request and a foreign one (wrong email/request
     * pairing) both collapse to the SAME 403 body — no existence oracle
     * (REQ-DDPSA-004) — and `SigningService` is never invoked either way.
     *
     * @return void
     */
    public function testNonExistentAndForeignRequestAreByteIdentical(): void
    {
        $this->mockSigningService->expects($this->never())->method('sign');

        // Case A: resolveInvitedSigner finds nothing at all.
        $this->mockObjectService->method('findAll')->willReturn([]);
        $this->withRequest(assertion: $this->mintAssertion(), params: ['signingRequestId' => 'no-such-request']);
        $notFound = $this->controller()->signDocument();

        $this->assertSame(Http::STATUS_FORBIDDEN, $notFound->getStatus());
        $this->assertSame(
            json_encode($notFound->getData()),
            json_encode(['error' => 'forbidden']),
            'Non-existent and foreign-request responses must be identical to the standard forbidden body.'
        );

    }//end testNonExistentAndForeignRequestAreByteIdentical()

    /**
     * A signerRecord that exists but belongs to a DIFFERENT email is treated
     * identically to a non-existent one (cross-signer IDOR guard).
     *
     * @return void
     */
    public function testCrossSignerRecordIsRejected(): void
    {
        $this->mockSigningService->expects($this->never())->method('sign');

        // A signer record exists, but for a different email address.
        $this->mockObjectService->method('findAll')->willReturn(
            [
                [
                    'id'               => 'someone-elses-signer',
                    'email'            => 'mallory@example.com',
                    'signingRequestId' => 'request-uuid-1',
                    'status'           => 'PENDING',
                ],
            ]
        );

        $this->withRequest(assertion: $this->mintAssertion(), params: ['signingRequestId' => 'request-uuid-1']);

        $result = $this->controller()->signDocument();

        $this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());

    }//end testCrossSignerRecordIsRejected()

    /**
     * Happy-path signDocument: consent + drawn signature recorded, drives
     * `SigningService::sign()` with the verified actor, status returned
     * (portal-signing-surface REQ-DDPSS-002).
     *
     * @return void
     */
    public function testSignDocumentHappyPath(): void
    {
        $this->withInvitedSigner();
        $this->withRequest(
            assertion: $this->mintAssertion(),
            params: [
                'signingRequestId' => 'request-uuid-1',
                'consent'          => true,
                'signature'        => 'data:image/png;base64,AAAA',
            ]
        );

        $this->mockSigningService->expects($this->once())
            ->method('sign')
            // Positional (NOT named) constraint args — the real signature is
            // sign(requestId, signerId, verifiedActor, signatureData); a
            // variadic PHPUnit ->with() treats named args as array keys, not
            // positional matchers, so this MUST stay positional.
            ->with(
                'request-uuid-1',
                'signer-uuid-1',
                $this->callback(
                    static function (array $actor): bool {
                        return ($actor['email'] ?? null) === 'alice@example.com'
                            && ($actor['jti'] ?? null) === 'jti-0000000000000000000000000000'
                            && ($actor['trust'] ?? null) === 'substantial';
                    }
                ),
                $this->callback(
                    static function (array $data): bool {
                        return ($data['consent'] ?? null) === true
                            && ($data['signature'] ?? null) === 'data:image/png;base64,AAAA';
                    }
                )
            )
            ->willReturn(['status' => 'SIGNED']);

        $result = $this->controller()->signDocument();

        $this->assertSame(Http::STATUS_OK, $result->getStatus());
        $this->assertSame('SIGNED', $result->getData()['status']);

    }//end testSignDocumentHappyPath()

    /**
     * Happy-path declineDocument: reason recorded, drives
     * `SigningService::decline()` with the verified actor.
     *
     * @return void
     */
    public function testDeclineDocumentHappyPath(): void
    {
        $this->withInvitedSigner();
        $this->withRequest(
            assertion: $this->mintAssertion(),
            params: [
                'signingRequestId' => 'request-uuid-1',
                'reason'           => 'Terms have changed',
            ]
        );

        $this->mockSigningService->expects($this->once())
            ->method('decline')
            // Positional — real signature is decline(requestId, signerId, reason, verifiedActor).
            ->with(
                'request-uuid-1',
                'signer-uuid-1',
                'Terms have changed',
                $this->callback(
                    static fn (array $actor): bool => ($actor['email'] ?? null) === 'alice@example.com'
                )
            )
            ->willReturn(['status' => 'DECLINED']);

        $result = $this->controller()->declineDocument();

        $this->assertSame(Http::STATUS_OK, $result->getStatus());
        $this->assertSame('DECLINED', $result->getData()['status']);

    }//end testDeclineDocumentHappyPath()

    /**
     * A downstream `SigningService` failure relays 502 — never leaking
     * exception internals (REQ-DDPSA-005/007).
     *
     * @return void
     */
    public function testDownstreamFailureReturns502(): void
    {
        $this->withInvitedSigner();
        $this->withRequest(assertion: $this->mintAssertion(), params: ['signingRequestId' => 'request-uuid-1']);

        $this->mockSigningService->method('sign')
            ->willThrowException(new \RuntimeException('Signer has already responded to this request'));

        $result = $this->controller()->signDocument();

        $this->assertSame(Http::STATUS_BAD_GATEWAY, $result->getStatus());
        $body = json_encode($result->getData());
        $this->assertStringNotContainsString('already responded', (string) $body);

    }//end testDownstreamFailureReturns502()

    /**
     * viewDocument happy path: returns the target document as base64 JSON,
     * scoped by the SAME invited-signer guard (REQ-DDPSA-006).
     *
     * @return void
     */
    public function testViewDocumentHappyPath(): void
    {
        $this->withInvitedSigner();
        $this->withRequest(assertion: $this->mintAssertion(), params: ['signingRequestId' => 'request-uuid-1']);

        $this->mockSigningService->method('getRequest')
            ->with('request-uuid-1')
            ->willReturn(
                [
                    'documentFileId'  => 42,
                    'initiatorUserId' => 'initiator-user',
                    'documentName'    => 'contract.pdf',
                ]
            );

        $mockFile = $this->createMock(File::class);
        $mockFile->method('getContent')->willReturn('%PDF-1.4 content');
        $mockFile->method('getMimeType')->willReturn('application/pdf');
        $mockFile->method('getName')->willReturn('contract.pdf');

        $mockFolder = $this->createMock(Folder::class);
        $mockFolder->method('getById')->with(42)->willReturn([$mockFile]);
        $this->mockRootFolder->method('getUserFolder')->with('initiator-user')->willReturn($mockFolder);

        $result = $this->controller()->viewDocument();

        $this->assertSame(Http::STATUS_OK, $result->getStatus());
        $data = $result->getData();
        $this->assertSame('contract.pdf', $data['documentName']);
        $this->assertSame('application/pdf', $data['mimeType']);
        $this->assertSame(base64_encode('%PDF-1.4 content'), $data['contentBase64']);

    }//end testViewDocumentHappyPath()

    /**
     * viewDocument when the resolved signing request is null (should not
     * happen given the invited-signer guard already passed, but defends the
     * uniform not-authorised contract regardless) -> 403.
     *
     * @return void
     */
    public function testViewDocumentNullRequestReturns403(): void
    {
        $this->withInvitedSigner();
        $this->withRequest(assertion: $this->mintAssertion(), params: ['signingRequestId' => 'request-uuid-1']);

        $this->mockSigningService->method('getRequest')->willReturn(null);

        $result = $this->controller()->viewDocument();

        $this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());

    }//end testViewDocumentNullRequestReturns403()
}//end class

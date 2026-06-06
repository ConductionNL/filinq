<?php

/**
 * Unit tests for SigningService
 *
 * Covers the signing request lifecycle: create, get, list, sign, decline,
 * cancel, bulk sign, and status transition validation per REQ-SIGN-01..05.
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-signing/tasks.md#2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCA\DocuDesk\Service\SigningAuditService;
use OCA\DocuDesk\Service\SigningService;
use OCA\DocuDesk\Service\SettingsService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for SigningService signing request lifecycle
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningServiceTest extends TestCase
{

    /**
     * @var SigningService
     */
    private SigningService $service;

    /**
     * @var SettingsService|MockObject
     */
    private SettingsService|MockObject $settingsService;

    /**
     * @var ObjectService|MockObject
     */
    private ObjectService|MockObject $objectService;

    /**
     * @var SigningAuditService|MockObject
     */
    private SigningAuditService|MockObject $auditService;

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $config;

    /**
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $userSession;

    /**
     * @var IUser|MockObject
     */
    private IUser|MockObject $user;

    /**
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $request;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectService = $this->getMockBuilder(className: ObjectService::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods(['saveObject', 'find', 'findAll', 'searchObjects'])
            ->getMock();

        $this->settingsService = $this->createMock(SettingsService::class);
        $this->settingsService->method('getObjectService')->willReturn($this->objectService);

        $this->auditService = $this->createMock(SigningAuditService::class);

        $this->config = $this->createMock(IAppConfig::class);
        $this->config->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default=''): string {
                $map = [
                    'signingRequest_register'     => 'signing',
                    'signingRequest_schema'       => 'signingRequest',
                    'signerRecord_register'       => 'signing',
                    'signerRecord_schema'         => 'signerRecord',
                    'signing_request_expiry_days' => '30',
                    'signing_default_level'       => 'SES',
                    'signing_provider'            => 'native',
                ];
                return $map[$key] ?? $default;
            }
        );

        $this->user = $this->createMock(IUser::class);
        $this->user->method('getUID')->willReturn('alice');
        $this->user->method('getDisplayName')->willReturn('Alice');

        $this->userSession = $this->createMock(IUserSession::class);
        $this->userSession->method('getUser')->willReturn($this->user);

        $this->request = $this->createMock(IRequest::class);
        $this->request->method('getRemoteAddress')->willReturn('127.0.0.1');

        $providerFactory     = $this->createMock(SigningProviderFactory::class);
        $notificationManager = $this->createMock(INotificationManager::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->service = new SigningService(
            settingsService: $this->settingsService,
            auditService: $this->auditService,
            providerFactory: $providerFactory,
            config: $this->config,
            userSession: $this->userSession,
            notificationManager: $notificationManager,
            logger: $logger,
            request: $this->request
        );

    }//end setUp()

    /**
     * createRequest() with valid data creates and persists a signing request.
     *
     * @return void
     */
    public function testCreateRequestHappyPath(): void
    {
        $savedRequest = [
            'id'              => 'req-001',
            'documentFileId'  => 'file-001',
            'documentName'    => 'besluit.pdf',
            'initiatorUserId' => 'alice',
            'signatureLevel'  => 'SES',
            'signingMode'     => 'sequential',
            'status'          => 'PENDING',
            'provider'        => 'native',
            'signerIds'       => [],
        ];

        // First saveObject call creates the request; subsequent calls update signerIds.
        $this->objectService->expects($this->atLeastOnce())
            ->method('saveObject')
            ->willReturn($savedRequest);

        $this->auditService->expects($this->once())
            ->method('logEvent');

        $result = $this->service->createRequest(
                data: [
                    'documentFileId' => 'file-001',
                    'documentName'   => 'besluit.pdf',
                    'signatureLevel' => 'SES',
                    'signingMode'    => 'sequential',
                ]
                );

        $this->assertSame('PENDING', $result['status']);
        $this->assertSame('alice', $result['initiatorUserId']);

    }//end testCreateRequestHappyPath()

    /**
     * createRequest() rejects missing documentFileId.
     *
     * @return void
     */
    public function testCreateRequestRejectsEmptyDocumentFileId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Document file ID is required');

        $this->service->createRequest(
                data: [
                    'documentFileId' => '',
                    'documentName'   => 'test.pdf',
                    'signatureLevel' => 'SES',
                    'signingMode'    => 'sequential',
                ]
                );

    }//end testCreateRequestRejectsEmptyDocumentFileId()

    /**
     * createRequest() rejects an invalid signature level.
     *
     * @return void
     */
    public function testCreateRequestRejectsInvalidSignatureLevel(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid signature level');

        $this->service->createRequest(
                data: [
                    'documentFileId' => 'file-001',
                    'documentName'   => 'test.pdf',
                    'signatureLevel' => 'INVALID',
                    'signingMode'    => 'sequential',
                ]
                );

    }//end testCreateRequestRejectsInvalidSignatureLevel()

    /**
     * createRequest() rejects an invalid signing mode.
     *
     * @return void
     */
    public function testCreateRequestRejectsInvalidSigningMode(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid signing mode');

        $this->service->createRequest(
                data: [
                    'documentFileId' => 'file-001',
                    'documentName'   => 'test.pdf',
                    'signatureLevel' => 'SES',
                    'signingMode'    => 'unknown-mode',
                ]
                );

    }//end testCreateRequestRejectsInvalidSigningMode()

    /**
     * getRequest() returns the object when found.
     *
     * @return void
     */
    public function testGetRequestReturnsObjectWhenFound(): void
    {
        $requestData = [
            'id'     => 'req-001',
            'status' => 'PENDING',
        ];
        $this->objectService->method('find')->willReturn($requestData);

        $result = $this->service->getRequest(requestId: 'req-001');

        $this->assertSame('PENDING', $result['status']);

    }//end testGetRequestReturnsObjectWhenFound()

    /**
     * getRequest() throws when the request is not found.
     *
     * @return void
     */
    public function testGetRequestThrowsWhenNotFound(): void
    {
        $this->objectService->method('find')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Signing request not found');

        $this->service->getRequest(requestId: 'does-not-exist');

    }//end testGetRequestThrowsWhenNotFound()

    /**
     * listRequests() returns an array of request arrays.
     *
     * @return void
     */
    public function testListRequestsReturnsArray(): void
    {
        $this->objectService->method('findAll')->willReturn(
                [
                    ['id' => 'req-001', 'status' => 'PENDING'],
                    ['id' => 'req-002', 'status' => 'COMPLETED'],
                ]
                );

        $result = $this->service->listRequests();

        $this->assertCount(2, $result);
        $this->assertSame('req-001', $result[0]['id']);

    }//end testListRequestsReturnsArray()

    /**
     * sign() marks the signer SIGNED and logs the audit event.
     *
     * @return void
     */
    public function testSignHappyPath(): void
    {
        $requestData = [
            'id'              => 'req-001',
            'status'          => 'PENDING',
            'signatureLevel'  => 'SES',
            'provider'        => 'native',
            'initiatorUserId' => 'bob',
            'signerIds'       => ['signer-001'],
        ];
        $signerData  = [
            'id'               => 'signer-001',
            'signingRequestId' => 'req-001',
            'userId'           => 'alice',
            'status'           => 'PENDING',
        ];

        $this->objectService->method('find')->willReturnOnConsecutiveCalls(
            $requestData,
            $signerData,
            $requestData,
            $signerData
        );
        $this->objectService->method('saveObject')->willReturnArgument(0);

        $this->auditService->expects($this->once())
            ->method('logEvent')
            ->with($this->equalTo('req-001'), $this->equalTo('SIGNED'));

        $result = $this->service->sign(requestId: 'req-001', signerId: 'signer-001');

        $this->assertSame('SIGNED', $result['status']);
        $this->assertArrayHasKey('signedAt', $result);

    }//end testSignHappyPath()

    /**
     * sign() throws when signer record belongs to a different request.
     *
     * @return void
     */
    public function testSignThrowsOnSignerRequestMismatch(): void
    {
        $requestData = [
            'id'     => 'req-001',
            'status' => 'PENDING',
        ];
        $signerData  = [
            'id'               => 'signer-001',
            'signingRequestId' => 'req-other',
            'userId'           => 'alice',
            'status'           => 'PENDING',
        ];

        $this->objectService->method('find')->willReturnOnConsecutiveCalls($requestData, $signerData);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not belong to this signing request');

        $this->service->sign(requestId: 'req-001', signerId: 'signer-001');

    }//end testSignThrowsOnSignerRequestMismatch()

    /**
     * sign() throws when the authenticated user is not the signer.
     *
     * @return void
     */
    public function testSignThrowsWhenUserIsNotSigner(): void
    {
        $requestData = [
            'id'     => 'req-001',
            'status' => 'PENDING',
        ];
        $signerData  = [
            'id'               => 'signer-001',
            'signingRequestId' => 'req-001',
            'userId'           => 'charlie',
            'status'           => 'PENDING',
        ];

        $this->objectService->method('find')->willReturnOnConsecutiveCalls($requestData, $signerData);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not authorized to sign as this signer');

        $this->service->sign(requestId: 'req-001', signerId: 'signer-001');

    }//end testSignThrowsWhenUserIsNotSigner()

    /**
     * cancelRequest() transitions the request status to CANCELLED.
     *
     * @return void
     */
    public function testCancelRequestTransitionsToCancel(): void
    {
        $requestData = [
            'id'     => 'req-001',
            'status' => 'PENDING',
        ];

        $this->objectService->method('find')->willReturn($requestData);
        $this->objectService->method('saveObject')->willReturnArgument(0);

        $result = $this->service->cancelRequest(requestId: 'req-001');

        $this->assertSame('CANCELLED', $result['status']);

    }//end testCancelRequestTransitionsToCancel()

    /**
     * cancelRequest() throws when the request is in a terminal state.
     *
     * @return void
     */
    public function testCancelRequestThrowsForTerminalStatus(): void
    {
        $requestData = [
            'id'     => 'req-001',
            'status' => 'COMPLETED',
        ];

        $this->objectService->method('find')->willReturn($requestData);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot cancel request in status');

        $this->service->cancelRequest(requestId: 'req-001');

    }//end testCancelRequestThrowsForTerminalStatus()

    /**
     * isValidTransition() returns true for allowed transitions.
     *
     * @return void
     */
    public function testIsValidTransitionAllowedPaths(): void
    {
        $this->assertTrue($this->service->isValidTransition(currentStatus: 'PENDING', newStatus: 'IN_PROGRESS'));
        $this->assertTrue($this->service->isValidTransition(currentStatus: 'IN_PROGRESS', newStatus: 'COMPLETED'));
        $this->assertTrue($this->service->isValidTransition(currentStatus: 'PENDING', newStatus: 'CANCELLED'));
        $this->assertTrue($this->service->isValidTransition(currentStatus: 'IN_PROGRESS', newStatus: 'EXPIRED'));

    }//end testIsValidTransitionAllowedPaths()

    /**
     * isValidTransition() returns false for disallowed transitions.
     *
     * @return void
     */
    public function testIsValidTransitionBlockedPaths(): void
    {
        $this->assertFalse($this->service->isValidTransition(currentStatus: 'COMPLETED', newStatus: 'PENDING'));
        $this->assertFalse($this->service->isValidTransition(currentStatus: 'DECLINED', newStatus: 'IN_PROGRESS'));
        $this->assertFalse($this->service->isValidTransition(currentStatus: 'EXPIRED', newStatus: 'SIGNED'));
        $this->assertFalse($this->service->isValidTransition(currentStatus: 'CANCELLED', newStatus: 'IN_PROGRESS'));

    }//end testIsValidTransitionBlockedPaths()

    /**
     * bulkSign() returns success/error results per request ID.
     *
     * @return void
     */
    public function testBulkSignReturnsResultsPerRequest(): void
    {
        $requestData = [
            'id'             => 'req-001',
            'status'         => 'PENDING',
            'signatureLevel' => 'SES',
            'provider'       => 'native',
            'signerIds'      => [],
        ];

        $this->objectService->method('find')->willReturn($requestData);

        $results = $this->service->bulkSign(requestIds: ['req-001']);

        $this->assertArrayHasKey('req-001', $results);
        // No signer record found for current user → error result.
        $this->assertFalse($results['req-001']['success']);

    }//end testBulkSignReturnsResultsPerRequest()
}//end class

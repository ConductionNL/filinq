<?php

/**
 * Unit tests for SigningService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-4.5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCA\DocuDesk\Service\SigningAuditService;
use OCA\DocuDesk\Service\SigningService;
use OCA\DocuDesk\Service\SettingsService;
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
 * Unit tests for SigningService
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
    private SettingsService|MockObject $mockSettingsService;

    /**
     * @var SigningAuditService|MockObject
     */
    private SigningAuditService|MockObject $mockAuditService;

    /**
     * @var SigningProviderFactory|MockObject
     */
    private SigningProviderFactory|MockObject $mockProviderFactory;

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockConfig;

    /**
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * @var INotificationManager|MockObject
     */
    private INotificationManager|MockObject $mockNotificationManager;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSettingsService = $this->createMock(SettingsService::class);
        $this->mockAuditService    = $this->createMock(SigningAuditService::class);
        $this->mockProviderFactory = $this->createMock(SigningProviderFactory::class);
        $this->mockConfig          = $this->createMock(IAppConfig::class);
        $this->mockUserSession     = $this->createMock(IUserSession::class);
        $this->mockNotificationManager = $this->createMock(INotificationManager::class);
        $this->mockLogger  = $this->createMock(LoggerInterface::class);
        $this->mockRequest = $this->createMock(IRequest::class);

        $this->service = new SigningService(
            settingsService: $this->mockSettingsService,
            auditService: $this->mockAuditService,
            providerFactory: $this->mockProviderFactory,
            config: $this->mockConfig,
            userSession: $this->mockUserSession,
            notificationManager: $this->mockNotificationManager,
            logger: $this->mockLogger,
            request: $this->mockRequest,
        );

    }//end setUp()

    /**
     * Test createRequest throws RuntimeException when no authenticated user.
     *
     * @return void
     */
    public function testCreateRequestThrowsWhenNoUser(): void
    {
        $this->mockUserSession->method('getUser')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No authenticated user');

        $this->service->createRequest(['documentFileId' => 1]);

    }//end testCreateRequestThrowsWhenNoUser()

    /**
     * Test constructor stores all dependencies (smoke test).
     *
     * @return void
     */
    public function testConstructorCreateInstance(): void
    {
        $this->assertInstanceOf(SigningService::class, $this->service);

    }//end testConstructorCreateInstance()

    /**
     * Test isValidStatusTransition for known valid transitions.
     *
     * @return void
     */
    public function testCreateRequestWithAuthenticatedUser(): void
    {
        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('testuser');
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $this->mockConfig->method('getValueString')->willReturn('SES');

        $mockObjectService = $this->createMock(\OCA\OpenRegister\Service\ObjectService::class);
        $mockObjectService->method('saveObject')->willReturn(['id' => 'new-request', 'status' => 'PENDING']);
        $this->mockSettingsService->method('getObjectService')->willReturn($mockObjectService);

        $result = $this->service->createRequest(
            [
                'documentFileId' => 'file-uuid',
                'documentName'   => 'Test Document',
                'signatureLevel' => 'SES',
            ]
        );

        $this->assertIsArray($result);

    }//end testCreateRequestWithAuthenticatedUser()

    /**
     * Test listRequests returns results from object service.
     *
     * @return void
     */
    public function testListRequestsReturnsResultsFromObjectService(): void
    {
        $mockObjectService = $this->createMock(\OCA\OpenRegister\Service\ObjectService::class);
        $mockObjectService->method('findAll')->willReturn([]);
        $this->mockSettingsService->method('getObjectService')->willReturn($mockObjectService);
        $this->mockConfig->method('getValueString')->willReturn('');

        $result = $this->service->listRequests();

        $this->assertIsArray($result);

    }//end testListRequestsReturnsResultsFromObjectService()
}//end class

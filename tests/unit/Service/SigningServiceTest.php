<?php

/**
 * Unit tests for SigningService status machine transitions.
 *
 * Covers:
 *  1. Valid status transitions are accepted (DRAFT→PENDING, PENDING→IN_PROGRESS, etc.)
 *  2. Invalid status transitions are rejected (COMPLETED→PENDING, etc.)
 *  3. Terminal states (COMPLETED, DECLINED, EXPIRED, CANCELLED) have no
 *     allowed transitions.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#9-2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\SettingsService;
use OCA\DocuDesk\Service\SigningAuditService;
use OCA\DocuDesk\Service\SigningService;
use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for SigningService status machine
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
     * The service under test.
     *
     * @var SigningService
     */
    private SigningService $service;

    /**
     * Mock app config.
     *
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockConfig;

    /**
     * Mock settings service.
     *
     * @var SettingsService|MockObject
     */
    private SettingsService|MockObject $mockSettings;

    /**
     * Mock object service.
     *
     * @var ObjectService|MockObject
     */
    private ObjectService|MockObject $mockObjectService;

    /**
     * Mock audit service.
     *
     * @var SigningAuditService|MockObject
     */
    private SigningAuditService|MockObject $mockAuditService;

    /**
     * Mock provider factory.
     *
     * @var SigningProviderFactory|MockObject
     */
    private SigningProviderFactory|MockObject $mockProviderFactory;

    /**
     * Mock user session.
     *
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * Mock notification manager.
     *
     * @var INotificationManager|MockObject
     */
    private INotificationManager|MockObject $mockNotificationManager;

    /**
     * Mock logger.
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Mock request.
     *
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig          = $this->createMock(originalClassName: IAppConfig::class);
        $this->mockSettings        = $this->createMock(originalClassName: SettingsService::class);
        $this->mockObjectService   = $this->createMock(originalClassName: ObjectService::class);
        $this->mockAuditService    = $this->createMock(originalClassName: SigningAuditService::class);
        $this->mockProviderFactory = $this->createMock(originalClassName: SigningProviderFactory::class);
        $this->mockUserSession     = $this->createMock(originalClassName: IUserSession::class);
        $this->mockNotificationManager = $this->createMock(originalClassName: INotificationManager::class);
        $this->mockLogger  = $this->createMock(originalClassName: LoggerInterface::class);
        $this->mockRequest = $this->createMock(originalClassName: IRequest::class);

        $this->mockSettings->method('getObjectService')->willReturn($this->mockObjectService);
        $this->mockConfig->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default=''): string {
                return $default;
            }
        );

        $this->service = new SigningService(
            settingsService: $this->mockSettings,
            auditService: $this->mockAuditService,
            providerFactory: $this->mockProviderFactory,
            config: $this->mockConfig,
            userSession: $this->mockUserSession,
            notificationManager: $this->mockNotificationManager,
            logger: $this->mockLogger,
            request: $this->mockRequest
        );

    }//end setUp()

    /**
     * DRAFT to PENDING is a valid transition.
     *
     * @return void
     */
    public function testDraftToPendingIsValid(): void
    {
        $this->assertTrue(
            condition: $this->service->isValidTransition(currentStatus: 'DRAFT', newStatus: 'PENDING')
        );

    }//end testDraftToPendingIsValid()

    /**
     * DRAFT to CANCELLED is a valid transition.
     *
     * @return void
     */
    public function testDraftToCancelledIsValid(): void
    {
        $this->assertTrue(
            condition: $this->service->isValidTransition(currentStatus: 'DRAFT', newStatus: 'CANCELLED')
        );

    }//end testDraftToCancelledIsValid()

    /**
     * PENDING to IN_PROGRESS is a valid transition.
     *
     * @return void
     */
    public function testPendingToInProgressIsValid(): void
    {
        $this->assertTrue(
            condition: $this->service->isValidTransition(currentStatus: 'PENDING', newStatus: 'IN_PROGRESS')
        );

    }//end testPendingToInProgressIsValid()

    /**
     * PENDING to CANCELLED is a valid transition.
     *
     * @return void
     */
    public function testPendingToCancelledIsValid(): void
    {
        $this->assertTrue(
            condition: $this->service->isValidTransition(currentStatus: 'PENDING', newStatus: 'CANCELLED')
        );

    }//end testPendingToCancelledIsValid()

    /**
     * PENDING to EXPIRED is a valid transition.
     *
     * @return void
     */
    public function testPendingToExpiredIsValid(): void
    {
        $this->assertTrue(
            condition: $this->service->isValidTransition(currentStatus: 'PENDING', newStatus: 'EXPIRED')
        );

    }//end testPendingToExpiredIsValid()

    /**
     * IN_PROGRESS to COMPLETED is a valid transition.
     *
     * @return void
     */
    public function testInProgressToCompletedIsValid(): void
    {
        $this->assertTrue(
            condition: $this->service->isValidTransition(currentStatus: 'IN_PROGRESS', newStatus: 'COMPLETED')
        );

    }//end testInProgressToCompletedIsValid()

    /**
     * IN_PROGRESS to DECLINED is a valid transition.
     *
     * @return void
     */
    public function testInProgressToDeclinedIsValid(): void
    {
        $this->assertTrue(
            condition: $this->service->isValidTransition(currentStatus: 'IN_PROGRESS', newStatus: 'DECLINED')
        );

    }//end testInProgressToDeclinedIsValid()

    /**
     * IN_PROGRESS to CANCELLED is a valid transition.
     *
     * @return void
     */
    public function testInProgressToCancelledIsValid(): void
    {
        $this->assertTrue(
            condition: $this->service->isValidTransition(currentStatus: 'IN_PROGRESS', newStatus: 'CANCELLED')
        );

    }//end testInProgressToCancelledIsValid()

    /**
     * IN_PROGRESS to EXPIRED is a valid transition.
     *
     * @return void
     */
    public function testInProgressToExpiredIsValid(): void
    {
        $this->assertTrue(
            condition: $this->service->isValidTransition(currentStatus: 'IN_PROGRESS', newStatus: 'EXPIRED')
        );

    }//end testInProgressToExpiredIsValid()

    /**
     * COMPLETED is a terminal state with no allowed transitions.
     *
     * @return void
     */
    public function testCompletedIsTerminal(): void
    {
        foreach (['DRAFT', 'PENDING', 'IN_PROGRESS', 'DECLINED', 'CANCELLED', 'EXPIRED'] as $target) {
            $this->assertFalse(
                condition: $this->service->isValidTransition(currentStatus: 'COMPLETED', newStatus: $target),
                message: "COMPLETED → {$target} should be rejected"
            );
        }

    }//end testCompletedIsTerminal()

    /**
     * DECLINED is a terminal state with no allowed transitions.
     *
     * @return void
     */
    public function testDeclinedIsTerminal(): void
    {
        foreach (['DRAFT', 'PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'EXPIRED'] as $target) {
            $this->assertFalse(
                condition: $this->service->isValidTransition(currentStatus: 'DECLINED', newStatus: $target),
                message: "DECLINED → {$target} should be rejected"
            );
        }

    }//end testDeclinedIsTerminal()

    /**
     * EXPIRED is a terminal state with no allowed transitions.
     *
     * @return void
     */
    public function testExpiredIsTerminal(): void
    {
        foreach (['DRAFT', 'PENDING', 'IN_PROGRESS', 'COMPLETED', 'DECLINED', 'CANCELLED'] as $target) {
            $this->assertFalse(
                condition: $this->service->isValidTransition(currentStatus: 'EXPIRED', newStatus: $target),
                message: "EXPIRED → {$target} should be rejected"
            );
        }

    }//end testExpiredIsTerminal()

    /**
     * CANCELLED is a terminal state with no allowed transitions.
     *
     * @return void
     */
    public function testCancelledIsTerminal(): void
    {
        foreach (['DRAFT', 'PENDING', 'IN_PROGRESS', 'COMPLETED', 'DECLINED', 'EXPIRED'] as $target) {
            $this->assertFalse(
                condition: $this->service->isValidTransition(currentStatus: 'CANCELLED', newStatus: $target),
                message: "CANCELLED → {$target} should be rejected"
            );
        }

    }//end testCancelledIsTerminal()

    /**
     * DRAFT to COMPLETED is an invalid skip transition.
     *
     * @return void
     */
    public function testDraftToCompletedIsInvalid(): void
    {
        $this->assertFalse(
            condition: $this->service->isValidTransition(currentStatus: 'DRAFT', newStatus: 'COMPLETED')
        );

    }//end testDraftToCompletedIsInvalid()

    /**
     * DRAFT to DECLINED is an invalid skip transition.
     *
     * @return void
     */
    public function testDraftToDeclinedIsInvalid(): void
    {
        $this->assertFalse(
            condition: $this->service->isValidTransition(currentStatus: 'DRAFT', newStatus: 'DECLINED')
        );

    }//end testDraftToDeclinedIsInvalid()

    /**
     * Unknown status has no valid transitions.
     *
     * @return void
     */
    public function testUnknownStatusHasNoValidTransitions(): void
    {
        $this->assertFalse(
            condition: $this->service->isValidTransition(currentStatus: 'UNKNOWN_STATE', newStatus: 'PENDING')
        );

    }//end testUnknownStatusHasNoValidTransitions()
}//end class

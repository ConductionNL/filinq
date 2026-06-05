<?php

/**
 * Unit tests for ConsentUpdateHandler
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\ConsentUpdateHandler;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ConsentUpdateHandler
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConsentUpdateHandlerTest extends TestCase
{

    /**
     * Handler under test.
     *
     * @var ConsentUpdateHandler
     */
    private ConsentUpdateHandler $handler;

    /**
     * Mock logger.
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Mock DI container.
     *
     * @var ContainerInterface|MockObject
     */
    private ContainerInterface|MockObject $mockContainer;

    /**
     * Mock app manager.
     *
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $mockAppManager;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger     = $this->createMock(originalClassName: LoggerInterface::class);
        $this->mockContainer  = $this->createMock(originalClassName: ContainerInterface::class);
        $this->mockAppManager = $this->createMock(originalClassName: IAppManager::class);

        $this->handler = new ConsentUpdateHandler(
            logger: $this->mockLogger,
            container: $this->mockContainer,
            appManager: $this->mockAppManager
        );

    }//end setUp()


    /**
     * Test updateConsentStatus throws when OpenRegister not installed
     *
     * @return void
     */
    public function testUpdateConsentStatusThrowsWhenNotInstalled(): void
    {
        $this->expectException(exception: \Exception::class);
        $this->expectExceptionMessage(message: 'Failed to update consent status');

        $this->mockAppManager->method('getInstalledApps')
            ->willReturn([]);

        $this->handler->updateConsentStatus('uuid-1', 'reg-1', 'sch-1', ['status' => 'granted']);

    }//end testUpdateConsentStatusThrowsWhenNotInstalled()


    /**
     * Test getConsentsByDocument throws when OpenRegister not installed
     *
     * @return void
     */
    public function testGetConsentsByDocumentThrowsWhenNotInstalled(): void
    {
        $this->expectException(exception: \Exception::class);
        $this->expectExceptionMessage(message: 'Failed to get consents for document');

        $this->mockAppManager->method('getInstalledApps')
            ->willReturn([]);

        $this->handler->getConsentsByDocument('doc-1', 'reg-1', 'sch-1');

    }//end testGetConsentsByDocumentThrowsWhenNotInstalled()


    /**
     * Test handler can be instantiated
     *
     * @return void
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(expected: ConsentUpdateHandler::class, actual: $this->handler);

    }//end testCanBeInstantiated()


    /**
     * Invoke the private guardPolicyPreemptedTransition method.
     *
     * @param array<string, mixed> $existing The record's current data.
     * @param array<string, mixed> $data     The proposed update.
     *
     * @return void
     */
    private function invokeGuard(array $existing, array $data): void
    {
        $method = new \ReflectionMethod(ConsentUpdateHandler::class, 'guardPolicyPreemptedTransition');
        $method->invoke($this->handler, $existing, $data);

    }//end invokeGuard()


    /**
     * Guard passes when the record has no policyMatch.
     *
     * @return void
     */
    public function testGuardAllowsUpdateWithoutPolicyMatch(): void
    {
        $this->invokeGuard(
            existing: ['consentStatus' => 'pending'],
            data: ['consentStatus' => 'consent_given']
        );

        // No exception means the guard passed.
        $this->addToAssertionCount(1);

    }//end testGuardAllowsUpdateWithoutPolicyMatch()


    /**
     * Guard passes when a policy-pre-empted record changes neither
     * consentStatus nor publicationDecision.
     *
     * @return void
     */
    public function testGuardAllowsNonTransitionUpdateOnPreemptedRecord(): void
    {
        $this->invokeGuard(
            existing: [
                'policyMatch'         => 'rule-uuid-1',
                'consentStatus'       => 'anonymized',
                'publicationDecision' => 'anonymize',
            ],
            data: [
                'consentStatus'       => 'anonymized',
                'publicationDecision' => 'anonymize',
                'objectionReason'     => 'updated note',
            ]
        );

        $this->addToAssertionCount(1);

    }//end testGuardAllowsNonTransitionUpdateOnPreemptedRecord()


    /**
     * Guard rejects a consentStatus change on a policy-pre-empted record.
     *
     * @return void
     */
    public function testGuardRejectsConsentStatusChangeOnPreemptedRecord(): void
    {
        $this->expectException(exception: \InvalidArgumentException::class);
        $this->expectExceptionMessage(message: 'consentStatus "consent_given" rejected on policy-pre-empted record');

        $this->invokeGuard(
            existing: [
                'policyMatch'   => 'rule-uuid-1',
                'consentStatus' => 'anonymized',
            ],
            data: ['consentStatus' => 'consent_given']
        );

    }//end testGuardRejectsConsentStatusChangeOnPreemptedRecord()


    /**
     * Guard rejects a publicationDecision-only change on a policy-pre-empted
     * record — the bypass route the both-fields check exists to close.
     *
     * @return void
     */
    public function testGuardRejectsPublicationDecisionChangeOnPreemptedRecord(): void
    {
        $this->expectException(exception: \InvalidArgumentException::class);
        $this->expectExceptionMessage(message: 'publicationDecision "publish_with_consent" rejected on policy-pre-empted record');

        $this->invokeGuard(
            existing: [
                'policyMatch'         => 'rule-uuid-1',
                'consentStatus'       => 'anonymized',
                'publicationDecision' => 'anonymize',
            ],
            data: ['publicationDecision' => 'publish_with_consent']
        );

    }//end testGuardRejectsPublicationDecisionChangeOnPreemptedRecord()


}//end class

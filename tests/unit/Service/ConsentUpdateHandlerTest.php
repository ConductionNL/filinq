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
     * Invoke the private `guardPolicyPreemptedTransition` method via reflection.
     *
     * The guard is private because nothing outside the handler should
     * call it directly — but unit-testing its branch table is the
     * tightest way to lock down the PR #147 Thread B regression
     * (the matchKind-driven standing-consent carve-out).
     *
     * @param array<string, mixed> $existing Current record state.
     * @param array<string, mixed> $data     Proposed update.
     *
     * @return void
     *
     * @throws \InvalidArgumentException When the guard rejects the update.
     */
    private function invokeGuard(array $existing, array $data): void
    {
        $ref    = new \ReflectionClass($this->handler);
        $method = $ref->getMethod('guardPolicyPreemptedTransition');
        $method->setAccessible(true);
        $method->invoke($this->handler, $existing, $data);

    }//end invokeGuard()


    /**
     * Standing-consent carve-out fires when matchKind is the persisted
     * 'standing_consent' marker and the operator flips publicationDecision
     * only. This is the PR #147 Thread B regression case: pre-fix the
     * guard read $existing['matchKind'] which was never persisted by
     * ConsentService::buildConsentData, so the carve-out never fired
     * and the override was 400-locked.
     *
     * @return void
     */
    public function testStandingConsentCarveOutAllowsPublicationDecisionOverride(): void
    {
        $existing = [
            'policyMatch'         => 'sc-uuid-1',
            'matchKind'           => 'standing_consent',
            'consentStatus'       => 'consent_given',
            'publicationDecision' => 'publish_with_consent',
        ];
        $data     = [
            'publicationDecision' => 'anonymize',
        ];

        // Should NOT throw — the standing-consent carve-out permits the
        // operator override on publicationDecision while consentStatus is
        // preserved.
        $this->invokeGuard(existing: $existing, data: $data);

        // Reflection-invoke returns void; the absence of a thrown
        // exception is the assertion. Anchor it explicitly so PHPUnit
        // records a positive assertion.
        $this->addToAssertionCount(count: 1);

    }//end testStandingConsentCarveOutAllowsPublicationDecisionOverride()


    /**
     * Prohibition match locks both consentStatus AND publicationDecision —
     * the carve-out MUST NOT fire when matchKind is 'prohibition'.
     *
     * @return void
     */
    public function testProhibitionMatchRejectsPublicationDecisionOverride(): void
    {
        $existing = [
            'policyMatch'         => 'pro-uuid-1',
            'matchKind'           => 'prohibition',
            'consentStatus'       => 'anonymized',
            'publicationDecision' => 'anonymize',
        ];
        $data     = [
            'publicationDecision' => 'publish',
        ];

        $this->expectException(exception: \InvalidArgumentException::class);
        $this->invokeGuard(existing: $existing, data: $data);

    }//end testProhibitionMatchRejectsPublicationDecisionOverride()


    /**
     * Records without a `policyMatch` are not policy-pre-empted; the
     * guard returns early and the update flows through.
     *
     * @return void
     */
    public function testNoPolicyMatchAllowsArbitraryTransition(): void
    {
        $existing = [
            'policyMatch'         => null,
            'matchKind'           => null,
            'consentStatus'       => 'pending',
            'publicationDecision' => 'pending',
        ];
        $data     = [
            'consentStatus'       => 'consent_given',
            'publicationDecision' => 'publish',
        ];

        $this->invokeGuard(existing: $existing, data: $data);
        $this->addToAssertionCount(count: 1);

    }//end testNoPolicyMatchAllowsArbitraryTransition()


}//end class

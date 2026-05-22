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
     * Invoke the private `guardServerControlledFields` method via
     * reflection. This is the always-on immutability gate that runs
     * ahead of `guardPolicyPreemptedTransition` in `updateConsentStatus`
     * — see PR #147 sixth-pass review for why splitting the two guards
     * was necessary.
     *
     * @param array<string, mixed> $existing Current record state.
     * @param array<string, mixed> $data     Proposed update.
     *
     * @return void
     *
     * @throws \InvalidArgumentException When the guard rejects the update.
     */
    private function invokeServerControlledGuard(array $existing, array $data): void
    {
        $ref    = new \ReflectionClass($this->handler);
        $method = $ref->getMethod('guardServerControlledFields');
        $method->setAccessible(true);
        $method->invoke($this->handler, $existing, $data);

    }//end invokeServerControlledGuard()


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
     * consent-status guard returns early and the
     * consentStatus / publicationDecision update flows through. The
     * server-controlled-fields guard still applies — see
     * `testUnmatchedRecordRejectsServerControlledFieldInjection` for
     * the regression lock on the sixth-pass bypass.
     *
     * @return void
     */
    public function testNoPolicyMatchAllowsConsentStatusTransition(): void
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

    }//end testNoPolicyMatchAllowsConsentStatusTransition()


    /**
     * Regression lock for the PR #147 sixth-pass blocker
     * (discussion_r3289219546). A PATCH that fabricates
     * `policyMatch` + `matchKind` on a vanilla record (policyMatch:
     * null, default WOO-objection state) was slipping past the
     * `if ($existingMatch === null) return` early-return inside
     * `guardPolicyPreemptedTransition` — because the foreach
     * immutability check sat AFTER that early-return.
     *
     * Splitting the guards (server-controlled fields now run as
     * `guardServerControlledFields` ahead of the consent-status lock)
     * closes the hole on both the matched and unmatched branches.
     * This test exercises the unmatched branch end-to-end.
     *
     * @return void
     */
    public function testUnmatchedRecordRejectsServerControlledFieldInjection(): void
    {
        $existing = [
            'policyMatch'         => null,
            'matchKind'           => null,
            'consentStatus'       => 'pending',
            'publicationDecision' => 'pending',
            'objectionDeadline'   => '2026-06-20T00:00:00+00:00',
        ];
        $data     = [
            'policyMatch'         => 'sc-uuid-attacker-picked',
            'matchKind'           => 'standing_consent',
            'consentStatus'       => 'consent_given',
            'publicationDecision' => 'publish_with_consent',
            'objectionDeadline'   => null,
        ];

        $this->expectException(exception: \InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(regularExpression: '/(matchKind|policyMatch) is server-controlled/');
        $this->invokeServerControlledGuard(existing: $existing, data: $data);

    }//end testUnmatchedRecordRejectsServerControlledFieldInjection()


    /**
     * Symmetric to the above — a PATCH carrying ONLY `policyMatch`
     * (no consentStatus / publicationDecision) on a `policyMatch:
     * null` record must still be rejected. Without the split-guard
     * fix the foreach immutability check was unreachable on this
     * branch.
     *
     * @return void
     */
    public function testUnmatchedRecordRejectsPolicyMatchInjection(): void
    {
        $existing = [
            'policyMatch'         => null,
            'matchKind'           => null,
            'consentStatus'       => 'pending',
            'publicationDecision' => 'pending',
        ];
        $data     = [
            'policyMatch' => 'pro-uuid-attacker-picked',
        ];

        $this->expectException(exception: \InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(regularExpression: '/policyMatch is server-controlled/');
        $this->invokeServerControlledGuard(existing: $existing, data: $data);

    }//end testUnmatchedRecordRejectsPolicyMatchInjection()


    /**
     * Regression lock for the PR #147 fourth-pass blocker — step 1 of the
     * 2-step prohibition-lock bypass. A PUT carrying ONLY `matchKind`
     * (no consentStatus / publicationDecision change) would otherwise
     * slip past the both-fields-false early-return and the downstream
     * `array_merge` would corrupt the server-controlled field. The guard
     * must reject the mutation before any merge can happen, regardless
     * of whether other update fields are present.
     *
     * @return void
     */
    public function testProhibitionMatchKindMutationRejected(): void
    {
        $existing = [
            'policyMatch'         => 'pro-uuid-1',
            'matchKind'           => 'prohibition',
            'consentStatus'       => 'anonymized',
            'publicationDecision' => 'anonymize',
        ];
        $data     = [
            'matchKind' => 'standing_consent',
        ];

        $this->expectException(exception: \InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(regularExpression: '/matchKind is server-controlled/');
        $this->invokeServerControlledGuard(existing: $existing, data: $data);

    }//end testProhibitionMatchKindMutationRejected()


    /**
     * Regression lock for the related shape of the same bypass — swapping
     * `policyMatch` to a different non-empty UUID. The original
     * clearing-only guard (rejected only `policyMatch: null`) left UUID
     * swaps open; the broader server-controlled check rejects all
     * non-equal proposals.
     *
     * @return void
     */
    public function testProhibitionPolicyMatchUuidSwapRejected(): void
    {
        $existing = [
            'policyMatch'         => 'pro-uuid-1',
            'matchKind'           => 'prohibition',
            'consentStatus'       => 'anonymized',
            'publicationDecision' => 'anonymize',
        ];
        $data     = [
            'policyMatch' => 'pro-uuid-2',
        ];

        $this->expectException(exception: \InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(regularExpression: '/policyMatch is server-controlled/');
        $this->invokeServerControlledGuard(existing: $existing, data: $data);

    }//end testProhibitionPolicyMatchUuidSwapRejected()


    /**
     * A PUT carrying matchKind / policyMatch values EQUAL to the existing
     * values is a no-op for those fields. The guard must NOT reject this
     * — otherwise idempotent clients that re-send the full record state
     * on every update would break. Only mutations to a different value
     * are rejected.
     *
     * @return void
     */
    public function testEqualServerControlledValuesAreAllowed(): void
    {
        $existing = [
            'policyMatch'         => 'pro-uuid-1',
            'matchKind'           => 'prohibition',
            'consentStatus'       => 'anonymized',
            'publicationDecision' => 'anonymize',
        ];
        $data     = [
            'policyMatch' => 'pro-uuid-1',
            'matchKind'   => 'prohibition',
        ];

        $this->invokeServerControlledGuard(existing: $existing, data: $data);
        $this->addToAssertionCount(count: 1);

    }//end testEqualServerControlledValuesAreAllowed()


}//end class

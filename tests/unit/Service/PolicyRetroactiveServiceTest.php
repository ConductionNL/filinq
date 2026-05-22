<?php

/**
 * Unit tests for PolicyRetroactiveService
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
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\PolicyMatchService;
use OCA\DocuDesk\Service\PolicyRetroactiveService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;


/**
 * Local stub mirroring the subset of OpenRegister's ObjectService that
 * `PolicyRetroactiveService` invokes. The real class is not autoloadable
 * from the docudesk test context (sister-app), so we expose a stub with
 * the same method signatures so PHPUnit's `createMock` can honour the
 * named-parameter call sites (`findAll(config: ...)`, `saveObject(object:
 * ..., uuid: ..., _rbac: false)`).
 *
 * This stub is test-only scaffolding — production code talks to the real
 * ObjectService via DI.
 *
 * @internal
 */
class FakeOpenRegisterObjectService
{
    /**
     * Mirror of ObjectService::findAll signature.
     *
     * @param array<string, mixed> $config        Filter/pagination config.
     * @param bool                 $_rbac         RBAC flag (matches OR's underscore prefix).
     * @param bool                 $_multitenancy Multitenancy flag.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(array $config=[], bool $_rbac=true, bool $_multitenancy=true): array
    {
        return [];

    }//end findAll()


    /**
     * Mirror of ObjectService::saveObject signature (subset used by the
     * retroactive sweep).
     *
     * @param array<string, mixed> $object        Object data to persist.
     * @param string               $register      Register slug.
     * @param string               $schema        Schema slug.
     * @param string|null          $uuid          Optional UUID for upserts.
     * @param bool                 $_rbac         RBAC flag.
     * @param bool                 $_multitenancy Multitenancy flag.
     *
     * @return object
     */
    public function saveObject(
        array $object,
        string $register,
        string $schema,
        ?string $uuid=null,
        bool $_rbac=true,
        bool $_multitenancy=true
    ): object {
        return new \stdClass();

    }//end saveObject()
}//end class FakeOpenRegisterObjectService

/**
 * Cover the contract of PolicyRetroactiveService.
 *
 * Spec §5 of entity-publication-policies. We exercise the public API directly
 * with stubs — the OpenRegister ObjectService is mocked.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PolicyRetroactiveServiceTest extends TestCase
{

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
     * Mock policy matcher.
     *
     * @var PolicyMatchService|MockObject
     */
    private PolicyMatchService|MockObject $mockPolicyMatcher;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger        = $this->createMock(originalClassName: LoggerInterface::class);
        $this->mockContainer     = $this->createMock(originalClassName: ContainerInterface::class);
        $this->mockPolicyMatcher = $this->createMock(originalClassName: PolicyMatchService::class);

    }//end setUp()


    /**
     * Build a fresh service instance with the configured mocks.
     *
     * @return PolicyRetroactiveService
     */
    private function makeService(): PolicyRetroactiveService
    {
        return new PolicyRetroactiveService(
            logger: $this->mockLogger,
            container: $this->mockContainer,
            policyMatcher: $this->mockPolicyMatcher
        );

    }//end makeService()


    /**
     * Inactive prohibitions MUST short-circuit and resolve nothing.
     *
     * @return void
     */
    public function testInactiveProhibitionResolvesNothing(): void
    {
        $this->mockContainer->expects($this->never())->method('get');

        $service = $this->makeService();
        $result  = $service->applyProhibitionMutation(
                prohibition: [
                    'active'     => false,
                    'matchRules' => [['type' => 'exact', 'value' => 'Jan Janssen']],
                    '@self'      => ['id' => 'p-1'],
                    'entityType' => 'PERSON',
                ]
                );

        $this->assertSame(expected: 0, actual: $result);

    }//end testInactiveProhibitionResolvesNothing()


    /**
     * Time-bound prohibitions outside their validity window MUST not sweep.
     *
     * @return void
     */
    public function testFutureProhibitionResolvesNothing(): void
    {
        $this->mockContainer->expects($this->never())->method('get');

        $service = $this->makeService();
        $result  = $service->applyProhibitionMutation(
                prohibition: [
                    'active'     => true,
                    'validFrom'  => '2099-01-01T00:00:00Z',
                    'matchRules' => [['type' => 'exact', 'value' => 'Jan Janssen']],
                    '@self'      => ['id' => 'p-1'],
                    'entityType' => 'PERSON',
                ]
                );

        $this->assertSame(expected: 0, actual: $result);

    }//end testFutureProhibitionResolvesNothing()


    /**
     * Prohibitions without a UUID anchor MUST be skipped with a warning.
     *
     * @return void
     */
    public function testProhibitionWithoutUuidLogsAndReturnsZero(): void
    {
        $this->mockLogger->expects($this->once())->method('warning');

        $service = $this->makeService();
        $result  = $service->applyProhibitionMutation(
                prohibition: [
                    'active'     => true,
                    'matchRules' => [['type' => 'exact', 'value' => 'Jan Janssen']],
                    'entityType' => 'PERSON',
                ]
                );

        $this->assertSame(expected: 0, actual: $result);

    }//end testProhibitionWithoutUuidLogsAndReturnsZero()


    /**
     * Standing-consent mutation MUST invalidate the matcher cache and do nothing else.
     *
     * @return void
     */
    public function testStandingConsentMutationOnlyInvalidatesCache(): void
    {
        $this->mockPolicyMatcher->expects($this->once())->method('invalidateCache');
        $this->mockContainer->expects($this->never())->method('get');

        $this->makeService()->applyStandingConsentMutation();

    }//end testStandingConsentMutationOnlyInvalidatesCache()


    /**
     * Rule removal MUST invalidate the matcher cache and do nothing else.
     *
     * @return void
     */
    public function testRuleRemovalOnlyInvalidatesCache(): void
    {
        $this->mockPolicyMatcher->expects($this->once())->method('invalidateCache');
        $this->mockContainer->expects($this->never())->method('get');

        $this->makeService()->applyRuleRemoval();

    }//end testRuleRemovalOnlyInvalidatesCache()


    /**
     * Wire the mock OpenRegister ObjectService into the DI container.
     *
     * @param array<int, array<string, mixed>> $candidates findAll() result rows for in-flight document records.
     * @param object|null                      $saveSpy   Optional spy collecting saveObject(object: ...) payloads.
     *
     * @return object The configured ObjectService mock.
     */
    private function configureObjectService(array $candidates, ?object $saveSpy=null): object
    {
        $objectService = $this->createMock(originalClassName: FakeOpenRegisterObjectService::class);
        $objectService->method('findAll')->willReturn($candidates);

        if ($saveSpy !== null) {
            $objectService->method('saveObject')->willReturnCallback(
                function (array $object, string $register, string $schema, ?string $uuid=null, bool $_rbac=true, bool $_multitenancy=true) use ($saveSpy) {
                    $saveSpy->payloads[] = ['object' => $object, 'register' => $register, 'schema' => $schema, 'uuid' => $uuid];
                    return new \stdClass();
                }
            );
        }

        $this->mockContainer
            ->method('get')
            ->with('OCA\OpenRegister\Service\ObjectService')
            ->willReturn($objectService);

        return $objectService;

    }//end configureObjectService()


    /**
     * Happy-path: an active prohibition force-resolves an in-flight WOO
     * record whose entityText matches a rule. Verifies the save payload
     * carries the canonical force-resolve state — including the
     * matchKind discriminator added in the sixth-pass review fix.
     *
     * @return void
     */
    public function testProhibitionForceResolvesMatchingInFlightRecord(): void
    {
        $candidates = [
            [
                '@self'         => ['id' => 'consent-uuid-1'],
                'entityText'    => 'Jan Janssen',
                'entityType'    => 'PERSON',
                'scope'         => 'document',
                'consentStatus' => 'pending',
                'policyMatch'   => null,
                'matchKind'     => null,
            ],
        ];
        $saveSpy = new \stdClass();
        $saveSpy->payloads = [];
        $this->configureObjectService(candidates: $candidates, saveSpy: $saveSpy);

        $this->mockPolicyMatcher->expects($this->once())
            ->method('entityMatchesAnyRule')
            ->willReturn(true);
        $this->mockPolicyMatcher->expects($this->once())->method('invalidateCache');

        $result = $this->makeService()->applyProhibitionMutation(
                prohibition: [
                    'active'     => true,
                    'matchRules' => [['type' => 'exact', 'value' => 'Jan Janssen']],
                    '@self'      => ['id' => 'pro-uuid-1'],
                    'entityType' => 'PERSON',
                ]
                );

        $this->assertSame(expected: 1, actual: $result);
        $this->assertCount(expectedCount: 1, haystack: $saveSpy->payloads);
        $payload = $saveSpy->payloads[0]['object'];
        $this->assertSame(expected: 'anonymized', actual: $payload['consentStatus']);
        $this->assertSame(expected: 'skipped', actual: $payload['notificationStatus']);
        $this->assertSame(expected: 'anonymize', actual: $payload['publicationDecision']);
        $this->assertNull(actual: $payload['objectionDeadline']);
        $this->assertSame(expected: 'pro-uuid-1', actual: $payload['policyMatch']);
        $this->assertSame(
            expected: PolicyMatchService::KIND_PROHIBITION,
            actual: $payload['matchKind'],
            message: 'forceResolveToAnonymized must write matchKind for parity with ConsentService::buildConsentData'
        );

    }//end testProhibitionForceResolvesMatchingInFlightRecord()


    /**
     * Audit-trail timestamps must survive the force-resolve write —
     * `notificationSentAt` and `objectionReceivedAt` are referenced by
     * downstream reporting and MUST NOT be reset by the sweep.
     *
     * @return void
     */
    public function testProhibitionPreservesAuditTimestamps(): void
    {
        $candidates = [
            [
                '@self'                 => ['id' => 'consent-uuid-1'],
                'entityText'            => 'Jan Janssen',
                'entityType'            => 'PERSON',
                'scope'                 => 'document',
                'consentStatus'         => 'pending',
                'policyMatch'           => null,
                'matchKind'             => null,
                'notificationSentAt'    => '2026-03-01T12:00:00+00:00',
                'objectionReceivedAt'   => '2026-03-15T09:00:00+00:00',
            ],
        ];
        $saveSpy = new \stdClass();
        $saveSpy->payloads = [];
        $this->configureObjectService(candidates: $candidates, saveSpy: $saveSpy);

        $this->mockPolicyMatcher->method('entityMatchesAnyRule')->willReturn(true);
        $this->mockPolicyMatcher->method('invalidateCache');

        $this->makeService()->applyProhibitionMutation(
            prohibition: [
                'active'     => true,
                'matchRules' => [['type' => 'exact', 'value' => 'Jan Janssen']],
                '@self'      => ['id' => 'pro-uuid-1'],
                'entityType' => 'PERSON',
            ]
        );

        $payload = $saveSpy->payloads[0]['object'];
        $this->assertSame(
            expected: '2026-03-01T12:00:00+00:00',
            actual: $payload['notificationSentAt']
        );
        $this->assertSame(
            expected: '2026-03-15T09:00:00+00:00',
            actual: $payload['objectionReceivedAt']
        );

    }//end testProhibitionPreservesAuditTimestamps()


    /**
     * Records already locked by a prohibition (`matchKind: 'prohibition'`)
     * MUST be skipped — the sweep must be idempotent so the prohibition
     * count stays correct across re-runs.
     *
     * @return void
     */
    public function testProhibitionSkipsAlreadyProhibitedRecords(): void
    {
        $candidates = [
            [
                '@self'         => ['id' => 'consent-uuid-1'],
                'entityText'    => 'Jan Janssen',
                'entityType'    => 'PERSON',
                'scope'         => 'document',
                'consentStatus' => 'anonymized',
                'policyMatch'   => 'pro-uuid-existing',
                'matchKind'     => PolicyMatchService::KIND_PROHIBITION,
            ],
        ];
        $saveSpy = new \stdClass();
        $saveSpy->payloads = [];
        $this->configureObjectService(candidates: $candidates, saveSpy: $saveSpy);

        $this->mockPolicyMatcher->expects($this->never())->method('entityMatchesAnyRule');
        $this->mockPolicyMatcher->method('invalidateCache');

        $result = $this->makeService()->applyProhibitionMutation(
                prohibition: [
                    'active'     => true,
                    'matchRules' => [['type' => 'exact', 'value' => 'Jan Janssen']],
                    '@self'      => ['id' => 'pro-uuid-1'],
                    'entityType' => 'PERSON',
                ]
                );

        $this->assertSame(expected: 0, actual: $result);
        $this->assertCount(expectedCount: 0, haystack: $saveSpy->payloads);

    }//end testProhibitionSkipsAlreadyProhibitedRecords()


    /**
     * Regression lock for the PR #147 sixth-pass blocker
     * (discussion_r3289224924). Records matched at detection-time by a
     * standing consent (`matchKind: 'standing_consent'`,
     * `consentStatus: 'consent_given'`) MUST be force-resolved when a
     * later-arriving prohibition matches the same entity — otherwise the
     * canonical "prohibitions win" rule is silently inverted on the
     * retroactive code path. Pre-fix the blanket `policyMatch !== null`
     * filter excluded these records; post-fix only `matchKind ===
     * KIND_PROHIBITION` is excluded.
     *
     * @return void
     */
    public function testProhibitionOverridesStandingConsent(): void
    {
        $candidates = [
            [
                '@self'         => ['id' => 'consent-uuid-1'],
                'entityText'    => 'Jan Janssen',
                'entityType'    => 'PERSON',
                'scope'         => 'document',
                'consentStatus' => 'consent_given',
                'policyMatch'   => 'sc-uuid-existing',
                'matchKind'     => PolicyMatchService::KIND_STANDING_CONSENT,
            ],
        ];
        $saveSpy = new \stdClass();
        $saveSpy->payloads = [];
        $this->configureObjectService(candidates: $candidates, saveSpy: $saveSpy);

        $this->mockPolicyMatcher->expects($this->once())
            ->method('entityMatchesAnyRule')
            ->willReturn(true);
        $this->mockPolicyMatcher->method('invalidateCache');

        $result = $this->makeService()->applyProhibitionMutation(
                prohibition: [
                    'active'     => true,
                    'matchRules' => [['type' => 'exact', 'value' => 'Jan Janssen']],
                    '@self'      => ['id' => 'pro-uuid-court-order'],
                    'entityType' => 'PERSON',
                ]
                );

        $this->assertSame(expected: 1, actual: $result);
        $payload = $saveSpy->payloads[0]['object'];
        $this->assertSame(
            expected: 'pro-uuid-court-order',
            actual: $payload['policyMatch'],
            message: 'standing-consent policyMatch must be overwritten by the new prohibition'
        );
        $this->assertSame(
            expected: PolicyMatchService::KIND_PROHIBITION,
            actual: $payload['matchKind'],
            message: 'matchKind must flip from standing_consent to prohibition'
        );
        $this->assertSame(expected: 'anonymized', actual: $payload['consentStatus']);

    }//end testProhibitionOverridesStandingConsent()


    /**
     * A prohibition with `entityType: "OTHER"` is a wildcard that should
     * sweep across records of any entity type, not just OTHER.
     *
     * @return void
     */
    public function testProhibitionResolvesAcrossEntityTypeOTHER(): void
    {
        $candidates = [
            [
                '@self'         => ['id' => 'consent-uuid-org'],
                'entityText'    => 'Acme B.V.',
                'entityType'    => 'ORGANIZATION',
                'scope'         => 'document',
                'consentStatus' => 'pending',
                'policyMatch'   => null,
                'matchKind'     => null,
            ],
            [
                '@self'         => ['id' => 'consent-uuid-person'],
                'entityText'    => 'Jan Janssen',
                'entityType'    => 'PERSON',
                'scope'         => 'document',
                'consentStatus' => 'pending',
                'policyMatch'   => null,
                'matchKind'     => null,
            ],
        ];
        $saveSpy = new \stdClass();
        $saveSpy->payloads = [];
        $this->configureObjectService(candidates: $candidates, saveSpy: $saveSpy);

        $this->mockPolicyMatcher->method('entityMatchesAnyRule')->willReturn(true);
        $this->mockPolicyMatcher->method('invalidateCache');

        $result = $this->makeService()->applyProhibitionMutation(
                prohibition: [
                    'active'     => true,
                    'matchRules' => [['type' => 'normalized', 'value' => 'sensitive token']],
                    '@self'      => ['id' => 'pro-uuid-wildcard'],
                    'entityType' => 'OTHER',
                ]
                );

        $this->assertSame(expected: 2, actual: $result);
        $this->assertCount(expectedCount: 2, haystack: $saveSpy->payloads);

    }//end testProhibitionResolvesAcrossEntityTypeOTHER()


}//end class

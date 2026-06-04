<?php

/**
 * Unit tests for ConsentService
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
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-6
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Exception\PolicyRejectedException;
use OCA\DocuDesk\Service\ConsentNotesHelper;
use OCA\DocuDesk\Service\ConsentService;
use OCA\DocuDesk\Service\ConsentUpdateHandler;
use OCA\DocuDesk\Service\ObjectionDeadlineChecker;
use OCA\DocuDesk\Service\PolicyMatchService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ConsentService
 *
 * Covers: delegation wrappers, idempotency on (documentId, entityKey),
 * workflow-state preservation, policyMatch re-evaluation, prohibition
 * rejection, and the validatePublicationConsentData scope rules.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-6
 */
class ConsentServiceTest extends TestCase
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
     * Mock app manager (OpenRegister installed by default).
     *
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $mockAppManager;

    /**
     * Mock objection-deadline checker.
     *
     * @var ObjectionDeadlineChecker|MockObject
     */
    private ObjectionDeadlineChecker|MockObject $mockDeadlineChecker;

    /**
     * Mock update handler.
     *
     * @var ConsentUpdateHandler|MockObject
     */
    private ConsentUpdateHandler|MockObject $mockUpdateHandler;

    /**
     * Real notes helper (no external deps).
     *
     * @var ConsentNotesHelper
     */
    private ConsentNotesHelper $notesHelper;

    /**
     * Set up shared mocks.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger          = $this->createMock(originalClassName: LoggerInterface::class);
        $this->mockContainer       = $this->createMock(originalClassName: ContainerInterface::class);
        $this->mockAppManager      = $this->createMock(originalClassName: IAppManager::class);
        $this->mockDeadlineChecker = $this->createMock(originalClassName: ObjectionDeadlineChecker::class);
        $this->mockUpdateHandler   = $this->createMock(originalClassName: ConsentUpdateHandler::class);
        $this->notesHelper         = new ConsentNotesHelper();

        // Default: OpenRegister is installed.
        $this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);

        // Default deadline: 28 days from now.
        $this->mockDeadlineChecker->method('calculateDeadline')->willReturn(new \DateTime('+28 days'));

    }//end setUp()

    // ------------------------------------------------------------------
    // Builder helper
    // ------------------------------------------------------------------

    /**
     * Build a ConsentService with specific ObjectService and policyMatcher behavior.
     *
     * @param array<mixed>              $searchResults What searchObjects() returns.
     * @param array<string, mixed>|null $policyResult  What PolicyMatchService::match() returns.
     * @param IAppManager|null          $appManager    Override app manager.
     *
     * @return array{service: ConsentService, capturedSaveArg: array<string, mixed>|null}
     */
    private function buildService(
        array $searchResults=[],
        ?array $policyResult=null,
        ?IAppManager $appManager=null
    ): array {
        $capturedSaveArg = null;

        $objectService = $this->createMock(originalClassName: \OCA\OpenRegister\Service\ObjectService::class);
        $objectService->method('searchObjects')->willReturn($searchResults);
        $objectService->method('saveObject')->willReturnCallback(
            function (array $object) use (&$capturedSaveArg): object {
                $capturedSaveArg = $object;
                return $this->buildSavedObject(data: $object);
            }
        );

        $container = $this->createMock(originalClassName: ContainerInterface::class);
        $container->method('get')
            ->with('OCA\OpenRegister\Service\ObjectService')
            ->willReturn($objectService);

        $policyMatcher = $this->createMock(originalClassName: PolicyMatchService::class);
        $policyMatcher->method('match')->willReturn($policyResult);

        $service = new ConsentService(
            logger: $this->mockLogger,
            container: $container,
            appManager: $appManager ?? $this->mockAppManager,
            deadlineChecker: $this->mockDeadlineChecker,
            updateHandler: $this->mockUpdateHandler,
            policyMatcher: $policyMatcher,
            notesHelper: $this->notesHelper
        );

        return ['service' => $service, 'capturedSaveArg' => &$capturedSaveArg];

    }//end buildService()

    /**
     * Build a minimal saved-object stub whose getObject() returns $data.
     *
     * @param array<string, mixed> $data Object payload.
     *
     * @return object
     */
    private function buildSavedObject(array $data): object
    {
        return new class($data) {
            /**
             * Construct with data.
             *
             * @param array<string, mixed> $data The data.
             */
            public function __construct(private readonly array $data)
            {
            }//end __construct()

            /**
             * Return the data.
             *
             * @return array<string, mixed>
             */
            public function getObject(): array
            {
                return $this->data;
            }//end getObject()
        };

    }//end buildSavedObject()

    // ------------------------------------------------------------------
    // Delegation wrappers
    // ------------------------------------------------------------------

    /**
     * Test updateConsentStatus delegates to handler
     *
     * @return void
     */
    public function testUpdateConsentStatusDelegates(): void
    {
        $expected      = ['consentStatus' => 'granted'];
        $policyMatcher = $this->createMock(originalClassName: PolicyMatchService::class);
        $service       = new ConsentService(
            logger: $this->mockLogger,
            container: $this->mockContainer,
            appManager: $this->mockAppManager,
            deadlineChecker: $this->mockDeadlineChecker,
            updateHandler: $this->mockUpdateHandler,
            policyMatcher: $policyMatcher,
            notesHelper: $this->notesHelper
        );

        $this->mockUpdateHandler->method('updateConsentStatus')
            ->with('uuid-1', 'reg-1', 'sch-1', ['consentStatus' => 'granted'])
            ->willReturn($expected);

        $result = $service->updateConsentStatus(consentId: 'uuid-1', register: 'reg-1', schema: 'sch-1', data: ['consentStatus' => 'granted']);
        $this->assertEquals(expected: $expected, actual: $result);

    }//end testUpdateConsentStatusDelegates()

    /**
     * Test checkObjectionDeadline delegates to checker
     *
     * @return void
     */
    public function testCheckObjectionDeadlineDelegates(): void
    {
        $policyMatcher = $this->createMock(originalClassName: PolicyMatchService::class);
        $service       = new ConsentService(
            logger: $this->mockLogger,
            container: $this->mockContainer,
            appManager: $this->mockAppManager,
            deadlineChecker: $this->mockDeadlineChecker,
            updateHandler: $this->mockUpdateHandler,
            policyMatcher: $policyMatcher,
            notesHelper: $this->notesHelper
        );

        $this->mockDeadlineChecker->method('checkObjectionDeadline')
            ->with('uuid-1', 'reg-1', 'sch-1')
            ->willReturn(true);

        $result = $service->checkObjectionDeadline(consentId: 'uuid-1', register: 'reg-1', schema: 'sch-1');
        $this->assertTrue(condition: $result);

    }//end testCheckObjectionDeadlineDelegates()

    /**
     * Test getConsentsByDocument delegates to handler
     *
     * @return void
     */
    public function testGetConsentsByDocumentDelegates(): void
    {
        $expected      = [['documentId' => 'doc-1', 'consentStatus' => 'pending']];
        $policyMatcher = $this->createMock(originalClassName: PolicyMatchService::class);
        $service       = new ConsentService(
            logger: $this->mockLogger,
            container: $this->mockContainer,
            appManager: $this->mockAppManager,
            deadlineChecker: $this->mockDeadlineChecker,
            updateHandler: $this->mockUpdateHandler,
            policyMatcher: $policyMatcher,
            notesHelper: $this->notesHelper
        );

        $this->mockUpdateHandler->method('getConsentsByDocument')
            ->with('doc-1', 'reg-1', 'sch-1')
            ->willReturn($expected);

        $result = $service->getConsentsByDocument(documentId: 'doc-1', register: 'reg-1', schema: 'sch-1');
        $this->assertEquals(expected: $expected, actual: $result);

    }//end testGetConsentsByDocumentDelegates()

    /**
     * Test createConsentRequest throws when OpenRegister not installed
     *
     * @return void
     */
    public function testCreateConsentRequestThrowsWhenNotInstalled(): void
    {
        $this->expectException(exception: \Exception::class);
        $this->expectExceptionMessage(message: 'Failed to create consent request');

        // Fresh app manager that reports OR not installed.
        $noOrAppManager = $this->createMock(originalClassName: IAppManager::class);
        $noOrAppManager->method('getInstalledApps')->willReturn([]);

        $policyMatcher = $this->createMock(originalClassName: PolicyMatchService::class);
        $service       = new ConsentService(
            logger: $this->mockLogger,
            container: $this->mockContainer,
            appManager: $noOrAppManager,
            deadlineChecker: $this->mockDeadlineChecker,
            updateHandler: $this->mockUpdateHandler,
            policyMatcher: $policyMatcher,
            notesHelper: $this->notesHelper
        );

        $service->createConsentRequest(
            documentId: 'doc-1',
            entityType: 'PERSON',
            entityText: 'John',
            register: 'reg-1',
            schema: 'sch-1'
        );

    }//end testCreateConsentRequestThrowsWhenNotInstalled()

    // ------------------------------------------------------------------
    // Idempotency — Task 6
    // ------------------------------------------------------------------

    /**
     * New (documentId, entityKey) → creates a new record, wasUpdated=false.
     *
     * @return void
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-6
     */
    public function testCreateConsentRequestCreatesNewRecordWhenNoneExists(): void
    {
        $bag     = $this->buildService(searchResults: []);
        $service = $bag['service'];

        $result = $service->createConsentRequest(
            documentId: 'doc-1',
            entityType: 'PERSON',
            entityText: 'Anneke Jansen',
            register: 'reg-1',
            schema: 'sch-1',
            extra: ['entityKey' => 'key-A']
        );

        $this->assertFalse(condition: $result['wasUpdated'], message: 'New record should have wasUpdated=false.');
        $this->assertSame(expected: 'doc-1', actual: $result['documentId']);

    }//end testCreateConsentRequestCreatesNewRecordWhenNoneExists()

    /**
     * Existing (documentId, entityKey) → updates the record, wasUpdated=true.
     *
     * @return void
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-6
     */
    public function testCreateConsentRequestUpdatesExistingRecord(): void
    {
        $existingData = [
            'documentId'          => 'doc-1',
            'entityKey'           => 'key-A',
            'scope'               => 'document',
            'legalBasis'          => 'Old basis',
            'notificationStatus'  => 'sent',
            'notificationSentAt'  => '2026-04-01T10:00:00Z',
            'consentStatus'       => 'pending',
            'publicationDecision' => 'pending',
            'objectionDeadline'   => '2026-05-01T10:00:00Z',
        ];

        $bag     = $this->buildService(searchResults: [$this->buildSavedObject(data: $existingData)]);
        $service = $bag['service'];

        $result = $service->createConsentRequest(
            documentId: 'doc-1',
            entityType: 'PERSON',
            entityText: 'Anneke Jansen',
            register: 'reg-1',
            schema: 'sch-1',
            extra: ['entityKey' => 'key-A', 'publicationBases' => ['New basis']]
        );

        $this->assertTrue(condition: $result['wasUpdated'], message: 'Re-submit should have wasUpdated=true.');

    }//end testCreateConsentRequestUpdatesExistingRecord()

    /**
     * Workflow state (objection fields) is preserved across re-events.
     *
     * @return void
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-6
     */
    public function testCreateConsentRequestPreservesWorkflowStateOnUpdate(): void
    {
        $existingData = [
            'documentId'          => 'doc-1',
            'entityKey'           => 'key-B',
            'scope'               => 'document',
            'consentStatus'       => 'objection_received',
            'objectionReceivedAt' => '2026-04-15T12:00:00Z',
            'objectionReason'     => 'Privacy concern',
            'notificationStatus'  => 'sent',
            'notificationSentAt'  => '2026-03-20T08:00:00Z',
            'objectionDeadline'   => '2026-04-17T08:00:00Z',
            'publicationDecision' => 'pending',
        ];

        $bag = $this->buildService(searchResults: [$this->buildSavedObject(data: $existingData)]);
        $capturedSaveArg = &$bag['capturedSaveArg'];
        $service         = $bag['service'];

        $service->createConsentRequest(
            documentId: 'doc-1',
            entityType: 'PERSON',
            entityText: 'Karin de Vries',
            register: 'reg-1',
            schema: 'sch-1',
            extra: ['entityKey' => 'key-B']
        );

        $this->assertSame(
            expected: 'objection_received',
            actual: $capturedSaveArg['consentStatus'] ?? null,
            message: 'consentStatus must not be reset on re-submit.'
        );
        $this->assertSame(
            expected: '2026-04-15T12:00:00Z',
            actual: $capturedSaveArg['objectionReceivedAt'] ?? null,
            message: 'objectionReceivedAt must not be reset on re-submit.'
        );
        $this->assertSame(
            expected: '2026-04-17T08:00:00Z',
            actual: $capturedSaveArg['objectionDeadline'] ?? null,
            message: 'objectionDeadline (WOO timer) must not be reset on re-submit.'
        );

    }//end testCreateConsentRequestPreservesWorkflowStateOnUpdate()

    /**
     * PolicyMatch is SET on update when previously null and a standing consent now applies.
     *
     * @return void
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-6
     */
    public function testPolicyMatchIsSetOnUpdateWhenNewlyApplicable(): void
    {
        $existingData = [
            'documentId'    => 'doc-1',
            'entityKey'     => 'key-C',
            'scope'         => 'document',
            'policyMatch'   => null,
            'consentStatus' => 'pending',
        ];

        $standingConsentResult = [
            'uuid'        => 'rule-uuid-1',
            'kind'        => PolicyMatchService::KIND_STANDING_CONSENT,
            'entityType'  => 'PERSON',
            'primaryName' => 'Standing consent rule',
        ];

        $bag = $this->buildService(
            searchResults: [$this->buildSavedObject(data: $existingData)],
            policyResult: $standingConsentResult
        );
        $capturedSaveArg = &$bag['capturedSaveArg'];
        $service         = $bag['service'];

        $service->createConsentRequest(
            documentId: 'doc-1',
            entityType: 'PERSON',
            entityText: 'Kees Bakker',
            register: 'reg-1',
            schema: 'sch-1',
            extra: ['entityKey' => 'key-C']
        );

        $this->assertSame(
            expected: 'rule-uuid-1',
            actual: $capturedSaveArg['policyMatch'] ?? null,
            message: 'policyMatch should be SET when previously null and standing consent matches.'
        );

    }//end testPolicyMatchIsSetOnUpdateWhenNewlyApplicable()

    /**
     * PolicyMatch is NOT cleared on update when no longer matching.
     *
     * @return void
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-6
     */
    public function testPolicyMatchIsNotClearedOnUpdateWhenNoLongerMatching(): void
    {
        $existingData = [
            'documentId'    => 'doc-1',
            'entityKey'     => 'key-D',
            'scope'         => 'document',
            'policyMatch'   => 'some-existing-uuid',
            'consentStatus' => 'consent_given',
        ];

        // No policy match now (rule deactivated).
        $bag = $this->buildService(
            searchResults: [$this->buildSavedObject(data: $existingData)],
            policyResult: null
        );
        $capturedSaveArg = &$bag['capturedSaveArg'];
        $service         = $bag['service'];

        $service->createConsentRequest(
            documentId: 'doc-1',
            entityType: 'PERSON',
            entityText: 'Pieter Smit',
            register: 'reg-1',
            schema: 'sch-1',
            extra: ['entityKey' => 'key-D']
        );

        $this->assertSame(
            expected: 'some-existing-uuid',
            actual: $capturedSaveArg['policyMatch'] ?? null,
            message: 'policyMatch must not be cleared when the rule no longer matches.'
        );

    }//end testPolicyMatchIsNotClearedOnUpdateWhenNoLongerMatching()

    /**
     * Prohibition match throws PolicyRejectedException; no record is created or updated.
     *
     * @return void
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-6
     */
    public function testProhibitionMatchThrowsPolicyRejectedException(): void
    {
        $this->expectException(exception: PolicyRejectedException::class);

        $prohibitionResult = [
            'uuid'        => 'prohibition-uuid-1',
            'kind'        => PolicyMatchService::KIND_PROHIBITION,
            'entityType'  => 'PERSON',
            'primaryName' => 'Beschermde Getuige A',
        ];

        $bag     = $this->buildService(policyResult: $prohibitionResult);
        $service = $bag['service'];

        $service->createConsentRequest(
            documentId: 'doc-1',
            entityType: 'PERSON',
            entityText: 'Beschermde Getuige A',
            register: 'reg-1',
            schema: 'sch-1'
        );

    }//end testProhibitionMatchThrowsPolicyRejectedException()

    /**
     * PolicyRejectedException carries rule UUID and name.
     *
     * @return void
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-6
     */
    public function testPolicyRejectedExceptionCarriesRuleDetails(): void
    {
        $prohibitionResult = [
            'uuid'        => 'rule-uuid-99',
            'kind'        => PolicyMatchService::KIND_PROHIBITION,
            'entityType'  => 'PERSON',
            'primaryName' => 'Witness Protection Rule',
        ];

        $bag     = $this->buildService(policyResult: $prohibitionResult);
        $service = $bag['service'];

        try {
            $service->createConsentRequest(
                documentId: 'doc-1',
                entityType: 'PERSON',
                entityText: 'Protected Person',
                register: 'reg-1',
                schema: 'sch-1'
            );
            $this->fail(message: 'Expected PolicyRejectedException was not thrown.');
        } catch (PolicyRejectedException $e) {
            $this->assertSame(expected: 'rule-uuid-99', actual: $e->getRuleUuid());
            $this->assertSame(expected: 'Witness Protection Rule', actual: $e->getRuleName());
        }

    }//end testPolicyRejectedExceptionCarriesRuleDetails()

    /**
     * Fallback: when entityKey is null, match by entityText.
     *
     * @return void
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-6
     */
    public function testEntityKeyNullFallsBackToEntityText(): void
    {
        $existingData = [
            'documentId'    => 'doc-1',
            'entityKey'     => null,
            'entityText'    => 'Karin de Vries',
            'scope'         => 'document',
            'consentStatus' => 'pending',
        ];

        $bag     = $this->buildService(searchResults: [$this->buildSavedObject(data: $existingData)]);
        $service = $bag['service'];

        // EntityKey absent → falls back to entityText lookup.
        $result = $service->createConsentRequest(
            documentId: 'doc-1',
            entityType: 'PERSON',
            entityText: 'Karin de Vries',
            register: 'reg-1',
            schema: 'sch-1',
            extra: []
        );

        $this->assertTrue(condition: $result['wasUpdated'], message: 'Should update (entityText fallback match).');

    }//end testEntityKeyNullFallsBackToEntityText()

    /**
     * Scope=entity records are not matched by the idempotency lookup.
     *
     * @return void
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-6
     */
    public function testScopeEntityRecordsAreNotMatchedByIdempotencyLookup(): void
    {
        // SearchObjects returns a scope=entity record (standing consent for same entity).
        $standingConsent = $this->buildSavedObject(
                data: [
                    'documentId'    => null,
                    'entityText'    => 'Karin de Vries',
                    'scope'         => 'entity',
                    'consentStatus' => 'consent_given',
                ]
                );

        $bag     = $this->buildService(searchResults: [$standingConsent]);
        $service = $bag['service'];

        $result = $service->createConsentRequest(
            documentId: 'doc-2',
            entityType: 'PERSON',
            entityText: 'Karin de Vries',
            register: 'reg-1',
            schema: 'sch-1'
        );

        $this->assertFalse(
            condition: $result['wasUpdated'],
            message: 'scope=entity records must not satisfy the idempotency check.'
        );

    }//end testScopeEntityRecordsAreNotMatchedByIdempotencyLookup()

    // ------------------------------------------------------------------
    // validatePublicationConsentData scope rules
    // ------------------------------------------------------------------

    /**
     * Task 4.5 — scope=document must include a documentId
     *
     * @return void
     */
    public function testValidateRejectsScopeDocumentWithoutDocumentId(): void
    {
        $this->expectException(exception: \InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(regularExpression: '/scope=document/');

        $bag = $this->buildService();
        $bag['service']->validatePublicationConsentData(
            data: [
                'scope'      => 'document',
                'entityType' => 'PERSON',
                'entityText' => 'Jan Janssen',
            ]
        );

    }//end testValidateRejectsScopeDocumentWithoutDocumentId()

    /**
     * Task 4.5 — scope=entity rejects documentId
     *
     * @return void
     */
    public function testValidateRejectsScopeEntityWithDocumentId(): void
    {
        $this->expectException(exception: \InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(regularExpression: '/scope=entity/');

        $bag = $this->buildService();
        $bag['service']->validatePublicationConsentData(
            data: [
                'scope'         => 'entity',
                'documentId'    => 'doc-1',
                'entityType'    => 'PERSON',
                'entityText'    => 'Jan Janssen',
                'matchRules'    => [['type' => 'exact', 'value' => 'Jan Janssen']],
                'consentMethod' => 'paper',
            ]
        );

    }//end testValidateRejectsScopeEntityWithDocumentId()

    /**
     * Task 4.5 — scope=entity requires matchRules
     *
     * @return void
     */
    public function testValidateRejectsScopeEntityWithoutMatchRules(): void
    {
        $this->expectException(exception: \InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(regularExpression: '/matchRule/');

        $bag = $this->buildService();
        $bag['service']->validatePublicationConsentData(
            data: [
                'scope'         => 'entity',
                'entityType'    => 'PERSON',
                'entityText'    => 'Jan Janssen',
                'consentMethod' => 'paper',
            ]
        );

    }//end testValidateRejectsScopeEntityWithoutMatchRules()

    /**
     * Task 4.5 — scope=entity requires consentMethod
     *
     * @return void
     */
    public function testValidateRejectsScopeEntityWithoutConsentMethod(): void
    {
        $this->expectException(exception: \InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(regularExpression: '/consentMethod/');

        $bag = $this->buildService();
        $bag['service']->validatePublicationConsentData(
            data: [
                'scope'      => 'entity',
                'entityType' => 'PERSON',
                'entityText' => 'Jan Janssen',
                'matchRules' => [['type' => 'exact', 'value' => 'Jan Janssen']],
            ]
        );

    }//end testValidateRejectsScopeEntityWithoutConsentMethod()

    /**
     * Task 4.5 — scope=entity must not set policyMatch
     *
     * @return void
     */
    public function testValidateRejectsPolicyMatchOnScopeEntity(): void
    {
        $this->expectException(exception: \InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(regularExpression: '/policyMatch/');

        $bag = $this->buildService();
        $bag['service']->validatePublicationConsentData(
            data: [
                'scope'         => 'entity',
                'entityType'    => 'PERSON',
                'entityText'    => 'Jan Janssen',
                'matchRules'    => [['type' => 'exact', 'value' => 'Jan Janssen']],
                'consentMethod' => 'paper',
                'policyMatch'   => 'some-other-uuid',
            ]
        );

    }//end testValidateRejectsPolicyMatchOnScopeEntity()

    /**
     * Task 4.5 — a fully-valid scope=document record passes
     *
     * @return void
     */
    public function testValidateAcceptsValidScopeDocument(): void
    {
        $bag = $this->buildService();
        $bag['service']->validatePublicationConsentData(
            data: [
                'scope'      => 'document',
                'documentId' => 'doc-1',
                'entityType' => 'PERSON',
                'entityText' => 'Jan Janssen',
            ]
        );

        $this->expectNotToPerformAssertions();

    }//end testValidateAcceptsValidScopeDocument()

    /**
     * Task 4.5 — a fully-valid scope=entity record passes
     *
     * @return void
     */
    public function testValidateAcceptsValidScopeEntity(): void
    {
        $bag = $this->buildService();
        $bag['service']->validatePublicationConsentData(
            data: [
                'scope'         => 'entity',
                'entityType'    => 'PERSON',
                'entityText'    => 'Jan Janssen',
                'matchRules'    => [['type' => 'exact', 'value' => 'Jan Janssen']],
                'consentMethod' => 'paper',
            ]
        );

        $this->expectNotToPerformAssertions();

    }//end testValidateAcceptsValidScopeEntity()
}//end class

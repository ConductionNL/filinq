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
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

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
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConsentServiceTest extends TestCase
{

    /**
     * Service under test.
     *
     * @var ConsentService
     */
    private ConsentService $service;

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

        $this->mockLogger          = $this->createMock(originalClassName: LoggerInterface::class);
        $this->mockContainer       = $this->createMock(originalClassName: ContainerInterface::class);
        $this->mockAppManager      = $this->createMock(originalClassName: IAppManager::class);
        $this->mockDeadlineChecker = $this->createMock(originalClassName: ObjectionDeadlineChecker::class);
        $this->mockUpdateHandler   = $this->createMock(originalClassName: ConsentUpdateHandler::class);
        $this->mockPolicyMatcher   = $this->createMock(originalClassName: PolicyMatchService::class);

        $this->service = new ConsentService(
            logger: $this->mockLogger,
            container: $this->mockContainer,
            appManager: $this->mockAppManager,
            deadlineChecker: $this->mockDeadlineChecker,
            updateHandler: $this->mockUpdateHandler,
            policyMatcher: $this->mockPolicyMatcher
        );

    }//end setUp()


    /**
     * Test updateConsentStatus delegates to handler
     *
     * @return void
     */
    public function testUpdateConsentStatusDelegates(): void
    {
        $expected = ['consentStatus' => 'granted'];
        $this->mockUpdateHandler->method('updateConsentStatus')
            ->with('uuid-1', 'reg-1', 'sch-1', ['consentStatus' => 'granted'])
            ->willReturn($expected);

        $result = $this->service->updateConsentStatus('uuid-1', 'reg-1', 'sch-1', ['consentStatus' => 'granted']);
        $this->assertEquals(expected: $expected, actual: $result);

    }//end testUpdateConsentStatusDelegates()


    /**
     * Test checkObjectionDeadline delegates to checker
     *
     * @return void
     */
    public function testCheckObjectionDeadlineDelegates(): void
    {
        $this->mockDeadlineChecker->method('checkObjectionDeadline')
            ->with('uuid-1', 'reg-1', 'sch-1')
            ->willReturn(true);

        $result = $this->service->checkObjectionDeadline('uuid-1', 'reg-1', 'sch-1');
        $this->assertTrue(condition: $result);

    }//end testCheckObjectionDeadlineDelegates()


    /**
     * Test getConsentsByDocument delegates to handler
     *
     * @return void
     */
    public function testGetConsentsByDocumentDelegates(): void
    {
        $expected = [['documentId' => 'doc-1', 'consentStatus' => 'pending']];
        $this->mockUpdateHandler->method('getConsentsByDocument')
            ->with('doc-1', 'reg-1', 'sch-1')
            ->willReturn($expected);

        $result = $this->service->getConsentsByDocument('doc-1', 'reg-1', 'sch-1');
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

        $this->mockAppManager->method('getInstalledApps')
            ->willReturn([]);

        $this->service->createConsentRequest('doc-1', 'PERSON', 'John', 'reg-1', 'sch-1');

    }//end testCreateConsentRequestThrowsWhenNotInstalled()


    /**
     * Task 4.5 — scope=document must include a documentId
     *
     * @return void
     */
    public function testValidateRejectsScopeDocumentWithoutDocumentId(): void
    {
        $this->expectException(exception: \InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(regularExpression: '/scope=document/');

        $this->service->validatePublicationConsentData(
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

        $this->service->validatePublicationConsentData(
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

        $this->service->validatePublicationConsentData(
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

        $this->service->validatePublicationConsentData(
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

        $this->service->validatePublicationConsentData(
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
        $this->service->validatePublicationConsentData(
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
        $this->service->validatePublicationConsentData(
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

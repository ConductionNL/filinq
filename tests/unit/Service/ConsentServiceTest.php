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
     * @var ConsentService
     */
    private ConsentService $service;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var ContainerInterface|MockObject
     */
    private ContainerInterface|MockObject $mockContainer;

    /**
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $mockAppManager;

    /**
     * @var ObjectionDeadlineChecker|MockObject
     */
    private ObjectionDeadlineChecker|MockObject $mockDeadlineChecker;

    /**
     * @var ConsentUpdateHandler|MockObject
     */
    private ConsentUpdateHandler|MockObject $mockUpdateHandler;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger           = $this->createMock(LoggerInterface::class);
        $this->mockContainer        = $this->createMock(ContainerInterface::class);
        $this->mockAppManager       = $this->createMock(IAppManager::class);
        $this->mockDeadlineChecker  = $this->createMock(ObjectionDeadlineChecker::class);
        $this->mockUpdateHandler    = $this->createMock(ConsentUpdateHandler::class);

        $this->service = new ConsentService(
            $this->mockLogger,
            $this->mockContainer,
            $this->mockAppManager,
            $this->mockDeadlineChecker,
            $this->mockUpdateHandler
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
        $this->assertEquals($expected, $result);

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
        $this->assertTrue($result);

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
        $this->assertEquals($expected, $result);

    }//end testGetConsentsByDocumentDelegates()


    /**
     * Test createConsentRequest throws when OpenRegister not installed
     *
     * @return void
     */
    public function testCreateConsentRequestThrowsWhenNotInstalled(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create consent request');

        $this->mockAppManager->method('getInstalledApps')
            ->willReturn([]);

        $this->service->createConsentRequest('doc-1', 'PERSON', 'John', 'reg-1', 'sch-1');

    }//end testCreateConsentRequestThrowsWhenNotInstalled()


}//end class

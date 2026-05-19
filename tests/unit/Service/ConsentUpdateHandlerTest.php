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
     * @var ConsentUpdateHandler
     */
    private ConsentUpdateHandler $handler;

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
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger     = $this->createMock(LoggerInterface::class);
        $this->mockContainer  = $this->createMock(ContainerInterface::class);
        $this->mockAppManager = $this->createMock(IAppManager::class);

        $this->handler = new ConsentUpdateHandler(
            $this->mockLogger,
            $this->mockContainer,
            $this->mockAppManager
        );

    }//end setUp()


    /**
     * Test updateConsentStatus throws when OpenRegister not installed
     *
     * @return void
     */
    public function testUpdateConsentStatusThrowsWhenNotInstalled(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to update consent status');

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
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to get consents for document');

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
        $this->assertInstanceOf(ConsentUpdateHandler::class, $this->handler);

    }//end testCanBeInstantiated()


}//end class

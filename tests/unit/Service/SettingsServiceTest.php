<?php

/**
 * Unit tests for SettingsService
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

use OCA\DocuDesk\Service\RegisterDiscoveryService;
use OCA\DocuDesk\Service\SettingsInitializer;
use OCA\DocuDesk\Service\SettingsService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Unit tests for SettingsService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SettingsServiceTest extends TestCase
{

    /**
     * @var SettingsService
     */
    private SettingsService $settingsService;

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockConfig;

    /**
     * @var ContainerInterface|MockObject
     */
    private ContainerInterface|MockObject $mockContainer;

    /**
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $mockAppManager;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var RegisterDiscoveryService|MockObject
     */
    private RegisterDiscoveryService|MockObject $mockDiscoveryService;

    /**
     * @var SettingsInitializer|MockObject
     */
    private SettingsInitializer|MockObject $mockInitializer;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig           = $this->createMock(IAppConfig::class);
        $this->mockContainer        = $this->createMock(ContainerInterface::class);
        $this->mockAppManager       = $this->createMock(IAppManager::class);
        $this->mockLogger           = $this->createMock(LoggerInterface::class);
        $this->mockDiscoveryService = $this->createMock(RegisterDiscoveryService::class);
        $this->mockInitializer      = $this->createMock(SettingsInitializer::class);

        $this->settingsService = new SettingsService(
            $this->mockConfig,
            $this->mockContainer,
            $this->mockAppManager,
            $this->mockLogger,
            $this->mockDiscoveryService,
            $this->mockInitializer
        );

    }//end setUp()


    /**
     * Test getObjectService throws when OpenRegister not installed
     *
     * @return void
     */
    public function testGetObjectServiceThrowsWhenNotInstalled(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenRegister service is not available');

        $this->mockAppManager->method('getInstalledApps')
            ->willReturn([]);

        $this->settingsService->getObjectService();

    }//end testGetObjectServiceThrowsWhenNotInstalled()


    /**
     * Test initialize delegates to SettingsInitializer
     *
     * @return void
     */
    public function testInitializeDelegatesToInitializer(): void
    {
        $expected = ['registers' => 1, 'schemas' => 2];
        $this->mockInitializer->method('initialize')
            ->willReturn($expected);

        $result = $this->settingsService->initialize();
        $this->assertEquals($expected, $result);

    }//end testInitializeDelegatesToInitializer()


    /**
     * Test getAllSettings returns expected structure
     *
     * @return void
     */
    public function testGetAllSettingsReturnsExpectedStructure(): void
    {
        $this->mockAppManager->method('isInstalled')
            ->willReturn(false);

        $this->mockDiscoveryService->method('loadObjectTypeConfiguration')
            ->willReturn(['publicationConsent_register' => '', 'publicationConsent_schema' => '']);

        $this->mockConfig->method('getValueString')
            ->willReturn('1');

        $result = $this->settingsService->getAllSettings();

        $this->assertArrayHasKey('objectTypes', $result);
        $this->assertArrayHasKey('openRegisters', $result);
        $this->assertArrayHasKey('configuration', $result);
        $this->assertFalse($result['openRegisters']);

    }//end testGetAllSettingsReturnsExpectedStructure()


    /**
     * Test updateSettings persists values
     *
     * @return void
     */
    public function testUpdateSettingsPersistsValues(): void
    {
        // Use a key that is in the WRITABLE_KEYS allowlist.
        $this->mockConfig->expects($this->once())
            ->method('setValueString')
            ->with('docudesk', 'signing_provider', 'native');

        $this->mockConfig->method('getValueString')
            ->willReturn('native');

        $result = $this->settingsService->updateSettings(['signing_provider' => 'native']);
        $this->assertEquals('native', $result['signing_provider']);

    }//end testUpdateSettingsPersistsValues()


    /**
     * Test updateSettings skips empty keys
     *
     * @return void
     */
    public function testUpdateSettingsSkipsEmptyKeys(): void
    {
        $this->mockConfig->expects($this->never())
            ->method('setValueString');

        $this->mockLogger->expects($this->once())
            ->method('warning');

        $result = $this->settingsService->updateSettings(['' => 'value']);
        $this->assertArrayHasKey('', $result);

    }//end testUpdateSettingsSkipsEmptyKeys()


}//end class

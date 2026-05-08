<?php

/**
 * Unit tests for RegisterDiscoveryService
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
use OCA\OpenRegister\Service\RegisterService;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for RegisterDiscoveryService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class RegisterDiscoveryServiceTest extends TestCase
{

    /**
     * @var RegisterDiscoveryService
     */
    private RegisterDiscoveryService $service;

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockConfig;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var RegisterService|MockObject
     */
    private RegisterService|MockObject $mockRegisterService;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig          = $this->createMock(IAppConfig::class);
        $this->mockLogger          = $this->createMock(LoggerInterface::class);
        $this->mockRegisterService = $this->createMock(RegisterService::class);

        $this->service = new RegisterDiscoveryService(
            $this->mockConfig,
            $this->mockLogger,
            $this->mockRegisterService
        );

    }//end setUp()


    /**
     * Test loadObjectTypeConfiguration returns expected keys
     *
     * @return void
     */
    public function testLoadObjectTypeConfigurationReturnsExpectedKeys(): void
    {
        $this->mockConfig->method('getValueString')
            ->willReturn('');

        $result = $this->service->loadObjectTypeConfiguration(['publicationConsent', 'template']);

        $this->assertArrayHasKey('publicationConsent_source', $result);
        $this->assertArrayHasKey('publicationConsent_schema', $result);
        $this->assertArrayHasKey('publicationConsent_register', $result);
        $this->assertArrayHasKey('template_source', $result);
        $this->assertArrayHasKey('template_schema', $result);
        $this->assertArrayHasKey('template_register', $result);

    }//end testLoadObjectTypeConfigurationReturnsExpectedKeys()


    /**
     * Test loadObjectTypeConfiguration returns configured values
     *
     * @return void
     */
    public function testLoadObjectTypeConfigurationReturnsConfiguredValues(): void
    {
        $this->mockConfig->method('getValueString')
            ->willReturnCallback(function ($app, $key, $default) {
                if ($key === 'publicationConsent_source') {
                    return 'openregister';
                }

                return $default;
            });

        $result = $this->service->loadObjectTypeConfiguration(['publicationConsent']);
        $this->assertEquals('openregister', $result['publicationConsent_source']);

    }//end testLoadObjectTypeConfigurationReturnsConfiguredValues()


    /**
     * Test fetchAvailableRegisters returns empty on exception
     *
     * @return void
     */
    public function testFetchAvailableRegistersReturnsEmptyOnException(): void
    {
        $this->mockRegisterService->method('findAll')
            ->willThrowException(new \Exception('Service error'));

        $result = $this->service->fetchAvailableRegisters();
        $this->assertEmpty($result);

    }//end testFetchAvailableRegistersReturnsEmptyOnException()


    /**
     * Test fetchAvailableRegisters returns empty on TypeError
     *
     * @return void
     */
    public function testFetchAvailableRegistersReturnsEmptyOnTypeError(): void
    {
        $this->mockRegisterService->method('findAll')
            ->willThrowException(new \TypeError('Type error'));

        $result = $this->service->fetchAvailableRegisters();
        $this->assertEmpty($result);

    }//end testFetchAvailableRegistersReturnsEmptyOnTypeError()


}//end class

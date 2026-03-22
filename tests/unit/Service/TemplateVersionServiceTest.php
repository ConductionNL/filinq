<?php

/**
 * Unit tests for TemplateVersionService
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

use OCA\DocuDesk\Service\OpenRegisterResolver;
use OCA\DocuDesk\Service\TemplateVersionService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Unit tests for TemplateVersionService
 *
 * Tests version creation, listing, restore, and diff operations.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class TemplateVersionServiceTest extends TestCase
{

    /**
     * The TemplateVersionService instance being tested
     *
     * @var TemplateVersionService
     */
    private TemplateVersionService $versionService;

    /**
     * Mocked ObjectService
     *
     * @var ObjectService|MockObject
     */
    private $objectService;

    /**
     * Mocked OpenRegisterResolver
     *
     * @var OpenRegisterResolver|MockObject
     */
    private $registerResolver;


    /**
     * Set up the test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectService    = $this->createMock(ObjectService::class);
        $this->registerResolver = $this->createMock(OpenRegisterResolver::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('OCA\OpenRegister\Service\ObjectService')
            ->willReturn($this->objectService);

        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getInstalledApps')
            ->willReturn(['openregister']);

        $this->registerResolver->method('getVersionRegisterAndSchema')
            ->willReturn(['register' => 'test-register', 'schema' => 'test-schema']);

        $this->versionService = new TemplateVersionService(
            $container,
            $appManager,
            $this->registerResolver
        );

    }//end setUp()


    /**
     * Test that createVersion saves a version object with correct data
     *
     * @return void
     */
    public function testCreateVersionSavesCorrectData(): void
    {
        $this->objectService->method('buildSearchQuery')
            ->willReturn([]);
        $this->objectService->method('searchObjectsPaginated')
            ->willReturn(['results' => [], 'total' => 0]);

        $savedData = null;
        $this->objectService->method('saveObject')
            ->willReturnCallback(function ($object) use (&$savedData) {
                $savedData = $object;
                return $object;
            });

        $templateState = [
            'name'    => 'Test Template',
            'content' => '<p>Hello</p>',
            'format'  => 'A4',
        ];

        $this->versionService->createVersion(
            'template-123',
            $templateState,
            'admin',
            'Initial version'
        );

        $this->assertNotNull($savedData);
        $this->assertEquals('template-123', $savedData['templateId']);
        $this->assertEquals(1, $savedData['version']);
        $this->assertEquals('Test Template', $savedData['name']);
        $this->assertEquals('<p>Hello</p>', $savedData['content']);
        $this->assertEquals('admin', $savedData['editor']);
        $this->assertEquals('Initial version', $savedData['changelog']);

    }//end testCreateVersionSavesCorrectData()


    /**
     * Test that getVersions returns paginated results
     *
     * @return void
     */
    public function testGetVersionsReturnsPaginated(): void
    {
        $expectedResult = [
            'results' => [
                ['id' => 'v1', 'version' => 2],
                ['id' => 'v2', 'version' => 1],
            ],
            'total' => 2,
        ];

        $this->objectService->method('buildSearchQuery')
            ->willReturn([]);
        $this->objectService->method('searchObjectsPaginated')
            ->willReturn($expectedResult);

        $result = $this->versionService->getVersions('template-123');

        $this->assertCount(2, $result['results']);
        $this->assertEquals(2, $result['total']);

    }//end testGetVersionsReturnsPaginated()


    /**
     * Test that getNextVersionNumber returns correct next number
     *
     * @return void
     */
    public function testGetNextVersionNumber(): void
    {
        $this->objectService->method('buildSearchQuery')
            ->willReturn([]);
        $this->objectService->method('searchObjectsPaginated')
            ->willReturn(['results' => [], 'total' => 3]);

        $next = $this->versionService->getNextVersionNumber('template-123');

        $this->assertEquals(4, $next);

    }//end testGetNextVersionNumber()


    /**
     * Test that getDiff returns both versions
     *
     * @return void
     */
    public function testGetDiffReturnsBothVersions(): void
    {
        $versionFrom = ['id' => 'v1', 'version' => 1, 'content' => 'old'];
        $versionTo   = ['id' => 'v2', 'version' => 2, 'content' => 'new'];

        $this->objectService->method('find')
            ->willReturnOnConsecutiveCalls($versionFrom, $versionTo);

        $result = $this->versionService->getDiff('v1', 'v2');

        $this->assertEquals('old', $result['from']['content']);
        $this->assertEquals('new', $result['to']['content']);

    }//end testGetDiffReturnsBothVersions()


}//end class

<?php

/**
 * Unit tests for DataResolverService
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

use Exception;
use OCA\DocuDesk\Service\DataResolverService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for DataResolverService
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
class DataResolverServiceTest extends TestCase
{

    /**
     * The service under test
     *
     * @var DataResolverService
     */
    private DataResolverService $service;

    /**
     * Mock container
     *
     * @var ContainerInterface&MockObject
     */
    private ContainerInterface $container;

    /**
     * Mock app manager
     *
     * @var IAppManager&MockObject
     */
    private IAppManager $appManager;

    /**
     * Mock logger
     *
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * Mock object service
     *
     * @var ObjectService&MockObject
     */
    private ObjectService $objectService;


    /**
     * Set up test fixtures
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container     = $this->createMock(ContainerInterface::class);
        $this->appManager    = $this->createMock(IAppManager::class);
        $this->logger        = $this->createMock(LoggerInterface::class);
        $this->objectService = $this->createMock(ObjectService::class);

        $this->appManager->method('getInstalledApps')
            ->willReturn(['openregister']);

        $this->container->method('get')
            ->with('OCA\OpenRegister\Service\ObjectService')
            ->willReturn($this->objectService);

        $this->service = new DataResolverService(
            $this->container,
            $this->appManager,
            $this->logger
        );

    }//end setUp()


    /**
     * Test resolving a single object reference
     *
     * @return void
     */
    public function testResolveSingleReference(): void
    {
        $objectData = ['id' => 'abc-123', 'naam' => 'Test Persoon'];

        $entity = $this->createMock(ObjectEntity::class);
        $entity->method('jsonSerialize')->willReturn($objectData);
        $this->objectService->expects($this->once())
            ->method('find')
            ->willReturn($entity);

        $result = $this->service->resolve(
            dataRefs: [
                [
                    'register' => 'brp',
                    'schema'   => 'persoon',
                    'id'       => 'abc-123',
                ],
            ]
        );

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('persoon', $result['data']);
        $this->assertEquals('Test Persoon', $result['data']['persoon']['naam']);
        $this->assertEmpty($result['errors']);

    }//end testResolveSingleReference()


    /**
     * Test ad-hoc data merging overrides resolved data
     *
     * @return void
     */
    public function testAdHocDataOverridesResolved(): void
    {
        $objectData = ['id' => 'abc-123', 'naam' => 'Original'];

        $entity = $this->createMock(ObjectEntity::class);
        $entity->method('jsonSerialize')->willReturn($objectData);
        $this->objectService->method('find')
            ->willReturn($entity);

        $result = $this->service->resolve(
            dataRefs: [
                [
                    'register' => 'brp',
                    'schema'   => 'persoon',
                    'id'       => 'abc-123',
                ],
            ],
            adHocData: ['persoon' => ['naam' => 'Override']]
        );

        $this->assertEquals('Override', $result['data']['persoon']['naam']);

    }//end testAdHocDataOverridesResolved()


    /**
     * Test that resolution failures produce descriptive errors
     *
     * @return void
     */
    public function testResolutionFailureProducesError(): void
    {
        $this->objectService->method('find')
            ->willReturn(null);

        $result = $this->service->resolve(
            dataRefs: [
                [
                    'register' => 'brp',
                    'schema'   => 'persoon',
                    'id'       => 'nonexistent',
                ],
            ]
        );

        $this->assertCount(1, $result['errors']);
        $this->assertEquals('brp', $result['errors'][0]['register']);
        $this->assertEquals('persoon', $result['errors'][0]['schema']);
        $this->assertEquals('nonexistent', $result['errors'][0]['id']);

    }//end testResolutionFailureProducesError()


    /**
     * Test missing required fields in reference
     *
     * @return void
     */
    public function testMissingRequiredFieldsInReference(): void
    {
        $result = $this->service->resolve(
            dataRefs: [
                ['register' => 'brp', 'schema' => ''],
            ]
        );

        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('missing required field', $result['errors'][0]['message']);

    }//end testMissingRequiredFieldsInReference()


    /**
     * Test that individual failures do not abort other resolutions
     *
     * @return void
     */
    public function testPartialResolutionOnFailure(): void
    {
        $goodEntity = $this->createMock(ObjectEntity::class);
        $goodEntity->method('jsonSerialize')->willReturn(['id' => 'good-id', 'naam' => 'Success']);

        $this->objectService->method('find')
            ->willReturnCallback(function ($id) use ($goodEntity) {
                if ($id === 'good-id') {
                    return $goodEntity;
                }

                return null;
            });

        $result = $this->service->resolve(
            dataRefs: [
                ['register' => 'brp', 'schema' => 'persoon', 'id' => 'good-id'],
                ['register' => 'brp', 'schema' => 'adres', 'id' => 'bad-id'],
            ]
        );

        $this->assertArrayHasKey('persoon', $result['data']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals('bad-id', $result['errors'][0]['id']);

    }//end testPartialResolutionOnFailure()


}//end class

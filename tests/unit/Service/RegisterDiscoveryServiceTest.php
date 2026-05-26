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
use OCA\OpenRegister\Db\Register;
use OCA\OpenRegister\Db\Schema;
use OCA\OpenRegister\Db\SchemaMapper;
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
     * @var SchemaMapper|MockObject
     */
    private SchemaMapper|MockObject $mockSchemaMapper;


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
        $this->mockSchemaMapper    = $this->createMock(SchemaMapper::class);

        $this->service = new RegisterDiscoveryService(
            $this->mockConfig,
            $this->mockLogger,
            $this->mockRegisterService,
            $this->mockSchemaMapper
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


    /**
     * Test fetchAvailableRegisters strips `properties` from expanded schemas
     *
     * @return void
     */
    public function testFetchAvailableRegistersStripsPropertiesFromExpandedSchemas(): void
    {
        // findAll returns register objects whose schemas are bare IDs; the
        // service expands each ID via SchemaMapper::find and strips properties.
        $this->mockRegisterService->method('findAll')
            ->willReturn(
                [
                    $this->makeRegister(
                        [
                            'id'      => 1,
                            'title'   => 'Register A',
                            'schemas' => [10],
                        ]
                    ),
                ]
            );

        $this->mockSchemaMapper->method('find')
            ->willReturn(
                $this->makeSchema(
                    [
                        'id'         => 10,
                        'title'      => 'Schema A',
                        'properties' => ['foo' => ['type' => 'string']],
                    ]
                )
            );

        $result = $this->service->fetchAvailableRegisters();

        $this->assertCount(1, $result);
        $this->assertSame(10, $result[0]['schemas'][0]['id']);
        $this->assertArrayNotHasKey('properties', $result[0]['schemas'][0]);
        $this->assertSame('Schema A', $result[0]['schemas'][0]['title']);

    }//end testFetchAvailableRegistersStripsPropertiesFromExpandedSchemas()


    /**
     * Test fetchAvailableRegisters passes orphan schema IDs through unchanged
     *
     * Orphan IDs are bare ints/strings (not arrays), so `filterSchemaProperties`
     * passes them through without trying to strip `properties`.
     *
     * @return void
     */
    public function testFetchAvailableRegistersPassesOrphanIdsThrough(): void
    {
        $this->mockRegisterService->method('findAll')
            ->willReturn(
                [
                    $this->makeRegister(
                        [
                            'id'      => 1,
                            'schemas' => [10, 999, 'uuid-orphan-abc'],
                        ]
                    ),
                ]
            );

        // Schema 10 resolves; the other two are orphans (find throws) and
        // must be retained as bare IDs.
        $this->mockSchemaMapper->method('find')
            ->willReturnCallback(
                function ($id) {
                    if ($id === 10) {
                        return $this->makeSchema(
                            [
                                'id'         => 10,
                                'title'      => 'Schema A',
                                'properties' => [],
                            ]
                        );
                    }

                    throw new \Exception('Schema not found');
                }
            );

        $result = $this->service->fetchAvailableRegisters();

        $this->assertCount(3, $result[0]['schemas']);
        $this->assertIsArray($result[0]['schemas'][0]);
        $this->assertSame(999, $result[0]['schemas'][1]);
        $this->assertSame('uuid-orphan-abc', $result[0]['schemas'][2]);

    }//end testFetchAvailableRegistersPassesOrphanIdsThrough()


    /**
     * Test fetchAvailableRegisters handles a register with an empty schemas array
     *
     * @return void
     */
    public function testFetchAvailableRegistersHandlesEmptySchemas(): void
    {
        $this->mockRegisterService->method('findAll')
            ->willReturn(
                [
                    $this->makeRegister(
                        [
                            'id'      => 1,
                            'title'   => 'Register A',
                            'schemas' => [],
                        ]
                    ),
                ]
            );

        $result = $this->service->fetchAvailableRegisters();

        $this->assertCount(1, $result);
        $this->assertSame([], $result[0]['schemas']);

    }//end testFetchAvailableRegistersHandlesEmptySchemas()


    /**
     * Build a Register mock whose jsonSerialize returns the given data.
     *
     * RegisterService::findAll yields Register entities; the service only
     * calls `jsonSerialize()` on them, so a configured mock is sufficient and
     * avoids depending on the OpenRegister entity constructors.
     *
     * @param array<string, mixed> $data The serialized representation
     *
     * @return Register|MockObject
     */
    private function makeRegister(array $data): Register|MockObject
    {
        $register = $this->createMock(Register::class);
        $register->method('jsonSerialize')->willReturn($data);

        return $register;

    }//end makeRegister()


    /**
     * Build a Schema mock whose jsonSerialize returns the given data.
     *
     * SchemaMapper::find returns a Schema entity; the service only calls
     * `jsonSerialize()` on it.
     *
     * @param array<string, mixed> $data The serialized representation
     *
     * @return Schema|MockObject
     */
    private function makeSchema(array $data): Schema|MockObject
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('jsonSerialize')->willReturn($data);

        return $schema;

    }//end makeSchema()


}//end class

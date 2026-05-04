<?php

/**
 * Unit tests for SettingsInitializer
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

use OCA\DocuDesk\Service\SettingsInitializer;
use OCA\OpenRegister\Db\Register;
use OCA\OpenRegister\Db\RegisterMapper;
use OCA\OpenRegister\Db\Schema;
use OCA\OpenRegister\Db\SchemaMapper;
use OCP\App\IAppManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for SettingsInitializer
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SettingsInitializerTest extends TestCase
{

    /**
     * The system under test.
     *
     * @var SettingsInitializer
     */
    private SettingsInitializer $initializer;

    /**
     * Mocked app config used to assert IAppConfig writes.
     *
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockConfig;

    /**
     * Mocked container used to resolve OpenRegister mappers in tests.
     *
     * @var ContainerInterface|MockObject
     */
    private ContainerInterface|MockObject $mockContainer;

    /**
     * Mocked app manager that reports OpenRegister as installed.
     *
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $mockAppManager;

    /**
     * Mocked logger used to assert info / warning / error messages.
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig     = $this->createMock(IAppConfig::class);
        $this->mockContainer  = $this->createMock(ContainerInterface::class);
        $this->mockAppManager = $this->createMock(IAppManager::class);
        $this->mockLogger     = $this->createMock(LoggerInterface::class);

        $this->initializer = new SettingsInitializer(
            $this->mockConfig,
            $this->mockContainer,
            $this->mockAppManager,
            $this->mockLogger
        );

    }//end setUp()


    /**
     * Test initialize returns error when OpenRegister not installed
     *
     * @return void
     */
    public function testInitializeReturnsErrorWhenNotInstalled(): void
    {
        $this->mockAppManager->method('isInstalled')
            ->willReturn(false);

        $result = $this->initializer->initialize();

        $this->assertFalse($result['configuration']);
        $this->assertNotEmpty($result['errors']);

    }//end testInitializeReturnsErrorWhenNotInstalled()


    /**
     * Test initialize returns error when OpenRegister not enabled
     *
     * @return void
     */
    public function testInitializeReturnsErrorWhenNotEnabled(): void
    {
        $this->mockAppManager->method('isInstalled')
            ->willReturn(true);
        $this->mockAppManager->method('getAppVersion')
            ->willReturn('1.0.0');
        $this->mockAppManager->method('isEnabledForUser')
            ->willReturn(false);

        $result = $this->initializer->initialize();

        $this->assertFalse($result['configuration']);
        $this->assertNotEmpty($result['errors']);

    }//end testInitializeReturnsErrorWhenNotEnabled()


    /**
     * Test initialize has expected result structure
     *
     * @return void
     */
    public function testInitializeHasExpectedResultStructure(): void
    {
        $this->mockAppManager->method('isInstalled')
            ->willReturn(false);

        $result = $this->initializer->initialize();

        $this->assertArrayHasKey('configuration', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('info', $result);

    }//end testInitializeHasExpectedResultStructure()


    // ──────────────────────────────────────────────────────────
    // applyObjectTypeConfigurationDefaults() — auto-default helper
    //
    // The helper seeds `{schemaSlug}_source/_register/_schema` IAppConfig keys
    // from the OpenRegister registers/schemas referenced in
    // `lib/Settings/docudesk_register.json`. It MUST preserve any existing
    // (admin-set) values per CAT-style override-protection rule (SET-007).
    // ──────────────────────────────────────────────────────────


    /**
     * Configure container + appManager so the helper's mapper lookups succeed.
     *
     * @param RegisterMapper&MockObject $registerMapper Stubbed register mapper.
     * @param SchemaMapper&MockObject   $schemaMapper   Stubbed schema mapper.
     *
     * @return void
     */
    private function bindMappers(RegisterMapper $registerMapper, SchemaMapper $schemaMapper): void
    {
        $this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);
        $this->mockContainer->method('get')->willReturnCallback(
            function (string $class) use ($registerMapper, $schemaMapper) {
                return match ($class) {
                    'OCA\OpenRegister\Db\RegisterMapper' => $registerMapper,
                    'OCA\OpenRegister\Db\SchemaMapper'   => $schemaMapper,
                    default                              => null,
                };
            }
        );

    }//end bindMappers()


    /**
     * Build a NextCloud Entity mock that responds to the magic `getId()` accessor.
     *
     * @param string $class Entity class to mock (Register or Schema).
     * @param int    $id    Value returned by `getId()`.
     *
     * @return object&MockObject
     */
    private function createEntityMock(string $class, int $id): object
    {
        $mock = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->addMethods(['getId'])
            ->getMock();
        $mock->method('getId')->willReturn($id);
        return $mock;

    }//end createEntityMock()


    /**
     * Invoke the private helper via reflection.
     *
     * @param array<string, mixed> $jsonDef Parsed JSON definition.
     *
     * @return void
     */
    private function invokeHelper(array $jsonDef): void
    {
        $ref    = new \ReflectionClass(SettingsInitializer::class);
        $method = $ref->getMethod('applyObjectTypeConfigurationDefaults');
        $method->setAccessible(true);
        $method->invoke($this->initializer, $jsonDef);

    }//end invokeHelper()


    /**
     * JSON fixture: one register, one schema.
     *
     * @return array<string, mixed>
     */
    private function jsonWithSingleSchema(): array
    {
        return [
            'components' => [
                'registers' => [
                    'consent' => [
                        'slug'    => 'consent',
                        'schemas' => ['publicationConsent'],
                    ],
                ],
            ],
        ];

    }//end jsonWithSingleSchema()


    /**
     * JSON fixture: two registers each with one schema.
     *
     * @return array<string, mixed>
     */
    private function jsonWithTwoSchemas(): array
    {
        return [
            'components' => [
                'registers' => [
                    'consent'   => ['slug' => 'consent',   'schemas' => ['publicationConsent']],
                    'templates' => ['slug' => 'templates', 'schemas' => ['template']],
                ],
            ],
        ];

    }//end jsonWithTwoSchemas()


    /**
     * Fresh install: every IAppConfig key empty → all three keys are written per schema.
     *
     * @return void
     */
    public function testFreshInstallWritesAllThreeKeysPerSchema(): void
    {
        $registerMapper = $this->createMock(RegisterMapper::class);
        $schemaMapper   = $this->createMock(SchemaMapper::class);
        $registerMapper->method('find')->willReturn($this->createEntityMock(Register::class, 11));
        $schemaMapper->method('find')->willReturn($this->createEntityMock(Schema::class, 22));
        $this->bindMappers($registerMapper, $schemaMapper);

        $this->mockConfig->method('getValueString')->willReturn('');

        $writes = [];
        $this->mockConfig->expects($this->exactly(3))
            ->method('setValueString')
            ->willReturnCallback(
                    function (string $app, string $key, string $value) use (&$writes): bool {
                        $writes[$key] = $value;
                        return true;
                    }
                    );

        $this->invokeHelper($this->jsonWithSingleSchema());

        $this->assertSame('openregister', $writes['publicationConsent_source'] ?? null);
        $this->assertSame('11', $writes['publicationConsent_register'] ?? null);
        $this->assertSame('22', $writes['publicationConsent_schema'] ?? null);

    }//end testFreshInstallWritesAllThreeKeysPerSchema()


    /**
     * Existing override on `_register` is preserved; the other two keys are still written.
     *
     * @return void
     */
    public function testPreservesExistingOverride(): void
    {
        $registerMapper = $this->createMock(RegisterMapper::class);
        $schemaMapper   = $this->createMock(SchemaMapper::class);
        $registerMapper->method('find')->willReturn($this->createEntityMock(Register::class, 11));
        $schemaMapper->method('find')->willReturn($this->createEntityMock(Schema::class, 22));
        $this->bindMappers($registerMapper, $schemaMapper);

        $this->mockConfig->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default) {
                if ($key === 'publicationConsent_register') {
                    return '99';
                }

                return '';
            }
        );

        $writtenKeys = [];
        $this->mockConfig->method('setValueString')->willReturnCallback(
            function (string $app, string $key) use (&$writtenKeys): bool {
                $writtenKeys[] = $key;
                return true;
            }
        );

        $this->invokeHelper($this->jsonWithSingleSchema());

        $this->assertNotContains('publicationConsent_register', $writtenKeys, 'override must be preserved');
        $this->assertContains('publicationConsent_source', $writtenKeys);
        $this->assertContains('publicationConsent_schema', $writtenKeys);

    }//end testPreservesExistingOverride()


    /**
     * Per-key gating: only the empty key in a (register, schema) pair gets written.
     *
     * @return void
     */
    public function testPerKeyGatingAllowsPartialOverride(): void
    {
        $registerMapper = $this->createMock(RegisterMapper::class);
        $schemaMapper   = $this->createMock(SchemaMapper::class);
        $registerMapper->method('find')->willReturn($this->createEntityMock(Register::class, 11));
        $schemaMapper->method('find')->willReturn($this->createEntityMock(Schema::class, 22));
        $this->bindMappers($registerMapper, $schemaMapper);

        // The _register key is set; _source and _schema are empty.
        $this->mockConfig->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default) {
                if ($key === 'publicationConsent_register') {
                    return '99';
                }

                return '';
            }
        );

        $writtenKeys = [];
        $this->mockConfig->method('setValueString')->willReturnCallback(
            function (string $app, string $key) use (&$writtenKeys): bool {
                $writtenKeys[] = $key;
                return true;
            }
        );

        $this->invokeHelper($this->jsonWithSingleSchema());

        $this->assertSame(
            ['publicationConsent_source', 'publicationConsent_schema'],
            $writtenKeys,
            'only empty keys should be written'
        );

    }//end testPerKeyGatingAllowsPartialOverride()


    /**
     * SchemaMapper::find throws → schema is skipped with a warning, no exception propagates.
     *
     * @return void
     */
    public function testMissingSchemaIsSkippedWithWarning(): void
    {
        $registerMapper = $this->createMock(RegisterMapper::class);
        $registerMapper->method('find')->willReturnCallback(
            function (string $id) {
                if ($id === 'consent') {
                    return $this->createEntityMock(Register::class, 11);
                }

                return $this->createEntityMock(Register::class, 33);
            }
        );
        $schemaMapper = $this->createMock(SchemaMapper::class);
        $schemaMapper->method('find')->willReturnCallback(
            function (string $id) {
                if ($id === 'template') {
                    throw new DoesNotExistException('Schema template not found');
                }

                return $this->createEntityMock(Schema::class, 22);
            }
        );
        $this->bindMappers($registerMapper, $schemaMapper);

        $this->mockConfig->method('getValueString')->willReturn('');

        $this->mockLogger->expects($this->atLeastOnce())
            ->method('warning')
            ->with(
                $this->stringContains('Auto-default skipped schema: schema not found'),
                $this->callback(fn (array $ctx) => ($ctx['schemaSlug'] ?? null) === 'template')
            );

        $writtenKeys = [];
        $this->mockConfig->method('setValueString')->willReturnCallback(
            function (string $app, string $key) use (&$writtenKeys): bool {
                $writtenKeys[] = $key;
                return true;
            }
        );

        $this->invokeHelper($this->jsonWithTwoSchemas());

        // The publicationConsent schema gets all three keys; template gets none.
        $this->assertCount(3, $writtenKeys);
        $this->assertNotContains('template_source', $writtenKeys);
        $this->assertNotContains('template_register', $writtenKeys);
        $this->assertNotContains('template_schema', $writtenKeys);

    }//end testMissingSchemaIsSkippedWithWarning()


    /**
     * RegisterMapper::find throws → schema is skipped with a warning, no exception propagates.
     *
     * @return void
     */
    public function testMissingRegisterIsSkippedWithWarning(): void
    {
        $registerMapper = $this->createMock(RegisterMapper::class);
        $registerMapper->method('find')->willReturnCallback(
            function (string $id) {
                if ($id === 'templates') {
                    throw new DoesNotExistException('Register templates not found');
                }

                return $this->createEntityMock(Register::class, 11);
            }
        );
        $schemaMapper = $this->createMock(SchemaMapper::class);
        $schemaMapper->method('find')->willReturn($this->createEntityMock(Schema::class, 22));
        $this->bindMappers($registerMapper, $schemaMapper);

        $this->mockConfig->method('getValueString')->willReturn('');

        $this->mockLogger->expects($this->atLeastOnce())
            ->method('warning')
            ->with(
                $this->stringContains('Auto-default skipped schema: register not found'),
                $this->callback(fn (array $ctx) => ($ctx['registerSlug'] ?? null) === 'templates')
            );

        $writtenKeys = [];
        $this->mockConfig->method('setValueString')->willReturnCallback(
            function (string $app, string $key) use (&$writtenKeys): bool {
                $writtenKeys[] = $key;
                return true;
            }
        );

        $this->invokeHelper($this->jsonWithTwoSchemas());

        $this->assertCount(3, $writtenKeys);
        $this->assertNotContains('template_source', $writtenKeys);

    }//end testMissingRegisterIsSkippedWithWarning()


    /**
     * Empty `components.registers` → helper logs info and writes nothing.
     *
     * @return void
     */
    public function testEmptyComponentsRegistersInJsonNoOps(): void
    {
        // Mappers should never be touched — but bind anyway to keep
        // container.get() consistent with the other tests.
        $this->bindMappers(
            $this->createMock(RegisterMapper::class),
            $this->createMock(SchemaMapper::class)
        );
        $this->mockConfig->expects($this->never())->method('setValueString');

        $this->mockLogger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->stringContains('Auto-default skipped: no registers declared in JSON'));

        $this->invokeHelper(['components' => ['registers' => []]]);

    }//end testEmptyComponentsRegistersInJsonNoOps()


    /**
     * Unexpected exception inside the helper is caught and logged; never propagates.
     *
     * @return void
     */
    public function testHelperFailureDoesNotPropagate(): void
    {
        $registerMapper = $this->createMock(RegisterMapper::class);
        // Throw something other than DoesNotExistException — e.g., RuntimeException.
        $registerMapper->method('find')->willThrowException(new \RuntimeException('boom'));
        $schemaMapper = $this->createMock(SchemaMapper::class);
        $this->bindMappers($registerMapper, $schemaMapper);

        $this->mockConfig->method('getValueString')->willReturn('');

        $this->mockLogger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Auto-default helper failed'));

        // Must not throw.
        $this->invokeHelper($this->jsonWithSingleSchema());
        $this->addToAssertionCount(1);

    }//end testHelperFailureDoesNotPropagate()


}//end class

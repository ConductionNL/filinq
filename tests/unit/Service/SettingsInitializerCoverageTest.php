<?php

/**
 * Unit tests for SettingsInitializer: provisioning of the templateVersion config
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/specs/template-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use JsonSerializable;
use OCA\Filinq\Service\SettingsInitializer;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Service\ConfigurationService;
use OCA\OpenRegister\Service\RegisterService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * initialize() back-fills the templateVersion register and schema keys from
 * OpenRegister, preferring the register the templates live in, and never lets
 * a failure there abort initialisation.
 *
 * @covers \OCA\Filinq\Service\SettingsInitializer
 */
class SettingsInitializerCoverageTest extends TestCase {

	/**
	 * App config values as stored.
	 *
	 * @var array<string, string>
	 */
	private array $values = [];

	/**
	 * Every setValueString call as "key=value".
	 *
	 * @var array<int, string>
	 */
	private array $writes = [];

	/**
	 * A JSON-serialisable row, as OpenRegister's entities are.
	 *
	 * @param array<string, mixed> $data The serialised form.
	 *
	 * @return JsonSerializable The row.
	 */
	private static function row(array $data): JsonSerializable {
		return new class($data) implements JsonSerializable {
			/**
			 * Constructor.
			 *
			 * @param array<string, mixed> $data The serialised form.
			 *
			 * @return void
			 */
			public function __construct(private readonly array $data) {
			}//end __construct()

			/**
			 * The serialised form.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return $this->data;
			}//end jsonSerialize()
		};

	}//end row()

	/**
	 * The version of the shipped settings file.
	 *
	 * @return string The version.
	 */
	private function shippedVersion(): string {
		$settings = json_decode(
			(string)file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'),
			true
		);

		return (string)$settings['info']['version'];

	}//end shippedVersion()

	/**
	 * An initializer over an installed OpenRegister whose registers and schemas are given.
	 *
	 * @param array<int, JsonSerializable>|null $registers The registers, or null when RegisterService fails.
	 * @param array<string|int, JsonSerializable> $schemas Schemas by id; an absent id throws.
	 * @param LoggerInterface $logger The logger.
	 * @param ConfigurationService|null $configurationService The configuration service, a stub when null.
	 *
	 * @return SettingsInitializer The initializer.
	 */
	private function initializer(
		?array $registers,
		array $schemas,
		LoggerInterface $logger,
		?ConfigurationService $configurationService = null,
	): SettingsInitializer {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => ($this->values[$key] ?? $default)
		);
		$config->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->writes[] = $key . '=' . $value;
				$this->values[$key] = $value;
				return true;
			}
		);

		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('isInstalled')->willReturn(true);
		$appManager->method('getAppVersion')->willReturn('1.0.0');
		$appManager->method('isEnabledForUser')->willReturn(true);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);

		$registerService = $this->createMock(RegisterService::class);
		if ($registers === null) {
			$registerService->method('findAll')->willThrowException(new Exception('registers unavailable'));
		} else {
			$registerService->method('findAll')->willReturn($registers);
		}

		$schemaMapper = $this->createMock(SchemaMapper::class);
		$schemaMapper->method('find')->willReturnCallback(
			static function ($id) use ($schemas): JsonSerializable {
				if (isset($schemas[$id]) === false) {
					throw new Exception('no schema ' . $id);
				}

				return $schemas[$id];
			}
		);

		$configurationService ??= $this->createMock(ConfigurationService::class);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static fn (string $id): object => match ($id) {
				'OCA\OpenRegister\Service\ConfigurationService' => $configurationService,
				'OCA\OpenRegister\Service\RegisterService' => $registerService,
				'OCA\OpenRegister\Db\SchemaMapper' => $schemaMapper,
			}
		);

		return new SettingsInitializer(config: $config, container: $container, appManager: $appManager, logger: $logger);

	}//end initializer()

	/**
	 * The shipped version is already imported, so only provisioning can write.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->values = ['configuration_version' => $this->shippedVersion()];
		$this->writes = [];

	}//end setUp()

	/**
	 * The templates' own register wins over an earlier register with the schema.
	 *
	 * @return void
	 */
	public function testPrefersTheTemplatesRegister(): void {
		$this->values['template_register'] = '7';
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('info')
			->with('Provisioned templateVersion register/schema config', ['register' => '7', 'schema' => '31']);
		$logger->expects($this->never())->method('error');

		$result = $this->initializer(
			registers: [
				self::row(['id' => 3, 'schemas' => [30]]),
				self::row(['id' => 7, 'schemas' => [99, 21, 31]]),
			],
			schemas: [
				30 => self::row(['id' => 30, 'slug' => 'templateVersion']),
				21 => self::row(['id' => 21, 'slug' => 'template']),
				31 => self::row(['id' => 31, 'slug' => 'templateVersion']),
			],
			logger: $logger
		)->initialize();

		$this->assertSame(
			['templateVersion_register=7', 'templateVersion_schema=31', 'templateVersion_source=openregister'],
			$this->writes
		);
		$this->assertFalse($result['configuration']);
		$this->assertSame([], $result['errors']);

	}//end testPrefersTheTemplatesRegister()

	/**
	 * Without the templates' register, the first register carrying the schema is used.
	 *
	 * @return void
	 */
	public function testFallsBackToTheFirstRegisterWithTheSchema(): void {
		$this->values['template_register'] = '9';
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('info')
			->with('Provisioned templateVersion register/schema config', ['register' => '4', 'schema' => '41']);

		$this->initializer(
			registers: [
				self::row(['id' => 2]),
				self::row(['id' => 4, 'schemas' => [41]]),
				self::row(['id' => 5, 'schemas' => [41]]),
			],
			schemas: [41 => self::row(['id' => 41, 'slug' => 'templateVersion'])],
			logger: $logger
		)->initialize();

		$this->assertSame(
			['templateVersion_register=4', 'templateVersion_schema=41', 'templateVersion_source=openregister'],
			$this->writes
		);

	}//end testFallsBackToTheFirstRegisterWithTheSchema()

	/**
	 * No register with the schema writes nothing and warns.
	 *
	 * @return void
	 */
	public function testWarnsWhenNoRegisterHoldsTheSchema(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('warning')
			->with('templateVersion schema not found in any register; template version history will be unavailable');
		$logger->expects($this->never())->method('info');

		$this->initializer(
			registers: [self::row(['id' => 1, 'schemas' => [10, 11]])],
			schemas: [10 => self::row(['id' => 10, 'slug' => 'template'])],
			logger: $logger
		)->initialize();

		$this->assertSame([], $this->writes);

	}//end testWarnsWhenNoRegisterHoldsTheSchema()

	/**
	 * Keys that are both set already are left alone and OpenRegister is not asked.
	 *
	 * @return void
	 */
	public function testLeavesProvisionedKeysAlone(): void {
		$this->values['templateVersion_register'] = '1';
		$this->values['templateVersion_schema'] = '2';
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('info');
		$logger->expects($this->never())->method('warning');
		$logger->expects($this->never())->method('error');

		$result = $this->initializer(registers: null, schemas: [], logger: $logger)->initialize();

		$this->assertSame([], $this->writes);
		$this->assertSame([], $result['errors']);

	}//end testLeavesProvisionedKeysAlone()

	/**
	 * A failing RegisterService is logged and does not abort initialisation.
	 *
	 * @return void
	 */
	public function testProvisioningFailureIsLoggedNotFatal(): void {
		$this->values['templateVersion_register'] = '1';
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('error')
			->with('Failed to provision templateVersion config: registers unavailable', ['app' => 'filinq']);

		$result = $this->initializer(registers: null, schemas: [], logger: $logger)->initialize();

		$this->assertSame([], $this->writes);
		$this->assertSame([], $result['errors']);
		$this->assertStringContainsString('is up to date or newer than', $result['info'][0]);

	}//end testProvisioningFailureIsLoggedNotFatal()

	/**
	 * A newer shipped version is imported and then recorded, after provisioning.
	 *
	 * @return void
	 */
	public function testImportsANewerVersionAndRecordsIt(): void {
		$this->values['configuration_version'] = '0.0.1';
		$this->values['template_register'] = '7';
		$version = $this->shippedVersion();

		$configurationService = $this->createMock(ConfigurationService::class);
		$configurationService->expects($this->once())
			->method('importFromApp')
			->with('filinq', $this->callback(static fn (array $data): bool => $data['info']['version'] === $version), $version)
			->willReturn([]);

		$result = $this->initializer(
			registers: [self::row(['id' => 7, 'schemas' => [31]])],
			schemas: [31 => self::row(['id' => 31, 'slug' => 'templateVersion'])],
			logger: $this->createMock(LoggerInterface::class),
			configurationService: $configurationService
		)->initialize();

		$this->assertSame(
			[
				'templateVersion_register=7',
				'templateVersion_schema=31',
				'templateVersion_source=openregister',
				'configuration_version=' . $version,
			],
			$this->writes
		);
		$this->assertTrue($result['configuration']);
		$this->assertSame(['Configuration updated to version ' . $version], $result['info']);

	}//end testImportsANewerVersionAndRecordsIt()
}//end class

<?php

/**
 * Settings initializer: the order of checks, reads and writes per outcome.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\SettingsInitializer;
use OCA\OpenRegister\Service\ConfigurationService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Locks what initialize() answers and in which order it talks to its collaborators.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SettingsInitializerFlowTest extends TestCase {

	/**
	 * Every call to a collaborator, in order.
	 *
	 * @var array<int, string>
	 */
	private array $calls = [];

	/**
	 * The app config values.
	 *
	 * @var array<string, string>
	 */
	private array $values = [];

	/**
	 * The version of the shipped settings file.
	 *
	 * @return string The version.
	 */
	private function shippedVersion(): string {
		$settings = json_decode(
			json: (string)file_get_contents(filename: __DIR__ . '/../../../lib/Settings/filinq_register.json'),
			associative: true
		);

		return (string)$settings['info']['version'];
	}//end shippedVersion()

	/**
	 * An initializer over recording doubles.
	 *
	 * @param array<string, mixed> $world The OpenRegister version, enabled flag, installed apps and failures.
	 *
	 * @return SettingsInitializer The initializer.
	 */
	private function initializer(array $world): SettingsInitializer {
		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = ''): string {
				$this->calls[] = 'get ' . $key;
				return ($this->values[$key] ?? $default);
			}
		);
		$config->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->calls[] = 'set ' . $key . '=' . $value;
				$this->values[$key] = $value;
				return true;
			}
		);

		$appManager = $this->createMock(originalClassName: IAppManager::class);
		$appManager->method('isInstalled')->willReturnCallback(
			function () use ($world): bool {
				$this->calls[] = 'isInstalled';
				return $world['version'] !== null;
			}
		);
		$appManager->method('getAppVersion')->willReturnCallback(
			function () use ($world): string {
				$this->calls[] = 'getAppVersion';
				return (string)$world['version'];
			}
		);
		$appManager->method('isEnabledForUser')->willReturnCallback(
			function () use ($world): bool {
				$this->calls[] = 'isEnabledForUser';
				return $world['enabled'];
			}
		);
		$appManager->method('getInstalledApps')->willReturnCallback(
			function () use ($world): array {
				$this->calls[] = 'getInstalledApps';
				return $world['installed'];
			}
		);

		$configurationService = $this->createMock(originalClassName: ConfigurationService::class);
		$configurationService->method('importFromApp')->willReturnCallback(
			function (string $appId, array $data, string $version) use ($world): array {
				$this->calls[] = 'importFromApp ' . $appId . ' ' . $version . ' ' . (isset($data['info']) === true ? 'settings' : 'other');
				if ($world['importFails'] === true) {
					throw new RuntimeException(message: 'import broke');
				}

				return [];
			}
		);

		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			function (string $id) use ($world, $configurationService): object {
				$this->calls[] = 'container ' . substr($id, (int)strrpos($id, '\\') + 1);
				if ($id === 'OCA\OpenRegister\Service\ConfigurationService' && $world['containerFails'] === false) {
					return $configurationService;
				}

				throw new Exception(message: 'no ' . $id);
			}
		);

		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		$logger->method('error')->willReturnCallback(
			function (string $message): void {
				$this->calls[] = 'log error ' . $message;
			}
		);
		$logger->method('warning')->willReturnCallback(
			function (string $message): void {
				$this->calls[] = 'log warning ' . $message;
			}
		);

		return new SettingsInitializer(config: $config, container: $container, appManager: $appManager, logger: $logger);
	}//end initializer()

	/**
	 * A world where OpenRegister is installed, enabled and serves its configuration service.
	 *
	 * @param array<string, mixed> $changes What differs.
	 *
	 * @return array<string, mixed> The world.
	 */
	private function world(array $changes): array {
		return array_merge(
			[
				'version'        => '0.2.10',
				'enabled'        => true,
				'installed'      => ['openregister'],
				'containerFails' => false,
				'importFails'    => false,
			],
			$changes
		);
	}//end world()

	/**
	 * Without OpenRegister nothing is read or written.
	 *
	 * @return void
	 */
	public function testOpenRegisterMissing(): void {
		$result = $this->initializer(world: $this->world(changes: ['version' => null]))->initialize();

		$this->assertSame(
			expected: ['configuration' => false, 'errors' => ['OpenRegister is not installed or version is too low'], 'info' => []],
			actual: $result
		);
		$this->assertSame(
			expected: ['isInstalled', 'log error Failed to initialize Filinq: OpenRegister is not installed or version is too low'],
			actual: $this->calls
		);
	}//end testOpenRegisterMissing()

	/**
	 * An OpenRegister below the minimum version counts as missing.
	 *
	 * @return void
	 */
	public function testOpenRegisterTooOld(): void {
		$result = $this->initializer(world: $this->world(changes: ['version' => '0.2.9']))->initialize();

		$this->assertSame(expected: ['OpenRegister is not installed or version is too low'], actual: $result['errors']);
		$this->assertSame(expected: ['isInstalled', 'getAppVersion'], actual: array_slice($this->calls, 0, 2));
		$this->assertCount(expectedCount: 3, haystack: $this->calls);
	}//end testOpenRegisterTooOld()

	/**
	 * A disabled OpenRegister stops before the configuration service.
	 *
	 * @return void
	 */
	public function testOpenRegisterDisabled(): void {
		$result = $this->initializer(world: $this->world(changes: ['enabled' => false]))->initialize();

		$this->assertSame(
			expected: ['configuration' => false, 'errors' => ['OpenRegister is not enabled'], 'info' => []],
			actual: $result
		);
		$this->assertSame(
			expected: ['isInstalled', 'getAppVersion', 'isEnabledForUser', 'log error Failed to initialize Filinq: OpenRegister is not enabled'],
			actual: $this->calls
		);
	}//end testOpenRegisterDisabled()

	/**
	 * OpenRegister missing from the installed apps: no configuration service.
	 *
	 * @return void
	 */
	public function testConfigurationServiceNotListed(): void {
		$result = $this->initializer(world: $this->world(changes: ['installed' => ['files']]))->initialize();

		$this->assertSame(
			expected: ['OpenRegister configuration service is not available: Configuration service is not available.'],
			actual: $result['errors']
		);
		$this->assertSame(
			expected: ['isInstalled', 'getAppVersion', 'isEnabledForUser', 'getInstalledApps', 'log error Failed to initialize Filinq: OpenRegister configuration service is not available: Configuration service is not available.'],
			actual: $this->calls
		);
	}//end testConfigurationServiceNotListed()

	/**
	 * A container that cannot build the configuration service names why.
	 *
	 * @return void
	 */
	public function testConfigurationServiceFailsToBuild(): void {
		$result = $this->initializer(world: $this->world(changes: ['containerFails' => true]))->initialize();

		$this->assertSame(
			expected: ['OpenRegister configuration service is not available: no OCA\OpenRegister\Service\ConfigurationService'],
			actual: $result['errors']
		);
		$this->assertFalse(condition: $result['configuration']);
	}//end testConfigurationServiceFailsToBuild()

	/**
	 * An installed version at the shipped one: provisioning runs, nothing is imported.
	 *
	 * @return void
	 */
	public function testUpToDate(): void {
		$version = $this->shippedVersion();
		$this->values = ['configuration_version' => $version, 'templateVersion_register' => '4', 'templateVersion_schema' => '9'];

		$result = $this->initializer(world: $this->world(changes: []))->initialize();

		$this->assertSame(
			expected: ['configuration' => false, 'errors' => [], 'info' => ['Configuration version ' . $version . ' is up to date or newer than ' . $version]],
			actual: $result
		);
		$this->assertSame(
			expected: [
				'isInstalled',
				'getAppVersion',
				'isEnabledForUser',
				'getInstalledApps',
				'container ConfigurationService',
				'get configuration_version',
				'get templateVersion_register',
				'get templateVersion_schema',
			],
			actual: $this->calls
		);
	}//end testUpToDate()

	/**
	 * A newer installed version is up to date too.
	 *
	 * @return void
	 */
	public function testNewerInstalledVersion(): void {
		$this->values = ['configuration_version' => '999.0.0', 'templateVersion_register' => '4', 'templateVersion_schema' => '9'];

		$result = $this->initializer(world: $this->world(changes: []))->initialize();

		$this->assertSame(
			expected: ['Configuration version 999.0.0 is up to date or newer than ' . $this->shippedVersion()],
			actual: $result['info']
		);
		$this->assertFalse(condition: $result['configuration']);
	}//end testNewerInstalledVersion()

	/**
	 * An older version imports the settings and records the version.
	 *
	 * Provisioning fails here (no RegisterService) and is only logged.
	 *
	 * @return void
	 */
	public function testImport(): void {
		$version = $this->shippedVersion();

		$result = $this->initializer(world: $this->world(changes: []))->initialize();

		$this->assertSame(
			expected: ['configuration' => true, 'errors' => [], 'info' => ['Configuration updated to version ' . $version]],
			actual: $result
		);
		$this->assertSame(
			expected: [
				'isInstalled',
				'getAppVersion',
				'isEnabledForUser',
				'getInstalledApps',
				'container ConfigurationService',
				'get configuration_version',
				'get templateVersion_register',
				'get templateVersion_schema',
				'container RegisterService',
				'log error Failed to provision templateVersion config: no OCA\OpenRegister\Service\RegisterService',
				'importFromApp filinq ' . $version . ' settings',
				'set configuration_version=' . $version,
			],
			actual: $this->calls
		);
	}//end testImport()

	/**
	 * A failed import records no version and reports the error.
	 *
	 * @return void
	 */
	public function testImportFails(): void {
		$this->values = ['templateVersion_register' => '4', 'templateVersion_schema' => '9'];

		$result = $this->initializer(world: $this->world(changes: ['importFails' => true]))->initialize();

		$this->assertSame(
			expected: ['configuration' => false, 'errors' => ['import broke'], 'info' => []],
			actual: $result
		);
		$this->assertSame(
			expected: ['importFromApp filinq ' . $this->shippedVersion() . ' settings', 'log error Failed to initialize Filinq: import broke'],
			actual: array_slice($this->calls, -2)
		);
		$this->assertArrayNotHasKey(key: 'configuration_version', array: $this->values);
	}//end testImportFails()
}//end class

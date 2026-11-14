<?php

/**
 * Unit tests for EffectiveSettings and the consumers that read through it.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#task-3.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\EffectiveSettings;
use OCA\Filinq\Service\ObjectionDeadlineChecker;
use OCA\Filinq\Service\WooProfileService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * REQ-DDMTH-008: organisation override, then instance setting, then code default.
 *
 * @covers \OCA\Filinq\Service\EffectiveSettings
 * @covers \OCA\Filinq\Service\ObjectionDeadlineChecker
 * @covers \OCA\Filinq\Service\WooProfileService
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class EffectiveSettingsTest extends TestCase {

	/**
	 * @var ObjectService&MockObject
	 */
	private ObjectService $objects;

	/**
	 * The instance settings, by key.
	 *
	 * @var array<string, string>
	 */
	private array $instance = [];

	/**
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Build the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(ObjectService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
	}//end setUp()

	/**
	 * The instance settings double.
	 *
	 * @return IAppConfig
	 */
	private function config(): IAppConfig {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => ($this->instance[$key] ?? $default)
		);

		return $config;
	}//end config()

	/**
	 * The resolver over the given visible organisation settings rows.
	 *
	 * @param array<int, array<string, mixed>> $rows The rows OpenRegister answers for the active organisation.
	 *
	 * @return EffectiveSettings
	 */
	private function settings(array $rows): EffectiveSettings {
		$this->objects->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug, array $filters) use ($rows): array {
				$this->assertSame(['filinq', 'organisationSettings', []], [$registerSlug, $schemaSlug, $filters]);
				return $rows;
			}
		);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new EffectiveSettings(objectResolver: new DocumentObjectServiceResolver($container, $apps), config: $this->config(), logger: $this->logger);
	}//end settings()

	/**
	 * Demostad's 42 days win over the instance's 35; without an override the instance holds; without either, 28.
	 *
	 * @return void
	 */
	public function testTheOrganisationOverridesTheInstanceWhichOverridesTheCode(): void {
		$this->instance = ['publication_objection_period_days' => '35'];
		$this->assertSame(42, $this->settings(rows: [['uuid' => 'os-1', 'name' => 'Demostad', 'consentPeriodDays' => 42]])->consentPeriodDays());

		$this->objects = $this->createMock(ObjectService::class);
		$this->assertSame(35, $this->settings(rows: [['uuid' => 'os-2', 'name' => 'Voorbeeld', 'consentPeriodDays' => null]])->consentPeriodDays());

		$this->objects = $this->createMock(ObjectService::class);
		$this->instance = [];
		$this->assertSame(28, $this->settings(rows: [])->consentPeriodDays());
	}//end testTheOrganisationOverridesTheInstanceWhichOverridesTheCode()

	/**
	 * An out-of-range period is not used; an unknown setting resolves to null.
	 *
	 * @return void
	 */
	public function testAnUnusablePeriodFallsBackAndAnUnknownNameIsNull(): void {
		$this->instance = ['publication_objection_period_days' => '900'];
		$settings = $this->settings(rows: [['uuid' => 'os-1', 'consentPeriodDays' => 0]]);

		$this->assertSame(28, $settings->consentPeriodDays());
		$this->assertNull($settings->get(name: 'signingProvider'));
	}//end testAnUnusablePeriodFallsBackAndAnUnknownNameIsNull()

	/**
	 * The default huisstijl comes from the organisation only; the store is read once.
	 *
	 * @return void
	 */
	public function testTheDefaultHuisstijlComesFromTheOrganisationAndIsReadOnce(): void {
		$this->objects->expects($this->once())->method('searchObjectsBySlug')->willReturn([['uuid' => 'os-1', 'defaultHuisstijl' => ' hs-demostad ']]);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$settings = new EffectiveSettings(objectResolver: new DocumentObjectServiceResolver($container, $apps), config: $this->config(), logger: $this->logger);

		$this->assertSame('hs-demostad', $settings->defaultHuisstijl());
		$this->assertSame('hs-demostad', $settings->defaultHuisstijl());
	}//end testTheDefaultHuisstijlComesFromTheOrganisationAndIsReadOnce()

	/**
	 * Two visible objects mean the scope did not hold: none is applied. An unreadable store inherits.
	 *
	 * @return void
	 */
	public function testTwoVisibleObjectsOrAnUnreadableStoreApplyNoOverride(): void {
		$this->instance = ['publication_objection_period_days' => '35'];
		$this->logger->expects($this->exactly(2))->method('warning');

		$two = $this->settings(rows: [['uuid' => 'a', 'consentPeriodDays' => 10], ['uuid' => 'b', 'consentPeriodDays' => 20]]);
		$this->assertSame(35, $two->consentPeriodDays());
		$this->assertNull($two->defaultHuisstijl());

		$this->objects = $this->createMock(ObjectService::class);
		$this->objects->method('searchObjectsBySlug')->willThrowException(new RuntimeException('store down'));
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$down = new EffectiveSettings(objectResolver: new DocumentObjectServiceResolver($container, $apps), config: $this->config(), logger: $this->logger);
		$this->assertSame(35, $down->consentPeriodDays());
	}//end testTwoVisibleObjectsOrAnUnreadableStoreApplyNoOverride()

	/**
	 * The objection deadline and the WOO profile read the organisation's values.
	 *
	 * @return void
	 */
	public function testTheConsumersReadTheOrganisationsValues(): void {
		$this->instance = [
			'publication_objection_period_days' => '28',
			'filinq_woo_entity_profiles' => json_encode(['anonymize' => ['BSN'], 'keep' => ['DATE']]),
		];
		$settings = $this->settings(
			rows: [['uuid' => 'os-1', 'consentPeriodDays' => 42, 'anonymizationProfile' => ['anonymize' => ['PERSON', 'IBAN'], 'keepVisible' => ['ORGANIZATION']]]]
		);

		$checker = new ObjectionDeadlineChecker(
			logger: $this->logger,
			container: $this->createMock(ContainerInterface::class),
			appManager: $this->createMock(IAppManager::class),
			config: $this->config(),
			effective: $settings
		);
		$this->assertSame(42, $checker->getObjectionPeriodDays());

		$woo = new WooProfileService(appConfig: $this->config(), effective: $settings);
		$this->assertSame(['anonymize' => ['PERSON', 'IBAN'], 'keep' => ['ORGANIZATION']], $woo->getProfile());

		$instanceOnly = new WooProfileService(appConfig: $this->config());
		$this->assertSame(['anonymize' => ['BSN'], 'keep' => ['DATE']], $instanceOnly->getProfile());
	}//end testTheConsumersReadTheOrganisationsValues()
}//end class

<?php

/**
 * Tests for DetectionProviderConfig: the per-organisation overlay and endpoint settings.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Detection;

use InvalidArgumentException;
use OCA\Filinq\Service\Detection\DetectionProviderConfig;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\Detection\DetectionProviderConfig
 */
class DetectionProviderConfigTest extends TestCase {

	/**
	 * Stored values.
	 *
	 * @var array<string, string>
	 */
	private array $stored = [];

	/**
	 * Mocked app config backed by $stored.
	 *
	 * @var IAppConfig&MockObject
	 */
	private IAppConfig&MockObject $appConfig;

	/**
	 * Back the mock with an array.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default=''): string => ($app === 'filinq' ? ($this->stored[$key] ?? $default) : $default)
		);
		$this->appConfig->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->assertSame('filinq', $app);
				$this->stored[$key] = $value;
				return true;
			}
		);
	}

	/**
	 * Organisations not in the map, null, and unknown stored providers use default.
	 *
	 * @return void
	 */
	public function testProviderIsResolvedPerOrganisation(): void {
		$this->stored['detection.provider'] = '{"org-a":"llm","org-c":"cloud"}';
		$config = new DetectionProviderConfig(appConfig: $this->appConfig);

		$this->assertSame('llm', $config->providerFor(organisationUuid: 'org-a'));
		$this->assertSame('default', $config->providerFor(organisationUuid: 'org-b'));
		$this->assertSame('default', $config->providerFor(organisationUuid: 'org-c'));
		$this->assertSame('default', $config->providerFor(organisationUuid: null));
	}

	/**
	 * Choosing a provider for one organisation leaves another's choice alone; default removes it.
	 *
	 * @return void
	 */
	public function testUpdatingOneOrganisationLeavesTheOthers(): void {
		$this->stored['detection.provider'] = '{"org-b":"llm"}';
		$config = new DetectionProviderConfig(appConfig: $this->appConfig);

		$config->update(data: ['organisation' => 'org-a', 'provider' => 'llm']);
		$this->assertSame(['org-b' => 'llm', 'org-a' => 'llm'], $config->providerMap());

		$config->update(data: ['organisation' => 'org-b', 'provider' => 'default']);
		$this->assertSame(['org-a' => 'llm'], $config->providerMap());
		$this->assertArrayNotHasKey('detection.method', $this->stored);
	}

	/**
	 * Defaults: require-local on, confidence 0.8, timeout 30, empty endpoint.
	 *
	 * @return void
	 */
	public function testDefaults(): void {
		$config = new DetectionProviderConfig(appConfig: $this->appConfig);

		$this->assertSame(
			[
				'providers'         => [],
				'endpoint'          => '',
				'agentflow'         => '',
				'requireLocal'      => true,
				'defaultConfidence' => 0.8,
				'timeout'           => 30,
				'available'         => ['default', 'llm'],
			],
			$config->toArray()
		);
	}

	/**
	 * Stored endpoint settings are read back, trimmed and clamped.
	 *
	 * @return void
	 */
	public function testStoredSettingsAreReadBack(): void {
		$this->stored = [
			'detection.hermiq_endpoint'         => ' http://hermiq:8000/run ',
			'detection.hermiq_agentflow'        => ' detect-entities ',
			'detection.require_local'           => '0',
			'detection.llm_default_confidence'  => '7',
			'detection.llm_timeout'             => '0',
		];
		$config = new DetectionProviderConfig(appConfig: $this->appConfig);

		$this->assertSame('http://hermiq:8000/run', $config->endpoint());
		$this->assertSame('detect-entities', $config->agentflow());
		$this->assertFalse($config->requireLocal());
		$this->assertSame(1.0, $config->defaultConfidence());
		$this->assertSame(30, $config->timeout());
	}

	/**
	 * The admin fields are written as given.
	 *
	 * @return void
	 */
	public function testEndpointFieldsAreWritten(): void {
		$config = new DetectionProviderConfig(appConfig: $this->appConfig);

		$config->update(data: ['endpoint' => ' https://10.0.0.4/run ', 'agentflow' => ' flow-1 ', 'requireLocal' => false]);

		$this->assertSame('https://10.0.0.4/run', $this->stored['detection.hermiq_endpoint']);
		$this->assertSame('flow-1', $this->stored['detection.hermiq_agentflow']);
		$this->assertSame('0', $this->stored['detection.require_local']);
	}

	/**
	 * Invalid input is refused and nothing is written, not even the valid fields.
	 *
	 * @return void
	 */
	public function testInvalidInputWritesNothing(): void {
		$config = new DetectionProviderConfig(appConfig: $this->appConfig);
		$cases  = [
			'endpoint must be an http or https URL.'                => ['agentflow' => 'f', 'endpoint' => 'ftp://x/'],
			'Unknown detection provider.'                           => ['organisation' => 'org-a', 'provider' => 'cloud'],
			'An organisation is required to choose its provider.'   => ['provider' => 'llm'],
			'agentflow must be a string.'                           => ['agentflow' => ['x']],
		];

		foreach ($cases as $message => $data) {
			try {
				$config->update(data: $data);
				$this->fail('Expected a refusal: '.$message);
			} catch (InvalidArgumentException $e) {
				$this->assertSame($message, $e->getMessage());
			}
		}

		$this->assertSame([], $this->stored);
	}
}

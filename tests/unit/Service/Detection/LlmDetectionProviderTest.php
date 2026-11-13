<?php

/**
 * Tests for LlmDetectionProvider and DefaultDetectionProvider.
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
use OCA\Filinq\Service\Detection\AgentflowTarget;
use OCA\Filinq\Service\Detection\DefaultDetectionProvider;
use OCA\Filinq\Service\Detection\DetectionProviderConfig;
use OCA\Filinq\Service\Detection\DetectionRequest;
use OCA\Filinq\Service\Detection\EndpointLocality;
use OCA\Filinq\Service\Detection\HermiqAgentflowClient;
use OCA\Filinq\Service\Detection\LlmDetectionProvider;
use OCA\Filinq\Service\Detection\LlmEntityMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Detection\LlmDetectionProvider
 * @covers \OCA\Filinq\Service\Detection\DefaultDetectionProvider
 * @uses   \OCA\Filinq\Service\Detection\DetectionResult
 * @uses   \OCA\Filinq\Service\Detection\LlmEntityMapper
 * @uses   \OCA\Filinq\Service\Detection\DetectionRequest
 * @uses   \OCA\Filinq\Service\Detection\AgentflowTarget
 */
class LlmDetectionProviderTest extends TestCase {

	/**
	 * Mocked config.
	 *
	 * @var DetectionProviderConfig&MockObject
	 */
	private DetectionProviderConfig&MockObject $config;

	/**
	 * Mocked locality.
	 *
	 * @var EndpointLocality&MockObject
	 */
	private EndpointLocality&MockObject $locality;

	/**
	 * Mocked hermiq client.
	 *
	 * @var HermiqAgentflowClient&MockObject
	 */
	private HermiqAgentflowClient&MockObject $client;

	/**
	 * A configured local endpoint.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->config   = $this->createMock(DetectionProviderConfig::class);
		$this->locality = $this->createMock(EndpointLocality::class);
		$this->client   = $this->createMock(HermiqAgentflowClient::class);
		$this->config->method('agentflow')->willReturn('detect-entities');
		$this->config->method('timeout')->willReturn(12);
		$this->config->method('defaultConfidence')->willReturn(0.7);
	}

	/**
	 * The provider under test.
	 *
	 * @param string $endpoint     The configured endpoint.
	 * @param bool   $requireLocal The toggle.
	 *
	 * @return LlmDetectionProvider The provider.
	 */
	private function provider(string $endpoint='http://hermiq:8000/run', bool $requireLocal=true): LlmDetectionProvider {
		$this->config->method('endpoint')->willReturn($endpoint);
		$this->config->method('requireLocal')->willReturn($requireLocal);

		return new LlmDetectionProvider(config: $this->config, locality: $this->locality, client: $this->client, mapper: new LlmEntityMapper());
	}

	/**
	 * The two providers report their stable ids; the default is always available and adds nothing.
	 *
	 * @return void
	 */
	public function testProvidersReportAStableId(): void {
		$default = new DefaultDetectionProvider();

		$this->assertSame('llm', $this->provider()->id());
		$this->assertSame('default', $default->id());
		$this->assertTrue($default->isAvailable());

		$result = $default->detect(request: new DetectionRequest(fileId: 1, entityTypes: null), text: 'Jan');
		$this->assertSame([], $result->entities);
		$this->assertSame('default', $result->providerUsed);
		$this->assertFalse($result->fellBack);
		$this->assertSame(['detectionProvider' => 'default', 'detectionFellBack' => false, 'detectionWarning' => null], $result->toPayload());
	}

	/**
	 * A public endpoint with require-local on is unavailable, and nothing is sent.
	 *
	 * @return void
	 */
	public function testNonLocalEndpointRefused(): void {
		$this->locality->method('isLocal')->with('https://llm.example.com/run')->willReturn(false);
		$this->client->expects($this->never())->method('entities');
		$this->client->expects($this->never())->method('answers');
		$provider = $this->provider(endpoint: 'https://llm.example.com/run');

		$this->assertFalse($provider->isAvailable());
		$this->assertSame('not-local', $provider->unavailableReason());
		$this->assertSame(['available' => false, 'reason' => 'not-local'], $provider->testConnection());

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('LLM detection is unavailable: not-local');
		$provider->detect(request: new DetectionRequest(fileId: 3, entityTypes: null), text: 'secret text');
	}

	/**
	 * Without an endpoint the provider is unconfigured; with require-local off a public host is allowed.
	 *
	 * @return void
	 */
	public function testUnconfiguredAndRequireLocalOff(): void {
		$this->assertSame('unconfigured', $this->provider(endpoint: '')->unavailableReason());

		$this->setUp();
		$this->locality->expects($this->never())->method('isLocal');
		$this->assertTrue($this->provider(endpoint: 'https://llm.example.com/run', requireLocal: false)->isAvailable());
	}

	/**
	 * A configured local endpoint is called with the text and types, and its entities are mapped.
	 *
	 * @return void
	 */
	public function testAConfiguredLocalEndpointDetectsEntities(): void {
		$this->locality->method('isLocal')->willReturn(true);
		$this->client->expects($this->once())
			->method('entities')
			->with(new AgentflowTarget(endpoint: 'http://hermiq:8000/run', agentflow: 'detect-entities', timeout: 12), 'Jan woont hier', ['PERSON'])
			->willReturn([['value' => 'Jan', 'type' => 'PER', 'start' => 0, 'end' => 3]]);

		$result = $this->provider()->detect(request: new DetectionRequest(fileId: 3, entityTypes: ['PERSON']), text: 'Jan woont hier');

		$this->assertSame('llm', $result->providerUsed);
		$this->assertFalse($result->fellBack);
		$this->assertSame('PERSON', $result->entities[0]['type']);
		$this->assertSame(0.7, $result->entities[0]['confidence']);
		$this->assertSame('llm', $result->entities[0]['detectionMethod']);
	}

	/**
	 * The connectivity test reports an endpoint that does not answer, and one that does.
	 *
	 * @return void
	 */
	public function testConnectionReportsReachability(): void {
		$this->locality->method('isLocal')->willReturn(true);
		$this->client->method('answers')->with('http://hermiq:8000/run', 5)->willReturnOnConsecutiveCalls(false, true);
		$provider = $this->provider();

		$this->assertSame(['available' => false, 'reason' => 'unreachable'], $provider->testConnection());
		$this->assertSame(['available' => true, 'reason' => null], $provider->testConnection());
	}
}

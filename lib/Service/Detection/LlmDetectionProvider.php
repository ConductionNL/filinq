<?php

/**
 * LLM Detection Provider
 *
 * Entity detection by a local hermiq agentflow.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Detection
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Detection;

use RuntimeException;

/**
 * Detects entities by calling the configured local hermiq agentflow.
 *
 * Unavailable while the endpoint or agentflow is unset, and while
 * `require_local` is on and the endpoint is not a loopback or private host. An
 * unavailable provider never sends anything, so no document text leaves the
 * perimeter. Its entities come back through {@see LlmEntityMapper}.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */
class LlmDetectionProvider implements DetectionProviderInterface {

	/**
	 * The provider id.
	 *
	 * @var string
	 */
	public const ID = 'llm';

	/**
	 * Constructor.
	 *
	 * @param DetectionProviderConfig $config   The detection settings.
	 * @param EndpointLocality        $locality The locality check for the endpoint.
	 * @param HermiqAgentflowClient   $client   The agentflow HTTP call.
	 * @param LlmEntityMapper         $mapper   The output mapper.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function __construct(
		private readonly DetectionProviderConfig $config,
		private readonly EndpointLocality $locality,
		private readonly HermiqAgentflowClient $client,
		private readonly LlmEntityMapper $mapper,
	) {
	}//end __construct()

	/**
	 * Detect entities in a text through the agentflow.
	 *
	 * @param DetectionRequest $request The file (not sent; the text is) and the enabled types.
	 * @param string           $text    The text.
	 *
	 * @return DetectionResult The mapped entities, positions relative to $text.
	 *
	 * @throws RuntimeException When the provider is unavailable, the call fails, or the answer is malformed.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function detect(DetectionRequest $request, string $text): DetectionResult {
		$reason = $this->unavailableReason();
		if ($reason !== null) {
			throw new RuntimeException('LLM detection is unavailable: '.$reason);
		}

		$items = $this->client->entities(
			target: new AgentflowTarget(
				endpoint: $this->config->endpoint(),
				agentflow: $this->config->agentflow(),
				timeout: $this->config->timeout()
			),
			text: $text,
			entityTypes: $request->entityTypes
		);

		return new DetectionResult(
			entities: $this->mapper->map(items: $items, defaultConfidence: $this->config->defaultConfidence()),
			providerUsed: self::ID
		);
	}//end detect()

	/**
	 * The provider id.
	 *
	 * @return string `llm`.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.1
	 */
	public function id(): string {
		return self::ID;
	}//end id()

	/**
	 * Whether the provider is configured and allowed to call its endpoint.
	 *
	 * @return bool True when it may be called.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function isAvailable(): bool {
		return $this->unavailableReason() === null;
	}//end isAvailable()

	/**
	 * Why the provider may not be called, or null when it may.
	 *
	 * @return string|null `unconfigured`, `not-local`, or null.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function unavailableReason(): ?string {
		$endpoint = $this->config->endpoint();
		if ($endpoint === '' || $this->config->agentflow() === '') {
			return 'unconfigured';
		}

		if ($this->allowedEndpoint(endpoint: $endpoint) === false) {
			return 'not-local';
		}

		return null;
	}//end unavailableReason()

	/**
	 * Whether the endpoint may be called: any endpoint when require-local is off, else a local one.
	 *
	 * @param string $endpoint The configured endpoint.
	 *
	 * @return bool
	 */
	private function allowedEndpoint(string $endpoint): bool {
		if ($this->config->requireLocal() === false) {
			return true;
		}

		return $this->locality->isLocal(url: $endpoint);
	}//end allowedEndpoint()

	/**
	 * Test the configuration: the reason it may not be called, else whether the endpoint answers.
	 *
	 * @return array{available: bool, reason: string|null}
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.1
	 */
	public function testConnection(): array {
		$reason = $this->unavailableReason();
		if ($reason !== null) {
			return ['available' => false, 'reason' => $reason];
		}

		if ($this->client->answers(endpoint: $this->config->endpoint(), timeout: min(5, $this->config->timeout())) === false) {
			return ['available' => false, 'reason' => 'unreachable'];
		}

		return ['available' => true, 'reason' => null];
	}//end testConnection()
}//end class

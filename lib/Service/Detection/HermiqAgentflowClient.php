<?php

/**
 * Hermiq Agentflow Client
 *
 * The HTTP call to a local hermiq agentflow that detects entities in text.
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

use OCP\Http\Client\IClientService;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

/**
 * Posts text to the configured agentflow and returns its `state.entities`.
 *
 * Request body: `{"agentflow": <ref>, "input": {"text": ..., "entityTypes": [...]|null}}`.
 * Expected answer: a JSON object whose `state.entities` holds the entity list.
 * Local addresses are allowed on purpose: the endpoint is required to be local.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */
class HermiqAgentflowClient {

	/**
	 * Constructor.
	 *
	 * @param IClientService $clientService Nextcloud's HTTP client factory.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function __construct(
		private readonly IClientService $clientService,
	) {
	}//end __construct()

	/**
	 * Run the agentflow over one text.
	 *
	 * @param string                  $endpoint    The agentflow-run URL.
	 * @param string                  $agentflow   The agentflow reference.
	 * @param string                  $text        The text.
	 * @param array<int, string>|null $entityTypes The enabled types, or null for all.
	 * @param int                     $timeout     Seconds before the call is abandoned.
	 *
	 * @return mixed The `state.entities` value, unvalidated.
	 *
	 * @throws RuntimeException When the call fails or times out.
	 * @throws UnexpectedValueException When the answer carries no `state.entities`.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function entities(string $endpoint, string $agentflow, string $text, ?array $entityTypes, int $timeout): mixed {
		try {
			$response = $this->clientService->newClient()->post(
				$endpoint,
				[
					'json'      => [
						'agentflow' => $agentflow,
						'input'     => ['text' => $text, 'entityTypes' => $entityTypes],
					],
					'timeout'   => $timeout,
					'nextcloud' => ['allow_local_address' => true],
				]
			);
		} catch (Throwable $e) {
			throw new RuntimeException('The agentflow could not be reached: '.$e->getMessage(), 0, $e);
		}

		$decoded = json_decode((string) $response->getBody(), true);
		if (is_array($decoded) === false
			|| is_array($decoded['state'] ?? null) === false
			|| array_key_exists('entities', $decoded['state']) === false
		) {
			throw new UnexpectedValueException('The agentflow answer carries no state.entities.');
		}

		return $decoded['state']['entities'];
	}//end entities()

	/**
	 * Whether the endpoint answers at all. Any HTTP answer counts; only a failed connection does not.
	 *
	 * @param string $endpoint The agentflow-run URL.
	 * @param int    $timeout  Seconds before giving up.
	 *
	 * @return bool True when something answered.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.1
	 */
	public function answers(string $endpoint, int $timeout): bool {
		try {
			$this->clientService->newClient()->get(
				$endpoint,
				[
					'timeout'     => $timeout,
					'http_errors' => false,
					'nextcloud'   => ['allow_local_address' => true],
				]
			);
			return true;
		} catch (Throwable) {
			return false;
		}
	}//end answers()
}//end class

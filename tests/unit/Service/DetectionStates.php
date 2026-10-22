<?php

/**
 * OpenRegister detection states for tests, built from the real BackendState shape.
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
 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymiserBackendStateClient;
use OCA\OpenRegister\Service\Anonymisation\BackendInfo;
use OCA\OpenRegister\Service\Anonymisation\BackendState;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Builders for AnonymiserBackendStateClient over a given OpenRegister state.
 */
final class DetectionStates {

	/**
	 * Build a client over a container that answers with the given OR service.
	 *
	 * @param BackendState|null $state The state OpenRegister holds, null for a
	 *                                 container that cannot resolve the service.
	 *
	 * @return AnonymiserBackendStateClient The client.
	 */
	public static function clientOver(?BackendState $state): AnonymiserBackendStateClient {
		$container = new class($state) implements ContainerInterface {
			public function __construct(
				private readonly ?BackendState $state,
			) {
			}

			public function get(string $id) {
				if ($id !== 'OCA\OpenRegister\Service\Anonymisation\AnonymisationBackendService' || $this->state === null) {
					throw new RuntimeException('No service registered under ' . $id);
				}

				return new class($this->state) {
					public function __construct(
						private readonly BackendState $state,
					) {
					}

					public function getState(): BackendState {
						return $this->state;
					}
				};
			}

			public function has(string $id): bool {
				return $this->state !== null;
			}
		};

		return new AnonymiserBackendStateClient(container: $container, logger: new NullLogger());

	}//end clientOver()

	/**
	 * A state as OpenRegister's AnonymisationBackendService builds it.
	 *
	 * @param bool $enabled Whether entity recognition is on.
	 * @param string $active The active method.
	 * @param string $effective The effective method.
	 * @param array<string, bool> $available Availability per method; regex is always true.
	 *
	 * @return BackendState The state.
	 */
	public static function orState(bool $enabled, string $active, string $effective, array $available = []): BackendState {
		$backends = [];
		foreach (['regex', 'presidio', 'openanonymiser', 'llm', 'hybrid'] as $method) {
			$isUp = ($available[$method] ?? ($method === 'regex'));
			$backends[$method] = new BackendInfo(
				name: $method,
				available: $isUp,
				configured: $isUp,
				lastProbedAt: '2026-09-29T08:00:00+00:00',
				latencyMs: 0
			);
		}

		return new BackendState(
			entityRecognitionEnabled: $enabled,
			activeMethod: $active,
			effectiveMethod: $effective,
			backends: $backends
		);

	}//end orState()
}//end class

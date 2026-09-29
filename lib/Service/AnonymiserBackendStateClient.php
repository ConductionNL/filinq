<?php

/**
 * Anonymiser Backend State Client
 *
 * Reads OpenRegister's AnonymisationBackendService, which owns the choice of
 * entity-detection backend, its probes and their availability (ADR-017).
 * Filinq never asks IAppManager or AppAPI itself.
 *
 * When OpenRegister cannot answer, the client says the state is unknown. It
 * used to say `regex`, which is a real backend that finds real BSNs, so an
 * instance with no detector at all read as one with a weak detector, and an
 * anonymisation run had no way to tell (anonymisation-fails-closed-without-a-detector).
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-fails-closed-without-a-detector/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use JsonSerializable;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Wraps the OpenRegister AnonymisationBackendService::getState() call.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-fails-closed-without-a-detector/tasks.md#task-1
 */
class AnonymiserBackendStateClient {

	/**
	 * The class OpenRegister declares, under its Anonymisation namespace.
	 *
	 * @var string
	 */
	public const OR_SERVICE = 'OCA\OpenRegister\Service\Anonymisation\AnonymisationBackendService';

	/**
	 * Refusal: filinq could not read which detector is live.
	 *
	 * @var string
	 */
	public const REFUSE_UNKNOWN = 'detection_state_unknown';

	/**
	 * Refusal: entity recognition is switched off on this instance.
	 *
	 * @var string
	 */
	public const REFUSE_DISABLED = 'detection_disabled';

	/**
	 * Refusal: the backend OpenRegister would use says it is unavailable.
	 *
	 * @var string
	 */
	public const REFUSE_UNAVAILABLE = 'detection_backend_unavailable';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container DI container used for lazy service resolution.
	 * @param LoggerInterface $logger Logger for debug/warning output.
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Retrieve the current anonymisation backend state.
	 *
	 * Keys: `known` (bool, false when OpenRegister could not be read),
	 * `entityRecognitionEnabled` (bool|null), `activeMethod` and
	 * `effectiveMethod` (string|null, OpenRegister's own values),
	 * `effectiveAvailable` (bool, the probe of the effective backend) and
	 * `backends` (array, OpenRegister's per-backend records).
	 *
	 * @return array{known: bool, entityRecognitionEnabled: bool|null, activeMethod: string|null,
	 *               effectiveMethod: string|null, effectiveAvailable: bool, backends: array<string, mixed>}
	 *
	 * @spec openspec/changes/anonymisation-fails-closed-without-a-detector/tasks.md#task-1
	 */
	public function getState(): array {
		try {
			$service = $this->container->get(self::OR_SERVICE);
			$raw = $service->getState();
		} catch (\Throwable $e) {
			$this->logger->warning(
				'OpenRegister AnonymisationBackendService could not be read; the detection state is unknown',
				['exception' => $e->getMessage()]
			);
			return $this->unknownState();
		}

		if ($raw instanceof JsonSerializable === true) {
			$raw = $raw->jsonSerialize();
		}

		if (is_array($raw) === false || isset($raw['effectiveMethod']) === false) {
			$this->logger->warning('OpenRegister AnonymisationBackendService answered in a shape filinq does not know');
			return $this->unknownState();
		}

		$backends = (array)($raw['backends'] ?? []);
		$effective = (string)$raw['effectiveMethod'];

		return [
			'known' => true,
			'entityRecognitionEnabled' => (bool)($raw['entityRecognitionEnabled'] ?? false),
			'activeMethod' => (string)($raw['activeMethod'] ?? $effective),
			'effectiveMethod' => $effective,
			'effectiveAvailable' => (($backends[$effective]['available'] ?? false) === true),
			'backends' => $backends,
		];

	}//end getState()

	/**
	 * Why an anonymisation run must be refused under this state, or null when a
	 * detector is live.
	 *
	 * @param array<string, mixed> $state A state from {@see getState()}.
	 *
	 * @return string|null One of the REFUSE_* reasons, or null.
	 *
	 * @spec openspec/changes/anonymisation-fails-closed-without-a-detector/tasks.md#task-2
	 */
	public function refusalReason(array $state): ?string {
		if (($state['known'] ?? false) !== true) {
			return self::REFUSE_UNKNOWN;
		}

		if (($state['entityRecognitionEnabled'] ?? false) !== true) {
			return self::REFUSE_DISABLED;
		}

		if (($state['effectiveAvailable'] ?? false) !== true) {
			return self::REFUSE_UNAVAILABLE;
		}

		return null;

	}//end refusalReason()

	/**
	 * The state when OpenRegister could not be read.
	 *
	 * @return array{known: false, entityRecognitionEnabled: null, activeMethod: null,
	 *               effectiveMethod: null, effectiveAvailable: false, backends: array<string, mixed>}
	 */
	private function unknownState(): array {
		return [
			'known' => false,
			'entityRecognitionEnabled' => null,
			'activeMethod' => null,
			'effectiveMethod' => null,
			'effectiveAvailable' => false,
			'backends' => [],
		];

	}//end unknownState()
}//end class

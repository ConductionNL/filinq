<?php

/**
 * Detection Provider Config
 *
 * The detection-provider settings: which provider each organisation uses, and
 * where the local hermiq agentflow lives.
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

use InvalidArgumentException;
use OCA\Filinq\Kernel\AppIdentity;
use OCP\IAppConfig;

/**
 * Reads and writes the detection-provider settings in IAppConfig.
 *
 * The provider is a per-organisation overlay: `detection.provider` holds a JSON
 * object from organisation UUID to provider id, and an organisation that is not
 * in it uses `default`. OpenRegister's own global detection-method setting is
 * never read or written here. The endpoint settings are instance-wide.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */
class DetectionProviderConfig {

	/**
	 * Per-organisation provider map key.
	 *
	 * @var string
	 */
	public const KEY_PROVIDER = 'detection.provider';

	/**
	 * Hermiq agentflow-run endpoint URL key.
	 *
	 * @var string
	 */
	public const KEY_ENDPOINT = 'detection.hermiq_endpoint';

	/**
	 * Hermiq agentflow reference key.
	 *
	 * @var string
	 */
	public const KEY_AGENTFLOW = 'detection.hermiq_agentflow';

	/**
	 * Require-local toggle key.
	 *
	 * @var string
	 */
	public const KEY_REQUIRE_LOCAL = 'detection.require_local';

	/**
	 * Default confidence for an LLM entity without a score.
	 *
	 * @var string
	 */
	public const KEY_DEFAULT_CONFIDENCE = 'detection.llm_default_confidence';

	/**
	 * Timeout in seconds for one agentflow call.
	 *
	 * @var string
	 */
	public const KEY_TIMEOUT = 'detection.llm_timeout';

	/**
	 * The provider ids an organisation may select.
	 *
	 * @var list<string>
	 */
	public const PROVIDERS = [DefaultDetectionProvider::ID, LlmDetectionProvider::ID];

	/**
	 * Confidence used when nothing is configured.
	 *
	 * @var float
	 */
	private const FALLBACK_CONFIDENCE = 0.8;

	/**
	 * Timeout used when nothing is configured.
	 *
	 * @var int
	 */
	private const FALLBACK_TIMEOUT = 30;

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $appConfig App configuration.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.2
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
	) {
	}//end __construct()

	/**
	 * The provider id an organisation uses.
	 *
	 * @param string|null $organisationUuid The organisation, or null when none is active.
	 *
	 * @return string `default` unless the organisation chose a known provider.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.2
	 */
	public function providerFor(?string $organisationUuid): string {
		if ($organisationUuid === null || $organisationUuid === '') {
			return DefaultDetectionProvider::ID;
		}

		$provider = ($this->providerMap()[$organisationUuid] ?? DefaultDetectionProvider::ID);
		if (in_array($provider, self::PROVIDERS, true) === false) {
			return DefaultDetectionProvider::ID;
		}

		return $provider;
	}//end providerFor()

	/**
	 * Every organisation's explicit provider choice.
	 *
	 * @return array<string, string> Organisation UUID to provider id.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.2
	 */
	public function providerMap(): array {
		$decoded = json_decode($this->appConfig->getValueString(AppIdentity::APP_ID, self::KEY_PROVIDER, '{}'), true);
		if (is_array($decoded) === false) {
			return [];
		}

		$map = [];
		foreach ($decoded as $organisation => $provider) {
			if (is_string($organisation) === true && is_string($provider) === true) {
				$map[$organisation] = $provider;
			}
		}

		return $map;
	}//end providerMap()

	/**
	 * The hermiq endpoint URL, trimmed.
	 *
	 * @return string The URL, or empty when unset.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function endpoint(): string {
		return trim($this->appConfig->getValueString(AppIdentity::APP_ID, self::KEY_ENDPOINT, ''));
	}//end endpoint()

	/**
	 * The hermiq agentflow reference, trimmed.
	 *
	 * @return string The reference, or empty when unset.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function agentflow(): string {
		return trim($this->appConfig->getValueString(AppIdentity::APP_ID, self::KEY_AGENTFLOW, ''));
	}//end agentflow()

	/**
	 * Whether the endpoint must be a loopback or private-network host. On unless explicitly '0'.
	 *
	 * @return bool The toggle.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function requireLocal(): bool {
		return $this->appConfig->getValueString(AppIdentity::APP_ID, self::KEY_REQUIRE_LOCAL, '1') !== '0';
	}//end requireLocal()

	/**
	 * The confidence for an LLM entity without a score, clamped to 0..1.
	 *
	 * @return float The confidence.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.2
	 */
	public function defaultConfidence(): float {
		$raw = $this->appConfig->getValueString(AppIdentity::APP_ID, self::KEY_DEFAULT_CONFIDENCE, '');
		if (is_numeric($raw) === false) {
			return self::FALLBACK_CONFIDENCE;
		}

		return max(0.0, min(1.0, (float) $raw));
	}//end defaultConfidence()

	/**
	 * The timeout for one agentflow call, at least one second.
	 *
	 * @return int Seconds.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function timeout(): int {
		$raw = $this->appConfig->getValueString(AppIdentity::APP_ID, self::KEY_TIMEOUT, '');
		if (is_numeric($raw) === false || (int) $raw < 1) {
			return self::FALLBACK_TIMEOUT;
		}

		return (int) $raw;
	}//end timeout()

	/**
	 * The settings as the admin view reads them.
	 *
	 * @return array<string, mixed> providers, endpoint, agentflow, requireLocal, defaultConfidence,
	 *                              timeout and the available provider ids.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.1
	 */
	public function toArray(): array {
		return [
			'providers'         => $this->providerMap(),
			'endpoint'          => $this->endpoint(),
			'agentflow'         => $this->agentflow(),
			'requireLocal'      => $this->requireLocal(),
			'defaultConfidence' => $this->defaultConfidence(),
			'timeout'           => $this->timeout(),
			'available'         => self::PROVIDERS,
		];
	}//end toArray()

	/**
	 * Apply an update from the admin view. Only the keys present are changed.
	 *
	 * `organisation` + `provider` set one organisation's choice; choosing
	 * `default` removes the organisation from the map, so the map only holds
	 * opt-ins. Everything is validated before anything is written.
	 *
	 * @param array<string, mixed> $data The submitted fields.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When a field is invalid; nothing is written then.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.1
	 */
	public function update(array $data): void {
		$writes = [];

		if (array_key_exists('provider', $data) === true) {
			$writes[self::KEY_PROVIDER] = $this->updatedProviderMap(data: $data);
		}

		if (array_key_exists('endpoint', $data) === true) {
			$writes[self::KEY_ENDPOINT] = $this->validEndpoint(value: $data['endpoint']);
		}

		if (array_key_exists('agentflow', $data) === true) {
			if (is_string($data['agentflow']) === false) {
				throw new InvalidArgumentException('agentflow must be a string.');
			}

			$writes[self::KEY_AGENTFLOW] = trim($data['agentflow']);
		}

		if (array_key_exists('requireLocal', $data) === true) {
			$writes[self::KEY_REQUIRE_LOCAL] = $this->flag(value: $data['requireLocal']);
		}

		foreach ($writes as $key => $value) {
			$this->appConfig->setValueString(AppIdentity::APP_ID, $key, $value);
		}
	}//end update()

	/**
	 * The provider map with one organisation's choice applied, JSON-encoded.
	 *
	 * @param array<string, mixed> $data The submitted fields.
	 *
	 * @return string The JSON map.
	 *
	 * @throws InvalidArgumentException When the organisation or provider is invalid.
	 */
	private function updatedProviderMap(array $data): string {
		$organisation = $data['organisation'] ?? null;
		if (is_string($organisation) === false || trim($organisation) === '') {
			throw new InvalidArgumentException('An organisation is required to choose its provider.');
		}

		$provider = $data['provider'];
		if (is_string($provider) === false || in_array($provider, self::PROVIDERS, true) === false) {
			throw new InvalidArgumentException('Unknown detection provider.');
		}

		$map = $this->providerMap();
		unset($map[trim($organisation)]);
		if ($provider !== DefaultDetectionProvider::ID) {
			$map[trim($organisation)] = $provider;
		}

		return (string) json_encode((object) $map);
	}//end updatedProviderMap()

	/**
	 * An endpoint as stored: empty, or an http(s) URL with a host.
	 *
	 * @param mixed $value The submitted value.
	 *
	 * @return string The trimmed URL.
	 *
	 * @throws InvalidArgumentException When it is not such a URL.
	 */
	private function validEndpoint(mixed $value): string {
		if (is_string($value) === false) {
			throw new InvalidArgumentException('endpoint must be a string.');
		}

		$url = trim($value);
		if ($url === '') {
			return '';
		}

		$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
		$host   = (string) parse_url($url, PHP_URL_HOST);
		if (in_array($scheme, ['http', 'https'], true) === false || $host === '') {
			throw new InvalidArgumentException('endpoint must be an http or https URL.');
		}

		return $url;
	}//end validEndpoint()

	/**
	 * A boolean-ish value as '1' or '0'.
	 *
	 * @param mixed $value The submitted value.
	 *
	 * @return string '1' or '0'.
	 */
	private function flag(mixed $value): string {
		if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
			return '1';
		}

		return '0';
	}//end flag()
}//end class

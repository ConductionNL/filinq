<?php

/**
 * Signer Auth Settings
 *
 * The admin configuration of the signer identity rails: which provider, the
 * broker's non-secret parameters, the credential reference, and the gate's
 * evidence window.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SignerAuth
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SignerAuth;

use InvalidArgumentException;
use OCP\IAppConfig;

/**
 * Reads and validates the `signer_auth_*` app config keys.
 *
 * No key holds a secret. The broker's client secret lives in OpenRegister's
 * credential broker and this class stores only its UUID, the
 * `credentialRef` (ADR-064); a value that is not a UUID is refused, so a
 * secret pasted into the field is never written.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class SignerAuthSettings {

	/**
	 * The providers an admin can select.
	 *
	 * @var list<string>
	 */
	public const SELECTABLE_PROVIDERS = ['nextcloud-session', 'oidc-broker'];

	/**
	 * Settings field => app config key.
	 *
	 * @var array<string, string>
	 */
	private const KEYS = [
		'provider' => 'signer_auth_provider',
		'issuer' => 'signer_auth_oidc_issuer',
		'clientId' => 'signer_auth_oidc_client_id',
		'authorizationEndpoint' => 'signer_auth_oidc_authorization_endpoint',
		'tokenEndpoint' => 'signer_auth_oidc_token_endpoint',
		'redirectUri' => 'signer_auth_oidc_redirect_uri',
		'scopes' => 'signer_auth_oidc_scopes',
		'acrMapping' => 'signer_auth_oidc_acr_mapping',
		'credentialRef' => 'signer_auth_oidc_credential_ref',
		'evidenceMaxAgeMinutes' => 'signer_auth_evidence_max_age_minutes',
		'guardianMinimumAssurance' => 'signer_auth_guardian_min_assurance',
	];

	/**
	 * Defaults: the session provider, a 15-minute window, no guardian raise.
	 *
	 * @var array<string, string>
	 */
	private const DEFAULTS = [
		'provider' => 'nextcloud-session',
		'scopes' => 'openid',
		'evidenceMaxAgeMinutes' => '15',
		'guardianMinimumAssurance' => 'low',
	];

	/**
	 * The assurance scale.
	 *
	 * @var AssuranceLevel
	 */
	private readonly AssuranceLevel $levels;

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $config App config.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $config,
	) {
		$this->levels = new AssuranceLevel();

	}//end __construct()

	/**
	 * The settings as the admin panel shows them.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function toArray(): array {
		$settings = [];
		foreach (array_keys(self::KEYS) as $field) {
			$settings[$field] = $this->read(field: $field);
		}

		$settings['evidenceMaxAgeMinutes'] = $this->evidenceMaxAgeSeconds() / 60;
		$settings['guardianMinimumAssurance'] = $this->guardianMinimumAssurance();
		$settings['brokerConfigured'] = $this->isBrokerConfigured();
		$settings['selectableProviders'] = self::SELECTABLE_PROVIDERS;

		return $settings;

	}//end toArray()

	/**
	 * Validate every given field, then write them all.
	 *
	 * Nothing is written when any field is invalid.
	 *
	 * @param array<string, mixed> $data Settings fields; unknown fields are ignored.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When a field is invalid; the message names it.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function update(array $data): void {
		$writes = [];
		foreach (self::KEYS as $field => $key) {
			if (array_key_exists($field, $data) === false) {
				continue;
			}

			$value = '';
			if (is_scalar($data[$field]) === true) {
				$value = trim((string)$data[$field]);
			}
			if ($this->isValid(field: $field, value: $value) === false) {
				throw new InvalidArgumentException('Invalid signer identity setting: ' . $field);
			}

			$writes[$key] = $value;
		}

		foreach ($writes as $key => $value) {
			$this->config->setValueString('filinq', $key, $value);
		}

	}//end update()

	/**
	 * The provider an admin selected.
	 *
	 * @return string The identifier, as stored; resolution is strict in the factory.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function providerId(): string {
		return $this->read(field: 'provider');

	}//end providerId()

	/**
	 * A broker parameter.
	 *
	 * @param string $field `issuer`, `clientId`, `authorizationEndpoint`, `tokenEndpoint`,
	 *                      `redirectUri`, `scopes` or `credentialRef`.
	 *
	 * @return string The value, '' when unset.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function broker(string $field): string {
		return $this->read(field: $field);

	}//end broker()

	/**
	 * The admin's acr mapping, decoded; [] when unset or unreadable.
	 *
	 * @return array<string, array{means: string, assurance: string}>
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function acrMapping(): array {
		$decoded = json_decode($this->read(field: 'acrMapping'), true);
		if (is_array($decoded) === false) {
			return [];
		}

		$mapping = [];
		foreach ($decoded as $acr => $entry) {
			if (is_array($entry) === true) {
				$mapping[(string)$acr] = [
					'means' => (string)($entry['means'] ?? 'oidc'),
					'assurance' => $this->levels->normalise(value: ($entry['assurance'] ?? '')),
				];
			}
		}

		return $mapping;

	}//end acrMapping()

	/**
	 * Is every broker parameter present, over https.
	 *
	 * @return bool True when the broker can be used.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function isBrokerConfigured(): bool {
		foreach (['issuer', 'authorizationEndpoint', 'tokenEndpoint'] as $field) {
			if (str_starts_with($this->read(field: $field), 'https://') === false) {
				return false;
			}
		}

		return $this->read(field: 'clientId') !== ''
			&& $this->read(field: 'redirectUri') !== ''
			&& $this->read(field: 'credentialRef') !== '';

	}//end isBrokerConfigured()

	/**
	 * How old identity evidence may be at the signing act.
	 *
	 * @return int Seconds; 15 minutes when unset or out of range.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function evidenceMaxAgeSeconds(): int {
		$minutes = (int)$this->read(field: 'evidenceMaxAgeMinutes');
		if ($minutes < 1 || $minutes > 1440) {
			$minutes = 15;
		}

		return $minutes * 60;

	}//end evidenceMaxAgeSeconds()

	/**
	 * The assurance every guardian must hold at least, whatever the request asks.
	 *
	 * @return string `low` (no raise) unless an admin set more.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function guardianMinimumAssurance(): string {
		return $this->levels->normalise(value: $this->read(field: 'guardianMinimumAssurance'));

	}//end guardianMinimumAssurance()

	/**
	 * Read one field with its default.
	 *
	 * @param string $field The settings field.
	 *
	 * @return string The stored value, or the default.
	 */
	private function read(string $field): string {
		$key = self::KEYS[$field] ?? '';
		if ($key === '') {
			return '';
		}

		return $this->config->getValueString('filinq', $key, self::DEFAULTS[$field] ?? '');

	}//end read()

	/**
	 * Is a value acceptable for a field.
	 *
	 * An empty value clears a broker field; the provider, window and guardian
	 * floor always need a real value.
	 *
	 * @param string $field The settings field.
	 * @param string $value The trimmed value.
	 *
	 * @return bool True when it may be written.
	 */
	private function isValid(string $field, string $value): bool {
		return match ($field) {
			'provider' => in_array($value, self::SELECTABLE_PROVIDERS, true),
			'issuer', 'authorizationEndpoint', 'tokenEndpoint' => $value === '' || $this->isHttpsUrl(value: $value),
			'redirectUri' => $value === '' || $this->isHttpsUrl(value: $value) || preg_match('#^http://(localhost|127\.0\.0\.1)(:\d+)?/#', $value) === 1,
			'clientId' => preg_match('/\s/', $value) !== 1,
			'scopes' => in_array('openid', explode(' ', $value), true),
			'acrMapping' => $value === '' || is_array(json_decode($value, true)),
			'credentialRef' => $value === '' || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1,
			'evidenceMaxAgeMinutes' => ctype_digit($value) === true && (int)$value >= 1 && (int)$value <= 1440,
			'guardianMinimumAssurance' => $this->levels->isLevel(value: $value),
			default => false,
		};

	}//end isValid()

	/**
	 * Is a value an https URL with a host.
	 *
	 * @param string $value The candidate.
	 *
	 * @return bool True for `https://host...`.
	 */
	private function isHttpsUrl(string $value): bool {
		return str_starts_with($value, 'https://') === true && filter_var($value, FILTER_VALIDATE_URL) !== false;

	}//end isHttpsUrl()
}//end class

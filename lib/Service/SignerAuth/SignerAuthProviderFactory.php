<?php

/**
 * Signer Auth Provider Factory
 *
 * Resolves signer-authentication providers by identifier, strictly.
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

use RuntimeException;

/**
 * No silent fallback: an unknown provider throws, naming it.
 *
 * The lesson of REQ-DDSTR-002, applied from day one: when the signing
 * provider factory fell back to the active provider, a QES request could be
 * completed by a native SES artifact. Here an admin who configures a
 * provider that is not registered gets an error at the first signing act,
 * not a Nextcloud session quietly standing in for DigiD.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class SignerAuthProviderFactory {

	/**
	 * The registered providers, by identifier.
	 *
	 * @var array<string, SignerAuthenticationProviderInterface>
	 */
	private array $providers = [];

	/**
	 * Constructor.
	 *
	 * @param SignerAuthSettings $settings Names the configured provider.
	 * @param NextcloudSessionProvider $sessionProvider The default provider.
	 * @param OidcBrokerProvider $brokerProvider The OIDC broker provider.
	 * @param iterable<SignerAuthenticationProviderInterface> $additional Providers a plugin registers
	 *                                                                    (for example a future `eudi-wallet`).
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SignerAuthSettings $settings,
		NextcloudSessionProvider $sessionProvider,
		OidcBrokerProvider $brokerProvider,
		iterable $additional = [],
	) {
		foreach ([$sessionProvider, $brokerProvider] as $provider) {
			$this->providers[$provider->getIdentifier()] = $provider;
		}

		foreach ($additional as $provider) {
			$this->providers[$provider->getIdentifier()] = $provider;
		}

	}//end __construct()

	/**
	 * The provider an admin configured.
	 *
	 * @return SignerAuthenticationProviderInterface The provider.
	 *
	 * @throws RuntimeException When the configured provider is not registered.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function configured(): SignerAuthenticationProviderInterface {
		return $this->get(identifier: $this->settings->providerId());

	}//end configured()

	/**
	 * A provider by identifier.
	 *
	 * @param string $identifier The identifier.
	 *
	 * @return SignerAuthenticationProviderInterface The provider.
	 *
	 * @throws RuntimeException When no provider is registered under it.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function get(string $identifier): SignerAuthenticationProviderInterface {
		$provider = $this->providers[$identifier] ?? null;
		if ($provider === null) {
			throw new RuntimeException('Signer-authentication provider "' . $identifier . '" is not registered');
		}

		return $provider;

	}//end get()

	/**
	 * Is a provider registered under an identifier.
	 *
	 * @param string $identifier The identifier.
	 *
	 * @return bool True when registered.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function has(string $identifier): bool {
		return isset($this->providers[$identifier]) === true;

	}//end has()

	/**
	 * The registered identifiers, in registration order.
	 *
	 * @return list<string>
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function identifiers(): array {
		return array_keys($this->providers);

	}//end identifiers()
}//end class

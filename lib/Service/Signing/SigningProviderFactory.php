<?php

/**
 * Signing Provider Factory
 *
 * Resolves the active signing provider based on admin configuration.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use OCP\App\IAppManager;
use OCP\IAppConfig;
use RuntimeException;

/**
 * Factory for resolving signing providers
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-4
 */
class SigningProviderFactory {

	/**
	 * Map of provider identifiers to their class instances
	 *
	 * @var array<string, SigningProviderInterface>
	 */
	private array $providers = [];

	/**
	 * Constructor
	 *
	 * @param IAppConfig $config The app config
	 * @param NativeSigningProvider $nativeProvider The native signing provider
	 * @param ValidSignProvider $validSignProvider The ValidSign provider
	 * @param LibreSignProvider $libreSignProvider The LibreSign provider, registered only when LibreSign is enabled
	 * @param IAppManager $appManager Tells whether the LibreSign app is enabled
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $config,
		NativeSigningProvider $nativeProvider,
		ValidSignProvider $validSignProvider,
		LibreSignProvider $libreSignProvider,
		IAppManager $appManager,
	) {
		$this->providers['native'] = $nativeProvider;
		$this->providers['validsign'] = $validSignProvider;

		// Offered only when the LibreSign app is there to sign
		// (libresign-signing-provider REQ-DDLSP-001).
		if ($appManager->isEnabledForAnyone(LibreSignProvider::IDENTIFIER) === true) {
			$this->providers[LibreSignProvider::IDENTIFIER] = $libreSignProvider;
		}

	}//end __construct()

	/**
	 * Get the currently configured signing provider
	 *
	 * @return SigningProviderInterface The active signing provider
	 *
	 * @throws RuntimeException When LibreSign is configured but not enabled
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#2-4
	 * @spec openspec/changes/libresign-signing-provider/tasks.md#task-2.1
	 */
	public function getActiveProvider(): SigningProviderInterface {
		$providerName = $this->config->getValueString('filinq', 'signing_provider', 'native');

		// Configured as LibreSign while LibreSign is gone: fail closed. The
		// native provider signs at SES only, so falling back would quietly
		// sign at a lower level than the admin chose.
		if ($providerName === LibreSignProvider::IDENTIFIER && isset($this->providers[$providerName]) === false) {
			throw new RuntimeException(
				'The signing provider is set to LibreSign, but the LibreSign app is not enabled. Enable LibreSign or choose another provider.'
			);
		}

		if (isset($this->providers[$providerName]) === false) {
			return $this->providers['native'];
		}

		return $this->providers[$providerName];
	}//end getActiveProvider()

	/**
	 * Get a specific provider by identifier
	 *
	 * @param string $identifier The provider identifier
	 *
	 * @return SigningProviderInterface The requested provider
	 *
	 * @throws RuntimeException If the provider is not available
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#2-4
	 */
	public function getProvider(string $identifier): SigningProviderInterface {
		if (isset($this->providers[$identifier]) === false) {
			throw new RuntimeException('Signing provider not available: ' . $identifier);
		}

		return $this->providers[$identifier];
	}//end getProvider()

	/**
	 * Get all available provider identifiers
	 *
	 * @return array<string> List of provider identifiers
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#2-4
	 */
	public function getAvailableProviders(): array {
		return array_keys($this->providers);
	}//end getAvailableProviders()
}//end class

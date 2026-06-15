<?php

/**
 * Signing Provider Factory
 *
 * Resolves the active signing provider based on admin configuration.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Signing;

use OCP\IAppConfig;
use RuntimeException;

/**
 * Factory for resolving signing providers
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-4
 */
class SigningProviderFactory
{

    /**
     * Map of provider identifiers to their class instances
     *
     * @var array<string, SigningProviderInterface>
     */
    private array $providers = [];

    /**
     * Constructor
     *
     * @param IAppConfig            $config            The app config
     * @param NativeSigningProvider $nativeProvider    The native signing provider
     * @param ValidSignProvider     $validSignProvider The ValidSign provider
     *
     * @return void
     */
    public function __construct(
        private readonly IAppConfig $config,
        NativeSigningProvider $nativeProvider,
        ValidSignProvider $validSignProvider
    ) {
        $this->providers['native']    = $nativeProvider;
        $this->providers['validsign'] = $validSignProvider;

    }//end __construct()

    /**
     * Get the currently configured signing provider
     *
     * @return SigningProviderInterface The active signing provider
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-4
     */
    public function getActiveProvider(): SigningProviderInterface
    {
        $providerName = $this->config->getValueString('docudesk', 'signing_provider', 'native');

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
    public function getProvider(string $identifier): SigningProviderInterface
    {
        if (isset($this->providers[$identifier]) === false) {
            throw new RuntimeException('Signing provider not available: '.$identifier);
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
    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);

    }//end getAvailableProviders()
}//end class

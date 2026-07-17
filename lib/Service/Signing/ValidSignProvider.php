<?php

/**
 * ValidSign Signing Provider
 *
 * Implements digital signing via ValidSign external service.
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
 * ValidSign signing provider
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
 */
class ValidSignProvider implements SigningProviderInterface
{
    /**
     * Constructor
     *
     * @param IAppConfig $config The app config
     *
     * @return void
     */
    public function __construct(
        private readonly IAppConfig $config
    ) {

    }//end __construct()

    /**
     * Get provider identifier
     *
     * @return string The provider identifier
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
     */
    public function getIdentifier(): string
    {
        return 'validsign';

    }//end getIdentifier()

    /**
     * Initiate a ValidSign signing flow (stub)
     *
     * @param string               $documentPath Path to the document
     * @param string               $documentName Display name of the document
     * @param array<string, mixed> $signers      Signer data array
     * @param string               $level        Signature level
     * @param array<string, mixed> $options      Additional options
     *
     * @return array<string, mixed> Result with ValidSign package ID
     *
     * @throws RuntimeException If provider is not configured
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
     */
    public function initiateSigning(
        string $documentPath,
        string $documentName,
        array $signers,
        string $level,
        array $options=[]
    ): array {
        $providerConfig = $this->getProviderConfig();

        if (empty($providerConfig['sourceId']) === true) {
            throw new RuntimeException(
                'ValidSign provider is not configured. Set the OpenConnector source ID in admin settings.'
            );
        }

        $externalId = 'validsign-'.bin2hex(random_bytes(16));

        return [
            'success'    => true,
            'externalId' => $externalId,
            'message'    => 'ValidSign signing request created (integration pending)',
        ];

    }//end initiateSigning()

    /**
     * Check status of a ValidSign signing flow (stub)
     *
     * Orphan-auth seam (hydra gate-6): a provider-contract status *read*, not
     * an authorization guard. No native caller — this is the external-provider
     * status-poll extension point (SigningProviderInterface::checkStatus),
     * currently a stub pending ValidSign integration; the live status surface
     * is OR's ApprovalChain via `SigningController::showRequest`. Classified as
     * a legit plugin seam in openspec/changes/orphan-auth-remediation/design.md.
     *
     * @param string $externalId The ValidSign package identifier
     *
     * @return array<string, mixed> The signing flow status
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
     */
    public function checkStatus(string $externalId): array
    {
        return [
            'status'      => 'pending',
            'signers'     => [],
            'completedAt' => null,
        ];

    }//end checkStatus()

    /**
     * Download the signed document from ValidSign (stub)
     *
     * @param string $externalId The ValidSign package identifier
     *
     * @return string The signed document content
     *
     * @throws RuntimeException Always throws - not yet implemented
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
     */
    public function downloadSignedDocument(string $externalId): string
    {
        throw new RuntimeException(
            'ValidSign document download not yet implemented. External ID: '.$externalId
        );

    }//end downloadSignedDocument()

    /**
     * Cancel a ValidSign signing flow (stub)
     *
     * @param string $externalId The ValidSign package identifier
     *
     * @return bool True if cancelled
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
     */
    public function cancelSigning(string $externalId): bool
    {
        return true;

    }//end cancelSigning()

    /**
     * Check if this provider supports the given signature level
     *
     * @param string $level The signature level to check
     *
     * @return bool True if supported
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
     */
    public function supportsLevel(string $level): bool
    {
        return in_array($level, ['SES', 'AdES', 'QES'], true) === true;

    }//end supportsLevel()

    /**
     * Produce a signed artifact (not yet implemented — honest-completion gate).
     *
     * The ValidSign external integration cannot yet return a signed file, so
     * this throws rather than presenting the unsigned original as signed. A QES
     * request routed here fails the honest-completion gate loudly (issue #304
     * scope: an unfinished provider must never mislabel the original).
     *
     * @param string               $documentContent The original document bytes.
     * @param array<string, mixed> $context         Signing context.
     *
     * @return string The signed document bytes.
     *
     * @throws RuntimeException Always — the ValidSign artifact flow is not wired.
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    public function produceSignedArtifact(string $documentContent, array $context): string
    {
        throw new RuntimeException(
            'ValidSign cannot yet produce a signed artifact — the external signing integration is '
            .'not implemented. The request must not complete with the unsigned original as its signed document.'
        );

    }//end produceSignedArtifact()

    /**
     * Get the provider configuration from app config
     *
     * @return array<string, mixed> The provider configuration
     */
    private function getProviderConfig(): array
    {
        $configJson = $this->config->getValueString('docudesk', 'signing_provider_config', '{}');
        $decoded    = json_decode($configJson, true);

        if (is_array($decoded) === false) {
            return [];
        }

        return $decoded;

    }//end getProviderConfig()
}//end class

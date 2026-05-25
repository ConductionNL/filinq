<?php

/**
 * Signing Provider Interface
 *
 * Defines the contract for pluggable digital signing providers.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Signing;

/**
 * Interface for signing providers
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface SigningProviderInterface
{
    /**
     * Get the unique identifier for this provider
     *
     * @return string The provider identifier
     */
    public function getIdentifier(): string;

    /**
     * Initiate a signing flow for a document
     *
     * @param string               $documentPath The Nextcloud file path
     * @param string               $documentName The document display name
     * @param array<string, mixed> $signers      Array of signer data
     * @param string               $level        Signature level (SES, AdES, QES)
     * @param array<string, mixed> $options      Additional options
     *
     * @return array<string, mixed> Result with keys: success, externalId, message
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-1
     */
    public function initiateSigning(
        string $documentPath,
        string $documentName,
        array $signers,
        string $level,
        array $options=[]
    ): array;

    /**
     * Check the status of an ongoing signing flow
     *
     * @param string $externalId The external signing flow identifier
     *
     * @return array<string, mixed> Status with keys: status, signers, completedAt
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-1
     */
    public function checkStatus(string $externalId): array;

    /**
     * Download the signed document from the provider
     *
     * @param string $externalId The external signing flow identifier
     *
     * @return string The signed document content
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-1
     */
    public function downloadSignedDocument(string $externalId): string;

    /**
     * Cancel an ongoing signing flow
     *
     * @param string $externalId The external signing flow identifier
     *
     * @return bool True if cancellation succeeded
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-1
     */
    public function cancelSigning(string $externalId): bool;

    /**
     * Check if this provider supports a given signature level
     *
     * @param string $level The signature level (SES, AdES, QES)
     *
     * @return bool True if the level is supported
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-1
     */
    public function supportsLevel(string $level): bool;
}//end interface

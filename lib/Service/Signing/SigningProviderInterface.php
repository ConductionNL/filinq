<?php

/**
 * Signing Provider Interface
 *
 * Defines the contract for pluggable digital signing providers.
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

use OCA\Filinq\Exception\SigningCancellationNotSupportedException;
use RuntimeException;

/**
 * Interface for signing providers
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface SigningProviderInterface {
	/**
	 * Get the unique identifier for this provider
	 *
	 * @return string The provider identifier
	 */
	public function getIdentifier(): string;

	/**
	 * Initiate a signing flow for a document
	 *
	 * @param string $documentPath The Nextcloud file path
	 * @param string $documentName The document display name
	 * @param array<string, mixed> $signers Array of signer data
	 * @param string $level Signature level (SES, AdES, QES)
	 * @param array<string, mixed> $options Additional options
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
		array $options = [],
	): array;

	/**
	 * Check the status of an ongoing signing flow
	 *
	 * Orphan-auth seam (hydra gate-6): this is a provider-contract status
	 * *read*, not an authorization guard. It intentionally has no native
	 * caller — the async external-provider status-poll leg is a pluggable
	 * extension point implemented by external providers (e.g. ValidSign) and
	 * invoked through the provider flow, not the live OR-ApprovalChain status
	 * path (`SigningController::showRequest`). Classified as a legit plugin
	 * seam in openspec/changes/orphan-auth-remediation/design.md.
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
	 * Withdraw an ongoing signing flow.
	 *
	 * VOID OR THROW, deliberately, and not `bool`.
	 *
	 * The previous `: bool` contract is what allowed `ValidSignProvider` to ship
	 * `return true;` with no call to ValidSign at all — an implementation-shaped
	 * statement that a user's request had been withdrawn when it was still live and
	 * still signable. A boolean invites one caller to write `if ($ok)` and the next
	 * to ignore it, and neither is wrong under the type.
	 *
	 * An implementation MUST either complete the withdrawal against its backend or
	 * raise. A provider with no cancellation capability MUST throw
	 * {@see SigningCancellationNotSupportedException} rather than return, so the
	 * caller can tell the user the truth.
	 *
	 * @param string $externalId The external signing flow identifier.
	 *
	 * @return void
	 *
	 * @throws SigningCancellationNotSupportedException When the provider cannot cancel at all.
	 * @throws RuntimeException When the provider could be reached but refused or failed.
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function cancelSigning(string $externalId): void;

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

	/**
	 * Produce a verifiable signed artifact from the original document bytes.
	 *
	 * Given the original document content (already a writeable PDF), the
	 * provider returns the signed document bytes. The native provider embeds a
	 * `/DocuDesk-Signature(...)` marker carrying an HMAC over the document
	 * content-hash so the artifact passes
	 * `SigningVerificationService::verifyDocument()`. External providers return
	 * the file their remote signing service produced.
	 *
	 * Honest-completion gate: a provider that cannot currently produce a signed
	 * artifact (native writer disabled, `signing_verification_secret` unset, or
	 * an external provider unconfigured/stubbed) MUST throw a descriptive
	 * exception rather than return the unsigned original — so the completing
	 * signature fails loudly instead of mislabelling the original as signed.
	 *
	 * @param string $documentContent The original document bytes.
	 * @param array<string, mixed> $context Signing context: signer,
	 *                                      signers, timestamp, ip,
	 *                                      level.
	 *
	 * @return string The signed document bytes.
	 *
	 * @throws \RuntimeException When no signed artifact can be produced.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function produceSignedArtifact(string $documentContent, array $context): string;
}//end interface

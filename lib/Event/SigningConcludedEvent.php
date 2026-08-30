<?php

/**
 * Filinq SigningConcludedEvent
 *
 * Public cross-app event Filinq dispatches when a delegated (provenance-
 * carrying) signing request reaches a terminal outcome. Consumer fleet apps
 * (e.g. shillinq) listen for it to perform their own downstream side effects —
 * Filinq owns the signing request only, never the consumer's side effect.
 * Carries the subject/provenance reference plus the outcome envelope built from
 * the persisted signing-request fields.
 *
 * @category  Event
 * @package   OCA\Filinq\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Event;

use OCP\EventDispatcher\Event;

/**
 * Cross-app conclusion event: Filinq reports a concluded delegated signing request.
 *
 * Fully immutable — Filinq constructs it from the persisted signing-request
 * array and the normalised terminal status; consumers only read. The
 * array-to-event mapping (including the eIDAS assurance-level resolution)
 * lives in the injectable {@see SigningConcludedEventFactory}, not in a static
 * named constructor on this value object.
 *
 * @category Event
 * @package  OCA\Filinq\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */
class SigningConcludedEvent extends Event {
	/**
	 * Construct the conclusion event.
	 *
	 * @param string $signingRequestId The concluded signing-request id
	 * @param string $status Normalised outcome (signed|declined|expired|cancelled)
	 * @param string|null $signedDocumentRef Reference to the signed document, when signed
	 * @param array<int, mixed> $signers Resolved signers list
	 * @param string|null $signedAt When the request concluded
	 * @param SigningProvenance $provenance Source app, subject reference and consumer references
	 * @param string $assuranceLevel Resolved eIDAS assurance (low|substantial|high,
	 *                               signing-trust-rebuild REQ-DDSTR-010) — the
	 *                               delegating consumer (e.g. decidesk's
	 *                               `EIDASSignatureService::resolveSignatureStage()`)
	 *                               maps this onto its own stage vocabulary. `low`
	 *                               for the native SES provider today; broker-resolved
	 *                               assurance is populated into the SAME field by the
	 *                               `signer-identity-rails` change.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly string $signingRequestId,
		private readonly string $status,
		private readonly ?string $signedDocumentRef,
		private readonly array $signers,
		private readonly ?string $signedAt,
		private readonly SigningProvenance $provenance,
		private readonly string $assuranceLevel = 'low',
	) {
		parent::__construct();

	}//end __construct()

	/**
	 * Get the concluded signing-request id.
	 *
	 * @return string The signing-request id.
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 */
	public function getSigningRequestId(): string {
		return $this->signingRequestId;
	}//end getSigningRequestId()

	/**
	 * Get the normalised outcome status (signed|declined|expired|cancelled).
	 *
	 * @return string The status.
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 */
	public function getStatus(): string {
		return $this->status;
	}//end getStatus()

	/**
	 * Get the reference to the signed document, when signed.
	 *
	 * @return string|null The signed document reference, or null.
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 */
	public function getSignedDocumentRef(): ?string {
		return $this->signedDocumentRef;
	}//end getSignedDocumentRef()

	/**
	 * Get the resolved signers list.
	 *
	 * @return array<int, mixed> The signers.
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 */
	public function getSigners(): array {
		return $this->signers;
	}//end getSigners()

	/**
	 * Get when the request concluded.
	 *
	 * @return string|null The signed-at timestamp, or null.
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 */
	public function getSignedAt(): ?string {
		return $this->signedAt;
	}//end getSignedAt()

	/**
	 * Get the consumer app that requested the signature.
	 *
	 * @return string The source app id.
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 */
	public function getSourceApp(): string {
		return $this->provenance->getSourceApp();
	}//end getSourceApp()

	/**
	 * Get the OpenRegister register of the originating object.
	 *
	 * @return string|null The subject register, or null.
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 */
	public function getSubjectRegister(): ?string {
		return $this->provenance->getSubjectRegister();
	}//end getSubjectRegister()

	/**
	 * Get the OpenRegister schema of the originating object.
	 *
	 * @return string|null The subject schema, or null.
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 */
	public function getSubjectSchema(): ?string {
		return $this->provenance->getSubjectSchema();
	}//end getSubjectSchema()

	/**
	 * Get the OpenRegister id of the originating object.
	 *
	 * @return string|null The subject id, or null.
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 */
	public function getSubjectId(): ?string {
		return $this->provenance->getSubjectId();
	}//end getSubjectId()

	/**
	 * Get the consumer's own external reference.
	 *
	 * @return string The external reference.
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 */
	public function getExternalReference(): string {
		return $this->provenance->getExternalReference();
	}//end getExternalReference()

	/**
	 * Get the correlation id from the request event.
	 *
	 * @return string The correlation id.
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 */
	public function getCorrelationId(): string {
		return $this->provenance->getCorrelationId();
	}//end getCorrelationId()

	/**
	 * Get the resolved eIDAS assurance level (signing-trust-rebuild REQ-DDSTR-010).
	 *
	 * @return string One of low|substantial|high.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function getAssuranceLevel(): string {
		return $this->assuranceLevel;
	}//end getAssuranceLevel()
}//end class

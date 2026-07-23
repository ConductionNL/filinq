<?php

/**
 * DocuDesk SigningConcludedEvent
 *
 * Public cross-app event DocuDesk dispatches when a delegated (provenance-
 * carrying) signing request reaches a terminal outcome. Consumer fleet apps
 * (e.g. shillinq) listen for it to perform their own downstream side effects —
 * DocuDesk owns the signing request only, never the consumer's side effect.
 * Carries the subject/provenance reference plus the outcome envelope built from
 * the persisted signing-request fields.
 *
 * @category  Event
 * @package   OCA\DocuDesk\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Event;

use OCP\EventDispatcher\Event;

/**
 * Cross-app conclusion event: DocuDesk reports a concluded delegated signing request.
 *
 * Fully immutable — DocuDesk constructs it from the persisted signing-request
 * array and the normalised terminal status; consumers only read.
 *
 * @category Event
 * @package  OCA\DocuDesk\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
 */
class SigningConcludedEvent extends Event
{
    /**
     * Construct the conclusion event.
     *
     * @param string            $signingRequestId  The concluded signing-request id
     * @param string            $status            Normalised outcome (signed|declined|expired|cancelled)
     * @param string|null       $signedDocumentRef Reference to the signed document, when signed
     * @param array<int, mixed> $signers           Resolved signers list
     * @param string|null       $signedAt          When the request concluded
     * @param string            $sourceApp         Consumer app that requested the signature
     * @param string|null       $subjectRegister   OpenRegister register of the originating object
     * @param string|null       $subjectSchema     OpenRegister schema of the originating object
     * @param string|null       $subjectId         OpenRegister id of the originating object
     * @param string            $externalReference Consumer's own reference
     * @param string            $correlationId     Correlation id from the request event
     * @param string            $assuranceLevel    Resolved eIDAS assurance (low|substantial|high,
     *                                             signing-trust-rebuild REQ-DDSTR-010) — the
     *                                             delegating consumer (e.g. decidesk's
     *                                             `EIDASSignatureService::resolveSignatureStage()`)
     *                                             maps this onto its own stage vocabulary. `low`
     *                                             for the native SES provider today; broker-resolved
     *                                             assurance is populated into the SAME field by the
     *                                             `signer-identity-rails` change.
     *
     * @return void
     */
    public function __construct(
        private readonly string $signingRequestId,
        private readonly string $status,
        private readonly ?string $signedDocumentRef,
        private readonly array $signers,
        private readonly ?string $signedAt,
        private readonly string $sourceApp,
        private readonly ?string $subjectRegister,
        private readonly ?string $subjectSchema,
        private readonly ?string $subjectId,
        private readonly string $externalReference='',
        private readonly string $correlationId='',
        private readonly string $assuranceLevel='low',
    ) {
        parent::__construct();

    }//end __construct()

    /**
     * Build a conclusion event from a persisted signing-request array.
     *
     * The request array already carries the provenance fields (persisted by
     * SigningService::createRequest from the request event) and the signers;
     * the status is the normalised terminal vocabulary value supplied by the
     * caller.
     *
     * @param array<string, mixed> $request           Persisted signing-request object array
     * @param string               $status            Normalised status (signed|declined|expired|cancelled)
     * @param string|null          $signedDocumentRef Reference to the signed document, when signed
     *
     * @return self
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public static function fromRequest(
        array $request,
        string $status,
        ?string $signedDocumentRef=null,
    ): self {
        return new self(
            signingRequestId: (string) ($request['id'] ?? $request['uuid'] ?? ''),
            status: $status,
            signedDocumentRef: $signedDocumentRef,
            signers: (array) ($request['signerIds'] ?? []),
            signedAt: ($request['signedAt'] ?? null),
            sourceApp: (string) ($request['sourceApp'] ?? ''),
            subjectRegister: ($request['subjectRegister'] ?? null),
            subjectSchema: ($request['subjectSchema'] ?? null),
            subjectId: ($request['subjectId'] ?? null),
            externalReference: (string) ($request['externalReference'] ?? ''),
            correlationId: (string) ($request['correlationId'] ?? ''),
            assuranceLevel: self::resolveAssuranceLevel(request: $request),
        );

    }//end fromRequest()

    /**
     * Resolve the eIDAS assurance level for the completion payload
     * (signing-trust-rebuild REQ-DDSTR-010).
     *
     * Conservative-by-construction: the native provider only ever produces SES
     * artifacts (`NativeSigningProvider::supportsLevel()`), so a native/SES
     * request always resolves `low`. Any level this map does not explicitly
     * recognise falls back to `low` rather than over-claiming an assurance the
     * request cannot actually evidence. `signer-identity-rails` is expected to
     * populate a broker-resolved, higher assurance into this SAME field for a
     * request whose provider actually supports it — this mapping never needs
     * to change for that, only the request's own `provider`/`signatureLevel`
     * do.
     *
     * @param array<string, mixed> $request The persisted signing-request array.
     *
     * @return string One of low|substantial|high.
     */
    private static function resolveAssuranceLevel(array $request): string
    {
        $provider = (string) ($request['provider'] ?? 'native');
        $level    = (string) ($request['signatureLevel'] ?? 'SES');

        if ($provider === 'native') {
            // The native provider only ever produces SES — never claim more.
            return 'low';
        }

        return match ($level) {
            'QES' => 'high',
            'AdES' => 'substantial',
            default => 'low',
        };

    }//end resolveAssuranceLevel()

    /**
     * Get the concluded signing-request id.
     *
     * @return string The signing-request id.
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public function getSigningRequestId(): string
    {
        return $this->signingRequestId;

    }//end getSigningRequestId()

    /**
     * Get the normalised outcome status (signed|declined|expired|cancelled).
     *
     * @return string The status.
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public function getStatus(): string
    {
        return $this->status;

    }//end getStatus()

    /**
     * Get the reference to the signed document, when signed.
     *
     * @return string|null The signed document reference, or null.
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public function getSignedDocumentRef(): ?string
    {
        return $this->signedDocumentRef;

    }//end getSignedDocumentRef()

    /**
     * Get the resolved signers list.
     *
     * @return array<int, mixed> The signers.
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public function getSigners(): array
    {
        return $this->signers;

    }//end getSigners()

    /**
     * Get when the request concluded.
     *
     * @return string|null The signed-at timestamp, or null.
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public function getSignedAt(): ?string
    {
        return $this->signedAt;

    }//end getSignedAt()

    /**
     * Get the consumer app that requested the signature.
     *
     * @return string The source app id.
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public function getSourceApp(): string
    {
        return $this->sourceApp;

    }//end getSourceApp()

    /**
     * Get the OpenRegister register of the originating object.
     *
     * @return string|null The subject register, or null.
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public function getSubjectRegister(): ?string
    {
        return $this->subjectRegister;

    }//end getSubjectRegister()

    /**
     * Get the OpenRegister schema of the originating object.
     *
     * @return string|null The subject schema, or null.
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public function getSubjectSchema(): ?string
    {
        return $this->subjectSchema;

    }//end getSubjectSchema()

    /**
     * Get the OpenRegister id of the originating object.
     *
     * @return string|null The subject id, or null.
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public function getSubjectId(): ?string
    {
        return $this->subjectId;

    }//end getSubjectId()

    /**
     * Get the consumer's own external reference.
     *
     * @return string The external reference.
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public function getExternalReference(): string
    {
        return $this->externalReference;

    }//end getExternalReference()

    /**
     * Get the correlation id from the request event.
     *
     * @return string The correlation id.
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;

    }//end getCorrelationId()

    /**
     * Get the resolved eIDAS assurance level (signing-trust-rebuild REQ-DDSTR-010).
     *
     * @return string One of low|substantial|high.
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    public function getAssuranceLevel(): string
    {
        return $this->assuranceLevel;

    }//end getAssuranceLevel()
}//end class

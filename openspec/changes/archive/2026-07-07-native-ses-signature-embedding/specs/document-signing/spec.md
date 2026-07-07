# document-signing Specification (delta)

---
status: proposed
---

## Purpose

Make the `document-signing` capability's signature-application claims true. The shipped signing
workflow (request creation, signer records, OR `ApprovalChain`, status machine, OR `AuditTrail`)
is unchanged. This delta specifies the missing step: the active provider produces a **verifiable
signed artifact** on completion, the artifact is stored as a **new Nextcloud file version**, the
request references **that artifact** (never the unsigned original), and — until a provider can
produce one — the request and the documentation say so honestly. Closes `ConductionNL/docudesk#304`.

## MODIFIED Requirements

### Requirement: Signature levels

The system SHALL support three eIDAS signature levels. The signature level is specified per
signing request and determines the authentication and signing method used. At the **SES** level,
the native provider produces the signed artifact locally; at the **AdES** and **QES** levels the
signed artifact is produced by a configured external signing provider. A signature level whose
provider cannot currently produce a signed artifact SHALL fail loudly at signing time (see the
honest-completion gate) rather than mark a document signed without producing one.

#### Scenario: Simple Electronic Signature (SES)
- WHEN the signature that completes a request is applied at level "SES"
- THEN the native provider produces a signed PDF that embeds a `/DocuDesk-Signature(...)` marker
  binding the signer's Nextcloud user identity, timestamp, and IP address
- AND the marker carries an HMAC (`mac`) over the document content-hash computed with the
  server-held `signing_verification_secret`
- AND `SigningVerificationService::verifyDocument()` reports the produced artifact as `valid: true`

#### Scenario: Advanced / Qualified Electronic Signature (AdES / QES) via external provider
- WHEN a request is created at level "AdES" or "QES"
- THEN the signing method is delegated to the configured external signing provider (e.g. ValidSign)
- AND the signed artifact is the file the external provider returns
- AND if no external provider is configured — or the configured provider cannot yet return a
  signed artifact — the request fails the honest-completion gate rather than completing unsigned

#### Scenario: SES is the only locally-produced level
- WHEN level "AdES" or "QES" is requested with no external provider configured
- THEN the request MUST NOT complete with a native SES artifact as a silent substitute
- AND the requested level's unavailability is reported to the initiator

## ADDED Requirements

### Requirement: Completion produces a verifiable signed artifact stored as a new file version

When the signature that transitions a signing request to COMPLETED is applied, the system SHALL
invoke the active signing provider (resolved via `SigningProviderFactory`) to produce the signed
document, and SHALL store that artifact as a **new Nextcloud file version of the original
document** (via the `files_versions` capability). The system SHALL NOT create the COMPLETED state
without a produced artifact. The stored artifact SHALL be verifiable: for a native SES request it
SHALL pass `SigningVerificationService::verifyDocument()`.

#### Scenario: All signers complete — a signed artifact is stored
- GIVEN a native SES request whose every signer has signed
- WHEN the request transitions to COMPLETED
- THEN the active provider produces the signed PDF
- AND it is stored as a new Nextcloud file version of the original document
- AND `SigningVerificationService::verifyDocument()` on that version returns `valid: true`
- AND a `SigningAuditEntry` records the completion

#### Scenario: The signed reference points at the artifact, not the original
- GIVEN a completed signing request
- WHEN the request's `signedDocumentRef` is read (and the cross-app `SigningConcludedEvent` is
  emitted for a delegated request)
- THEN `signedDocumentRef` references the stored signed artifact (file id + version)
- AND it is NOT the unsigned original `documentFileId`

### Requirement: Honest-completion gate when no artifact can be produced

The system SHALL fail the signing operation loudly and SHALL NOT transition the request to a state
that presents a signed document when no configured provider can produce a signed artifact for the
requested level — the native writer is unavailable, `signing_verification_secret` is unset, or the
external provider is unconfigured/stubbed. In that case `signedDocumentRef` SHALL be null or
explicitly flagged as unavailable, and SHALL never be set to the unsigned original.

#### Scenario: Native writer unavailable — request does not falsely complete
@e2e exclude backend guard — provider-availability failure is covered by PHPUnit on the signing service, no navigable UI surface
- GIVEN native signing is enabled but the SES artifact writer cannot run (e.g. `#304` unresolved
  or `signing_verification_secret` unset)
- WHEN a signer attempts the completing signature
- THEN the operation fails with a descriptive error
- AND the request is NOT marked COMPLETED with the original file as its `signedDocumentRef`

#### Scenario: Stubbed external provider does not mislabel the original
@e2e exclude backend guard — external-provider stub path is covered by PHPUnit, not a UI flow
- GIVEN a request at level "QES" routed to an external provider that cannot return a signed file
- WHEN completion is attempted
- THEN no signed artifact is recorded and `signedDocumentRef` is null/flagged
- AND the unsigned original is never presented as the signed document

### Requirement: Documented signing readiness reflects implementation reality

The documentation SHALL distinguish the shipped signing workflow + audit trail from signature
embedding until a provider produces a verifiable artifact. The `docs/GOVERNMENT-FEATURES.md` F-13
entry and the `docs/features.json` signing narrative SHALL NOT state or imply that signature
embedding / signed-artifact production is complete while it is not.

#### Scenario: Feature sheet does not overstate signing
@e2e exclude docs content — feature-sheet accuracy, not a navigable app surface
- GIVEN the SES artifact writer has not yet landed (`#304` open)
- WHEN `docs/GOVERNMENT-FEATURES.md` F-13 and `docs/features.json` are read
- THEN they present the signing workflow + audit trail as available and signature embedding as
  in progress — not a completed "legally meaningful signature process"

#### Scenario: Feature sheet is corrected when the artifact writer lands
@e2e exclude docs content — feature-sheet accuracy, not a navigable app surface
- GIVEN the SES artifact writer ships and produces verifiable artifacts
- WHEN the documentation is updated
- THEN F-13 may state signing (SES) as available, consistent with the verifier passing

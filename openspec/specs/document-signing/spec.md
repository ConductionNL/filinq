---
status: done
---

# document-signing Specification

## Purpose
Provides digital signing of documents with eIDAS signature levels (SES, AdES, QES) and sequential or parallel multi-signer workflows. Signing requests, signer records, and immutable audit entries are stored as OpenRegister objects, the document is locked during signing, and a strict status machine (DRAFT, PENDING, IN_PROGRESS, COMPLETED, DECLINED, EXPIRED, CANCELLED) governs the lifecycle while signers are notified through Nextcloud. This gives Filinq a legally meaningful, auditable signature process.

@e2e exclude Backend signing API + eIDAS crypto + status machine + OR schema/audit contracts; no navigable UI surface. Covered by PHPUnit (SignatureService, status transitions, audit immutability) and Newman (/api/signing/* contracts).
## Requirements
### Requirement: Signing request creation
The system SHALL allow authenticated users to create a signing request for a document. A signing request specifies the document (Nextcloud file ID), the signature level (SES, AdES, or QES), the signing mode (sequential or parallel), and an ordered list of signers. The signing request SHALL be stored as an OpenRegister object via ObjectService using the SigningRequest schema.

#### Scenario: Create a signing request for a single signer
- **WHEN** a user submits a POST to `/api/signing/requests` with a file ID, signature level "SES", mode "sequential", and one signer
- **THEN** a SigningRequest object is created in OpenRegister with status "PENDING"
- **AND** a SignerRecord object is created with status "PENDING"
- **AND** the document file is locked for editing via ILockManager
- **AND** a SigningAuditEntry is created recording the initiation

#### Scenario: Create a signing request with sequential multi-signer
- **WHEN** a user creates a signing request with signers [A, B, C] in sequential mode
- **THEN** signer A receives a Nextcloud notification to sign
- **AND** signers B and C do not receive notifications until the previous signer completes

#### Scenario: Create a signing request with parallel multi-signer
- **WHEN** a user creates a signing request with signers [A, B, C] in parallel mode
- **THEN** all three signers receive Nextcloud notifications simultaneously
- **AND** each signer can sign independently of the others

### Requirement: Signing request lifecycle management
The system SHALL enforce a strict status machine for signing requests: DRAFT -> PENDING -> IN_PROGRESS -> COMPLETED | DECLINED | EXPIRED | CANCELLED. Invalid transitions SHALL be rejected with an error response.

#### Scenario: Sequential signing progresses through signers
- **WHEN** signer A signs in a sequential request with signers [A, B, C]
- **THEN** signer A's SignerRecord status changes to "SIGNED" with timestamp
- **AND** signer B receives a notification to sign
- **AND** the SigningRequest status changes to "IN_PROGRESS"
- **AND** a SigningAuditEntry records signer A's signature

#### Scenario: All signers complete
- **WHEN** the last signer in a request signs the document
- **THEN** the SigningRequest status changes to "COMPLETED"
- **AND** the document file lock is released
- **AND** the signed PDF is stored as a new file version in Nextcloud

#### Scenario: Signer declines
- **WHEN** a signer declines a signing request with a reason
- **THEN** the SignerRecord status changes to "DECLINED" with the reason
- **AND** the SigningRequest status changes to "DECLINED"
- **AND** the request initiator receives a notification with the decline reason
- **AND** the document file lock is released

#### Scenario: Signing request expires
- **WHEN** a signing request passes its deadline without all signatures
- **THEN** the SigningRequest status changes to "EXPIRED"
- **AND** all pending SignerRecords are marked "EXPIRED"
- **AND** the document file lock is released

#### Scenario: Initiator cancels a signing request
- **WHEN** the initiator sends a DELETE to `/api/signing/requests/{id}`
- **THEN** the SigningRequest status changes to "CANCELLED"
- **AND** all pending SignerRecords are marked "CANCELLED"
- **AND** a SigningAuditEntry records the cancellation

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

### Requirement: Pluggable signing provider interface
The system SHALL define a SigningProviderInterface that all signing providers implement. The active provider is selected based on admin configuration. The interface SHALL define methods for: initiating a signing flow, checking signing status, downloading the signed document, and cancelling a signing flow.

#### Scenario: Native provider handles SES signing
- **WHEN** the signing provider is set to "native" and a SES signing request is created
- **THEN** NativeSigningProvider processes the signing locally without external API calls
- **AND** the signed PDF is returned directly

#### Scenario: ValidSign provider handles external signing
- **WHEN** the signing provider is set to "validsign" and a signing request is created
- **THEN** ValidSignProvider sends the document to ValidSign via OpenConnector
- **AND** the signer receives an email from ValidSign with a signing link
- **AND** upon completion, the signed document is retrieved and stored in Nextcloud

#### Scenario: Provider not configured
- **WHEN** a signing request requires an external provider but none is configured
- **THEN** the system returns an error indicating the signing provider is not configured
- **AND** a warning is logged

### Requirement: Signing status tracking
The system SHALL provide a REST API endpoint to retrieve the current status of a signing request, including the status of each individual signer.

#### Scenario: View signing request status
- **WHEN** a user sends a GET to `/api/signing/requests/{id}`
- **THEN** the response includes: request status, document reference, signature level, list of signers with their individual status and timestamps, and any decline reasons

#### Scenario: List all signing requests
- **WHEN** a user sends a GET to `/api/signing/requests`
- **THEN** the response includes all signing requests the user has initiated or is a signer on
- **AND** each request includes summary status information

### Requirement: Bulk signing
The system SHALL allow users to sign multiple pending signing requests in a single authenticated session.

#### Scenario: Bulk sign multiple documents
- **WHEN** a user sends a POST to `/api/signing/bulk` with an array of signing request IDs
- **THEN** each document is signed in sequence using the user's current authentication
- **AND** each individual signing creates its own SigningAuditEntry
- **AND** the response includes the result (success/failure) for each request

### Requirement: Signature verification
The system SHALL provide the ability to verify signatures embedded in PDF documents, including checking certificate validity, detecting document tampering, and displaying signature details.

#### Scenario: Verify a signed document
- **WHEN** a user sends a GET to `/api/signing/verify/{fileId}`
- **THEN** the response lists all signatures with: signer identity, timestamp, signature level, and validity status
- **AND** tampered documents show an "INVALID" status with details

### Requirement: Signing audit trail
The system SHALL maintain an immutable audit trail for all signing-related events. Audit entries SHALL be stored as OpenRegister objects and SHALL NOT be modifiable or deletable through the API. The retention period SHALL be minimum 10 years per Archiefwet 1995.

#### Scenario: Audit trail records signing events
- **WHEN** any signing action occurs (create, sign, decline, cancel, expire)
- **THEN** a SigningAuditEntry is created with: action type, actor identity, timestamp, IP address, signature level, provider used, and document reference

#### Scenario: Audit trail is immutable
- **WHEN** a user attempts to update or delete a SigningAuditEntry via the API
- **THEN** the request is rejected with a 403 Forbidden response

#### Scenario: Retrieve audit trail for a signing request
- **WHEN** a user sends a GET to `/api/signing/requests/{id}/audit`
- **THEN** the response includes all audit entries for that signing request in chronological order

### Requirement: Signing request data model
The system SHALL store signing data using three OpenRegister schemas defined in `filinq_register.json`.

#### Scenario: SigningRequest schema
- **WHEN** a signing request is created
- **THEN** the object contains: id, documentFileId, documentName, initiatorUserId, signatureLevel (SES|AdES|QES), signingMode (sequential|parallel), status (DRAFT|PENDING|IN_PROGRESS|COMPLETED|DECLINED|EXPIRED|CANCELLED), provider (native|validsign|docusign|adobesign|libresign), deadline (ISO 8601), signerIds (array), createdAt, updatedAt

#### Scenario: SignerRecord schema
- **WHEN** a signer record is created
- **THEN** the object contains: id, signingRequestId, userId, displayName, email, order (for sequential), status (PENDING|SIGNED|DECLINED|EXPIRED|CANCELLED), signedAt, declineReason, ipAddress, signatureData (base64)

#### Scenario: SigningAuditEntry schema
- **WHEN** an audit entry is created
- **THEN** the object contains: id, signingRequestId, action (CREATED|SIGNED|DECLINED|CANCELLED|EXPIRED|COMPLETED|VIEWED), actorUserId, actorDisplayName, timestamp, ipAddress, signatureLevel, provider, metadata (JSON)

### Requirement: Signing REST API
The system SHALL expose signing functionality via REST API endpoints registered in `appinfo/routes.php`.

#### Scenario: API endpoints are registered
- **WHEN** the Filinq app is loaded
- **THEN** the following routes are available:
  - POST `/api/signing/requests` (create signing request)
  - GET `/api/signing/requests` (list signing requests)
  - GET `/api/signing/requests/{id}` (get signing request details)
  - DELETE `/api/signing/requests/{id}` (cancel signing request)
  - POST `/api/signing/requests/{id}/sign` (sign a document)
  - POST `/api/signing/requests/{id}/decline` (decline a signing request)
  - POST `/api/signing/bulk` (bulk sign)
  - GET `/api/signing/verify/{fileId}` (verify signatures)
  - GET `/api/signing/requests/{id}/audit` (get audit trail)

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

### Requirement: Signed-artifact assertion binds signer identity cryptographically (REQ-DDSTR-001)
The native SES assertion MUST carry a version discriminator `v: 2` and a MAC computed as `HMAC-SHA256(secret, sha256(canonical-document) . "\n" . canonical-JSON-of-assertion-without-mac)`, where the canonical document is the artifact with every `/DocuDesk-Signature(...)` payload blanked (unchanged) and the canonical assertion JSON has sorted keys. The verifier MUST recompute the MAC over both parts, so the identity fields (`signer`, `timestamp`, `level`, `method`, `ip`) are tamper-evident: an artifact whose assertion payload is rewritten (e.g. signer name swapped) while keeping the original `mac` MUST verify with status `invalid`. Assertions without `v: 2` or without `mac` MUST report status `unverifiable` with reason `legacy-assertion-v1` and MUST NOT report `verified`. MAC comparison MUST use `hash_equals()`.

#### Scenario: Payload-rewritten artifact fails verification
- **GIVEN** a PDF signed by the native provider with assertion v2 (secret configured)
- **WHEN** the base64 assertion payload is rewritten to claim a different signer name, keeping the original `mac`, and the artifact is verified
- **THEN** the signature reports status `invalid`
- **AND** the document-level `isValid` is `false`
- @e2e exclude cryptographic mutation test — artifact byte surgery is not browser-drivable; covered by PHPUnit (tests/unit/Service/SigningVerificationServiceTest.php)

#### Scenario: Legacy v1 assertion is unverifiable, never valid
- **GIVEN** a PDF carrying a pre-rebuild assertion (no `v` field, MAC over content hash only)
- **WHEN** the artifact is verified
- **THEN** the signature reports status `unverifiable` with reason `legacy-assertion-v1`
- **AND** it is never reported `verified` or `valid: true`
- @e2e exclude legacy-artifact fixture path — covered by PHPUnit (tests/unit/Service/SigningVerificationServiceTest.php)

#### Scenario: Writer and verifier are symmetric on a genuine artifact
- **GIVEN** the signing secret is configured and a request completes via the native provider
- **WHEN** the produced artifact is verified unmodified
- **THEN** the signature reports status `verified` with the completing signer's identity, timestamp and level
- @e2e exclude signing-trust-rebuild.spec.ts not yet authored — covered by PHPUnit (tests/unit/Service/Signing/NativeSigningProviderTest.php, tests/unit/Service/SigningVerificationServiceTest.php)

### Requirement: Provider and level honesty on the completion path (REQ-DDSTR-002)
The completion path MUST NOT silently substitute providers or levels. (1) Request creation MUST validate that the requested provider supports the requested signature level (via `SigningProviderInterface::supportsLevel()`) and reject an unsupported pair with HTTP 400 before any object is persisted. (2) `produceAndStoreSignedArtifact()` MUST resolve the request's named provider strictly — an unknown provider name MUST fail the completion loudly and MUST NOT fall back to `getActiveProvider()` or the native provider. (3) Every `produceSignedArtifact()` implementation MUST refuse a level it does not support by throwing. In all three cases the request MUST NOT transition to COMPLETED and `signedDocumentRef` MUST NOT be set.

#### Scenario: QES request cannot complete with a native SES artifact
- **GIVEN** a signing request at level "QES" whose provider field is "native"
- **WHEN** request creation is attempted
- **THEN** the API rejects it with HTTP 400 naming the unsupported level/provider pair
- **AND** no SigningRequest object is persisted
- @e2e exclude backend guard — covered by PHPUnit (tests/unit/Service/SigningServiceTest.php)

#### Scenario: Unknown provider fails completion loudly
- **GIVEN** a persisted request whose provider field names a provider that is not registered
- **WHEN** the final signer signs and completion is attempted
- **THEN** the operation fails with an error naming the unavailable provider
- **AND** the request stays IN_PROGRESS with no `signedDocumentRef`
- **AND** no fallback provider produces an artifact
- @e2e exclude backend guard — provider-registry fault injection is covered by PHPUnit (tests/unit/Service/SigningServiceTest.php)

#### Scenario: Provider refuses an unsupported level at production time
- **GIVEN** `NativeSigningProvider::produceSignedArtifact()` is invoked with context level "AdES"
- **WHEN** the artifact production runs
- **THEN** it throws rather than producing an SES-mechanism artifact labelled "AdES"
- @e2e exclude defence-in-depth provider guard — covered by PHPUnit (tests/unit/Service/Signing/NativeSigningProviderTest.php)

### Requirement: Every terminal transition passes the status machine (REQ-DDSTR-003)
`decline()` MUST validate the DECLINED transition via `isValidTransition()` before mutating any signer or request object, exactly as `cancelRequest()` already does. A signing request in a terminal state (COMPLETED, DECLINED, EXPIRED, CANCELLED) MUST reject any further transition, and the signer record MUST NOT be mutated when the request-level transition is rejected.

#### Scenario: Declining a completed request is rejected
- **GIVEN** a signing request with status COMPLETED and a signer record
- **WHEN** that signer calls POST `/api/signing/requests/{id}/decline`
- **THEN** the request is rejected with an error
- **AND** the stored request status remains COMPLETED
- **AND** the signer record's status is unchanged
- @e2e exclude backend guard — covered by PHPUnit (tests/unit/Service/SigningServiceTest.php)

#### Scenario: Decline from a signable state still works
- **GIVEN** a signing request with status IN_PROGRESS and a PENDING signer owned by the caller
- **WHEN** the signer declines with a reason
- **THEN** the signer record becomes DECLINED with the reason and the request becomes DECLINED
- **AND** a `docudesk.signing.DECLINED` audit entry is written
- @e2e exclude backend guard — covered by PHPUnit (tests/unit/Service/SigningServiceTest.php)

### Requirement: Session download never serves the unsigned original (REQ-DDSTR-004)
`NativeSigningProvider::downloadSignedDocument()` MUST return a path only when the persisted session has status `completed`, a non-empty `signedDocumentPath`, and `markerEmbedded === true`. In every other case it MUST throw. The fallback branch returning the session's original `documentPath` MUST be removed — the unsigned original MUST never be presented as the signed document through any code path (extends the existing honest-completion gate to the pluggable session seam).

#### Scenario: Completed session without an embedded marker refuses download
- **GIVEN** a persisted signing session with status `completed`, `markerEmbedded: false` and an empty `signedDocumentPath`
- **WHEN** `downloadSignedDocument()` is called for its externalId
- **THEN** it throws a descriptive error
- **AND** the original document path is never returned
- @e2e exclude pluggable-provider session seam has no native UI caller (per signing-via-or-approval-with-provider-plugins); covered by PHPUnit (tests/unit/Service/Signing/NativeSigningProviderTest.php)

### Requirement: Verification reports three honest states (REQ-DDSTR-005)
Each reported signature MUST carry `status` ∈ {`verified`, `invalid`, `unverifiable`} plus a machine-readable `reason`, with `valid` retained as the derived boolean `status === 'verified'` for response-shape compatibility. An embedded external signature (`/Type /Sig` without a Filinq marker) that Filinq cannot cryptographically validate MUST report `unverifiable` with reason `external-signature-unsupported` — not `invalid`. A Filinq v2 marker whose MAC fails MUST report `invalid`. The document-level response MUST add `verdict` ∈ {`verified`, `tampered`, `unverifiable`, `mixed`} while `isValid` keeps its strict meaning (at least one signature and all `verified`). No state may ever escalate to `verified` without a passing MAC.

#### Scenario: Externally signed PDF is unverifiable, not tampered
- **GIVEN** a PDF containing a genuine external CMS signature and no Filinq marker
- **WHEN** GET `/api/signing/verify/{fileId}` is called
- **THEN** the signature reports status `unverifiable` with reason `external-signature-unsupported`
- **AND** the document `verdict` is `unverifiable` and `isValid` is `false`
- @e2e exclude backend verification contract — covered by PHPUnit (tests/unit/Service/SigningVerificationServiceTest.php)

#### Scenario: Failing MAC is reported as tampering
- **GIVEN** a v2-signed PDF whose content bytes were modified after signing
- **WHEN** the document is verified
- **THEN** the signature reports status `invalid`
- **AND** the document `verdict` is `tampered`
- @e2e exclude artifact byte-flip mutation — covered by PHPUnit (tests/unit/Service/SigningVerificationServiceTest.php)

### Requirement: The decidesk delegation seam is preserved and exposes assurance (REQ-DDSTR-010)
The rebuild MUST preserve — or version without breaking — the `docudesk-signing` OpenConnector Source contract that decidesk drives from `EIDASSignatureService` (source slug `docudesk-signing`, `EIDASSignatureService::composeDocudeskSigningRequest()`). The request contract `POST /signing-requests` accepting `{documentId, signatories, signingLevel, returnTarget}` and returning `{id | signingRequestId, signingUrl}` MUST remain backward-compatible: no existing field may be removed, renamed, or have its type changed, and any new field MUST be additive. On completion, the callback the consumer maps to `resolveSignatureStage()` MUST include the resolved eIDAS **assurance level** (`low | substantial | high`) of the completed request so decidesk's `QesGuard` can gate on it. For a native SES completion the exposed assurance MUST be `low` (the SES level floor); a broker-authenticated value is populated into this same field by `signer-identity-rails` (REQ-DDSIR-007). The assurance level MUST be the only identity-strength signal exposed on the seam — no BSN, other national identifier, or raw token may appear in the seam payload (data minimisation carried from `signer-identity-rails`).

#### Scenario: Decidesk seam request/response shape is unchanged
- **GIVEN** decidesk posts `{documentId, signatories, signingLevel, returnTarget}` to the `docudesk-signing` source `/signing-requests` endpoint
- **WHEN** the signing request is created
- **THEN** the response carries `id` (or `signingRequestId`) and `signingUrl` with unchanged field names and types
- **AND** no previously present request or response field is removed or renamed
- @e2e exclude cross-app OpenConnector Source contract — decidesk drives it with no filinq UI surface; covered by PHPUnit (tests/unit/Controller/SigningControllerTest.php)

#### Scenario: Completion payload exposes the resolved assurance level
- **GIVEN** a signing request delegated from decidesk that completes via the native provider
- **WHEN** the completion callback the consumer maps to `resolveSignatureStage()` is emitted
- **THEN** it includes the resolved eIDAS assurance level, which is `low` for a native SES completion
- **AND** `QesGuard` can read the assurance without receiving any BSN or raw token
- @e2e exclude backend completion-payload contract — covered by PHPUnit (tests/unit/Event/SigningConcludedEventTest.php, tests/unit/Service/SigningServiceTest.php)


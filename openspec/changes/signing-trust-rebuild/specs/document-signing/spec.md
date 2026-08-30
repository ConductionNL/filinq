# document-signing Specification (delta)

---
status: proposed
---

## Purpose

Rebuild residuals of the signing security wave (GH #282/#284/#287/#304 —
verified open at HEAD, with the already-shipped fixes explicitly out of
scope): bind signer identity into the signed artifact's MAC, report
verification honestly in three states, enforce provider/level honesty on the
completion path, route every terminal transition through the status machine,
make the session download fail closed, and preserve the decidesk delegation
seam (`docudesk-signing`) backward-compatibly while exposing the resolved
eIDAS assurance level on completion. All requirements are ADDED; no
existing `document-signing` requirement is weakened.

## ADDED Requirements

### Requirement: Signed-artifact assertion binds signer identity cryptographically (REQ-DDSTR-001)

The native SES assertion MUST carry a version discriminator `v: 2` and a MAC
computed as `HMAC-SHA256(secret, sha256(canonical-document) . "\n" .
canonical-JSON-of-assertion-without-mac)`, where the canonical document is the
artifact with every `/DocuDesk-Signature(...)` payload blanked (unchanged) and
the canonical assertion JSON has sorted keys. The verifier MUST recompute the
MAC over both parts, so the identity fields (`signer`, `timestamp`, `level`,
`method`, `ip`) are tamper-evident: an artifact whose assertion payload is
rewritten (e.g. signer name swapped) while keeping the original `mac` MUST
verify with status `invalid`. Assertions without `v: 2` or without `mac` MUST
report status `unverifiable` with reason `legacy-assertion-v1` and MUST NOT
report `verified`. MAC comparison MUST use `hash_equals()`.

#### Scenario: Payload-rewritten artifact fails verification

- GIVEN a PDF signed by the native provider with assertion v2 (secret configured)
- WHEN the base64 assertion payload is rewritten to claim a different signer name, keeping the original `mac`, and the artifact is verified
- THEN the signature reports status `invalid`
- AND the document-level `isValid` is `false`
- @e2e exclude cryptographic mutation test — artifact byte surgery is not browser-drivable; covered by PHPUnit (tests/unit/Service/SigningVerificationServiceTest.php)

#### Scenario: Legacy v1 assertion is unverifiable, never valid

- GIVEN a PDF carrying a pre-rebuild assertion (no `v` field, MAC over content hash only)
- WHEN the artifact is verified
- THEN the signature reports status `unverifiable` with reason `legacy-assertion-v1`
- AND it is never reported `verified` or `valid: true`
- @e2e exclude legacy-artifact fixture path — covered by PHPUnit (tests/unit/Service/SigningVerificationServiceTest.php)

#### Scenario: Writer and verifier are symmetric on a genuine artifact

- GIVEN the signing secret is configured and a request completes via the native provider
- WHEN the produced artifact is verified unmodified
- THEN the signature reports status `verified` with the completing signer's identity, timestamp and level
- @e2e tests/e2e/spec-coverage/signing-trust-rebuild.spec.ts

### Requirement: Provider and level honesty on the completion path (REQ-DDSTR-002)

The completion path MUST NOT silently substitute providers or levels.
(1) Request creation MUST validate that the requested provider supports the
requested signature level (via `SigningProviderInterface::supportsLevel()`)
and reject an unsupported pair with HTTP 400 before any object is persisted.
(2) `produceAndStoreSignedArtifact()` MUST resolve the request's named
provider strictly — an unknown provider name MUST fail the completion loudly
and MUST NOT fall back to `getActiveProvider()` or the native provider.
(3) Every `produceSignedArtifact()` implementation MUST refuse a level it does
not support by throwing. In all three cases the request MUST NOT transition to
COMPLETED and `signedDocumentRef` MUST NOT be set.

#### Scenario: QES request cannot complete with a native SES artifact

- GIVEN a signing request at level "QES" whose provider field is "native"
- WHEN request creation is attempted
- THEN the API rejects it with HTTP 400 naming the unsupported level/provider pair
- AND no SigningRequest object is persisted
- @e2e tests/e2e/spec-coverage/signing-trust-rebuild.spec.ts

#### Scenario: Unknown provider fails completion loudly

- GIVEN a persisted request whose provider field names a provider that is not registered
- WHEN the final signer signs and completion is attempted
- THEN the operation fails with an error naming the unavailable provider
- AND the request stays IN_PROGRESS with no `signedDocumentRef`
- AND no fallback provider produces an artifact
- @e2e exclude backend guard — provider-registry fault injection is covered by PHPUnit (tests/unit/Service/SigningServiceTest.php)

#### Scenario: Provider refuses an unsupported level at production time

- GIVEN `NativeSigningProvider::produceSignedArtifact()` is invoked with context level "AdES"
- WHEN the artifact production runs
- THEN it throws rather than producing an SES-mechanism artifact labelled "AdES"
- @e2e exclude defence-in-depth provider guard — covered by PHPUnit (tests/unit/Service/Signing/NativeSigningProviderTest.php)

### Requirement: Every terminal transition passes the status machine (REQ-DDSTR-003)

`decline()` MUST validate the DECLINED transition via `isValidTransition()`
before mutating any signer or request object, exactly as `cancelRequest()`
already does. A signing request in a terminal state (COMPLETED, DECLINED,
EXPIRED, CANCELLED) MUST reject any further transition, and the signer record
MUST NOT be mutated when the request-level transition is rejected.

#### Scenario: Declining a completed request is rejected

- GIVEN a signing request with status COMPLETED and a signer record
- WHEN that signer calls POST `/api/signing/requests/{id}/decline`
- THEN the request is rejected with an error
- AND the stored request status remains COMPLETED
- AND the signer record's status is unchanged
- @e2e tests/e2e/spec-coverage/signing-trust-rebuild.spec.ts

#### Scenario: Decline from a signable state still works

- GIVEN a signing request with status IN_PROGRESS and a PENDING signer owned by the caller
- WHEN the signer declines with a reason
- THEN the signer record becomes DECLINED with the reason and the request becomes DECLINED
- AND a `docudesk.signing.DECLINED` audit entry is written
- @e2e tests/e2e/spec-coverage/signing-trust-rebuild.spec.ts

### Requirement: Session download never serves the unsigned original (REQ-DDSTR-004)

`NativeSigningProvider::downloadSignedDocument()` MUST return a path only when
the persisted session has status `completed`, a non-empty
`signedDocumentPath`, and `markerEmbedded === true`. In every other case it
MUST throw. The fallback branch returning the session's original
`documentPath` MUST be removed — the unsigned original MUST never be presented
as the signed document through any code path (extends the existing
honest-completion gate to the pluggable session seam).

#### Scenario: Completed session without an embedded marker refuses download

- GIVEN a persisted signing session with status `completed`, `markerEmbedded: false` and an empty `signedDocumentPath`
- WHEN `downloadSignedDocument()` is called for its externalId
- THEN it throws a descriptive error
- AND the original document path is never returned
- @e2e exclude pluggable-provider session seam has no native UI caller (per signing-via-or-approval-with-provider-plugins); covered by PHPUnit (tests/unit/Service/Signing/NativeSigningProviderTest.php)

### Requirement: Verification reports three honest states (REQ-DDSTR-005)

Each reported signature MUST carry `status` ∈ {`verified`, `invalid`,
`unverifiable`} plus a machine-readable `reason`, with `valid` retained as the
derived boolean `status === 'verified'` for response-shape compatibility. An
embedded external signature (`/Type /Sig` without a Filinq marker) that
Filinq cannot cryptographically validate MUST report `unverifiable` with
reason `external-signature-unsupported` — not `invalid`. A Filinq v2 marker
whose MAC fails MUST report `invalid`. The document-level response MUST add
`verdict` ∈ {`verified`, `tampered`, `unverifiable`, `mixed`} while `isValid`
keeps its strict meaning (at least one signature and all `verified`). No state
may ever escalate to `verified` without a passing MAC.

#### Scenario: Externally signed PDF is unverifiable, not tampered

- GIVEN a PDF containing a genuine external CMS signature and no Filinq marker
- WHEN GET `/api/signing/verify/{fileId}` is called
- THEN the signature reports status `unverifiable` with reason `external-signature-unsupported`
- AND the document `verdict` is `unverifiable` and `isValid` is `false`
- @e2e tests/e2e/spec-coverage/signing-trust-rebuild.spec.ts

#### Scenario: Failing MAC is reported as tampering

- GIVEN a v2-signed PDF whose content bytes were modified after signing
- WHEN the document is verified
- THEN the signature reports status `invalid`
- AND the document `verdict` is `tampered`
- @e2e exclude artifact byte-flip mutation — covered by PHPUnit (tests/unit/Service/SigningVerificationServiceTest.php)

### Requirement: The decidesk delegation seam is preserved and exposes assurance (REQ-DDSTR-010)

The rebuild MUST preserve — or version without breaking — the
`docudesk-signing` OpenConnector Source contract that decidesk drives from
`EIDASSignatureService` (verified at HEAD: source slug `docudesk-signing`,
`EIDASSignatureService::composeDocudeskSigningRequest()`). The request contract
`POST /signing-requests` accepting `{documentId, signatories, signingLevel,
returnTarget}` and returning `{id | signingRequestId, signingUrl}` MUST remain
backward-compatible: no existing field may be removed, renamed, or have its type
changed, and any new field MUST be additive. On completion, the callback the
consumer maps to `resolveSignatureStage()` MUST include the resolved eIDAS
**assurance level** (`low | substantial | high`) of the completed request so
decidesk's `QesGuard` can gate on it. For a native SES completion the exposed
assurance MUST be `low` (the SES level floor); a broker-authenticated value is
populated into this same field by `signer-identity-rails` (REQ-DDSIR-007). The
assurance level MUST be the only identity-strength signal exposed on the seam —
no BSN, other national identifier, or raw token may appear in the seam payload
(data minimisation carried from `signer-identity-rails`).

#### Scenario: Decidesk seam request/response shape is unchanged

- GIVEN decidesk posts `{documentId, signatories, signingLevel, returnTarget}` to the `docudesk-signing` source `/signing-requests` endpoint
- WHEN the signing request is created
- THEN the response carries `id` (or `signingRequestId`) and `signingUrl` with unchanged field names and types
- AND no previously present request or response field is removed or renamed
- @e2e exclude cross-app OpenConnector Source contract — decidesk drives it with no filinq UI surface; covered by Newman (docudesk-signing collection) + PHPUnit (tests/unit/Controller/SigningControllerTest.php)

#### Scenario: Completion payload exposes the resolved assurance level

- GIVEN a signing request delegated from decidesk that completes via the native provider
- WHEN the completion callback the consumer maps to `resolveSignatureStage()` is emitted
- THEN it includes the resolved eIDAS assurance level, which is `low` for a native SES completion
- AND `QesGuard` can read the assurance without receiving any BSN or raw token
- @e2e exclude backend completion-payload contract — covered by PHPUnit (tests/unit/Service/SigningServiceTest.php) + Newman

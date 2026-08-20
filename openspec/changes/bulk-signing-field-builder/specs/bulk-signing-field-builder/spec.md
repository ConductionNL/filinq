# bulk-signing-field-builder Specification (delta)

---
status: proposed
---

## Purpose

Competitive-parity signing workflow features on top of the rebuilt honest
pipeline (`depends_on: signing-trust-rebuild`): bulk send (CSV/XLSX recipient
lists creating a tracked batch of ordinary signing requests from one document
or template — DocuSeal/DocuSign parity), per-signer visual signature field
placement stored on the request and rendered tamper-evidently into the
artifact (DocuSeal parity; conditional fields are future scope), and
multi-document envelope grouping with a single signing ceremony and
per-document artifacts/audit (LibreSign parity). Distinct from the existing
`document-signing` "Bulk signing" requirement, which covers bulk *signing* by
one signer and is unchanged.

## ADDED Requirements

### Requirement: Batch, envelope and placement data live in the signing register (REQ-DDBSF-001)

The app MUST declare, additively and with a register version bump for boot
import: schema `bulkSigningBatch` (`title`, `documentFileId` XOR
`templateRef`, `signatureLevel`, `signingMode`, `provider`,
`recipientSource` enum `csv|xlsx`, `totalRows`, `acceptedRows`,
`rejectedRows[]` of `{row, reason}`, `status` enum
`validating|ready|creating|completed|completed_with_errors|cancelled`,
`requestRefs[]`, `createdBy`), schema `signingEnvelope` (`title`,
`requestRefs[]` ordered, `initiatorUserId`, `status` enum
`pending|in_progress|completed|partially_declined|cancelled`), and additive
`signingRequest` properties `fieldPlacements[]` of
`{signerIndex, page, x, y, width, height, type}` (type enum
`signature|initials|date|text|checkbox`, coordinates normalised 0–1) and
`envelopeRef`. All objects MUST be stored via OpenRegister (ADR-001); no
existing property may be dropped.

#### Scenario: Register import ships the new shapes additively

- GIVEN the bumped register JSON is imported on app boot
- WHEN the `signing` register's schemas are inspected
- THEN `bulkSigningBatch` and `signingEnvelope` exist with the declared properties
- AND `signingRequest` carries `fieldPlacements` and `envelopeRef` alongside every pre-existing property
- @e2e exclude declarative register import — covered by the manifest validation test + PHPUnit register-drift pin, no UI surface

### Requirement: Bulk send validates first, then creates isolated ordinary requests (REQ-DDBSF-002)

Bulk send MUST be two-phase. Phase 1: an uploaded CSV/XLSX recipient list is
parsed and validated (parse errors, invalid emails, unresolvable recipients,
duplicate rows) into a persisted validation report on the batch (status
`ready`); nothing is sent. Phase 2: only on explicit confirmation, a
background job creates one signing request per accepted row through the
ordinary single-request creation path — every request subject to the same
validation, level/provider honesty and assurance floors as a manually created
request — with per-row error isolation (a failing row is recorded in
`rejectedRows` and MUST NOT abort remaining rows). The batch MUST track
progress and terminal status (`completed` / `completed_with_errors`), and
batch cancel MUST cancel only member requests still in a cancellable state.
Row and size caps MUST be enforced (default 1000 rows) and cell values MUST be
treated as inert data (no formula evaluation). The validation report MUST be
readable only by the batch initiator or an admin.

#### Scenario: Mixed CSV yields a report, then a partial batch

- GIVEN a CSV of 50 recipients of which 3 rows carry invalid emails
- WHEN the file is uploaded and validated, and the initiator confirms sending
- THEN the validation report names exactly the 3 rejected rows with reasons before anything is sent
- AND 47 signing requests are created as a tracked batch with `requestRefs` and progress
- AND the batch ends `completed_with_errors` listing the 3 rows
- @e2e tests/e2e/spec-coverage/bulk-signing-field-builder.spec.ts

#### Scenario: A failing row does not abort the batch

- GIVEN a confirmed batch where one accepted row fails at request-creation time (e.g. the recipient was deleted mid-flight)
- WHEN the background job processes the batch
- THEN that row is appended to `rejectedRows` with its reason
- AND every other row's request is still created
- @e2e exclude mid-flight fault injection in the background job — covered by PHPUnit (tests/unit/Service/BulkSigningServiceTest.php)

#### Scenario: Batch requests pass the same honesty gates

- GIVEN a batch configured at level "QES" with provider "native"
- WHEN validation runs
- THEN the batch is rejected with the same level/provider error as a single request (REQ-DDSTR-002)
- AND no member request is created
- @e2e exclude gate-parity assertion — covered by PHPUnit (tests/unit/Service/BulkSigningServiceTest.php)

### Requirement: Field placements are stored per signer and rendered tamper-evidently (REQ-DDBSF-003)

The request form MUST provide a visual placement editor over a page preview
that stores `fieldPlacements` on the signing request (per-signer, page,
normalised 0–1 coordinates, five static types). At artifact production the
native provider MUST render the placed fields as visible blocks at their
positions BEFORE computing the v2 canonical form and MAC, so the visible
blocks are covered by the MAC and any post-hoc move or alteration fails
verification. External providers MUST receive the placements in their
provider payload for provider-native mapping. Requests without placements
MUST produce artifacts exactly as before. Conditional fields MUST NOT ship in
this change (the schema carries no condition key; future scope).

#### Scenario: A placed field appears in the artifact at its position

- GIVEN a request with a `signature` placement for signer 2 on page 3 at (0.6, 0.8)
- WHEN the request completes and the artifact is produced
- THEN page 3 of the artifact shows a visible signature block for signer 2 at that position
- AND the artifact passes v2 MAC verification
- @e2e tests/e2e/spec-coverage/bulk-signing-field-builder.spec.ts

#### Scenario: Moving a rendered block after signing trips verification

- GIVEN a completed artifact containing rendered placement blocks
- WHEN the block's bytes are relocated/altered and the document is verified
- THEN verification reports status `invalid` / verdict `tampered`
- @e2e exclude artifact byte-surgery mutation — covered by PHPUnit (tests/unit/Service/Signing/NativeSigningProviderTest.php)

#### Scenario: Placement-free requests are byte-compatible

- GIVEN a request without `fieldPlacements`
- WHEN its artifact is produced
- THEN the artifact contains no rendered blocks and verifies exactly as under `signing-trust-rebuild`
- @e2e exclude regression parity — covered by PHPUnit round-trip tests

### Requirement: Envelopes group documents into one ceremony with per-document records (REQ-DDBSF-004)

Envelope creation MUST accept N documents and one signer roster and create N
ordinary signing requests (each carrying `envelopeRef`) plus one
`signingEnvelope`. Each signer MUST receive one notification per envelope
(not per document), and the signing view MUST present all of the signer's
pending records in the envelope for a single ceremony in which each document
is signed through the ordinary `sign()` path with all its gates. Artifacts
and audit trails MUST remain per document. The envelope status MUST roll up
from member requests: `completed` only when all members are COMPLETED; any
member DECLINED yields `partially_declined` while already-signed members
remain signed (per-document legal independence). Envelope read/cancel MUST be
gated to the initiator, a member signer (read only), or an admin — the same
posture as single requests.

#### Scenario: Three documents, one ceremony, three artifacts

- GIVEN an envelope of 3 documents with signers A and B
- WHEN A receives notifications and completes the ceremony, and B does the same
- THEN A and B each received one envelope notification per round, not three
- AND 3 COMPLETED requests exist with 3 verifiable artifacts and 3 chronological audit trails
- AND the envelope status is `completed`
- @e2e tests/e2e/spec-coverage/bulk-signing-field-builder.spec.ts

#### Scenario: One decline yields a partial envelope, signed documents stand

- GIVEN the same envelope where B declines document 2 after A signed everything
- WHEN the envelope status is read
- THEN it is `partially_declined`
- AND documents 1 and 3 proceed per their own request lifecycles and document 2 is DECLINED
- AND A's completed signatures are unchanged
- @e2e tests/e2e/spec-coverage/bulk-signing-field-builder.spec.ts

#### Scenario: Ceremony signing bypasses no gates

- GIVEN an envelope member request whose status machine or assurance gate would reject a direct sign attempt
- WHEN the signer uses "sign all" in the ceremony
- THEN that member document is rejected with the same error as a direct call
- AND the remaining member documents are still processed individually
- @e2e exclude gate-parity fault injection — covered by PHPUnit (tests/unit/Service/SigningEnvelopeServiceTest.php)

### Requirement: Batch and envelope surfaces are first-class UI (REQ-DDBSF-005)

The signing UI MUST add: a bulk-send wizard (file upload → validation report →
confirm) and batch list/detail with progress and the rejected-row report; an
envelope creation flow and envelope detail showing member-document statuses;
and the placement editor on the request form. All surfaces MUST use the shared
component library (ADR-012), keep modals in dedicated files, use NL Design
System tokens (ADR-003), and ship EN source strings with NL translations
(ADR-005).

#### Scenario: The wizard walks upload to tracked batch

- GIVEN a user with a recipient CSV and a document
- WHEN they complete the bulk-send wizard
- THEN they see the validation report before confirming, and after confirming a batch detail page with live progress and per-row outcomes
- @e2e tests/e2e/spec-coverage/bulk-signing-field-builder.spec.ts

#### Scenario: Envelope detail rolls member statuses up

- GIVEN an in-progress envelope
- WHEN the initiator opens its detail page
- THEN each member document's request status is listed and the envelope roll-up status matches the members
- @e2e tests/e2e/spec-coverage/bulk-signing-field-builder.spec.ts

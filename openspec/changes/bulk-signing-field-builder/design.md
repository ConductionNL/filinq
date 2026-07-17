# Design: bulk-signing-field-builder

## Context

Verified at HEAD: the signing register carries `signingRequest`,
`signerRecord`, `signingAuditEntry` (deprecated write path), `signingSession`.
Request creation (`SigningService::createRequest()`) validates level/mode and
creates one request + N signer records; the completion path produces the
signed artifact via the provider (`produceAndStoreSignedArtifact`, honest
gates per `signing-trust-rebuild`). The frontend has
`src/views/signing/SigningRequestForm.vue` (single request),
`BulkSigningPanel.vue` (bulk *signing* as a signer — a different feature that
stays), and a `signing.js` store module. There is no batch, no envelope, no
field placement anywhere. PDF composition exists in the app (`PdfService`,
used by generation/waarmerk work) — placement rendering reuses it (ADR-011).
Async batch machinery precedent: wave-1 `redaction-at-scale` uses queued
OR-backed batches with progress; this change mirrors that shape rather than
inventing a second batch idiom.

Related: `template-management` (template refs for per-recipient generation),
`docudesk-signing-events` (terminal-event contract — envelope roll-up listens
to the same conclusions), `multi-tenant-hardening` (org scoping of the new
objects arrives fleet-wide there).

## Goals / Non-Goals

**Goals**

1. One-to-many send: CSV/XLSX → validation report → tracked batch of ordinary
   signing requests (reuse, don't fork, the single-request path).
2. Per-signer field placement stored on the request and rendered visibly into
   the artifact without weakening the v2 MAC.
3. Envelope = one ceremony over N documents with per-document artifacts and
   audit.

**Non-Goals**

- Conditional/computed fields; external accountless recipients; merged
  envelope artifact; any change to bulk *signing* (`bulkSign()`), templates,
  or tenancy (see proposal Out of Scope).

## Decisions

### D1 — Batch is a coordinator object; rows become ordinary requests

`bulkSigningBatch` (register `signing`): `title`, `documentFileId` XOR
`templateRef`, `signatureLevel`, `signingMode`, `provider`,
`requiredAssurance` (from `signer-identity-rails` when present, else level
floor), `recipientSource` (`csv|xlsx`), `totalRows`, `acceptedRows`,
`rejectedRows` (array of `{row, reason}`), `status`
(`validating|ready|creating|completed|completed_with_errors|cancelled`),
`requestRefs` (array of created request UUIDs), `createdBy`, timestamps.
Each accepted row creates a **normal** `signingRequest` through
`SigningService::createRequest()` — the batch never bypasses validation,
level floors or honest-completion gates. Rejected alternative: a
"multi-recipient request" object — would fork the entire lifecycle/status
machine and every downstream consumer (audit, events, portal) for no gain.

Two-phase UX: (1) upload + parse → validation report persisted on the batch
(status `ready`, nothing sent); (2) explicit confirm → background job
(`BulkSigningJob`, NC background job like `SigningExpirationJob`) creates
requests row by row with per-row try/catch — a failing row lands in
`rejectedRows`, the loop continues (per-row isolation). Parsing: CSV native;
XLSX via the spreadsheet library already vendored for document processing if
present, else CSV-only ships first and XLSX rides the same seam (apply-time
ADR-011 check — do not add a heavy dependency for one sheet format without
checking OR/app vendor trees first).

### D2 — Field placements live on the request; rendering is provider work

`signingRequest.fieldPlacements` (additive property): array of
`{signerIndex, page, x, y, width, height, type}` with `type` ∈
`signature|initials|date|text|checkbox` and coordinates normalised 0–1
page-relative (resolution-independent; a PDF page box maps them
deterministically). `signerIndex` refers to the position in `signerIds` (a
UUID ref would dangle when signer records are created after form editing;
index is resolved at render time). The editor is a Vue overlay on a PDF-page
preview writing placements to the form model — placement is DATA on the
request; the visible rendering happens once, at artifact production:
`NativeSigningProvider::produceSignedArtifact()` draws the field blocks
(signer name/date/initials text in a bordered box) onto the relevant pages via
the existing PDF composition service, THEN computes the v2 canonical form and
MAC over the composed bytes — so the visible blocks are inside the
MAC-protected content and cannot be moved/altered without tripping
verification. External providers receive placements in their provider payload
(ValidSign et al. have their own placement vocabulary; mapping is per-plugin).
Conditional fields: the schema deliberately has no `condition` key — future
scope, additive when it comes.

### D3 — Envelope is a grouping object; ceremony is a UI/notification concern

`signingEnvelope` (register `signing`): `title`, `requestRefs` (ordered
array), `initiatorUserId`, `status` (`pending|in_progress|completed|
partially_declined|cancelled`), timestamps. Member requests stay fully
independent at the data/audit/artifact level (per-document artifacts + per-
document trails — the legal record is per document). Envelope behaviours:

- **Creation**: one form, N documents, one signer roster applied to all →
  N requests + 1 envelope; member requests carry `envelopeRef` (additive
  property on `signingRequest`) for back-navigation.
- **One notification per signer** per envelope (not per document): the
  notification job groups by envelopeRef.
- **One ceremony**: the signing view loads all of a signer's pending records
  in the envelope; "sign all" iterates the ordinary `sign()` calls
  sequentially server-side — each document keeps its own audit entry and
  artifact (and its own assurance gate when identity rails land).
- **Roll-up**: envelope status derives from member statuses (listener on the
  same terminal transitions `docudesk-signing-events` hooks); COMPLETED only
  when all members COMPLETED; any DECLINED → `partially_declined` (members
  already signed stay signed — per-document legal independence, LibreSign
  semantics).

Rejected alternative: one request with N documents — breaks the 1:1
request↔artifact↔audit contract every existing requirement and consumer
assumes.

### D4 — Reuse boundaries

- Template-referenced batches call the existing generation service per row
  (template + row columns as merge data) and then create the request on the
  generated file — no new generation code (ADR-011).
- Batch/envelope lists and detail surfaces use CnDataTable/CnDetailPage etc.
  (ADR-012); modals in `src/modals/` (hydra modal-isolation gate).
- No new notification dialect — envelope grouping filters/aggregates within
  the existing notification path (ADR-031 untouched).

## OpenRegister usage (ADR-001)

All new objects in the existing `signing` register:
`bulkSigningBatch` (new schema), `signingEnvelope` (new schema),
`signingRequest.fieldPlacements` + `signingRequest.envelopeRef` (additive
properties). Register version bump for boot import; union-additive diff.
All reads/writes via ObjectService; batch progress polled from the batch
object (no bespoke endpoint state).

## Seed Data

Nil-UUID fixtures (self-evidently fake): batch
`00000000-0000-0000-0000-00000000f001` (Demostad, status
`completed_with_errors`, 5 rows / 4 accepted / 1 rejected `{row: 3, reason:
'invalid email'}`), envelope `…f002` over demo requests `…f003`/`…f004`, and a
demo request carrying two `fieldPlacements` (signature p1 @ 0.6/0.8,
initials p2 @ 0.1/0.9). CSV fixture committed under `tests/fixtures/`
(emails `@example.invalid` only).

## Security Considerations

- Batch creation authz = the same as single-request creation (initiator
  scoping); batch/envelope read/cancel gated to initiator-or-admin exactly
  like `cancelRequest` (no new IDOR surface — hydra gates 6/7/9 on the new
  controller methods).
- CSV parsing: size cap, row cap (configurable, default 1000 — chunk-safe
  under the OR IN()-list lessons), formula-injection-inert (values are data,
  never evaluated), MIME/extension validation.
- Per-row isolation must not become an oracle: the validation report is
  visible only to the batch initiator/admin.
- Placement rendering happens BEFORE MAC computation — visible blocks are
  tamper-evident; a post-hoc moved block fails verification (mutation test).
- Envelope "sign all" runs each ordinary `sign()` with all its gates
  (ownership, status machine, assurance when present) — no batch bypass.

## Risks / Trade-offs

- **XLSX dependency weight** — mitigated by the CSV-first seam (D1); XLSX may
  ship as a fast-follow if no vendored parser exists.
- **Placement coordinates vs. exotic PDFs** (rotated/cropped pages):
  normalised coordinates map via the page box; artifacts with unusual
  geometry may misplace blocks — the marker/MAC stays authoritative, and the
  editor previews the real page so the author sees what ships.
- **Envelope partial outcomes** (some signed, one declined) are legally
  intentional but need clear UI copy — PO review at apply time.
- **Batch size ambition**: 1000-row default cap is deliberate; the
  redaction-at-scale queue pattern proves bigger volumes are a job-queue
  concern, not a request-loop concern — raise the cap only with queue
  batching evidence.

## Migration Plan

1. Register version bump adds two schemas + two additive properties; boot
   import; no existing data touched.
2. Existing requests have no `envelopeRef`/`fieldPlacements` — all flows
   treat absence as "standalone request / no placements" (current behaviour).
3. No artifact-format change for requests without placements; with
   placements, artifacts remain v2-MAC verifiable (same verifier).

## Open Questions

- Should a batch optionally create an envelope per recipient when
  `templateRef` generates multiple documents per row? Deferred — no verified
  demand; keep row = one document = one request.
- Envelope-level deadline overriding member deadlines: PO call at apply time
  (LibreSign has a single flow deadline; current design keeps per-request
  deadlines authoritative).

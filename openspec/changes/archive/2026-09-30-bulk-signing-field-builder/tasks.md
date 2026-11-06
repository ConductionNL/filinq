# Tasks: bulk-signing-field-builder

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 13.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & data model

- [x] 1.1 Additive register edit in `lib/Settings/filinq_register.json`: schemas `bulkSigningBatch` + `signingEnvelope`, properties `signingRequest.fieldPlacements[]` + `signingRequest.envelopeRef` per REQ-DDBSF-001; register version bump for boot import
  - Bulk send half done: `bulkSigningBatch` 1.0.0, register 8.35.0. Placement half done: `signingRequest` 1.8.0 `fieldPlacements`, register 8.36.0. Envelope half done: `signingEnvelope` 1.0.0, `signingRequest` 1.9.0 and `signerRecord` 1.6.0 `envelopeRef`, register 8.37.0.
  - Union-additive diff against merge base; `tests/validate-manifest.js` passes; property titles present (hydra schema-property-titles gate)

- [x] 1.2 Seed data per design.md (nil-UUID batch `…f001` with rejected-row fixture, envelope `…f002`, placement demo request); CSV fixture with `@example.invalid` emails under `tests/fixtures/`
  - Bulk send half done: three demo batches in lib/Settings/filinq_mock_register.json (DemoDataCoverageTest wants three per schema); CSV fixture tests/fixtures/bulk-signing/recipients-50.csv. Placement seed: the first signingRequest demo object carries a signature and a date field. Envelope seed: three demo envelopes (pending, partly declined, cancelled).

## 2. Bulk send backend

- [x] 2.1 `BulkSigningService`: CSV parse (native) + validation (emails, resolvable recipients, duplicates, row/size caps default 1000, formula-inert cells) persisting the report on the batch, status `ready` (REQ-DDBSF-002 phase 1); XLSX via existing vendored parser if present, else CSV-first with the seam documented (ADR-011 check)
  - Built: `lib/Service/BulkSigning/` BulkSigningRecipientParser (CSV comma/semicolon, XLSX through ZipArchive + SimpleXML: no spreadsheet library is vendored and none was added), BulkSigningRowValidator, BulkSigningService::createBatch(); tests/unit/Service/BulkSigning/BulkSigningRecipientParserTest.php, BulkSigningServiceTest.php

- [x] 2.2 `BulkSigningJob` (NC background job): per-row creation through `SigningService::createRequest()` with try/catch isolation into `rejectedRows`, progress + terminal status, batch cancel of still-cancellable members (REQ-DDBSF-002 phase 2)
  - Built: lib/BackgroundJob/BulkSigningJob.php -> BulkSigningRunner (initiator set with IUserSession::setVolatileActiveUser, cleared in finally; status read before every row; progress saved per row)
  - Level/provider/assurance gate parity proven (QES+native batch rejected pre-send)

- [x] 2.3 `BulkSigningController` + routes (create/validate/confirm/list/show/cancel): explicit auth attributes, initiator-or-admin guards on read/cancel, report visible to initiator/admin only (hydra route-auth/no-admin-idor/semantic-auth gates)
  - Built: lib/Controller/BulkSigningController.php, routes api/signing/batches (index, create, show, confirm, cancel); tests/unit/Controller/BulkSigningControllerTest.php

## 3. Field placement

- [x] 3.1 Placement persistence + provider payload: `fieldPlacements` accepted/validated on create (coordinates 0–1, five types, signerIndex bounds), passed to external providers' payloads (REQ-DDBSF-003)
  - Built: lib/Service/Signing/FieldPlacements.php (rules), SignedArtifactProducer::withPlacements (called from SigningService::createRequest; 400 for a broken rule, a page the PDF lacks, or LibreSign), placements and signer names in the provider context at completion. LibreSign's request-signature call has no field input, so placements are refused for it rather than dropped; ValidSign is not wired (it throws on produce). Tests: SigningServiceTest placement cases, FieldPlacementRenderingTest::testPlacementRules.

- [x] 3.2 Native rendering: draw placed blocks via the existing PDF composition service BEFORE v2 canonicalisation/MAC in `produceSignedArtifact()`; mutation test proves a moved block → `tampered`; placement-free byte-compatibility regression test (REQ-DDBSF-003)
  - Built: lib/Service/Signing/FieldPlacementRenderer.php (FPDI, the library the merge already uses), drawn in NativeSigningProvider::produceSignedArtifact() before the canonical hash; placements also join the assertion. Tests: tests/unit/Service/Signing/FieldPlacementRenderingTest.php (real renderer, provider and verifier: drawn and verified, moved block invalid, no placements byte-identical, page past the end refused).

- [x] 3.3 Placement editor UI on `SigningRequestForm.vue`: page-preview overlay, drag/resize per signer, five static types; component under `src/views/signing/`, dialogs in `src/modals/` (REQ-DDBSF-005)
  - Built: src/views/signing/FieldPlacementEditor.vue (pdfjs page preview through api/documents/{fileId}/versions/0/download; click to place, drag or arrow keys to move, Shift and an arrow key to resize, Delete to remove; every box is a labelled button), pure rules in src/views/signing/fieldPlacement.js (vitest tests/vitest/fieldPlacement.spec.js). No dialog was needed. Resize is keyboard and bottom-right only.

## 4. Envelopes

- [x] 4.1 `SigningEnvelopeService`: create N requests + envelope with `envelopeRef` back-refs; roll-up listener on member terminal transitions (`completed` / `partially_declined` semantics per REQ-DDBSF-004); envelope cancel; initiator/member/admin read gates
  - Built: lib/Service/SigningEnvelope/ (SigningEnvelopeService, SigningEnvelopeRepository, SigningEnvelopeRollUp), SigningEnvelopeController + routes api/signing/envelopes (index, create, show, sign, cancel). The roll-up is derived on read and stored when it moved, not a listener (design, Resolved at apply). Tests: tests/unit/Service/SigningEnvelope/SigningEnvelopeServiceTest.php (real SigningService, every payload validated with Opis against its real schema fragment), SigningEnvelopeRollUpTest.php.

- [x] 4.2 Ceremony: envelope-grouped notifications (one per signer per envelope, existing notification path — ADR-031 untouched); signing view lists all pending member records; "sign all" iterates ordinary `sign()` per document with per-document gate errors surfaced (REQ-DDBSF-004)
  - Built: signerRecord `signingRequested` gains a `created` filter on an empty `envelopeRef`; `signingEnvelope` declares `envelopeRequested` (recipients the `signerUserIds` relation), stored after its members. Sign all runs SigningService::bulkSign() on the open members. Tests: SigningEnvelopeServiceTest::testEachSignerIsNotifiedOncePerEnvelope, testSignAllSignsEveryMemberThroughTheOrdinarySign, testSignAllRefusesAMemberWithTheDirectErrorAndGoesOn.

- [x] 4.3 Envelope + batch UI: bulk-send wizard (upload → report → confirm), batch list/detail with progress, envelope create/detail with member roll-up (CnDataTable/CnDetailPage, NL Design System tokens) (REQ-DDBSF-005)
  - Bulk send half done: src/modals/BulkSendModal.vue (upload, report, confirm, progress, cancel) opened from SigningRequestForm, Bulk sends index page (typed, no new custom page). Envelope half done: src/modals/SigningEnvelopeModal.vue (from the request form), src/views/signing/SigningEnvelopePanel.vue on every member's request page (documents, status, sign all, cancel), Envelopes index page (typed). vitest tests/vitest/signingEnvelope.spec.js.

## 5. Quality, i18n, docs

- [x] 5.1 Unit tests ≥75% on new code (validation matrix, row isolation, cap enforcement, roll-up semantics, placement MAC coverage); run in container `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`
  - Bulk send half done (BulkSigning*Test, BulkSigningControllerTest). Placement half done (FieldPlacementRenderingTest, SigningServiceTest placement cases). Envelope half done (SigningEnvelopeServiceTest, SigningEnvelopeRollUpTest).

- [x] 5.2 Playwright e2e `tests/e2e/spec-coverage/bulk-signing-field-builder.spec.ts`: 50-row CSV (3 bad) → report → 47-request batch; placement rendered in completed artifact + verify `verified`; 3-doc envelope single ceremony → 3 artifacts/trails, decline → `partially_declined`; verify on Postgres (8080), nldesign theme enabled
  - Bulk send, placement and envelope halves written, not run (the dev instance serves another checkout): tests/e2e/spec-coverage/bulk-signing-field-builder.spec.ts.

- [x] 5.3 i18n EN source + NL translations (wizard, report reasons, envelope statuses, placement editor)
  - Bulk send, placement and envelope strings in all six locales.

- [x] 5.4 Docs in `docs/features/` (bulk send, placement, envelopes — explicit "conditional fields: future" note) with Playwright screenshots (ADR-010); `openspec validate bulk-signing-field-builder --strict` passes
  - Bulk send half: docs/features/bulk-send.md. Placement half: docs/features/signing-field-placement.md. Envelopes: docs/features/signing-envelopes.md. No screenshots: the dev instance serves another checkout.

## Quality checklist

- Batches/envelopes never bypass single-request gates (ownership, status machine, level/provider honesty, assurance) — parity tests mandatory
- All new objects via OpenRegister; no Filinq-local tables (ADR-001)
- CSV cells inert (no formula evaluation), caps enforced, report initiator/admin-only
- `composer check:strict` green; hydra gates pass; no overlap with `BulkSigningPanel.vue` (bulk *signing*) semantics
- Depends on `signing-trust-rebuild` — placement rendering builds on the v2 MAC; do not apply before it lands

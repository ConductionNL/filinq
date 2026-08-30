# Tasks: bulk-signing-field-builder

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 13.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & data model

- [ ] 1.1 Additive register edit in `lib/Settings/filinq_register.json`: schemas `bulkSigningBatch` + `signingEnvelope`, properties `signingRequest.fieldPlacements[]` + `signingRequest.envelopeRef` per REQ-DDBSF-001; register version bump for boot import
  - Union-additive diff against merge base; `tests/validate-manifest.js` passes; property titles present (hydra schema-property-titles gate)

- [ ] 1.2 Seed data per design.md (nil-UUID batch `…f001` with rejected-row fixture, envelope `…f002`, placement demo request); CSV fixture with `@example.invalid` emails under `tests/fixtures/`

## 2. Bulk send backend

- [ ] 2.1 `BulkSigningService`: CSV parse (native) + validation (emails, resolvable recipients, duplicates, row/size caps default 1000, formula-inert cells) persisting the report on the batch, status `ready` (REQ-DDBSF-002 phase 1); XLSX via existing vendored parser if present, else CSV-first with the seam documented (ADR-011 check)

- [ ] 2.2 `BulkSigningJob` (NC background job): per-row creation through `SigningService::createRequest()` with try/catch isolation into `rejectedRows`, progress + terminal status, batch cancel of still-cancellable members (REQ-DDBSF-002 phase 2)
  - Level/provider/assurance gate parity proven (QES+native batch rejected pre-send)

- [ ] 2.3 `BulkSigningController` + routes (create/validate/confirm/list/show/cancel): explicit auth attributes, initiator-or-admin guards on read/cancel, report visible to initiator/admin only (hydra route-auth/no-admin-idor/semantic-auth gates)

## 3. Field placement

- [ ] 3.1 Placement persistence + provider payload: `fieldPlacements` accepted/validated on create (coordinates 0–1, five types, signerIndex bounds), passed to external providers' payloads (REQ-DDBSF-003)

- [ ] 3.2 Native rendering: draw placed blocks via the existing PDF composition service BEFORE v2 canonicalisation/MAC in `produceSignedArtifact()`; mutation test proves a moved block → `tampered`; placement-free byte-compatibility regression test (REQ-DDBSF-003)

- [ ] 3.3 Placement editor UI on `SigningRequestForm.vue`: page-preview overlay, drag/resize per signer, five static types; component under `src/views/signing/`, dialogs in `src/modals/` (REQ-DDBSF-005)

## 4. Envelopes

- [ ] 4.1 `SigningEnvelopeService`: create N requests + envelope with `envelopeRef` back-refs; roll-up listener on member terminal transitions (`completed` / `partially_declined` semantics per REQ-DDBSF-004); envelope cancel; initiator/member/admin read gates

- [ ] 4.2 Ceremony: envelope-grouped notifications (one per signer per envelope, existing notification path — ADR-031 untouched); signing view lists all pending member records; "sign all" iterates ordinary `sign()` per document with per-document gate errors surfaced (REQ-DDBSF-004)

- [ ] 4.3 Envelope + batch UI: bulk-send wizard (upload → report → confirm), batch list/detail with progress, envelope create/detail with member roll-up (CnDataTable/CnDetailPage, NL Design System tokens) (REQ-DDBSF-005)

## 5. Quality, i18n, docs

- [ ] 5.1 Unit tests ≥75% on new code (validation matrix, row isolation, cap enforcement, roll-up semantics, placement MAC coverage); run in container `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`

- [ ] 5.2 Playwright e2e `tests/e2e/spec-coverage/bulk-signing-field-builder.spec.ts`: 50-row CSV (3 bad) → report → 47-request batch; placement rendered in completed artifact + verify `verified`; 3-doc envelope single ceremony → 3 artifacts/trails, decline → `partially_declined`; verify on Postgres (8080), nldesign theme enabled

- [ ] 5.3 i18n EN source + NL translations (wizard, report reasons, envelope statuses, placement editor)

- [ ] 5.4 Docs in `docs/features/` (bulk send, placement, envelopes — explicit "conditional fields: future" note) with Playwright screenshots (ADR-010); `openspec validate bulk-signing-field-builder --strict` passes

## Quality checklist

- Batches/envelopes never bypass single-request gates (ownership, status machine, level/provider honesty, assurance) — parity tests mandatory
- All new objects via OpenRegister; no Filinq-local tables (ADR-001)
- CSV cells inert (no formula evaluation), caps enforced, report initiator/admin-only
- `composer check:strict` green; hydra gates pass; no overlap with `BulkSigningPanel.vue` (bulk *signing*) semantics
- Depends on `signing-trust-rebuild` — placement rendering builds on the v2 MAC; do not apply before it lands

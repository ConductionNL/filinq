# Tasks: dossier-management-ui

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register

- [ ] 1.1 Add the optional `status` property + `x-openregister-lifecycle` annotation to the `dossier` schema in `lib/Settings/docudesk_register.json` (dossier-register delta)
  - Canonical `initial: open` (the lifecycle dialect trap: only the canonical `initial` key is honoured); exactly the six declared transitions; register-i18n tags on the new user-facing enum values; register version bump with changelog entry; existing seed dossiers untouched (absent status = open).

## 2. Backend

- [ ] 2.1 Implement `lib/Service/DossierManagementService.php` detail/index aggregation (REQ-DDDMU-001, REQ-DDDMU-002)
  - Folder listing under caller ACLs; faceted `anonymizationLink` state; `documentReview` checked state, redaction-at-scale batch data and `publicationRecord` state each presence-gated (hidden-not-broken); `BasesResolverService` for grondslagen with visible unknown-slug warnings.

- [ ] 2.2 Implement membership operations (REQ-DDDMU-004, REQ-DDDMU-005)
  - Add = upload/copy into the bound folder; remove = confirmed move to trashbin; auto-dossier creation binding a new folder for multi-uploads; no second membership store.

- [ ] 2.3 Add `lib/Controller/DossierManagementController.php` + `api/dossiers/*` routes (REQ-DDDMU-002)
  - Explicit auth attributes on every method; per-object dossier readability guard before aggregation (no IDOR); ADR-022 justification (five-source aggregation) in the docblock; routes registered before any catch-all; the grondslagen-PDF route itself stays owned by `fix-dossier-grondslagen-route-mismatch` (declared dependency — verify it is merged or include it in the test environment).

## 3. Frontend

- [ ] 3.1 Dossiers index manifest page (REQ-DDDMU-001, REQ-DDDMU-007)
  - `CnIndexPage`/`CnDataTable`; status/publication/grondslagen chips; manifest schema refs use slugs.

- [ ] 3.2 Dossier detail: documents section with inline viewer switch + mini-menu, grondslagen, batch runs, publication sections (REQ-DDDMU-002, REQ-DDDMU-006)
  - No page reload on document switch (GH #50); mark-as-checked presence-gated on the review workbench; header actions batch-anonymize / grondslagen-PDF / publish (presence-gated); deep-link to redaction-at-scale progress when installed.

- [ ] 3.3 Create dialog, inline title rename, "+ Document toevoegen" CTA, remove-confirm dialog (REQ-DDDMU-003, REQ-DDDMU-004)
  - Full-payload PUT on every dossier update (PUT-semantic rule — rename must not null `bases`/`checkedOn`); modals/dialogs in own files under `src/modals/`/`src/dialogs/`; `NcSelect` with `inputLabel`; NL Design tokens.

- [ ] 3.4 Auto-dossier modal on multi-upload (REQ-DDDMU-005)
  - Shown only for >1 document in one action (GH #47); name prefill + "grondslagen allemaal geselecteerd" toggle preselecting the six canonical bases; cancel uploads without a dossier.

## 4. Quality

- [ ] 4.1 PHPUnit unit tests for DossierManagementService/Controller (aggregation presence gates, IDOR guard, lifecycle transitions incl. out-of-order rejection, rename field-survival) — minimum 75% coverage on new code
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Explicit pin: a rename PUT preserves `bases`, `checkedOn`, `description`, `status` (saveObject PUT-semantic trap).

- [ ] 4.2 Playwright e2e specs `tests/e2e/workflows/dossier-management.spec.ts` + `tests/e2e/spec-coverage/dossier-management.spec.ts` covering the `@e2e`-referenced scenarios end-to-end with OpenRegister on the Postgres dev instance
  - Multi-upload modal, add/remove with trashbin restore, no-reload document switch, transition offers; test through the UI; nldesign-theme accessibility pass.

- [ ] 4.3 Verify the dossier → folder-batch → grondslagen-PDF chain end-to-end on a seeded dossier with the route fix applied
  - Batch runs appear in the detail; the grondslagen PDF downloads (no 500); publication section renders per pipeline presence.

- [ ] 4.4 i18n: EN + NL for all new UI strings (chips, sections, dialogs, transition labels, confirmations)
  - Keys in English.

- [ ] 4.5 Documentation `docs/features/dossier-management.md` with Playwright MCP screenshots (ADR-010); run `openspec validate dossier-management-ui --strict`
  - Documents the folder-as-membership model, the lifecycle semantics and the GH #47/#48/#50/#51 behaviours.

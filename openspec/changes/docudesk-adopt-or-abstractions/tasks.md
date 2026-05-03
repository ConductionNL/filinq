# Tasks — docudesk: adopt OR abstractions

Spec-only change. The "code paths" listed are implementation hints that the apply phase will
turn into edits, not work to do in this change. All checkboxes start unchecked.

## Phase 1 — register-resolver consumption

docudesk does not have the same `getValueString(APP_ID, 'register', '')` pattern that
pipelinq has, but it does read register/schema config in `SettingsService`, the OCR pipeline,
and the correspondence sync. Audit those reads and route them through
`RegisterResolverService` once OR ships the spec.

- [ ] 1.1 Inventory all `IAppConfig::getValueString(...)` reads in
      `lib/Service/SettingsService.php` and route them through `RegisterResolverService`
      where they resolve a register or schema reference.
- [ ] 1.2 Drop `lib/Service/SettingsService.php:61` `MIN_OPENREGISTER_VERSION = '0.2.10'` —
      duplicates `appinfo/info.xml` `<dependencies>`. Read from the manifest if needed.
- [ ] 1.3 Update the docudesk frontend stores to consume the resolver via OR's
      `useRegisterResolver` composable rather than reading `appConfig` directly.

## Phase 2 — lifecycle annotation migration

The single biggest finding: `BatchCorrespondenceJob` encodes a state machine in inline
strings. Migrate to `x-openregister-lifecycle` annotation per ADR-022.

- [ ] 2.1 `lib/BackgroundJob/BatchCorrespondenceJob.php:111,162,168,186,199` — five literal
      `'status' => 'processing'|'success'|'error'|'completed'` writes. Define the lifecycle
      states (`pending`, `processing`, `success`, `error`, `completed`) on the
      batch-correspondence schema as `x-openregister-lifecycle.states[]`. Replace the inline
      writes with OR's lifecycle transition API.
- [ ] 2.2 `lib/Service/Signing/NativeSigningProvider.php:105`,
      `lib/Service/Signing/ValidSignProvider.php:114` — `'status' => 'pending'`. Add
      `pending`, `signed`, `rejected`, `expired` to the signing-request schema's lifecycle.
- [ ] 2.3 `lib/Service/FileEntityStatsService.php:126`,
      `lib/Service/BatchUploadService.php:48`,
      `lib/Service/BatchStateService.php:33` — additional literal status strings. Fold into
      the same per-schema lifecycle annotations defined above.
- [ ] 2.4 Document the state transition rules (which transitions are allowed, which require
      auth) in the lifecycle annotation. Reference `hydra-gate-orphan-auth` so the apply
      phase wires authorization.

## Phase 3 — notification annotation migration

docudesk's signing flow (sign-request issued, sign-request completed, batch finished)
currently fires NC `notificationManager` calls inline. Migrate to
`x-openregister-notifications` per ADR-025.

- [ ] 3.1 Identify all direct `notificationManager->notify()` / `setSubject()` call sites in
      `lib/Service/` (signing providers + batch-correspondence job). Convert to declarative
      `x-openregister-notifications` triggers keyed on lifecycle transitions.
- [ ] 3.2 Add notification copy to the i18n source-of-truth (Phase 9) so the apply phase can
      ship it through `i18n-api-language-negotiation`.

## Phase 4 — archival annotation

`SigningAuditService.php:7` carries a `// Archiefwet 1995 minimum 10-year retention` comment
but no machine-readable annotation. Add `x-openregister-archival` per ADR-024.

- [ ] 4.1 Add `x-openregister-archival.retention: P10Y` to the signing-audit schema. Replace
      the comment with the annotation reference.
- [ ] 4.2 Add `x-openregister-archival` to the report, template, and entity schemas in
      `openspec/specs/document-register/spec.md` with retention values that match the
      Archiefwet selectielijst categories the audit calls out.
- [ ] 4.3 Add `x-openregister-archival` to the batch-correspondence and
      anonymization-result schemas (shorter retention; document why).

## Phase 5 — calculation annotation

Anonymization confidence, OCR confidence, and metadata-enrichment outputs are all computed
fields. Migrate them to `x-openregister-calculations`.

- [ ] 5.1 Anonymization spec: anonymization-confidence, entity-density, redaction-coverage
      become calculation annotations on the report schema.
- [ ] 5.2 Metadata-enrichment spec: classification, language-detection, summarization
      outputs become calculations.
- [ ] 5.3 OCR confidence + extracted-text-length on the file-attachment schema (in OR; PR
      against OR if the schema is upstream).

## Phase 6 — spec rewrites (stream 2)

Cite `.claude/audit-2026-05-03/02-spec-rewrite.md` for the full audit context.

- [ ] 6.1 Rewrite `openspec/specs/document-register/spec.md`:
      - Define report, template, entity schemas as full JSON-schema (drop `properties: []`).
      - Add `x-openregister-archival` per Phase 4.
      - Mandate validation through OR's schema validator.
- [ ] 6.2 Rewrite `openspec/specs/anonymization/spec.md`:
      - Replace custom file ingest with OR File Attachments.
      - Replace custom entity extraction with OR `TextExtractionService` integration.
      - Anonymization becomes a calculation annotation (Phase 5).
- [ ] 6.3 Rewrite `openspec/specs/batch-anonymization/spec.md`:
      - Replace `ICache` per-file state tracking with child-object lifecycle annotation.
      - Delegate scheduling to OR Background Jobs.
- [ ] 6.4 (P2) Rewrite `openspec/specs/metadata-enrichment/spec.md` to declare enrichment as
      a calculation annotation rather than a custom service.

## Phase 7 — hardcoded magic-number cleanup

All paths and constants per `.claude/audit-2026-05-03/04-hardcoded.md`. Each becomes an
admin-config key (default value preserved).

- [ ] 7.1 `lib/Service/OcrService.php:69` — `DEFAULT_LANGUAGES = 'nld+eng'` → admin-config
      `docudesk.ocr.default_languages` (default `nld+eng`).
- [ ] 7.2 `lib/Service/OcrService.php:76` — `DEFAULT_DPI = 300` → admin-config
      `docudesk.ocr.default_dpi` (default `300`).
- [ ] 7.3 `lib/Service/TemplateService.php:49` — `LOCK_TIMEOUT_MINUTES = 15` → admin-config
      `docudesk.templates.lock_timeout_minutes` (default `15`).
- [ ] 7.4 `lib/Service/BatchStateService.php:17` — `CACHE_TTL = 7200` → admin-config
      `docudesk.batch.cache_ttl_seconds` (default `7200`).
- [ ] 7.5 `lib/Service/BatchStateService.php:18` — `DEFAULT_MAX_FILES = 100` → admin-config
      `docudesk.batch.max_files_per_run` (default `100`).
- [ ] 7.6 `lib/Service/BatchStateService.php:19` — `CACHE_PREFIX` → drop after Phase 2 lifecycle
      migration removes the cache-based state machine.
- [ ] 7.7 `lib/Service/CorrespondenceService.php:50` — `SYNC_BATCH_LIMIT = 10` → admin-config
      `docudesk.correspondence.sync_batch_limit` (default `10`).
- [ ] 7.8 `lib/Service/CorrespondenceService.php:57` — `DEFAULT_FORMAT = 'pdf'` → admin-config
      `docudesk.correspondence.default_format` (default `pdf`).
- [ ] 7.9 `lib/Service/DataResolverService.php:45` — `MAX_DEPTH = 3` → admin-config
      `docudesk.data_resolver.max_depth` (default `3`).
- [ ] 7.10 `lib/Service/SettingsService.php:61` — `MIN_OPENREGISTER_VERSION = '0.2.10'` →
      DROP (duplicates `appinfo/info.xml` `<dependencies>` block).

## Phase 8 — manifest adoption

Cite `hydra/openspec/changes/adopt-app-manifest/`.

- [ ] 8.1 Create `openspec/manifest.yaml` with: `tier: 2`, `dependencies: ["openregister"]`,
      `consumes: [register-resolver-service, pluggable-integration-registry,
      i18n-source-of-truth, i18n-api-language-negotiation, multi-tenancy-context]`.
- [ ] 8.2 Pin minimum OR version in the manifest (replace the dropped
      `MIN_OPENREGISTER_VERSION` constant from Phase 1).
- [ ] 8.3 Validate the manifest with the Hydra manifest schema once it ships.

## Phase 9 — multi-tenancy + i18n adoption

Gated on nc-vue `multi-tenancy-context` and OR `i18n-source-of-truth` /
`i18n-api-language-negotiation` shipping.

- [ ] 9.1 Adopt `multi-tenancy-context` in the docudesk frontend: read `currentTenant` from
      the nc-vue composable in every store; remove any hand-rolled tenant lookup.
- [ ] 9.2 Adopt `i18n-source-of-truth` for translatable fields on report, template, entity
      schemas (label, description, lifecycle-state-display-name, notification copy from
      Phase 3).
- [ ] 9.3 Adopt `i18n-api-language-negotiation` for the docudesk public API: respect the
      `Accept-Language` header on read responses.
- [ ] 9.4 Update spec scenarios in `openspec/specs/document-register/spec.md` to require
      tenant-scoped + i18n-aware reads.

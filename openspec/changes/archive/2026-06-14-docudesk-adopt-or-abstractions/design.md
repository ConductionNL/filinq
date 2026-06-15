# Design — docudesk: adopt OR abstractions

status: pr-created

## Context

docudesk is the document-management app of the Conduction stack: OCR, anonymization,
correspondence generation, digital signing, and template-driven document creation. The
2026-05-03 OR-abstraction audit places it at Tier 2 on the audit's
extraction/de-duplication scale: it has its own service layer that legitimately wraps
third-party libraries (Tesseract OCR, ValidSign, NativeSigning), but it duplicates
state-machine, archival, and notification machinery that now lives in OpenRegister.

This change is the per-app adoption proposal that pairs with the Hydra
`adopt-app-manifest` change and the four OR-side specs
(`register-resolver-service`, `pluggable-integration-registry`, `i18n-source-of-truth`,
`i18n-api-language-negotiation`) plus the nc-vue `multi-tenancy-context` spec.

## Goals

- Stop encoding lifecycle state in inline `'status' => '…'` strings.
- Stop hand-rolling cache-keyed batch state tracking.
- Stop hardcoding tenant-tunable values (OCR languages, DPI, lock timeouts, batch sizes).
- Make Archiefwet retention machine-readable, not buried in a comment.
- Make the three core schemas (report, template, entity) declarative JSON-schema instead of
  `properties: []` placeholders.
- Make docudesk consumable by Hydra coordination via a manifest.

## Non-Goals

- Replacing third-party signing or OCR libraries. NativeSigning, ValidSign, and Tesseract
  remain. Only the surrounding orchestration changes.
- Frontend store rewrites beyond what the multi-tenancy context migration requires.
- Changing the public API contract. Lifecycle annotation must produce the same status values
  on the wire that callers see today (transition-only, not value-renaming, migration).

## Decisions

### Decision 1 — Lifecycle annotation, not enum constraints

docudesk has at least four schemas that need lifecycle state: batch-correspondence,
signing-request, batch-upload, and anonymization-result. The audit's stream 4 finding
counted ten literal status writes across six files.

**Decision**: encode each schema's state machine as `x-openregister-lifecycle` with explicit
state list, transition graph, and per-transition authorization. NOT as a JSON-schema `enum`.

**Why**: the lifecycle annotation per ADR-022 gives transition-time hooks (notifications,
audit, calculations) that an enum cannot express. The apply phase wires these hooks; this
spec only declares the states.

### Decision 2 — Archival annotation per schema, not per app

The audit found `// Archiefwet 1995 minimum 10-year retention` as a comment in the signing
audit service. The Archiefwet selectielijst uses different retention periods per category
(signing audits = 10y, batch results = 1y, correspondence = 7y, etc).

**Decision**: each schema gets its own `x-openregister-archival.retention` ISO-8601 duration.
NOT a single app-wide retention.

**Why**: the selectielijst applies per category, not per app. ADR-024 mandates per-schema
declaration so OR's archival job can route correctly.

### Decision 3 — Schema validation now mandatory

`openspec/specs/document-register/spec.md` currently declares the three schemas with
`properties: []` and refers to "ad-hoc fields written by the controller". This means OR's
schema validator never runs.

**Decision**: rewrite as full JSON-schema with `required`, `properties`, and
`additionalProperties: false`. Existing rows that don't match must be migrated by a
one-shot apply-phase script.

**Why**: the `properties: []` shape is a stream 2 finding (NEEDS-REWRITE). Without strict
validation, we can't add the lifecycle/archival/calculation annotations because OR's
validator skips schemas with empty properties.

### Decision 4 — Anonymization consumes OR primitives, no replacement

OR's `TextExtractionService` and File Attachments cover the anonymization pipeline's input
side. The audit notes anonymization currently has its own file-upload + extraction flow.

**Decision**: rewrite `openspec/specs/anonymization/spec.md` to consume OR primitives. Keep
the actual NLP/PII detection algorithms in docudesk (those are the value-add). Drop the
custom plumbing.

**Why**: stream 2 finding. Reuses OR's hardening (CSP, mime-validation, virus-scan hooks)
instead of re-implementing it.

### Decision 5 — Status strings on the wire stay the same

The migration MUST be transition-only. A controller that today returns `{"status":
"processing"}` must continue to return `{"status": "processing"}` after Phase 2.

**Why**: the apply phase will not break API consumers. Lifecycle annotation maps internal
state machine to the on-wire string verbatim. Renaming is a separate, future change.

### Decision 6 — Magic-number cleanup uses default-preserving admin-config

For each constant moving to admin-config (Phase 7), the default value MUST equal the
current hardcoded value.

**Why**: zero behavioral change at install. Tenants opt into tuning, but a fresh install
behaves identically to today.

### Decision 7 — Manifest minimum OR version replaces source constant

Phase 1 drops `SettingsService::MIN_OPENREGISTER_VERSION = '0.2.10'`. The manifest
(`openspec/manifest.yaml`) is the single source of truth for the OR version pin.

**Why**: stream 4 + ADR-026 (manifest) — appinfo/info.xml `<dependencies>` plus the
manifest declare it once.

## Risks / Trade-offs

| Risk | Mitigation |
| --- | --- |
| Schema migration on report/template/entity may reject legacy rows. | Apply phase ships a `repair` step that maps unknown fields into `additionalProperties` JSON column or quarantines invalid rows for review. |
| Lifecycle annotation in OR is new — apply timing depends on OR shipping ADR-022. | Phase 2 is gated; manifest declares minimum OR version that includes lifecycle support. |
| Five status writes in `BatchCorrespondenceJob` are spread across try/catch blocks; lifecycle transitions must preserve catch-block semantics. | Apply phase translates each `'status' => 'error'` into the appropriate lifecycle `transitionTo('error', $exception->getMessage())` call so audit captures cause. |
| OCR `DEFAULT_LANGUAGES = 'nld+eng'` is read across multiple call sites; admin-config rollout must hit them all. | Phase 7 task references the constant by name; the apply phase greps for usages and routes them all through `IAppConfig`. |
| Archival annotation per schema requires legal review of Archiefwet retention values per category. | Phase 4 task explicitly references the comment in `SigningAuditService.php:7` and asks the apply phase to confirm with the DPO before merging. |

## Migration path

1. OR ships `register-resolver-service`, `pluggable-integration-registry`,
   `i18n-source-of-truth`, `i18n-api-language-negotiation` (gates Phase 1, 9).
2. OR ships ADR-022 lifecycle + ADR-024 archival + ADR-025 notification annotation runtime
   (gates Phases 2, 3, 4, 5).
3. nc-vue ships `multi-tenancy-context` (gates Phase 9).
4. Hydra ships `adopt-app-manifest` (gates Phase 8).
5. docudesk apply phase runs in order: 1 → 6 → 2 → 3 → 4 → 5 → 7 → 8 → 9.
   Schema rewrite (Phase 6) precedes lifecycle/archival/calculation migration so the
   annotations have a strict schema to attach to.

## Open Questions

- Does `CorrespondenceService::DEFAULT_FORMAT = 'pdf'` need to be tenant-tunable, or
  user-tunable per template? Phase 7 assumes admin-config; defer to apply phase if a
  per-template override is needed.
- `BatchStateService::CACHE_PREFIX` becomes vestigial after Phase 2 — can it be deleted
  entirely, or do downstream consumers (test fixtures, support tools) depend on it? Apply
  phase confirms before removal.
- Anonymization-result archival retention: 1 year (audit retention) or longer (legal-hold
  potential)? DPO sign-off in Phase 4.

## Context

DocuDesk's intake path (upload → extract → review → anonymise, plus batch/folder variants) accepts any file and discovers problems late or never: an encrypted PDF fails deep in extraction with a generic error; a scan-only PDF "succeeds" with zero entities; a record without a document type or language quietly skips downstream logic. `metadata-enrichment` derives metadata but never judges it; OR schema validation judges object shape but not file quality. `docs/GOVERNMENT-FEATURES.md` nonetheless claims automatic quality control is available (F-02).

House pattern to follow: `metadata-enrichment` declares derived fields as `x-openregister-calculations` with a DocuDesk service as computation backend (ADR-031). Validation verdicts are exactly such derived fields.

## Goals / Non-Goals

**Goals:**

- A configurable check catalogue: file format (mime allowlist, extension/mime match), integrity (parseable by `DocumentTextExtractor`), PDF encryption, text-layer presence, metadata completeness (required fields on the record).
- A verdict contract: `validationStatus` (`passed|warnings|failed`) + `validationFindings[]` on the document record, computed via the ADR-031 calculation pattern.
- Per-document-type validation profiles in admin settings, each check `off|warning|blocking`; ship warn-only defaults.
- On-demand pre-intake endpoint that validates a file without persisting.
- Blocking checks gate upload/extract with structured 422; warnings never block.
- UI: verdict chip + findings panel; text-layer findings cross-link to OCR.
- Truthfulness docs work: F-02 downgrade-then-restore; delete stale Solr full-text-search doc.

**Non-Goals:**

- Mutating any metadata (enrichment's job).
- PDF/A production or conformance certification.
- Virus scanning (OR file attachments, REQ-ANON-00).
- Search of any kind (OR's domain, ADR-022) — the Solr doc deletion is cleanup, not relocation.

## Decisions

### D1. Verdict is a calculation, not an ad-hoc write

`validationStatus` and `validationFindings` are declared as `x-openregister-calculations` on the document/report schemas; `DocumentValidationService` is the computation backend that OR's calculation engine invokes. Identical phasing to `metadata-enrichment` REQ-META-CAL: until OR ships the ADR-031 runtime, the existing event-listener wiring computes and stores the fields, with the listener containing no validation logic of its own (single service, two dispatch mechanisms). This keeps the verdict queryable/filterable through standard OR object endpoints with no new persistence.

### D2. Check catalogue is fixed, profiles are configuration

The five check families (format, integrity, encryption, text-layer, metadata completeness) are code; what varies per deployment is configuration: which document types use which profile, allowed mime list, required metadata fields, and per-check severity (`off|warning|blocking`). Arbitrary user-defined check plugins were rejected for v1 — every concrete need gathered (encrypted PDF, scan-only PDF, wrong format, incomplete record) fits the five families, and a plugin surface would demand its own security review. Profiles live in app config under `docudesk.validation.profiles` (JSON), edited via the admin settings UI.

### D3. Warn-only by default; blocking is an explicit admin opt-in

Shipped defaults: every check `warning`. A deployment that has never opened the settings sees richer records and zero behaviour change — no new 422s, no broken automation. Only when an admin sets a check to `blocking` does upload/extract reject (422 with finding list). This mirrors the prohibition gate's "no records configured → no-op" posture.

### D4. Text-layer check is the anonymisation-safety check

`text-layer-missing` fires when extraction yields no/negligible text from a page-bearing format (threshold: < 32 extracted characters per page on average, configurable). Its finding carries `suggestedAction: "ocr"` and the UI cross-links to the existing `ocr-document-scanning` flow. This converts the most dangerous silent failure (zero entities on a scan → "nothing to redact") into an explicit, actionable warning.

### D5. Findings reference identifiers, never content

A finding is `{checkId, severity, message (localised), field?: string, suggestedAction?: string}`. `field` names a metadata field (e.g. `documentType`); no finding ever embeds extracted text or entity values. Same logging policy as the prohibition gate: IDs only.

### D6. Solr doc is deleted, not respecced

`docs/api/full-text-search.md` documents a Solr/SolrCloud integration with zero corresponding code, settings, or specs. Search belongs to OpenRegister (ADR-022), so the honest fix is removal plus a one-line pointer in the docs index to OR/NC unified search. Bundled into this change because both items are F-row truthfulness fixes on the same docs surface; it is a doc-cleanup task only and adds no requirements.

## Risks / Trade-offs

- **False "failed" on exotic-but-valid files** (extractor limitation, not file corruption) → warn-only defaults and per-check `off` keep operators unblocked; finding messages name the check so admins can tune.
- **Calculation-runtime timing** (ADR-031 not landed in OR) → same listener-fallback already proven by metadata-enrichment; the spec requires identical behaviour under both dispatch mechanisms.
- **Profile misconfiguration blocking intake fleet-wide** → blocking is per-check per-profile and the 422 body lists exactly which check fired; admin settings page shows a "blocking checks active" summary.
- **Per-page text threshold heuristics** → configurable threshold; findings advisory by default.

## Migration Plan

1. Downgrade `docs/GOVERNMENT-FEATURES.md` F-02 to *Gepland (in ontwikkeling)*; delete `docs/api/full-text-search.md` (immediate truthfulness fixes).
2. Land `DocumentValidationService` + check catalogue + unit tests.
3. Land schema calculation annotations + listener-fallback wiring + on-demand endpoint.
4. Land admin settings profiles UI + pipeline 422 gate.
5. Land listing/detail UI surfacing + e2e.
6. Restore F-02 to *Beschikbaar* in the verify-passing PR.

No data migration; records validated before this change show "not yet validated" until next touch or on-demand run.

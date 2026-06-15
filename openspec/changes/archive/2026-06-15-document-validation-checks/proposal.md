## Why

`docs/GOVERNMENT-FEATURES.md` row F-02 claims "Documentvalidatie (formaat, metadata) — Automatische kwaliteitscontrole" is **Beschikbaar**. Nothing delivers it: `metadata-enrichment` standardises document types and normalises dates (enrichment, not validation), and the huisstijl schema has OR validation enabled (object-shape validation, not file quality control). No spec, change, or code produces a quality verdict on a document. This is the second tender-facing truthfulness gap in the same checklist.

It is also a real operational gap for an anonymisation suite. Documents that enter the pipeline broken waste an operator round-trip and — worse — can silently degrade redaction quality: an encrypted PDF cannot be anonymised; a scan-only PDF without a text layer yields **zero detected entities**, which an operator can mistake for "nothing to redact" and publish PII. Best-in-class intake tooling (ABBYY-style capture, WOO-lakmoes checklists) validates format, integrity, and metadata completeness at the door and surfaces a verdict on the record.

This change adds configurable validation checks (format/integrity on the file, completeness on the record's metadata) producing a `validationStatus` verdict + `validationFindings[]` on the document record, following the same ADR-031 pattern as `metadata-enrichment`: the verdict is declared as an `x-openregister-calculations` annotation whose computation backend is a DocuDesk service.

## What Changes

- **NEW capability:** `document-validation-checks`. Validation profiles, the check catalogue (format, integrity, text-layer, encryption, metadata completeness), the verdict contract, the on-demand endpoint, and the UI surfacing.
- **NEW service `lib/Service/DocumentValidationService.php`:** runs the check catalogue against a file + its record; pure computation backend (it MUST NOT write derived fields directly — OR's calculation engine invokes it and stores the result, mirroring `MetadataEnrichmentService`).
- **NEW schema annotations:** `validationStatus` + `validationFindings` declared as `x-openregister-calculations` on the document/report schemas in `docudesk_register.json`.
- **NEW controller endpoint:** `POST /api/validation/validate` — on-demand pre-intake check on a file ID, returns findings without persisting anything.
- **NEW admin settings:** validation profile per document type (allowed formats, required metadata fields, per-check severity `off|warning|blocking`). Defaults ship warn-only; nothing blocks until an admin opts in.
- **NEW pipeline hook:** upload/extract surfaces blocking findings as HTTP 422; warning findings ride along on the response.
- **UI:** verdict chip on document listing + detail, findings panel, OCR cross-link for text-layer findings.
- **Docs cleanup (truthfulness, this change):** downgrade `docs/GOVERNMENT-FEATURES.md` F-02 to *Gepland (in ontwikkeling)* until verify passes; **delete the stale `docs/api/full-text-search.md` Solr document** — it documents admin settings and a Solr/SolrCloud integration that exist nowhere in `lib/`, `src/`, specs, or changes, and full-text search is OpenRegister's domain per ADR-022 (doc-removal task only; no search functionality is specced here).

### Out of scope

- Content enrichment (language, keywords, topics, type standardisation, date normalisation) — that is `metadata-enrichment`; validation only *judges*, never mutates.
- PDF/A conformance *production* — `print-functionality` / `pdf-conversion-service` own producing archival PDFs. (A lightweight PDF/A claim check is a possible later profile check, not v1.)
- Virus scanning — REQ-ANON-00 already delegates to OR file-attachment virus handling.
- Object-shape validation of register records — OR schema validation owns that.
- Any full-text-search capability.

## Capabilities

### New Capabilities

- `document-validation-checks`

## Cross-app Dependencies

- **Soft** — OpenRegister ADR-031 calculation runtime — verdict-as-calculation is Phase 2 (same gating as `metadata-enrichment` REQ-META-CAL); until it ships, the event-listener fallback computes and stores the verdict the same way enrichment does today.

## Impact

- **Code (docudesk):** `lib/Service/DocumentValidationService.php` (NEW), `lib/Controller/ValidationController.php` (NEW) or extension of `MetadataController`, `lib/Settings/docudesk_register.json` (calculation annotations), admin settings UI section, upload/extract pipeline hook, document listing/detail UI.
- **API contract:** one new endpoint; upload/extract MAY now return 422 — but only when an admin has explicitly marked a check blocking, so existing deployments see no behaviour change.
- **Privacy/compliance:** closes the "zero entities because zero text" silent-failure mode; findings reference check IDs and metadata field names only, never document content.
- **Docs:** F-02 truthfulness fix; stale Solr doc removed.
- **Migration:** none — new calculated fields are additive; absent values render as "not yet validated".

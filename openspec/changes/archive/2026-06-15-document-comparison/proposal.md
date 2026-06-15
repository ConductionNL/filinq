## Why

`docs/GOVERNMENT-FEATURES.md` row F-07 claims "Documentvergelijking — Versieverschillen detecteren" is **Beschikbaar**. It is not: the only diff surface in the codebase is template version diff (`template-management` REQ-TMPL-09), which compares Twig template revisions — not documents. No spec, change, or code covers comparing two documents or two versions of the same document. This is a tender-facing truthfulness gap (the checklist is used in government PvE evaluations) and a genuine category gap: every serious document-processing/redaction suite (ABBYY, Adobe, government WOO tooling) offers side-by-side comparison, and for a redaction tool specifically, the killer use case is **original vs anonymised output** — "show me exactly what was redacted, and prove nothing flagged was missed".

This change delivers document comparison built on what already exists: Nextcloud Files versioning for version resolution (never re-store content), `DocumentTextExtractor` for text, and the OR NER pipeline (`Entity`/`EntityRelation` rows keyed by file ID) to annotate diff hunks with redaction metadata.

## What Changes

- **NEW capability:** `document-comparison`. Server-side structured diff of two comparison subjects (two versions of one file, or two distinct files), with redaction-aware annotation when the pair is an original/anonymised pair, plus a redaction-completeness signal (entities recorded for the source file that produced no corresponding change hunk).
- **NEW service `lib/Service/DocumentComparisonService.php`:** resolves subjects via `IRootFolder` + `IVersionManager` (files_versions), extracts text via the existing `DocumentTextExtractor`, computes a word-level structured diff, annotates hunks against OR `EntityRelation` rows for the source file.
- **NEW controller `lib/Controller/ComparisonController.php`** + route: `POST /api/comparison/compare`. `#[NoAdminRequired]` with per-file access guards on BOTH subjects (IDOR-safe per ADR-005).
- **NEW UI:** side-by-side comparison view reachable from the document detail surface, with version pickers, synchronized scrolling, change highlighting, and redaction badges.
- **NO new schemas, no migrations, no persistence.** Comparison is ephemeral — computed on demand from NC Files + OR data already on disk.
- **Docs:** `docs/GOVERNMENT-FEATURES.md` F-07 row is downgraded to *Gepland (in ontwikkeling)* as the first task of this change and restored to *Beschikbaar* only when the apply phase lands and is verified.

### Out of scope

- The pre-commit before/after anonymisation preview inside the anonymise pipeline — that is `enhanced-anonymization` acceptance criterion 3 (in-pipeline, single document, pre-commit). This change is post-hoc comparison of any two stored subjects.
- Template version diff — already specced (`template-management` REQ-TMPL-09).
- Visual/layout diff of rendered pages (pixel or bounding-box comparison). v1 is text-level; PDF page-image diff is a possible follow-up.
- Three-way merge or accepting/rejecting changes back into a document — comparison is read-only.
- Comparing more than two subjects at once.

## Capabilities

### New Capabilities

- `document-comparison`

## Cross-app Dependencies

- **Soft** — `openregister` NER pipeline (`Entity`/`EntityRelation` mappers via DI) — redaction annotation and the completeness signal need `EntityRelation` rows for the source file; when absent (file never went through extraction) comparison still works as a plain diff.
- **None hard.** NC Files versioning (`files_versions`) is a server-bundled app; when disabled, version subjects degrade gracefully (only current contents comparable).

## Impact

- **Code (docudesk):** `lib/Service/DocumentComparisonService.php` (NEW), `lib/Controller/ComparisonController.php` (NEW), `appinfo/routes.php`, `src/` comparison view + entry from document detail.
- **API contract:** one new endpoint. No changes to existing endpoints.
- **Privacy/compliance:** strengthens the redaction story — operators can verify the anonymised output against the original and see a server-computed list of recorded entities that did NOT produce a change hunk (potential missed redactions). Comparison responses contain document text the user can already read (both subjects access-guarded); nothing new is persisted or logged beyond file IDs.
- **Migration:** none.

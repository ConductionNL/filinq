## Context

DocuDesk anonymises documents through the OR NER pipeline: extraction writes `Entity`/`EntityRelation` rows keyed by file ID; anonymisation produces a redacted output file (increasingly a sibling in `anonymised/` per the output-folder-layout changes) where each redacted span is replaced by an anonymisation key / placeholder (REQ-ANON-06). Documents live in NC Files; NC's `files_versions` app keeps prior versions of every file. Nothing in DocuDesk today can put two of these artifacts side by side — even though `docs/GOVERNMENT-FEATURES.md` claims comparison is available.

Three adjacent surfaces must NOT be duplicated:

1. `template-management` REQ-TMPL-09 — template version diff (Twig source, template register). Different artifact class entirely.
2. `enhanced-anonymization` acceptance criterion 3 — pre-commit before/after preview inside the anonymise pipeline. Single document, transient pipeline state, before committing.
3. OR owns search/indexing (ADR-022) — comparison extracts text per request via `DocumentTextExtractor`, it does not build an index.

## Goals / Non-Goals

**Goals:**

- Compare two subjects: (a) two versions of one NC file, (b) two distinct NC files. Resolve content via `IRootFolder` / `IVersionManager`; never copy or re-store.
- Server-computed structured word-level diff (hunks with type + positions), rendered side-by-side in the UI.
- Redaction-aware annotation: when the right subject is the anonymised output of the left subject, annotate replacement hunks with the matched entity's type and ID.
- Redaction-completeness signal: list source-file `EntityRelation` rows marked for anonymisation that produced no corresponding change hunk.
- IDOR-safe: both subjects must be readable by the requesting user.

**Non-Goals:**

- Persisting comparison results (no schema, no OR objects, no cache table).
- Visual/pixel diff of rendered pages.
- Editing/merging from the comparison view.
- Re-running NER as part of comparison — only existing `EntityRelation` rows are consulted.

## Decisions

### D1. Comparison is ephemeral — no persistence

A comparison is a pure function of (left subject, right subject, OR rows at request time). Persisting results would duplicate state that goes stale the moment either file changes, and would create a second store of document-derived text (privacy surface). Recompute on demand; the extractor + LCS diff on typical government documents (≤ a few hundred KB of text) is well within request budget. A guard caps extracted text at a configurable size (`docudesk.comparison.max_text_bytes`, default 5 MB) and responds 413 beyond it.

### D2. Word-level LCS diff computed in DocuDesk PHP

The diff runs server-side in `DocumentComparisonService` (tokenise to words + whitespace, longest-common-subsequence, coalesce into hunks). No new composer dependency unless `sebastian/diff` (already in the dev graph via PHPUnit) is promoted — apply phase decides; the spec fixes only the response contract. Client-side diffing was rejected: the redaction annotation and completeness signal need OR mapper access, and shipping both full texts to the client just to diff them duplicates work the server must do anyway for annotation.

### D3. Subjects are `{fileId, versionTimestamp?}` pairs

One request shape covers both use cases: omit `versionTimestamp` for current content, set it to compare a historical version (resolved via `IVersionManager::getVersionsFor` on the user's own file handle). Two distinct file IDs cover original-vs-anonymised and any ad-hoc pair. Access control: each subject's file MUST be resolvable through the requesting user's folder (`IRootFolder::getUserFolder($uid)->getById($fileId)`), which makes shared-file access follow NC sharing semantics for free.

### D4. Redaction annotation matches anonymisation keys, falls back to entity-value matching

When the right text's inserted spans contain anonymisation keys/placeholders (REQ-ANON-06 UUIDs or `[TYPE]`-style placeholders from the anonymisation result), the service maps them to `EntityRelation` rows via the stored replacement mapping on the anonymisation result/report for the source file. Where no key mapping exists (older outputs), it falls back to matching removed-span text against `Entity` canonical values for the source file. Annotation is best-effort and clearly marked per hunk (`redaction: {entityId, entityType, matchedBy: "key"|"value"}`); unannotated change hunks are simply plain edits.

### D5. Completeness signal compares recorded intent against observed hunks

For an original/anonymised pair, every source-file `EntityRelation` row that was part of the anonymise set (not skip-flagged via `skipAnonymization`) SHOULD correspond to ≥ 1 change hunk. Rows with zero matching hunks are returned in `unredactedEntities[]` — by entity ID and canonical name, never literal document text (mirrors the prohibition-gate logging policy). This is a *signal*, not a verdict: text normalisation differences can cause false positives, so the UI presents it as "verify these manually", and the response carries no blocking semantics.

### D6. UI lives on the document detail surface

Entry point "Compare…" on the document detail view (and on anonymisation results, pre-wired to original-vs-anonymised). The view is a dedicated route with two pickers (file + version), synchronized scroll panes, hunk highlighting, and redaction badges. Modal-free per ADR-004 modal-isolation expectations; pickers reuse NC file-picker components.

## Risks / Trade-offs

- **Text-extraction nondeterminism across formats** (e.g. DOCX original vs PDF anonymised output) → spurious whitespace/layout hunks. Mitigation: normalise whitespace before diffing; surface a "different source formats" notice on cross-format pairs.
- **Completeness-signal false positives** (entity replaced by identical-length placeholder text in normalised form). Mitigation: presented as advisory, never blocking; matchedBy metadata lets the UI explain.
- **Large documents** → request-time CPU. Mitigation: D1 size cap + 413.
- **files_versions disabled** → version subjects unavailable. Mitigation: explicit 422 with a machine-readable reason; current-content comparison unaffected.

## Migration Plan

1. Downgrade `docs/GOVERNMENT-FEATURES.md` F-07 to *Gepland (in ontwikkeling)* (truthfulness fix, immediate).
2. Land `DocumentComparisonService` + controller + route + unit tests.
3. Land the comparison UI + Playwright coverage.
4. Restore F-07 to *Beschikbaar* in the same PR that passes verify.

No data migration; nothing persisted.

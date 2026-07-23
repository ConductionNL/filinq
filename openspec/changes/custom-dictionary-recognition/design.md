# Design: custom-dictionary-recognition

## Context

Verified at HEAD (DocuDesk `spec/market-gap-wave3-2026-07`, OpenRegister HEAD):

- **Detection is OR-owned.** `EntityRecognitionHandler`
  (`lib/Service/TextExtraction/`) dispatches over `regex` / `presidio` /
  `openanonymiser` / `llm` / `hybrid`. Its entity types are compile-time
  constants (`ENTITY_TYPE_PERSON … ENTITY_TYPE_IP_ADDRESS`) and its regex
  set (`getRegexPatterns()`) is a hard-coded, instance-global list. There is
  no organisation-scoped, user-managed term list anywhere in OR.
- **The catalogue is shared and type-agnostic.** OR persists detections in
  `oc_openregister_entities` (`type`, `value`, `category`, `organisation`)
  with one `oc_openregister_entity_relations` row per occurrence
  (`fileId`, `positionStart/End`, `confidence`, `detectionMethod`,
  `anonymized`, `bases[]`, `skipAnonymization`). `EntityRelationMapper`
  writes it; the review UI, grondslagen pass and redaction read it.
- **Redaction is type-agnostic.** `DocumentProcessingHandler::anonymizeDocument()`
  builds `[<localized-type>: <number>]` for any entity type and
  `localizeEntityType()` returns the raw label for a type it does not know —
  so a `CUSTOM_DICTIONARY` occurrence redacts with zero engine change.
- **DocuDesk already reads document text** (`AnonymizationService::readNodeTextSafely()`)
  and owns the enabled-type whitelist
  (`GrondslagProposalService::getEntityTypeWhitelist()`), and its
  `extractAndDetectEntities()` is the single detection entry point every
  DocuDesk flow drives.
- OR's `detectionMethod` for these rows is a DocuDesk-contributed value
  (`custom_dictionary`) parallel to the existing `manual` method.

## Goals / Non-Goals

**Goals:**

- Organisation-managed term lists that add `CUSTOM_DICTIONARY` occurrences
  to OR's shared catalogue so they flow through review and redaction like any
  other entity.
- CRUD + CSV/newline import + an admin page, all org-scoped fail-closed.
- A pure, unit-testable matcher (exact / case-insensitive / word-boundary)
  that needs no live NC instance to prove.

**Non-Goals:**

- No new detection engine and no fork of the pipeline — matches are written
  into OR's catalogue, not a DocuDesk-local entity store.
- No change to OR's `EntityRecognitionHandler`, its type constants, its regex
  set or its redaction placeholder format.
- No fuzzy/approximate matching in v1 (flag declared, deferred — Open
  Questions).
- No review-workbench UI work — that is `anonymization-review-workbench`
  (active); `CUSTOM_DICTIONARY` rows surface there for free via the catalogue.

## Decisions

### D1 — Dictionaries + terms as register objects, org-scoped (justified controller)

Two schemas in the `document` register:

- `customDictionary`: `label`, `description`, `colour`, `matchMode`
  (`exact`|`caseInsensitive`|`wordBoundary`, default `caseInsensitive`),
  `fuzzy` (bool, accepted-and-ignored in v1), `active` (bool), `termCount`
  (calculated). Owning organisation via `@self.organisation`.
- `customDictionaryTerm`: `value` (the term), `label` (optional per-term
  display, defaults to the dictionary label), `dictionary` (uuid reference).

CRUD is OR's `ObjectService`. DocuDesk wraps it in
`CustomDictionaryController` (`api/custom-dictionaries`,
`api/custom-dictionaries/{uuid}`, `.../{uuid}/terms`,
`.../{uuid}/import`). Per ADR-022 a bare proxy is forbidden; this controller
is justified because it adds (1) the **organisation gate** (a caller may only
read/write dictionaries of their accessible organisations — fail-closed,
mirroring OR's tenant rule), (2) **CSV/newline import parsing** (server-side,
not client-side), and (3) **term-count enrichment**. OR services are resolved
lazily by FQCN string via the DI container (the `EmlBackend`/`EntitySearch`
cross-app pattern) so DocuDesk stays loadable without OR; without OR the
endpoints return an explanatory unavailable state.

### D2 — The matcher (pure, unit-pinned)

`CustomDictionaryMatchService::match(string $text, array $terms, string $mode): array`
returns occurrences `{value, label, positionStart, positionEnd}`:

| Mode | Rule |
|---|---|
| `exact` | `mb_strpos` byte-for-byte, case-sensitive |
| `caseInsensitive` (default) | `mb_stripos`, case-folded both sides |
| `wordBoundary` | case-insensitive **and** the match is delimited by a non-word boundary (`\b` semantics over Unicode letters/digits) so "Berg" does not match inside "Bergen" |

All modes return **every** occurrence (all positions), not just the first,
so multi-occurrence redaction and counts are correct. Blank/whitespace-only
terms are skipped. Longest-term-first ordering is applied so an overlapping
shorter term cannot pre-empt a longer one (mirrors OR's redaction
longest-needle rule). This method is a pure function of its inputs — the
primary phpunit seam; no NC container required.

### D3 — Writing occurrences into OR's catalogue

For each active dictionary of the file's organisation, the matcher runs over
the document text; occurrences are written as `CUSTOM_DICTIONARY`
entities/relations through OR's `EntityRelationMapper`:

- entity `type` = `CUSTOM_DICTIONARY`, `value` = the matched term,
  `category` = `contextual_data` (OR's `CATEGORY_CONTEXTUAL_DATA`).
- relation `detectionMethod` = `custom_dictionary`, `confidence` = `1.0`
  (a dictionary hit is a certainty, not a probability), positions from D2,
  and the per-list label carried so the review/summary can show which
  dictionary matched.
- Idempotency: a re-run first clears prior `custom_dictionary` relations for
  the file (matching OR's re-extract semantics) so re-matching does not
  append duplicates.

The pass is **best-effort** and hooked into
`AnonymizationService::extractAndDetectEntities()` after OR extraction and
before the normalize/policy pass, so `CUSTOM_DICTIONARY` rows are in the
returned entity set and the review UI. A matcher failure is logged and never
blocks OR-side detection (honest degradation: the operator sees OR's hits
plus a warning that dictionary matching did not run).

### D4 — Respecting the enabled-type whitelist

`CUSTOM_DICTIONARY` participates in
`GrondslagProposalService::getEntityTypeWhitelist()`: when the operator has
disabled it, the matcher is skipped (dictionaries stay manageable but do not
auto-detect). This keeps one consistent enable/disable surface for all types.

### D5 — CSV / newline import

`POST api/custom-dictionaries/{uuid}/import` accepts either a `text/csv`
upload (first column = value, optional second column = per-term label) or a
`text/plain` newline list. The server trims, drops blanks, de-duplicates
against existing terms (case-insensitive), creates `customDictionaryTerm`
objects, and returns `{added, skipped, total}`. No client-side parsing (a
malformed file must fail on the server, not silently in the browser).

### D6 — UI (Manifest-V2 shell)

A "Custom dictionaries" page registered in `src/manifest.json` +
`registry.js` (NOT `src/router/index.js`): `CnIndexPage`/`CnDataTable` of
dictionaries (label, term count, match mode, active); a detail/edit view
(term table, add/remove, import upload, match-mode `NcSelect` with
`inputLabel`, active toggle, colour picker). Dialogs live in their own files
under `src/dialogs/`. NL Design tokens; the page is admin/manager gated
consistently with the controller.

## OpenRegister service usage (ADR-001)

| Operation | Service |
|---|---|
| Dictionary/term CRUD | OR `ObjectService` (via `CustomDictionaryController`, org-gated) |
| Document text | DocuDesk `AnonymizationService::readNodeTextSafely()` (already reads the node) |
| Catalogue writes | OR `EntityRelationMapper` (lazy FQCN, `CUSTOM_DICTIONARY` rows) |
| Redaction of matches | OR `DocumentProcessingHandler` (unchanged, type-agnostic) |

ADR-011 check: no crypto here; matching is verbatim string comparison over
already-extracted text.

## Declarative vs imperative

- **Declarative**: the two schemas + register-i18n tags on user-facing string
  fields; the manifest page; per-dictionary `matchMode` as data.
- **Imperative (justified)**: the org gate, CSV import parsing, the matcher
  (position-accurate string scanning), and the catalogue write (must be
  atomic with the detection run it feeds).

## Seed Data

One demo dictionary so the admin page renders non-empty:

```json
{
  "@self": {"register": "document", "schema": "customDictionary", "slug": "seed-custom-dictionary-projectnamen"},
  "label": "Projectnamen",
  "description": "Interne projectcodenamen die niet door Presidio worden herkend",
  "colour": "#8E44AD",
  "matchMode": "caseInsensitive",
  "fuzzy": false,
  "active": true
}
```

Plus two demo `customDictionaryTerm` rows ("Operatie Zilverreiger",
"Dossier Karekiet") referencing it. No real personal data by construction.

## Security Considerations

- Every dictionary/term route is org-gated fail-closed; a caller never sees or
  edits another organisation's lists.
- Terms are organisation content, not secrets, and are stored as ordinary
  register objects (they are the *search needles*, not detected PII); the
  detected *occurrences* live in OR's catalogue under OR's existing scoping.
- The matcher never logs matched values (count-only logs, PII-free).
- Import size is bounded (max rows / bytes config) to avoid a
  denial-of-service via a huge upload.

## Risks / Trade-offs

- [A very large dictionary × large document is O(terms × text)] → bounded by a
  configurable max active-terms-per-organisation; matcher is a single linear
  pass per term; acceptable for the low-hundreds term lists municipalities
  actually maintain.
- [`CUSTOM_DICTIONARY` redacts as `[CUSTOM_DICTIONARY: n]` unless localized] →
  accepted for v1; the per-list label is carried on the relation for the
  review UI, and a localized placeholder label is an OR-side follow-up.
- [False positives from a broad term (e.g. a common word added as a term)] →
  the operator owns the list; word-boundary mode mitigates; every hit is still
  reviewable/skippable in the workbench before redaction.

## Migration Plan

Additive: two schemas + seed + register version bump (boot import), new
services/controller/routes/views, one detection hook. No existing schema
changes; no data migration. Rollback = remove routes/UI + the detection hook;
dictionary objects remain readable.

## Open Questions

- **Fuzzy matching** (Levenshtein / phonetic for near-misses and OCR noise) —
  flag declared now, matching deferred; would need a bounded-distance matcher
  and a per-term threshold. Recorded, not built in v1.
- **Localized `CUSTOM_DICTIONARY` placeholder label** in OR's
  `localizeEntityType()` — OR-side follow-up so redaction shows a localized
  label instead of the raw type.
- **Sharing a dictionary across organisations** (central + local lists) —
  deferred; v1 is strictly single-organisation ownership.

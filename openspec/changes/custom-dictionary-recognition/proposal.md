---
kind: code
---

# Proposal: custom-dictionary-recognition

## Why

Presidio and regex catch the *generic* identifiers (PERSON, BSN, IBAN,
EMAIL). They do not — and cannot — catch the identifiers that only a
specific municipality knows are sensitive: an internal project codename
("Operatie Zilverreiger"), a local street or buurt name that models miss,
a case-file code format unique to that organisation, the name of a
whistle-blower, a supplier under a running procurement. Municipal
anonymisation tooling treats an organisation-managed **term list** as core,
not a nice-to-have:

- **Octobox Anonimiseren** (Gemeente Zaanstad, algoritmeregister
  gm0479/24449830) is described as "algoritmes, **waardelijsten voor
  automatische termherkenning** en NLP" — value-lists sit *next to* the NLP
  model, not inside it (R3 section C).
- **Anonimiseringssoftware Gemeente Noordwijk** (algoritmeregister
  gm0575/91386595) is registered as municipal PII detection tuned by
  value-lists rather than a fixed entity model (R3 section C).
- R3's ranked demand table lists "**Custom dictionary / value-list /
  context-aware term recognition**" as an unspecced concept (demand_score 2,
  municipal table-stakes) — no active DocuDesk change covers it.

This is a recall gap with a hard privacy consequence: a project codename or
a named individual that the operator *knows* is sensitive silently ships to
the Woo-publicatie because no recognizer was watching for it. A managed
dictionary closes it, and it is buildable as a leaf recognizer over the
engines OpenRegister already owns.

Verified at HEAD (why this is a leaf, not a rebuild):

- OpenRegister's `EntityRecognitionHandler` owns detection with a fixed set
  of entity-type constants (`ENTITY_TYPE_PERSON … ENTITY_TYPE_IP_ADDRESS`)
  and a method dispatch (`regex`, `presidio`, `openanonymiser`, `llm`,
  `hybrid`); it has **no** organisation-scoped term-list recognizer and its
  `getRegexPatterns()` is a hard-coded, instance-global list
  (`lib/Service/TextExtraction/EntityRecognitionHandler.php`).
- OR persists every detection in the shared catalogue
  (`oc_openregister_entities` + one `oc_openregister_entity_relations` row
  per occurrence) via `EntityRelationMapper` — the same store the review UI,
  the grondslagen pass and OR's redaction engine already read.
- OR's redaction engine is entity-type-agnostic: `DocumentProcessingHandler`
  builds a placeholder `[<localized-type>: <number>]` for *any* entity type
  and falls back to the raw label for a type it does not localize
  (`localizeEntityType()`), so a new `CUSTOM_DICTIONARY` type flows through
  redaction with no engine change.
- DocuDesk already reads a document's text projection itself
  (`AnonymizationService::readNodeTextSafely()`) and already owns the
  enabled-entity-type selection (`GrondslagProposalService::getEntityTypeWhitelist()`).

So DocuDesk adds a recognizer that contributes `CUSTOM_DICTIONARY`
occurrences into OR's shared catalogue — exactly parallel to the existing
manual-entity path — while OR keeps ownership of extraction, the catalogue,
review and redaction.

## What Changes

- **Managed dictionaries (register-backed, org-scoped)**: two new schemas in
  the `document` register — `customDictionary` (a named list: label, colour,
  match options, owning organisation, active flag) and `customDictionaryTerm`
  (one term per row: value, optional per-term label, dictionary reference).
  Full CRUD is OR's ObjectService via a thin DocuDesk controller that adds
  the organisation gate; lists are organisation-scoped fail-closed.
- **Matching engine option**: per-dictionary match mode — exact,
  case-insensitive (default), and word-boundary — applied by a
  `CustomDictionaryMatchService` over the document's extracted text. A
  `fuzzy` flag is declared in the schema but explicitly deferred (matching
  it is out of scope for v1; the flag is accepted and ignored with a note).
- **A distinct entity type through the normal pipeline**: matches become
  `CUSTOM_DICTIONARY` entities/relations written through OR's
  `EntityRelationMapper` (per-list label carried on the relation), so they
  appear in detection results, the review workbench, the grondslagen summary
  and OR's redaction identically to a Presidio hit — no pipeline fork.
- **CSV / newline import**: bulk-load terms into a dictionary from an
  uploaded CSV or a pasted newline-separated list (dedupe, trim, skip
  blanks), reporting added/skipped counts.
- **Admin UI page (Manifest-V2 shell)**: a "Custom dictionaries" management
  page registered in `src/manifest.json` + `registry.js` (never the dead
  `src/router/index.js`) — list/create/edit dictionaries, manage terms,
  import, toggle active, pick match mode.

## Capabilities

### New Capabilities

- `custom-dictionary-recognition`: organisation-managed term lists as an
  additional PII recognizer — register-backed dictionary + term CRUD,
  exact/case-insensitive/word-boundary matching, a `CUSTOM_DICTIONARY` entity
  type flowing through the existing detection→review→anonymisation pipeline,
  CSV/newline import, and a Manifest-V2 admin page.

### Modified Capabilities

<!-- none — OR's TextExtractionService, EntityRelationMapper, entity
     catalogue, review workbench and DocumentProcessingHandler redaction are
     consumed unchanged. The `anonymization` capability's detection→review→
     anonymise pipeline is extended by a new recognizer, not modified. -->

## Impact

- `lib/Settings/docudesk_register.json`: new `customDictionary` and
  `customDictionaryTerm` schemas in the `document` register (org-scoped,
  register-i18n on user-facing string fields), seed data (one demo
  dictionary + terms), register version bump with changelog entry.
- New `lib/Service/CustomDictionaryMatchService.php`: text-vs-terms matcher
  (exact / case-insensitive / word-boundary), producing `CUSTOM_DICTIONARY`
  occurrences and writing them via OR's `EntityRelationMapper` (lazy FQCN
  container resolution — loadable without OR).
- New `lib/Service/CustomDictionaryService.php` + a thin
  `lib/Controller/CustomDictionaryController.php` (`api/custom-dictionaries/*`
  CRUD + `/import`): justified non-pass-through per ADR-022 (org gate + CSV
  import parsing + term-count enrichment; not a bare OR proxy).
- Hook the matcher into DocuDesk's detection entry point
  (`AnonymizationService::extractAndDetectEntities`) as a best-effort
  post-pass after OR extraction, respecting the enabled-entity-type
  whitelist.
- `src/manifest.json` + `registry.js` + new views: Custom dictionaries admin
  page (schema refs use slug form).
- Consumes (unchanged, presence-gated so DocuDesk loads without OR): OR
  `TextExtractionService` (text/chunks), `EntityRelationMapper` (catalogue
  writes), `DocumentProcessingHandler` redaction (type-agnostic).
- Dependencies / non-overlap: `anonymization-review-workbench` (active) renders
  the review — `CUSTOM_DICTIONARY` rows appear there via the shared catalogue,
  no change to that surface; `enable-kenteken-entity-type` (active) adds one
  fixed KENTEKEN type — orthogonal to this change's *dynamic, per-list* type;
  `redaction-at-scale` / `image-redaction` (active) are not touched. These are
  declared dependencies, not re-specced.
- Evidence: Octobox Zaanstad + Noordwijk algoritmeregister (R3 C); R3
  ranked-demand table row "Custom dictionary / value-list … term recognition".

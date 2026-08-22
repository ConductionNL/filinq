# Custom Dictionary Recognition

## Overview

Presidio and regex catch the *generic* identifiers (PERSON, BSN, IBAN,
EMAIL). They do not — and cannot — catch the identifiers that only a
specific municipality knows are sensitive: an internal project codename
("Operatie Zilverreiger"), a local street or buurt name a model misses, a
case-file code format unique to that organisation, or the name of a
whistle-blower.

Municipal anonymisation tooling treats an organisation-managed **term list**
as core, not a nice-to-have:

- **Octobox Anonimiseren** (Gemeente Zaanstad, algoritmeregister
  gm0479/24449830) is described as "algoritmes, **waardelijsten voor
  automatische termherkenning** en NLP" — value-lists sit *next to* the NLP
  model, not inside it.
- **Anonimiseringssoftware Gemeente Noordwijk** (algoritmeregister
  gm0575/91386595) is registered as municipal PII detection tuned by
  value-lists rather than a fixed entity model.

Custom dictionary recognition closes this recall gap: an organisation
maintains its own list of terms, Filinq matches those terms against every
document's extracted text, and a match becomes a `CUSTOM_DICTIONARY`
occurrence in OpenRegister's shared entity catalogue — appearing in
detection results, the review workbench, the grondslagen summary and
redaction exactly like a Presidio or regex hit.

Filinq adds a **recognizer**, not an engine: OpenRegister still owns
extraction, the catalogue, review and redaction. Nothing about
`EntityRecognitionHandler`, its type constants, its regex set or its
redaction placeholder format changes.

## How It Works

1. An organisation manager creates a **custom dictionary** — a label,
   colour, match mode, and an active flag — from the "Custom dictionaries"
   admin page.
2. Terms are added one at a time, or bulk-loaded via **CSV or newline-list
   import** (server-side parsing only; trims, skips blanks, de-duplicates
   case-insensitively, and reports `{added, skipped, total}`).
3. When a document is extracted and detected
   (`AnonymizationService::extractAndDetectEntities`), Filinq runs a
   best-effort pass **after** OpenRegister's own extraction: for every
   active dictionary the caller's organisation can access, it matches that
   dictionary's terms against the file's text chunks
   (`CustomDictionaryMatchService`) and writes every occurrence into
   OpenRegister's catalogue via `EntityRelationMapper` — entity type
   `CUSTOM_DICTIONARY`, category `contextual_data`, `detectionMethod =
   custom_dictionary`, confidence `1.0`.
4. The pass is **idempotent**: before writing, it clears the file's prior
   `custom_dictionary` relations, so re-running detection never appends
   duplicates.
5. The pass is **best-effort**: a failure is logged and surfaced as a
   `customDictionaryWarning` string on the detection response, but
   OpenRegister's own detected entities are always still returned.
6. `CUSTOM_DICTIONARY` participates in the same enabled-entity-type
   whitelist as every other type
   (`GrondslagProposalService::getEntityTypeWhitelist`) — when the operator
   disables it, the matcher is skipped entirely (dictionaries stay
   manageable but stop auto-detecting).

## Match Modes

| Mode | Rule |
|---|---|
| `exact` | Case-sensitive, byte-for-byte match. |
| `caseInsensitive` (default) | Case-folded on both sides. |
| `wordBoundary` | Case-insensitive **and** delimited by a non-word boundary, so "Berg" does not match inside "Bergen". |

Match mode is set **per dictionary** — every term in a dictionary shares
that dictionary's mode. All modes return **every** occurrence (not just the
first), and overlapping terms are resolved **longest-term-first**, so a
shorter term can never pre-empt a longer one at the same position (mirrors
OpenRegister's own redaction longest-needle rule).

A `fuzzy` flag exists on the schema and is accepted by the API, but is
**explicitly deferred** — no approximate/near-miss matching in this version.

## Organisation Scoping

Dictionaries and terms are organisation-scoped, fail-closed: a caller only
ever sees or edits dictionaries whose organisation is among their own
accessible organisations. This is enforced server-side in
`CustomDictionaryService`, not merely hidden in the UI — a cross-organisation
`GET`/`PUT`/`DELETE` returns HTTP 403, and a cross-organisation dictionary is
silently absent from list responses (never "everyone may see it" as a
fallback).

## API

All routes live under `api/custom-dictionaries` (Filinq's own controller,
not a bare OpenRegister object proxy — see `CustomDictionaryController`'s
ADR-022 justification):

| Method | Route | Purpose |
|---|---|---|
| GET | `api/custom-dictionaries` | List dictionaries visible to the caller. |
| POST | `api/custom-dictionaries` | Create a dictionary. |
| GET | `api/custom-dictionaries/{id}` | Show one dictionary (with live `termCount`). |
| PUT | `api/custom-dictionaries/{id}` | Update a dictionary. |
| DELETE | `api/custom-dictionaries/{id}` | Delete a dictionary (cascade-deletes its terms). |
| GET | `api/custom-dictionaries/{id}/terms` | List a dictionary's terms. |
| POST | `api/custom-dictionaries/{id}/terms` | Add one term. |
| DELETE | `api/custom-dictionaries/{id}/terms/{termId}` | Remove one term. |
| POST | `api/custom-dictionaries/{id}/import` | CSV/newline import (`file` multipart field or `content` + `format` params). |

When OpenRegister is not installed, every route returns HTTP 503 with an
explanatory body rather than crashing.

## Admin UI

The "Custom dictionaries" page is registered in the Manifest-V2 shell
(`src/manifest.json` + `src/registry.js`, never `src/router/index.js`):

- **Index** (`CustomDictionaryIndex.vue`): a `CnIndexPage` listing every
  accessible dictionary with its label, live term count, match mode and
  active state. "Add" opens `CustomDictionaryFormDialog.vue` and navigates
  to the new dictionary's detail page.
- **Detail** (`CustomDictionaryDetail.vue`): dictionary metadata (editable
  via the same form dialog), a term table with add/remove, and an
  **Import…** button opening `CustomDictionaryImportDialog.vue` for
  CSV/newline bulk loading.

> Playwright MCP screenshots of the live pages were not captured for this
> revision of the document — this apply pass was implemented and verified
> (PHPUnit + `npm run build`) in an isolated worktree without a seeded,
> deployed Nextcloud instance to screenshot against. Capturing screenshots
> against a live instance with the seed dictionary loaded is a follow-up.

## Seed Data

One demo dictionary ships for the admin page to render non-empty:

```json
{
  "label": "Projectnamen",
  "description": "Interne projectcodenamen die niet door Presidio worden herkend",
  "colour": "#8E44AD",
  "matchMode": "caseInsensitive",
  "active": true
}
```

...with two demo terms: "Operatie Zilverreiger" and "Dossier Karekiet". No
real personal data by construction.

## Deferred / Follow-ups

- **Fuzzy matching** (Levenshtein / phonetic, for near-misses and OCR noise)
  — the flag is declared and accepted now; matching is a future change.
- **Localized `CUSTOM_DICTIONARY` placeholder label** — redaction currently
  shows `[CUSTOM_DICTIONARY: n]` unless OpenRegister's
  `localizeEntityType()` gains a translation for the type; the matching
  dictionary/term label is carried on the relation's `context` field for
  the review UI in the meantime.
- **Cross-organisation ("central + local") dictionaries** — v1 is strictly
  single-organisation ownership.

## Related Specs

- `openspec/changes/custom-dictionary-recognition/` — this change.
- `openspec/specs/anonymization/spec.md` — the detection → review →
  anonymise pipeline this recognizer plugs into (consumed, not modified).
- `anonymization-review-workbench` — renders `CUSTOM_DICTIONARY` rows via
  the shared catalogue; no change to that surface.
- `enable-kenteken-entity-type` — orthogonal fixed-type precedent for
  extending the curated entity-type list.

# custom-dictionary-recognition Specification (delta)

---
status: proposed
---

## Purpose

Let an organisation manage its own term lists — project codenames, local
street/buurt names, case-file codes, internal codenames, named individuals —
as an additional PII recognizer alongside Presidio/regex. Terms are stored as
organisation-scoped register objects with a chosen match mode
(exact / case-insensitive / word-boundary); matches become a distinct
`CUSTOM_DICTIONARY` entity type written into OpenRegister's shared entity
catalogue, so they flow through the existing detection → review →
anonymisation pipeline exactly like any other entity. Terms load via CSV or a
newline list and are managed from a Manifest-V2 admin page. Detection,
catalogue, review and redaction remain OpenRegister-owned (verified at HEAD);
DocuDesk adds a recognizer, not an engine.

## ADDED Requirements

### Requirement: Organisation-managed dictionaries and terms (REQ-DDCDR-001)

The app MUST provide organisation-scoped `customDictionary` and
`customDictionaryTerm` register objects (in the `document` register) storing,
per dictionary, a label, description, colour, match mode
(`exact`|`caseInsensitive`|`wordBoundary`, default `caseInsensitive`), a
deferred `fuzzy` flag, an `active` flag and a calculated term count; and per
term a value, an optional per-term label and a reference to its dictionary. A
dictionary and its terms MUST belong to exactly one organisation and MUST be
readable and editable only by callers whose accessible organisations include
it (fail-closed).

#### Scenario: A dictionary and its terms are organisation-scoped

- GIVEN organisation A owns a dictionary "Projectnamen" with two terms
- WHEN a user whose only accessible organisation is B lists custom dictionaries
- THEN "Projectnamen" and its terms are not returned
- @e2e exclude tenant-scoping is backend authorization logic — covered by PHPUnit (tests/unit/Service/CustomDictionaryServiceTest.php)

#### Scenario: Match mode defaults to case-insensitive

- GIVEN a new dictionary created without an explicit match mode
- WHEN it is persisted and read back
- THEN its match mode is `caseInsensitive`
- @e2e tests/e2e/spec-coverage/custom-dictionary-recognition.spec.ts

### Requirement: Deterministic term matching engine (REQ-DDCDR-002)

The matcher MUST return every occurrence of a dictionary's terms in a text
according to the dictionary's match mode: `exact` MUST match byte-for-byte
case-sensitively; `caseInsensitive` MUST match ignoring case; `wordBoundary`
MUST match ignoring case only when the occurrence is delimited by a non-word
boundary so a term does not match inside a longer word. Each returned
occurrence MUST carry its start and end positions. Blank or whitespace-only
terms MUST be skipped, and overlapping terms MUST be resolved longest-term
first so a shorter term cannot pre-empt a longer one. The `fuzzy` flag MUST be
accepted and ignored in this version (no approximate matching).

#### Scenario: Word-boundary mode does not match inside a longer word

- GIVEN a dictionary in `wordBoundary` mode with the term "Berg"
- WHEN the text "De inwoners van Bergen protesteerden" is matched
- THEN no occurrence is returned for "Berg"
- @e2e exclude pure matcher logic — covered by PHPUnit (tests/unit/Service/CustomDictionaryMatchServiceTest.php::testWordBoundaryDoesNotMatchInsideWord)

#### Scenario: Case-insensitive mode returns all occurrences

- GIVEN a dictionary in `caseInsensitive` mode with the term "Zilverreiger"
- WHEN a text contains "Zilverreiger" and "zilverreiger"
- THEN both occurrences are returned with their positions
- @e2e exclude pure matcher logic — covered by PHPUnit (tests/unit/Service/CustomDictionaryMatchServiceTest.php)

### Requirement: Matches flow through the pipeline as CUSTOM_DICTIONARY entities (REQ-DDCDR-003)

Dictionary matches MUST be written into OpenRegister's shared entity catalogue
via `EntityRelationMapper` as entities of type `CUSTOM_DICTIONARY`
(category `contextual_data`) with one relation per occurrence carrying
`detectionMethod = custom_dictionary`, confidence `1.0`, the occurrence
positions and the owning dictionary's label — so the occurrences appear in
detection results, the review workbench, the grondslagen summary and
OpenRegister's redaction identically to any other entity. The matching pass
MUST run inside DocuDesk's existing detection entry point after OpenRegister
extraction, MUST be skipped when the `CUSTOM_DICTIONARY` type is disabled in
the operator's enabled-type selection, MUST clear its prior
`custom_dictionary` relations for a file before re-matching (no duplicates),
and MUST be best-effort: a matcher failure MUST be logged and surfaced as a
warning without blocking OpenRegister-side detection. DocuDesk MUST NOT add a
DocuDesk-local entity store and MUST NOT modify OpenRegister's detection
engine or redaction placeholder format.

#### Scenario: A dictionary hit is detected, reviewable and redacted

- GIVEN an active dictionary with the term "Operatie Zilverreiger" and a document containing it
- WHEN the document is extracted and detected
- THEN a CUSTOM_DICTIONARY occurrence for "Operatie Zilverreiger" appears in the review workbench and is replaced in the anonymised output
- @e2e tests/e2e/spec-coverage/custom-dictionary-recognition.spec.ts

#### Scenario: Re-running detection does not duplicate dictionary relations

- GIVEN a document already matched once by a dictionary
- WHEN detection is run again on the same document
- THEN the CUSTOM_DICTIONARY occurrence count is unchanged (prior custom_dictionary relations were cleared first)
- @e2e exclude idempotency of the catalogue write — covered by PHPUnit (tests/unit/Service/CustomDictionaryMatchServiceTest.php::testReRunDoesNotDuplicate)

#### Scenario: Matcher failure does not block OpenRegister detection

- GIVEN the dictionary catalogue write path fails
- WHEN a document is detected
- THEN OpenRegister's own detected entities are still returned together with a warning that dictionary matching did not run
- @e2e exclude fault-injection on the write path — covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)

### Requirement: Organisation-gated dictionary and term management API (REQ-DDCDR-004)

The app MUST provide `api/custom-dictionaries` CRUD routes (list, create,
read, update, delete a dictionary and manage its terms) backed by
OpenRegister's ObjectService. Every route MUST enforce the organisation gate
server-side and fail closed: a caller may only operate on dictionaries of
their accessible organisations, and a non-permitted call MUST receive HTTP 403
with a neutral body. Each route MUST declare an explicit Nextcloud auth
attribute and MUST perform the organisation check in the method body (not rely
on the hidden navigation entry). When OpenRegister is unavailable the routes
MUST return an explanatory unavailable state, never a crash.

#### Scenario: Editing another organisation's dictionary is refused

- GIVEN a dictionary owned by organisation A
- WHEN a user with access only to organisation B calls update on it
- THEN the response is HTTP 403 and the dictionary is unchanged
- @e2e exclude authorization matrix — covered by PHPUnit (tests/unit/Controller/CustomDictionaryControllerTest.php)

#### Scenario: A permitted manager creates a dictionary

- GIVEN a manager of organisation A
- WHEN they create a dictionary "Straatnamen" with match mode word-boundary
- THEN it is persisted under organisation A and listed for them
- @e2e tests/e2e/spec-coverage/custom-dictionary-recognition.spec.ts

### Requirement: CSV and newline term import (REQ-DDCDR-005)

The app MUST provide `POST api/custom-dictionaries/{uuid}/import` accepting
either a CSV upload (first column the term value, optional second column a
per-term label) or a newline-separated plain-text list, parsed server-side.
Import MUST trim values, skip blank lines, de-duplicate case-insensitively
against the dictionary's existing terms, create `customDictionaryTerm`
objects for the remainder, and return the counts of added, skipped and total
terms. Import size MUST be bounded to prevent a denial-of-service via an
oversized upload. Parsing MUST NOT be delegated to the browser.

#### Scenario: Import adds new terms and skips duplicates

- GIVEN a dictionary already containing the term "Ridderkerk"
- WHEN a newline list of "Ridderkerk", "Barendrecht", "  " (blank) is imported
- THEN the response reports 1 added, 2 skipped, and only "Barendrecht" is created
- @e2e exclude import parsing/dedupe logic — covered by PHPUnit (tests/unit/Service/CustomDictionaryServiceTest.php::testImportDedupesAndSkipsBlanks)

#### Scenario: Import through the admin page

- GIVEN a manager on a dictionary detail page
- WHEN they upload a CSV of terms
- THEN the new terms appear in the term table with the reported added count
- @e2e tests/e2e/spec-coverage/custom-dictionary-recognition.spec.ts

### Requirement: Custom-dictionary admin UI (REQ-DDCDR-006)

The app MUST provide a gated "Custom dictionaries" management page registered
in the Manifest-V2 shell (`src/manifest.json` + `registry.js`, never
`src/router/index.js`): an index (`CnIndexPage` + `CnDataTable`: label, term
count, match mode, active) and a detail/edit view (term table with add/remove,
CSV/newline import, match-mode selector, active toggle, colour). Every
`NcSelect` MUST carry an `inputLabel`; modals and dialogs MUST live in their
own files under `src/modals/`/`src/dialogs/`; colours and spacing MUST use
Nextcloud CSS variables / NL Design tokens with no hardcoded colours. The
navigation entry MUST be hidden for users the organisation gate refuses.

#### Scenario: The dictionaries page lists dictionaries with their term counts

- GIVEN two dictionaries owned by the operator's organisation
- WHEN the operator opens the Custom dictionaries page
- THEN both are listed with their label, term count, match mode and active state
- @e2e tests/e2e/spec-coverage/custom-dictionary-recognition.spec.ts

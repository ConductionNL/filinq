# inbound-auto-classification Specification (delta)

---
status: proposed
---

## Purpose

Classification suggestions for inbound documents: a rule/keyword
document-type classifier (Dutch intake types) plus a correspondent
extractor over the already-computed NER entities, both landing as
`classificationResult` suggestion records that a human confirms, corrects
or rejects — never silent auto-filing (AI Act human-oversight posture,
mirroring the review-workbench suggest-then-approve philosophy). A
confirmed suggestion is the only path to canonical metadata and dossier
filing; corrections are recorded as the training corpus for the
explicitly deferred learned classifier. All processing is local.

## ADDED Requirements

### Requirement: Inbound documents receive a document-type suggestion (REQ-DDIAC-001)

The system MUST classify inbound documents with extractable text into
exactly one of the Dutch intake types `brief`, `besluit`, `factuur`,
`rapport`, `contract`, `formulier`, `overig`, using rule/keyword
vocabularies owned by a dedicated stateless `DocumentTypeClassifier`
class consumed via dependency injection (the REQ-META-11 class-boundary
discipline; vocabularies MUST NOT be redefined in consuming services).
Each classification MUST produce a normalised confidence (0–1) and record
the `method`; a below-threshold score MUST yield `overig` with its low
confidence — never a fabricated confident type. Classification MUST run
where enrichment runs (object events and the on-demand enrichment API),
MUST be governed by the `enable_inbound_classification` admin toggle, and
a document without extractable text MUST be skipped with the reason
flagged, never silently ignored.

#### Scenario: An inbound invoice is typed with confidence

- GIVEN classification enabled and an inbound PDF containing "Factuurnummer", an IBAN and payment terms
- WHEN enrichment runs for the document
- THEN a `classificationResult` exists with `suggestedDocumentType: "factuur"` and a confidence score
- AND the document's canonical metadata is unchanged
- @e2e tests/e2e/spec-coverage/inbound-classification.spec.ts

#### Scenario: An ambiguous document falls back to overig

- GIVEN a document whose text matches no type vocabulary above the threshold
- WHEN classification runs
- THEN the suggestion is `overig` with a low confidence value
- @e2e exclude scoring-threshold contract; covered by PHPUnit (tests/unit/Service/DocumentTypeClassifierTest.php)

#### Scenario: Toggle disables the surface

- GIVEN `enable_inbound_classification` is off
- WHEN a document is enriched
- THEN no `classificationResult` is created
- @e2e exclude admin-toggle pass-through; covered by PHPUnit (tests/unit/Service/InboundClassificationServiceTest.php)

### Requirement: A correspondent suggestion is derived from detected entities (REQ-DDIAC-002)

The system MUST derive a suggested correspondent (`name` plus entity
taxonomy `PERSON` | `ORGANIZATION`) from the document's already-detected
OpenRegister NER entities, ranked by document position
(letterhead/first-page zone first), frequency, and organisation-suffix
heuristics. The system MUST NOT run a second entity-detection engine and
MUST NOT fabricate a correspondent when no candidate exists. When entity
detection has not yet run for the document, the suggestion MUST carry a
`correspondentPending` flag and MUST be superseded by an updated
suggestion once detection completes.

#### Scenario: Letterhead organisation becomes the correspondent

- GIVEN a document whose detected entities include "Heijmans B.V." (ORGANIZATION, first page) and three PERSON mentions in the body
- WHEN classification runs
- THEN `suggestedCorrespondent` is `Heijmans B.V.` with `entityType: ORGANIZATION`
- @e2e tests/e2e/spec-coverage/inbound-classification.spec.ts

#### Scenario: No detection yet is flagged, not fabricated

- GIVEN a document that has no entity detection results yet
- WHEN classification runs
- THEN the suggestion carries the type leg plus `correspondentPending`
- AND no correspondent name is invented
- @e2e exclude pending-flag degradation; covered by PHPUnit (tests/unit/Service/InboundClassificationServiceTest.php)

### Requirement: Suggestions require human confirmation — never silent auto-file (REQ-DDIAC-003)

Classification output MUST land exclusively as a `classificationResult`
object in status `suggested`; the system MUST NOT write the canonical
`documentType`, correspondent metadata, tags, or file location from a
suggestion. A human MUST be able to confirm, correct (confirm with a
different value — both the suggested and the confirmed values MUST be
retained on the record) or reject each suggestion; only a confirm action
MAY apply canonical effects, and every confirm/reject MUST record
`confirmedBy`/`confirmedAt` and be auditable. The system MUST NOT offer
an auto-confirm-above-confidence option in this wave. The confirmation
surface MUST show pending suggestions (type, confidence, correspondent,
dossier) and support acting on them individually and in bulk over
visible rows.

#### Scenario: Confirming applies canonical metadata

- GIVEN a `suggested` classification of `factuur` for a document
- WHEN a user confirms it
- THEN the document's canonical `documentType` becomes `factuur` and the record moves to `confirmed` with `confirmedBy` set
- @e2e tests/e2e/spec-coverage/inbound-classification.spec.ts

#### Scenario: Correcting records the corpus row

- GIVEN a `suggested` type `brief` the reviewer knows is a `besluit`
- WHEN the reviewer confirms with the corrected type
- THEN the record retains `suggestedDocumentType: "brief"` AND `confirmedDocumentType: "besluit"`
- @e2e tests/e2e/spec-coverage/inbound-classification.spec.ts

#### Scenario: Rejection has no canonical effect

- GIVEN a `suggested` classification
- WHEN a user rejects it
- THEN the record moves to `rejected` and the document's metadata and location are unchanged
- @e2e exclude no-op rejection contract; covered by PHPUnit (tests/unit/Controller/ClassificationControllerTest.php)

#### Scenario: No suggestion ever auto-applies

- GIVEN a suggestion with confidence 0.99 that no human has acted on
- WHEN any amount of time passes and enrichment re-runs
- THEN the document's canonical metadata and location remain unchanged
- @e2e exclude absence-of-behaviour guard; covered by PHPUnit (tests/unit/Service/InboundClassificationServiceTest.php)

### Requirement: Dossier routing is a suggestion only (REQ-DDIAC-004)

The system MUST set `suggestedDossier` only on a high-precision match
(suggested correspondent name or an explicit case reference matching a
dossier name); fuzzy matching MUST NOT be used in this wave. Filing MUST
happen only on human confirmation: confirming a suggestion with a dossier
MUST attach the document via the dossier's existing folder binding
(moving the file into the dossier's bound folder when resolvable, and
recording the association with a visible degradation notice when not).
The system MUST NOT move, tag, or re-file any document automatically.

#### Scenario: Matching dossier is suggested, not applied

- GIVEN a dossier named "Heijmans B.V. — nieuwbouw" and an inbound document whose correspondent suggestion is "Heijmans B.V."
- WHEN classification runs
- THEN the suggestion carries the dossier reference and the file has not moved
- @e2e tests/e2e/spec-coverage/inbound-classification.spec.ts

#### Scenario: Confirmation files the document

- GIVEN a suggestion carrying a dossier with a bound folder
- WHEN the user confirms including the dossier
- THEN the file moves into the dossier's bound folder and `confirmedDossier` is recorded
- @e2e tests/e2e/spec-coverage/inbound-classification.spec.ts

### Requirement: Classification results are persisted minimally with declared retention (REQ-DDIAC-005)

Every classification MUST persist exactly one active
`classificationResult` OpenRegister object keyed by `fileId`
(re-classification MUST supersede the previous record, status
`superseded`), carrying the suggestion fields, the confirmation fields
and the method — and MUST NOT store the document text or any detected
entity values other than the single suggested/confirmed correspondent
name. The schema MUST declare `x-openregister-archival` retention (`P1Y`,
explicit placeholder pending selectielijst sign-off, correspondent name
being personal data held for the intake purpose). The confirmation API
MUST declare its auth posture on every method and MUST resolve documents
through the requesting user's access only.

#### Scenario: One active record per file

- GIVEN a document classified twice (detection completed in between)
- WHEN the records are queried
- THEN exactly one record is in a non-superseded status and the earlier one is `superseded`
- @e2e exclude idempotency-key contract; covered by PHPUnit (tests/unit/Service/InboundClassificationServiceTest.php)

#### Scenario: Record carries no document content

- GIVEN a confirmed classification for a document containing many detected entities
- WHEN the `classificationResult` object is read
- THEN it contains the correspondent name at most and no document text and no other entity values
- @e2e exclude data-minimisation shape contract; covered by PHPUnit (tests/unit/Service/InboundClassificationServiceTest.php)

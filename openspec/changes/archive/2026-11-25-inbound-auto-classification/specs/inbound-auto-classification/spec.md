# inbound-auto-classification Specification (delta)

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

The system MUST classify each inbound document with extractable text into
exactly one Dutch intake type, with a normalised confidence (0–1) and the
`method`; a below-threshold score MUST yield `overig` with its low
confidence, never a fabricated confident type. Classification MUST run
where enrichment runs, behind the `enable_inbound_classification` toggle,
and a document without extractable text MUST be skipped with the reason
flagged. The classifier boundary is set out in the scenarios below.

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

#### Scenario: The types and the classifier boundary
- GIVEN the classifier
- WHEN its output is read
- THEN the type is one of `brief`, `besluit`, `factuur`, `rapport`, `contract`, `formulier` or `overig`
- AND the rule/keyword vocabularies live in a dedicated stateless `DocumentTypeClassifier` consumed through dependency injection (the REQ-META-11 class-boundary discipline), and no consuming service redefines them
- @e2e exclude classifier boundary; covered by PHPUnit (tests/unit/Service/DocumentTypeClassifierTest.php::testEveryTypeIsInTheSchemaEnum, ::testLanguageClassifierIsUntouched)

#### Scenario: Where classification runs and what it skips
- GIVEN classification enabled
- WHEN an object event or the on-demand enrichment API offers a document
- THEN it is classified when it is inbound and has text
- AND a document without extractable text is skipped with its reason flagged, never silently ignored
- @e2e exclude trigger and skip paths; covered by PHPUnit (tests/unit/Service/InboundClassificationServiceTest.php::testADocumentWithoutTextIsSkippedWithItsReason, ::testOnlyInboundDocumentsAreClassified)

### Requirement: A correspondent suggestion is derived from detected entities (REQ-DDIAC-002)

The system MUST derive a suggested correspondent (`name` plus `PERSON` or
`ORGANIZATION`) from the document's already-detected OpenRegister NER
entities, ranked as the scenarios below state. It MUST NOT run a second
entity-detection engine and MUST NOT fabricate a correspondent when no
candidate exists. Before detection has run, the suggestion MUST carry
`correspondentPending` and MUST be superseded once detection completes.

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

#### Scenario: Candidates are ranked by position, frequency and suffix
- GIVEN several detected entities
- WHEN the correspondent is ranked
- THEN document position counts first (letterhead or first-page zone), then frequency, then organisation-suffix heuristics
- @e2e exclude ranking rule; covered by PHPUnit (tests/unit/Service/Classification/CorrespondentRankerTest.php)

### Requirement: Suggestions require human confirmation — never silent auto-file (REQ-DDIAC-003)

Classification output MUST land only as a `classificationResult` in status
`suggested`; the system MUST NOT write canonical `documentType`,
correspondent metadata, tags or file location from a suggestion. A human
MUST be able to confirm, correct or reject each suggestion; only a confirm
MAY apply canonical effects. The system MUST NOT offer an
auto-confirm-above-confidence option in this wave. Recording and the
confirmation surface are set out in the scenarios below.

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

#### Scenario: Every decision is recorded
- GIVEN a suggestion
- WHEN a person confirms, corrects or rejects it
- THEN `confirmedBy` and `confirmedAt` are recorded and the decision is auditable
- AND a correction keeps both the suggested and the confirmed values on the record
- @e2e exclude decision recording; covered by PHPUnit (tests/unit/Service/Classification/ClassificationDecisionServiceTest.php)

#### Scenario: The confirmation surface lists and acts in bulk
- GIVEN pending suggestions
- WHEN the confirmation surface renders
- THEN it shows each suggestion's type, confidence, correspondent and dossier
- AND it supports acting on them one at a time and in bulk over the visible rows
- @e2e tests/e2e/spec-coverage/inbound-classification.spec.ts

### Requirement: Dossier routing is a suggestion only (REQ-DDIAC-004)

The system MUST set `suggestedDossier` only on a high-precision match (the
suggested correspondent name or an explicit case reference matching a
dossier name); fuzzy matching MUST NOT be used in this wave. Filing MUST
happen only on human confirmation, through the dossier's existing folder
binding. The system MUST NOT move, tag or re-file any document
automatically.

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

#### Scenario: A dossier without a reachable folder is recorded with a notice
- GIVEN a confirmation with a dossier whose bound folder cannot be resolved for the caller
- WHEN the confirmation is applied
- THEN the association is recorded and a visible degradation notice says the file was not moved
- @e2e exclude degradation path; covered by PHPUnit (tests/unit/Service/Classification/ClassificationDecisionServiceTest.php)

### Requirement: Classification results are persisted minimally with declared retention (REQ-DDIAC-005)

Every classification MUST persist exactly one active `classificationResult`
per `fileId`; re-classification MUST supersede the previous record (status
`superseded`). The record carries the suggestion fields, the confirmation
fields and the method, and MUST NOT store the document text or any
detected entity value other than the single correspondent name. Retention
and the API's access posture are set out in the scenarios below.

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

#### Scenario: The schema declares its retention
- GIVEN the shipped `classificationResult` schema
- WHEN its archival annotation is read
- THEN it declares `x-openregister-archival` retention `P1Y`, an explicit placeholder pending selectielijst sign-off, because the correspondent name is personal data held for the intake purpose
- @e2e exclude schema annotation; no dedicated test yet

#### Scenario: The confirmation API reaches files through the caller's access
- GIVEN the confirmation API
- WHEN any of its methods is called
- THEN the method declares its auth posture
- AND documents are resolved through the requesting user's own access only
- @e2e exclude access path; covered by PHPUnit (tests/unit/Controller/ClassificationControllerTest.php::testPendingListsTheCallersSuggestions, ::testWithoutASessionNothingIsReachable)


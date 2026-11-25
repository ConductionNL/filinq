# inbound-auto-classification Specification

## Purpose
A document that arrives before anybody knows which case it belongs to
gets a record, its defaults, a suggested type and a suggested party, and
stays inside the access model from the first byte. An attachment reaches
the case with the message it arrived with, and a document taken off a
case lands on a worklist instead of nowhere. Round 4 discovery cluster
44, eleven candidates. Consumed by dossiq, which owns the promote-to-case
act.

## Requirements

### Requirement: An attachment is a record of its own, linked to its message (REQ-IDW-01)

Filinq MUST create one `intakeDocument` per arriving file, including one
per attachment, each attachment carrying `arrivedWith` referencing the
record of the message it arrived with. Assigning the message MUST offer
to assign its attachments in the same act. Assigning an attachment on its
own MUST be allowed and MUST be recorded on both records.

#### Scenario: An aanvraag with three bijlagen stays together

- GIVEN a message carrying an aanvraag and three attachments
- WHEN it arrives through any channel
- THEN four intake records exist, the three attachments reference the message, and assigning the message offers all four

#### Scenario: One attachment belongs elsewhere

- GIVEN four intake records from one message
- WHEN a clerk assigns one attachment to a different case
- THEN that assignment is recorded on the attachment and noted on the message record

### Requirement: Default metadata is stamped at creation (REQ-IDW-02)

Filinq MUST declare `intakeDefaultRule` objects matching on channel or
sender pattern, each naming the metadata it stamps and its order. The
matching rule's values MUST be written onto the `intakeDocument` at
creation, before any classification runs. The record MUST name the rule
that stamped it. Classification MUST offer changes to a stamped value as
a suggestion and MUST NOT overwrite it silently.

#### Scenario: Nothing arrives unclassified

- GIVEN a default rule stamping documentType "brief" and confidentiality "intern" on the scan channel
- WHEN a scan arrives
- THEN the intake record carries both values and names the rule, before the classifier has run

#### Scenario: The classifier suggests, it does not overwrite

- GIVEN a stamped documentType of "brief" and a classifier suggesting "factuur"
- WHEN the clerk opens the record
- THEN the stamped value stands and the suggestion is offered beside it

#### Scenario: No rule matches

- GIVEN a channel with no matching default rule
- WHEN a document arrives
- THEN the record is created with no stamped defaults and says so, rather than failing

### Requirement: Party details are suggested from the document, never written (REQ-IDW-03)

Filinq MUST read the entity occurrences already detected on the file and
MUST offer a party suggestion carrying its confidence and its source
span. It MUST NOT write a party record without a person accepting the
suggestion. Accept, edit and reject MUST each be recorded as a
correction. The extraction MUST be declared as its own
`x-openregister-processing` activity with purpose, legal basis, data
categories and retention.

#### Scenario: The NAW block is offered, not filed

- GIVEN an incoming letter whose text carries a name and an address
- WHEN the intake record is opened
- THEN a party suggestion is shown with its confidence and the text it came from, and no party record has been written

#### Scenario: A rejection teaches the corpus

- GIVEN a party suggestion a clerk rejects
- WHEN they reject it
- THEN the rejection is recorded as a correction against that sender

#### Scenario: The activity is declared

- GIVEN the register descriptor after this change
- WHEN the processing activity export is produced
- THEN party extraction appears as its own activity with a purpose, a legal basis and a retention reference
- @e2e exclude the export is a register descriptor read; covered by the processing-activity PHPUnit test

### Requirement: A detached document lands on a worklist (REQ-IDW-04)

A document detached from a record MUST return to its `intakeDocument` in
state `detached`, carrying the reason and the user who detached it, and
MUST be created one if it never had it. The intake inbox MUST list
detached documents as their own worklist.

#### Scenario: A document removed from the wrong case

- GIVEN a document filed on the wrong case
- WHEN a handler detaches it with the reason "verkeerde zaak"
- THEN it appears in the detached worklist with that reason and the handler's name

#### Scenario: A document that never had an intake record

- GIVEN a document uploaded straight onto a case
- WHEN it is detached
- THEN an intake record is created for it in state `detached`

### Requirement: Routing and acceptance are declared by the consuming app (REQ-IDW-05)

Filinq MUST store, per record type reference the consumer declares, who
an inbound document routes to and whether an acceptance step is required,
and MUST apply it at arrival. Filinq MUST NOT define the record type
vocabulary itself.

#### Scenario: A bezwaar routes to the jurists

- GIVEN a case type declaring that inbound documents route to the jurist group and require acceptance
- WHEN a document is filed against that type
- THEN it is routed to that group and waits for acceptance

#### Scenario: No declaration, no routing

- GIVEN a record type with no routing declared
- WHEN a document is filed against it
- THEN the document is assigned without routing and without an acceptance step

### Requirement: The inbox is inside the access model and says what it is still reading (REQ-IDW-06)

Every `intakeDocument` MUST be an OpenRegister object from creation,
under a declared authorization cascade. The recognised text of a scan
MUST be written to the document's searchable content through the existing
OCR path, and text recognition MUST run locally. Classification and
extraction MUST run as background jobs, with progress on the intake
record, and the inbox MUST show documents still being read rather than
hiding them.

#### Scenario: A waiting document obeys the same permissions as a filed one

- GIVEN a user without access to the intake group
- WHEN they query the objects API for waiting documents
- THEN the cascade refuses them, exactly as it refuses a filed document
- @e2e exclude authorization cascade; covered by PHPUnit against the objects API with two users

#### Scenario: A scan is found by its words

- GIVEN a scanned letter mentioning "hoorzitting"
- WHEN the OCR has run and a user searches for that word
- THEN the scan is in the results

#### Scenario: A batch that is still being read

- GIVEN two hundred scans arriving at once
- WHEN a clerk opens the inbox
- THEN the records still being classified are listed with their progress

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

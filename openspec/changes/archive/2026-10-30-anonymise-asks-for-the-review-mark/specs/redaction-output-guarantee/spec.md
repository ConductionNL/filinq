# redaction-output-guarantee Specification (delta)

**Status**: planned
**OpenSpec changes**:
- [anonymise-asks-for-the-review-mark](../../) (this delta)

## ADDED Requirements

### Requirement: A refused anonymise says what to do (REQ-RWB-05)

When the review gate refuses a write, the anonymise endpoint SHALL answer HTTP 409 with a translated message, the gate's reason under `reviewRequired` and the document id. It SHALL NOT answer 5xx for a refusal. For `never_checked` the Dutch message SHALL be "Controleer eerst de gevonden gegevens". No file SHALL be written.

#### Scenario: Nobody checked the document
- GIVEN a document whose detection run has no review mark
- WHEN an operator asks for the anonymised copy
- THEN the answer is 409 with `reviewRequired: never_checked` and "Controleer eerst de gevonden gegevens", and no file is written
- @e2e exclude pinned by `AnonymizationControllerReviewGateTest::testAnUncheckedDocumentIsAConflictNotAServerError`; the live gate needs a detector and OpenRegister

#### Scenario: The findings changed after the check
- GIVEN a mark recorded over an earlier set of findings
- WHEN anonymise is asked for with the current findings
- THEN the answer is 409 with `reviewRequired: checked_an_older_run`
- @e2e exclude pinned by `AnonymizationControllerReviewGateTest::testAMarkOverOtherFindingsIsAConflictToo`

### Requirement: The screens record the check before they anonymise (REQ-RWB-06)

The review screens SHALL offer the reviewer an explicit action to mark the findings as checked, and SHALL record the mark only from that action, never on their own. The mark SHALL be recorded over exactly the entities the anonymise call sends. The anonymise action SHALL stay unavailable until the findings on screen are checked, and changing the selection after the check SHALL ask for a new one. Recording a mark SHALL require that the caller can open the file.

#### Scenario: Check, then anonymise
- GIVEN a document with detected entities on the review screen
- WHEN the reviewer marks it as checked and then anonymises
- THEN the mark and the anonymise request carry the same entities and the copy is written
- @e2e exclude pinned by the store spec `records the check over exactly the entities the anonymise call sends`

#### Scenario: A file the caller cannot open
- GIVEN a file outside the caller's reach
- WHEN they post a review mark for it
- THEN the answer is 404 and nothing is recorded
- @e2e exclude pinned by `RedactionOutputControllerTest::testAFileTheCallerCannotOpenIsNotMarked`

### Requirement: A folder or dossier run writes only checked files (REQ-RWB-07)

A folder or dossier anonymise SHALL send only the files whose findings a person checked, and SHALL list by name the files it left alone because nobody checked them.

#### Scenario: Some files are not checked
- GIVEN a folder with one checked and one unchecked file
- WHEN the folder is anonymised
- THEN only the checked file is sent and the unchecked file is listed as still needing a check
- @e2e exclude pinned by `folderAnonymization.spec.js` and `anonymization.spec.js`

### Requirement: My documents shows who checked a file (REQ-RWB-08)

My documents SHALL show per file whether a review mark exists, and on hover who checked it and when. The status SHALL be read in one call per page, and SHALL leave out files the caller cannot open.

#### Scenario: A page of documents
- GIVEN a page with one checked and one unchecked file
- WHEN the list renders
- THEN the first row says Gecontroleerd and the second Niet gecontroleerd
- @e2e exclude pinned by `RedactionOutputControllerTest::testTheStatusLeavesOutFilesTheCallerCannotOpen` and `reviewMark.spec.js`

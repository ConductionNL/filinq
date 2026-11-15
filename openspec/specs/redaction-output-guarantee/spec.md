# redaction-output-guarantee Specification

## Purpose
What leaves the building is checked by a person and cannot be read back.
Round 4 discovery cluster 57, four candidates, two of them `must` with a
documented passer only, admitted under decision D6. Consumed by dossiq,
which reviews the proposal.

## Requirements

### Requirement: The written copy cannot be read back (REQ-RWB-01)

A redacted document filinq writes MUST contain no recoverable trace of a
redacted value. Filinq MUST verify the produced bytes for each of: text
beneath the mark, embedded thumbnail or preview streams, XMP, EXIF and
document properties, incremental updates or earlier revisions, annotation
and form field values, and files attached inside the document. The
verification MUST run on every output mode, and an output mode with no
verification entry MUST fail the suite. The result MUST be recorded
against the `anonymizationLink`.

#### Scenario: No live text under the mark

- GIVEN a document with a name redacted
- WHEN the written copy's text is extracted
- THEN the name does not appear

#### Scenario: The thumbnail does not betray the page

- GIVEN a redacted PDF carrying an embedded preview
- WHEN the copy is written
- THEN the preview is absent or is regenerated from the redacted page
- @e2e exclude the check reads the produced bytes; covered by PHPUnit with fixtures per output mode

#### Scenario: An accessibility rewrite does not restore the text

- GIVEN the PDF/UA output mode
- WHEN a redacted document is produced through it
- THEN every verification route passes on those bytes too

#### Scenario: A new output mode cannot pass by omission

- GIVEN an output mode added with no verification entry
- WHEN the suite runs
- THEN it fails, naming the mode
- @e2e exclude suite wiring; covered by the verifier's own unit test

### Requirement: Nothing is written until a person has checked it (REQ-RWB-02)

Filinq MUST refuse to write a redacted copy until a
`redactionReviewMark` exists for that document and its current detection
run, naming the person and the moment. The refusal MUST be enforced in
the service, so it applies to the API, the batch path and the leaf alike.
Re-running detection MUST clear the mark. The refusal message MUST tell
the operator what to do.

#### Scenario: The screen path is gated

- GIVEN a document whose entities have been detected and not reviewed
- WHEN an operator asks for the anonymised output
- THEN the request is refused and the message says the document must be checked first

#### Scenario: A batch of fifty-five thousand is gated too

- GIVEN a batch run over unreviewed documents
- WHEN the batch is started
- THEN the unreviewed documents are refused by the same rule, and the batch reports them
- @e2e exclude batch path; covered by PHPUnit on the batch service

#### Scenario: Re-detecting clears the check

- GIVEN a document marked checked
- WHEN detection is re-run on it
- THEN the mark is cleared and output is refused again, saying why

### Requirement: A publishable document is composed from redacted copies (REQ-RWB-03)

Filinq MUST be able to compose a document over the records a named saved
view returns, resolving each entry's redacted copy through its
`anonymizationLink`. It MUST NOT reference, link or embed an original. A
record in the view with no redacted copy MUST be reported as not ready
and the document MUST NOT be produced until it is ready or the record
leaves the view. The composed document MUST record the view, the count
and the moment.

#### Scenario: A Woo-publicatielijst composes itself

- GIVEN a saved view of published Woo documents, each with a redacted copy
- WHEN the list is composed
- THEN one document is produced linking each redacted copy, and recording the view and the count

#### Scenario: An original is never referenced

- GIVEN a record whose original and redacted copy both exist
- WHEN the list is composed
- THEN the entry resolves through the `anonymizationLink` to the redacted copy, and the original appears nowhere

#### Scenario: A record that is not ready stops the list

- GIVEN a record in the view with no redacted copy
- WHEN the list is composed
- THEN the composition reports that record as not ready and produces nothing

### Requirement: A download can be gated on an accepted agreement (REQ-RWB-04)

Filinq MUST support a `downloadAgreement` carrying its text and its
version. When a file is gated, the agreement MUST be shown before the
download, and the acceptance MUST be recorded with the person, the moment
and the version. A new version MUST ask again. If the acceptance cannot
be written, the download MUST NOT be served.

#### Scenario: A reuse condition is accepted before the file arrives

- GIVEN a published document gated on a hergebruiksvoorwaarde
- WHEN a reader downloads it
- THEN they accept the agreement first and the acceptance is recorded with its version

#### Scenario: A new version asks again

- GIVEN a reader who accepted version 1
- WHEN the agreement is published as version 2 and they download again
- THEN they are asked again

#### Scenario: An unrecordable acceptance stops the download

- GIVEN a store that refuses the acceptance write
- WHEN a reader accepts
- THEN the file is not served and the reader is told the acceptance could not be recorded
- @e2e exclude failure path; covered by PHPUnit with a refusing store

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

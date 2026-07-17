# flow-operations Specification (delta)

---
status: proposed
---

## Purpose

DocuDesk's Nextcloud Flow integration: four file operations (anonymise
document, run OCR, convert to PDF/A, run validation checks) registered
with the platform workflow engine so admins and users automate document
processing on native Flow triggers ("File created", "Tag assigned", ...)
without code. Operations are thin asynchronous shells over existing
DocuDesk engines — they inherit every gate (prohibition gate, human
review checked gate, OCR admin settings) and can bypass none. Every run
is recorded as a `flowOperationRun` processing-log object; failures
notify the file owner via Nextcloud notifications. All processing stays
local.

## ADDED Requirements

### Requirement: Four Flow operations are registered with the workflow engine (REQ-DDFLO-001)

The system MUST register four operations — Anonymise document, Run OCR,
Convert to PDF/A, Run validation checks — by listening to
`OCP\WorkflowEngine\Events\RegisterOperationsEvent` and calling
`registerOperation()` for each. Each operation MUST implement
`OCP\WorkflowEngine\ISpecificOperation` bound to the file entity, MUST be
available in both `SCOPE_ADMIN` and `SCOPE_USER`, MUST provide a
translated display name, description and icon, and MUST ship its Flow
rule-builder registration (operator script loaded via
`LoadSettingsScriptsEvent`) so the operation is configurable in the Flow
settings UI. `validateOperation()` MUST reject a malformed operation
configuration with `UnexpectedValueException` so an invalid rule cannot
be saved.

#### Scenario: Admin builds a rule from a DocuDesk operation

- GIVEN a Nextcloud instance with DocuDesk enabled
- WHEN an admin opens Settings → Flow
- THEN all four DocuDesk operations are offered as rule operations with name, description and icon
- AND a rule "when File created and folder is /Inkomend, then Run OCR" can be saved
- @e2e tests/e2e/spec-coverage/flow-operations.spec.ts

#### Scenario: Malformed operation config is rejected at save time

- GIVEN an admin configuring the Run validation checks operation
- WHEN the rule is saved with a `documentType` value that is not a known profile key
- THEN `validateOperation()` throws and the engine refuses to store the rule
- @e2e exclude rule-save validation contract; covered by PHPUnit (tests/unit/Flow/OperationValidationTest.php)

### Requirement: Operations execute asynchronously via queued jobs (REQ-DDFLO-002)

`onEvent()` MUST only match (via `IRuleMatcher::getMatchingOperations()`),
create a `flowOperationRun` object in status `queued`, and enqueue one
`FlowOperationJob` (QueuedJob) per matched file; document processing MUST
NOT run inside the triggering request. Non-file entities and folders MUST
be ignored. An identical `{operation, fileId}` pair with a run already in
status `queued` or `running` MUST NOT be enqueued twice. The job MUST
execute the operation in the file owner's context, and the delegated
service's own access and policy checks MUST apply unchanged.

#### Scenario: Upload returns before processing runs

- GIVEN a rule "File created in /Inkomend → Run OCR" and a scanned PDF uploaded there
- WHEN the upload request completes
- THEN a `flowOperationRun` exists in status `queued` for the file
- AND OCR has not yet run; it completes on the next cron execution
- @e2e tests/e2e/spec-coverage/flow-operations.spec.ts

#### Scenario: Duplicate trigger does not double-process

- GIVEN a queued OCR run for file 812006
- WHEN a second matching event fires for the same file before the job runs
- THEN no second `flowOperationRun` is created for `{ocr, 812006}`
- @e2e exclude dedupe contract on the enqueue path; covered by PHPUnit (tests/unit/Flow/FlowOperationJobTest.php)

### Requirement: Operation outputs never re-trigger flows (REQ-DDFLO-003)

Files produced by a DocuDesk Flow operation MUST NOT re-enter DocuDesk
Flow processing (e.g. the PDF/A artifact): before enqueueing, `onEvent()`
MUST check the candidate fileId against the `producedFileId` values
recorded on `flowOperationRun` objects and skip matches with a run in
status `skipped`, reason `own_output`. A conversion loop (output triggers
"File created" triggers conversion) MUST be impossible.

#### Scenario: PDF/A output does not loop

- GIVEN a rule "File created in /Archief → Convert to PDF/A"
- WHEN a conversion writes `besluit.pdfa.pdf` into /Archief
- THEN the new file's create event yields a run in status `skipped` with reason `own_output`
- AND no second conversion is enqueued
- @e2e tests/e2e/spec-coverage/flow-operations.spec.ts

### Requirement: Anonymise operation is intake-only and honours every gate (REQ-DDFLO-004)

The Anonymise document operation MUST run the anonymisation intake
pipeline (`AnonymizationService::extractAndDetectEntities()`, including
its grondslag proposals and policy matching) so the document enters the
human review queue. It MUST NOT create or update a `documentReview`
object, MUST NOT pass `acknowledgedOverrides`, and MUST NOT export or
publish any redacted output for a document the checked gate has not
cleared; anonymise-commit MAY run only when the
`anonymization-review-workbench` checked gate permits it for that file. A
`ProhibitionGateException` MUST fail the run with the gate reason
recorded. The run's `resultSummary` MUST carry entity counts per type
only — never detected entity values.

#### Scenario: Tagged document enters review, not export

- GIVEN a rule "Tag assigned `woo-verzoek` → Anonymise document" and a document containing a name and a BSN
- WHEN the tag is assigned and the job runs
- THEN detection completes and the document appears in the anonymisation review queue with its detected entities
- AND no anonymised export exists and the document is not marked reviewed
- @e2e tests/e2e/spec-coverage/flow-operations.spec.ts

#### Scenario: Prohibition gate failure is recorded, not bypassed

- GIVEN a document whose entities match an active publication prohibition
- WHEN the anonymise operation's commit path would run
- THEN the run ends `failed` with the prohibition-gate reason in `errorMessage`
- AND no override is applied
- @e2e exclude gate pass-through contract; covered by PHPUnit (tests/unit/Flow/AnonymizeDocumentOperationTest.php)

#### Scenario: Run summary contains no entity values

- GIVEN a completed anonymise run that detected the entity "J. de Vries"
- WHEN the `flowOperationRun` object is read
- THEN `resultSummary` reports counts per entity type (e.g. PERSON: 1)
- AND the string "J. de Vries" appears nowhere on the run object
- @e2e exclude data-minimisation shape contract; covered by PHPUnit (tests/unit/Flow/AnonymizeDocumentOperationTest.php)

### Requirement: Run OCR operation delegates to the OCR trigger surface (REQ-DDFLO-005)

The Run OCR operation MUST delegate to `OcrService::processFile()` under
the same admin settings as every other OCR surface (`ocr-trigger-surface`
REQ-DDOCR-006): OCR disabled MUST fail the run with reason `ocr_disabled`;
Tesseract unavailable MUST fail it with reason `tesseract_unavailable`; a
non-candidate MIME (`needsOcr()` false) MUST end the run `skipped`. A
completed run MUST persist the `ocrResult` object per REQ-DDOCR-005 with
`triggeredBy: "flow"`, and the run's `resultSummary` MUST carry the
confidence and text length — never the recovered text.

#### Scenario: Scan dropped in a watched folder is OCR'd

- GIVEN OCR enabled, Tesseract installed, and a rule "File created in /Inkomend → Run OCR"
- WHEN a scanned PDF lands in /Inkomend and cron runs
- THEN the run succeeds, the file's `ocrResult` records `triggeredBy: "flow"` with a confidence score
- AND the file listing shows `ocrProcessed: true`
- @e2e tests/e2e/spec-coverage/flow-operations.spec.ts

#### Scenario: Disabled OCR fails flagged, never silent

- GIVEN the admin has disabled OCR and an OCR rule still exists
- WHEN a matching file arrives
- THEN the run ends `failed` with reason `ocr_disabled`
- AND the file owner receives the failure notification
- @e2e exclude admin-toggle degradation; covered by PHPUnit (tests/unit/Flow/RunOcrOperationTest.php)

### Requirement: Convert to PDF/A produces an additive artifact (REQ-DDFLO-006)

The Convert to PDF/A operation MUST produce a NEW PDF/A file via the
existing conversion services (`Pdfa3ConversionService::convertExistingPdf()`
for PDF sources; `PdfConversionService::convertToPdf()` first for
convertible non-PDF sources) as a uniquely-named sibling of the source,
or inside the operation-configured `targetFolder` (resolved within the
owner's folder tree only). The source file MUST NOT be modified,
replaced, or deleted. The run MUST record the `producedFileId`; an
unconvertible source (e.g. encrypted PDF, unsupported MIME) MUST fail the
run with the reason recorded.

#### Scenario: Decision letter is archived as PDF/A

- GIVEN a rule "Tag assigned `archiveren` → Convert to PDF/A" with target folder `Archief`
- WHEN a DOCX decision letter is tagged and the job runs
- THEN a PDF/A file exists in `Archief`, the run records its `producedFileId`
- AND the original DOCX is unchanged
- @e2e tests/e2e/spec-coverage/flow-operations.spec.ts

#### Scenario: Encrypted PDF fails flagged

- GIVEN an encrypted PDF matching a PDF/A rule
- WHEN the job runs
- THEN the run ends `failed` with a conversion-refused reason
- AND the source file is untouched
- @e2e exclude conversion error contract; covered by PHPUnit (tests/unit/Flow/ConvertToPdfaOperationTest.php)

### Requirement: Run validation checks records profile findings (REQ-DDFLO-007)

The Run validation checks operation MUST call
`DocumentValidationService::validate()` with the operation-configured
document-type profile and record the findings and the aggregated verdict
in the run's `resultSummary`. An aggregate verdict of `error` MUST mark
the run `failed` (so the failure notification fires); `warning` MUST
leave the run `succeeded` with the findings attached.

#### Scenario: Upload with a disallowed format is flagged

- GIVEN a rule "File created in /Besluiten → Run validation checks (documentType: besluit)"
- WHEN a file whose format the `besluit` profile disallows is uploaded
- THEN the run ends `failed` with the format-not-allowed finding in `resultSummary`
- AND the file owner is notified
- @e2e tests/e2e/spec-coverage/flow-operations.spec.ts

#### Scenario: Warning-level findings do not fail the run

- GIVEN a document that yields only warning-severity findings for its profile
- WHEN the validation job runs
- THEN the run ends `succeeded` and `resultSummary` carries the warnings
- @e2e exclude severity-aggregation contract; covered by PHPUnit (tests/unit/Flow/RunValidationOperationTest.php)

### Requirement: Every run is logged and failures notify the file owner (REQ-DDFLO-008)

Every triggered operation MUST persist a `flowOperationRun` OpenRegister
object (`operation`, `fileId`, `fileName`, `ownerUserId`, `status`
`queued|running|succeeded|failed|skipped`, `statusReason`,
`triggerEvent`, `startedAt`, `finishedAt`, `resultSummary`,
`producedFileId`, `errorMessage`) with lifecycle declared via
`x-openregister-lifecycle` (initial `queued`) and retention via
`x-openregister-archival` (`P1Y`, operational processing log). A run
entering `failed` MUST notify the file owner via a Nextcloud notification
declared in the verified `x-openregister-notifications` dialect with
recipient `{"kind": "field", "field": "ownerUserId"}` (a confirmed NC
uid — never an external address), using the documented status-condition
approximation of the `docudesk-notifications` capability. The system MUST
offer a Flow-runs listing showing recent runs with status, operation,
file and failure reason.

#### Scenario: Failed run notifies the owner

- GIVEN a failed OCR run for a file owned by `w.devries`
- WHEN notifications are delivered
- THEN `w.devries` receives a Nextcloud notification naming the file, the operation and the failure reason
- AND no external email address is notified
- @e2e tests/e2e/spec-coverage/flow-operations.spec.ts

#### Scenario: Runs listing shows the processing log

- GIVEN completed, failed and skipped runs exist
- WHEN a user opens the Flow-runs listing
- THEN each run shows operation, file name, status, timing and reason
- @e2e tests/e2e/spec-coverage/flow-operations.spec.ts

### Requirement: DocuDesk is listed in the workflow app-store category (REQ-DDFLO-009)

`appinfo/info.xml` MUST declare the `workflow` app-store category in
addition to the existing `organization` category, so DocuDesk is
discoverable in the Flow/workflow category where the reference workflow
apps (workflow_ocr, workflow_pdf_converter) are found.

#### Scenario: Category is declared

- GIVEN the shipped `appinfo/info.xml`
- WHEN its category elements are read
- THEN both `organization` and `workflow` are present
- @e2e exclude static app-metadata declaration; covered by PHPUnit (tests/unit/AppInfo/InfoXmlTest.php)

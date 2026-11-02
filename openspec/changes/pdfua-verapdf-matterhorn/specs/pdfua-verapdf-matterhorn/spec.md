# pdfua-verapdf-matterhorn Specification (delta)

---
status: proposed
---

## Purpose

Upgrade the wave-1 heuristic accessibility surface to real PDF/UA-1
(Matterhorn Protocol) conformance validation, computed locally through the
veraPDF backend that `verapdf-validation` introduces (shared, not
duplicated). Produce a persisted, re-runnable accessibility conformance
report per document and honest remediation guidance, without regressing the
always-available heuristic floor. Statutory frame: Besluit digitale
toegankelijkheid overheid / EU Directive 2016/2102 / EN 301 549 → WCAG 2.1
AA; validated profile PDF/UA-1 (ISO 14289-1).

## ADDED Requirements

### Requirement: PDF/UA-1 conformance is validated through the shared veraPDF backend (REQ-DDPUM-001)

The app MUST validate PDF/UA-1 conformance using the veraPDF integration
introduced by `verapdf-validation` (`VeraPdfService`) — the SAME probed,
admin-installed local CLI binary, the SAME `filinq.verapdf.*` app config,
the SAME availability probe and admin-settings status row — invoked with
veraPDF's PDF/UA (`ua1`) validation flavour. This change MUST NOT introduce a
second validator binary, a second probe, a second config namespace, or a
second admin status row. Document bytes MUST NOT leave the instance. When the
veraPDF binary is absent or disabled, PDF/UA validation MUST degrade honestly
(the heuristic floor from `pdfua-accessible-output` remains, with an explicit
"not validated" state) and MUST NEVER fabricate a conformance verdict. The
validation result MUST be machine-readable — `flavour` (`ua1`), `compliant`,
`failedChecks` (Matterhorn clause/test/checkpoint references only, never
document content), `validatorVersion` — and a validator timeout, crash, or
unparseable output MUST raise a typed error that is NEVER recorded or reported
as compliant.

#### Scenario: Heuristically-tagged PDF fails real Matterhorn validation

- GIVEN an available veraPDF binary and a PDF that carries `/StructTreeRoot`, `/Lang`, a title and `pdfuaid:part` but contains untagged real content and a figure without an alternative description
- WHEN PDF/UA validation runs
- THEN the result is `compliant: false` for flavour `ua1` with the failed Matterhorn checkpoints listed by clause reference
- @e2e tests/e2e/spec-coverage/pdfua-verapdf-matterhorn.spec.ts

#### Scenario: Absent validator degrades to the heuristic floor honestly

- GIVEN no veraPDF binary on the instance
- WHEN PDF/UA conformance validation is requested
- THEN the response states the validator is unavailable and PDF/UA conformance was not validated
- AND no `compliant` value or conformance verdict is fabricated
- AND the wave-1 heuristic accessibility findings remain available
- @e2e exclude probe/degradation branch — covered by PHPUnit (tests/unit/Service/VeraPdfServiceTest.php)

### Requirement: A persisted accessibility conformance report is stored per document and re-runnable (REQ-DDPUM-002)

`POST /api/validation/accessibility/{fileId}` MUST run PDF/UA validation for a
file resolved through the requesting user's folder (404 when not resolvable,
without existence disclosure) and persist the result as an
`accessibilityConformanceReport` object via OpenRegister, keyed by `fileId`
(re-running updates the same object, no duplicates): `flavour` (`ua1`),
`compliant`, `failedCheckCount`, `failedChecks` (bounded list of
`{clause, testNumber, checkpoint}` references), `validatorVersion`,
`validatedAt`, `trigger` (`manual` | `validation` | `generation`). This report
MUST be SEPARATE from `verapdf-validation`'s PDF/A `conformanceReport` (a
document may be PDF/A conformant yet PDF/UA non-conformant; one shared object
would let each run clobber the other). The report MUST contain checkpoint and
clause references only — no document content or personal data (AVG Art.
5(1)(c)); it is accessibility-audit evidence. The document detail view MUST
surface the report (flavour, verdict, failed checkpoints, guidance).

#### Scenario: Accessibility conformance report is stored and shown

- GIVEN a readable PDF and an available validator
- WHEN the user runs the accessibility conformance check from the document detail view
- THEN an `accessibilityConformanceReport` exists for the file with verdict, flavour `ua1` and validator version
- AND the detail view shows the verdict with any failed checkpoints
- @e2e tests/e2e/spec-coverage/pdfua-verapdf-matterhorn.spec.ts

#### Scenario: PDF/A and PDF/UA reports coexist without clobbering

- GIVEN a file with an existing `conformanceReport` (PDF/A, verapdf-validation)
- WHEN PDF/UA validation runs and persists its report
- THEN the `accessibilityConformanceReport` is written as a distinct object
- AND the existing PDF/A `conformanceReport` is unchanged
- @e2e exclude dual-report persistence — covered by PHPUnit (tests/unit/Service/VeraPdfServiceTest.php)

#### Scenario: Inaccessible file yields 404

- GIVEN a fileId that does not resolve within the requesting user's folder
- WHEN the accessibility conformance endpoint is called
- THEN the response is HTTP 404 with a generic body
- @e2e exclude IDOR-safe resolution — covered by PHPUnit controller tests mirroring the ValidationController pattern

### Requirement: Remediation guidance is honest and never claims certification (REQ-DDPUM-003)

The accessibility conformance report and findings MUST attach remediation
guidance (i18n EN/NL) derived from the failure shape: a document produced by
Filinq's own tagged/accessible output path MUST advise regenerating through
Filinq with the accessible output option; an imported/uploaded PDF or an
untagged-mPDF-path output MUST advise re-authoring from an accessible source
and MUST state honestly that Filinq does not retag imported pages; per-check
failures MUST reference the Matterhorn clause/checkpoint. The report and UI
MUST describe a conformance verdict and MUST NOT claim PDF/UA certification.
The app MUST NOT auto-retag or otherwise auto-remediate imported pages in v1.

#### Scenario: Imported PDF failing Matterhorn gets honest guidance

- GIVEN a failed PDF/UA verdict on an uploaded PDF that Filinq did not generate
- WHEN the report renders
- THEN the guidance states Filinq does not retag imported pages and advises re-authoring from an accessible source
- AND the report does not claim the document is PDF/UA certified
- @e2e tests/e2e/spec-coverage/pdfua-verapdf-matterhorn.spec.ts

#### Scenario: Filinq-generated artifact advises regeneration

- GIVEN a PDF/UA failure on a document generated by Filinq's accessible output path
- WHEN the report renders
- THEN the guidance advises regenerating the document through Filinq with the accessible option
- @e2e exclude guidance-classification branch — covered by PHPUnit (tests/unit/Service/VeraPdfServiceTest.php)

### Requirement: Validator verdict is the authoritative conformance, heuristics remain the floor (REQ-DDPUM-004)

The app MUST surface the PDF/UA (Matterhorn) validator verdict as the
authoritative accessibility conformance state for a document whenever the
veraPDF validator is available and its accessibility checks are enabled in the
resolved profile. When the validator is unavailable, the wave-1 heuristic
accessibility findings (`pdfua-accessible-output` REQ-DDPUA-003) MUST remain
the accessibility signal and MUST be presented as heuristic ("looks tagged"),
never as a conformance verdict. Both heuristic and validator findings MUST remain visible with their
source labelled (heuristic vs validator-backed), consistent with how
`verapdf-validation` distinguishes `archival` from heuristic findings. A
heuristic pass together with a validator fail MUST surface as a fail. The
heuristic floor MUST NOT be removed or regressed by this change.

#### Scenario: Validator fail overrides a heuristic pass in the surfaced state

- GIVEN a PDF whose heuristic accessibility checks all pass but whose veraPDF `ua1` verdict is non-compliant, with validator checks enabled
- WHEN the operator views the document's accessibility conformance
- THEN the surfaced conformance state is non-conformant (the validator verdict)
- AND both the passing heuristic findings and the failing validator findings are visible with their source labelled
- @e2e tests/e2e/spec-coverage/pdfua-verapdf-matterhorn.spec.ts

#### Scenario: Without the validator, heuristics are labelled as heuristic

- GIVEN no veraPDF binary and a PDF that passes the wave-1 heuristics
- WHEN the operator views its accessibility state
- THEN the state is presented as a heuristic "looks tagged" result, not a PDF/UA conformance verdict
- @e2e exclude presentation-labelling branch — covered by Vitest on the accessibility panel state

## ADDED Requirements (amendment 2026-10-05, Woo rows 15.3 and 15.7, decision D5)

### Requirement: Every PDF attached to an OpenRegister object is checked for PDF/UA (REQ-DDPUM-006)

Filinq SHALL listen to `NodeCreatedEvent` and `NodeWrittenEvent`, and for a
file with MIME type `application/pdf` whose path lies under OpenRegister's
`Open Registers` root it SHALL add one `PdfUaAttachCheckJob` (a `QueuedJob`)
with the file id. The listener SHALL NOT read the file or run veraPDF on the
request that created it. The job SHALL run the PDF/UA (`ua1`) check through
`ConformanceService` with the trigger `attach`, store the
`accessibilityConformanceReport`, and set exactly one of the system tags
`pdfua-conform`, `pdfua-niet-conform` or `pdfua-niet-gecontroleerd` on the
file, removing the other two. A check that did not produce a verdict SHALL
tag `pdfua-niet-gecontroleerd`.

#### Scenario: a PDF attached to a publication in opencatalogi is checked
- GIVEN veraPDF available, and an officer who attaches `besluit.pdf` to a publication in opencatalogi
- WHEN the background job has run
- THEN filinq holds an accessibility conformance report for that file with trigger `attach`, and the publication's file list in opencatalogi shows the label `pdfua-conform` or `pdfua-niet-conform`
- e2e: `tests/e2e/spec-coverage/pdfua-verapdf-matterhorn.spec.ts`

#### Scenario: the upload does no validation work
- GIVEN a PDF created under `Open Registers/`
- WHEN the listener handles the real `NodeCreatedEvent`
- THEN one `PdfUaAttachCheckJob` is added and `ConformanceService` is not called
- @e2e exclude a listener side effect; covered by PHPUnit `PdfUaAttachListenerTest::testTheListenerOnlyQueues`

#### Scenario: a file outside OpenRegister is left to the existing paths
- GIVEN a PDF created in a user's own Documents folder
- WHEN the listener handles the event
- THEN nothing is queued
- @e2e exclude a path filter; covered by PHPUnit `PdfUaAttachListenerTest::testAFileOutsideOpenRegisterIsIgnored`

#### Scenario: no validator means no claim
- GIVEN veraPDF not installed
- WHEN the job runs on an attached PDF
- THEN the file is tagged `pdfua-niet-gecontroleerd` and no compliant report is stored
- @e2e exclude a degradation path; covered by PHPUnit `PdfUaAttachCheckJobTest::testNoValidatorTagsNotChecked`

### Requirement: Publication readiness counts a PDF/UA verdict that did not pass (REQ-DDPUM-007)

`PublicationReadiness::evaluate()` SHALL add a readiness reason naming the
file for every PDF of the record whose latest accessibility conformance report
is not compliant or is missing. The reason SHALL be a warning while the
profile severity of `pdfua-conformance-failed` is below `blocking`, and SHALL
make the record not ready when it is `blocking`. A missing report SHALL count
as not passed.

#### Scenario: a failing PDF warns before hand-off
- GIVEN a Woo record whose PDF is tagged `pdfua-niet-conform` and the default severity
- WHEN readiness is evaluated
- THEN `readinessReasons` names the file and the PDF/UA failure, and the hand-off shows the warning
- @e2e exclude a readiness computation; covered by PHPUnit `PublicationReadinessTest::testAFailedPdfUaVerdictIsAReason`

#### Scenario: an administrator makes it block
- GIVEN the profile severity of `pdfua-conformance-failed` set to `blocking`
- WHEN readiness is evaluated for a record with a failing or unchecked PDF
- THEN the record is not ready and cannot be handed off
- @e2e exclude a readiness computation; covered by PHPUnit `PublicationReadinessTest::testABlockingSeverityStopsTheHandOff`

### Requirement: A document filinq generated can be regenerated as PDF/UA, an imported one cannot (REQ-DDPUM-008)

For a PDF whose provenance says filinq generated it from a template, filinq
SHALL offer an action that regenerates it through the tagged output path,
validates the new copy with `ua1`, and stores it as a new version of the file
only when it is compliant. A copy that is not compliant SHALL be discarded and
the original kept, with the verdict shown. For any other PDF the action SHALL
NOT be offered, and REQ-DDPUM-003's guidance applies unchanged (decision D5).

#### Scenario: a generated decision letter becomes accessible
- GIVEN a decision letter filinq generated from a template, tagged `pdfua-niet-conform`
- WHEN the officer chooses to make it accessible
- THEN a new version is stored only after the `ua1` check passes, and the file is tagged `pdfua-conform`
- @e2e exclude needs the veraPDF binary; covered by PHPUnit `AccessibleRegenerationServiceTest::testACompliantCopyReplacesTheOriginal` and one live run recorded in the PR

#### Scenario: an imported PDF is never converted
- GIVEN an uploaded PDF filinq did not generate, tagged `pdfua-niet-conform`
- WHEN the officer opens its conformance card
- THEN no regenerate action is offered, and the guidance says filinq does not retag imported pages
- e2e: `tests/e2e/spec-coverage/pdfua-verapdf-matterhorn.spec.ts`

#### Scenario: a regenerated copy that still fails is discarded
- GIVEN a regeneration whose new copy fails `ua1`
- WHEN the action completes
- THEN the original file and its version are unchanged and the failed verdict is shown
- @e2e exclude a failure path; covered by PHPUnit `AccessibleRegenerationServiceTest::testAFailingCopyIsDiscarded`

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

# anonymization Specification (delta)

---
status: proposed
---

## Purpose

Wire sanitisation into the anonymisation pipeline: anonymisation runs persist
and surface the sanitization report OpenRegister already computes (and
DocuDesk currently discards), and the anonymise endpoint gains an additive
opt-in `sanitize` flag that applies the outbound sanitization pass to the
FINAL artifact. Existing requirements (REQ-ANON-00..10 and the
outputFormat/appendBasisSummary requirements) are unchanged; this delta only
ADDs requirements.

## ADDED Requirements

### Requirement: Anonymisation runs surface the OpenRegister sanitization report (REQ-DDSAN-006)

The anonymise path MUST, after an office-document anonymisation run, read
OpenRegister's run report via
`DocumentProcessingHandler::getLastSanitizationReport()` (exposed through
`FileService`) and persist it as a `sanitizationRecord` with `trigger:
"anonymisation"`, surfaced alongside the run result. At HEAD the office
sanitiser runs unconditionally inside OR anonymisation and its report is
computed then discarded by DocuDesk (zero callers — the orphaned-capability
class); this requirement consumes it without changing anonymisation
behaviour. Runs of non-sanitisable formats (plain text, PDF-text
replacement) produce no office report and MUST NOT fabricate one.

#### Scenario: Office anonymisation run persists its sanitization report

- GIVEN a DOCX with comments and track changes entering anonymisation
- WHEN the run completes
- THEN a `sanitizationRecord` with `trigger: "anonymisation"` exists carrying OR's category counts for the run
- AND the anonymisation result UI shows what the sanitiser removed
- @e2e tests/e2e/spec-coverage/document-sanitization.spec.ts

#### Scenario: Text-file run fabricates no report

- GIVEN a plain-text file entering anonymisation
- WHEN the run completes
- THEN no `sanitizationRecord` is created for the run
- @e2e exclude no-op branch — covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)

### Requirement: The anonymise endpoint MUST accept an optional `sanitize` flag for the final artifact (REQ-DDSAN-007)

The anonymise endpoint (single and batch) MUST accept an additive boolean
`sanitize` (default `false`; tenant default via app config
`docudesk.sanitization.default`). When true, the outbound sanitization pass
(`document-sanitization` REQ-DDSAN-001/002) MUST run on the FINAL artifact —
after the `outputFormat` conversion gate and any grondslagen-summary append —
because PDF conversion mints fresh metadata the source-side sanitisation
never saw. When false or absent, behaviour MUST be byte-identical to
pre-change output (additive and non-breaking, same contract style as
`outputFormat`). A sanitize-pass failure MUST NOT discard the anonymised
file: it surfaces as a structured warning on the response with the
unsanitized artifact retained and flagged, mirroring the summary-append
failure contract.

#### Scenario: sanitize true cleans the converted output

- GIVEN a DOCX anonymised with `outputFormat: "pdf"` and `sanitize: true`
- WHEN the run completes
- THEN the delivered PDF has been sanitized AFTER conversion (no producer/source identity metadata, no hidden payload)
- AND a `sanitizationRecord` references the delivered artifact
- @e2e tests/e2e/spec-coverage/document-sanitization.spec.ts

#### Scenario: Omitted flag preserves pre-change behaviour

- GIVEN an anonymise call without `sanitize`
- WHEN the run completes
- THEN the output bytes match pre-change behaviour and no outbound pass runs
- @e2e exclude additive-compat contract — covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)

#### Scenario: Sanitize failure keeps the anonymised artifact

- GIVEN `sanitize: true` and a sanitizer failure on the final artifact
- WHEN the run completes
- THEN the anonymised (unsanitized) file is retained
- AND the response carries a structured sanitization warning, not an error discarding the run
- @e2e exclude failure-contract branch — covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)

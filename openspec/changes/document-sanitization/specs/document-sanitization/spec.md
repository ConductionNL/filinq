# document-sanitization Specification (delta)

---
status: proposed
---

## Purpose

Strip hidden metadata and active/hidden content (author identity, track
changes, comments, embedded objects, scripts, XMP, prior-save remnants) from
office files and PDFs before publication or disclosure, as an opt-in pass
producing a sanitized derivative plus a content-free report of what was
removed. Office sanitisation delegates to OpenRegister's existing
`OfficeDocumentSanitizer`; PDF sanitisation delegates to a new OR-side PDF
sanitizer seam. The pass wires into anonymisation output and the wave-1
publication/sealing flow (sanitized + sealed is the publication end state).

## ADDED Requirements

### Requirement: A document can be sanitized on demand into a clean derivative (REQ-DDSAN-001)

The app MUST provide a sanitization action (`POST /api/sanitization/{fileId}`)
that an authenticated user can run on an office file or PDF they can access,
producing a sanitized derivative file beside the source (never a silent
in-place rewrite of the source). The file MUST be resolved through the
requesting user's folder (404 when not resolvable, without existence
disclosure). Office files MUST be sanitized via OpenRegister's
`OfficeDocumentSanitizer::sanitize()`; PDFs via the OpenRegister PDF
sanitizer seam. Encrypted documents MUST fail closed with a structured,
caller-correctable error (HTTP 422), mirroring the engine's
`REASON_ENCRYPTED` contract. While the PDF seam is not available at runtime,
a PDF sanitization request MUST return a fail-flagged
`sanitizationSkipped` result with reason `pdf_sanitizer_unavailable` —
never a success claim.

#### Scenario: Office document is sanitized into a derivative

- GIVEN a DOCX with comments, track changes and author metadata
- WHEN the user runs the sanitize action
- THEN a sanitized derivative appears beside the source
- AND the source file's bytes are unchanged
- @e2e tests/e2e/spec-coverage/document-sanitization.spec.ts

#### Scenario: Inaccessible file yields 404

- GIVEN a fileId that does not resolve within the requesting user's folder
- WHEN the sanitize endpoint is called
- THEN the response is HTTP 404 with a generic body
- @e2e exclude IDOR-safe resolution — covered by PHPUnit (tests/unit/Controller/SanitizationControllerTest.php) mirroring the ValidationController pattern

#### Scenario: PDF request degrades fail-flagged without the OR seam

- GIVEN an OpenRegister version without the standalone PDF sanitizer
- WHEN the user sanitizes a PDF
- THEN the response carries `sanitizationSkipped` with reason `pdf_sanitizer_unavailable`
- AND no record or UI state claims the PDF was sanitized
- @e2e exclude version-skew degradation branch — covered by PHPUnit (tests/unit/Service/DocumentSanitizationServiceTest.php)

### Requirement: PDF sanitization removes hidden payload through a full re-save (REQ-DDSAN-002)

The PDF sanitization pass MUST remove: /Info identity fields and XMP
identity namespaces (the field/namespace scope of OR's existing
`PdfMetadataSanitizer`), comments/annotations, embedded files, JavaScript
(`/JavaScript` name tree, `/JS` actions), `/OpenAction` and `/AA` entries —
and MUST emit the result as a full re-serialisation so prior-save incremental
updates and orphaned objects are absent from the output. The pass MUST NOT
alter visible page content. Removal means removal: no masked,
emptied-but-present, or overlay constructs.

The pass MUST make one exception (decision D2): a PDF/A-3 embedded file
declared with an `/AFRelationship` of `Source` or `Data` is the archival
payload of the container (the MDTO/machine-readable sidecar that makes the
PDF/A-3 a valid archival record) and MUST be PRESERVED, together with the
`/AF` association that binds it to the document. Every other embedded file —
including PDF/A-3 attachments with any other `/AFRelationship`
(`Alternative`, `Supplement`, `Unspecified`) and all non-PDF/A-3 embedded
files — MUST be stripped. Each preserved attachment MUST be listed in the
sanitization report as preserved (with its relationship), so preservation is
accountable and never silent.

#### Scenario: Hidden payload is absent from the sanitized PDF

- GIVEN a PDF with an author Info entry, a comment annotation, an embedded file and an OpenAction script
- WHEN PDF sanitization runs
- THEN the output contains none of: the author value, the annotation object, the embedded file, the OpenAction entry
- AND the output is a single full save without incremental-update remnants
- @e2e exclude byte-level output assertions — covered by PHPUnit against fixture PDFs (tests/unit/Service/DocumentSanitizationServiceTest.php)

#### Scenario: Declared PDF/A-3 archival attachment is preserved

- GIVEN a PDF/A-3 file with an MDTO sidecar attachment declared `/AFRelationship /Source`
- WHEN PDF sanitization runs
- THEN the sidecar attachment and its `/AF` association are preserved in the output
- AND the report lists it as a preserved attachment with relationship `Source`
- @e2e exclude PDF/A-3 attachment-preservation branch — covered by PHPUnit fixtures shared with Pdfa3ConversionServiceTest

#### Scenario: Non-archival embedded files are stripped

- GIVEN a PDF/A-3 file carrying both a `/Source` MDTO sidecar and a separate embedded spreadsheet with `/AFRelationship /Unspecified`, plus a plain (non-PDF/A-3) embedded attachment
- WHEN PDF sanitization runs
- THEN the `/Source` sidecar is preserved
- AND both the `/Unspecified` attachment and the plain embedded attachment are removed from the output
- AND the report records the removed attachments as an embedded-files count
- @e2e exclude embedded-file strip branch — covered by PHPUnit fixtures shared with Pdfa3ConversionServiceTest

### Requirement: Every sanitization run persists a content-free report (REQ-DDSAN-003)

Every sanitization run MUST persist a `sanitizationRecord` object via
OpenRegister carrying `fileId`, `sanitizedFileId`, `trigger` (`manual` |
`anonymisation` | `publication`), `engine`, `sanitizedAt`, `sanitizedBy`,
and a `report` object of category counts (for office runs: OpenRegister's
`SanitizationReport` serialisation verbatim; for PDF runs: the PDF category
counts incl. `resaved`). The record MUST NOT contain removed content, author
names, comment text, or any other recovered value (AVG Art. 5(1)(c) data
minimisation; the record is Art. 5(2) accountability evidence). The UI MUST
show the report per document (what was removed, per category, with counts).

#### Scenario: Report shows category counts, not content

- GIVEN a sanitized DOCX that had 4 comments and 6 metadata fields
- WHEN the operator opens the sanitization report
- THEN it shows `commentsRemoved: 4` and `metadataFieldsScrubbed: 6` per category
- AND no comment text or author value appears anywhere in the record or UI
- @e2e tests/e2e/spec-coverage/document-sanitization.spec.ts

#### Scenario: Report shape is pinned against OpenRegister

- GIVEN OpenRegister's `SanitizationReport::jsonSerialize()` field set at HEAD
- WHEN the unit suite runs
- THEN a drift test fails if the persisted office `report` field set no longer matches OR's serialisation
- @e2e exclude register/contract drift pin — covered by PHPUnit (tests/unit/Service/DocumentSanitizationServiceTest.php)

### Requirement: Publication hand-off consults the sanitized signal (REQ-DDSAN-004)

The app MUST compute a per-artifact sanitized signal — true when a
`sanitizationRecord` exists whose `sanitizedFileId` matches the artifact
being handed off — and the Woo publication hand-off surface MUST warn before
handing off an artifact whose signal is false. The warning MUST NOT
hard-block: gating stays with the publication record's readiness lifecycle
(`woo-publicatie-pipeline` REQ-DDWPP-003, referenced not modified), and the
signal is computed DocuDesk-side (sibling boundary: OpenCatalogi owns
publication endpoints).

#### Scenario: Unsanitized hand-off warns

- GIVEN a publication-ready document with no matching `sanitizationRecord`
- WHEN the operator opens the Woo hand-off action
- THEN a warning states the artifact was not sanitized, with a link to the sanitize action
- AND the operator can still proceed
- @e2e tests/e2e/spec-coverage/document-sanitization.spec.ts

#### Scenario: Sanitized artifact hands off without the warning

- GIVEN a document whose hand-off artifact matches a `sanitizationRecord.sanitizedFileId`
- WHEN the operator opens the hand-off action
- THEN no sanitization warning is shown
- @e2e exclude signal-computation branch — covered by PHPUnit (tests/unit/Service/DocumentSanitizationServiceTest.php)

### Requirement: Sanitize-then-seal ordering is enforced on the action surface (REQ-DDSAN-005)

The sanitize and seal actions MUST enforce the sanitize→seal order for the
publication end state (sanitized + sealed): running sanitization on an
artifact that carries an active waarmerk (`document-waarmerk-certification`
REQ-DDWMK-002, referenced not modified) MUST warn that the derivative will
be unsealed and requires re-sealing, and the seal action MUST surface the
sanitized signal so the operator seals the clean artifact. Neither action is
blocked — the ordering is guided, and the seal's own verification remains
the cryptographic truth (a sanitized copy of a sealed file simply carries no
valid seal).

#### Scenario: Sanitizing a sealed document warns about the seal

- GIVEN a PDF with an active waarmerk
- WHEN the user starts the sanitize action on it
- THEN a warning explains the derivative will not carry the seal and must be re-sealed
- @e2e tests/e2e/spec-coverage/document-sanitization.spec.ts

#### Scenario: Seal action shows the sanitized state

- GIVEN an unsanitized PDF
- WHEN the user opens the waarmerk seal action
- THEN the dialog shows that the artifact is not sanitized, with a link to sanitize first
- @e2e exclude dialog-state wiring — covered by Vitest component tests on the seal dialog state

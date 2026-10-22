# scan-intake Specification (delta)

---
status: proposed
---

## Purpose

A scanner delivers one PDF per batch. Filinq cuts the batch at separator
sheets it printed itself and hands each document to the intake inbox
(`document-intake-inbox`). Requested by the dossiq competitor analysis,
register row 1.6, round 2 finding C08.

## ADDED Requirements

### Requirement: A scanned batch is an object with a lifecycle (REQ-SCI-01)

Filinq MUST declare a `scanBatch` schema in the `document` register with
`file`, `scannerId`, `receivedAt`, `separatorMode` (enum `qr`,
`blankPage`), `segments[]`, `lastError` and a declarative
`x-openregister-lifecycle` on `status` with initial `received` and
terminal states `split` and `failed`.

#### Scenario: A file in a watched folder becomes a batch

- GIVEN a scanner profile with a watched folder
- WHEN a PDF lands in that folder
- THEN one `scanBatch` exists in `received` with `scannerId` set to the profile id and `file` pointing at the PDF
- @e2e exclude folder watch runs as a background job; covered by PHPUnit on `ScanBatchService::receive()`

### Requirement: Separator sheets are printed by filinq and read by filinq (REQ-SCI-02)

Filinq MUST offer a print action that renders separator sheets, each
carrying a QR code with the payload `filinq:sep:v1:<profileId>` and, when
a case number is given, `:<caseNumber>`. The splitter MUST recognise
exactly that payload in `qr` mode and a page under the profile's ink
threshold in `blankPage` mode. The splitter MUST NOT call any external
service.

#### Scenario: A clerk prints separators for three cases

- GIVEN a clerk pastes three case numbers into the print dialog
- WHEN the clerk prints
- THEN one PDF with three pages is produced, each page carrying a QR code whose payload ends in one of the three numbers
- e2e: `tests/e2e/scan-separators.spec.ts`

#### Scenario: A blank page separates in blankPage mode

- GIVEN a profile in `blankPage` mode and a batch of five pages whose third page is blank
- WHEN the batch is split
- THEN two segments exist, pages 1 to 2 and pages 4 to 5, and the blank page belongs to neither
- @e2e exclude page-raster analysis; covered by PHPUnit with a fixture batch

### Requirement: Every segment reaches the inbox through the intake event (REQ-SCI-03)

For each segment the splitter MUST write one PDF and dispatch one
`IntakeDocumentReceivedEvent` with channel `scan`, `sender` set to the
scanner id and `sourceRef` set to the separator's case number when it
carried one. The splitter MUST NOT assign a document to any object. After
the last segment the batch MUST be `split` with `segments[]` naming every
created `intakeDocument`.

#### Scenario: A batch with two QR separators yields three intake documents

- GIVEN a `qr` profile and a batch of nine pages with separators on pages 4 and 7, the second naming case `2026-0042`
- WHEN the batch is split
- THEN three `intakeDocument` objects exist in `received`, the third with `sourceRef = 2026-0042`, none with `assignedTo`, and the batch is `split`
- @e2e exclude cross-service split and event fan-out; covered by PHPUnit on `ScanBatchService::split()` with a fixture batch

#### Scenario: A batch without separators is one document

- GIVEN a batch of four pages and no separator
- WHEN the batch is split
- THEN one `intakeDocument` with all four pages exists and the batch is `split`
- @e2e exclude covered by the same PHPUnit fixture suite

### Requirement: The inbox pre-fills the assign target from the separator (REQ-SCI-04)

When an `intakeDocument` carries a `sourceRef` that matches a case number
pattern, the assign dialog of the intake page and of the
`filinq-document-intake` leaf MUST pre-fill its object picker with that
number. The write check of REQ-DII-03 MUST still run on assign.

#### Scenario: The clerk sees the case the separator named

- GIVEN an `intakeDocument` with `sourceRef = 2026-0042` and a case with that identifier the clerk may write
- WHEN the clerk opens the assign dialog
- THEN the picker shows that case selected, and confirming assigns the document to it
- e2e: `tests/e2e/intake-inbox.spec.ts`

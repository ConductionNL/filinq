---
kind: code
depends_on: [document-intake-inbox]
---

# Proposal: scan-intake-with-separator-sheets

Competitor gap register, row 1.6 "Watched folder or scan import with
separator sheets" (`procest/_gaps/gap-register.md` in
ConductionNL/market-intelligence, 2026-09-13). Rated no, owner filinq,
size M. Opened by the small-owner lane of the OpenSpec phase.

## Why

A municipal scanner produces one PDF per batch, not one per document.
Someone has to cut the batch where one document ends and the next begins.
Today filinq's OCR folder watch (`FolderExtractionJob`, spec
`ocr-document-scanning`) reads a file as one document, and
`document-intake-inbox` receives it as one `intakeDocument`. Nothing
recognises a separator sheet, so a batch of forty letters lands in the
inbox as one letter.

The best competitor in the register: OpenCase,
`lib/Service/SeparationSheetService.php`
(`_round2/compare/M1-functionality.md`). Round 2 C08 put paper intake with
filinq, if filinq wants it. The intake inbox now exists, so the batch split
has a place to deliver to.

## What changes

- A `scanBatch` object in the `document` register: the batch file, the
  scanner id, the split result and its status.
- A watched intake folder per scanner. A file that lands there becomes a
  `scanBatch` and is split.
- A separator sheet: a page carrying a QR code that filinq printed, or a
  blank page when the profile says so. The QR payload names the scanner
  profile and, optionally, the case number the clerk wrote it for.
- Each segment becomes one `intakeDocument` with channel `scan` through the
  existing `IntakeDocumentReceivedEvent`. A segment whose separator named a
  case number carries that number as `sourceRef`, so the inbox can offer
  the assignment first; the feeder still never assigns (REQ-DII-02).
- A print action that produces separator sheets: one blank, or one per
  case number the clerk pastes in.

## How dossiq consumes it

Nothing new. The register's dossiq half for this row is "none; the assign
target of 1.4". The split documents reach dossiq the way any intake document
does: the clerk assigns them in the inbox leaf `filinq-document-intake`
(REQ-DII-05), and dossiq picks up the link through its
`zaakinformatieobject` relation.

## ADRs

- ADR-001 and ADR-070: the batch and its result are OpenRegister objects.
- ADR-031: the batch lifecycle is declared on the schema, not in PHP.
- ADR-041 and ADR-066: the feeder dispatches the intake event; no verb
  crosses into dossiq.
- Filinq's own rule: all processing stays local. QR detection runs on the
  server with no external call.

## Existing specs it extends

`ocr-document-scanning` (the folder watch becomes the batch feeder) and the
delta `document-intake-inbox` (REQ-DII-02, the event every feeder uses).

## Out of scope

- Assigning a document to a case from the scanner. The separator only
  carries the number; a clerk confirms in the inbox.
- Barcode formats other than QR. One format, printed by filinq, read by
  filinq.
- OCR of the segments. The existing OCR path runs on each `intakeDocument`
  as it does today.

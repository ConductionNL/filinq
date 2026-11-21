# Design: scan-intake-with-separator-sheets

Kind: code. One schema, one feeder, one splitter, one print action.

## Context

`FolderExtractionJob` already walks a folder and runs extraction per file.
`GrondslagenPdfWriter::mergeSummaryIntoPdf()` already reads and writes
pages with `setasign/fpdi`, so page-level cutting needs no new dependency.
`IntakeService::receive()` (document-intake-inbox, D2) is the only door
into the inbox. The new work is the recognition of a separator page and
the bookkeeping around a batch.

## D1. `scanBatch`

In the `document` register, English names (dossiq decision D13).

| property | type | notes |
|---|---|---|
| `file` | file reference | the batch PDF as scanned |
| `scannerId` | string | the watched folder's profile id |
| `receivedAt` | date-time | set by the watch |
| `separatorMode` | enum `qr`, `blankPage` | from the scanner profile |
| `segments[]` | array of `{pages: [from, to], intakeDocumentRef, caseNumber}` | written by the splitter |
| `status` | lifecycle `received`, `split`, `failed` | `x-openregister-lifecycle`, initial `received` |
| `lastError` | string | set on `failed` |

`hardValidation: true`. `split` and `failed` are terminal. A batch that
holds no separator at all becomes one segment; that is not an error.

## D2. Scanner profiles

An admin setting `scanProfiles[]`, each `{id, folder, separatorMode}`,
stored through the existing settings plane. One watched folder per
profile. The watch reuses `FolderExtractionJob`'s walk and hands every new
PDF to `ScanBatchService::receive()` instead of to extraction.

## D3. The separator sheet

`SeparatorSheetService::print(caseNumbers[])` renders one page per number,
plus one blank separator when the list is empty, through `PdfService`. Each
page carries a QR code with the payload
`filinq:sep:v1:<profileId>[:<caseNumber>]` and the same text in print, so a
clerk can read what the scanner will read. No third-party QR service: the
code is drawn locally.

## D4. The splitter

`ScanBatchService::split(batch)`:

1. Read the batch with FPDI, page by page.
2. For each page, detect a separator: in `qr` mode, decode a QR on the page
   image (local decoder over the page raster); in `blankPage` mode, treat
   a page whose ink coverage is under the profile threshold as a separator.
3. Cut the page list at each separator. The separator page itself is
   dropped.
4. For each segment, write a new PDF, store it in the intake folder, and
   dispatch `IntakeDocumentReceivedEvent` with channel `scan`, the file,
   `sender` = the scanner id and `sourceRef` = the case number from the
   separator when present.
5. Record the segments on the batch and transition it to `split`.

A decoding failure on one page does not fail the batch: the page stays in
the current segment and the batch records a warning per page.

## D5. The inbox offer

The inbox already lists `sourceRef`. When it looks like a case number the
assign dialog pre-fills the object picker with it. That is a filter on the
picker, not an assignment; REQ-DII-03's write check still runs.

## Risks

- A separator sheet scanned upside down. QR decoding is rotation
  invariant; the blank-page mode does not care.
- A clerk forgets the last separator. The trailing pages become the last
  segment, which is what the clerk expects.
- Personal data in a batch that fails to split. The batch object follows
  the document register's retention, like a rejected intake document.

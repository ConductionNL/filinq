# Review Workbench

## Overview

The review workbench (`/review/:fileId`) is where a person decides, document by
document, what is anonymised before anything is committed. It shows the
original beside the anonymised result, the findings as one decision panel, and
a per-document "reviewed" mark that the anonymise commit checks.

Screenshots of the split view, the badges, the grondslag pickers and the
checked gate follow with the Playwright capture run (task 4.5).

## The review rendition

Detection and review run on **one PDF per source file**, the review
rendition, so the reviewer judges exactly the pages detection read:

| Source | Rendition | Text layer |
| --- | --- | --- |
| A PDF with its own text | the PDF itself (`native-pdf`) | `native` |
| An office, e-mail or HTML file | a PDF converted through the conversion cascade (`converted`); the backend that made it is recorded | `native` |
| A scan or an image | the scan, with the text recognised by OCR (`ocr-text-layer`) | `ocr`, or `none` without OCR |

The rendition is prepared when detection runs, stored as a `reviewRendition`
object (one per source, replaced when it is prepared again) and copied onto
the file's `anonymizationLink` as `reviewRendition` when the anonymisation is
committed: `fileId`, `sha256`, `kind`, `backend`, `textLayer` and
`preparedAt`. A converted rendition's text is what detection reads, so every
finding sits on the pages the reviewer sees; the workbench shows the
converted PDF as the original and says how it was made.

A converted rendition is written next to its source, with the source's name
and a `.pdf` extension, and is reused while it is newer than the source. A PDF
of that name that is not the rendition is never overwritten: no rendition is
made then, and detection runs on the source as before.

### A check holds for one rendition

The reviewed mark stores the rendition's `sha256`. When the rendition is
prepared again with a different `sha256`, the mark no longer holds: the
workbench asks for a new review, and the anonymise commit is refused through
the checked gate, on the single-file and the batch path alike.

### No text layer is not a clean page

A scan without OCR has text layer `none`: detection could not read it and so
found nothing on it. The workbench says so, and "Mark as reviewed" needs the
reviewer to confirm that every page was read by eye. The mark records that
confirmation (`readByEye`).

## Certain and uncertain findings

Two confidence numbers decide what the reviewer sees, and they are set in two
places on purpose:

- **The detection floor** is OpenRegister's `anonymisation.confidenceThreshold`
  (file settings). Below it, OpenRegister reports nothing at all. It is
  OpenRegister's setting because every app that detects through OpenRegister
  shares it. When an OpenRegister does not expose it, the workbench shows the
  default 0.5 and says it is not configurable there.
- **Certain from** is filinq's `filinq.review.certain_from` (default 0.85, in
  the admin settings). A detected finding below it is **uncertain**, at or
  above it **certain**; a finding added by hand is always certain. It is
  filinq's setting because it is a review policy, not a detection one. Saving
  a value below the floor is refused, naming the floor.

The panel reads, for example, "Found from 0.6 (OpenRegister), certain from 0.9
(filinq)". Each finding carries a certain or uncertain badge, a filter shows
only the uncertain ones, and a counter says how many there are.

An uncertain finding is pre-selected for anonymisation. The document can only
be marked reviewed once every uncertain finding has an explicit decision: the
reviewer includes or skips it, or confirms the pre-selection. A mark asked for
while one is undecided is refused, and the refusal names the findings.

## API

| Method | Path | What |
| --- | --- | --- |
| GET | `/api/review/{fileId}` | The review state: `checked`, `stale`, the mark, the gate mode, the `rendition`, the `thresholds` and the uncertain findings without a decision |
| POST | `/api/review/{fileId}/check` | Mark reviewed; `byEye` confirms reading by eye, `decided` lists the relation ids of the uncertain findings the reviewer decided. 409 with `reason` `not_extracted`, `no_text_layer` or `uncertain_undecided` (with `undecidedFindings`) |
| DELETE | `/api/review/{fileId}/check` | Remove the mark |

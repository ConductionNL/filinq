---
kind: code
depends_on: []
---

# Proposal: work-stamp-text-on-every-page

Half handed to filinq by decidiq `agenda-paper-watermark` (matrix row
`age-19` in decidiq's `openspec/parity/capabilities.json`, owned by
decidiq). Written in the owner-moves pass of 28 September 2026.

## Why

A council member who leaks a confidential paper should be named on every
page of it. decidiq's change `agenda-paper-watermark`, merged on decidiq
`development`, serves such a paper only stamped with the reader's name, the
date and a text such as "Vertrouwelijk", and it refuses to serve the paper
at all while the stamping is missing. The stamping is filinq's.

decidiq `agenda-paper-watermark`, Risks: "filinq does not yet expose a
stamping method (checked at filinq `7733074`: `PdfService`,
`Pdfa3ConversionService` and `MergedPdfDocument` import pages with FPDI and
mPDF, and none takes a watermark text). The filinq half is a small method on
the page-import loop it already has; this proposal names it as filinq's to
build, and the decidiq tasks wait on it." Its design: "filinq: a method that
takes a PDF and a text and returns the PDF with the text stamped on every
page."

Row `age-19`, "Watermark meeting papers on screen and in exports with the
viewer's name and the date." Diligent Boards rates yes: "watermarks show the
viewer's name and view date on the page view and on exported documents, with
optional custom text"
(https://help.diligentoneplatform.com/helpdocs/boards/en-us/Content/boards-web-admin/books/watermarking-documents-bwa.htm).

By the owner-moves rule a half that another product's merged change depends
on is built. filinq's own row `wk-watermark` ("Put a watermark on a document
before it is shared") was deferred on 27 September for want of demand; this
change builds the stamping step that row would also use, not its sharing
screen.

## What changes

- A stamping service that imports every page of a PDF and puts a text on
  each: across the page, at its foot, or both.
- A typed command, `DocumentStampRequestedEvent`, that a sibling app
  dispatches with a PDF and the text and reads the stamped PDF back from.
  ADR-041 names typed events, not cross-container service resolution, as
  the way one app asks another to act, so decidiq's `FilinqStamp` dispatches
  this event instead of resolving a filinq service.
- A refusal the caller can show when a file is not a PDF, is encrypted, or
  is larger than the configured limit.

## Out of scope

- Deciding when a paper is stamped and caching the stamped copy (decidiq).
- A "watermark before sharing" action in filinq's own screens (row
  `wk-watermark`, still deferred).
- Stamping Office files. The caller converts first.

## Impact

- New `lib/Service/PdfStampService.php`, `lib/Event/DocumentStampRequestedEvent.php`,
  `lib/EventListener/DocumentStampRequestedListener.php`; registration in
  `lib/AppInfo/Application.php`.

## Cross-app dependencies

- decidiq `agenda-paper-watermark`: `lib/Support/FilinqStamp.php` dispatches
  the event with the reader's display name, the date and time and the
  meeting's watermark text, and answers 503 when the event is not handled.

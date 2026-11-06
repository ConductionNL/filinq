# Design: work-stamp-text-on-every-page

Read at filinq development `9fd042f8` and decidiq development on
2026-09-28.

## Context

- `Pdfa3ConversionService` already loops over every page of a PDF with
  mPDF's FPDI import: `setSourceFile()` (`lib/Service/Pdfa3ConversionService.php:453`),
  then `importPage()` and `useTemplate()` per page (:479-480). mPDF is a
  dependency (`composer.json:71`, `mpdf/mpdf ^8.3`).
- `PdfService::createMpdfInstance()` (`lib/Service/PdfService.php:424`)
  builds a configured mPDF instance with filinq's fonts.
- filinq's typed events for sibling apps: `DocumentSigningRequestedEvent`
  (`lib/Event/DocumentSigningRequestedEvent.php`) carries its inputs and a
  result slot (`setHandled()`, `setSigningRequestId()`), and its listener
  catches every failure so the dispatching app never sees an exception.
- decidiq `agenda-paper-watermark` design: `PaperWatermarkPolicy` decides,
  `FilinqStamp` asks filinq, `PaperController` answers 503 with "This paper
  can only be shown with a watermark, and the watermark service is not
  installed" when filinq cannot stamp.

## Decisions

### D1. One service on the page loop filinq already has

`PdfStampService::stamp(string $pdf, string $text, array $options): string`
imports each page with `useTemplate(adjustPageSize: true)` so every page
keeps its size and orientation, and writes the text on it. `placement` is
`diagonal` (mPDF watermark text across the page, light grey, 15 percent
opacity by default), `footer` (one line in 8 pt at the bottom margin), or
`both`. Text is plain, escaped, at most 200 characters, and may hold several
lines separated by a newline. The result is a new PDF; the input is never
changed.

Alternative considered: stamp only the first page, like the received stamp
of `intake-acknowledge-and-stamp`. Rejected: a leaked page must carry the
name on its own.

### D2. A typed command for sibling apps

`DocumentStampRequestedEvent(sourceApp, pdfContent, text, placement =
'both', correlationId = '')` with a result slot: `setStampedPdf()`,
`setHandled()`, `setRefusal(code, reason)`. Codes: `not-a-pdf`,
`encrypted`, `too-large` (default limit 50 MB, an app setting), `failed`.
The listener runs the service and never throws to the caller.

Alternative considered: a file id in and a file id out. Rejected: the
stamped copy is personal to one reader and must not become a file in anyone's
Files; decidiq keeps it in its own cache.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| stamping a PDF | imperative service | a file operation (document generation class) |
| asking filinq to stamp | typed event, ADR-041 | a cross-app command with a result |

## Seed data

None. A test fixture: a three-page PDF with one landscape page.

## Risks

- A large paper is slow to stamp. The limit refuses what is too large with a
  reason, and decidiq caches the stamped copy per reader and version.
- An encrypted PDF cannot be imported. It is refused with `encrypted`, never
  served unstamped.

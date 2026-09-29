---
kind: code
depends_on: []
---

# Proposal: intake-ocr-on-arrival

Matrix row `in-ocr-auto` in filinq's `openspec/parity/capabilities.json`,
rated no, built.state none. Decided `build` in the build-all pass of
28 September 2026: the row sits in intake, filinq's core area.

## Why

A scanned letter reaches the intake inbox as an image or a PDF without a
text layer. Nobody can search it, the classifier has nothing to read, and
the worklist cannot tell a clerk whether the text is still coming or will
never come.

The parts exist and none of them are joined:

- `OcrService::processFile()` (`lib/Service/OcrService.php:443`) runs
  Tesseract on one file. It has no caller on the intake path.
- `IntakeReadingProgress` (`lib/Service/Intake/IntakeReadingProgress.php`)
  defines the reading states `queued`, `reading`, `read` and `failed`, and
  the `intakeDocument` schema carries `readingState`, `readingError` and
  `readingUpdatedAt`. Nothing in `lib/` writes them.
- `inbound-documents-and-the-worklist` task 4.2 hands the OCR step to the
  scan feeder, and `scan-intake-with-separator-sheets` puts OCR of the
  segments out of its scope. Each change points at the other.

This change closes that loop: every document that arrives with a file
that needs OCR is read by a background job, without anyone starting it.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `in-ocr-auto` | Run OCR automatically when a file lands in a folder, without anyone starting it. | no: no job triggers OCR on arrival, `OcrService::processFile()` has no intake caller |

### Demand

No demand row. The row is in the core area (intake), which is the rule
that decides it.

### Competitors rated yes

- Paperless-ngx (source read at v3.2.1): "src/documents/management/commands/document_consumer.py:377
  watches the consume folder and queues consumption, which runs OCR with no
  user action (docs/usage.md:156)".

## What changes

- A document that arrives in the intake inbox with a file that needs OCR
  is queued for reading the moment `IntakeService::receive()` stores it.
  Every channel goes through that door, including the scanner folders of
  `scan-intake-with-separator-sheets`, so one trigger covers them all.
- A queued job runs `OcrService::processFile()`, writes the recognised
  text to the intake document's searchable content and moves
  `readingState` through `reading` to `read`, or to `failed` with the
  reason.
- The worklist shows the reading state per row, so a clerk sees "reading",
  "text recognised" or "text could not be read".
- An admin setting turns reading on arrival on or off. It is on by default
  and does nothing when OCR is off or Tesseract is missing, and then the
  document says so instead of waiting forever.
- `OcrService::processFile()` sends an image straight to the image path.
  Today it runs the PDF path first for every file.

## Capabilities

### New capabilities

- `intake-ocr-on-arrival`: read the text of an arriving document in the
  background, and say on the document how far that got.

### Modified capabilities

None. `ocr-trigger-surface` keeps the manual "Run OCR" action and the
anonymisation fallback; this change is the automatic intake half.

## Impact

- New `lib/BackgroundJob/IntakeOcrJob.php`.
- `lib/Service/IntakeService.php`: queue the job after a document with a
  file is stored.
- `lib/Service/OcrService.php`: the image branch fix.
- `lib/Settings/filinq_register.json`: `contentText` on `intakeDocument`.
- `src/views/intake/IntakeIndex.vue`: the reading state column.
- Admin settings: the `ocr_on_arrival` toggle next to the OCR settings.

## Out of scope

- Watching arbitrary Nextcloud folders outside intake. A file that should
  be read lands in the inbox first.
- A searchable PDF copy with a text layer. The text is kept on the record.
- Handwriting. Tesseract reads printed text only.

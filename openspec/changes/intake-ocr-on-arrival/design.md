# Design: intake-ocr-on-arrival

Kind: code. One job, one trigger, one column, one setting.

## Context

Read at development `38818eeb`.

- `lib/Service/IntakeService.php:95` `receive()` is the only door into the
  inbox. It stores the document through `IntakeRepository::save()` and
  returns it. `receiveMessage()` (:173) calls `receive()` per attachment.
- `lib/Service/OcrService.php:443` `processFile(int $fileId)` returns
  `{text, confidence, ocrProcessed}`. It returns an empty result, not an
  exception, when OCR is disabled (`isOcrEnabled()`, :201), Tesseract is
  missing (`isTesseractAvailable()`, :125), the file is not found, or the
  MIME type needs no OCR (`needsOcr()`, :179). For every candidate it runs
  `extractTextFromPdf()` first and only then, for an image, overwrites the
  result with `extractTextFromImage()` (:480-483), so an image is
  rasterised as a PDF before it is read.
- `lib/Service/Intake/IntakeReadingProgress.php` `progressFor(state, error,
  moment)` returns the `readingState`, `readingError` and
  `readingUpdatedAt` to store. No class in `lib/` calls it.
- `intakeDocument` (`lib/Settings/filinq_register.json`) has
  `readingState` (enum `queued`, `reading`, `read`, `failed`),
  `readingError` and `readingUpdatedAt`, and no property for the text.
- `lib/BackgroundJob/` holds `QueuedJob` classes such as
  `FolderExtractionJob`; `IJobList::add()` queues one.
- The OCR settings `ocr_enabled`, `ocr_languages` and `ocr_dpi` live in
  `SettingsService` (:209-225, allow-list :391).

## Goals / Non-goals

Goals: read every arriving document that needs OCR without a user action;
make the reading state visible and never stuck.

Non-goals: folder watches outside intake, searchable PDF output,
re-reading on demand (that is `ocr-trigger-surface`).

## Decisions

### D1. The trigger is the intake door, not a file listener

`receive()` queues `IntakeOcrJob` with `{uuid, fileId}` after it stores a
new document whose `file` is set and whose MIME type `needsOcr()`. A
Nextcloud `NodeCreatedEvent` listener was considered and rejected: it
fires on every file write on the instance, and it cannot tell an intake
file from any other. A document delivered twice keeps its first record
(the existing `findBySourceRef()` branch) and is not queued again.

### D2. The job drives the reading states

`IntakeOcrJob::run()` writes `reading`, calls `processFile()`, and then:

- text recognised: `contentText` holds the text, state `read`;
- OCR off or Tesseract missing: state `failed`, reason "Text recognition
  is off or not installed on this server";
- no text found: state `failed`, reason "No text was found in this
  document";
- an exception: state `failed` with the message.

Every write goes through `IntakeReadingProgress::progressFor()`, so the
state set stays in one place. A job that dies between `reading` and the
end leaves `reading`; the next run of the same job for that uuid starts
over, and `readingUpdatedAt` shows how long it has sat.

### D3. The text lives on the record

`contentText` (string) on `intakeDocument`. OpenRegister indexes object
properties, so the inbox search finds the letter by a word in it, and the
classifier of `inbound-auto-classification` reads the same property. The
text is as personal as the file it came from and has the same access: the
intake record's. Alternative considered: a separate `ocrResult` object as
`ocr-trigger-surface` plans for anonymisation. Rejected here, because the
intake search needs the text itself and that change stores none.

### D4. One setting, safe by default

`ocr_on_arrival`, a boolean app setting in the `SettingsService`
allow-list, default `true`. When it is off, `receive()` queues nothing and
`readingState` stays unset, so the worklist shows no reading column value
for that row.

### D5. Images go to the image path

`processFile()` picks `extractTextFromImage()` for an image MIME type and
`extractTextFromPdf()` otherwise, instead of running both.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| queueing the read | imperative `IJobList::add()` in `receive()` | a side effect of one service call |
| reading states | imperative update through the repository | the states are written by the job that did the work |
| telling someone a read failed | declarative, owned by `intake-failure-reaches-someone` | that change declares the `readingFailed` rule |

## Seed data

`intakeDocument` gains `contentText`. The seed gives the existing sample
scan a `readingState` of `read` and a short `contentText`, so the column
and the search show something on a clean install.

## Risks / trade-offs

- Large scans are slow. The job runs in cron, never in the request that
  stored the document.
- A failed read leaves the document in the inbox, assignable by hand
  (`IntakeReadingProgress::showsInInbox()`).

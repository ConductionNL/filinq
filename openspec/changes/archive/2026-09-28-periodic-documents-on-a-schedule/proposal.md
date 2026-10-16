---
kind: code
depends_on: []
---

# Proposal: periodic-documents-on-a-schedule

Matrix row `gen-periodic` (filinq), rated partial. Written on 28 September
2026 when `documents-from-a-template` was archived: its task 4.1 was ticked,
but a read of the code at the head of the build-all stack shows two halves
missing.

## Why

`PeriodicDocumentService::run()` reads the saved view and writes a
`generatedDocument` entry that counts the records, but it renders no file:
there is no call into `DocumentService` or `PdfService`, so the "besluitenlijst"
is a record with no document behind it. And nothing runs it on its cadence:
`appinfo/info.xml` registers no periodic job, and the only trigger is the
manual `POST api/periodic-documents/run`.

## What changes

- A timed job runs every active `periodicDocument` whose cadence has come
  round since `lastRunAt` (daily, weekly, monthly, quarterly; `onDemand` never).
- A run renders the named template over the view's records through the
  existing generation path, stores the PDF, and the `generatedDocument`
  entry points at that file.
- A run that fails leaves the previous document untouched and records why
  on the schedule, so a broken schedule is visible rather than silent.

## Out of scope

- Authoring a schedule on a screen; the schedule is a register object today.

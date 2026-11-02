# Design: periodic-documents-on-a-schedule

## Context

Read at the head of the build-all stack, 28 September 2026.

- `lib/Service/PeriodicDocumentService.php` `run(array $schedule)` reads the
  view (`readView()`), stamps the layout and writes a `generatedDocument`
  entry through `write()`; `recordRun()` stores `lastRunAt`,
  `lastRunRecords` and `lastRunDocument` on the schedule.
- `periodicDocument` (hardValidation true) has `cadence` enum `daily`,
  `weekly`, `monthly`, `quarterly`, `onDemand` and `active`.
- `appinfo/info.xml` background jobs: SigningExpirationJob,
  DomainFolderReconciliationJob, UploadFragmentReaperJob. None for this.

## Decisions

### D1. One timed job, hourly

`PeriodicDocumentJob extends TimedJob`, interval one hour, lists active
schedules and runs the ones that are due. Due means: no `lastRunAt`, or
`lastRunAt` plus the cadence period is in the past. A pure
`PeriodicCadence::isDue(cadence, lastRunAt, now)` holds the rule.

### D2. The run renders

`run()` calls the same generation path as `POST api/documents/generate`
with the template id and the records as a list reference, stores the PDF
through `DocumentStorageService`, and writes its file id on the entry.

### D3. A failure is recorded

A failed run writes `lastRunError` on the schedule (a new optional
property) and leaves `lastRunDocument` as it was.

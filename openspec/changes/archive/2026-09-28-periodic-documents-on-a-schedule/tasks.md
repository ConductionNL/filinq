# Tasks: periodic-documents-on-a-schedule

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 4. -->

- [x] 1.1 `PeriodicCadence::isDue()` with PHPUnit for every cadence, the first run and `onDemand` (D1)
- [x] 1.2 `PeriodicDocumentJob` (hourly TimedJob) registered in `appinfo/info.xml`, running due schedules; PHPUnit with the real cadence class (D1)
- [x] 1.3 `run()` renders the template over the view's records to a stored PDF and records its file id; PHPUnit on the render call and the payload validated against `generatedDocument` (D2)
- [x] 1.4 `lastRunError` on `periodicDocument` with a register version bump; a failed run records it and keeps the previous document (D3)

## Evidence

- 1.1 `tests/unit/Service/PeriodicCadenceTest.php` (14 cases).
- 1.2 `tests/unit/BackgroundJob/PeriodicDocumentJobTest.php`; the sweep in `PeriodicDocumentServiceTest::testTheSweepRunsWhatIsDueAndSkipsTheRest`.
- 1.3 `PeriodicDocumentServiceTest::testARunRendersTheViewIntoAStoredPdfInTheOwnersFiles`. The entry is written by the generation path's own logger, with `viewSlug`, `recordCount` and the layout passed as PHP-only `recordFields` so no HTTP request can write into its own audit entry.
- 1.4 `PeriodicDocumentServiceTest::testABrokenViewIsWrittenOnTheScheduleAndLastWeeksDocumentStays`; the exact schedule payloads validate against the shipped schema in `tests/unit/Settings/PeriodicDocumentScheduleSchemaTest.php`. Also `lastRunErrorAt`.
- Design note: the PDF goes to the schedule owner's Files (OpenRegister `@self.owner`); a schedule with no owner fails with that reason. On demand it goes to the caller's Files.

# Tasks: periodic-documents-on-a-schedule

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 4. -->

- [ ] 1.1 `PeriodicCadence::isDue()` with PHPUnit for every cadence, the first run and `onDemand` (D1)
- [ ] 1.2 `PeriodicDocumentJob` (hourly TimedJob) registered in `appinfo/info.xml`, running due schedules; PHPUnit with the real cadence class (D1)
- [ ] 1.3 `run()` renders the template over the view's records to a stored PDF and records its file id; PHPUnit on the render call and the payload validated against `generatedDocument` (D2)
- [ ] 1.4 `lastRunError` on `periodicDocument` with a register version bump; a failed run records it and keeps the previous document (D3)

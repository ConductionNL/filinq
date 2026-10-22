# Print jobs in the app

## Why

Matrix row `gen-print` (area generate, core): "Send generated documents to
print, one by one or as a batch, and follow the print job." Rated partial,
state building, with no open change for the missing half. Decided build on
2026-09-28 in the build-all pass because the row is in the core area.

What is built: `PrintJobController` creates a job, a batch, shows one job,
downloads its PDF and takes a status update from an external print service
(`api/print/jobs`, `api/print/batch`, `api/print/jobs/{id}`, `.../download`,
`.../status`).

What is not: no screen calls any of it, so a handler cannot send a letter to
print or see what happened to it. There is no list of jobs. And
`PrintJobService` keeps each job's status and its whole PDF as app-config
strings (`storeJobStatus`, `storeJobPdf`), which puts file bytes in the
`oc_appconfig` table that every request loads.

## What changes

- Job state moves to an OpenRegister `printJob` object; the PDF moves to the
  app's data folder. Existing app-config entries are migrated once and removed.
- `GET api/print/jobs` lists the caller's jobs.
- A Print jobs page lists them with status, and "Send to print" appears on a
  letter in Correspondence, one or a selection.

## Impact

- `PrintJobService`, `PrintJobController`, a repair step, `filinq_register.json`
  (new `printJob` schema, register bump), `src/manifest.json`, a new view.

# Design: print-jobs-in-the-app

## Context

At the build-all stack head (28 Sep 2026): `PrintJobService` renders through
the template path and stores `print_job_<id>` status JSON and the PDF itself in
`IAppConfig`. `PrintJobController::create` takes `templateId`, `data`,
`options`, `filename`; `batch` takes items; `updateStatus` is for an external
print service. `PrintPreview.vue` is the only print surface and calls
`api/print/preview` and `api/print/pdf-a` only.

## Decisions

### D1. A job is an OpenRegister object, its PDF a file in app data

`printJob` holds the status lifecycle (`queued`, `sent`, `printed`,
`failed`), the requester, the item count and the file reference. The PDF goes
to `IAppData` folder `print-jobs`. A repair step moves any `print_job_*`
app-config entries and deletes them.

### D2. Two surfaces

A Print jobs page (manifest route `PrintJobs`) lists the caller's jobs, newest
first, with status and a download. In Correspondence, a selection of letters
gets "Send to print", which creates one batch job.

### D3. Status comes from outside

`updateStatus` stays the way a print service reports back. The page shows the
last status and when it changed; it does not poll faster than once a minute.

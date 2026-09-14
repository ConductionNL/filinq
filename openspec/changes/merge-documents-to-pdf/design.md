# Design: merge-documents-to-pdf

Kind: code. One schema, one service, one leaf, one queued job.

## Context

Two primitives exist. `PdfConversionService::convertToPdf(File)` walks a
backend cascade and returns a PDF/A-3b file (`pdf-conversion`).
`GrondslagenPdfWriter::mergeSummaryIntoPdf()` imports all pages of two
PDFs into one FPDI document. The merge generalises the second over the
output of the first, for n inputs, with a cover page and bookmarks.

## D1. `mergeJob`

In the `document` register, English names (dossiq decision D13).

| property | type | notes |
|---|---|---|
| `inputs[]` | array of `{fileId, label}` | in merge order |
| `options` | object | `coverTemplateRef`, `coverData`, `bookmarks` (bool), `targetFolder` |
| `requestedBy` | string | user id |
| `hostObject` | object reference `{register, schema, id}` | optional; the object the action ran on |
| `resultFileId` | integer | set on `done` |
| `pageCount` | integer | set on `done` |
| `progress` | integer 0 to 100 | updated by the job |
| `lastError` | string | set on `failed` |
| `status` | lifecycle `queued`, `running`, `done`, `failed` | initial `queued` |

`hardValidation: true`. `done` and `failed` are terminal.

## D2. `DocumentMergeService`

`lib/Service/DocumentMergeService.php`, Controller to Service to
OpenRegister (ADR-008).

1. Check read on every input and write on the target folder, server-side.
2. For each input: `convertToPdf()`; a conversion failure fails the job
   with the per-backend report in `lastError`, and no partial result is
   written.
3. When `coverTemplateRef` is set: render the cover through `PdfService`
   with `coverData` plus the input list, and prepend it.
4. Import all pages of each converted input with FPDI, in order. When
   `bookmarks` is true, add one outline entry per input at its first page,
   labelled with `inputs[].label`.
5. Write the result as `<name>.pdf` in `targetFolder`, or beside the first
   input when unset, and run the PDF/A-3b conversion once more on the
   result so the merged file carries the archival profile.
6. Set `resultFileId`, `pageCount`, `done`.

Under `mergeSyncThresholdPages` (setting, default 50 pages) the service
runs inline and the action returns the file. Above it, the job is queued
(`MergeDocumentsJob`, a `QueuedJob`) and the action returns the `mergeJob`
id; the leaf polls `progress`.

## D3. The leaf, ADR-066 `render-surface`

- Server: `RegisterLeafProvidersEvent` listener adds a `LeafDescriptor`
  with id `filinq-merge-to-pdf`, kind `render-surface`, `renderMode:
  component`, surface `bulkAction`.
- Client: `registerIntegration()` under the same id with a `bulkAction`
  and a `widget` (gate-24 parity: the widget lists the host object's merge
  jobs and their results). Props: the selected file ids, the host object
  and the target folder.
- The dialog: order the inputs by drag, pick a cover template (optional),
  toggle bookmarks, name the result. Confirm calls
  `POST /apps/filinq/api/merge`.
- Nothing crosses the seam except the result file in the folder the
  consumer named. The consumer's files browser refreshes and shows it.

## D4. Endpoint

`POST /apps/filinq/api/merge` (`NoAdminRequired`), body `{inputs[],
options, hostObject}`; `GET /apps/filinq/api/merge/{id}` for progress.
Both guarded per object as in D2; a caller who may not read an input gets
a 403 and no job.

## Risks

- A huge batch. The threshold moves it to the queue; the queued job runs
  under the same read and write checks as the inline path.
- An input in a format no backend converts. The job fails with the
  cascade report; the user sees which file and why.
- Personal data in the merged file. It lives in the folder the consumer
  named, under that folder's rights; filinq keeps no copy.

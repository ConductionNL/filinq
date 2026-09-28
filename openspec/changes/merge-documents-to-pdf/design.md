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

Corrected on 2026-09-28, when the leaf was built. The first version of this
section placed the leaf in a `bulkAction` slot of the host's files browser.
The shared integration registry (`@conduction/nextcloud-vue`
`src/integrations/registry.js`) knows the surfaces `user-dashboard`,
`app-dashboard`, `detail-page` and `single-entity`, and has no bulk-action
slot, so a `bulkAction` declared here would be ignored by every host. The
leaf therefore carries its own selection.

- Server: `RegisterMergeToPdfLeafListener` adds a `LeafDescriptor` with id
  `filinq-merge-to-pdf`, kind `render-surface`, `renderMode: mount`,
  surfaces `detail-page` and `single-entity`, wired by
  `IntegrationLeafRegistrar`.
- Client: `src/integrations/registerMergeToPdfLeaf.js` registers the same
  id, icon, surfaces and render mode, in `main.js` and in the `filinq-leaves`
  bundle. Props: the host object (`register`, `schema`, `objectId`).
- The widget lists the host object's documents through
  `GET /api/case-documents/files`, which already leaves out files the reader
  cannot see. The handler ticks documents, orders them with move up and move
  down buttons (keyboard operable, where a drag list is not), picks an
  optional cover template from `GET /api/templates`, toggles bookmarks and
  names the result. Confirm calls `POST /apps/filinq/api/merge` without a
  target folder, so the server writes the PDF beside the first document,
  which on a case is the case folder.
- Nothing crosses the seam except the result file. A bulk action on a host's
  own file list needs a bulk-action slot in the shared registry first; that is
  a nextcloud-vue change, not this one.

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

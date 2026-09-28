# Merge documents into one PDF

A handler on a case often has to send one file that holds several
documents: the application, the decision and the attachments, in a set
order. Filinq merges them into one PDF and puts it beside the first
document.

## On a case page

The `Merge to PDF` leaf shows up on the page of any object whose app places
it (id `filinq-merge-to-pdf`, surfaces `detail-page` and `single-entity`).

1. Tick the documents that go in. Only documents you may open are listed.
2. Put them in order with **Move up** and **Move down**. The PDF follows the
   list under "In the PDF, in this order".
3. Pick a cover page template, or leave "No cover page".
4. Leave "A bookmark per document" on to get one bookmark for each document.
5. Give the PDF a name, or leave it empty for the default name.
6. Choose **Merge**. You need at least two documents.

A small merge is done at once and the leaf links to the PDF. A large one is
queued: the leaf says so, and the PDF appears beside the first document when
the background job has finished. A merge that fails says why and writes no
file.

## Through the API

`POST /apps/filinq/api/merge` takes `inputs` (each `{fileId, label}`, in
order), `options` (`coverTemplateRef`, `bookmarks`, `name`, `targetFolder`)
and `hostObject` (`register`, `schema`, `id`). It answers 200 with the
finished job or 202 with the queued job; `GET /apps/filinq/api/merge/{id}`
reads a job back.

Next step: ask the app that shows your cases to place the leaf on its case
page, then merge the documents of one case to try it.

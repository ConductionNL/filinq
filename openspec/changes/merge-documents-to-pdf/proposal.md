---
kind: code
---

# Proposal: merge-documents-to-pdf

Competitor gap register, row 4.14 "Merge to PDF, copy to another case"
(`procest/_gaps/gap-register.md` in ConductionNL/market-intelligence,
2026-09-13). Rated partial, owner filinq, size M. Opened by the
small-owner lane of the OpenSpec phase.

## Why

A handler who sends a case file to a lawyer, a bezwaarcommissie or an
archive wants one PDF, in order, with a cover page, not a zip of twelve
files in nine formats. dossiq has `DossierZipExporter` (a zip) and
`EmailArchivalService` (one mail to one PDF); nothing merges a selection of
documents into one PDF (register note). Filinq already converts any file to
PDF/A-3b (`pdf-conversion`, `PdfConversionService`) and already
concatenates pages with FPDI (`GrondslagenPdfWriter::mergeSummaryIntoPdf()`).
What is missing is the operation over a selection, and a place to ask for
it.

The best competitor in the register: xxllnc Zaken,
`frontend-mono/apps/main/src/modules/case/views/documents/actions/MergeAction.tsx`
(`_round2/compare/M1-functionality.md`).

The second half of the row, copy to another case, is not filinq's. The
register says so: copy-to-case is the files leaf's copy between objects,
which exists. This change covers the merge only.

## What changes

- A `mergeJob` object in the `document` register: the ordered input files,
  the options, the result file and a lifecycle.
- `DocumentMergeService::merge(files[], options)`: convert every input to
  PDF/A-3b through the existing cascade, concatenate in the given order
  with FPDI, prepend an optional cover page from a filinq template, add
  bookmarks per input, and store the result next to the inputs or in a
  folder the caller names.
- An action leaf per ADR-066 with id `filinq-merge-to-pdf`, kind
  `render-surface`, offered as a bulk action over selected rows in any
  files browser that places it. The leaf runs the merge in filinq's own
  bundle and DI context and writes the result file. It carries no verb into
  the consuming app.
- A queued job for merges over the synchronous threshold, with progress on
  the `mergeJob` object.

## How dossiq consumes it

The register's dossiq half: "a Merge to PDF action over the selected rows;
Copy to case from file-actions". dossiq places `filinq-merge-to-pdf` as a
bulk action on its Files tab, which `documents-on-the-case` (dossiq, open)
builds over `CnFilesBrowser`. The result lands in the case folder and shows
up as a document like any other. Copy to case is the files leaf's existing
copy between objects and needs nothing here.

## ADRs

- ADR-075: filinq owns document and PDF generation for the fleet, one
  channel. The merge is that channel's next verb; no other app embeds a
  PDF engine for it.
- ADR-066: the action is a render-surface leaf; the consumer renders and
  reads, filinq executes.
- ADR-001 and ADR-070: the job is an OpenRegister object.
- ADR-031: the job lifecycle is declared on the schema.

## Existing specs it extends

`pdf-conversion` (the per-file conversion the merge calls first),
`pdfa3-conversion` (the output profile) and `pdf-generation` (the cover
page).

## Out of scope

- Copy to another case. The files leaf's copy exists.
- Redaction inside the merge. A handler anonymises first with the existing
  flow and merges the output.
- Editing page order after the merge. Order is chosen before, in the
  action's dialog.

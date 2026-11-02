# Design: documents from a template, and how they look

Kind: code. Two schemas, two services, two leaves, one scheduled job.

## Context

`TemplateService`, `TemplateRenderer`, `TemplateVersionService` and
`PdfService` already render a template to a document.
`Pdfa3ConversionService` writes the archival profile.
`GrondslagenPdfWriter::mergeSummaryIntoPdf()` and the open change
`merge-documents-to-pdf` already concatenate PDFs. What is missing is
everything about how the page looks, and everything about rendering over
more than one record.

The register is `filinq`, one register since descriptor v8.0.0.

## D1. The layout is an object, selected by the template

A `pageLayout` object carries paper size, margins, header, footer, logo,
and whether the first page differs. A template names a layout. Three
consequences worth having: the briefpapier changes in one place, a
template can be reused across layouts for two organisational units, and
the generated document records which layout version it used, the same way
`generated-document-names-its-template-version` records the template
version.

A layout is versioned, because a besluit generated in March must still be
explicable in 2029.

## D2. The archive is a job with a manifest

Redmine caps its bulk download at `bulk_download_max_size` for a reason.
So the archive is a job: it collects, it respects an administered
ceiling, and it writes a manifest listing every file included and every
file left out with the reason, whether that reason is the ceiling, a
permission or a conversion failure.

A bundle that silently omits a file is worse than a bundle that refuses,
because the omission is discovered by the bezwaarcommissie.

## D3. A periodic document names a view, not a query

The schedule holds the slug of a saved view, a template, a layout and a
cadence. Restating the query in the schedule is how the schedule and the
list diverge. Until a view has a slug, the schedule names the view id and
the tasks record that as the interim.

The render runs as a job, writes one generated document, and records the
view, the number of records it read and the moment it ran. Re-running it
produces a new document; it never edits the previous one, because a
besluitenlijst that changed after it was published is not a
besluitenlijst.

## D4. The review date is a date, and somebody is asked

`reviewInterval` on a released document computes a review date. When it
arrives, the document's owner is notified through the notification
dialect, and the document is listed as due. A review date nobody is told
about is a field, not a capability. This is the same shape as the
retention reminder, which is openregister's under D7.

## D5. A file is a value, and the value is a reference

A form field holding a document holds a reference to the document record,
not a copy of the file. The field's validation checks that the reference
resolves and that the caller may read it. Storing the file in the field
would put the same bytes in two places and make a version history
meaningless.

## D6. Trust is what was verified, in words

A Verified badge that does not say what was verified teaches people to
trust a green tick. The verification result states which signature was
checked, against which key or certificate, when, and what that proves.
Filinq already verifies inbound signatures; what it does not do is say
what the result means.

## D7. A submitted form is rendered once, at submission

The document is produced when the form is submitted, from the values as
submitted, and filed. Rendering it later from the current values would
produce a document that does not match what the citizen sent, which is
the one property the dossier needs it to have.

## Risks

- **A layout that breaks an existing template.** A template names a
  layout version; changing the layout creates a new version and existing
  templates stay on the one they name until somebody moves them.
- **A bundle that is enormous.** The ceiling is administered, the job
  reports what it left out, and the user is told before the job starts if
  the selection already exceeds it.
- **A schedule that fires on a view somebody deleted.** The job fails
  visibly, names the view and stops, rather than producing an empty
  besluitenlijst.
- **A review notification nobody receives.** It goes through the
  notification dialect, so a user's own preferences govern it, and the
  document stays listed as due whether or not the notification was read.

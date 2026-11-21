# PDF/UA validation with veraPDF

Government documents must be accessible (Besluit digitale toegankelijkheid
overheid, EN 301 549). For a PDF that means PDF/UA-1 (ISO 14289-1), checked
against the Matterhorn Protocol. Filinq's quick accessibility check only looks
for the marks of a tagged PDF: tags, a language, a title, the PDF/UA
identifier. A PDF can carry all of them and still fail PDF/UA, for example
with content outside the tags or a figure without an alternative text. Filinq
therefore checks PDFs against PDF/UA-1 with veraPDF, the same validator it
uses for PDF/A (see [PDF/A validation](verapdf-validation.md)): the same
installation, the same `filinq.verapdf.*` settings and the same status row in
the admin settings. Documents never leave the instance, and a report holds
Matterhorn clause and checkpoint references only, never text from the
document.

## Which verdict counts

| What Filinq knows | What the document shows |
| --- | --- |
| A PDF/UA-1 report from veraPDF | The validator's verdict: **conforms** or **does not conform**. A quick check that passes beside a failing report is a fail. |
| No report, quick check passes | **Not validated**: it looks tagged, which is not a conformance verdict. |
| No report, quick check finds problems | **Not validated**, with the problems the quick check found. |

Every accessibility finding says where it comes from: **Validator** or
**Quick check**. A verdict is a conformance verdict, never a certification.

### Example: tagged, and still not PDF/UA

`tests/sample-documents/pdfua/heuristics-pass-matterhorn-fail.pdf` is tagged,
marked, has a language, a title and the PDF/UA identifier, so the quick check
finds nothing. veraPDF fails it on three Matterhorn checkpoints:

| Clause | Test | What is wrong |
| --- | --- | --- |
| 7.21.4.1 | 1 | a font is not embedded |
| 7.3 | 1 | a figure has no alternative text |
| 7.1 | 3 | a paragraph is drawn outside the tags |

The document therefore reads **does not conform**, not "looks tagged".
`pdfua1-conformant.pdf` beside it, a LibreOffice PDF/UA export, passes both.

## Check one document

Open a PDF and choose **PDF/UA report**. The report shows the state from the
table above, the Matterhorn checkpoints that failed (clause, test number,
checkpoint), what to do about it, and which veraPDF version checked it and
when. **Check against PDF/UA** runs the check again; a file has one report,
and a new check updates it. The PDF/UA report is kept apart from the PDF/A
report, so one check never overwrites the other.

The advice depends on where the PDF came from:

- **Made by Filinq through its accessible output**: generate it again in
  Filinq with the accessible output option.
- **Imported, uploaded, or made through Filinq's untagged output**: Filinq does
  not retag imported pages. Create the document again from an accessible
  source, for example the original Word document with headings and
  alternative texts.

Without veraPDF the report says the document was not checked for PDF/UA, and
no verdict is shown.

## Make a generated document accessible

For a PDF Filinq generated from a template, a report that does not pass offers
**Make accessible**. Filinq renders the same template version over the same
data again through the tagged output path and checks the new copy:

- the copy passes: it is stored as a new version of the file and tagged
  `pdfua-conform`
- the copy still fails: it is discarded, the file and its version stay as they
  were, and the failed verdict is shown

For a PDF Filinq did not generate the action is not offered, and the API
answers `409` with `not-generated-by-filinq`. Filinq never converts or retags
imported pages.

## Every PDF attached to a register object

When a PDF is added to, or a new version is written in, a user's
`Open Registers` folder (the files OpenRegister attaches to objects, such as a
publication in OpenCatalogi), Filinq queues a background PDF/UA check. The
upload itself never waits for veraPDF. When the check has run, the file
carries exactly one of these system tags, visible in every app that lists
files and assigned only by the server:

| Tag | Meaning |
| --- | --- |
| `pdfua-conform` | veraPDF found the PDF conformant with PDF/UA-1 |
| `pdfua-niet-conform` | veraPDF found it not conformant |
| `pdfua-niet-gecontroleerd` | no verdict: veraPDF is missing, took too long or could not read the file |

A file outside `Open Registers` is left to the other validation paths.

## Accessibility checks in document validation

Document validation's **Accessibility checks** group carries two
validator-backed checks beside the quick checks, which stay as they are:

- `pdfua-conformance-failed`: veraPDF found the PDF not conformant with
  PDF/UA-1; the finding names the failed checkpoints
- `accessibility-validator-unavailable`: the check is on but veraPDF gave no
  verdict, so the PDF was not validated

They are off until a validation profile gives `pdfua-conformance-failed` a
severity, in the `validation.profiles` app config, keyed by document type:

```json
{ "default": { "severities": { "pdfua-conformance-failed": "warning" } } }
```

At `blocking` the finding stops intake the same way the other checks do.
`accessibility-validator-unavailable` is always a warning.

## Publication readiness

Before a Woo record is handed off, every PDF of the record (the document and
its redacted copy) whose PDF/UA report is failing, or that has no report, is a
readiness reason that names the file. A missing report counts as not passed.
The reason is a warning while the severity of `pdfua-conformance-failed` is
below `blocking`; at `blocking` it stops the hand-off. The record keeps the
outcome as `pdfUaGate`: `clear`, `warning` or `blocking`.

## API

| Route | What it does |
| --- | --- |
| `GET /api/validation/accessibility/{fileId}` | The stored report, the quick-check findings, the surfaced state, and whether the file can be regenerated. Runs no validation. |
| `POST /api/validation/accessibility/{fileId}` | Runs the PDF/UA-1 check and stores the report. |
| `POST /api/validation/accessibility/{fileId}/regenerate` | Regenerates a generated PDF as PDF/UA; keeps it only when it passes. |

Every route answers `404` for a file outside the caller's own folder, without
saying whether it exists.

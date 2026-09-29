# PDF/A validation with veraPDF

An e-depot accepts a PDF only when it really meets PDF/A. A file that only
says it is PDF/A gets through Filinq's own checks and is refused later, at
the archive. Filinq therefore checks documents against the standard with
veraPDF, a validator installed on your own server. Documents never leave the
instance, and a report holds rule references and font names only, never text
from the document.

## Install veraPDF

veraPDF is a Java program the administrator installs on the Nextcloud server
(see verapdf.org). Filinq looks for `verapdf` on the path. Point it elsewhere,
switch it off or give it more time with app config:

```bash
occ config:app:set filinq filinq.verapdf.binary_path --value=/opt/verapdf/verapdf
occ config:app:set filinq filinq.verapdf.enabled --value=false
occ config:app:set filinq filinq.verapdf.max_seconds --value=120
```

Under **Administration settings > Filinq > PDF/A validation** you see whether
the validator is installed and which version, or that it is switched off.
Without it Filinq still checks that a PDF claims to be PDF/A, and says that
this is all it checked.

## Check one document

Open a PDF in **My documents** and choose **PDF/A report**. The report shows:

- the verdict for the level the document claims, or 3b when it claims none
- the rules it breaks: standard, clause, test number and how many places
- the fonts it uses without embedding them
- what to do about it
- which veraPDF version checked it, and when

**Check against PDF/A** runs the check again. A file has one report: a new
check updates it.

The advice depends on where the PDF came from:

- **Made by Filinq**: generate it again in Filinq, which embeds the fonts.
- **Imported or uploaded**: Filinq cannot embed fonts in pages it imported
  whole. Convert again from the original file (for example the Word
  document), or expect the e-depot to refuse the PDF.
- **Other rule failures**: look the rules up in the standard, fix the source
  and create the PDF again.

When veraPDF is missing, too slow or cannot read the file, the check says so
and records no verdict. A validator that failed never reads as a pass.

## Archival checks in document validation

Document validation has an **Archival checks (PDF/A)** group beside the
document checks:

- `pdfa-conformance-failed`: the PDF breaks rules of its PDF/A level
- `pdfa-font-not-embedded`: the PDF uses fonts it does not carry
- `archival-validator-unavailable`: veraPDF could not run, so the PDF was
  not checked

They are off until a validation profile gives them a severity, in the
`validation.profiles` app config, keyed by document type (here `archive`):

```json
{ "archive": { "severities": { "pdfa-conformance-failed": "error", "pdfa-font-not-embedded": "warning", "archival-validator-unavailable": "warning" } } }
```

At severity `error` the finding stops intake the same way the other checks
do.

## PDF/A-3 conversion

When veraPDF is installed, every PDF/A-3 conversion is checked before it is
returned. The response carries `X-Docudesk-Pdfa3-Verified`: `true`, `false`,
or `skipped` when no validator ran. The report is stored on the source file
as its PDF/A-3 conversion report.

By default a failing conversion is still returned, with `false` in the
header. To refuse it instead:

```bash
occ config:app:set filinq filinq.pdfa3.strict_verify --value=true
```

The conversion then fails with reason `output_validation_failed`.

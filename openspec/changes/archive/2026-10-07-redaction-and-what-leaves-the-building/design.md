# Design: redaction and what leaves the building

Kind: code. Two schemas, one gate, one verification suite, one composed
render.

## Context

The redaction path exists. `AnonymizationService` orchestrates,
OpenRegister's `FileService::anonymizeDocument()` rewrites the file,
`AnonymizationPersistenceService` writes the `anonymizationLink`, and
`ReplacementVerificationService` and `UnredactedEntitiesValidator`
already check parts of the result. `anonymisation-prohibition-gate`
established that this app refuses rather than degrades.

What is missing is a guarantee somebody can hold us to, and a step a
person cannot walk past.

## D1. Irreversibility is a test, not a sentence

A claim that a redaction is irreversible is worth what verifies it. So
the guarantee is a list of recovery routes, each with a check over the
produced bytes:

| route | check |
|---|---|
| text under the mark | extract text, assert no detected value is present |
| embedded thumbnail or preview stream | assert absent or regenerated from the redacted page |
| XMP, EXIF and document properties | assert stripped or rewritten |
| incremental PDF update or prior revision | assert one revision, no incremental section |
| annotation or form field values | assert removed, not merely hidden |
| attached files inside the PDF | assert removed or themselves redacted |

The suite runs on every output mode, including the PDF/A and PDF/UA
paths, because an accessibility rewrite that re-adds a text layer is
exactly how this guarantee breaks quietly.

## D2. The gate is server-side and fails closed

Output is refused unless the document carries a checked mark naming the
person and the moment. The refusal lives in the service, not in the
screen, so the API, the batch path and the leaf all hit it. A gate a
batch run can skip is not a gate, and a batch run is where a Woo dossier
of fifty-five thousand documents actually goes through.

The mark is per document and per detection run. Re-running detection
clears it, because the thing that was checked is no longer the thing that
would be written.

## D3. The review step is where a human is, and it says what it is for

The workbench is `anonymization-review-workbench`. What this change adds
is the consequence: the checked mark, its clearing rule, and a refusal
message that tells an operator what to do rather than what went wrong.

## D4. The composed document references copies, never originals

A Woo-publicatielijst renders over the records a named view returns and
links each entry to the redacted copy. The original is never referenced,
never linked and never embedded. The `anonymizationLink` already pairs
the two, so the composition resolves the copy from the link rather than
from a naming convention, which is how a wrong file gets published.

A record in the view with no redacted copy is listed as not ready and the
document is not produced until it is, or the operator removes it from the
view.

## D5. The agreement is a version, an acceptance and a file

A `downloadAgreement` object carries its text and its version. An
acceptance records who, when and which version, against the file and the
person. The download is served only after the acceptance is written, and
a new version of the agreement asks again. An acceptance that cannot be
written stops the download, because an unrecorded acceptance is the same
as none.

## Risks

- **An output path added later that skips the suite.** The suite runs
  against the produced bytes of every output mode, and a new mode without
  a verification entry fails the suite rather than passing by omission.
- **An operator who checks without looking.** Nothing solves that, and
  the record of who checked is what makes it accountable rather than
  anonymous.
- **A redaction the detector never proposed.** The workbench's
  select-text-to-create-entity is the answer, and the gate does not
  distinguish automatic from manual entities: both are what was checked.
- **A view that changes between composing and publishing.** The composed
  document records the view, the count and the moment, so what was
  published is explicable afterwards.

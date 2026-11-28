---
kind: code
depends_on: []
---

# Proposal: forms-as-documents

Split out of `documents-from-a-template` on 28 September 2026, when that
change was finished and archived. It carries REQ-DFT-05 and the two tasks
that were never started there (4.3 and 5.1). No matrix row names it; the
requirement came from the dossiq competitor analysis with the rest of
`documents-from-a-template`.

## Why

A permit form asks for evidence, such as an overview of DigiD connections,
and today the applicant attaches a copy of a file the organisation already
holds. And a submitted aanvraagformulier is not filed on the case as a
document at all, so the dossier does not show what the applicant sent.

## What changes

- A form field can hold a reference to a document record. The field checks
  that the reference resolves and that the caller may read it, and no file
  is copied into the field.
- A submitted form is rendered to a document at submission, from the values
  as submitted, through filinq's template rendering, and filed on the record.

## Open question (needs a decision)

filinq has no form surface. The form lives in whichever app renders it:
OpenRegister's object forms, portaliq's portal forms, or a consuming app.
The field type belongs where the form is defined, and filinq supplies the
render at submission. Which surface owns the field type is a product call,
recorded for Ruben in the build-all lane state, and this change is not
built until it is made.

## Out of scope

- Everything else in `documents-from-a-template`, which is archived.

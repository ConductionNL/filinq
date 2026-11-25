# Design: forms-as-documents

Kind: code. The open decision was made on 24 November 2026: D1 stands.

## Context

Read at the head of the build-all stack, 28 September 2026.

- filinq renders a template with values through `DocumentService::generateDocument()`
  and files the result through `DocumentStorageService`.
- filinq has no form definition, no form renderer and no field-type
  registry. `grep -ri "formField\|fieldType" lib src` finds none.

## Decisions

### D1. filinq renders, the form surface defines

The field type "document reference" is declared by the surface that defines
forms. filinq offers one endpoint that renders a submitted form's values
through a named template and files the result on the record, so every form
surface can call it at submission.

### D2. Values as submitted

The render takes the values from the submission payload, never from the
record afterwards, so a later change on the case does not change the filed
document.

## Decision (24 November 2026)

Question: which surface owns the document-reference field type?
Options: (a) OpenRegister object forms, (b) portaliq, (c) the surface that
defines the form, with filinq offering the render and the reference check.
Chosen: (c), which is D1. Reason: filinq has no form definition or renderer,
and putting the field type in filinq would make it a second form surface; (a)
and (b) are each one of the surfaces that define forms, so neither can own
the field for the other. Both call the same two filinq routes:

- `POST api/forms/document-reference`: `{register?, schema?, id}` answers the
  reference and the record's title when the caller may read it, 404
  otherwise (also when the record exists, so the check reveals nothing).
- `POST api/forms/submissions`: `{templateId, record, values,
  documentFields?, filename?, format?}` renders through the template with the
  values as submitted under `form`, the checked references under
  `documents`, and files the result in the record's folder (201).

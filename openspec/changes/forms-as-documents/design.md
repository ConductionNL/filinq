# Design: forms-as-documents

Kind: code. Blocked on one decision: which surface owns forms.

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

## Open questions

- Which surface owns the field type (OpenRegister object forms, portaliq, or
  each consuming app)?

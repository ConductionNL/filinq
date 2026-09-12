---
kind: code
---

# Proposal: template-library-leaf

## Why

dossiq's documents-on-the-case tab wants to offer "generate from template"
next to the files that already sit on a case. The templates live in filinq
(`template-management`, `document-creatie-sjablonen`). Today dossiq keeps its
own Generate document action and a `DossiqMergeTemplateNode` because filinq
offers no surface a sibling can place. That is a second template engine in the
fleet, and the dossiq analysis lists it as the thing to retire (row A16, tier B
and sibling requests, section 3).

Competitor evidence, in `concurrentie-analyse/procest/_round2/`:
`oc/pages/ManageTemplates.md` (OpenCase) and `zs/pages/Catalogus.md`
(Zaaksysteem) both offer the template library from inside the case.

## What changes

- A `data-provider` leaf `filinq-templates` (ADR-066) that lists the
  templates applicable to a host object, filtered on the object's register and
  schema, in filinq's DI context. Read only.
- A `render-surface` leaf `filinq-generate-document` with a widget and a tab.
  The widget offers the applicable templates and opens filinq's merge dialog.
  The merge runs in filinq (`REQ-DCS-02`); the result lands in the host
  object's folder as a filinq document.
- A `scope` on templates: an optional list of `{register, schema}` pairs a
  template applies to. Empty scope means any object.

## Out of scope

- The merge engine itself. It exists (`document-creatie-sjablonen`).
- dossiq's retirement of `DossiqMergeTemplateNode`. That is dossiq's change
  once this leaf ships.

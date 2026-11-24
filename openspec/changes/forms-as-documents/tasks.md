# Tasks: forms-as-documents

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 0. -->

## 0. Decision

- [x] 0.1 Decide which surface owns forms and the document-reference field type (design, open question). Nothing below starts before it. Decided 24 Nov 2026 (delegated product decision): D1 as drafted. The surface that defines a form owns its fields, the document-reference field type included; filinq answers the two calls every form surface makes: `POST api/forms/document-reference` and `POST api/forms/submissions`. See design.md D1.

## 1. Build

- [x] 1.1 A form field holds a reference to a document record, validated as resolvable and readable by the caller; no file is copied into the field (REQ-DFT-05). Was `documents-from-a-template` task 5.1.
- [x] 1.2 A submitted form is rendered to a document at submission, from the values as submitted, and filed on the record (REQ-DFT-05). Was `documents-from-a-template` task 4.3.

## Build notes

- 1.1: `FormDocumentFiling::checkReference()` (`lib/Controller/FormDocumentFiling.php`, beside its controller like `TemplateRequestHandler`) reads the
  record with the caller's rights and answers `{register, schema, id, title}`;
  it never reads or copies the file. A record the caller may not read answers
  the same 404 as one that does not exist, so the check does not reveal that a
  record exists. Route `POST api/forms/document-reference`. PHPUnit:
  `tests/unit/Controller/FormDocumentFilingTest.php`.
- 1.2: `FormDocumentFiling::renderSubmission()` renders through
  `DocumentService::generateDocument()` with no data references and the
  submission as `adHocData` (`form`, `documents`, `record`), so the filed
  document is the values as submitted (D2), and stores it in the record's
  folder through OpenRegister's `FileService::addFile()`. Every field named in
  `documentFields` is checked as in 1.1 before anything renders. Route
  `POST api/forms/submissions` (201). PHPUnit for the service and
  `tests/unit/Controller/FormDocumentControllerTest.php`.
- Not part of this change: the field type itself in OpenRegister object forms
  or portaliq; each form surface calls these two routes from its own change.

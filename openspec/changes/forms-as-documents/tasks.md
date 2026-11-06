# Tasks: forms-as-documents

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 3. -->

## 0. Decision

- [ ] 0.1 Decide which surface owns forms and the document-reference field type (design, open question). Nothing below starts before it.

## 1. Build

- [ ] 1.1 A form field holds a reference to a document record, validated as resolvable and readable by the caller; no file is copied into the field (REQ-DFT-05). Was `documents-from-a-template` task 5.1.
- [ ] 1.2 A submitted form is rendered to a document at submission, from the values as submitted, and filed on the record (REQ-DFT-05). Was `documents-from-a-template` task 4.3.

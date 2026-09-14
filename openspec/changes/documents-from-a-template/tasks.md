# Tasks: documents-from-a-template

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11. -->

## 1. Schemas

- [ ] 1.1 Add `pageLayout` (paper, margins, header, footer, logo, first-page difference, version) to the `filinq` register, with an authorization cascade and a descriptor version bump (REQ-DFT-01)
- [ ] 1.2 Add `periodicDocument` (view slug, template, layout, cadence, last run) and `archiveJob` (inputs, ceiling, manifest, lifecycle) to the same register (REQ-DFT-02, REQ-DFT-03)

## 2. Layout

- [ ] 2.1 A template names a layout version; `PdfService` renders through it, and the generated document records both versions (REQ-DFT-01)
- [ ] 2.2 Admin surface to author and version a layout, with a preview of the first and following pages (REQ-DFT-01)

## 3. The archive

- [ ] 3.1 `CaseArchiveService`: collect every file on an object, honour the administered ceiling, write the manifest of what was included and what was left out with the reason (REQ-DFT-02)
- [ ] 3.2 Leaf `filinq-download-all-files` per ADR-066, warning before the job starts when the selection already exceeds the ceiling (REQ-DFT-02)

## 4. Periodic and released documents

- [ ] 4.1 Render a template over the records a saved view returns, on a cadence, writing a new generated document each run and never editing the previous one (REQ-DFT-03)
- [ ] 4.2 `reviewInterval` on a released document computes a review date, lists the document as due and notifies its owner through the notification dialect (REQ-DFT-04)
- [ ] 4.3 A submitted form is rendered to a document at submission, from the values as submitted, and filed on the record (REQ-DFT-05)

## 5. Field values and trust

- [ ] 5.1 A form field holds a reference to a document record, validated as resolvable and readable by the caller; no file is copied into the field (REQ-DFT-05)
- [ ] 5.2 The verification result states which signature was checked, against which key, when, and what that proves (REQ-DFT-06)

## 6. Quality

- [ ] 6.1 PHPUnit inside the container for the layout, the ceiling, the manifest, the schedule and the review date; 75% on new code (ADR-009)
- [ ] 6.2 Playwright `tests/e2e/documents-from-a-template.spec.ts`; Dutch and English strings; docs in `docs/features/document-templates.md` with screenshots

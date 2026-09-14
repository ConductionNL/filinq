# Tasks: redaction-and-what-leaves-the-building

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10. -->

## 1. Schemas

- [ ] 1.1 Add `redactionReviewMark` (document, detection run, checkedBy, checkedAt) and `downloadAgreement` with its acceptance to the `filinq` register, with authorization cascades and a descriptor version bump (REQ-RWB-02, REQ-RWB-04)

## 2. The irreversibility guarantee

- [ ] 2.1 `RedactionIrreversibilityVerifier`: check the produced bytes for text under the mark, embedded previews, XMP and EXIF, incremental updates, annotations and form fields, and embedded attachments (REQ-RWB-01)
- [ ] 2.2 Run the verifier on every output mode including PDF/A and PDF/UA; a new output mode with no verification entry fails the suite (REQ-RWB-01)
- [ ] 2.3 Record the verification result on the `anonymizationLink` so a published copy can be shown to have been checked (REQ-RWB-01)

## 3. The review gate

- [ ] 3.1 Refuse output server-side until a `redactionReviewMark` exists for the document and its current detection run (REQ-RWB-02)
- [ ] 3.2 Clear the mark when detection is re-run, and say so in the refusal message (REQ-RWB-02)
- [ ] 3.3 Enforce the same gate on the API, the batch path and the leaf, with one message that tells the operator what to do (REQ-RWB-02)

## 4. Composition and the agreement

- [ ] 4.1 Compose a publishable document over the records a named saved view returns, resolving each redacted copy through its `anonymizationLink` and referencing no original (REQ-RWB-03)
- [ ] 4.2 Gate a download on an accepted `downloadAgreement`, recording who, when and which version; a new version asks again and an unwritable acceptance stops the download (REQ-RWB-04)

## 5. Quality

- [ ] 5.1 PHPUnit inside the container for every verification route, the gate on all three paths, the composition and the agreement; 75% on new code (ADR-009)
- [ ] 5.2 Playwright `tests/e2e/redaction-gate.spec.ts`; Dutch and English strings; docs in `docs/features/redaction.md` with screenshots; tell dossiq the review leaf id

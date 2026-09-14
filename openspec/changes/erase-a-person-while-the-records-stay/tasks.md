# Tasks: erase-a-person-while-the-records-stay

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10. -->

## 1. Schemas

- [ ] 1.1 Add `subjectErasureRequest` (subject identifiers, ground, requester, lifecycle, progress) and `erasureCertificate` to the `filinq` register, with authorization cascades, retention and a descriptor version bump (REQ-EPR-01, REQ-EPR-05)
- [ ] 1.2 Declare the erasure as its own `x-openregister-processing` activity with purpose, legal ground, data categories and retention (REQ-EPR-01)

## 2. The preview

- [ ] 2.1 `SubjectErasurePreviewService`: documents, occurrence counts, final versions, obligations, unprocessable files and the reason, capped with the cap and the full count both stated (REQ-EPR-02)
- [ ] 2.2 Match over the entity catalogue plus operator-added dictionary terms; every occurrence reviewable and excludable with a reason before the job runs (REQ-EPR-02)

## 3. The job

- [ ] 3.1 Erase occurrence by occurrence through the existing anonymisation path, with progress on the request and a resumable run (REQ-EPR-03)
- [ ] 3.2 Where the current version is final, write a new version referencing it, then erase the superseded version's content while keeping its version record (REQ-EPR-03)
- [ ] 3.3 Destroy any reversible-pseudonymisation mapping entries for the subject and record the destruction (REQ-EPR-04)

## 4. Refusals and the certificate

- [ ] 4.1 Refuse a document under retention, a publication prohibition or a legal hold; list it with the obligation and who must decide (REQ-EPR-04)
- [ ] 4.2 Write the certificate: documents, occurrence counts, moment, actor, ground, refusals with reasons, and anything already published that needs republishing (REQ-EPR-05)

## 5. Quality

- [ ] 5.1 PHPUnit inside the container for matching, the preview cap, the final-version path, every refusal kind, mapping destruction and the certificate; 75% on new code (ADR-009)
- [ ] 5.2 Playwright `tests/e2e/subject-erasure.spec.ts`; Dutch and English strings; docs in `docs/features/subject-erasure.md` with screenshots; tell dossiq and opencatalogi the leaf id and the republication list

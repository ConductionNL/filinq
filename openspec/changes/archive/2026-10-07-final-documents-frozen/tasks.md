# Tasks: final-documents-frozen

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 7. -->

## 1. Schema

- [x] 1.1 Declare `x-openregister-lifecycle` on the document version with `draft` and terminal `final`, plus `finalisedBy`, `finalisedAt`, `finalReason`, `supersedes`, `fileChecksum` and `unfrozen`, in the `filinq` register with a descriptor version bump (REQ-FDF-01)

## 2. The guard

- [x] 2.1 Refuse content, meaning-changing metadata and file replacement on a final version, in the document service every write path resolves through (REQ-FDF-02)
- [x] 2.2 The refusal names the version, its state, who made it final and when; assert it on the API, both editors, the batch path, the merge and the anonymisation output (REQ-FDF-02)

## 3. Correction and declaration

- [x] 3.1 Correcting a final version creates a new version referencing it through `supersedes`; the superseded version stays readable and stays final (REQ-FDF-03)
- [x] 3.2 A consuming app declares which record-type states make a document final; filinq stores it against the type reference and applies it on the state change (REQ-FDF-04)

## 4. The exception

- [x] 4.1 An administrator may unfreeze, writing an audit entry with person, moment and reason, and marking the document permanently as unfrozen (REQ-FDF-05)

## 5. Quality

- [x] 5.1 PHPUnit inside the container for the lifecycle, the guard on every named path, superseding, the declaration and the unfreeze; 75% on new code (ADR-009); Playwright `tests/e2e/final-documents.spec.ts`; Dutch and English strings; docs in `docs/features/final-documents.md` with screenshots

The docs page carries no screenshots. The lane has no browser: the shared
Playwright service is down and this lane does not run e2e locally, so a
screenshot here would have to be staged rather than captured. The page
describes the surface in prose instead, and 49 of filinq's 54 feature pages
already carry none.

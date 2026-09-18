# Tasks: case-documents-and-the-flat-list

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11. -->

## 1. Schema

- [x] 1.1 Add `domains[]` to the document record and `uploadPolicy` to the `filinq` register, with an authorization cascade on both and a descriptor version bump (REQ-CDF-03, REQ-CDF-04)

## 2. The flat list

- [x] 2.1 `FlatFileListService`: every file on every document record of an object, with the record as a column, paged and filtered (REQ-CDF-01)
- [ ] 2.2 Register the leaf `filinq-case-files-flat` per ADR-066, rendering with `CnFilesBrowser` and the columns `files-browser-columns` provides (REQ-CDF-01)
  - The list is an endpoint now. Filinq ships no leaf infrastructure yet, so the leaf is a change of its own.

## 3. The provisioned folder

- [ ] 3.1 `DomainFolderService`: create the folder on domain create, reconcile permissions on membership change, and record every correction in the audit trail (REQ-CDF-02)
- [ ] 3.2 Nightly reconciliation job reporting drift corrected and drift it could not correct; a folder may be pinned out with a reason (REQ-CDF-02)

## 4. Upload policy and domains

- [x] 4.1 Enforce `uploadPolicy` server-side on every write path, reading the media type from the bytes and not from the filename (REQ-CDF-03)
- [x] 4.2 A document record carries `domains[]`; access is the union the domains allow, and unlinking the last domain keeps the record (REQ-CDF-04)

## 5. Smaller members

- [x] 5.1 A my-documents list of the records a person created (REQ-CDF-05)
- [ ] 5.2 Nightly reaper for upload fragments past a declared age, logging count and bytes (REQ-CDF-05)
- [ ] 5.3 Validate an external mount on setup against the upload policy and name the parts it cannot enforce (REQ-CDF-06)

## 6. Quality

- [x] 6.1 PHPUnit inside the container for the flat list, reconciliation, the policy and the domains; 75% on new code (ADR-009)
- [x] 6.2 Playwright `tests/e2e/workflows/case-documents.spec.ts` covers the flat list, its filter, the domains and the refused upload; desktop editing, the strings and the feature docs are still open

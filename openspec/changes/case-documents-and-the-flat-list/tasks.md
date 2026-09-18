# Tasks: case-documents-and-the-flat-list

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11. -->

## 1. Schema

- [x] 1.1 Add `domains[]` to the document record and `uploadPolicy` to the `filinq` register, with an authorization cascade on both and a descriptor version bump (REQ-CDF-03, REQ-CDF-04)

## 2. The flat list

- [x] 2.1 `FlatFileListService`: every file on every document record of an object, with the record as a column, paged and filtered (REQ-CDF-01)
- [ ] 2.2 Register the leaf `filinq-case-files-flat` per ADR-066, rendering with `CnFilesBrowser` and the columns `files-browser-columns` provides (REQ-CDF-01)
  - BLOCKED, MEASURED 2026-09-18, same blocker as `document-intake-inbox` 3.2:
    filinq consumes no `RegisterLeafProvidersEvent` (`grep -rn` over `lib/`
    returns nothing) and `webpack.config.js` declares no `leaves` entry. A leaf
    registered without that entry is DARK: the host renders nothing and the
    registration reports success, which is the worst of both. Not forced.
  - The list is an endpoint now. Filinq ships no leaf infrastructure yet, so the leaf is a change of its own.

## 3. The provisioned folder

- [~] 3.1 `DomainFolderService`: create the folder on domain create, reconcile permissions on membership change, and record every correction in the audit trail (REQ-CDF-02)
  - BUILT: `DomainFolderService` with `ensureFolder()` and `reconcile()`, over a
    narrow `DomainFolderGateway` seam.
  - 🔴 THE HARD PART IS NOT THE CORRECTING, IT IS THE NOT CLAIMING. The
    requirement's third scenario is a mount that REFUSES a permission change,
    and a reconciler that swallows that and reports "reconciled" is worse than
    none: somebody reads the line and stops looking while a group that should
    have lost access still has it. So a PARTLY reconciled folder reports
    REFUSED, not corrected, even though some changes landed, and what did land
    is still listed. Mutation-checked.
  - A FAILED REVOKE IS THE ONE THAT MATTERS. A failed grant leaves somebody
    without access and they report it within the hour; a failed revoke leaves
    somebody WITH access nobody meant them to have, and nobody reports that.
  - A folder that cannot even be READ is a refusal, not an empty folder.
    Reading "no groups have access" from a failed read would revoke nothing and
    grant everything, the widest possible wrong answer. Mutation-checked.
  - A PIN IS REPORTED AS PINNED, WITH ITS REASON, not omitted; and a pin with
    no reason is not a pin, which is what the requirement's "recorded reason"
    exists to prevent.
  - 🔑 WHAT WAITS, AND WHY IT IS A SEAM RATHER THAN AN OVERSIGHT: the
    Nextcloud-backed gateway. `OCP\Share\IManager` DOES NOT EXIST in this
    repository's test environment, measured with `interface_exists()` under
    `tests/bootstrap-unit.php`, where `OCP\Files\IRootFolder` returns true and
    the share manager returns false. An adapter written here could be typed but
    neither run nor tested, and a caller-less adapter nobody can execute is the
    shape this work keeps finding. Named rather than shipped blind.
  - ALSO STILL OPEN: the audit-trail write on each correction. The outcome
    carries everything an audit line needs (`state`, `path`, `granted`,
    `revoked`, `refused`), and filinq's only auditor today is
    `SigningAuditService`, which is signing-specific. Writing a second auditor
    here would be a second answer to "what is on the trail"; it belongs with
    whoever generalises that one.
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

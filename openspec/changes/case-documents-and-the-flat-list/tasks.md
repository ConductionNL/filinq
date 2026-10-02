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
- [x] 3.2 Nightly reconciliation job reporting drift corrected and drift it could not correct; a folder may be pinned out with a reason (REQ-CDF-02)
  - `DomainFolderReconciliationJob` (nightly `TimedJob`) over `DomainFolderReconciler`,
    which reads its domains from `DomainDirectory`.
  - 🔴 THE REPORT HAS TWO HALVES AND THE SECOND IS THE ONE THAT GOES MISSING.
    `corrected` and `refused` are separate lists, both always present, counted
    apart, and each refusal gets its own warning line naming the folder, the
    group, the action and the reason. A count on its own cannot be acted on.
    Mutation-checked by routing REFUSED into the corrected bucket: two
    assertions redden, not a setup line.
  - A DOMAIN THAT THREW IS REPORTED AS REFUSED, NOT SKIPPED, and the run carries
    on. Skipping makes a folder nobody reached indistinguishable from one that
    was already right, and shortens the report instead of failing it.
  - 🔴 "NOBODY CONFIGURED A DOMAIN SOURCE" AND "THERE ARE NO DOMAINS" MUST NOT
    COLLAPSE. A domain is a unit or a case, which belongs to the app that owns
    cases, so filinq defines no domain schema and reads whatever register an
    administrator points it at (`domainFolder_register`, `domainFolder_schema`,
    `domainFolder_owner`). Unconfigured, unowned and unreadable are each
    reported as a SKIP with a reason; an empty domain list would read as a
    clean night while every folder drifted.
  - A PIN IS REPORTED WITH ITS REASON and is not counted as in step.
  - STILL OPEN, unchanged from 3.1: the audit-trail write on each correction,
    and the Nextcloud-backed `DomainFolderGateway`. The report carries
    everything an audit line needs; what is missing is a second auditor beside
    the signing-specific one, which is somebody else's generalisation.

## 4. Upload policy and domains

- [x] 4.1 Enforce `uploadPolicy` server-side on every write path, reading the media type from the bytes and not from the filename (REQ-CDF-03)
- [x] 4.2 A document record carries `domains[]`; access is the union the domains allow, and unlinking the last domain keeps the record (REQ-CDF-04)

## 5. Smaller members

- [x] 5.1 A my-documents list of the records a person created (REQ-CDF-05)
- [x] 5.2 Nightly reaper for upload fragments past a declared age, logging count and bytes (REQ-CDF-05)
  - `UploadFragmentReaper` plus `UploadFragmentReaperJob`, sweeping every seen
    user's documents folder nightly and logging removed, bytes, kept and
    refused, per user and once for the instance.
  - 🔴 IT SWEEPS FILINQ'S OWN TREE AND NOT `<user>/uploads`. Nextcloud already
    reaps its DAV chunk directories with `OCA\DAV\BackgroundJob\UploadCleanup`,
    and a second reaper over the same directory would be a second answer to one
    question with two declared ages disagreeing about which won. What nothing
    reaps is the half-written file an interrupted upload leaves INSIDE the
    documents folder.
  - 🔴 A FRAGMENT IS RECOGNISED BY ITS SUFFIX, NEVER BY BEING EMPTY, SMALL OR
    OLD. The costly bug here is not leaving a fragment, it is taking a document
    with it; the tests that matter assert what the reaper LEAVES. The sync
    client's marker is matched mid-name too, so
    `advies.pdf.ocTransferId873492.part` is not missed.
  - A DELETE THAT WAS REFUSED IS NOT COUNTED AS REMOVED, or the reclaimed bytes
    become a number nobody can check against the disk and a read-only mount
    reports a clean sweep every night for ever. Same for an unreadable
    timestamp: it is not reaped, because guessing "old enough" deletes
    somebody's upload while guessing "too new" costs one night of disk.
  - THE AGE IS DECLARED (`uploadFragmentMaxAgeHours`, default 24) WITH A FLOOR
    OF ONE HOUR. Zero is the one value an administrator can type that turns
    housekeeping into data loss, and "0 means no limit" is a common enough
    convention that somebody will try it; it is raised, not honoured.
- [~] 5.3 Validate an external mount on setup against the upload policy and name the parts it cannot enforce (REQ-CDF-06)
  - BUILT: `ExternalMountValidator` over a narrow `MountCapabilityProbe` seam,
    reporting per requirement rather than per capability.
  - 🔴 IT NAMES THE REQUIREMENT, NOT THE PROPERTY. The requirement asks setup to
    name "the reconciliation requirement it cannot meet", and a line reading
    `supportsPerGroupPermissions: false` hands an administrator a field name
    instead of a consequence. Every finding carries the sentence somebody acts
    on: a group removed from the domain keeps its access.
  - 🔴 EVERY ANSWER IS THREE-VALUED. `null` is `unknown` and is reported apart
    from `cannot`: a mount that answers no is one filinq can describe, a mount
    that answers nothing is one where the first sign of trouble is a group that
    still reaches a folder. Folding unknown into either loses exactly that.
  - THE UPLOAD POLICY IS READ, NOT ASSUMED. Bypassable writes are reported only
    when a policy is declared; otherwise it would be a warning about a rule
    nobody wrote. A policy that could not be READ counts as present, because
    dropping the finding is the one silence this check exists to break.
  - 🔑 WHAT WAITS, AND WHY IT IS A SEAM RATHER THAN AN OVERSIGHT: the probe over
    a real mount. `OCP\Files\Mount\IMountPoint` and `OCP\Files\Storage\IStorage`
    DO NOT EXIST in this repository's test environment, measured with
    `interface_exists()` under `tests/bootstrap-unit.php`, where
    `OCP\Files\IRootFolder` returns true and both of those return false. An
    adapter written here could be typed but neither run nor tested, and the
    setup route that called it would 500 on a probe nothing provides. Same
    seam, same reason, as the Nextcloud half of `DomainFolderGateway` in 3.1.
  - ALSO NOT BUILT: the setup surface itself, and desktop editing / resumable
    upload, which the requirement says MUST be verified against the case folder
    and MUST NOT be reimplemented in filinq. Verifying them is an instance test,
    not code here.

## 6. Quality

- [x] 6.1 PHPUnit inside the container for the flat list, reconciliation, the policy and the domains; 75% on new code (ADR-009)
- [x] 6.2 Playwright `tests/e2e/workflows/case-documents.spec.ts` covers the flat list, its filter, the domains and the refused upload; desktop editing, the strings and the feature docs are still open

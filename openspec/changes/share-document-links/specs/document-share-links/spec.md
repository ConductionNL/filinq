# document-share-links Specification (delta)

## Purpose

A clerk shares one or several documents with an outside party through one
link that runs out, and can take it back. filinq records what left, by
whom and until when. Nextcloud serves the link. Matrix rows `sh-bundle`,
`sh-link` and `sh-revoke` (filinq), demand
https://github.com/paperless-ngx/paperless-ngx/pull/11682.

## ADDED Requirements

### Requirement: A clerk shares one document through a link (REQ-SDL-001)

Filinq MUST offer "Share by link" on a document in My documents. It MUST
create a Nextcloud public link on that file with read permission only and
MUST show the link for the clerk to copy. The share MUST be made as the
session user. Nextcloud's refusal, when the clerk may not share the file,
MUST be shown in the dialog and MUST leave no share and no record.

Rows: `sh-link` (filinq matrix)

#### Scenario: A clerk sends one decision to an advisor

- GIVEN a clerk on `/my-documents` with `besluit-2026-114.pdf`
- WHEN the clerk chooses "Share by link", keeps the expiry at 14 days and confirms
- THEN the dialog shows a link, and a logged-out browser opening it gets the PDF
- @e2e tests/e2e/document-share-links.spec.ts

#### Scenario: A file the clerk may not share

- GIVEN a document shared with the clerk without reshare permission
- WHEN the clerk tries to share it by link
- THEN the dialog shows Nextcloud's reason and no link or share record exists
- @e2e tests/e2e/document-share-links.spec.ts

### Requirement: Several documents leave as one archive with a manifest (REQ-SDL-002)

When two or more documents are selected, filinq MUST build one archive of
them through `CaseArchiveService`, with a manifest inside listing every
file included and every file left out with its reason, MUST record it as an
`archiveJob` with its file id, and MUST share the archive through one link.
Before building, the dialog MUST say how many files and bytes the bundle
holds and whether it goes over the ceiling. A failed job write MUST stop
the share.

Rows: `sh-bundle` (filinq matrix)

#### Scenario: Four documents for the bezwaarcommissie

- GIVEN a clerk in bulk selection on `/my-documents` with four documents selected
- WHEN the clerk chooses "Share by link", names the bundle "Bezwaar 2026-031" and confirms
- THEN one link opens `Bezwaar 2026-031.zip` holding the four documents and `manifest.json`
- @e2e tests/e2e/document-share-links.spec.ts

#### Scenario: Over the ceiling, told before

- GIVEN a selection larger than the ceiling
- WHEN the clerk opens the dialog
- THEN the dialog says the bundle goes over the ceiling and names what would be left out, before anything is built
- @e2e tests/e2e/document-share-links.spec.ts

### Requirement: Every link runs out (REQ-SDL-003)

Every link MUST carry an expiry date, 14 days ahead by default. A share
without an expiry MUST be refused. When Nextcloud's sharing settings
enforce a shorter maximum or a link password, filinq MUST apply them.

Rows: `sh-link`, `sh-bundle` (filinq matrix)

#### Scenario: The enforced maximum wins

- GIVEN Nextcloud enforces link expiry at 7 days
- WHEN a clerk picks an expiry 30 days ahead
- THEN the dialog sets it to 7 days ahead and says why
- @e2e tests/e2e/document-share-links.spec.ts

### Requirement: A link can be taken back, and closes when it runs out (REQ-SDL-004)

The "Shared links" page MUST list the clerk's shares with their state and
expiry. "Take back" MUST delete the Nextcloud share, delete a bundle's
archive, and move the record to `revoked` with the person and the moment.
Only the sharer or an admin MAY take a share back. A timed job MUST move a
share past its expiry to `expired` and delete its archive, and MUST move a
share deleted in Files to `revoked`.

Rows: `sh-revoke` (filinq matrix)

#### Scenario: A clerk takes a link back

- GIVEN a clerk with an active link on the Shared links page
- WHEN the clerk chooses "Take back"
- THEN the row reads "Taken back" with the clerk's name, and the link no longer opens
- @e2e tests/e2e/document-share-links.spec.ts

#### Scenario: Somebody else's link

- GIVEN a second employee who did not make the share
- WHEN that employee sends a take-back for it to `DELETE api/shares/{id}`
- THEN the answer is 403 and the link still opens
- @e2e exclude API authorization; covered by Newman in task 2.3

#### Scenario: A link runs out

- GIVEN a bundle link whose expiry has passed
- WHEN the timed job runs
- THEN the record reads "Expired" and the archive is gone from `Shared bundles`
- @e2e exclude timed job; covered by PHPUnit with a clock in task 2.5

### Requirement: The record says what left, not how to open it (REQ-SDL-005)

Every share MUST be recorded as a `documentShare` in OpenRegister, naming
the kind, each file with its version, the Nextcloud share id, the
`archiveJob` for a bundle, the sharer, the moment, the expiry and the
note. The record MUST NOT hold the link's token or URL. A download through
the link is recorded by `documents-in-and-out-of-the-building`
(REQ-DIO-06) with the share id as the link, and this capability MUST NOT
record downloads again.

Rows: `sh-bundle`, `sh-link`, `sh-revoke` (filinq matrix)

#### Scenario: An archivist reads what left

- GIVEN a document shared by link last month and taken back a week later
- WHEN an archivist opens the Shared links page as admin
- THEN the row names the file and its version, the sharer, the expiry and who took it back, and shows no link
- @e2e tests/e2e/document-share-links.spec.ts

### Requirement: A labelled document is shared only after a confirmation (REQ-SDL-006)

When a file carries a confidentiality label, the dialog MUST name the
label and MUST NOT share until the clerk confirms. The label MUST be
written on the share record.

Rows: `sh-link`, `sh-bundle` (filinq matrix)

#### Scenario: A confidential file needs a confirmation

- GIVEN a document labelled "Confidential"
- WHEN a clerk opens "Share by link" on it
- THEN the dialog says the document is labelled "Confidential" and the share button stays disabled until the clerk ticks the confirmation
- @e2e tests/e2e/document-share-links.spec.ts

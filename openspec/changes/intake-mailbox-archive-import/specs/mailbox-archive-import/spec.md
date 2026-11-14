# mailbox-archive-import Specification (delta)

## Purpose

A mailbox archive (MBOX, PST or a ZIP of `.eml` files) is split into
messages and each message is filed to a dossier or sent to the intake
inbox, once, in resumable steps. Matrix row `in-mail-archive` (filinq).
Builds on the per-message filing of open change `email-ingestion`.

## ADDED Requirements

### Requirement: An archivist starts an import from a file in Nextcloud (REQ-MAI-001)

filinq MUST let a user start an import from an MBOX, PST or ZIP file they
can read in Nextcloud Files, recorded as a `mailArchiveImport` object with a
lifecycle (`received`, `mapping`, `importing`, `done`, `failed`,
`cancelled`), counts and a list of failures, and MUST let the user cancel a
running import.

Rows: `in-mail-archive` (filinq matrix)

#### Scenario: An archivist imports a departing official's mailbox

- GIVEN an archivist with `mailbox-jansen.mbox` in their Files
- WHEN the archivist opens the mail archive import page, picks the file and starts
- THEN an import appears in `mapping` listing the folders found in the file
- @e2e tests/e2e/mailbox-archive-import.spec.ts

### Requirement: The container format is detected and split on the server (REQ-MAI-002)

filinq MUST split MBOX and ZIP archives itself and MUST convert a PST with
the `readpst` tool on the server. When `readpst` is not installed a PST
import MUST fail at once with "readpst is not installed on this server",
and the admin settings MUST show the tool's status. No message MUST be sent
to an external service.

#### Scenario: A PST without the tool

- GIVEN a server without `readpst`
- WHEN an archivist starts an import of `archief.pst`
- THEN the import is `failed` with the message "readpst is not installed on this server"
- @e2e tests/e2e/mailbox-archive-import.spec.ts

### Requirement: Mapped folders go to dossiers, the rest to the intake inbox (REQ-MAI-003)

Before importing, the archivist MUST be able to map each archive folder to
a dossier they may write to. A message in a mapped folder MUST be filed to
that dossier the way `email-ingestion` files a message. A message in an
unmapped folder MUST arrive in the intake inbox with channel `mail`.

#### Scenario: One folder filed, one folder to the inbox

- GIVEN an import in `mapping` with folders "Projecten/Brug" and "Inbox"
- WHEN the archivist maps "Projecten/Brug" to the dossier "Vervanging brug Zuid" and starts the import
- THEN after the job runs, the mails of "Projecten/Brug" are in that dossier and the mails of "Inbox" wait in `/intake`
- @e2e tests/e2e/mailbox-archive-import.spec.ts

### Requirement: A large archive drains in resumable units (REQ-MAI-004)

filinq MUST process an import in bounded units per cron run, MUST save
where it stopped after each unit, and MUST resume from there after a
restart or crash.

#### Scenario: A crash does not start over

- GIVEN an import of 5,000 messages that stopped after 1,200
- WHEN the next cron run starts
- THEN it continues at message 1,201 and the counts go on from 1,200
- @e2e exclude cron work units are not drivable from the browser; covered by PHPUnit with a saved cursor

### Requirement: Nothing is filed twice (REQ-MAI-005)

A message already filed to the same dossier, or already in the intake inbox
under the same message id, MUST be counted as `skippedDuplicate` and MUST
NOT be filed or received again.

#### Scenario: The same archive imported twice

- GIVEN an archive that was imported to completion
- WHEN an archivist imports the same file again with the same mapping
- THEN the second import finishes with every message counted as a skipped duplicate and no new documents in the dossier
- @e2e tests/e2e/mailbox-archive-import.spec.ts

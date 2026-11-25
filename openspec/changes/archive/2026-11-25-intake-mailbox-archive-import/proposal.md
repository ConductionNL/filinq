---
kind: code
depends_on: [email-ingestion]
---

# Proposal: intake-mailbox-archive-import

Matrix row `in-mail-archive` in filinq's `openspec/parity/capabilities.json`,
rated no, built.state none. Written in the OpenSpec pass of 27 September
2026.

## Why

An organisation moves off a shared mailbox, or a departing official's
mailbox has to be kept. What IT hands over is one file: an MBOX export or
an Outlook PST with years of mail in folders. Today nobody can hand that
file to filinq. Open change `email-ingestion` takes one `.eml` at a time
from a watched folder and says so in its non-goals: "no PST/MBOX
bulk-archive splitter in v1 (the cluster's bulk import PST/MBOX story needs
a container-format decision first - deferred)"
(`openspec/changes/email-ingestion/design.md`, Non-Goals). Its open
questions name the same gap: "PST/MBOX bulk-archive splitting ... needs a
container-format decision; candidate for a later wave with
redaction-at-scale-style work units."

This change makes that decision and adds the splitter in front of the
ingestion path `email-ingestion` defines, so every message still becomes
one `emailDocument` filed the same way.

The row sits in intake, filinq's core area.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `in-mail-archive` | Import a mailbox archive in bulk and file the mails to dossiers. | no: `email-ingestion` is unbuilt and excludes PST and MBOX in v1 |

### Demand

None recorded.

### Competitors rated yes

None. Paperless-ngx is partial: "a mail rule can sweep an IMAP folder back
to maximum_age days ... .eml files dropped in the consume folder are
parsed. No mbox or pst import: grep -riE 'mbox|\.pst|maildir' src/ finds
nothing, and no dossier target"
(https://github.com/paperless-ngx/paperless-ngx, v3.2.1). Built for the
core-area rule.

## What changes

- An archivist picks an MBOX file, a PST file or a ZIP of `.eml` files in
  Nextcloud Files and starts an import from filinq.
- The import is a `mailArchiveImport` object with a lifecycle and counts.
  It splits the archive into single messages in bounded work units, so a
  ten-gigabyte archive drains over many cron runs.
- MBOX and ZIP are split in PHP. PST is converted with the `readpst` tool
  when it is installed; without it the import fails at once with a clear
  message, and the admin page shows the tool's status.
- The archivist maps the archive's mail folders to dossiers. Messages in a
  mapped folder are filed to that dossier through `email-ingestion`'s path.
  Messages in an unmapped folder go to the intake inbox with channel
  `mail`, to be assigned one by one.
- Every message is idempotent by content hash and message id, so running
  the same archive twice files nothing twice.
- The import page shows progress, per-folder counts and every message that
  failed with its reason.

## Capabilities

### New capabilities

- `mailbox-archive-import`: split an MBOX, PST or ZIP archive into messages
  and file them to dossiers or the intake inbox, resumably and without
  duplicates.

### Modified capabilities

None. `email-ingestion` keeps its watched folders and its per-message
filing; this change calls that filing for each split message.

## Impact

- `lib/Settings/filinq_register.json`: new `mailArchiveImport` schema with
  lifecycle; version bump and seed.
- New `lib/Service/MailArchiveSplitter.php` (MBOX, ZIP, PST through
  `readpst`), `lib/Service/MailArchiveImportService.php`, and a cron job
  that processes one work unit per run.
- A route to start, read and cancel an import.
- `src/views/`: an import page with the folder-to-dossier mapping and
  progress, in the manifest.
- Admin settings: the `readpst` status line and the per-run message limit.

## Cross-app dependencies

None. Mailbox connectivity stays with integriq as `email-ingestion` D5
decides; this change reads a file the user already has in Nextcloud.

## Out of scope

- Native `.msg` parsing. A `.msg` inside a ZIP fails visibly, as
  `email-ingestion` D6 does.
- Deduplication of the same message across dossiers (per `email-ingestion`).
- Reading a live mailbox.

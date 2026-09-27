# Design: intake-mailbox-archive-import

Kind: code. One schema, one splitter, one job, one page.

## Context

Read at development `2088cc1f`.

- Open change `email-ingestion` (0 of 11 tasks done) defines
  `emailDocument` (source `.eml`, PDF/A derivative, dossier ref, envelope,
  threading metadata, `contentHash`, status `received`, `filed`, `failed`),
  `EmailIngestionJob` as a bounded cron scan (D2, `files_per_tick`
  default 25), idempotency by content hash or message id plus dossier (D2),
  file first and convert second (D3), `.msg` as a visible failure (D6).
  None of it is in `lib/` yet: `emailDocument` is not in
  `lib/Settings/filinq_register.json`.
- `lib/Service/IntakeService.php:95` `receive()` accepts channel `mail`
  (`lib/Event/IntakeDocumentReceivedEvent.php:71`) and dedupes on
  `sourceRef` (:104-117); `receiveMessage()` (:173) files a message and
  its attachments as linked intake documents (`arrivedWith`).
- `lib/Service/OcrService.php:125` probes a binary with `exec(... --version)`,
  the pattern for `readpst`.
- openregister `lib/Service/TextExtraction/EmlParser.php` parses one RFC
  5322 message.
- `dossier` schema in the register; `DossierManagementService` resolves a
  dossier's bound folder.

New: `mailArchiveImport`, the splitter, the import service, the job, the
page.

## Goals / Non-goals

Goals: one archive in, every message filed once, resumable after a crash,
visible failures.

Non-goals: `.msg`, live mailboxes, cross-dossier dedupe, reading the
archive's calendar or contacts items.

## Decisions

### D1. The container-format decision

| format | how | why |
|---|---|---|
| MBOX (mboxrd and mboxo) | streamed in PHP, split on `From ` lines at line start, unescaped `>From` | a text format; no dependency; streaming keeps memory flat |
| ZIP of `.eml` | PHP `ZipArchive`, one entry per message | exports from many clients look like this |
| PST | `readpst -e -o <tmp>` into one `.eml` per message, then as a ZIP | no maintained PHP PST reader; `readpst` (libpst) is packaged in every distribution |

Alternative considered: converting PST in openregister. Rejected for now:
openregister has no PST reader either, and a binary probe here is the same
pattern filinq already uses for Tesseract. When openregister gains one,
this splitter calls it instead.

### D2. `mailArchiveImport`

| property | type | notes |
|---|---|---|
| `file` | integer | the archive's Nextcloud file id |
| `format` | enum `mbox`, `zip`, `pst` | detected, not chosen |
| `folderMap[]` | `{archiveFolder, dossier}` | set by the archivist |
| `cursor` | object | where the splitter stopped: byte offset (MBOX), entry index (ZIP) |
| `counts` | `{found, filed, toInbox, skippedDuplicate, failed}` | updated per work unit |
| `failures[]` | `{messageId, archiveFolder, reason}` | every failed message |
| `status` | lifecycle `received`, `mapping`, `importing`, `done`, `failed`, `cancelled` | `x-openregister-lifecycle` |
| `requestedBy` | string | the session user |

### D3. Bounded work units under cron

A `TimedJob` takes the oldest `importing` import and processes at most
`filinq.mail_archive.messages_per_run` messages (default 200), then saves
`cursor` and `counts`. A crash resumes from the saved cursor; idempotency
(D5) absorbs the last half-processed unit. Alternative considered: one
`QueuedJob` per archive. Rejected: a long run blocks the cron slot and a
crash loses the position.

### D4. Mapped folders to dossiers, the rest to the inbox

The splitter lists the archive's folders first (status `mapping`) so the
archivist can map each to a dossier. A message in a mapped folder goes
through `email-ingestion`'s filing (the `emailDocument` path, file first,
convert second). A message in an unmapped folder goes to
`IntakeService::receiveMessage()` with channel `mail` and `sourceRef` set
to its message id. Alternative considered: requiring a dossier for every
folder. Rejected: a real mailbox has an "Inbox" of mixed mail that only a
person can sort.

### D5. Idempotency

For filing: `email-ingestion`'s rule (content hash, or message id plus
dossier). For the inbox: the existing `sourceRef` skip. A second import of
the same archive counts every message as `skippedDuplicate`.

### D6. Who may import

An import reads the archive with the session user's file access and writes
to dossiers the user may write to; a folder mapped to a dossier the user
cannot write to is refused at mapping time. Admin-only is not required: an
archivist who holds the file and the dossiers may import.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| import status | declarative lifecycle on `mailArchiveImport` | read by the page, guarded transitions |
| splitting and filing | imperative job | file parsing in bounded units |
| finished notice | declarative `x-openregister-notifications` on reaching `done` or `failed`, to `requestedBy` | the person who started it is told, no code |

## Seed data

One `mailArchiveImport` in `done` with counts and two failures (one `.msg`,
one unreadable message), and one in `mapping` with two folders listed.

## Risks / trade-offs

- Very large archives. Bounded units and a resumable cursor; the page shows
  an estimate from the file size.
- A PST with thousands of folders. The mapping list is searchable and
  unmapped folders simply go to the inbox.
- `readpst` output layout differs per version. The splitter reads every
  `.eml` under the output directory recursively and takes the folder from
  the relative path.

## Open questions

- Should attachments of an inbox-bound message become their own intake
  documents, as `receiveMessage()` does for mail today? Default: yes, the
  same path.

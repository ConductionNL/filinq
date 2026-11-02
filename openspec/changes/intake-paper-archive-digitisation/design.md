# Design: intake-paper-archive-digitisation

Kind: code. Two schemas, one separator variant, one filing branch, one page.

## Context

Read at development `2088cc1f`.

- `scanBatch` in `lib/Settings/filinq_register.json`: `file`, `scannerId`,
  `receivedAt`, `separatorMode`, `pageCount`, `segments`, `lastError`,
  `status`.
- `lib/Service/ScanBatchService.php`: `receive(File, profile)` (:137),
  `split(batch, profile, file, target)` (:162), `segmentsOf(pages, mode,
  profile)` (:238); each segment dispatches `IntakeDocumentReceivedEvent`
  with channel `scan` (spec REQ-SCI-03).
- `lib/Service/SeparatorSheetService.php` renders sheets with payload
  `filinq:sep:v1:<profileId>[:<caseNumber>]` (REQ-SCI-02 of open change
  `scan-intake-with-separator-sheets`). Routes `scanIntake#separators`,
  `listProfiles`, `declareProfiles`, `split`
  (`appinfo/routes.php:119-122`).
- `lib/Service/ScanProfileService.php` holds profiles in app config
  (`all()` :86, `find()` :127, `forPath()` :150).
- Open change `inbound-documents-and-the-worklist` REQ-IDW-06 writes OCR
  text to a document's searchable content; `lib/Service/OcrService.php`
  runs Tesseract locally.
- `dossier` schema and `DossierManagementService` bind a dossier to a home
  folder.

New: `digitisationProject`, `paperOriginal`, the v2 payload, the filing
branch, `DigitisationProjectService`, the pages.

## Goals / Non-goals

Goals: every paper original has a record; every scan knows its original;
the archivist sees progress per box; a sampled share is checked by a
person.

Non-goals: substitution and destruction of paper, image enhancement,
handwriting.

## Decisions

### D1. Two schemas

`digitisationProject`: `name`, `archiveDescription`, `boxes[]`,
`scanProfile`, `target` (`{register, schema, id}` of the record the scans
are filed on, typically a dossier), `checkShare` (0 to 100, default 10),
`status` lifecycle `open`, `scanning`, `checking`, `closed`.

`paperOriginal`: `project`, `box`, `shelfMark`, `description`,
`dateRange`, `scanBatch`, `document` (file id of the scan), `scannedAt`,
`check` (`{result: passed|rejected, checkedBy, checkedAt, note}`),
`status` lifecycle `registered`, `scanned`, `checked`, `rejected`
(`rejected` goes back to `registered` on rescan).

Alternative considered: one property on `scanBatch`. Rejected: a batch
holds many originals and an original may be rescanned into a new batch.

### D2. The separator names the original

Payload `filinq:sep:v2:<profileId>:po:<paperOriginalUuid>`. The v1 payload
stays valid. The sheet also prints the shelf mark and description so a
person can check it before it goes into the scanner.

### D3. A project segment bypasses the inbox

In `ScanBatchService::split()`, a segment whose separator carries `po:`
is written to the project target's folder, gets the original's metadata
(shelf mark, description, date range) and sets the original to `scanned`.
A segment without it goes to the inbox as today. OCR runs through the
existing path on the filed file. Alternative considered: letting the
segment arrive in the inbox and pre-filling the assignment. Rejected: four
hundred boxes of assignments by hand is the work the project exists to
avoid.

### D4. Sampling

When an original is scanned, `DigitisationProjectService` draws it into
the checking queue with probability `checkShare`, and always when the
separator was unreadable or the page count is zero. An original not drawn
is `checked` with `result: passed` and `checkedBy: sample-not-drawn`, so the
record says it was not looked at.

### D5. CSV box lists

An archivist imports a box list as CSV (`box`, `shelfMark`, `description`,
`dateFrom`, `dateTo`), through openregister's object import where the
columns map one to one. Rows that fail are listed with their reason.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| project and original status | declarative lifecycles | guarded transitions, read by the pages |
| progress counts per box | declarative aggregation on `paperOriginal` grouped by `box` and `status` | no counters in filinq |
| filing a segment | imperative branch in `ScanBatchService` | a file operation |
| sampling | imperative service | a random draw per original |

## Seed data

One project "Bouwvergunningen 1970-2000" with two boxes, five originals in
every status, and a checking queue of one.

## Risks / trade-offs

- A separator placed before the wrong original. The printed shelf mark lets
  the scanner operator catch it; a checker who finds a mismatch rejects the
  scan with a note.
- A 10 percent default sample may be too low for a substitution decision.
  The share is per project, and the substitution rules belong to
  openregister.

## Open questions

- Should a closed project hand its checked originals to openregister's
  archiving process automatically? Left to openregister's half.

# paper-archive-digitisation Specification (delta)

## Purpose

A paper archive is digitised box by box. Every paper original has a
record, every scan knows which original it copies and is filed on the
project's record, and each original is marked scanned and then checked.
Matrix row `in-paper-archive` (filinq). What happens to the paper after
that is openregister's archiving process.

## ADDED Requirements

### Requirement: An archivist runs a digitisation project (REQ-PAD-001)

filinq MUST let an archivist create a `digitisationProject` naming the
archive, its boxes, a scan profile, the record the scans are filed on and
the share to check, and MUST let them register `paperOriginal` objects per
box by hand or from a CSV box list. The project page MUST show per box how
many originals are registered, scanned, checked and rejected.

Rows: `in-paper-archive` (filinq matrix)

#### Scenario: An archivist loads a box list

- GIVEN an archivist with a project "Bouwvergunningen 1970-2000"
- WHEN the archivist imports a CSV listing 40 originals for box 117
- THEN the project page shows box 117 with 40 registered originals
- @e2e tests/e2e/paper-archive-digitisation.spec.ts

### Requirement: A separator sheet names the original it precedes (REQ-PAD-002)

filinq MUST print one separator sheet per paper original with a QR code
payload `filinq:sep:v2:<profileId>:po:<uuid>` and the shelf mark and
description in print. The splitter MUST keep reading the v1 payload.

#### Scenario: A scan operator prints the sheets for a box

- GIVEN box 117 with 40 registered originals
- WHEN the scan operator chooses "Print separators for this box"
- THEN a PDF of 40 pages is produced, each showing one original's shelf mark and a QR code naming it
- @e2e tests/e2e/paper-archive-digitisation.spec.ts

### Requirement: A scan of an original is filed on the project's record (REQ-PAD-003)

A segment whose separator names a paper original MUST be filed in the
project target's folder with the original's shelf mark, description and
date range, MUST NOT enter the intake inbox, and MUST set the original to
`scanned` with the file and the batch. A segment without such a separator
MUST go to the intake inbox as before.

#### Scenario: A scanned box lands on the dossier

- GIVEN a batch of box 117 scanned with its separator sheets
- WHEN the batch is split
- THEN 40 documents are in the dossier "Bouwvergunningen 1970-2000", each carrying its shelf mark, and none waits in `/intake`
- @e2e exclude batch splitting runs in the background; covered by PHPUnit on `ScanBatchService::split()` with a fixture batch

### Requirement: A sampled share is checked by a person (REQ-PAD-004)

When an original is scanned, filinq MUST put it in the checking queue with
the project's check share, and always when the separator was unreadable. A
checker MUST pass it or reject it with a note; a rejected original MUST go
back to `registered` for a rescan. An original not drawn MUST be recorded as
passed with `checkedBy` set to `sample-not-drawn`.

#### Scenario: A checker sends a scan back

- GIVEN a scanned original in the checking queue whose second page is cut off
- WHEN the checker compares it with the paper and rejects it with the note "page 2 cut off"
- THEN the original reads `registered` again with the note, and box 117 counts one rejected
- @e2e tests/e2e/paper-archive-digitisation.spec.ts

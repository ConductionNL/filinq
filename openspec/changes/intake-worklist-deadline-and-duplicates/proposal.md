---
kind: code
depends_on: [document-intake-inbox]
---

# Proposal: intake-worklist-deadline-and-duplicates

Matrix rows `in-deadline` and `in-duplicate` in filinq's
`openspec/parity/capabilities.json`. `in-deadline` is rated no with
built.state none; `in-duplicate` is rated no with built.state built (only
the re-delivery skip is built). Written in the OpenSpec pass of 27
September 2026.

## Why

A registrar opens `/intake` and sees what is waiting, oldest first by the
received column. Two questions have no answer there.

- **What is late?** Nothing records how long a document may wait. A
  search of the intake services and `lib/Settings/filinq_register.json`
  for `handlingDeadline`, `afhandeltermijn` or `overdue` finds nothing.
  `ObjectionDeadlineChecker` exists, but it watches Woo objection periods
  on consent records, not incoming post.
- **Did this arrive before?** `IntakeService::receive()`
  (`lib/Service/IntakeService.php:104-117`) quietly keeps the first
  document when a channel delivers the same `sourceRef` again. That is
  right. But the same letter scanned on Monday and mailed on Tuesday has two
  source references and becomes two waiting documents, and nothing says
  they are the same file.

Both answers belong on the same worklist row, so one change covers both.
Both rows sit in intake, filinq's core area.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `in-deadline` | Set a handling deadline on incoming post and see what is overdue. | no: no deadline field, no overdue view |
| `in-duplicate` | Get warned when an incoming document duplicates one already registered. | no: only the silent `sourceRef` re-delivery skip; no content-level check, no warning in `IntakeIndex.vue` |

### Demand

None recorded on either row.

### Competitors rated yes

- Paperless-ngx on `in-duplicate` (source read at v3.2.1): "src/documents/serialisers.py:1085
  get_duplicate_documents by checksum; task result flags duplicates
  (serialisers.py:2762); document detail Duplicates tab
  (document-detail.component.ts:147) ... Exact content matches only."
  Evidence: https://github.com/paperless-ngx/paperless-ngx (v3.2.1)
- Nobody rates `in-deadline` yes. Paperless-ngx is partial: "a date custom
  field can hold a deadline and saved views filter on it ... No built-in
  handling deadline or overdue list".

## What changes

- Every waiting document gets a `handlingDeadline`. An intake default rule
  can set a handling period in working days, counted from `receivedAt`. A
  registrar can change the deadline on the document, with a reason.
- The rule also says when a document counts as handled: when it is
  assigned to a record, or when an outgoing letter answers it through the
  post register of `documents-in-and-out-of-the-building`.
- The worklist gets a deadline column that reads "3 days left", "due
  today" or "5 days overdue", and a third mode, "Overdue", next to Waiting
  and Taken off a record.
- Every arriving file gets a `contentHash` (SHA-256 of its bytes). When the
  hash matches another intake document, filinq sets `duplicateOf` on the
  newcomer. The row shows "Possible duplicate" with a link to the first
  one. The registrar keeps both or rejects the newcomer with the reason
  "duplicate of" and the first one's subject.

## Capabilities

### New capabilities

- `intake-worklist-signals`: a handling deadline with an overdue view, and a
  warning for a document whose content arrived before.

### Modified capabilities

None. The `sourceRef` re-delivery skip of `document-intake-inbox` stays as
it is.

## Impact

- `lib/Settings/filinq_register.json`: `intakeDocument` gains
  `handlingDeadline`, `deadlineReason`, `contentHash`, `duplicateOf` and a
  read-time computed `daysLeft`; `intakeDefaultRule` gains
  `handlingDays` and `handledWhen`. Version bump and seed.
- `lib/Service/IntakeService.php`: hash the file and look up an earlier
  intake document with the same hash before saving; set the deadline from
  the matched rule.
- `lib/Service/IntakeRepository.php`: `findOverdue()` and
  `findByContentHash()`.
- `src/views/intake/IntakeIndex.vue`: the deadline column, the Overdue
  mode, the duplicate badge and a "Change deadline" row action.
- `docs/features/`: a section with screenshots.

## Out of scope

- Near-duplicates (the same letter scanned twice gives different bytes).
  Exact content only, as Paperless-ngx does.
- Notifications about overdue post. The Overdue mode is the list; a digest
  can follow once somebody asks for it.
- Deadlines on the case. Once a document is on a record, the record's own
  deadlines apply.

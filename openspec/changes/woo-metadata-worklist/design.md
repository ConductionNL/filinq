# Design: woo-metadata-worklist

Kind: code. One calculated field, one filter, one bulk action, one setting.

## Context

Read at development `2088cc1f`. Everything named here from the pipeline is
designed in open change `woo-publicatie-pipeline` (0 of 16 tasks done) and
not yet in `lib/`:

- `publicationRecord` with a DiWoo block `wooCategory`, `documentsoort`,
  `publisher`, `officieleTitel`, `creatiedatum`, `publicatiedatum`
  (`design.md` D1), and a lifecycle on `status`.
- `publicationLogEntry`, append-only, with action `metadata_assembled`.
- REQ-DDWPP-004: handoff refused while a mandatory DiWoo field is missing;
  the Woo category is chosen from opencatalogi's 17 TOOI informatiecategorieen.
- The publications index and detail as manifest pages over `CnIndexPage`
  (`design.md` D7).

In `lib/` today: `lib/Service/WooProfileService.php:40` holds only the
anonymisation entity profile. `x-openregister-calculations` is already used
in `lib/Settings/filinq_register.json` (`generatedDocument.validationStatus`
with a backend, `batchCorrespondenceJob.errorRate` with an expression).

New: `diwooMissing`, the filter, the bulk action, the default publisher.

## Goals / Non-goals

Goals: the gap is visible per record and across the queue; filling the
same value on many records takes one action; every record's log says who
set what.

Non-goals: automatic categorisation, new DiWoo fields.

## Decisions

### D1. The gap is a calculation

`diwooMissing` is an `x-openregister-calculations` entry with backend
`filinq.diwoo` returning the names of the empty mandatory fields, so the
same list drives the column, the filter and the handoff refusal. The
filter is `diwooMissing` not empty. Alternative considered: a stored flag
written on save. Rejected: a field emptied by a later edit would leave the
flag wrong.

### D2. Bulk update through the service

`PublicationPipelineService::updateMetadata(records[], values)` writes the
given fields on every selected record the user may edit, and one
`publicationLogEntry` per record. A record in a state past handoff is
skipped and reported. Alternative considered: openregister's generic mass
edit. Rejected: it would not write the pipeline's log entries.

### D3. Default publisher

Admin setting `woo.default_publisher` (a TOOI organisatie URI) prefills
`publisher` on new records. It is only a default; the record keeps what the
coordinator sets.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| missing fields | declarative calculation | one source for column, filter and refusal |
| bulk update | imperative service | writes the log per record |
| default publisher | admin setting read at create | a single value per organisation |

## Seed data

Add to the pipeline's seed: three publication records, one complete, one
missing `wooCategory`, one missing `publisher` and `documentsoort`.

## Risks / trade-offs

- A wrong bulk value on forty records. Each record has its own log entry, so
  the change can be read back and undone per record.

## Open questions

- None. VPB-02 asks for the overview and completion, not for automation.

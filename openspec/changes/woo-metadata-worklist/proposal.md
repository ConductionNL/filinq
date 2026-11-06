---
kind: code
depends_on: [woo-publicatie-pipeline]
---

# Proposal: woo-metadata-worklist

Matrix row `woo-metadata-gaps` in filinq's `openspec/parity/capabilities.json`,
rated no, built.state none. Written in the OpenSpec pass of 27 September
2026.

## Why

A Woo coordinator has two hundred documents queued for active publication.
The Woo index needs six DiWoo fields on each. Open change
`woo-publicatie-pipeline` makes the handoff refuse a record that lacks one,
"with a message naming the missing DiWoo fields" (REQ-DDWPP-004). That is
one record at a time, at the last step. Nobody sees across the queue which
records still miss what, and nobody can fill the same publisher or
documentsoort on forty records at once.

The tender that asks for it says so as a requirement of its own:
Dordrecht/Drechtsteden 407973, requirement VPB-02. The pipeline change
itself cites the same tender for its 224 requirements, but its spec covers
the refusal, not the overview.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `woo-metadata-gaps` | See which documents waiting for publication lack the metadata the Woo index needs, and fill it in. | no: `grep -ri 'diwoo\|informatiecategorie' lib src` finds nothing; the `publicationRecord` schema exists only in the design of `woo-publicatie-pipeline` (0 of 16 tasks done) |

### Demand

- tender: https://www.tenderned.nl/aankondigingen/overzicht/407973
  (requirement VPB-02)

### Competitors rated yes

None. The row is built for its tender demand.

## What changes

- Every publication record carries a derived list of the mandatory DiWoo
  fields it still misses.
- The publications index gets a "Metadata incomplete" filter and a column
  that names the missing fields.
- A Woo coordinator selects several records and sets one value on all of
  them: the Woo category, the documentsoort or the publisher. Each record
  gets its own log entry.
- An admin sets a default publisher (the organisation's TOOI URI) that new
  records start with.

## Capabilities

### New capabilities

- `woo-metadata-worklist`: see which queued publication records miss which
  DiWoo fields and fill them for a selection at once.

### Modified capabilities

None. REQ-DDWPP-004 keeps refusing the handoff; this change makes the gap
visible before that step.

## Impact

- `lib/Settings/filinq_register.json`: `publicationRecord` (added by
  `woo-publicatie-pipeline`) gains a calculated `diwooMissing`.
- `lib/Service/PublicationPipelineService.php` (new in the pipeline
  change): a bulk metadata update that writes one `publicationLogEntry` per
  record with action `metadata_assembled`.
- The publications index view: the filter, the column and the bulk action.
- Admin settings: the default publisher.

## Cross-app dependencies

- opencatalogi: keeps validating TOOI binding at sitemap render
  (WOO-TOOI-001/002). No new work is asked.

## Out of scope

- Guessing a category from the document's content. The coordinator chooses.
- Fields beyond the six mandatory DiWoo fields of REQ-DDWPP-004.

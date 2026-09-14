---
kind: umbrella
depends_on: []
---

# Proposal: competitor-parity-2026-09

The filinq half of the OpenSpec phase of the dossiq competitor parity
programme. Two sources of record, in order of writing: the gap register
at `procest/_gaps/` in ConductionNL/market-intelligence (2026-09-13), and
the round 4 discovery sweep at `procest/_round4/discovery/`
(`build-plan.md`, `candidates.json`, `decisions.md`,
`found-and-lacking.md`, 2026-09-14).

Ruben's rule, from the ownership rules: dossiq reaches 100% comparability
with the competition, and logic that belongs to another app is specified
in that app; dossiq consumes it. The build plan moves 50 of the 631
candidates to filinq. This umbrella indexes what filinq writes for them.

Nothing here is implemented. Each indexed change carries its own
`proposal.md`, `design.md`, `specs/` and `tasks.md`.

## Wave 1, from the gap register

Opened by the small-owner lane and merged in filinq#1082.

| change | register row | size | dossiq consumer |
|---|---|---|---|
| `scan-intake-with-separator-sheets` | 1.6 | M | the split documents land in the intake inbox and dossiq assigns them there |
| `merge-documents-to-pdf` | 4.14 | M | a Merge to PDF bulk action on the Files tab, in `documents-on-the-case` |

Row 1.4 was already covered by `document-intake-inbox` on `development`.

## Wave 2, from the discovery sweep

Size is the one each proposal states: S is a declaration or one guard, M
is a handful of tasks, L is a new mechanism.

| change | cluster or candidate | candidates | size | decision | dossiq consumer |
|---|---|---|---|---|---|
| `inbound-documents-and-the-worklist` | cluster 44 | 11 | L | D6, D21 | places the intake leaf, owns the promote-to-case act |
| `case-documents-and-the-flat-list` | cluster 21 | 11 | M | D6, D21 | places the flat-list leaf on its Files tab |
| `documents-from-a-template` | cluster 30 | 8 | M | D6, D21 | selects a layout per case type, places Download all as archive |
| `redaction-and-what-leaves-the-building` | cluster 57 | 4 | M | D6, D21 | places the review surface and reviews the proposal |
| `final-documents-frozen` | C-documents-5 | 1 | S | D6 | declares which case-type status makes a document final |
| `erase-a-person-while-the-records-stay` | C-access-and-privacy-22 | 1 | M | D6, D21, D7 | places the request and reviews the refusals |

Thirty-six candidates. The remaining fourteen of filinq's fifty are
cluster 43, which moves out; see below.

## What moved out: the archiving process

Cluster 43, "The archiving process, from nomination to destruction or
transfer", is sixteen candidates, twelve of them `must`, and the build
plan gave it to filinq with decision D7 pending.

**D7 is answered: the archiving process with sign-off lives in
openregister, not filinq.** So cluster 43 is not written here. filinq
keeps inbound document classification and document-level work, which is
what the six wave-2 changes above cover.

Two notes for whoever writes cluster 43 in openregister.

1. filinq's `archiefwet-retention-engine` on `development` already
   consumes OpenRegister's archival stack rather than rebuilding it: the
   per-object `retention` metadata, the schema-level `archive`
   configuration with selectielijst lookup, and the
   `DestructionCheckJob`, destruction list, approve or reject,
   `DestructionExecutionJob` and certificate pipeline behind
   `/api/archival/*`. What that change adds is municipality-side:
   selectielijst master data, the archive configuration on filinq's own
   schemas, the archivist screens, and destruction-date propagation into
   the publication pipeline. Cluster 43's process half belongs beside the
   pipeline, in openregister.
2. Two wave-2 changes here touch the same ground from the document end
   and stop at the boundary. `final-documents-frozen` freezes a document
   and says nothing about retention.
   `erase-a-person-while-the-records-stay` refuses a document under a
   retention obligation and names it for a person to decide.

## Build order

1. `inbound-documents-and-the-worklist`. Largest, and three changes on
   `development` already hold parts of it, so it unblocks the most and
   starts from the most.
2. `case-documents-and-the-flat-list`. Waits on nothing;
   `files-browser-columns` in nextcloud-vue is already on `development`
   and gives the flat list its columns.
3. `final-documents-frozen`. S, and two later changes read the final
   state it declares.
4. `documents-from-a-template`. Reads a saved view by slug once
   nextcloud-vue's `saved-view-tree-and-labels` lands, and names a view
   by id until then.
5. `redaction-and-what-leaves-the-building`. Assumes the shape of
   `anonymization-review-workbench` and `reversible-pseudonymization`,
   both open.
6. `erase-a-person-while-the-records-stay`. Reads the final state from 3
   and the mapping from `reversible-pseudonymization`.

Nothing in this list blocks anything outside filinq.

## The halves the consuming apps carry

Every change ends at the document. dossiq owns the case type, the
resultaattype, the promote-to-case act and the review of a redaction
proposal. opencatalogi owns publication and republication. integriq owns
the mail transport and the sender authentication under D12. openregister
owns detection, redaction, the record fields, delete and destroy, and
now the archiving process under D7.

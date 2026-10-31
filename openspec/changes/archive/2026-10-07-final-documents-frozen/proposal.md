---
kind: code
---

# Proposal: final-documents-frozen

Round 4 discovery candidate C-documents-5, "Final documents frozen
against change" (`procest/_round4/discovery/candidates.json` in
ConductionNL/market-intelligence, 2026-09-14). Rated `must`, marked a
matrix hole, three driven passers, dossiq `partial`. Named in the wave-2
brief as one of the top twenty-five.

The candidate sits in cluster 29, "Read-only, frozen and locked", whose
owner is openregister. Its other five members freeze a case, a phase or a
message. This one freezes a document, and a document is filinq's, so this
change is the document half and openregister keeps the rest.

## Why

The Archiefwet and a vastgesteld besluit both require that what was
published stays what was published. That is the candidate's own clause.
A besluit that can still be edited after it is taken is a besluit nobody
can prove the contents of, and the proof is what the archive is for.

Filinq versions documents (`DocumentVersionService`, the
`document-versions` spec) and records how a generated document was made
(`generated-document-names-its-template-version`). Nothing marks a
document final, and nothing refuses a write to one that is.

## The proving passers

- **Forgejo and Gitea** freeze a released artefact by protecting its tag:
  Repository Settings, Tags, tag protection, `/tag_protections`
  (`menu-tree.md`, `api.go`). The design decision worth copying is that
  protection is a rule about a name, evaluated at write time, not a flag
  somebody remembers to set.
- **OpenCase** freezes a document once it is declared final
  (`D-opencase-23`).

The candidate's consolidation note: "Both freeze the document once it is
declared final or published. matrix hole." There is no row in the corpus
to hold it, which is what makes it one of the 47 holes D6 closes.

## The decision it rests on

**D6**, relevance-led promotion. Every `must` candidate enters. This one
would have entered on its three driven passers anyway; what D6 settles is
that it also becomes a row, which it could not before because no row
existed to hold it.

## What filinq builds

- **A final state on a document version.** A version is draft or final.
  Final is set once, by a named person, at a recorded moment, with the
  reason or the act that made it final.
- **A refusal at every write path.** Content, metadata that changes
  meaning, and file replacement are all refused on a final version,
  server-side, so the API, the editors, the batch paths and the leaves
  all hit the same rule.
- **A new version instead.** Correcting a final document produces a new
  version that references the one it supersedes. The superseded version
  stays readable, which is the difference between a correction and a
  rewrite.
- **A declared rule, not only a flag.** A record type declares which
  states make its documents final, so a besluit freezes when the decision
  is taken without anybody pressing a button.
- **An honest exception, recorded.** An administrator may unfreeze, and
  doing so writes an audit entry naming the person, the moment and the
  reason, and marks the document as having been unfrozen. A product with
  no exception grows a workaround outside the product; a product with an
  unrecorded exception has no guarantee at all.

## How dossiq consumes it

dossiq declares, per case type and per resultaattype, which status makes
a document final. The besluit freezes when the decision is taken.
Retention and destruction after that are openregister's under D7, and a
frozen document is the state those act on.

## Existing specs it extends

`document-versions` (the version chain), `document-register` (the record
and its state), `document-editing` and `document-rich-editing` (the write
paths that must refuse), `generated-document-names-its-template-version`
(what a generated document already records) and `document-signing` (a
signed document is a common reason a version is final).

## ADRs

- ADR-001 and ADR-070: the version and its state are OpenRegister
  objects.
- ADR-031: the draft-to-final transition is a declared lifecycle, not a
  boolean somebody sets.
- ADR-008: the refusal lives in the service layer.

## Size

S. One lifecycle on an object that exists, one guard applied to the write
paths, one audited exception.

## Dependencies

None blocking. openregister's cluster 29 work freezes a case and a phase;
this change freezes a document and the two do not wait on each other.

## Out of scope

- Freezing a case, a phase or a message. openregister, cluster 29.
- Retention, destruction and transfer. Moved to openregister by D7.
- Preventing a file being copied out of Nextcloud. A frozen document is a
  refusal to change the record, not a rights management system.

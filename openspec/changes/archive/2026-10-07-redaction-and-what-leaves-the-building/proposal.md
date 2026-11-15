---
kind: code
---

# Proposal: redaction-and-what-leaves-the-building

Round 4 discovery cluster 57, "Redaction and what leaves the building"
(`procest/_round4/discovery/build-plan.md` in
ConductionNL/market-intelligence, 2026-09-14). Owner filinq, size M, no
dependency and no decision blocks it. The cluster's mechanism line:
extend filinq's redaction client and the anonymisation specs; dossiq
reviews the proposal.

## Why

The commonest and most serious Woo failure is a black rectangle drawn
over live text. Stating that the written copy is irreversible is the
capability, and so is the human review step between detection and output.
Filinq has both halves in pieces and states neither.

## Candidates

Four. Two are `must`.

| candidate | relevance | driven passers | dossiq |
|---|---|---|---|
| C-documents-40 redaction irreversible in the written copy | must | none, pinkroccade documented | partial |
| C-documents-37 automatic redaction proposals reviewed by the handler | must | none, pinkroccade documented | partial |
| C-decisions-5 publishable document composed from a saved search | should | none, youtrack documented | no |
| C-documents-38 download gated on an accepted agreement, recorded | could | tuleap | no |

The cluster reads 3 passers, 1 driven and 2 documented, proving system
tuleap. It is the smallest cluster filinq owns and it carries two of the
sharpest obligations.

## The decisions it rests on

- **D6**, relevance-led promotion. Both `must` members enter with no
  driven passer at all, which is exactly what the relevance-led bar was
  answered to allow. A Woo redaction obligation is not a comparison, it
  is a duty.
- **D21**, documented-only candidates admitted and labelled. Three of
  the four have no driven passer: C-documents-40, C-documents-37 and
  C-decisions-5. Each is marked documented wherever it appears and is
  never counted in a driven tally.
- **D17**, a broad market including MKB. A download gated on an accepted
  reuse condition is as much a commercial artefact as a municipal one.

## The proving passers

- **PinkRoccade iZaaksuite** is the documented claim on both `must`
  members, in iOpenbaar
  (`/proces-services/wet-open-overheid/`): the product marks what it
  found on every page, the handler adds or removes markings before the
  copy is written, and the redacted version is a new document from which
  the original cannot be recovered.
- **Tuleap** is the only driven passer in the cluster:
  `plugins/frs` with `/project/{id}/admin/license-agreements` gates a
  download on an accepted agreement.
- **YouTrack** composes a publishable document from a saved search,
  documented: Generate Release Notes, exporting a list of issues as an
  HTML page grouped by type, from a saved search.

## What filinq already has, verified at HEAD

This is an extension, not a rebuild.

- `AnonymizationService`, `DocumentAnonymizeRunner` and
  `AnonymisedPdfOutputService` run the redaction path.
- `AnonymizationPersistenceService` and the `anonymizationLink` schema
  record the source and anonymised pair, both facetable.
- `anonymization-review-workbench` (open) composes the preview, the
  entity table, selection-to-entity and a per-document checked gate.
- `anonymise-pdf-only-output-mode`, `image-redaction`,
  `accessible-redaction-output` and `pdfua-accessible-output` own the
  output shapes.
- `reversible-pseudonymization` (open) adds the encrypted reverse
  mapping, which is the one place where recovery is deliberate and gated.
- OpenRegister's `FileService::anonymizeDocument()` and
  `DocumentProcessingHandler::getLastPlaceholderMap()` do the redaction
  itself, per ADR-011.

What is missing is a stated, tested guarantee about the written copy, a
review gate that cannot be walked past, a composed publishable document,
and the accepted-agreement gate.

## What filinq builds

- **A stated and tested irreversibility guarantee.** The written copy
  contains no recoverable trace of what was removed: not as text under a
  rectangle, not in an embedded thumbnail, not in the XMP or EXIF
  metadata, not in an incremental PDF update, not in a revision history.
  Every one of those is checked by an automated test over the produced
  bytes, and the check is what the guarantee means.
- **A review gate that cannot be skipped.** Output is refused until a
  named person has marked the document checked. The refusal is
  server-side, so it holds for the API and the batch path as well as the
  screen. This is the fail-closed posture
  `anonymisation-fails-closed-without-a-detector` already establishes for
  the detector, applied to the human step.
- **A publishable document composed from a saved search.** A
  Woo-publicatielijst rendered over the records a named view returns,
  through the template path, with the redacted copies referenced and the
  originals never.
- **A download gated on an accepted agreement.** A declared agreement is
  shown before a file downloads, acceptance is recorded with who, when
  and which version, and an unaccepted download does not happen.

## How dossiq consumes it

dossiq places the review surface and reviews the proposal, which is the
cluster's own division of labour. The Woo-publicatielijst reads a dossiq
saved view. Publication itself is opencatalogi's. dossiq's own
`WOOAnonymisationAssistService` redacts a document for publication today
and is the surface this replaces, per ADR-075: one document channel for
the fleet.

## The capability it declares, and the specs it extends

The requirements are new behaviour rather than changes to existing
anonymisation requirements, so they land in a capability of their own,
`redaction-output-guarantee`, the way `index-page-map-view` was declared
beside `index-page`. It builds on `anonymization` and
`batch-anonymization` (the redaction path),
`anonymization-entity-review` (the entity decisions),
`anonymisation-grondslagen-summary` (what is recorded about why),
`anonymisation-prohibition-gate` (the fail-closed precedent),
`document-creatie-sjablonen` (the composed document) and
`entity-publication-policies` (what may be published).

## ADRs

- ADR-011: the detection and the redaction stay OpenRegister's; filinq
  orchestrates and records.
- ADR-075: one document channel for the fleet.
- ADR-001 and ADR-070: the agreement, the acceptance and the review gate
  are OpenRegister objects.
- ADR-066: the review surface reaches dossiq as a leaf.

## Size

M. Four candidates, one gate, one guarantee with its test suite, one
composed render and one agreement object, over a redaction path that
exists.

## Dependencies

None blocking. `anonymization-review-workbench` and
`reversible-pseudonymization` are open and this change assumes their
shape rather than restating it; if either lands first, the gate hangs off
the checked flag the workbench defines.

## Out of scope

- Detecting entities. OpenRegister's detection pipeline.
- Publishing the result. opencatalogi.
- Erasing a person across the product. Sibling change
  `erase-a-person-while-the-records-stay`.
- Recovering an original. `reversible-pseudonymization` owns the one
  gated path where that is allowed, and this change's guarantee is about
  the written copy, not about that mapping.

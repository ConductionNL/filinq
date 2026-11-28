---
kind: code
---

# Proposal: erase-a-person-while-the-records-stay

Round 4 discovery candidate C-access-and-privacy-22, "Erasure of one
person across the whole product"
(`procest/_round4/discovery/candidates.json` in
ConductionNL/market-intelligence, 2026-09-14). Rated `must`, one driven
passer (Zammad) and one documented (YouTrack, source `D-youtrack-2`),
dossiq `no`.

The candidate sits in cluster 38, "Erasure, export and the data
subject's own rights", whose owner is openregister, size L, decisions D10
and D22. The register erasure, the two-step delete and the data subject's
export are openregister's. What lives inside documents is filinq's, and
that is this change.

## Why

The candidate's clause is the sharpest sentence in the whole sweep:

> this is the Dutch tension in one feature: the Archiefwet forbids
> deleting the zaak and the AVG obliges you to erase the person

Both duties are real and neither yields. The only answer that satisfies
them is to remove the person from the records while the records stay.
That is anonymisation applied as a product operation over everything one
person touched, not as a file operation over one document at a time.

The candidate's consolidation note says both passers do exactly this:
"Both run erasure as a product operation over everything one person
touched."

## The proving passers

- **Zammad 7** is the driven passer, measured: data privacy tasks at
  `app/models/data_privacy_task.rb`, with `prepare_deletion_preview` at
  `:20`, `MAX_PREVIEW_TICKETS` at `:24` and `deletion_counts` at `:47`.
  The design decision to copy is the preview: the operator sees what an
  erasure will touch before it touches anything, and the preview is
  capped so producing it cannot itself become the incident.
- **YouTrack** is the documented passer, `D-youtrack-2`. Labelled
  documented under D21 and never counted in a driven tally.

## What dossiq and filinq have today

The candidate's own notes: "`WOOAnonymisationAssistService` redacts a
document for publication; nothing anonymises a person across the system"
and "zero hits for an erasure task". `WOOAnonymisationAssistService` is
dossiq's.

Filinq is the seed, verified at HEAD:

- `EntityDetectionService` and OpenRegister's text extraction find PERSON
  and ORGANIZATION occurrences per file, and the shared entity catalogue
  holds them.
- `AnonymizationService`, `DocumentAnonymizeRunner` and
  `BatchAnonymizeService` already anonymise one document and a batch.
- `anonymizationLink` records the source and anonymised pair, both
  facetable, which is how a written copy is found again.
- `reversible-pseudonymization` (open) adds the encrypted reverse
  mapping, the one gated path where a value can be recovered.
- `entity-search` finds the documents an entity appears in.

So the mechanism exists per document. What is missing is the subject: a
person, the list of documents they appear in, a preview of what an
erasure would touch, and one job that does it and proves it did.

## The decisions it rests on

- **D6**, relevance-led promotion. A `must` enters whatever the passer
  count, and this one has one driven passer.
- **D21**, documented-only admitted and labelled. The YouTrack half is
  documented and is never blended into a driven count.
- **D7**, the archiving process moves to openregister. That matters here
  because erasure and retention are the same argument from two ends: the
  record must stay for the Archiefwet and the person must go for the AVG.
  The retention side is openregister's; the removal of the person from
  document content is filinq's.
- **D10 and D22**, which govern the cluster: delete, restore and destroy,
  and who owns access compiled into the query. Both are openregister's
  and this change consumes them rather than restating them.

## What filinq builds

- **A subject erasure request.** A person, named by the identifiers the
  instance holds, with a legal ground and a requester.
- **A preview before anything is touched.** Every document the person
  appears in, how many occurrences in each, which of them are in final
  versions, which are under a retention obligation, and which cannot be
  anonymised and why. Capped, like Zammad's, and honest about what the
  cap hid.
- **One job that erases across the documents.** The occurrences are
  replaced through the existing anonymisation path, document by document,
  with progress, and a new version where a version is final, so the
  Archiefwet chain stays intact.
- **A certificate of what was done.** Which documents, which occurrences,
  when, by whom, on which ground, and what was refused with the reason. A
  data subject who asks for erasure is entitled to be told what happened,
  and an archivist is entitled to see the record stayed.
- **A refusal that is a decision.** A document under a retention
  obligation, a prohibition policy, or a legal hold is not erased. It is
  listed, with the obligation named, so somebody decides rather than the
  job deciding silently.
- **The reverse mapping is destroyed too.** Where
  `reversible-pseudonymization` holds an encrypted mapping for this
  person, the erasure destroys it. An erasure that leaves a way back is
  not an erasure.

## How dossiq consumes it

dossiq places the request and the preview, and reviews the refusals.
Erasing the person from the case's own fields, and the two-step delete
and destroy, are openregister's under D10. filinq answers one question
for both: what is inside the documents, and what happened to it.
opencatalogi republishes anything already published, which the
certificate lists.

## Existing specs it extends

`anonymization` and `batch-anonymization` (the path that does the work),
`anonymization-link` (the source and copy pairing),
`entity-search` (finding the documents a person appears in),
`entity-publication-policies` and `anonymisation-prohibition-gate` (what
may not be touched), `processing-activity-export` (the AVG Article 30
record) and `e-discovery-legal-hold` (the hold that refuses).

## ADRs

- ADR-011: the detection and the redaction stay OpenRegister's.
- ADR-001 and ADR-070: the request, the preview and the certificate are
  OpenRegister objects.
- ADR-075: one document channel for the fleet.
- ADR-066: the request surface reaches dossiq as a leaf.

## Size

M. One request object, one preview, one job and one certificate, over an
anonymisation path and an entity catalogue that both exist.

## Dependencies

`reversible-pseudonymization` for the mapping that must also be
destroyed; until it lands there is no mapping to destroy and the rule is
inert rather than wrong. openregister's delete, restore and destroy under
D10 for the record side.

## Out of scope

- Erasing the person from a record's own fields. openregister, under D10
  and D22.
- The data subject's own export. openregister, cluster 38.
- Destroying a record. Retention and destruction move to openregister by
  D7.
- Deciding whether an erasure is owed. That is a legal judgement a person
  makes, and the request records who made it and on what ground.

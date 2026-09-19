---
kind: code
---

# Proposal: inbound-documents-and-the-worklist

Round 4 discovery cluster 44, "Inbound documents, classification and the
document worklist" (`procest/_round4/discovery/build-plan.md` in
ConductionNL/market-intelligence, 2026-09-14). Owner filinq, size L,
depends on "mail intake that can be trusted", no decision blocks it. The
cluster's mechanism line: extend filinq's classification and OCR path;
dossiq owns the promote-to-case act.

## Why

A gemeente's postkamer receives a document before anybody knows which
case it belongs to. Eleven capabilities in this cluster describe what
happens between arrival and filing, and dossiq fails eight of them. The
loudest is the one that loses evidence: an aanvraag arrives with its
bijlagen and nothing promotes the attachment to a document on the case.

## Candidates

Eleven, all of them. Four are `must` and one is a matrix hole.

| candidate | relevance | driven passers | dossiq |
|---|---|---|---|
| C-documents-15 inbound attachment promoted to a case document | must, matrix hole | dimpact-zac, xxllnc-zaken | no |
| C-documents-3 worklist for documents detached from a case | must | dimpact-zac | no |
| C-intake-25 incoming document classified by the product, not typed by hand | must | none, visma-circle documented | partial |
| C-intake-32 party details extracted from the incoming document | must | none, visma-circle documented | no |
| C-documents-16 routing and acceptance of inbound documents, per case type | should | xxllnc-zaken | no |
| C-intake-28 default metadata stamped on every inbound document | should | opencase | partial |
| C-intake-47 inbound document inbox as a real case, inside the access model | should | opencase | no |
| C-documents-28 text recognition on scans and images, made searchable | should | none, atabix and youtrack documented | no |
| C-documents-35 process started by a document upload, per case type | should | valtimo | no |
| C-documents-30 filing into the document store from the contact centre screen | should | none, pinkroccade documented | no |
| C-intake-13 retro-digitisation of a paper archive, with OCR and metadata | should | none, decos-join documented | partial |

The cluster reads 9 passers, 4 driven and 5 documented, proving system
dimpact-zac.

## The decisions it rests on

- **D6**, relevance-led promotion. Every `must` enters whatever the
  passer count, which is what admits C-intake-25 and C-intake-32: both
  are `must`, both have a documented passer only, and both describe work
  an intake clerk does by hand all day.
- **D21**, documented-only candidates admitted and labelled. Five of the
  eleven have no driven passer: C-intake-25, C-intake-32, C-documents-28,
  C-documents-30 and C-intake-13. Each is marked documented wherever it
  appears and is never counted in a driven tally.
- **D17**, a broad market including MKB. Retro-digitisation and the
  contact centre screen are recorded rather than dropped for being
  municipal-sounding; a ten-person office scans post too.

## The proving passers

- **Dimpact ZAC** is both `must` members with a driven passer: Inbox
  productaanvragen (`productaanvraag-intake/spec.md`) promotes the
  arriving attachment, and Ontkoppelde documenten
  (`document-management/spec.md`) is the worklist for a document taken
  off a case.
- **xxllnc Zaken** routes and accepts an inbound document per case type
  (`document-management/spec.md`), and is the second passer on
  promotion.
- **OpenCase** is the inbox as a real record, inside the access model
  from arrival (`code-census.md`), and the default metadata on incoming
  documents (`Configuration-IncomingDocuments.md`).
- **Valtimo** names the process a file upload starts, per case type
  (`CaseDefinition-Algemeen.md`).
- **Visma Circle** is the documented claim on both classification and NAW
  extraction, Djumalytics Autoclassificatie and Djumalytics NAW
  (`/software/zaakgericht-werken`).

## What filinq builds

Filinq already has most of the substrate, and two changes on
`development` already own parts of this cluster.

- `inbound-auto-classification` classifies document type and
  correspondent and routes to a department, suggest-then-approve, over
  `MetadataService::enhanceMetadata()` and `LanguageClassifier`.
- `document-intake-inbox` gives a waiting document a record, a channel
  and a lifecycle of `received`, `assigned` and `rejected`, and a leaf
  the consuming app places.
- `ocr-trigger-surface` and `scan-intake-with-separator-sheets` own the
  scan path.

This change extends those three rather than restating them, and adds what
none of them covers.

- **Promotion of an arriving attachment.** A message with attachments
  produces one intake document per attachment, each linked to the message
  it arrived with, so bijlagen and the aanvraag stay together and are
  assigned together.
- **Default metadata stamped at arrival.** The instance declares defaults
  per channel and per sender, applied at creation, before any
  classification runs. An unclassified document is invisible under a
  classification-driven access model, which is the reason this is a
  default and not a later enrichment.
- **Party details extracted from the document.** The NAW block is read
  from the extracted text through the entity detection filinq already
  runs, and offered as a suggestion on the intake record. It is never
  written silently, the same suggest-then-approve posture
  `anonymization-review-workbench` and `inbound-auto-classification`
  already take.
- **The detached-document worklist.** A document removed from a record
  returns to a worklist with the reason and the person who detached it,
  rather than disappearing.
- **Routing and acceptance per case type.** An inbound document is routed
  to an owner, a group or a role, and the acceptance step is declared by
  the type it is filed against.
- **The inbox is inside the access model.** The intake record is an
  OpenRegister object from arrival, under the same authorization cascade
  as everything else, never a staging table outside every permission
  check.
- **Text recognition made searchable.** What the OCR read is indexed with
  the document, so a scan is found by its content.
- **A declared process per upload.** The host declares what happens when
  a file lands, per record type.

## How dossiq consumes it

dossiq places the intake leaf on its Documents page and its case detail
page, and owns the promote-to-case act, which is the cluster's own
division of labour. Assigning writes the link on the intake document and
dossiq picks it up through its existing `zaakinformatieobject` relation.
dossiq declares the routing and the acceptance step on the case type. The
mail path is integriq's under D12, and the trusted-mail defect named in
the sweep's non-row findings (`InboundEmailJob` matches a subject tag and
authenticates nothing) is fixed there, not here.

## Existing specs it extends

`inbound-auto-classification` and `metadata-enrichment` (classification
and stamping), `document-intake-inbox` (the intake record and the leaf),
`ocr-document-scanning` (the OCR path), `entity-search` and
`anonymization-entity-review` (the entity detection the NAW suggestion
reads), `document-register` and `unified-search-provider` (where a
document is found).

## ADRs

- ADR-001 and ADR-070: the intake record is an OpenRegister object, with
  no filinq table.
- ADR-066: the inbox and the worklist reach a consuming app as leaves;
  filinq carries no verb into dossiq.
- ADR-011: the entity detection, the text extraction and the
  anonymisation all stay OpenRegister's.
- ADR-008: Controller, Service, Mapper.

## Size

L. Eleven candidates, one new schema, two new services and two leaves,
over three changes that already exist.

## Dependencies

Mail intake that can be trusted, cluster 25, which is dossiq's and
integriq's. The intake record can be created from the scan and upload
channels without it, so this change is not blocked, but the mail channel
is only safe once the sender is authenticated.

## Out of scope

- Creating a case from the inbox. That is dossiq's act on the object the
  leaf hands back.
- The mail transport, OAuth2 and the sender authentication. integriq,
  under D12.
- The archiving process after filing. Moved to openregister by D7.

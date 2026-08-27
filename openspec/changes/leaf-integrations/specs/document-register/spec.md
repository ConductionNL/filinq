# document-register Specification (delta)

---
status: proposed
---

## Purpose

Surface the standard OR leaves on the register's production records: NC Mail linkage plus
a create-from-email template on `correspondence`, the files leaf on `generatedDocument`
and `dossier` (linking the pipeline's file outputs into the standard leaf surface), and
the deck leaf on `dossier`. Consumes `integration-email`, the OR files leaf, and
`integration-deck` via the registry (ADR-019, ADR-022).

## ADDED Requirements

### Requirement: Correspondence Is A Mail Link Target With Create-From-Email

The `correspondence` schema SHALL declare `configuration.linkedTypes` containing `"mail"`
and a `configuration.mailObjectTemplate` field map, so that a produced letter's related
NC Mail traffic can be linked to the correspondence record, and an inbound case email can
create a correspondence record with the subject pre-filling `caseReference` context.
Every key in the `mailObjectTemplate` map SHALL name a real `correspondence` property.
Creation from email SHALL create the register record only — it SHALL NOT trigger
generation, rendering, or any send.

#### Scenario: Inbound case mail becomes a correspondence record

- GIVEN an inbound email about case "Z-2026-114" in NC Mail
- WHEN the caseworker uses the create-from-email action targeting `correspondence`
- THEN a correspondence record SHALL be created with the mapped fields pre-filled
- AND no document SHALL be generated and no message SHALL be sent by the action

#### Scenario: Linking mail leaves the audit record intact

- GIVEN an existing correspondence record with `status` "generated"
- WHEN a related NC Mail message is linked to it from the Mail sidebar
- THEN the message SHALL appear on the record's leaf surface
- AND `status`, `templateId`, `generatedAt`, and `generatedBy` SHALL be unchanged

### Requirement: Pipeline File Outputs Surface Through The Files Leaf

The `generatedDocument` and `dossier` schemas SHALL declare
`configuration.linkedTypes` containing `"files"`, so the standard files leaf renders on
their detail surfaces and the pipeline's outputs (the `fileId`/`filePath` a generation
produced; a dossier's source and produced files) are reachable through the leaf rather
than through bespoke file widgets. The leaf SHALL be a linking/visibility surface: it
SHALL NOT become a second write path for `fileId`/`filePath`, and Filinq's in-app
generation, anonymisation, and signing pipeline surfaces SHALL remain untouched.

#### Scenario: A generated document's file is reachable from its record

- GIVEN a `generatedDocument` with `status` "completed" and a `fileId`
- WHEN the caseworker opens the record's detail surface
- THEN the files leaf SHALL be rendered with the produced file linked
- AND opening the file SHALL go through NC Files under normal file ACLs

#### Scenario: Dossier files are linked on the dossier record

- GIVEN a dossier with linked source documents
- WHEN the dossier detail surface renders
- THEN the files leaf SHALL be present and list the linked files
- AND the app-owned dossier surfaces SHALL remain present and unchanged

### Requirement: Dossier Follow-Ups Use The Deck Leaf

The `dossier` schema SHALL declare `configuration.linkedTypes` containing `"deck"`, so
dossier-level publication follow-up work is tracked as linked Deck cards through the leaf
instead of an app-local task system. Card state SHALL NOT drive any dossier field.

#### Scenario: Follow-up card on a dossier

- GIVEN a dossier awaiting its periodic `checkedOn` review
- WHEN a review card is created via the deck leaf
- THEN the card SHALL be linked to the dossier and visible on its leaf surface
- AND `bases` and `checkedOn` SHALL be unchanged until a human edits them in-app

### Requirement: Leaf Declarations Are Configuration-Only And Import Cleanly

This delta SHALL change only `configuration` blocks (`linkedTypes`,
`mailObjectTemplate`) on the named schemas in `lib/Settings/filinq_register.json`
(with an `info.version` bump so existing installs re-import). No schema property, no
`required` list, and no other schema SHALL be touched, and the register SHALL import
into OpenRegister with zero configuration-validation errors.

#### Scenario: Register imports cleanly with the leaf declarations

- GIVEN the updated `filinq_register.json`
- WHEN it is imported into OpenRegister
- THEN `linkedTypes` and `mailObjectTemplate` validation SHALL pass with zero errors
- AND every schema not named by this delta SHALL be byte-identical to before

---
kind: code
depends_on: [zgw-document-bridge]
---

# Proposal: generate-store-in-case-system

Matrix row `td-dms-link` from humaniq's matrix
(`openspec/parity/capabilities.json` in ConductionNL/humaniq), rated
partial there and owned by filinq. Written in the OpenSpec pass of 27
September 2026.

## Why

An organisation keeps its official documents in a case system or document
management system. filinq files what it generates in Nextcloud Files and
nowhere else. `DocumentService::storeOutputIfRequested()`
(`lib/Service/DocumentService.php:734`) writes the bytes through
`DocumentStorageService` into `DocuDesk/<namespace>/` when
`options.output.mode` is `files` or `both` (:521, modes at :85). There is
no other destination. An HR officer who generates an employment contract
then uploads it to the DMS by hand, or it is never filed there.

The route out already exists for one document. The open change
`zgw-document-bridge` lets integriq (formerly OpenConnector) push a
redacted copy back to the source case system as a new informatieobject
(REQ-DDZGW-005), watching objects in status `ready_for_writeback`. It only
carries documents that came from the case system. A document filinq
generates has no original there and no way onto that bridge.

### Matrix rows (humaniq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `td-dms-link` | Store generated HR documents automatically in the organisation's document management system. | partial: generated HR documents are rendered by filinq and stored in Nextcloud Files; `grep -rniE 'zgw\|zds\|drc' lib src` in humaniq finds no link to an external DMS |

The row comes from humaniq's matrix and is owned by filinq. The missing
half this change specifies: generated documents stored in the
organisation's case system over the ZGW Documenten API, not only in
Nextcloud Files. It serves every generated document, not only HR ones.

### Demand

- tender: https://www.tenderned.nl/aankondigingen/overzicht/415705
  (Delft Support, requirements E4 and E22: a link with the DMS, StUF-ZDS or
  NEN-ISO 16175)

### Competitors rated yes

None of the six HR competitors in humaniq's matrix is rated yes. HR2day is
partial: "the Excasso product integrates with document management systems;
for HR documents ... stores them in the HR2day dossier, no DMS link is
stated" (https://www.hr2day.com/pensioen/,
https://www.hr2day.com/features/automatische-documenten/). The row is
built on the tender demand.

## What changes

- An admin declares a case-system destination per template namespace or
  per template: which bridge source, which document type
  (informatieobjecttype), which confidentiality level, which source
  organisation.
- When filinq stores a generated document from such a template, it also
  records a delivery for the case system in status `ready_for_writeback`,
  pointing at the stored file. A caller can name a destination on the
  request instead.
- integriq's push synchronisation picks the delivery up, creates the
  informatieobject and writes the outcome back, the same way the bridge
  does for redacted copies.
- The generated document record links its delivery, so a clerk sees
  whether it reached the case system. A failed delivery is visible in the
  admin bridge panel with its error and a retry.
- filinq never calls the case system itself.

## Capabilities

### New capabilities

- `case-system-delivery`: a generated document handed to the case system
  through the bridge, with a delivery record per document and destination.

### Modified capabilities

None. `zgw-document-bridge` keeps its inbound leg and its write-back of
redacted copies; this change adds a second kind of outbound object beside
it.

## Impact

- `lib/Settings/filinq_register.json`: new schemas `caseSystemDestination`
  and `caseSystemDelivery`; `generatedDocument` gains
  `caseSystemDeliveryId`. Register version bump.
- `lib/Service/DocumentService.php`: after a successful store, record the
  delivery. New `lib/Service/CaseSystemDeliveryService.php`.
- One route: `POST api/bridge/deliveries/{id}/retry`.
- `src/views/settings/`: the bridge status panel from
  `zgw-document-bridge` gains a destinations list and the waiting and
  failed deliveries.
- `docs/features/`: a section with a screenshot.

## Out of scope

- Updating or replacing a document already in the case system. A second
  generation is a second informatieobject.
- Creating a case. A delivery may name an existing case to relate to; it
  never creates one.
- NEN-ISO 16175 export as a file format. The tender names it as one of
  three options; this change serves the ZGW and StUF-ZDS sources the bridge
  already declares.

## Cross-app dependencies

- integriq: a push synchronisation on `caseSystemDelivery` objects in
  `ready_for_writeback` that creates an EnkelvoudigInformatieObject (ZGW
  Documenten API) or a StUF-ZDS document with the delivery's metadata,
  uploads the file, relates it to the case when `zaakUrl` is set, and sets
  `written_back` with `resultExternalId`, or `writeback_failed` with
  `writeBackError`.
- humaniq: render with `options.output.mode` `both` instead of `return`
  (humaniq `HrDocumentService.php` `buildOptions()`, :1128) so filinq files
  the document and the namespace destination applies, keep the returned
  bytes for its own record, and show the delivery status on its generated
  document page.

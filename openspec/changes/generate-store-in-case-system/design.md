# Design: generate-store-in-case-system

Kind: code. Two schemas, one service, one route, and a list on the admin
bridge panel. The case-system call is integriq's.

## Context

Read at development `2088cc1f`.

- `lib/Service/DocumentService.php:168` `generateDocument()` stores the
  output with the private `storeOutputIfRequested()` (:734) when
  `resolveOutputMode()` (:521) answers `files` or `both`, then logs a
  `generatedDocument` with `fileId` and `filePath` (:247-262). The store is
  `DocumentStorageService::store()` (`lib/Service/DocumentStorageService.php:173`).
  In `both` mode a storage failure becomes a warning, not an error
  (:785-789).
- The bulk path `generateBulkSync()` (:814) honours the same output mode
  per object.
- `generatedDocument` (`lib/Settings/filinq_register.json:1125`, 1.3.0) has
  `fileId`, `filePath` and `caseId` ("UUID of the linked Zaak (case) in
  Procest (optional)") and no case-system field.
- The open change `zgw-document-bridge` (written 2026-07-17) adds
  `bridgeSource` (`sourceType` `zgw-drc` or `stuf-zds`, `vendor`,
  `synchronizationId`, `active`) and `externalDocument`, whose outbound leg
  is an integriq push synchronisation on `processingStatus =
  ready_for_writeback` (its design.md D2, D3) and a bridge status panel in
  admin settings (REQ-DDZGW-008). It names a separate `bridge` register;
  since register v8.0.0 (2026-08-24, #771) filinq has one register,
  `filinq`.
- humaniq renders through `generateDocument()` with no `output` option, so
  mode `return`, and stores the bytes on its own object (humaniq
  `HrDocumentService.php:589-612`).
- `src/views/settings/Settings.vue` is the admin settings page.

New: `caseSystemDestination`, `caseSystemDelivery`,
`CaseSystemDeliveryService`, the retry route, the panel list.

## Goals / Non-goals

Goals: a generated document reaches the case system without a person
uploading it; the delivery is visible and retryable; filinq never talks
ZGW or StUF.

Non-goals: updating a document in the case system, creating a case,
NEN-ISO 16175 file export.

## Decisions

### D1. Its own outbound object, on the bridge's contract

`caseSystemDelivery` is a new schema next to `externalDocument`, in the
register the bridge lands in (`filinq` unless the bridge change keeps a
`bridge` register). It reuses the bridge's outbound state names:
`ready_for_writeback`, `written_back`, `writeback_failed`, with retry back
to `ready_for_writeback`. Alternative considered: an `externalDocument`
with an empty `externalId`. Rejected: that schema means "a copy of
something the case system masters" (REQ-DDZGW-003), and a generated
document has no original there.

### D2. Destinations are declared, requests may override

`caseSystemDestination` holds `templateNamespace` or `templateId`,
`sourceId` (a `bridgeSource`), `informatieobjecttype`,
`vertrouwelijkheidaanduiding`, `bronorganisatie` and `active`. A template
match wins over a namespace match. A request may carry
`options.output.caseSystem` with the same fields plus `zaakUrl`.
Alternative considered: request-only. Rejected, because humaniq and other
callers would each have to learn the case system's type URLs; the admin
knows them once.

### D3. Only a stored document is delivered

A destination applies only when the document was stored (`files` or
`both`). A request with `options.output.caseSystem` and mode `return`
answers 400 "A document sent to the case system must be stored first. Use
output mode files or both." A store that failed in `both` mode records no
delivery and adds the warning "Not sent to the case system: the document
was not stored." Alternative considered: deliver the bytes from memory.
Rejected: the bridge carries a file reference, and a delivery without a
stored file cannot be retried.

### D4. Recording a delivery never fails the generation

After a successful store, `CaseSystemDeliveryService` writes the delivery
and puts its id on the `generatedDocument`. When the source is inactive or
integriq is absent, the delivery still waits in `ready_for_writeback` and
the panel shows it. A write failure of the delivery object itself becomes
a warning on the generation.

### D5. One delivery per document and destination

A document is delivered once per destination. Retry moves the same object
back to `ready_for_writeback`; it never creates a second one, so the case
system never receives a duplicate from filinq.

### D6. Metadata filinq fills, and what it leaves to integriq

filinq fills `titel` (the file name without extension), `bestandsnaam`,
`formaat` (the MIME type), `taal` (`dut`), `creatiedatum`, `auteur` (the
generating user's display name) and the destination fields. integriq maps
them to the ZGW or StUF-ZDS fields. Alternative considered: filinq builds
the ZGW request body. Rejected, per the bridge's non-goal "No ZGW/StUF
client code in Filinq".

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| delivery status | `x-openregister-lifecycle` on `deliveryStatus` | the bridge's own state names, guarded by OpenRegister |
| picking a destination | imperative, in `CaseSystemDeliveryService` | a lookup at store time with a precedence rule |
| the case-system call | integriq push synchronisation | filinq never calls ZGW or StUF |
| link to the document | `x-openregister-relations` from `caseSystemDelivery.generatedDocumentId` to `generatedDocument` | a typed relation, shown on both sides |
| failure notice | `x-openregister-notifications` on `writeback_failed` to the document's `generatedBy` | declarative, like `correspondenceFailed` |

## Seed data

New `caseSystemDestination` (1.0.0) and `caseSystemDelivery` (1.0.0).
`generatedDocument` moves to 1.4.0 with `caseSystemDeliveryId`. The seed
adds one inactive destination for namespace `hrmq` on the bridge's demo
source, and one delivery in `written_back` for a seeded generated
document, so the panel shows both states on a fresh install.

## Risks / trade-offs

- An admin declares the wrong document type. The case system rejects the
  create and the delivery shows `writeback_failed` with the case system's
  message.
- A document holding personal data leaves Nextcloud. It goes to the
  organisation's own case system, set up by its admin; the destination
  carries the confidentiality level the case system enforces.
- The bridge lands with a separate `bridge` register after all. Then both
  new schemas go there too; nothing else changes.

## Open questions

- Does integriq's mapping support creating an informatieobject and a
  zaakinformatieobject in one push? The bridge change raises the same
  question (its design.md D2); the answer covers both.

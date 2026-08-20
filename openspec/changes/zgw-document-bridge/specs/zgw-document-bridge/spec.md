# zgw-document-bridge Specification (delta)

---
status: proposed
---

## Purpose

DocuDesk-side contract for processing documents that are mastered in an
external case system (zaaksysteem): an OR-backed staging register that
OpenConnector synchronizes ZGW Documenten API (DRC) and StUF-ZDS documents +
metadata into (Rx.Enterprise, Djuma, zaaksysteem.nl, generic ZGW — per
Dordrecht 407973); a processing-status lifecycle through which DocuDesk runs
its anonymize/consent/publish flows on the staged copy and releases the
redacted derivative for write-back as a **new** informatieobject; ≤24h
freshness health per source in admin settings; and source provenance on
documents. The originals never leave the zaaksysteem as master record (Arnhem
407824). The connector/sync engine itself is OpenConnector's; DocuDesk defines
schemas, status semantics, hooks and UI only.

## ADDED Requirements

### Requirement: Bridge staging register schemas (REQ-DDZGW-001)

The app MUST declare a `bridge` register in
`lib/Settings/docudesk_register.json` with two schemas: `bridgeSource` (per
configured case-system source: `name`, `sourceType` enum `zgw-drc`|`stuf-zds`,
`vendor`, `synchronizationId`, `syncIntervalMinutes`, `lastSyncAt`,
`lastSyncStatus` enum `success`|`error`, `lastSyncError`, `active`) and
`externalDocument` (per synced informatieobject: `sourceId`, `externalId`,
`zaakIdentificatie`, `title`, `filename`, `format`, `creatiedatum`,
`vertrouwelijkheidaanduiding`, `versie`, `stagedFileRef`, `contentHash`,
`syncedAt`, `processingStatus`, `dossierRef`, `resultFileRef`,
`resultExternalId`, `writeBackError`). All bridge data MUST be stored as
OpenRegister objects (ADR-001, no custom tables) and the register version MUST
be bumped so `ConfigurationService::importFromApp()` imports it on boot.

#### Scenario: Register import creates the bridge schemas

- GIVEN a Nextcloud instance with DocuDesk and OpenRegister installed
- WHEN the app boots and `ConfigurationService::importFromApp()` runs
- THEN the `bridge` register exists with schemas `bridgeSource` and `externalDocument`
- AND the seeded demo source and staged documents are queryable via `ObjectService::searchObjects()` with `@self.register = bridge`
- @e2e exclude register import is a boot-time backend concern with no UI surface of its own — covered by PHPUnit register-import assertions (tests/unit/Settings/)

### Requirement: Inbound sync contract — OpenConnector stages, DocuDesk never fetches (REQ-DDZGW-002)

External connectivity MUST remain in OpenConnector: one OpenConnector
Synchronization per source targets register `bridge`, schema
`externalDocument`, mapping ZGW/StUF document metadata onto the schema
properties, staging the file content into a per-source Nextcloud staging
folder and setting `stagedFileRef`, and updating the owning `bridgeSource`'s
`lastSyncAt`/`lastSyncStatus`/`lastSyncError` after each run. DocuDesk MUST
NOT contain any ZGW, StUF or case-system HTTP/SOAP client code, and MUST NOT
declare an `info.xml` dependency on OpenConnector (the bridge register is
inert without it).

#### Scenario: Synced informatieobject appears as a staged external document

- GIVEN an OpenConnector synchronization configured for source "Demostad zaaksysteem" targeting `bridge`/`externalDocument`
- WHEN the synchronization runs and delivers a new informatieobject
- THEN an `externalDocument` object exists with the ZGW metadata, a `stagedFileRef` pointing at the staged copy and `processingStatus` `staged`
- AND `bridgeSource.lastSyncAt` and `lastSyncStatus` reflect the run
- @e2e exclude requires a live OpenConnector + mock DRC environment out of scope for DocuDesk's Playwright suite — contract pinned by PHPUnit fixture tests on BridgeService reading pre-seeded objects (tests/unit/Service/BridgeServiceTest.php)

#### Scenario: No case-system client in DocuDesk

- GIVEN the DocuDesk codebase at this change's completion
- WHEN `lib/` is inspected
- THEN no class performs HTTP or SOAP calls to a ZGW Documenten API or StUF-ZDS endpoint
- @e2e exclude static codebase property, enforced by review + a PHPUnit architecture test, not a browser flow

### Requirement: Originals stay mastered in the zaaksysteem (REQ-DDZGW-003)

DocuDesk MUST treat every staged copy as a read-only input: it MUST NOT
modify, re-version or delete the original informatieobject in the source
system, MUST NOT edit the staged source file in place (all processing output
goes to new derivative files), and MUST NOT expose the staged copy as an
editable document in MyDocuments. Deleting a staged `externalDocument` object
or file in Nextcloud MUST NOT propagate any deletion to the source system.

#### Scenario: Anonymisation of a bridge document leaves the staged original intact

- GIVEN a staged external document with `contentHash` H
- WHEN an operator runs anonymisation on it via a dossier flow
- THEN the redacted output is written to a new file recorded in `resultFileRef`
- AND the staged file's content hash still equals H
- @e2e tests/e2e/workflows/zgw-bridge-workflow.spec.ts

### Requirement: Processing-status lifecycle is declarative and guarded (REQ-DDZGW-004)

The `externalDocument` schema MUST declare an `x-openregister-lifecycle`
annotation with canonical `initial: staged` and exactly the transitions
`staged → in_processing`, `in_processing → processed`, `in_processing →
staged`, `processed → ready_for_writeback`, `ready_for_writeback →
written_back`, `ready_for_writeback → writeback_failed`, `writeback_failed →
ready_for_writeback`. Invalid transitions MUST be rejected by OpenRegister's
lifecycle guard. DocuDesk-side transitions (`staged → in_processing →
processed → ready_for_writeback`) are set by `BridgeService`; the
`written_back`/`writeback_failed` transitions belong to OpenConnector's push
leg.

#### Scenario: Invalid transition is rejected

- GIVEN an external document in status `staged`
- WHEN a save attempts to set `processingStatus` to `written_back` directly
- THEN OpenRegister rejects the transition and the object remains `staged`
- @e2e exclude lifecycle guard is enforced server-side by OpenRegister — covered by PHPUnit transition tests (tests/unit/Service/BridgeServiceTest.php) and OR's own lifecycle suite

#### Scenario: Status updates carry all fields forward

- GIVEN an external document with full ZGW metadata
- WHEN `BridgeService` transitions its status
- THEN the save carries all schema properties forward (PUT semantics) and no metadata field is nulled
- @e2e exclude data-integrity property of the save path — covered by a PHPUnit test asserting a non-changed field survives the transition

### Requirement: Write-back delivers the redacted derivative as a new informatieobject (REQ-DDZGW-005)

The bridge write-back contract MUST deliver the redacted derivative to the
source system as a **new** informatieobject related to the same zaak — never
as an update, new version or replacement of the original. When an operator (or
policy) releases a processed document, DocuDesk MUST set `processingStatus =
ready_for_writeback` with `resultFileRef` populated; OpenConnector's push
synchronization performs the external write and records the outcome by setting
`processingStatus = written_back` + `resultExternalId` (success) or
`writeback_failed` + `writeBackError` (failure). The new informatieobject's
metadata MUST identify it as a DocuDesk derivative (title suffix
"(geanonimiseerd)", reference to the original's identificatie, processing
date and anonymisation profile).

#### Scenario: Release for write-back

- GIVEN a processed external document with a redacted derivative in `resultFileRef`
- WHEN the operator releases it for write-back
- THEN `processingStatus` becomes `ready_for_writeback`
- AND after the OpenConnector push leg succeeds the object shows `written_back` with a `resultExternalId` different from `externalId`
- @e2e tests/e2e/workflows/zgw-bridge-workflow.spec.ts

#### Scenario: Failed write-back is visible and retryable

- GIVEN a document in `ready_for_writeback` whose push failed
- WHEN the operator views it
- THEN it shows status `writeback_failed` with the `writeBackError` message
- AND a retry action returns it to `ready_for_writeback`
- @e2e tests/e2e/spec-coverage/zgw-bridge.spec.ts

### Requirement: Dossier pick-up of bridge documents (REQ-DDZGW-006)

An operator MUST be able to attach staged external documents to a DocuDesk
dossier: the staged file is copied into the dossier's Nextcloud folder (the
unit the existing folder-batch anonymisation and grondslagen capabilities
operate on), `externalDocument.dossierRef` is set to the dossier object UUID
and `processingStatus` transitions to `in_processing`. The existing `dossier`
schema MUST NOT be modified.

#### Scenario: Attach staged documents to a dossier

- GIVEN a dossier "Woo-verzoek 2026-021" and two staged external documents for zaak ZAAK-2026-0042
- WHEN the operator attaches both to the dossier
- THEN copies exist in the dossier folder and both objects carry `dossierRef` and status `in_processing`
- AND the existing batch-anonymisation flow can process the dossier folder unchanged
- @e2e tests/e2e/workflows/zgw-bridge-workflow.spec.ts

### Requirement: ≤24h freshness health per source (REQ-DDZGW-007)

`GET api/bridge/sources` MUST return every `bridgeSource` with a computed
`health` value: `fresh` when the last successful sync is less than 24 hours
old, `stale` when the source is active but the last successful sync is 24
hours old or older (Dordrecht 407973 ≤24h freshness requirement), `failing`
when `lastSyncStatus = error`, and `inactive` when `active = false`. Health
MUST be computed at read time from stored telemetry, not persisted.

#### Scenario: Source turns stale after 24 hours without sync

- GIVEN an active source whose `lastSyncAt` is 25 hours in the past with `lastSyncStatus` `success`
- WHEN `api/bridge/sources` is called
- THEN that source's `health` is `stale`
- @e2e exclude time-arithmetic unit behaviour — covered by PHPUnit clock-injected tests (tests/unit/Service/BridgeServiceTest.php); the panel rendering is covered under REQ-DDZGW-008

### Requirement: Bridge status panel in admin settings (REQ-DDZGW-008)

DocuDesk admin settings MUST include a bridge status panel listing each
configured source with name, vendor, source type, last sync time and a health
chip (`fresh`/`stale`/`failing`/`inactive`), rendered with
`@conduction/nextcloud-vue` components (ADR-012) and Nextcloud CSS
variables/NL Design tokens only (ADR-003). When no sources are configured (or
OpenConnector is not installed) the panel MUST show an explanatory empty state
instead of an error.

#### Scenario: Admin sees per-source health

- GIVEN an admin on the DocuDesk admin settings page with one fresh and one failing source seeded
- WHEN the bridge status panel renders
- THEN both sources are listed with their vendor and last sync time
- AND the failing source shows a `failing` health chip with its error message
- @e2e tests/e2e/spec-coverage/zgw-bridge.spec.ts

#### Scenario: Empty state without OpenConnector

- GIVEN an instance where no `bridgeSource` objects exist
- WHEN the panel renders
- THEN it shows an empty state explaining that sources are configured via OpenConnector
- @e2e tests/e2e/spec-coverage/zgw-bridge.spec.ts

### Requirement: Source badge on externally-sourced documents (REQ-DDZGW-009)

Documents whose file id matches an `externalDocument.stagedFileRef` MUST show
a source badge ("Zaaksysteem: {vendor}") in the MyDocuments listing and on the
document detail header, so an operator always sees that the master record
lives in the case system. The badge lookup MUST be batched per listing (one
bridge query per page, not per row). Provenance detail beyond the badge links
to OpenRegister's synced-from surface and MUST NOT be reimplemented in
DocuDesk.

#### Scenario: Badge on a bridge document

- GIVEN a staged external document from vendor "zaaksysteem.nl" visible in MyDocuments
- WHEN the listing renders
- THEN the row shows the badge "Zaaksysteem: zaaksysteem.nl"
- AND a Nextcloud-native file without a bridge record shows no badge
- @e2e tests/e2e/spec-coverage/zgw-bridge.spec.ts

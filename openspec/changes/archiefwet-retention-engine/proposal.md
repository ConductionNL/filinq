---
kind: code
tracking_issue: https://github.com/ConductionNL/docudesk/issues/231
depends_on: []
---

# Proposal: archiefwet-retention-engine

## Why

Every Dutch municipal document DocuDesk touches is a government record under
the Archiefwet 1995: it must carry a waardering from the **Selectielijst
gemeenten en intergemeentelijke organen** (bewaren, or vernietigen after a
term), destruction may only happen through a reviewed and approved
**vernietigingslijst** ending in a **verklaring van vernietiging**, and
permanently kept records must be transferred (overbrenging) to an archival
institution. The decidesk market deep-dive found this is the #1 whitespace in
the municipal SaaS market: incumbent vendors contractually exclude records
management/archiving from their scope. The Dordrecht 407973 tender requires
destruction-date propagation to coupled systems, and the NC ecosystem report
shows `files_retention` (folder-tag-based auto-delete, no appraisal, no
approval, no certificate) is structurally inadequate for Archiefwet
compliance. Evidence row: intelligence DB
`mg2026-archiefwet-retention`; tracking issue ConductionNL/docudesk#231.

DocuDesk today has **no** retention surface at all: no selectielijst, no
schedule computation, no disposal workflow, no transfer state. Wave-1 changes
already reserved the propagation fields (`publicationRecord.destructionDate` /
`destructionDateSource` in `woo-publicatie-pipeline`) but nothing computes or
supplies those dates.

**Verified at OpenRegister HEAD (ebedbdd5a)**: OR already ships a
records-management retention stack — per-object `retention` archival metadata
(`archiefnominatie`, `archiefstatus`, `classificatie`, `bewaartermijn`,
`archiefactiedatum`, `legalHold`), schema-level `archive` configuration with
selectielijst lookup and configurable afleidingswijzen, a
`DestructionCheckJob` → destruction-list → approve/reject →
`DestructionExecutionJob` → certificate pipeline behind
`/api/archival/*`, and immutability guards (409 `OBJECT_DESTROYED` /
`OBJECT_TRANSFERRED`). Per ADR-022 DocuDesk must **consume** this stack, not
rebuild it. What is missing is everything municipality- and DocuDesk-side:
the selectielijst master data, the archive configuration on DocuDesk's record
schemas, the archivist UI, and the destruction-date propagation into the
publication pipeline and the zaaksysteem bridge.

## What

1. **Selectielijst master data**: a new `archief` register (no fleet slug
   collision, verified) with a `selectielijstEntry` schema matching the field
   contract OR's `RetentionService::lookupSelectielijstEntry()` reads
   (`categorie`, `omschrijving`, `bewaartermijn`, `archiefnominatie`, `bron`,
   `toelichting`), plus `destructionList` and `destructionCertificate` schemas
   as the storage homes OR's archival settings point at. Seeded with
   representative VNG selectielijst gemeenten 2020 entries (category numbers
   shipped as explicit placeholders pending selectielijst-manager sign-off,
   same convention as REQ-DREG-ALINK-01).
2. **Retention categories on record schemas**: `archive` configuration
   (enabled, classificatie, afleidingswijze, closureField) declared on
   DocuDesk's record schemas so OR stamps `retention` metadata at creation
   and computes `archiefactiedatum` from trigger event + term. Dossier-side
   adoption is specified here as engine requirements; the dossier-register
   canonical spec itself is owned by a sibling change (see design.md).
3. **Disposal workflow surface**: an Archiefbeheer UI where an archivist
   reviews vernietigingslijsten (proposal → review → approve full/partial or
   reject → destruction with verklaring van vernietiging), calling OR's
   `/api/archival/*` endpoints directly — no DocuDesk pass-through
   controllers.
4. **Transfer-to-archive state**: `bewaren` records that reach their
   archiefactiedatum enter the overbrenging flow and become read-only once
   `overgebracht` (the actual e-depot ingestion ships in
   `tmlo-mdto-metadata`, which depends on this change).
5. **Destruction-date propagation**: the engine becomes the single source
   feeding `publicationRecord.destructionDate`/`destructionDateSource`
   (wave-1 fields, reused verbatim) toward OpenCatalogi, and honouring a
   zaaksysteem-supplied vernietigingsdatum from the `zgw-document-bridge`
   staging metadata as the master-record override.
6. **Guard**: Archiefwet-controlled record schemas MUST NOT use the
   `x-openregister-archival` auto-delete annotation (its hourly sweep deletes
   without any vernietigingslijst approval — verified at OR HEAD); the
   annotation remains only for operational logs, in the object shape OR HEAD
   validates.

## Capabilities

### Added Capabilities

- `archiefwet-retention-engine`: Archiefwet 1995 retention for DocuDesk
  records — selectielijst master data, retention categories and schedule
  computation (delegated to OpenRegister), vernietigingslijst review/approval
  with verklaring van vernietiging, transfer-to-archive state, and
  destruction-date propagation to the Woo publication pipeline and the
  zaaksysteem bridge.

### Modified Capabilities

- `document-register`: record schemas carry Archiefwet `archive`
  configuration; the `correspondence` archival mechanism moves from the
  `x-openregister-archival` auto-delete annotation to engine-managed
  destruction (same P7Y term, destruction now requires an approved
  vernietigingslijst); remaining annotations use the object shape OR HEAD
  validates.

## Affected Projects

- [x] Project: `docudesk` — new `archief` register + schemas + seeds, archive
  config on record schemas, `RetentionSurfaceService` (thin, read/propagate
  only), Archiefbeheer UI, this OpenSpec change.
- Consumed: `openregister` — RetentionService / DestructionService /
  DestructionCheckJob / ArchivalController (`/api/archival/*`), archival
  settings (`selectielijstRegister/Schema`, `destructionListRegister/Schema`,
  `archivalRegister`). No OR code change expected; gaps are filed as OR
  issues (see design.md Open Questions).
- Aligned: wave-1 `woo-publicatie-pipeline` (destructionDate propagation
  fields) and `zgw-document-bridge` (zaaksysteem-supplied destruction dates).

## Out of Scope

- Any retention arithmetic, destruction execution or certificate generation
  in DocuDesk code — OR owns all of it (ADR-022).
- TMLO/MDTO metadata and e-depot ingestion — `tmlo-mdto-metadata`
  (depends on this change).
- Legal holds — `e-discovery-legal-hold` (depends on this change).
- The dossier-register canonical spec file — sibling ownership; dossier-side
  behaviour is specified as engine requirements only.
- A full VNG selectielijst import UI beyond the existing OR bulk object
  import; only seeds + CSV import path are covered.

## Success Criteria

- `openspec validate archiefwet-retention-engine --strict` exits 0.
- On a fresh install, the `archief` register imports with seeded
  selectielijst entries and OR's archival settings can be wired to it from
  the DocuDesk admin panel.
- A record created under a schema with `archive.enabled` carries
  `retention.archiefnominatie/classificatie/bewaartermijn/archiefactiedatum`
  computed by OR from the configured trigger event + term.
- An archivist can review, partially approve and reject a
  vernietigingslijst from the Archiefbeheer UI, and after execution a
  verklaring van vernietiging is listed.
- A `bewaren` record past its archiefactiedatum is offered for overbrenging
  and becomes read-only (409 `OBJECT_TRANSFERRED`) once transferred.
- `publicationRecord.destructionDate` is auto-filled from the engine (or the
  zaaksysteem-supplied date) with `destructionDateSource` naming the origin.
- No Archiefwet-controlled schema carries `x-openregister-archival`;
  `composer check:strict` and the unit suite pass with zero new violations.

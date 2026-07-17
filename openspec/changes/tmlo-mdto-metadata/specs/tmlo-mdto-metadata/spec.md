# tmlo-mdto-metadata Specification (delta)

---
status: proposed
---

## Purpose

MDTO (Metagegevens voor Duurzaam Toegankelijke Overheidsinformatie, the
successor of TMLO) archival metadata for DocuDesk records, so that
permanently kept records can actually reach an e-depot: the six core
archival attributes ride OpenRegister's `retention` field (populated by the
`archiefwet-retention-engine` dependency), a `mdtoSupplement` schema
carries the MDTO informatieobject attributes OR does not model
(aggregatieniveau, dekkingInTijd, beperkingGebruik, betrokkene, taal,
omschrijving), DocuDesk prefills what it uniquely knows (AVG/Woo-derived
use restrictions from consent/prohibition/anonymisation state),
completeness validation gates overbrenging, and deterministic MDTO XML/JSON
sidecars per document/dossier feed OR's e-depot transfer stack.

## ADDED Requirements

### Requirement: MDTO core attributes have a single source of truth (REQ-DDTMM-001)

The app MUST source every MDTO core archival attribute (waardering,
bewaartermijn, archiefactiedatum, archiefstatus, classificatie) exclusively
from the OpenRegister `retention` field populated by the retention engine,
and MUST NOT store a second copy of any of these attributes in a DocuDesk
schema. The archiefvormer MUST default to OpenRegister's
`organisation_identifier` app setting, overridable per record only through
`mdtoSupplement.archiefvormerOverride`. DocuDesk MUST NOT write the
OR-owned `retention` or `tmlo` structures directly.

#### Scenario: Core fields render from retention, read-only

- GIVEN a record whose `retention` block carries nominatie, termijn, actiedatum and classificatie
- WHEN the MDTO panel on the document detail page renders
- THEN the core attributes display the retention values read-only
- AND no DocuDesk schema property duplicates them
- @e2e tests/e2e/workflows/tmlo-mdto-metadata.spec.ts

#### Scenario: No app-side writes to platform metadata fields

- GIVEN the DocuDesk codebase at this change's completion
- WHEN `lib/` is inspected
- THEN no code writes the `retention` or `tmlo` object fields directly
- @e2e exclude static codebase property, enforced by review + a PHPUnit architecture test, not a browser flow

### Requirement: mdtoSupplement schema models the attributes OR lacks (REQ-DDTMM-002)

The document register MUST gain an `mdtoSupplement` schema
(`hardValidation: true`, at most one supplement per described record):
`objectRef` (UUID of the described record), `objectType`
(`document` | `dossier`), `aggregatieniveau` (enum
`archiefstuk` | `dossier`), `omschrijving`, `taal` (RFC 5646, default
`nl`), `dekkingInTijdBegin`/`dekkingInTijdEind` (dates),
`beperkingGebruik[]` (`type` enum `avg-persoonsgegevens` |
`woo-uitzondering` | `auteursrecht` | `vertrouwelijk` | `overig`,
`grondslag`, `omschrijving`, optional `einddatum`), `betrokkene[]` (`rol`,
`naamRef` — a reference, never PII copied in clear), and
`archiefvormerOverride`. The register version MUST be bumped so
`ConfigurationService::importFromApp()` imports it on boot.

#### Scenario: Register import creates the supplement schema and seed

- GIVEN a fresh install after `ConfigurationService::importFromApp()` runs
- WHEN the document register's schemas are listed
- THEN `mdtoSupplement` exists with the documented properties and enums
- AND the seeded demo supplement validates against it
- @e2e exclude register import is a boot-time backend concern with no UI surface of its own — covered by PHPUnit register-import assertions (tests/unit/Settings/)

#### Scenario: Betrokkene never carries cleartext PII

- GIVEN an operator adding a betrokkene entry in the MDTO panel
- WHEN the entry is saved
- THEN it stores a `naamRef` reference to the canonical contact record
- AND no detection-output entity text is copied into the supplement
- @e2e exclude data-shape rule on a backend write — covered by PHPUnit schema-validation tests

### Requirement: DocuDesk context prefills the supplement as confirmable proposals (REQ-DDTMM-003)

The app MUST prefill supplement values it can derive —
`aggregatieniveau` from the record type, `dekkingInTijd` from
document/dossier dates, and `beperkingGebruik` proposals from DocuDesk
state (an unresolved or objected consent record proposes
`avg-persoonsgegevens` with its grondslag; an active publication
prohibition match proposes `woo-uitzondering` with its legal authority; an
existing anonymised derivative proposes a restriction note naming the
anonymised rendition). Every derived `beperkingGebruik` value MUST be
flagged as proposed until an operator confirms it, and the export MUST use
only confirmed values.

#### Scenario: Objection state proposes an AVG use restriction

- GIVEN a document with a consent record in `objection_received`
- WHEN the MDTO panel loads its supplement
- THEN a proposed `beperkingGebruik` entry of type `avg-persoonsgegevens` with the consent grondslag is shown as a proposal chip
- AND it is excluded from export until the operator accepts it
- @e2e tests/e2e/workflows/tmlo-mdto-metadata.spec.ts

#### Scenario: Dekking in tijd derives from document dates

- GIVEN a document with known creation/closure dates
- WHEN prefill runs
- THEN `dekkingInTijdBegin`/`dekkingInTijdEind` are proposed from those dates
- AND an operator can overwrite them before confirmation
- @e2e exclude derivation rule — covered by PHPUnit prefill-matrix tests (tests/unit/Service/MdtoMappingServiceTest.php)

### Requirement: Completeness validation gates overbrenging (REQ-DDTMM-004)

The app MUST validate MDTO completeness per record — verdict plus an
explicit missing-field list — against the MDTO minimum set (identificatie,
naam, waardering, classificatie, archiefvormer, aggregatieniveau, and
dekkingInTijd for `bewaren` records), and the overbrenging flow MUST NOT
proceed while the verdict is incomplete. The gate MUST be at least as
strict as OpenRegister's `MdtoXmlGenerator::validateRequiredFields()`: any
input OR's generator would reject MUST already be reported incomplete by
the gate.

#### Scenario: Incomplete record cannot be transferred

- GIVEN a `bewaren` record awaiting overbrenging whose supplement lacks `aggregatieniveau`
- WHEN the archivist attempts the transfer action
- THEN the action is blocked with a completeness banner listing `aggregatieniveau` as missing
- @e2e tests/e2e/workflows/tmlo-mdto-metadata.spec.ts

#### Scenario: Gate implies generator success

- GIVEN any record input the gate reports complete
- WHEN OpenRegister's MDTO generation runs on the required subset
- THEN the generator raises no missing-field error
- AND this implication is pinned by a unit test against OR HEAD behaviour
- @e2e exclude backend validator cross-check — covered by PHPUnit (tests/unit/Service/MdtoMappingServiceTest.php)

### Requirement: Deterministic MDTO sidecar per document and dossier (REQ-DDTMM-005)

The app MUST export, per document/dossier record, an MDTO XML sidecar (with
a lossless JSON projection) in the `https://www.nationaalarchief.nl/mdto`
namespace: core elements matching OpenRegister's generator output for the
same input (element names, waardering mapping — pinned by fixture tests),
supplement elements (aggregatieniveau, omschrijving, taal, dekkingInTijd,
beperkingGebruik, betrokkene), and a `bestand` section (filename, size,
format, sha256 checksum) from the Nextcloud file facts. Output MUST be
deterministic for unchanged input. A dossier sidecar MUST aggregate its
member documents as archiefstukken at `aggregatieniveau: dossier`
(references by identificatie), read through the dossier capability's
surface without modifying dossier-register schemas (sibling ownership —
see design.md).

#### Scenario: Document sidecar carries core, supplement and bestand

- GIVEN a complete document record with a confirmed supplement
- WHEN the operator exports the MDTO sidecar
- THEN the XML is one `mdto:informatieobject` in the mdto namespace with identificatie, naam, waardering, classificatie, archiefvormer, aggregatieniveau, dekkingInTijd, confirmed beperkingGebruik and a bestand section with the file's sha256 checksum
- AND exporting again without changes yields byte-identical output
- @e2e tests/e2e/workflows/tmlo-mdto-metadata.spec.ts

#### Scenario: Dossier sidecar aggregates member archiefstukken

- GIVEN a dossier with two member documents, each with complete MDTO metadata
- WHEN the dossier sidecar is exported
- THEN it contains a dossier-level informatieobject at `aggregatieniveau: dossier` referencing both members as archiefstukken by identificatie
- @e2e exclude aggregation assembly — covered by PHPUnit fixture tests (tests/unit/Service/MdtoMappingServiceTest.php)

### Requirement: e-Depot handoff rides OpenRegister's transfer stack (REQ-DDTMM-006)

The overbrenging action MUST deliver via OpenRegister's
`EdepotTransferService` (SIP built with the transport and profile
configured in OR's e-depot settings); DocuDesk MUST NOT contain any e-depot
transport, protocol or HTTP client code. On confirmed ingestion the record
MUST get `retention.archiefstatus = overgebracht` (read-only per the
retention engine) and the exported sidecar MUST be attached to the record
as an OR file attachment for the municipal audit trail. When no e-depot
transport is configured, the action MUST degrade to an explicit
"export for manual delivery" download — never a silent no-op.

#### Scenario: Configured transport transfers and seals the record

- GIVEN a complete `bewaren` record and a configured OR e-depot transport
- WHEN the archivist confirms overbrenging
- THEN the SIP is delivered through OpenRegister's transfer service
- AND on confirmation the record's archiefstatus is `overgebracht` and the sidecar is attached to the record
- @e2e exclude requires a live e-depot transport out of scope for the Playwright suite — covered by PHPUnit with a fake TransportInterface (tests/unit/Service/MdtoMappingServiceTest.php)

#### Scenario: Missing transport degrades to manual export

- GIVEN no e-depot transport configured in OpenRegister
- WHEN the archivist opens the overbrenging action
- THEN the UI offers the sidecar download for manual delivery and states that no transport is configured
- @e2e tests/e2e/workflows/tmlo-mdto-metadata.spec.ts

#### Scenario: No transport code in DocuDesk

- GIVEN the DocuDesk codebase at this change's completion
- WHEN `lib/` is inspected
- THEN no class implements or calls an e-depot transport/protocol directly
- @e2e exclude static codebase property, enforced by review + a PHPUnit architecture test, not a browser flow

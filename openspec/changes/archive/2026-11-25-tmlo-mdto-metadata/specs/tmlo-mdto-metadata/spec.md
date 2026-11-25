# tmlo-mdto-metadata Specification (delta)

## Purpose

MDTO (Metagegevens voor Duurzaam Toegankelijke Overheidsinformatie, the
successor of TMLO) archival metadata for Filinq records, so that
permanently kept records can actually reach an e-depot: the six core
archival attributes ride OpenRegister's `retention` field (populated by the
`archiefwet-retention-engine` dependency), a `mdtoSupplement` schema
carries the MDTO informatieobject attributes OR does not model
(aggregatieniveau, dekkingInTijd, beperkingGebruik, betrokkene, taal,
omschrijving), Filinq prefills what it uniquely knows (AVG/Woo-derived
use restrictions from consent/prohibition/anonymisation state),
completeness validation gates overbrenging, and deterministic MDTO XML/JSON
sidecars per document/dossier feed OR's e-depot transfer stack.

## ADDED Requirements

### Requirement: MDTO core attributes have a single source of truth (REQ-DDTMM-001)

The app MUST read every MDTO core archival attribute (waardering,
bewaartermijn, archiefactiedatum, archiefstatus, classificatie) only from
OpenRegister's `retention` field and MUST NOT copy any of them into a Filinq
schema. The archiefvormer MUST default to OpenRegister's
`organisation_identifier` setting, overridable per record only through
`mdtoSupplement.archiefvormerOverride`. Filinq MUST NOT write the OR-owned
`retention` or `tmlo` structures.

#### Scenario: Core fields render from retention, read-only

- GIVEN a record whose `retention` block carries nominatie, termijn, actiedatum and classificatie
- WHEN the MDTO panel on the document detail page renders
- THEN the core attributes display the retention values read-only
- AND no Filinq schema property duplicates them
- @e2e tests/e2e/workflows/tmlo-mdto-metadata.spec.ts

#### Scenario: No app-side writes to platform metadata fields

- GIVEN the Filinq codebase at this change's completion
- WHEN `lib/` is inspected
- THEN no code writes the `retention` or `tmlo` object fields directly
- @e2e exclude static codebase property, enforced by review + a PHPUnit architecture test, not a browser flow

### Requirement: mdtoSupplement schema models the attributes OR lacks (REQ-DDTMM-002)

The document register MUST gain an `mdtoSupplement` schema
(`hardValidation: true`, at most one per described record) with `objectRef`
(UUID of the described record), `objectType` (`document` | `dossier`),
`aggregatieniveau` (`archiefstuk` | `dossier`), `omschrijving`, `taal`
(RFC 5646, default `nl`), `dekkingInTijdBegin`/`dekkingInTijdEind` (dates),
`beperkingGebruik[]`, `betrokkene[]` and `archiefvormerOverride`. The
register version MUST be bumped so boot import picks it up.

#### Scenario: Register import creates the supplement schema and seed

- GIVEN a fresh install after `ConfigurationService::importFromApp()` runs
- WHEN the document register's schemas are listed
- THEN `mdtoSupplement` exists with the documented properties and enums
- AND the seeded demo supplement validates against it
- @e2e exclude register import is a boot-time backend concern with no UI surface of its own — covered by PHPUnit register-import assertions (tests/unit/Settings/)

#### Scenario: A use restriction has a closed type

- GIVEN the shipped `mdtoSupplement` schema
- WHEN a `beperkingGebruik` entry is validated
- THEN its `type` is one of `avg-persoonsgegevens`, `woo-uitzondering`, `auteursrecht`, `vertrouwelijk` or `overig`
- AND it requires a `grondslag` and may carry an `omschrijving` and an `einddatum`
- @e2e exclude schema shape — covered by PHPUnit (tests/unit/Settings/MdtoSupplementSchemaTest.php::testTheSchemaIsInTheRegisterWithItsFields)

#### Scenario: Betrokkene never carries cleartext PII

- GIVEN an operator adding a betrokkene entry in the MDTO panel
- WHEN the entry is saved
- THEN it stores a `naamRef` reference to the canonical contact record
- AND no detection-output entity text is copied into the supplement
- @e2e exclude data-shape rule on a backend write — covered by PHPUnit schema-validation tests

### Requirement: Filinq context prefills the supplement as confirmable proposals (REQ-DDTMM-003)

The app MUST prefill what it can derive: `aggregatieniveau` from the record
type, `dekkingInTijd` from document or dossier dates, and `beperkingGebruik`
proposals from Filinq state (an unresolved or objected consent proposes
`avg-persoonsgegevens` with its grondslag, a prohibition match
`woo-uitzondering` with its legal authority, an anonymised derivative a note
naming it). Each derived restriction MUST stay proposed until an operator
confirms it; the export MUST use only confirmed values.

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

The app MUST give each record a completeness verdict with an explicit
missing-field list against the MDTO minimum set (identificatie, naam,
waardering, classificatie, archiefvormer, aggregatieniveau, and
dekkingInTijd for `bewaren` records); overbrenging MUST NOT proceed while it
is incomplete. Any input OpenRegister's
`MdtoXmlGenerator::validateRequiredFields()` would reject MUST already be
reported incomplete.

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

The app MUST export per document or dossier record a deterministic MDTO XML
sidecar, with a lossless JSON projection, in the
`https://www.nationaalarchief.nl/mdto` namespace: core elements matching
OpenRegister's generator (names and waardering mapping pinned by fixtures),
the supplement elements, and a `bestand` section (filename, size, format,
sha256) from the file. A dossier sidecar MUST reference its members as
archiefstukken, read without changing dossier-register schemas.

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

Overbrenging MUST deliver through OpenRegister's `EdepotTransferService`
with the transport and profile of its e-depot settings; Filinq MUST NOT
contain e-depot transport, protocol or HTTP client code. On confirmed
ingestion the record MUST get `retention.archiefstatus = overgebracht` and
the sidecar MUST be attached to it as an OpenRegister file. Without a
transport the action MUST offer an explicit export for manual delivery,
never a silent no-op.

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

#### Scenario: No transport code in Filinq

- GIVEN the Filinq codebase at this change's completion
- WHEN `lib/` is inspected
- THEN no class implements or calls an e-depot transport/protocol directly
- @e2e exclude static codebase property, enforced by review + a PHPUnit architecture test, not a browser flow

# Design: tmlo-mdto-metadata

## Context

### Verified at OpenRegister HEAD (ebedbdd5a)

Two partially-overlapping MDTO surfaces exist:

- **TMLO stack**: `ObjectEntity.tmlo` JSON with six core sub-fields
  (`classificatie`, `archiefnominatie` `blijvend_bewaren`|`vernietigen`,
  `archiefactiedatum`, `archiefstatus`
  `actief`|`semi_statisch`|`overgebracht`|`vernietigd`, `bewaarTermijn`,
  `vernietigingsCategorie`); register-level gate
  (`configuration.tmloEnabled === true`); `TmloService` with a status state
  machine and per-target required-field rules; export endpoints
  `GET /api/tmlo/{register}/{schema}/{id}/export`, `/export` (batch),
  `/summary`. `TmloService::generateMdtoXml()` reads **`tmlo`** and emits
  `mdto:informatieobject` (identificatie, naam, classificatie, waardering,
  archiefactiedatum, archiefstatus...) in namespace
  `https://www.nationaalarchief.nl/mdto`.
- **e-Depot stack**: `EdepotTransferService::executeTransfer()` builds a SIP
  over a `TransportInterface` with configurable transports + profiles
  (`getTransportConfig()`, `getAvailableProfiles()`); settings at
  `/api/settings/edepot` (+ `/test`). Its `MdtoXmlGenerator::generate()`
  reads **`retention`** + files metadata and requires
  `retention.archiefnominatie`, `retention.bewaartermijn` and the
  `organisation_identifier` app setting (archiefvormer); emits
  identificatie, naam, waardering, bewaartermijn, informatiecategorie,
  archiefvormer, toelichting, bestand.

**Consequences.** (1) The two generators read different per-object fields —
an object populated only via the retention engine exports through the
e-depot generator but returns 422 from `/api/tmlo/*` ("no TMLO metadata").
(2) Neither generator emits `aggregatieniveau`, `beperkingGebruik`,
`dekkingInTijd`, `betrokkene`, `taal` or `omschrijving` — MDTO
informatieobject attributes an e-depot ingest profile typically requires.
Both gaps are OR-side; this change files them as OR issues and designs
around them without duplicating OR logic (see D3/D5).

### DocuDesk side

`archiefwet-retention-engine` (dependency) puts `retention` archival
metadata on record objects and defines the overbrenging state. The
`document` register hosts record schemas; the `dossier` register's canonical
spec is owned by a sibling change — dossier aggregation here is capability
behaviour only. Documents carry the file facts (name, size, mimetype,
content hash) the MDTO bestand section needs; the consent register
(`publicationConsent`, `publicationProhibition`) and anonymisation state
know *why* a record's use is restricted — the raw material for
`beperkingGebruik` that no generic platform layer can derive.

Stakeholders: gemeente DIV/archivists preparing overbrenging; e-depot
suppliers ingesting SIPs; Woo teams whose disclosure restrictions must
survive transfer as beperkingGebruik.

## Goals / Non-Goals

**Goals:**

- Complete MDTO informatieobject metadata per document/dossier: OR core
  fields + a DocuDesk supplement, with context-derived prefill.
- A hard completeness gate in front of overbrenging.
- Deterministic MDTO XML/JSON sidecars per document/dossier, e-depot-ready
  (bestand + checksum), dossier-aggregating.

**Non-Goals:**

- No second copy of retention-owned fields (waardering, termijn,
  actiedatum, classificatie live in `retention` only).
- No transport/protocol code; no DocuDesk e-depot client (OR's
  EdepotTransferService owns delivery).
- No arbitrary-register MDTO tooling; no fix for OR's internal
  `tmlo`/`retention` duplication (OR issue).

## Decisions

### D1 — Single source of truth: `retention` core + `mdtoSupplement` extras

The six core archival attributes come from `retention` (populated by the
retention engine). The MDTO attributes OR does not model live in one new
schema, **`mdtoSupplement`** (document register):

- `objectRef` (UUID of the described record object), `objectType`
  (`document` | `dossier`),
- `aggregatieniveau` (enum `archiefstuk` | `dossier`),
- `omschrijving` (string), `taal` (RFC 5646 code, default `nl`),
- `dekkingInTijdBegin` / `dekkingInTijdEind` (dates),
- `beperkingGebruik[]` (objects: `type` enum
  `avg-persoonsgegevens` | `woo-uitzondering` | `auteursrecht` |
  `vertrouwelijk` | `overig`; `grondslag` string; `omschrijving`;
  `einddatum` optional),
- `betrokkene[]` (objects: `rol`, `naamRef` — a reference, never
  PII-in-clear copied from detection output),
- `archiefvormerOverride` (optional; default comes from OR's
  `organisation_identifier`).

Rejected alternatives: (a) folding these onto each record schema — smears
MDTO concerns across every schema and breaks when a schema is
sibling-owned (dossier); (b) storing them in `retention` — that field is
OR-owned MDM territory (never write platform-owned structures from an app);
(c) a new register — the supplement describes document-register artifacts
and belongs beside them.

### D2 — Prefill is proposal, not decree

`MdtoMappingService::prefill()` derives, per record: `aggregatieniveau`
from record type; `dekkingInTijd` from the document's creation/closure
dates (dossier: min/max over members); `beperkingGebruik` proposals from
DocuDesk state — an unresolved/objection consent → `avg-persoonsgegevens`
with the consent grondslag; an active `publicationProhibition` match →
`woo-uitzondering` with the prohibition's legalAuthority; an anonymised
derivative existing → a note that the public rendition is the anonymised
one. Proposals land in the supplement flagged `proposed` until an operator
confirms in the MDTO panel; export uses confirmed values plus explicitly
accepted proposals. Rationale: beperkingGebruik has legal effect at the
e-depot; a wrong silent value is worse than an empty one.

### D3 — Completeness gate before overbrenging

`MdtoMappingService::validateCompleteness()` returns
`{complete: bool, missing: string[]}` against the MDTO minimum set:
identificatie (uuid + bron), naam, waardering
(`retention.archiefnominatie`), `classificatie`, archiefvormer
(`organisation_identifier` or override), `aggregatieniveau`, and — for
`bewaren` records — `dekkingInTijd`. The overbrenging surface from
`archiefwet-retention-engine` calls this gate and blocks transfer while
incomplete, listing the missing fields verbatim. The rule mirrors (and
never weakens) `MdtoXmlGenerator::validateRequiredFields()` — if OR's
generator would 422, the gate MUST already have said "incomplete"; a unit
test pins gate-implies-generator-success on the required subset.

### D4 — Sidecar assembly: OR core + supplement merge, deterministic

`MdtoMappingService::buildSidecar()` produces one `mdto:informatieobject`
per record: core elements exactly as OR's `MdtoXmlGenerator` emits them
(same namespace, element names and waardering mapping — pinned by fixture
tests), plus the supplement elements (`aggregatieniveau`, `omschrijving`,
`taal`, `dekkingInTijd`, `beperkingGebruik`, `betrokkene`), plus the
`bestand` section from Nextcloud file facts (name, size, format, sha256
checksum). JSON projection is a lossless mirror of the XML. Output is
deterministic for unchanged input (stable element order, no timestamps
outside declared fields). Dossier sidecar: an `informatieobject` at
`aggregatieniveau: dossier` aggregating member documents as archiefstukken
(identificatie references), exported as one XML document.

**Uncertainty, stated**: assembling MDTO elements in DocuDesk duplicates
element construction OR already half-owns. The alternatives were worse —
patching OR's two generators from app side is impossible, and waiting on an
upstream extension-hook makes this change undeliverable. An OR issue
proposing supplement/extension-element support in `MdtoXmlGenerator` is
filed at apply time; when OR ships it, DocuDesk's assembly shrinks to a
data provider. The DocuDesk assembly reuses OR's constants
(`TmloService::MDTO_NAMESPACE`) rather than re-declaring them.

### D5 — Transfer rides OR's e-depot stack

The "Overbrengen" action (on the retention engine's overbrenging surface)
runs: completeness gate (D3) → sidecar build (D4) → OR
`EdepotTransferService` SIP transfer using the transport/profile configured
in OR's `/api/settings/edepot` → on confirmed ingest, `archiefstatus =
overgebracht` (record read-only per the retention engine; the sidecar is
attached to the record as an OR file attachment for the municipal audit
trail). If no e-depot transport is configured, the action degrades to
"export sidecar for manual delivery" (download) — explicitly visible, never
a silent no-op. DocuDesk contains no transport code (architecture test).

### D6 — Frontend per ADR-012 / ADR-003

MDTO panel on document detail (and dossier detail via the dossier
capability's surface): core fields read-only from the OR-rendered
retention block; supplement fields editable via `CnFormDialog`; proposal
chips (accept/edit/reject) for prefill; completeness banner with the
missing-field list; export + overbrengen actions. NC CSS variables only;
NcSelect with `inputLabel`; modals in `src/modals/`.

## OpenRegister service usage (ADR-001 / config.yaml)

| Operation | OR surface |
|---|---|
| Core archival metadata | `retention` (via archiefwet-retention-engine) |
| Supplement CRUD | `ObjectService::saveObject()`/`searchObjects()` on `document`/`mdtoSupplement` (PUT-semantic: carry all fields forward) |
| Archiefvormer default | `organisation_identifier` app setting (read) |
| SIP + delivery | `EdepotTransferService` (transports/profiles from OR settings) |
| Sidecar attachment | OR file attachments on the record object |
| Namespace/constants | `TmloService::MDTO_NAMESPACE` (reused, not re-declared) |

ADR-011 check: checksum via PHP `hash('sha256', ...)`; no new
date/validation utilities; language codes passed opaquely.

## Declarative-vs-imperative decision (ADR-031)

- **Declarative**: `mdtoSupplement` is a plain schema
  (`hardValidation: true`); proposal state is data (`proposed` flags);
  core-field ownership is register configuration (dependency change).
- **Imperative (justified exceptions)**: prefill derivation (cross-register
  aggregation over consent/prohibition/anonymisation state — beyond any
  `x-openregister-*` dialect); completeness validation (multi-source rule
  evaluation, mirror of an OR-side imperative validator); sidecar assembly
  + transfer orchestration (external integration in ADR-031 terms; delivery
  itself stays in OR).

## Seed Data

Shipped in `docudesk_register.json` `objects[]` (nil-UUID/`seed-*`
placeholders, demo-municipality flavour):

```json
{
  "@self": {"register": "document", "schema": "mdtoSupplement", "slug": "demostad-besluit-mdto"},
  "objectRef": "00000000-0000-0000-0000-000000000001",
  "objectType": "document",
  "aggregatieniveau": "archiefstuk",
  "omschrijving": "Besluit subsidietoekenning cultuur (demo)",
  "taal": "nl",
  "dekkingInTijdBegin": "2024-11-03",
  "dekkingInTijdEind": "2024-11-03",
  "beperkingGebruik": [
    {
      "type": "avg-persoonsgegevens",
      "grondslag": "AVG art. 6 lid 1 sub e",
      "omschrijving": "Openbare rendition is de geanonimiseerde afleiding."
    }
  ],
  "betrokkene": []
}
```

## Risks / Trade-offs

- [OR generator drift (element names, waardering map, namespace)] → fixture
  tests pin DocuDesk's core-element output against OR's generator output for
  the same input; verified against OR HEAD at apply time, not this spec's
  snapshot.
- [Two OR metadata fields (`tmlo` vs `retention`)] → DocuDesk writes
  neither directly and reads `retention` only; `/api/tmlo/*` endpoints are
  NOT used while they read `tmlo` (they would 422 on retention-only
  objects); OR issue filed. If OR later unifies, only the read adapter
  changes.
- [beperkingGebruik legal weight] → proposal-not-decree (D2); export marks
  operator-confirmed values only; PII never copied in clear into
  `betrokkene` (references only).
- [SIP profile variance across e-depot suppliers] → profiles are OR
  configuration; DocuDesk emits standard MDTO and leaves
  profile shaping to OR's Edepot profiles; manual-export degradation path.
- [Dossier aggregation touches sibling-owned dossier surface] → this change
  only *reads* dossier data and renders its panel through the dossier
  capability's extension point; the dossier-register spec file is untouched
  (relationship documented here, mirroring the brief's ownership split).

## Migration Plan

Additive: one schema + seeds (register version bump, boot import), one
service, UI panel + actions, gate wiring into the overbrenging surface.
Rollback: remove UI/service; supplements remain inert data. No existing
schema changes; no data migration.

## Open Questions

- Should the sidecar also ship as a Woo-index/DiWoo cross-walk (MDTO ↔
  DiWoo field mapping) for records that were also published? Deferred; the
  publication pipeline owns DiWoo.
- MDTO XSD validation in CI (validate generated XML against the Nationaal
  Archief schema) — desirable; depends on vendoring the XSD; decided at
  apply time.
- Upstream extension-element support in OR's `MdtoXmlGenerator` (issue to
  file) — adoption plan once available.

---
kind: code
tracking_issue: https://github.com/ConductionNL/docudesk/issues/237
depends_on: [archiefwet-retention-engine]
---

# Proposal: tmlo-mdto-metadata

## Why

A Dutch government DMS cannot hand records to an archiefbewaarplaats or
e-depot without standardised archival metadata: **MDTO** (Metagegevens voor
Duurzaam Toegankelijke Overheidsinformatie, Nationaal Archief — the
successor of TMLO) is the interoperability contract every Dutch e-depot
ingestion expects. Norwegian competitor Documaster wins the same category on
Noark 5 conformance; the Dutch analogue is MDTO conformance, and no
Nextcloud-ecosystem app offers it. Without MDTO, the
`archiefwet-retention-engine`'s overbrenging state is a dead end: a record
can be marked for permanent keeping but can never actually reach an e-depot.
Evidence: tracking issue ConductionNL/docudesk#237; intelligence DB
`mg2026-archiefwet-retention` ("transfer-to-archive with TMLO/MDTO
metadata"); Documaster Noark5 analogy from the competitor research.

**Verified at OpenRegister HEAD (ebedbdd5a)**: OR ships two MDTO-related
surfaces — (a) a TMLO stack (`ObjectEntity.tmlo` with six core sub-fields,
register-level `tmloEnabled` gate, `TmloService` status machine, MDTO XML
export at `/api/tmlo/{register}/{schema}/{id}/export` + batch + summary in
the `https://www.nationaalarchief.nl/mdto` namespace) and (b) an e-depot
stack (`EdepotTransferService` building SIPs over configurable
transports/profiles, `MdtoXmlGenerator` emitting
identificatie/naam/waardering/bewaartermijn/informatiecategorie/
archiefvormer/bestand from the `retention` field + the
`organisation_identifier` app setting). Neither emits the full MDTO
informatieobject attribute set (no `aggregatieniveau`, `beperkingGebruik`,
`dekkingInTijd`, `betrokkene`), and the two generators read **different**
per-object fields (`tmlo` vs `retention`). Per ADR-022 DocuDesk consumes
these surfaces and supplies only what is genuinely DocuDesk domain
knowledge: the mapping of document/dossier content onto MDTO attributes,
the AVG/Woo-derived use restrictions, completeness validation before
overbrenging, and the export sidecar assembly.

## What

1. **MDTO metadata model**: the six core archival attributes ride
   OpenRegister (`retention` — populated by the retention engine this change
   depends on); a new `mdtoSupplement` schema in the `document` register
   carries the MDTO informatieobject attributes OR does not model:
   `aggregatieniveau` (archiefstuk | dossier), `omschrijving`, `taal`,
   `dekkingInTijd` (begin/eind), `beperkingGebruik[]`, `betrokkene[]`,
   `archiefvormerOverride`. One supplement per document/dossier record, no
   duplication of any retention-owned field.
2. **Auto-prefill from DocuDesk context**: DocuDesk derives supplement
   values it already knows — `aggregatieniveau` from the record type,
   `dekkingInTijd` from document/dossier dates, and `beperkingGebruik`
   proposals from the consent/prohibition/anonymisation state (a
   DocuDesk-unique capability: the app knows *why* a record's use is
   restricted). Proposals are operator-confirmable, never silently final.
3. **Completeness validation before overbrenging**: a validator that
   returns a per-record verdict + missing-field list against the MDTO
   minimum set; the overbrenging flow from `archiefwet-retention-engine`
   MUST NOT proceed while the verdict is incomplete.
4. **MDTO export sidecar**: per document/dossier an MDTO XML (and JSON
   projection) sidecar assembled from the OR core fields + supplement, in
   the `nationaalarchief.nl/mdto` namespace, including the bestand section
   (filename, size, format, checksum) for e-depot ingestion; dossier
   sidecars aggregate their member archiefstukken.
5. **e-Depot handoff**: transfer rides OR's `EdepotTransferService`
   (SIP + transports + profiles configured in OR settings); DocuDesk ships
   no transport code; confirmed ingestion sets `archiefstatus =
   overgebracht` (read-only per the retention engine).

## Capabilities

### Added Capabilities

- `tmlo-mdto-metadata`: MDTO (TMLO-successor) archival metadata for
  DocuDesk records — supplement schema for the informatieobject attributes
  OpenRegister does not model, DocuDesk-context auto-prefill (including
  AVG/Woo-derived use restrictions), completeness validation gating
  overbrenging, and MDTO XML/JSON export sidecars per document/dossier for
  e-depot ingestion via OpenRegister's e-depot stack.

### Modified Capabilities

- `document-register`: hosts the new `mdtoSupplement` schema.

## Affected Projects

- [x] Project: `docudesk` — `mdtoSupplement` schema + seeds,
  `MdtoMappingService` (prefill + completeness + sidecar assembly), MDTO
  panel in document/dossier detail, overbrenging gate wiring, this OpenSpec
  change.
- Consumed: `openregister` — `retention` archival metadata (via
  `archiefwet-retention-engine`), `EdepotTransferService`/
  `MdtoXmlGenerator`, `/api/tmlo/*` export endpoints, `organisation_identifier`
  setting. Gaps (extension elements in the OR generators, `tmlo` vs
  `retention` duplication) are filed as OR issues, not patched around.
- Depends on: `archiefwet-retention-engine` (retention metadata source,
  overbrenging state).

## Out of Scope

- Retention computation, destruction, selectielijst — `archiefwet-retention-engine`.
- e-depot transport/protocol implementations (OR owns transports/profiles);
  no DocuDesk HTTP client to any e-depot.
- A generic MDTO editor for arbitrary OR registers (DocuDesk documents and
  dossiers only).
- The dossier-register canonical spec file (sibling ownership) — dossier
  aggregation is expressed as capability behaviour here.
- Reconciling OR's internal `tmlo`-vs-`retention` duplication — OR issue.

## Success Criteria

- `openspec validate tmlo-mdto-metadata --strict` exits 0.
- A document record shows an MDTO panel with core fields (read-only, from
  `retention`) and supplement fields, prefilled from DocuDesk context where
  derivable.
- A record with missing MDTO minimum fields cannot enter overbrenging; the
  UI lists exactly which fields are missing.
- The exported sidecar validates: mdto namespace, identificatie/naam/
  waardering/classificatie/archiefvormer/aggregatieniveau/dekkingInTijd/
  beperkingGebruik present, bestand section with checksum; deterministic
  output for unchanged input.
- A dossier export aggregates its member documents as archiefstukken.
- `composer check:strict` and the unit suite pass with zero new violations.

---
status: draft
---
# TMLO / MDTO Metadata

## Purpose

Dutch local government (gemeenten, provincies, waterschappen, gemeenschappelijke regelingen) is bound by the Archiefwet to keep its records duurzaam toegankelijk — durably accessible, findable, interpretable, and transferable to a permanent archive (e-Depot) at the end of the retention period. The two metadata standards that govern this in 2026 are TMLO 1.2.1 (Toepassingsprofiel Metadatering Lokale Overheden), which is the schema that has been in production at most gemeenten for the past decade, and MDTO 1.0 (Metadata Duurzaam Toegankelijke Overheidsinformatie), the Nationaal Archief's successor that is rolling out as the new mandatory format for transfer to the rijks-e-Depot from 2025 onwards. Both standards must be supported in parallel: existing records ingested or born under TMLO need to remain valid TMLO until their transfer date, and new records — especially those destined for the Nationaal Archief — must be born MDTO.

Docudesk today stores documents and their basic metadata in OpenRegister but does not enforce either standard's required-element set, does not validate against either schema, does not couple metadata to a bewaartermijn through a Selectielijst, and cannot produce an export package that an e-Depot will accept. This brief defines what it takes to close those gaps: a metadata model that covers TMLO 1.2.1 and MDTO 1.0 verplichte elementen per document-type (zaak-document, ongestructureerd document, document-record, etc.), validation on save and on export, a binding to bewaartermijnen from a configurable Selectielijst, globally unique identification per NEN-2082, mappings to and from ZGW DocumentenAPI / Common Ground metadata so a document received from a zaak-systeem arrives with usable metadata, and an export step that emits the standardised TMLO XML or MDTO XML package that an e-Depot ingest workflow expects.

The choice to do TMLO and MDTO together rather than only MDTO is deliberate. TMLO is the lived reality for most municipalities right now; MDTO is the destination. Treating them as two profiles over a shared underlying metadata model — with a documented crosswalk — lets a single docudesk installation hold both kinds of records simultaneously and migrate gradually, without forcing an org-wide flag-day conversion. The mapping to ZGW and Common Ground is the third side of the triangle: every modern Dutch gemeente has zaak-systemen that produce documents, and those documents arrive with ZGW DocumentenAPI metadata that needs to flow into TMLO/MDTO fields without manual re-entry.

## Data Model

The metadata model is a single `document_metadata` object attached to every docudesk document, with a discriminator field `profile` that selects either `tmlo-1.2.1` or `mdto-1.0`. Both profiles share a common base — identification, classification, dates, actors — and add profile-specific fields.

Shared base fields: `id` (UUID), `documentId` (UUID, foreign key to the docudesk document), `profile` (enum), `profileVersion` (string), `identification` (object: `kenmerk` — the human-readable identifier, `uri` — the globally unique identifier per NEN-2082, `bron` — the system that minted the identifier), `naam` (string, required), `omschrijving` (string), `taal` (ISO 639-2 code, required), `aggregatieniveau` (enum: `archief`, `serie`, `dossier`, `record`), `classificatie` (object: `code`, `omschrijving`, `bron` — typically a verwijzing into the gemeente's Documentair Structuurplan), `dekking` (object: `geografisch`, `temporeel`), `event` (array of `{type, tijdstip, actor, instrument}` — at minimum a `creation` event), `actoren` (array of `{rol, naam, identifier, type}` — types include `natuurlijkPersoon`, `nietNatuurlijkPersoon`, `organisatie`), `bewaartermijn` (object — see Selectielijst section below), `openbaarheid` (object: `status` per Woo, `grondslag`, `vervaltermijn`), `vertrouwelijkheidsaanduiding` (Wob/Woo enum), `relatie` (array of `{type, target, omschrijving}` — types include `isPartOf`, `references`, `replaces`, `replacedBy`).

TMLO 1.2.1 adds: `dctermsType` (TMLO-specific type list), `mediumOpReceptie`, `verschijningsvorm` (object: `formaat`, `bestandsformaat`, `omvang`), `integriteit` (object: `algoritme`, `waarde`, `tijdstip`), `dekkingInTijd`, `vorm` (`structuur`, `redactie`).

MDTO 1.0 adds: `dekkingInTijdBegin` / `dekkingInTijdEind` (split per MDTO schema), `bestand` (array — MDTO models each rendition as a Bestand object with `omvang`, `bestandsformaat` via FDD reference, `checksum` of type `Checksum` with `checksumAlgoritme` + `checksumWaarde` + `checksumDatum`, `URLBestand`), `betrokkene` (replaces TMLO `actoren` with stricter typing), `archiefvormer` (required for records destined for the Nationaal Archief).

A `selectielijst_koppeling` object links the document to the Selectielijst entry that governs its retention. Fields: `id`, `documentMetadataId`, `selectielijstId` (UUID — references an entry in a configurable selectielijst register, defaulting to the VNG selectielijst gemeenten 2020), `procestype` (string), `resultaat` (string), `bewaartermijnJaren` (integer), `bewaartermijnTrigger` (enum: `definitief`, `eindeZaak`, `eindeRelatie`, `vernietigingDatum`), `vernietigingDatum` (date — computed from `event[type=creation].tijdstip + bewaartermijnJaren` when trigger is `definitief`, or set when the trigger event fires), `vernietigingsStatus` (enum: `actief`, `gepland`, `uitgesteld`, `vernietigd`, `overgebracht`).

An `identificatie_register` object enforces the NEN-2082 unique-identifier rule. Fields: `id`, `uri` (string, unique constraint across the instance), `documentMetadataId`, `mintedAt`, `mintedBy`, `scheme` (enum: `URN`, `URI`, `Handle`, `DOI`). The minter is a service that constructs the URI from an instance-configured base (e.g. `https://docs.gemeente-zeist.nl/doc/`) plus a UUIDv7 — UUIDv7 was chosen so the identifier sorts by mint time, which simplifies operational debugging and matches the NEN-2082 recommendation that identifiers be persistent and globally unique without coordination.

A `zgw_mapping` object holds the crosswalk applied to an inbound ZGW DocumentenAPI EnkelvoudigInformatieObject. Fields: `id`, `documentMetadataId`, `zgwInformatieobjecttype` (URI), `zgwIdentificatie`, `mappingApplied` (object — the field-by-field mapping result), `unmappedFields` (array — anything in the ZGW payload that did not land in TMLO/MDTO, surfaced to the user for manual handling).

All objects live in a `docudesk_metadata` register on OpenRegister so they share lifecycle, ACL, audit, and search infra with the documents themselves. The register's schema versions are pinned to the TMLO/MDTO profile versions to make profile upgrades explicit.

## Requirements

### REQ-MD-001 — Required-element enforcement per profile on save

The system SHALL validate every `document_metadata` object against its declared profile's required-element list on save and SHALL refuse to persist objects that are missing required elements.

- GIVEN a `document_metadata` with `profile=tmlo-1.2.1` and missing `naam`
  WHEN the object is saved
  THEN the API SHALL respond 400 with a field-level error naming `naam` and SHALL NOT persist the object.
- GIVEN a `document_metadata` with `profile=mdto-1.0` that has no `archiefvormer` and is flagged for Nationaal Archief transfer
  WHEN the object is saved
  THEN the API SHALL respond 400 with `archiefvormer required for nationaal-archief transfer`.
- GIVEN a `document_metadata` with all required elements present and well-typed
  WHEN the object is saved
  THEN it SHALL be persisted and the schema-version SHALL be stamped on the row.

### REQ-MD-002 — Bewaartermijn binding from configurable Selectielijst

The system SHALL link every document to exactly one Selectielijst entry, SHALL compute `vernietigingDatum` from the entry's `bewaartermijnJaren` + trigger event, and SHALL allow the Selectielijst register to be configured per instance.

- GIVEN a Selectielijst entry with `bewaartermijnJaren=10` and `bewaartermijnTrigger=definitief`
  WHEN a document is linked to that entry with `event.creation.tijdstip=2024-03-01`
  THEN `vernietigingDatum` SHALL be computed as `2034-03-01` and stored.
- GIVEN a Selectielijst entry with `bewaartermijnTrigger=eindeZaak`
  WHEN the document is linked
  THEN `vernietigingDatum` SHALL be null until the linked zaak is closed, at which point it SHALL be computed and stored.
- GIVEN an instance whose Selectielijst register is configured to a custom register
  WHEN a document is linked
  THEN the link SHALL be validated against that register, not the VNG default.

### REQ-MD-003 — Globally unique identifier minted per NEN-2082

The system SHALL mint exactly one globally unique URI per document (NEN-2082), SHALL persist it in `identification.uri` and the `identificatie_register`, and SHALL refuse to mint a second URI for the same document.

- GIVEN a new document with no identifier
  WHEN metadata is first saved
  THEN a URI SHALL be minted using the instance-configured base + UUIDv7 and recorded in both `identification.uri` and `identificatie_register`.
- GIVEN an existing document whose metadata already has a URI
  WHEN metadata is updated
  THEN the existing URI SHALL be preserved unchanged.
- GIVEN two documents minted on the same instance
  WHEN both are persisted
  THEN their URIs SHALL be distinct.

### REQ-MD-004 — ZGW DocumentenAPI inbound mapping

The system SHALL accept an inbound ZGW DocumentenAPI EnkelvoudigInformatieObject and SHALL produce a TMLO or MDTO `document_metadata` object by applying the documented crosswalk, SHALL record the mapping in `zgw_mapping`, and SHALL surface any unmapped fields for manual review.

- GIVEN an inbound EnkelvoudigInformatieObject with `titel`, `identificatie`, `creatiedatum`, `vertrouwelijkheidaanduiding`, `informatieobjecttype` populated
  WHEN the mapping runs in MDTO mode
  THEN `naam`, `kenmerk`, `event.creation.tijdstip`, `vertrouwelijkheidsaanduiding`, and `classificatie.code` SHALL be populated from the corresponding ZGW fields per the documented crosswalk.
- GIVEN an inbound EIO with a non-standard custom field
  WHEN the mapping runs
  THEN the custom field SHALL appear in `zgw_mapping.unmappedFields` and SHALL NOT cause the mapping to fail.

### REQ-MD-005 — Common Ground / NL API outbound mapping

The system SHALL expose the metadata through Common Ground / NL API conventions, with field names per the relevant schemas (`https://schemas.nl/`), so a consuming app sees a familiar shape regardless of which profile (TMLO/MDTO) the underlying record uses.

- GIVEN a TMLO metadata record exposed through the API
  WHEN consumed by a Common Ground client
  THEN the JSON SHALL use snake_case keys per NL API and SHALL include both the TMLO-native fields and the common-base normalisation.
- GIVEN an MDTO metadata record consumed by the same client
  WHEN the same fields are present in both profiles (e.g. `naam`, `omschrijving`)
  THEN the JSON payload SHALL be field-shape compatible across profiles for the shared fields.

### REQ-MD-006 — TMLO 1.2.1 XML export

The system SHALL produce an XML document per document conforming to TMLO 1.2.1 schema with all required elements populated and SHALL validate against the official TMLO XSD before returning.

- GIVEN a fully-populated `document_metadata` with `profile=tmlo-1.2.1`
  WHEN the TMLO export endpoint is called
  THEN it SHALL return an XML body that validates against the TMLO 1.2.1 XSD with no errors.
- GIVEN a `document_metadata` that fails TMLO validation at export time (e.g. an element was emptied after initial save)
  WHEN the export endpoint is called
  THEN the API SHALL respond 422 with the XSD validation errors and SHALL NOT return an XML body.

### REQ-MD-007 — MDTO 1.0 XML export

The system SHALL produce an XML document per document or aggregation conforming to MDTO 1.0 schema with all required elements populated and SHALL validate against the official MDTO XSD before returning.

- GIVEN a fully-populated `document_metadata` with `profile=mdto-1.0`
  WHEN the MDTO export endpoint is called
  THEN it SHALL return an XML body that validates against the MDTO 1.0 XSD with no errors.
- GIVEN an MDTO Bestand whose `checksumAlgoritme` is not in the FDD-allowed set
  WHEN the export is validated
  THEN the API SHALL respond 422 naming the offending element.

### REQ-MD-008 — Aggregation-aware export packages for e-Depot

The system SHALL produce an export package (TMLO or MDTO) at archief/serie/dossier/record aggregation level that bundles the metadata XML(s), the document bitstreams, and a manifest listing every file with its checksum.

- GIVEN a dossier with three documents, each with one rendition
  WHEN the MDTO export package is requested for that dossier
  THEN the resulting zip SHALL contain one MDTO XML for the dossier, one MDTO XML per document, one XML per Bestand, the three bitstreams, and a manifest listing every file with its SHA-256 checksum.
- GIVEN any package produced by the export
  WHEN unpacked and re-validated by an external XSD validator
  THEN every XML in the package SHALL validate against its declared schema.

### REQ-MD-009 — Identifier and bewaartermijn round-trip on import

The system SHALL accept its own export packages as imports, preserve the original identifier (`identification.uri`), and preserve the original bewaartermijn binding rather than minting a new one or applying default retention.

- GIVEN an MDTO package previously exported from this docudesk instance
  WHEN it is re-imported
  THEN the resulting `document_metadata.identification.uri` SHALL equal the original and `selectielijst_koppeling.vernietigingDatum` SHALL equal the original.
- GIVEN an MDTO package whose URI collides with an existing document on the importing instance
  WHEN it is imported
  THEN the API SHALL respond 409 with the conflict details and SHALL NOT overwrite the existing record.

### REQ-MD-010 — Profile-version pinning and migration record

The system SHALL stamp the schema version (TMLO 1.2.1 or MDTO 1.0) on every metadata row at save, SHALL allow records to be migrated from TMLO to MDTO via an explicit migration step, and SHALL record the migration as an event on the document.

- GIVEN a TMLO 1.2.1 record
  WHEN the migration-to-MDTO endpoint is called
  THEN a new MDTO 1.0 record SHALL be created (replacing or alongside the TMLO record per config), the original SHALL be marked migrated, and an `event` of type `migration` SHALL be appended to the document with both profile versions and the timestamp.
- GIVEN any saved record
  WHEN it is read
  THEN `profileVersion` SHALL be present and SHALL match the schema version actually applied at save time.

## Standards & Sources

The two profile schemas are the canonical sources: TMLO 1.2.1 from KING/VNG (`gemmaonline.nl`), with its accompanying XSD; MDTO 1.0 from the Nationaal Archief (`nationaalarchief.nl/archiveren/mdto`) with its XSD, the FDD (File Format DataBase) reference list for bestandsformaten, and the published documentation of required elements per object type. The element semantics — what `aggregatieniveau`, `dekking`, `event`, `actoren` mean — are taken from each standard's own documentation rather than re-derived; the data model in this brief is a representation of those standards' canonical fields, not an opinion about them.

NEN-2082 (the Dutch national norm for Information and Records Management) provides the rule that every record must have a globally unique identifier that persists across system migrations. The choice of UUIDv7 plus an instance-configured base URI satisfies "global uniqueness without coordination" while keeping identifiers sortable by mint time, which is operationally useful and consistent with how modern identifier mints (e.g. Datacite's DOI minter) behave.

The Selectielijst is governed by the Archiefwet 1995 (and the modernised Archiefwet 2024) plus the published selectielijsten per overheidslaag — for municipalities, the VNG Selectielijst gemeenten en intergemeentelijke organen 2020. The Selectielijst is data, not code: the docudesk instance points at a register that holds the selectielijst entries, so an organisation can override or extend with their own additions without forking the code.

The ZGW DocumentenAPI mapping uses the official VNG DocumentenAPI 1.x schema (currently 1.5 in production at most gemeenten, with 1.6 rolling out) as the inbound shape; the crosswalk is documented in the docudesk spec and follows the conventions already used by the Nationaal Archief in its TMLO-to-MDTO migration guides. The NL API design rules (`docs.geostandaarden.nl/api/API-Designrules/`) and the central `schemas.nl/` repository govern the JSON shape on outbound endpoints, so a Common Ground client sees snake_case keys, ISO 8601 timestamps, and consistent envelope conventions.

For the e-Depot transfer step, the relevant standards are the rijks-e-Depot's intake-specifications (the Nationaal Archief publishes a SIP — Submission Information Package — profile) and, in the case of municipal e-Depots like the e-Depot van de Erfgoedinstelling, that institution's local SIP profile derived from the same MDTO base. The exporter produces an MDTO-compliant package and leaves the SIP-wrapping to a thin adapter so the same export can target multiple e-Depot variants.

## Cross-app integration

- **docudesk base**: every metadata object is attached 1:1 to a docudesk document; document uploads trigger a metadata-stub creation, and validation status on the metadata gates whether a document can be considered "definitive" (in MDTO terms, a record).
- **openregister**: all metadata, Selectielijst entries, identifier register, and ZGW mappings live as OpenRegister objects so they share the platform's audit, ACL, retention, search, and computed-fields capabilities; the `vernietigingDatum` computation uses the `computed-fields` capability so it is consistent across consumers.
- **openconnector**: inbound ZGW DocumentenAPI listening (when a zaak-systeem POSTs a notificatie) and outbound e-Depot transfer both go through OpenConnector sources, so credentials, retry policy, and call logs are managed centrally.
- **opencatalogi**: the Selectielijst register can be syndicated via opencatalogi so a municipality can subscribe to the VNG selectielijst as a curated catalog entry rather than maintaining a local copy.
- **eidas-qes-signature** (see sibling brief): a signed document's signature artefact is captured as a `relatie` of type `hasSignature` on the metadata so signature provenance is part of the archival record.

## Target users

- **DIV-medewerkers / archief-medewerkers** at municipalities are the primary day-to-day users — they verify metadata, link to the Selectielijst, mark records for transfer or destruction, and trigger e-Depot exports. The validation-on-save rule SHALL surface field-level errors in plain Dutch so a non-technical archivist can fix them inline.
- **Zaaksysteem developers** integrating their systems with docudesk consume the ZGW inbound mapping — they POST EnkelvoudigInformatieObjecten and get a TMLO/MDTO-tagged document back, with a stable URI they can reference.
- **Nationaal Archief / municipal e-Depot operators** consume the MDTO export packages and validate them on ingest; the exporter's pre-validation against the official XSD reduces ingest-time failures.
- **Auditors and Algemene Rekenkamer / RHC reviewers** read the audit trail through OpenRegister's standard audit-log viewer to verify that retention has been applied per Selectielijst and that no identifier has been silently changed.
- **Platform administrators** configure the Selectielijst register, the identifier base URI, and the profile defaults (TMLO vs MDTO) for new documents; defaults are per-register so a single instance can run pure-MDTO and legacy-TMLO registers side by side.

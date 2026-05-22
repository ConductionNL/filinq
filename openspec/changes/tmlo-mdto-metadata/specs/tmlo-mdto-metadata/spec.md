---
status: proposed
---

# TMLO / MDTO Metadata Specification

## Purpose

Enables Dutch local government to store, validate, and export document metadata conforming to TMLO 1.2.1 (legacy standard in production) and MDTO 1.0 (successor rolling out 2025+), with bewaartermijn binding, globally unique identification per NEN-2082, ZGW DocumentenAPI inbound mapping, and archival-grade XML export packages for e-Depot transfer.

## Relation to Existing Specs

- **document-register**: Base document storage; metadata is attached 1:1 to documents
- **openregister**: Persistence layer for metadata, Selectielijst entries, identifier register, ZGW mappings
- **openconnector**: ZGW DocumentenAPI listening and e-Depot outbound transfer
- **opencatalogi**: Selectielijst syndication and versioning
- **eidas-qes-signature**: Signature artefacts captured as relaties on metadata

---

## Requirements

### REQ-MD-001: Required-element Enforcement Per Profile on Save

The system SHALL validate every `document_metadata` object against its declared profile's required-element list on save and SHALL refuse to persist objects that are missing required elements.

#### Scenario: TMLO profile validation failure
- GIVEN a `document_metadata` with `profile=tmlo-1.2.1` and missing `naam`
- WHEN the object is saved via OpenRegister API
- THEN the API SHALL respond 400 with a field-level error naming `naam` as required
- AND the error SHALL be in Dutch for non-technical archivist users
- AND the object SHALL NOT be persisted

#### Scenario: TMLO profile validation success
- GIVEN a `document_metadata` with `profile=tmlo-1.2.1` with all required elements populated (naam, taal, aggregatieniveau, event, actoren)
- WHEN the object is saved
- THEN it SHALL be persisted
- AND `profileVersion` SHALL be stamped as "1.2.1"

#### Scenario: MDTO Archiefvormer requirement
- GIVEN a `document_metadata` with `profile=mdto-1.0` that has no `archiefvormer`
- AND the document is flagged for Nationaal Archief transfer
- WHEN the object is saved
- THEN the API SHALL respond 400 with error message "archiefvormer is verplicht voor rijks-e-Depot overdracht"
- AND the object SHALL NOT be persisted

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| MD-001 | Validate required elements per declared profile on save | MUST | Planned |
| MD-002 | Return field-level errors in Dutch | MUST | Planned |
| MD-003 | Stamp profileVersion on every saved record | MUST | Planned |

---

### REQ-MD-002: Bewaartermijn Binding from Configurable Selectielijst

The system SHALL link every document to exactly one Selectielijst entry, SHALL compute `vernietigingDatum` from the entry's `bewaartermijnJaren` + trigger event, and SHALL allow the Selectielijst register to be configured per instance.

#### Scenario: Compute vernietigingsDatum with definitief trigger
- GIVEN a Selectielijst entry with:
  - `procestype: "Verlenen omgevingsvergunning"`
  - `resultaat: "Verleend"`
  - `bewaartermijnJaren: 10`
  - `bewaartermijnTrigger: "definitief"`
- AND a document is linked to that entry
- AND the document has `event[type=creation].tijdstip: "2024-03-15T14:32:00+01:00"`
- WHEN the link is saved via the selectielijst_koppeling API
- THEN `vernietigingsDatum` SHALL be computed as `2034-03-15`
- AND `vernietigingsStatus` SHALL be set to `actief`

#### Scenario: Defer vernietigingsDatum computation with eindeZaak trigger
- GIVEN a Selectielijst entry with `bewaartermijnTrigger: "eindeZaak"`
- AND a document linked to an open zaak
- WHEN the selectielijst_koppeling is created
- THEN `vernietigingsDatum` SHALL be null
- AND `vernietigingsStatus` SHALL be null
- AND when the linked zaak is closed (via zaak-systeem notification)
- THEN `vernietigingsDatum` SHALL be computed automatically using OpenRegister computed-fields

#### Scenario: Custom Selectielijst register
- GIVEN an instance configured with a custom Selectielijst register (not VNG default)
- WHEN a document is linked to a selectielijstId
- THEN the system SHALL validate the `selectielijstId` exists in the configured register
- AND the system SHALL NOT allow linking to entries from other registers

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| MD-010 | Link every document to exactly one Selectielijst entry | MUST | Planned |
| MD-011 | Compute vernietigingsDatum from bewaartermijnJaren + trigger event | MUST | Planned |
| MD-012 | Support configurable Selectielijst register per instance | SHOULD | Planned |
| MD-013 | Allow eindeZaak trigger to defer vernietigingsDatum until zaak closure | MUST | Planned |

---

### REQ-MD-003: Globally Unique Identifier Minted Per NEN-2082

The system SHALL mint exactly one globally unique URI per document per NEN-2082, SHALL persist it in `identification.uri` and the `identificatie_register`, and SHALL refuse to mint a second URI for the same document.

#### Scenario: Mint URI on first metadata save
- GIVEN a new document with no existing metadata
- WHEN metadata is first saved
- THEN a URI SHALL be minted using the format: `{instance-configured-base}/{UUIDv7}`
  - Example: `https://docs.gemeente-zeist.nl/doc/018f3b8a-0000-7000-8000-000000000001`
- AND the URI SHALL be recorded in both `identification.uri` and `identificatie_register`
- AND `identification.bron` SHALL be "docudesk-identifier-minter"
- AND the UUIDv7 SHALL be sortable by mint time (lexicographic order preserves creation order)

#### Scenario: Preserve URI on metadata updates
- GIVEN an existing document whose metadata already has a URI
- WHEN metadata is updated (name, description, classification changed)
- THEN the existing URI in `identification.uri` SHALL be preserved unchanged
- AND no new entry SHALL be created in `identificatie_register`

#### Scenario: Enforce uniqueness
- GIVEN two documents on the same instance
- WHEN both are persisted with metadata
- THEN their URIs SHALL be distinct
- AND the `identificatie_register.uri` field SHALL have a unique constraint enforced at database level

#### Scenario: Round-trip preservation
- GIVEN a document exported as TMLO/MDTO XML
- WHEN the same document is re-imported
- THEN `identification.uri` SHALL equal the original URI
- AND the `identificatie_register` entry SHALL NOT be duplicated

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| MD-020 | Mint one URI per document using instance-base + UUIDv7 | MUST | Planned |
| MD-021 | Record minted URI in both identification.uri and identificatie_register | MUST | Planned |
| MD-022 | Preserve URI on metadata updates | MUST | Planned |
| MD-023 | Enforce unique constraint on identificatie_register.uri | MUST | Planned |

---

### REQ-MD-004: ZGW DocumentenAPI Inbound Mapping

The system SHALL accept an inbound ZGW DocumentenAPI EnkelvoudigInformatieObject and SHALL produce a TMLO or MDTO `document_metadata` object by applying the documented crosswalk, SHALL record the mapping in `zgw_mapping`, and SHALL surface any unmapped fields for manual review.

#### Scenario: Map ZGW EIO to TMLO metadata
- GIVEN an inbound EnkelvoudigInformatieObject with:
  - `titel: "Aanvraagformulier kapvergunning"`
  - `identificatie: "ZAKEN2GO-2024-00123456"`
  - `creatiedatum: "2024-04-01"`
  - `vertrouwelijkheidaanduiding: "openbaar"`
  - `informatieobjecttype: "https://zaak-systeem.example.nl/.../informatieobjecttypen/abc-123"`
- WHEN the mapping runs in TMLO mode
- THEN the resulting `document_metadata` SHALL have:
  - `naam: "Aanvraagformulier kapvergunning"`
  - `identification.kenmerk: "ZAKEN2GO-2024-00123456"`
  - `event[type=creation].tijdstip: "2024-04-01T00:00:00+02:00"`
  - `vertrouwelijkheidsaanduiding: "openbaar"`
  - `classificatie.code: {mapped from informatieobjecttype}`

#### Scenario: Handle custom ZGW fields
- GIVEN an inbound EIO with a custom field `x-custom-metadata: "some-value"`
- WHEN the mapping runs
- THEN the custom field SHALL appear in `zgw_mapping.unmappedFields`
- AND it SHALL NOT cause the mapping to fail
- AND the archivist SHALL be able to see unmapped fields in the UI for manual handling

#### Scenario: Map ZGW EIO to MDTO metadata
- GIVEN the same inbound EIO
- WHEN the mapping runs in MDTO mode
- THEN all TMLO-mapped fields SHALL also be present
- AND additional MDTO fields such as `archiefvormer`, `betrokkene` SHALL be populated from ZGW metadata where available

#### Scenario: Mapping error handling
- GIVEN an inbound EIO with a malformed or unreachable informatieobjecttype URI
- WHEN the mapping runs
- THEN the mapping SHALL NOT fail
- AND the unresolved informatieobjecttype SHALL appear in `unmappedFields`
- AND the rest of the metadata SHALL be created with empty or null `classificatie`

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| MD-030 | Accept ZGW DocumentenAPI EnkelvoudigInformatieObject | MUST | Planned |
| MD-031 | Apply documented crosswalk to TMLO fields | MUST | Planned |
| MD-032 | Apply documented crosswalk to MDTO fields | MUST | Planned |
| MD-033 | Record mapping result in zgw_mapping | MUST | Planned |
| MD-034 | Surface unmapped ZGW fields for manual review | MUST | Planned |
| MD-035 | Mapping gracefully handles missing or malformed fields | MUST | Planned |

---

### REQ-MD-005: Common Ground / NL API Outbound Mapping

The system SHALL expose metadata through Common Ground / NL API conventions with field names per `https://schemas.nl/`, so a consuming app sees a familiar shape regardless of which profile (TMLO/MDTO) the underlying record uses.

#### Scenario: TMLO metadata via NL API
- GIVEN a TMLO 1.2.1 metadata record retrieved via OpenRegister API
- WHEN the response is formatted per NL API conventions
- THEN all field names SHALL use snake_case (not camelCase)
- AND timestamps SHALL be ISO 8601 with timezone
- AND nested objects (classificatie, dekking, event, etc.) SHALL be formatted per NL API envelope standards
- Example: `{ "data": { "naam": "...", "omschrijving": "...", "classificatie": {...} } }`

#### Scenario: MDTO metadata via NL API
- GIVEN an MDTO 1.0 metadata record
- WHEN the same endpoint returns MDTO data
- THEN field names for shared fields (naam, omschrijving, classificatie, etc.) SHALL be field-shape compatible
- AND a client consuming both TMLO and MDTO records from the same API SHALL not need conditional logic for shared fields

#### Scenario: JSON serialization of complex types
- GIVEN metadata with arrays (event, actoren, bestand, relatie)
- WHEN serialized via NL API
- THEN arrays SHALL be present as JSON arrays
- AND each element SHALL conform to its schema's snake_case requirements

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| MD-040 | Expose metadata via NL API conventions (snake_case) | MUST | Planned |
| MD-041 | Format timestamps as ISO 8601 with timezone | MUST | Planned |
| MD-042 | Ensure field-shape compatibility between TMLO and MDTO profiles | SHOULD | Planned |

---

### REQ-MD-006: TMLO 1.2.1 XML Export

The system SHALL produce an XML document per document conforming to TMLO 1.2.1 schema with all required elements populated and SHALL validate against the official TMLO XSD before returning.

#### Scenario: Export valid TMLO XML
- GIVEN a fully-populated `document_metadata` with `profile=tmlo-1.2.1`
- AND all required TMLO elements present (naam, taal, aggregatieniveau, identificatie.uri, event, actoren)
- WHEN the TMLO export endpoint is called
- THEN an XML body SHALL be returned that validates against the official TMLO 1.2.1 XSD with zero errors
- AND the response Content-Type SHALL be `application/xml`
- AND the XML declaration SHALL specify `<?xml version="1.0" encoding="UTF-8"?>`

#### Scenario: Export validation failure
- GIVEN a `document_metadata` that fails TMLO validation at export time
  - e.g., `naam` was emptied after initial save, or `actoren` array is empty
- WHEN the export endpoint is called
- THEN the API SHALL respond 422 Unprocessable Entity with the XSD validation errors named
- AND an XML body SHALL NOT be returned
- AND the error response SHALL list each missing or invalid element

#### Scenario: TMLO aggregation-level export
- GIVEN a request to export a dossier (aggregatieniveau=dossier) containing 3 documents
- WHEN the export is requested at dossier level
- THEN one TMLO XML SHALL be produced for the dossier metadata
- AND the dossier XML SHALL reference or contain the URIs of the three contained documents

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| MD-050 | Produce TMLO 1.2.1-conformant XML per document | MUST | Planned |
| MD-051 | Validate XML against TMLO 1.2.1 XSD before returning | MUST | Planned |
| MD-052 | Return 422 with detailed XSD errors on validation failure | MUST | Planned |
| MD-053 | Support aggregation-level export (archief/serie/dossier/record) | SHOULD | Planned |

---

### REQ-MD-007: MDTO 1.0 XML Export

The system SHALL produce an XML document per document or aggregation conforming to MDTO 1.0 schema with all required elements populated and SHALL validate against the official MDTO XSD before returning.

#### Scenario: Export valid MDTO XML
- GIVEN a fully-populated `document_metadata` with `profile=mdto-1.0`
- AND all required MDTO elements present (naam, taal, aggregatieniveau, identificatie.uri, event, betrokkene, archiefvormer)
- AND all Bestand elements have valid checksums (checksumAlgoritme in FDD-allowed set)
- WHEN the MDTO export endpoint is called
- THEN an XML body SHALL be returned that validates against the official MDTO 1.0 XSD with zero errors
- AND the response Content-Type SHALL be `application/xml`

#### Scenario: MDTO checksum validation
- GIVEN an MDTO Bestand whose `checksumAlgoritme` is not in the FDD-allowed set (e.g., "MD5" or proprietary value)
- WHEN the export is validated
- THEN the API SHALL respond 422 with error "Bestand checksumAlgoritme 'MD5' is not FDD-approved; use SHA-256 or SHA-512"
- AND the XML SHALL NOT be produced

#### Scenario: MDTO Nationaal Archief compliance
- GIVEN a document flagged for transfer to the rijks-e-Depot
- AND the metadata has `archiefvormer: "Gemeente Zeist"`
- WHEN the export is run
- THEN the exported MDTO XML SHALL include the archiefvormer element
- AND the XML SHALL conform to the Nationaal Archief's MDTO profile (stricter subset)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| MD-060 | Produce MDTO 1.0-conformant XML per document or aggregation | MUST | Planned |
| MD-061 | Validate XML against MDTO 1.0 XSD before returning | MUST | Planned |
| MD-062 | Enforce FDD-approved checksumAlgoritme in Bestand elements | MUST | Planned |
| MD-063 | Return 422 with detailed XSD errors on validation failure | MUST | Planned |
| MD-064 | Support Nationaal Archief transfer compliance profile | SHOULD | Planned |

---

### REQ-MD-008: Aggregation-Aware Export Packages for e-Depot

The system SHALL produce an export package (TMLO or MDTO) at archief/serie/dossier/record aggregation level that bundles the metadata XML(s), the document bitstreams, and a manifest listing every file with its checksum.

#### Scenario: Export MDTO package for a dossier
- GIVEN a dossier with three documents, each with one rendition
- WHEN the MDTO export package is requested for that dossier
- THEN the resulting zip file SHALL contain:
  - One MDTO XML for the dossier-level metadata
  - One MDTO XML per document (3 XMLs)
  - One MDTO XML per Bestand rendition (3 XMLs)
  - Three document bitstreams (PDFs or other formats)
  - One manifest.xml listing every file with SHA-256 checksum
- AND the zip structure SHALL follow the e-Depot SIP convention

#### Scenario: Package validation at export time
- GIVEN any package produced by the export
- WHEN unpacked and re-validated by an external XSD validator
- THEN every XML in the package SHALL validate against its declared schema (MDTO 1.0 or TMLO 1.2.1)
- AND the manifest checksums SHALL match the actual file checksums

#### Scenario: TMLO package structure
- GIVEN a request to export a serie as TMLO
- WHEN the package is created
- THEN the zip SHALL contain TMLO-conformant XMLs
- AND the structure SHALL be equivalent to MDTO but with TMLO schema and elements

#### Scenario: Large package handling
- GIVEN a dossier with 100 documents and 500 MB total size
- WHEN the export package is requested
- THEN the system SHALL stream the zip to the client without buffering the entire package in memory
- AND the response SHALL include Content-Disposition: attachment header

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| MD-070 | Produce export package at aggregation level (archief/serie/dossier/record) | MUST | Planned |
| MD-071 | Include metadata XML(s), bitstreams, and manifest in package | MUST | Planned |
| MD-072 | Compute and list SHA-256 checksums in manifest | MUST | Planned |
| MD-073 | Validate all XMLs against XSD before packaging | MUST | Planned |
| MD-074 | Stream large packages without buffering in memory | SHOULD | Planned |

---

### REQ-MD-009: Identifier and Bewaartermijn Round-Trip on Import

The system SHALL accept its own export packages as imports, preserve the original identifier (`identification.uri`), and preserve the original bewaartermijn binding rather than minting a new one or applying default retention.

#### Scenario: Re-import MDTO package
- GIVEN an MDTO package previously exported from this docudesk instance
- AND the package contains metadata with `identification.uri: "https://docs.gemeente-zeist.nl/doc/018f3b8a-0000-7000-8000-000000000001"`
- AND the package contains selectielijst_koppeling with `vernietigingsDatum: "2034-03-15"`
- WHEN the package is re-imported
- THEN the resulting `document_metadata.identification.uri` SHALL equal the original
- AND the `selectielijst_koppeling.vernietigingsDatum` SHALL equal the original
- AND no new URI shall be minted

#### Scenario: URI collision on import
- GIVEN an MDTO package whose URI collides with an existing document on the importing instance
- WHEN the import is attempted
- THEN the API SHALL respond 409 Conflict with details: `"URI 'https://docs.gemeente-zeist.nl/doc/018f3b8a-0000-7000-8000-000000000001' already exists in document ABC-123"`
- AND the existing record SHALL NOT be overwritten
- AND the user SHALL be prompted to either re-generate the imported document's URI or skip the import

#### Scenario: Cross-instance import with URI preservation
- GIVEN an export from gemeente-A transferred to gemeente-B (different instance)
- WHEN the export is imported to gemeente-B
- THEN the URI from gemeente-A SHALL be preserved in `identification.uri`
- AND it SHALL be valid in gemeente-B's context (e.g., linked as reference, not as a minted identifier)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| MD-080 | Accept own export packages as imports | MUST | Planned |
| MD-081 | Preserve original identification.uri on re-import | MUST | Planned |
| MD-082 | Preserve original bewaartermijn binding on re-import | MUST | Planned |
| MD-083 | Enforce URI uniqueness per instance (409 on collision) | MUST | Planned |

---

### REQ-MD-010: Profile-Version Pinning and Migration Record

The system SHALL stamp the schema version (TMLO 1.2.1 or MDTO 1.0) on every metadata row at save, SHALL allow records to be migrated from TMLO to MDTO via an explicit migration step, and SHALL record the migration as an event on the document.

#### Scenario: TMLO-to-MDTO migration
- GIVEN a TMLO 1.2.1 record with all required fields
- WHEN the migration-to-MDTO endpoint is called (e.g., PATCH /document-metadata/{id}/migrate-to-mdto)
- THEN a new MDTO 1.0 `document_metadata` record SHALL be created
- AND the original TMLO record SHALL be marked as migrated (status field or archived per config)
- AND an `event` of type `migration` SHALL be appended to the document with:
  - `type: "migration"`
  - `tijdstip: {current timestamp}`
  - `actor: {user who triggered it}`
  - `instrument: "TMLO 1.2.1 → MDTO 1.0"`
- AND `identification.uri` SHALL be preserved from TMLO to MDTO
- AND `selectielijst_koppeling` SHALL be transferred with all fields intact

#### Scenario: Profile version stamping
- GIVEN any saved `document_metadata` record
- WHEN it is retrieved
- THEN `profileVersion` SHALL be present
- AND it SHALL match the schema version actually applied at save time (e.g., "1.2.1" for TMLO, "1.0" for MDTO)
- AND it SHALL be immutable (cannot be changed after save except via explicit migration)

#### Scenario: Audit trail of migration
- GIVEN a document that has migrated from TMLO to MDTO
- WHEN the OpenRegister audit log is queried
- THEN both the original TMLO save and the MDTO creation SHALL appear with timestamps
- AND the migration event SHALL be visible in the document's event array

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| MD-090 | Stamp profileVersion on every save | MUST | Planned |
| MD-091 | Implement explicit TMLO → MDTO migration endpoint | MUST | Planned |
| MD-092 | Preserve identification.uri and bewaartermijn on migration | MUST | Planned |
| MD-093 | Record migration as event on document | MUST | Planned |
| MD-094 | Maintain audit trail of profile changes | MUST | Planned |

---

## API Endpoints (Minimal Set)

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/docudesk/documents/{id}/metadata` | GET | Retrieve metadata for a document |
| `/api/docudesk/documents/{id}/metadata` | POST | Create metadata for a document |
| `/api/docudesk/documents/{id}/metadata` | PATCH | Update metadata for a document |
| `/api/docudesk/documents/{id}/metadata/export/tmlo` | GET | Export as TMLO 1.2.1 XML |
| `/api/docudesk/documents/{id}/metadata/export/mdto` | GET | Export as MDTO 1.0 XML |
| `/api/docudesk/documents/{id}/metadata/export/package/tmlo` | GET | Export aggregation-level TMLO package |
| `/api/docudesk/documents/{id}/metadata/export/package/mdto` | GET | Export aggregation-level MDTO package |
| `/api/docudesk/documents/{id}/metadata/migrate-to-mdto` | PATCH | Migrate TMLO record to MDTO |
| `/api/docudesk/documents/{id}/metadata/selectielijst-koppeling` | POST | Link to Selectielijst entry |
| `/api/docudesk/metadata/import/zgw-eio` | POST | Ingest ZGW EnkelvoudigInformatieObject and create metadata |

## Standards & Normative References

- **TMLO 1.2.1**: Official schema and XSD from KING/VNG (gemmaonline.nl)
- **MDTO 1.0**: Official schema and XSD from Nationaal Archief (nationaalarchief.nl)
- **NEN-2082**: Dutch national standard for information and records management (globally unique identifiers)
- **Archiefwet 1995 / 2024**: Legal framework for records retention and transfer
- **VNG Selectielijst gemeenten 2020**: Default retention policy register (overrideable per instance)
- **ZGW DocumentenAPI 1.5+**: Inbound document metadata source
- **NL API Design Rules**: Common Ground / NL API conventions (schemas.nl)
- **FDD (File Format Database)**: Approved file format codes for Bestand checksum algorithms

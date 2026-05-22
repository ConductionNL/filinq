# Tasks: TMLO / MDTO Metadata

## Phase 1: Data Model & Persistence

### Task 1.1: Define OpenRegister Schema for document_metadata
- [ ] Create OpenRegister schema definition for `docudesk_metadata` register
- [ ] Define base fields (id, documentId, profile, profileVersion, identification, naam, omschrijving, taal, aggregatieniveau, classificatie, dekking, event, actoren, bewaartermijn, openbaarheid, vertrouwelijkheidsaanduiding, relatie)
- [ ] Define TMLO 1.2.1 profile extension fields (dctermsType, mediumOpReceptie, verschijningsvorm, integriteit, dekkingInTijd, vorm)
- [ ] Define MDTO 1.0 profile extension fields (dekkingInTijdBegin, dekkingInTijdEind, bestand, betrokkene, archiefvormer)
- [ ] Add schema versioning (profileVersion constraint)
- [ ] Register with OpenRegister; test basic CRUD

### Task 1.2: Implement identificatie_register Entity
- [ ] Create OpenRegister schema for `identificatie_register`
- [ ] Add unique constraint on `uri` field
- [ ] Add foreign key to document_metadata
- [ ] Implement URI minting logic (instance-base + UUIDv7)
- [ ] Test uniqueness enforcement
- [ ] Verify UUIDv7 sort-by-time property

### Task 1.3: Implement selectielijst_koppeling Entity
- [ ] Create OpenRegister schema for selectielijst_koppeling
- [ ] Define fields: documentMetadataId, selectielijstId, procestype, resultaat, bewaartermijnJaren, bewaartermijnTrigger, vernietigingsDatum, vernietigingsStatus
- [ ] Add foreign key to document_metadata
- [ ] Implement computed-field for vernietigingsDatum calculation based on trigger
- [ ] Support trigger types: definitief, eindeZaak, eindeRelatie, vernietigingDatum
- [ ] Test with multiple Selectielijst registers

### Task 1.4: Implement zgw_mapping Entity
- [ ] Create OpenRegister schema for zgw_mapping
- [ ] Define fields: id, documentMetadataId, zgwInformatieobjecttype, zgwIdentificatie, mappingApplied, unmappedFields
- [ ] Add foreign key to document_metadata
- [ ] Store mapping result as JSON object
- [ ] Test with sample ZGW EnkelvoudigInformatieObject payloads

---

## Phase 2: Validation & Enforcement

### Task 2.1: Implement Profile Validation Service
- [ ] Create ValidationService that validates document_metadata against declared profile
- [ ] Define required-element lists for TMLO 1.2.1
- [ ] Define required-element lists for MDTO 1.0
- [ ] Implement field-level error collection
- [ ] Generate Dutch error messages for each missing/invalid field
- [ ] Integrate validation into OpenRegister create/update hooks
- [ ] Test validation with minimal and complete documents
- [ ] Test error message clarity for non-technical users

### Task 2.2: Implement Profile-Version Stamping
- [ ] Add profileVersion field to document_metadata schema
- [ ] Implement versioning logic at save time (read from profile enum)
- [ ] Ensure immutability after save (except during migration)
- [ ] Log version changes in audit trail
- [ ] Test version is correctly stamped for both TMLO and MDTO

---

## Phase 3: Identification & Selectielijst

### Task 3.1: Implement URI Minting Service
- [ ] Create IdentifierMintingService
- [ ] Implement instance-base URI configuration (environment or admin settings)
- [ ] Implement UUIDv7 generation (use php-ulid or similar library)
- [ ] Auto-mint URI on first document_metadata save
- [ ] Preserve existing URI on subsequent saves
- [ ] Test minting is idempotent
- [ ] Test URI uniqueness across concurrent saves
- [ ] Verify identifiers are sortable by mint time

### Task 3.2: Implement Selectielijst Linking
- [ ] Create SelectielijstService
- [ ] Implement validation of selectielijstId against configured register
- [ ] Implement vernietigingsDatum computation for definitief trigger:
  - Formula: creation_date + bewaartermijnJaren
- [ ] Implement deferred computation for eindeZaak trigger (via computed-field)
- [ ] Support custom Selectielijst registers (configurable per instance)
- [ ] Test with VNG default and custom registers
- [ ] Test trigger-based computation logic
- [ ] Implement admin interface for Selectielijst configuration

---

## Phase 4: ZGW DocumentenAPI Integration

### Task 4.1: Implement ZGW Inbound Mapping Service
- [ ] Create ZGWMappingService
- [ ] Implement documented crosswalk from ZGW EIO → TMLO fields
  - titel → naam
  - identificatie → identification.kenmerk
  - creatiedatum → event[type=creation].tijdstip
  - vertrouwelijkheidaanduiding → vertrouwelijkheidsaanduiding
  - informatieobjecttype → classificatie (via cache or lookup)
- [ ] Implement crosswalk from ZGW EIO → MDTO fields
- [ ] Collect unmapped fields in zgw_mapping.unmappedFields array
- [ ] Gracefully handle missing informatieobjecttype (don't fail, leave classificatie empty)
- [ ] Test with sample ZGW payloads
- [ ] Test error handling for malformed inputs

### Task 4.2: Implement ZGW Inbound Listening (OpenConnector Integration)
- [ ] Create OpenConnector listener for ZGW DocumentenAPI notifications
- [ ] Trigger on notificatie with action=created (inbound EIO)
- [ ] Call ZGWMappingService to create document_metadata
- [ ] Persist ZGW mapping record
- [ ] Test with live ZGW notifications (or mocked notifications)
- [ ] Test error handling and retry logic

### Task 4.3: Implement ZGW Endpoint
- [ ] Create POST `/api/docudesk/metadata/import/zgw-eio` endpoint
- [ ] Accept ZGW EnkelvoudigInformatieObject JSON
- [ ] Call ZGWMappingService
- [ ] Return created document_metadata with URI
- [ ] Test endpoint with sample payloads

---

## Phase 5: XML Export & Validation

### Task 5.1: Implement TMLO 1.2.1 XML Export
- [ ] Download TMLO 1.2.1 official XSD from gemmaonline.nl
- [ ] Create TMLoExportService
- [ ] Implement serialization of document_metadata → TMLO XML
- [ ] Implement XSD validation before returning (use PHP XSD validator)
- [ ] Return 422 with detailed validation errors on failure
- [ ] Test with valid TMLO data
- [ ] Test with invalid data (missing required elements)
- [ ] Ensure XML declaration and encoding are correct

### Task 5.2: Implement MDTO 1.0 XML Export
- [ ] Download MDTO 1.0 official XSD from nationaalarchief.nl
- [ ] Create MDTOExportService
- [ ] Implement serialization of document_metadata → MDTO XML
- [ ] Validate bestandsformaat codes against FDD reference
- [ ] Validate checksumAlgoritme against FDD-approved list (SHA-256, SHA-512, etc.)
- [ ] Implement XSD validation before returning
- [ ] Return 422 with detailed validation errors on failure
- [ ] Test with valid MDTO data
- [ ] Test with invalid checksumAlgoritme (reject MD5, etc.)
- [ ] Test Nationaal Archief compliance profile (archiefvormer required)

### Task 5.3: Create API Endpoints for XML Export
- [ ] POST `/api/docudesk/documents/{id}/metadata/export/tmlo` → returns XML
- [ ] POST `/api/docudesk/documents/{id}/metadata/export/mdto` → returns XML
- [ ] Test endpoints with valid metadata
- [ ] Test error responses (422 for validation failures)
- [ ] Monitor response times for large documents

---

## Phase 6: Export Packages & e-Depot

### Task 6.1: Implement Package Bundling Service
- [ ] Create ExportPackageService
- [ ] Implement aggregation-level export (archief/serie/dossier/record)
- [ ] Bundle metadata XML(s), document bitstreams, and manifest
- [ ] Implement SHA-256 checksum computation for all files
- [ ] Create manifest.xml with file list and checksums
- [ ] Generate zip file in e-Depot SIP convention structure
- [ ] Test with multiple documents at each aggregation level
- [ ] Test with large packages (500+ MB) using streaming

### Task 6.2: Implement Package Validation
- [ ] Validate all XMLs in package against XSD before zipping
- [ ] Verify manifest checksums match actual files
- [ ] Test validation with valid and invalid packages

### Task 6.3: Create Package Export Endpoints
- [ ] POST `/api/docudesk/documents/{id}/metadata/export/package/tmlo` → returns zip
- [ ] POST `/api/docudesk/documents/{id}/metadata/export/package/mdto` → returns zip
- [ ] Support aggregation-level selection (via query param)
- [ ] Implement streaming response (Content-Disposition: attachment)
- [ ] Test endpoints with sample data

---

## Phase 7: Import & Round-Trip

### Task 7.1: Implement Package Import Service
- [ ] Create ImportPackageService
- [ ] Parse incoming zip file (validate structure)
- [ ] Extract metadata XMLs and validate against XSD
- [ ] Deserialize XML back to document_metadata objects
- [ ] Preserve identification.uri and selectielijst_koppeling
- [ ] Detect URI collisions (409 response)
- [ ] Create documents and metadata in OpenRegister
- [ ] Test round-trip (export → import → export, no loss)
- [ ] Test URI collision handling

### Task 7.2: Create Package Import Endpoint
- [ ] POST `/api/docudesk/metadata/import/package` → accepts zip
- [ ] Call ImportPackageService
- [ ] Return 409 on URI collision with details
- [ ] Return 400 on invalid package structure
- [ ] Return 200 with imported document URIs on success
- [ ] Test endpoint with valid packages

---

## Phase 8: Profile Migration

### Task 8.1: Implement TMLO → MDTO Migration Service
- [ ] Create MigrationService
- [ ] Implement TMLO → MDTO field mapping
- [ ] Preserve identification.uri
- [ ] Preserve selectielijst_koppeling (or transfer bewaartermijn)
- [ ] Create new MDTO metadata record
- [ ] Mark original TMLO as migrated (or archive per config)
- [ ] Append migration event to document
- [ ] Test with complete TMLO records
- [ ] Verify audit trail captures migration

### Task 8.2: Create Migration Endpoint
- [ ] PATCH `/api/docudesk/documents/{id}/metadata/migrate-to-mdto`
- [ ] Call MigrationService
- [ ] Return 200 with new MDTO metadata URI
- [ ] Return 400 if record is already MDTO
- [ ] Test endpoint

---

## Phase 9: API & Configuration

### Task 9.1: Implement Core Metadata CRUD Endpoints
- [ ] GET `/api/docudesk/documents/{id}/metadata` → retrieve metadata
- [ ] POST `/api/docudesk/documents/{id}/metadata` → create metadata
- [ ] PATCH `/api/docudesk/documents/{id}/metadata` → update metadata
- [ ] DELETE `/api/docudesk/documents/{id}/metadata` → delete metadata (with safeguards)
- [ ] All endpoints validate input per profile
- [ ] All endpoints return Dutch error messages
- [ ] Test with TMLO and MDTO profiles

### Task 9.2: Implement Selectielijst Linking Endpoint
- [ ] POST `/api/docudesk/documents/{id}/metadata/selectielijst-koppeling` → link to entry
- [ ] Call SelectielijstService
- [ ] Return 400 if selectielijstId not found in configured register
- [ ] Return 200 with computed vernietigingsDatum
- [ ] Test with different triggers (definitief, eindeZaak)

### Task 9.3: Implement Admin Configuration
- [ ] Add admin setting for identifier base URI (e.g., "https://docs.gemeente-zeist.nl/doc/")
- [ ] Add admin setting for Selectielijst register (default: VNG 2020, allow custom)
- [ ] Add admin setting for default profile (TMLO vs MDTO)
- [ ] Add admin setting for profile migration behavior (replace or parallel)
- [ ] Create UI for archivists to verify and edit metadata
- [ ] Create UI for DIV-medewerkers to link to Selectielijst and mark for export

---

## Phase 10: Integration Testing & Documentation

### Task 10.1: End-to-End Testing
- [ ] Test document creation → metadata creation (auto-mint URI)
- [ ] Test metadata validation on save
- [ ] Test Selectielijst linking with vernietigingsDatum computation
- [ ] Test ZGW inbound (from zaak-systeem)
- [ ] Test TMLO XML export and validation
- [ ] Test MDTO XML export and validation
- [ ] Test package export and import (round-trip)
- [ ] Test TMLO → MDTO migration
- [ ] Test with concurrent saves and updates

### Task 10.2: Integration with Other Modules
- [ ] Test with docudesk document lifecycle
- [ ] Test with OpenRegister audit trail
- [ ] Test with OpenConnector ZGW notifications
- [ ] Test with eidas-qes-signature (signature as relatie)
- [ ] Test with opencatalogi Selectielijst syndication

### Task 10.3: Documentation
- [ ] Write admin guide: configuring base URI, Selectielijst, profile defaults
- [ ] Write archiver guide: verifying metadata, linking to Selectielijst, triggering exports
- [ ] Write developer guide: ZGW inbound mapping, API usage, extending profiles
- [ ] Create TMLO/MDTO crosswalk documentation
- [ ] Document error codes and troubleshooting
- [ ] Document performance characteristics (large package handling)

### Task 10.4: Acceptance Testing with Archivists
- [ ] Run with 2-3 DIV-medewerkers in test environment
- [ ] Verify Dutch error messages are clear
- [ ] Verify UI for metadata verification is usable
- [ ] Gather feedback on metadata required-field UX
- [ ] Test e-Depot export packages with test e-Depot ingest

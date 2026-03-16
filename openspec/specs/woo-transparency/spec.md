# woo-transparency Specification

## Purpose
WOO (Wet open overheid) / FOIA compliance features in Docudesk: request processing queue, document redaction workflow, publication to a reading room, and redaction audit trail. Extends the existing anonymization pipeline with WOO-specific workflow and publication capabilities.

## Context
The existing `anonymization` spec covers the technical pipeline: upload, entity detection, and replacement. This spec adds the WOO-specific workflow layer on top: managing a queue of documents that need assessment and redaction, tracking which redaction grounds (weigeringsgronden) apply, and publishing the final set of redacted documents to a public reading room. This is the Docudesk side of the WOO workflow; the case management side lives in Procest's `woo-case-type` spec.

**Relation to existing specs:**
- `anonymization` spec: provides the entity detection and redaction engine (ANON-001 through ANON-056). This spec uses that pipeline but adds WOO context.
- Procest `woo-case-type` spec: manages the WOO case lifecycle. This spec handles the document processing that Procest delegates to Docudesk.

## ADDED Requirements

### Requirement: WOO document queue
The system MUST provide a document processing queue for WOO requests.

#### Scenario: Receive documents from Procest
- GIVEN a WOO case in Procest with 20 collected documents
- WHEN Procest sends the documents to Docudesk for WOO processing
- THEN a WOO processing batch MUST be created in Docudesk
- AND all 20 documents MUST appear in the processing queue
- AND each document MUST have initial status "Te beoordelen"

#### Scenario: Document assessment statuses
- GIVEN a document in the WOO queue
- THEN the following assessment statuses MUST be supported:
  - **Te beoordelen** -- not yet assessed
  - **Openbaar** -- fully disclosable, no redaction needed
  - **Deels openbaar** -- needs redaction before disclosure
  - **Niet openbaar** -- withheld entirely
- AND transitioning to "Niet openbaar" MUST require selecting weigeringsgrond(en)

#### Scenario: Bulk assessment
- GIVEN 20 documents in the queue
- WHEN the user selects 5 documents and sets status "Openbaar"
- THEN all 5 MUST be updated in one action
- AND the remaining 15 MUST retain their current status

### Requirement: Weigeringsgronden (refusal grounds)
The system MUST support tagging documents with legal grounds for withholding.

#### Scenario: Tag document with refusal ground
- GIVEN a document assessed as "Niet openbaar"
- WHEN the user selects weigeringsgronden
- THEN they MUST choose from WOO Article 5.1 and 5.2 grounds:
  - 5.1.1: Eenheid van de Kroon
  - 5.1.2: Veiligheid van de Staat
  - 5.1.2.e: Eerbiediging persoonlijke levenssfeer
  - 5.1.2.f: Belang van opsporing en vervolging
  - 5.2.e: Persoonlijke beleidsopvattingen
  - (complete list per WOO)
- AND multiple grounds MAY be selected per document
- AND each ground MUST be stored with the document assessment

#### Scenario: Partial redaction with grounds
- GIVEN a document assessed as "Deels openbaar"
- WHEN the user marks specific entities for redaction
- THEN each redacted entity or section MUST be linkable to a weigeringsgrond
- AND the redaction mapping (entity -> ground) MUST be stored for the besluit

### Requirement: Redaction with WOO context
The anonymization pipeline MUST be extended with WOO-specific redaction features.

#### Scenario: Selective entity redaction
- GIVEN a document with 15 detected entities
- WHEN the user reviews the entities in the WOO redaction view
- THEN they MUST be able to select which entities to redact (not all-or-nothing)
- AND they MUST be able to add manual redaction regions (mark areas not detected by AI)
- AND each redaction MUST be linkable to a weigeringsgrond

#### Scenario: Redaction preview
- GIVEN a document with selected redactions
- WHEN the user clicks "Voorbeeld"
- THEN a preview MUST show the document with redacted areas blacked out
- AND the user MUST be able to approve or adjust before finalizing

#### Scenario: Redaction produces clean document
- GIVEN a finalized redaction
- WHEN the anonymized document is generated
- THEN redacted text MUST be irrecoverably removed (not just visually hidden)
- AND redacted areas MUST show black bars (standard WOO convention)
- AND the original document MUST be preserved unchanged

### Requirement: Inventarislijst generation
The system MUST generate a document inventory (inventarislijst) for the WOO decision.

#### Scenario: Generate inventarislijst
- GIVEN a WOO batch with 20 assessed documents
- WHEN the user requests the inventarislijst
- THEN a document MUST be generated listing all documents with:
  - Volgnummer (sequential number)
  - Document omschrijving (title/description)
  - Datum document (document date)
  - Beoordeling (openbaar/deels openbaar/niet openbaar)
  - Weigeringsgrond(en) (if applicable)
- AND the inventarislijst MUST be exportable as PDF and CSV

### Requirement: Reading room publication
The system MUST support publishing WOO documents to a public reading room.

#### Scenario: Publish WOO package
- GIVEN a completed WOO batch with:
  - 10 documents marked "Openbaar"
  - 5 documents redacted (deels openbaar)
  - 5 documents withheld (niet openbaar)
  - Generated inventarislijst
  - Besluit document (from Procest)
- WHEN the user triggers publication
- THEN a public reading room page MUST be created containing:
  - The besluit document
  - The inventarislijst
  - The 10 openbare documents
  - The 5 redacted (anonymized) versions of deels openbare documents
- AND the niet openbare documents MUST NOT be included
- AND the reading room MUST be accessible without authentication

#### Scenario: Reading room URL
- GIVEN a published WOO package
- THEN the reading room MUST have a permanent public URL
- AND the URL MUST be shareable with the verzoeker and the public

## Non-Requirements
- This spec does NOT cover the WOO case lifecycle (managed by Procest woo-case-type spec)
- This spec does NOT cover WOO decision registration (managed by Procest besluiten-management spec)
- This spec does NOT cover proactive WOO publication (actieve openbaarmaking) -- future spec

## Dependencies
- Docudesk anonymization pipeline (entity detection, redaction engine)
- Procest woo-case-type spec (case management, document collection)
- OpenRegister for batch and assessment data storage
- Nextcloud public pages/shares for reading room

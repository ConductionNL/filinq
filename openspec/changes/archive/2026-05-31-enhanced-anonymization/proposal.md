---
status: proposed
source: market-intelligence
clusters: [89]
total_tenders: 64
total_requirements: 116
---

# Enhanced Anonymization

## Summary

Extend DocuDesk's existing anonymization pipeline with batch processing, configurable anonymization rules per document type, before/after preview, and a comprehensive audit trail. The current implementation handles single-document anonymization with entity detection; this change scales it to organizational workflows required by the Woo (Wet open overheid) and AVG/GDPR compliance processes.

## Demand Evidence

### Cluster 89: Document anonymisation
- **64 tenders**, **116 requirements** (primarily Dutch government via TenderNed)
- Country distribution: TenderNed 102 reqs, Belgium 1 req
- Driven by Woo (Wet open overheid) compliance and AVG/GDPR requirements

### Sample Requirements from Tenders
- **RUD Utrecht**: "Het exporteren van de (geanonimiseerde) documenten."
- **Gemeente Overbetuwe**: "De gemeente Overbetuwe wil inzichtelijk krijgen hoe Opdrachtnemer omgaat met de Wet Open overheid (Woo)."
- **Gemeente Berg en Dal**: "De Oplossing beschikt over anonimiseerfunctionaliteit of kan koppelen met de gangbare anonimiseertools om documenten te anonimiseren."
- **Gemeente Stein**: "De oplossing is in staat om in de toekomst middels een plug-in documenten te laten anonimiseren door een derde partij. Het geanonimiseerde document zal..."
- **Gemeente Stein**: "In de oplossing kunnen zaken, onafhankelijk van het kanaal en indien zo geconfigureerd, anoniem worden ingediend."

## What Docudesk Already Does

- **Anonymization Pipeline** (implemented): Complete single-document anonymization:
  - File upload to user-scoped DocuDesk folder
  - Text extraction and entity detection (PERSON, ORGANIZATION, EMAIL, PHONE, etc.) using OpenRegister NER
  - Anonymization by replacing detected entities with placeholders
  - 100% local processing (no external cloud), GDPR/AVG compliant by design
  - Services: `AnonymizationService`, `EntityDetectionService`, `FileUploadService`, `DocumentTextExtractor`, `AnonymizationResultParser`
- **Consent Management** (implemented): Consent tracking for anonymization workflows
- **Language Classifier** (implemented): Detect document language for NER model selection

### What Is Missing
- No batch anonymization (one document at a time only)
- No configurable rules per document type (same rules for everything)
- No before/after preview comparison
- No detailed audit trail of what was anonymized (which entities, where)
- No Woo-specific workflow support (publication-ready anonymization)

## Scope

### In Scope
1. **Batch anonymization** -- submit multiple documents (or an entire folder) for anonymization in one operation, with background job processing and progress tracking
2. **Configurable anonymization rules** -- define rule sets per document type (e.g., "Woo publication" rules vs. "internal AVG" rules), with entity type inclusion/exclusion, confidence thresholds, and custom replacement patterns
3. **Before/after preview** -- side-by-side view showing the original document with highlighted entities alongside the anonymized version, before committing the anonymization
4. **Entity review workflow** -- allow users to review detected entities before anonymization: confirm, reject, or add missed entities manually
5. **Audit trail** -- log every anonymization action: which document, which entities were detected, which were anonymized, who approved, when, and the rule set used
6. **Export of anonymized documents** -- export anonymized documents in original format (PDF) or as a Woo publication package

### Out of Scope
- Video or audio anonymization
- Real-time anonymization during document creation
- Third-party anonymization service integration (current local-only approach is a feature)

## Acceptance Criteria

1. GIVEN a folder with 25 documents, WHEN batch anonymization is triggered, THEN all 25 documents are processed with a progress indicator, AND results are available per document
2. GIVEN a "Woo publication" rule set that anonymizes PERSON and PHONE but keeps ORGANIZATION, WHEN applied to a document containing all three entity types, THEN only persons and phone numbers are replaced
3. GIVEN a document with 12 detected entities, WHEN the before/after preview is opened, THEN the original shows 12 highlighted entities, AND the anonymized version shows 12 replacements in the same positions
4. GIVEN a detected entity list, WHEN a user marks 2 entities as "keep" (false positive) and adds 1 missed entity, THEN the final anonymization respects these manual overrides
5. GIVEN an anonymized document, WHEN the audit trail is viewed, THEN it shows: original filename, entity count by type, rule set used, reviewer identity, timestamp, and any manual overrides
6. GIVEN a confidence threshold of 0.8 in the rule set, WHEN an entity is detected with confidence 0.6, THEN it is flagged for manual review rather than auto-anonymized

## Risks and Dependencies

- Batch processing of large document sets requires background job infrastructure (Nextcloud cron/OC background jobs)
- Before/after preview requires rendering both versions in the browser -- PDF rendering may be resource-intensive
- Entity review workflow adds UI complexity; needs careful UX design
- Audit trail storage may grow large for organizations processing thousands of Woo documents

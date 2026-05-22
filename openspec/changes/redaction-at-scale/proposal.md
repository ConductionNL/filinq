## Why

Dutch government organisations face legal requirements under Woo (wet open overheid) and AVG to mask persoonsgegevens, contact details, pricing, and trade secrets before publication and sharing. Today most gemeenten redact documents manually in Adobe Acrobat or Word, drawing black rectangles—which is slow, inconsistent, and leaks data when underlying text remains selectable. Woo's shift from reactive to proactive openbaarmaking has multiplied the volume: organisations process thousands of pages per month. 

Conduction klanten cannot afford manual redaction. They need a high-throughput pipeline that combines pattern-based auto-mask (BSN, IBAN, telefoonnummer, postcode, email, kenteken), NLP entity recognition for names and organisations, manual reviewer override, side-by-side preview, and bulk processing. Output must be true-redacted—the underlying text removed from the PDF, not merely covered—with redaction history stored separately so internal users with the unredact role can reach originals.

## What Changes

- Introduce a **RedactionJob** entity that queues, executes, and tracks document redaction with status (queued, running, completed, failed, partially_completed) and mode (auto, manual, hybrid).
- Introduce a **RedactionProfile** entity defining pattern-based and entity-type rules, allowLists, denyLists, language, and sharing model.
- Introduce a **RedactionPattern** entity with regex, category (bsn, iban, phone, email, postcode, kenteken, custom), optional validators (11-proef for BSN, MOD-97 for IBAN), and replacement rules.
- Introduce a **RedactionAnnotation** entity capturing auto-detected and reviewer-added redaction rectangles per job and page, with sourceText encrypted at rest, category, origin source, and approval workflow (pending → applied/rejected).
- Introduce a **RedactedDocument** entity binding a redacted output to its source and job, with cryptographic verification (contentHash), access-control roles, and retention policy.
- Introduce a **RedactionAudit** entity logging every action (auto_detected, reviewer_added, reviewer_removed, applied, exported, original_accessed) for compliance and debugging.
- High-throughput pattern matching pipeline (≥50 pages/min on standard worker tier) using regex + caching.
- Dutch-language NLP entity recognition (PERSON, ORG, LOC) at configurable confidence.
- Reviewer UI with side-by-side original ↔ redacted preview, toggle-on-off annotation control, and per-document redaction history.
- Bulk mode that queues per-document jobs, surfaces aggregate progress, and produces summary reports.
- Role-based access (unredact role) for internal retrieval of originals, with audit trail and owner notification.
- Profile governance: ownership, read-only sharing, versioning.

## Capabilities

### New Capabilities

- `redaction-pipeline`: Pattern-based auto-detection of PII (BSN, IBAN, phone, email, postcode, kenteken) with 11-proef and MOD-97 validators; regex caching for throughput.
- `redaction-nlp-entity-recognition`: Dutch-language NLP model for PERSON, ORG, LOC entity detection at configurable confidence; allowList suppression.
- `redaction-profile-management`: Create, share (read-only), version, and reuse redaction profiles; per-profile language and entity-type configuration.
- `redaction-annotation-workflow`: Reviewer UI for adding, removing, and categorising annotations; annotation history per job and page.
- `redaction-preview`: Side-by-side original ↔ redacted output preview with annotation toggle control.
- `redaction-export-verification`: True PDF text removal (stream mutation, not visual cover), flattening, and re-extraction validation; content hash verification.
- `redaction-bulk-mode`: Queue N documents in one job; track progress per-document and per-category; aggregate summary reporting.
- `redaction-unredact-access`: Role-based retrieval of original documents with audit trail and owner notification; retention policy enforcement.
- `redaction-audit-logging`: Complete audit trail of every action (detection, review, export, original access) for AVG/Woo compliance.

### Modified Capabilities

None. Redaction is a new feature; no existing DocuDesk capability is affected.

## Impact

**Affected code (DocuDesk):**
- New database tables / register schemas: `redaction_job`, `redaction_profile`, `redaction_pattern`, `redaction_annotation`, `redacted_document`, `redaction_audit` (or equivalent OpenRegister objects if using the register model).
- New PHP services: `RedactionService` (job orchestration), `PatternMatchingService` (regex engine), `NLPEntityRecognitionService` (NLP integration), `RedactionPreviewService`, `RedactionExportService`.
- New routes: `POST /api/redactions/jobs`, `GET /api/redactions/jobs/{jobId}`, `PATCH /api/redactions/jobs/{jobId}/annotations`, `GET /api/redactions/jobs/{jobId}/preview`, `POST /api/redactions/jobs/{jobId}/export`.
- New Vue UI: redaction-job-list, redaction-preview-panel, annotation-editor, profile-manager, bulk-upload wizard.

**Affected downstream apps:**
- **opencatalogi**: Woo-besluit publication pipeline can trigger redaction jobs before release.
- **docudesk e-discovery-legal-hold**: Privileged-passage redaction before production export.
- **openconnector**: Optional NER service integration for external models.
- **openregister**: Profile + pattern storage if using register model.
- **mydash**: Redaction throughput dashboards (pages/week, categories, reviewer time).

**APIs / Dependencies:**
- PDF manipulation: iText or Ghostscript for true text removal and flattening.
- NLP: spaCy or HuggingFace Dutch-language NER model.
- Job queue: existing DocuDesk async job system or integration with a background queue (Resque, Gearman).
- Encryption: existing DocuDesk encryption context for sourceText at-rest.

**Data / Migrations:**
- Register-based: new register schema definitions, seed profile examples, seed pattern library.
- Job queue: schema for pending jobs, progress tracking, result storage.
- Audit trail: integrate with OpenRegister audit mapper or separate audit table.

**Architectural Alignment:**
- ADR-001 (Data Layer): RedactionJob, Profile, Pattern, Annotation, Document as register objects or dedicated tables per data-model choice.
- ADR-005 (Security): unredact role gating, audit trail integration, encryption of sourceText at rest.
- ADR-006 (Schema Standards): entity schemas follow OpenAPI 3.0.0 conventions.
- ADR-008 (Testing): unit tests for pattern validation (11-proef, MOD-97), integration tests for NLP, export verification.
- Woo Article 5.1, AVG Article 4(5), NIST SP 800-188, PDF 1.7 / ISO 32000 alignment.

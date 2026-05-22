## Why

DocuDesk's retention engine automatically deletes or archives content according to policy — the right behavior for routine records management. But when an organization faces litigation, regulatory investigation, a Woo-verzoek, an AVG access request, or internal investigation, every byte must be preserved untouched until the matter is resolved or the hold is released. Without a first-class hold mechanism, organizations either disable retention globally (creating storage and AVG-compliance problems) or risk spoliation claims and dwangsommen.

This spec introduces **Matter** and **Hold** entities to suspend retention, **Custodian** workflow with formal hold-notice acknowledgement, search and review surfaces where reviewers tag documents as responsive / privileged / not-responsive, a **ProductionSet** workflow for defensible disclosure, and an **AccessAudit** trail for compliance. The system follows the EDRM (Electronic Discovery Reference Model) phase model so legal teams familiar with Relativity, Everlaw, or Logikcull can map their existing process onto DocuDesk.

For the Dutch market the same engine serves Woo-verzoeken (verzoek → matter, deadline → target date, production-set → public-disclosure bundle) and gemeente AVG-inzageverzoeken (production-set → subject's personal-data export).

## What Changes

- Add **Matter** register to DocuDesk, with schemas for matter metadata, matter type (litigation, regulatory, woo, avg_inzage, internal_investigation), status (open, on_hold, in_review, producing, closed), and fields for lead reviewer and due date.
- Add **LegalHold** schema with scope definition (custodians, date range, registers, schemas, keywords), status (active, released), and release reason tracking.
- Add **HoldNotice** schema to track custodian delivery, acknowledgement, reminders, and escalation.
- Add **Custodian** schema to bind users to matters, track their data sources, and manage hold-notice delivery.
- Add **ReviewTag** schema for document-level tagging (responsive, not_responsive, privileged, hot, needs_redaction, confidential) with audit trail.
- Add **ProductionSet** schema to bundle finalized responsive documents, generate encrypted ZIPs with load files, and track export recipients.
- Add **AccessAudit** schema as append-only (no UPDATE/DELETE) to record every document interaction (viewed, downloaded, tagged, redacted, exported).
- Implement retention suspension hook: when the retention engine evaluates a document under active hold, skip deletion/archival and log the skip.
- Implement custodian hold-notice delivery via in-app notification and email, with configurable reminders and escalation to manager.
- Implement search and review surface for reviewers to query documents and apply tags (target: 3-second response for matters up to 100k documents).
- Implement privilege redaction validation: documents tagged "privileged" or "needs_redaction" MUST be redacted before inclusion in production sets; unredacted privileged documents are hard-blocked from export.
- Implement production-set export: generate encrypted ZIP with load file (bates numbers, paths, hashes, tags), passphrase protection, and delivery tracking.
- Register AccessAudit as append-only: OpenRegister forbids UPDATE/DELETE on the schema, all writes are CREATE-only.

## Capabilities

### New Capabilities

- `matter-register`: Defines the Matter register, schema, and seed data.
- `legal-hold-lifecycle`: Active/released holds with scope filters and suspension hooks into retention.
- `custodian-hold-notice`: Delivery, acknowledgement, reminders, escalation.
- `document-review-and-tagging`: Search + ReviewTag assignment + audit trail.
- `privilege-redaction-gate`: Blocks unredacted privileged documents from production export.
- `production-set-export`: Encrypted ZIP generation with load file, passphrase, delivery tracking.
- `access-audit-immutable`: Append-only AccessAudit for compliance and forensics.

### Modified Capabilities

- **docudesk retention engine**: Honors suspension hook before deletion/archival of documents under hold.
- **docudesk redaction-at-scale**: Integrates with privilege redaction validation in production-set export.

## Impact

**Affected code (DocuDesk):**
- `lib/Settings/docudesk_register.json` — add seven new schemas (Matter, LegalHold, HoldNotice, Custodian, ReviewTag, ProductionSet, AccessAudit) and registers for matter, hold, custodian, review, production, audit.
- `lib/Service/RetentionService.php` — add suspension hook before deletion/archival (check if document is under active hold; if yes, skip and log).
- `lib/Service/CustodianNoticeService.php` — new service to deliver hold notices, track acknowledgements, send reminders, escalate to manager.
- `lib/Service/ReviewService.php` — new service for search queries and tag application.
- `lib/Service/ProductionExportService.php` — new service to generate encrypted ZIPs with load files.
- `lib/Controller/MatterController.php`, `HoldController.php`, `ReviewController.php`, `ProductionController.php`, `AuditController.php` — API endpoints.
- Vue components for matter list, hold activation, custodian notice, document review + tagging, production-set export.

**Affected code (OpenRegister):** 
- AccessAudit schema MUST be marked append-only (no UPDATE/DELETE). OpenRegister's `SaveObject` evaluator currently blocks updates via archival status; a follow-up OpenRegister change (`append-only-schemas`) will add a schema-level flag `immutable: "append-only"` to enforce this at the API level without archival side-effects.

**Cross-app integrations:**
- **openregister**: documents under hold flagged in the object store; retention metadata tagged.
- **docudesk retention engine**: explicit suspension hook honored before deletion/archival.
- **docudesk redaction-at-scale**: privileged passages redacted before production export.
- **opencatalogi / Woo publicatie**: Woo production-sets can flow directly to the public catalogue.
- **openconnector**: optional sync to specialist e-discovery tools (Relativity, Everlaw) for very large matters.

**APIs / dependencies:**
- HTTP API: new endpoints surface via OpenRegister's generic `/api/objects/{register}` routes plus DocuDesk-specific controllers for review, notice delivery, and export.
- DI: `ObjectService`, `AuditTrailService`, `NotificationService`, `FileService` all reused from platform.
- Background jobs for custodian reminder dispatch and export-set generation.

**Data / migrations:**
- Running DocuDesk's `RegistersLoader` repair step applies the new schemas and seed objects.
- No existing data affected (matters, holds, custodians are new).
- Rollback: remove the registers and schemas by reverting `docudesk_register.json`.

**Architectural alignment:**
- ADR-001 (Data Layer): all domain data in OpenRegister; config in `IAppConfig` only.
- ADR-005 (Security): authorization checks on matter/hold operations (only legal_hold_admin can create/release holds); per-document review-tag checks (reviewer only tags documents in scope).
- ADR-006 (Schema Standards): PascalCase schemas, schema.org vocabulary, explicit types + required flags.
- ADR-016 (Mandatory Seed Data): seed matters, holds, custodians across municipality/consultancy personas.

## Risks / Trade-offs

- **Retention performance**: suspension hook adds a query (is this doc under hold?) on every retention evaluation. Mitigation: index holds by document ID; cache active holds per app-session.
- **Custodian complexity**: multi-step acknowledgement workflow (delivery → reminder → escalation) requires careful state tracking. Mitigation: HoldNotice schema captures full lifecycle; idempotent reminder dispatch.
- **Redaction blocking**: production export is blocked until all privileged docs are redacted. Risk: reviewers stuck if redaction capacity is low. Mitigation: defer unredacted docs to next production cycle; track redaction backlog in UI.
- **AccessAudit append-only**: OpenRegister does not currently enforce append-only at schema level; current approach relies on archival status. Mitigation: follow-up OpenRegister change; document the current limitation.

## Standards

- EDRM (Electronic Discovery Reference Model) — phase alignment.
- Federal Rules of Civil Procedure (FRCP) Rule 37(e) — spoliation defensibility.
- Sedona Conference Principles for Electronic Document Production.
- AVG Article 15 (right of access) and Woo (Wet open overheid) procedural deadlines.
- NEN 2082 / ISO 16175 records-management compatibility for the audit trail.

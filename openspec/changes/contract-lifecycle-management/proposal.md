## Why

DocuDesk treats contracts as ordinary documents with a content-type tag, sufficient for storage and retention but not for operational management. Legal, procurement, sales, and finance teams lack visibility into contract state (draft, signed, expiring, renewed) and lack automation for approval routing, signature collection, renewal reminders, and clause standardisation. Gemeenten, MKB klanten, and back-office operations today run contracts on a mix of email, SharePoint, DocuSign exports, and Excel trackers — missing renewals, lapsed termination windows, and document-draft disagreements are endemic. This spec introduces a Contract Lifecycle Management (CLM) system inside docudesk with explicit lifecycle states, versioning with redlines, approval routing, e-signature integration, automated reminders, renewal workflows, a reusable clause library, and portfolio search.

## What Changes

- Add a new **Contract** entity (extends docudesk Document) with lifecycle states (draft, in_review, awaiting_signature, active, expiring_soon, expired, terminated, superseded), contract type (dienstverlening, inkoop, arbeidsovereenkomst, NDA, licentie, SLA, raamovereenkomst), counterparty org/contact, term dates, notice period, renewal settings, owner, and department.
- Introduce a **versioning model** (ContractVersion) that preserves prior versions immutable and produces redline diffs between drafts.
- Add a **ContractApproval** workflow that enforces sequential approval chains per contract type, configured per tenant.
- Introduce a **ContractSignature** integration layer (DocuSign, SignHey, manual) that tracks envelope status, signs with qualified certificates, and transitions contracts to active.
- Add a **ContractReminder** system that fires notifications before expiry, notice period, or custom dates, and surfaces expiring_soon status.
- Introduce a **renewal workflow** that clones an active contract as a new draft, prefills counterparty and terms, and marks the parent as superseded.
- Add a **ContractClause library** (reusable clause code, category, jurisdiction) and **ContractClauseUsage** tracking so legal teams standardise terms and detect deprecated clause usage.
- Add a **contract portfolio surface** (search, filter by counterparty, status, value band, date range, owner, clause content; export CSV/Excel) respecting per-row ACL.

## Capabilities

### New Capabilities

- `contract-lifecycle`: Contract entity, lifecycle state machine, status validation
- `contract-versioning`: Version history, immutable versions, redline diffs
- `contract-approvals`: Approval routing, role-based approver chains, sequential decision gates
- `contract-signatures`: E-signature provider integration (DocuSign, SignHey), webhook tracking, qualified certificates
- `contract-reminders`: Expiry/notice/review triggers, notification channels, status flags
- `contract-renewal`: Renewal workflow, parent-child linking, supersession tracking
- `contract-clauses`: Clause library, clause usage tracking, deprecation warnings
- `contract-portfolio`: Contract search, filtering, reporting, export (CSV/Excel)

### Modified Capabilities

None. Contract CLM is additive to docudesk's document model.

## Impact

**Affected code (docudesk):**
- `lib/Entities/ContractEntity.php` — new entity extending DocumentEntity with CLM-specific fields
- `lib/Entities/ContractVersionEntity.php`, `ContractApprovalEntity.php`, `ContractSignatureEntity.php`, `ContractReminderEntity.php`, `ContractClauseEntity.php`, `ContractClauseUsageEntity.php` — new entities
- `lib/Settings/docudesk_register.json` — new `contract` register with schemas for all entities and lifecycle state definitions
- `lib/Services/ContractService.php` — state validation, version creation, approval routing logic
- `lib/Services/ContractSignatureService.php` — envelope creation, webhook handling, certificate tracking
- `lib/Services/ContractReminderService.php` — scheduled task for reminder firing
- `lib/Services/ContractClauseService.php` — clause library management
- `lib/Controllers/ContractController.php` — REST API for contract CRUD, versioning, approvals, renewal
- `docs/features/contract-lifecycle-management.md` — feature documentation with user workflows and API examples

**Affected code (openconnector):**
- New `DocuSignConnector`, `SignHeyConnector` connectors for e-signature envelope lifecycle management (separate change)

**Affected downstream apps:**
- `openregister`: Contract entity stored as register schema; counterparty links resolve via openregister relations
- `openconnector`: e-signature provider connectors (DocuSign, SignHey, Itsme, ZorgID)
- `mydash`: Contract portfolio dashboards
- `pipelinq`: Sales contract creation from won opportunities
- `procest`: Procurement contracts from awarded tenders
- `financeq`: Contract value feeds into budget commitments

**APIs / dependencies:**
- HTTP API: `POST /api/contracts`, `GET /api/contracts/{id}`, `PUT /api/contracts/{id}`, `POST /api/contracts/{id}/approve`, `POST /api/contracts/{id}/send-for-signature`, `POST /api/contracts/{id}/renew`, `GET /api/contracts/search` (with filtering)
- Webhook endpoint: `POST /api/webhooks/signature-envelope/{provider}` for DocuSign/SignHey envelope callbacks
- DI: new services `ContractService`, `ContractSignatureService`, `ContractReminderService`, `ContractClauseService`

**Data / migrations:**
- New register + schemas in `docudesk_register.json` (per ADR-013 loadable templates)
- Database migration: none; all data lives in OpenRegister's `object` table
- Scheduled task: `app:background:cron:contract:reminders` (nightly reminder check)
- Config section: `contract.approval_policies`, `contract.signature_providers`, `contract.reminder_defaults`

**Architectural alignment:**
- ADR-006 (Schema Standards): Contract and related schemas use PascalCase names, schema.org-aligned vocabulary, explicit types
- ADR-011 (Deduplication): ContractApproval and ContractSignature use `$ref` for user/contact links rather than duplication
- ADR-013 (Loadable Register Templates): contract register + all seed data ship via `docudesk_register.json` envelopes
- ADR-016 (Mandatory Seed Data): seed contracts cover gemeente, MKB, and Conduction personas with realistic lifecycles

status: draft

# Contract Lifecycle Management

## Purpose

Provide a full Contract Lifecycle Management (CLM) capability inside docudesk so that legal, procurement, sales, and finance teams can manage contracts end-to-end without leaving the platform. Today docudesk treats contracts as ordinary documents with a content-type tag; that is sufficient for storage and retention but does not surface the operational state of a contract (draft, in negotiation, signed, active, expired) and does not automate the work that surrounds it (approval routing, signature collection, renewal reminders, clause reuse, portfolio search). Gemeenten, MKB klanten, and Conduction's own back-office all run contracts on a mix of email, SharePoint, DocuSign exports, and Excel trackers, which routinely produces missed renewals, lapsed termination windows, and signed documents that disagree with the negotiated draft.

This spec introduces a Contract entity with explicit lifecycle states, a versioning model that preserves redlines between drafts, configurable approval routes per contract type, a signature integration layer (DocuSign / SignHey / qualified e-signing), automated reminders before key dates (notice period, expiry, renewal trigger), a renewal workflow that can clone-and-amend an existing contract, a reusable clause library so legal teams can standardise terms, and a contract portfolio search surface that lets users find contracts across counterparties, value bands, status, and clause content. CLM positions docudesk as a credible alternative to Ironclad / Juro / ContractWorks for the MKB and gemeente segments, complementing the existing Woo/AVG strengths.

## Data Model

**Contract** (extends docudesk Document): contractNumber, title, counterpartyOrg (FK organisation), counterpartyContact (FK person), contractType (enum: dienstverlening, inkoop, arbeidsovereenkomst, NDA, licentie, SLA, raamovereenkomst, ...), value, currency, startDate, endDate, noticePeriodDays, autoRenew (boolean), renewalTermMonths, status (enum: draft, in_review, awaiting_signature, active, expiring_soon, expired, terminated, superseded), owner (FK user), department, tags[].

**ContractVersion**: contractId, versionNumber, createdBy, createdAt, body (rich text or file reference), changeSummary, redlineFromVersion (FK previous version), isCurrent.

**ContractApproval**: contractId, versionId, approverRole, approverUser, decision (pending/approved/rejected/delegated), decidedAt, comment, sequenceOrder.

**ContractSignature**: contractId, versionId, signatoryName, signatoryEmail, party (us/counterparty), provider (docusign/signhey/manual), externalEnvelopeId, status, signedAt, certificateBlob.

**ContractReminder**: contractId, triggerType (expiry/notice/review/custom), triggerOffsetDays, recipients[], lastFiredAt, nextFireAt.

**ContractClause** (library): clauseCode, title, category, body, jurisdiction, language, deprecated, lastReviewedBy, lastReviewedAt.

**ContractClauseUsage**: contractId, clauseCode, versionId, position.

## Requirements

### REQ-CLM-001: Lifecycle state machine
GIVEN a contract in state X, WHEN a user attempts a transition, THEN the system MUST validate the transition against the configured state machine and reject illegal moves (e.g. cannot go from draft directly to active without passing awaiting_signature unless contractType allows it).

### REQ-CLM-002: Version history with redlines
GIVEN a contract is edited after first save, WHEN the user saves changes, THEN the system MUST create a new ContractVersion preserving the prior body and produce a redline diff viewable in-app; previous versions MUST remain immutable.

### REQ-CLM-003: Approval routing
GIVEN a contract type with an approval policy (e.g. value > €50k requires CFO approval), WHEN the contract is submitted for review, THEN the system MUST resolve the approver chain, notify approvers in sequence, and block progression to awaiting_signature until all approvals are recorded.

### REQ-CLM-004: E-signature integration
GIVEN an approved contract version, WHEN the owner triggers "send for signature", THEN the system MUST create an envelope in the configured provider, track signature status via webhook, attach the signed PDF + certificate of completion to the version on completion, and transition the contract to active.

### REQ-CLM-005: Expiry and notice reminders
GIVEN a contract with endDate and/or noticePeriodDays, WHEN the system clock reaches a configured reminder offset, THEN the system MUST notify the owner and any additional recipients via the docudesk notification channel and via email, and MUST set the contract status to expiring_soon at the appropriate threshold.

### REQ-CLM-006: Renewal workflow
GIVEN an active contract approaching expiry, WHEN the owner initiates a renewal, THEN the system MUST clone the current version as a new draft contract, link the renewal to the parent contract, prefill counterparty and term fields, and mark the parent as superseded when the renewal goes active.

### REQ-CLM-007: Clause library
GIVEN a clause library, WHEN a user inserts a clause into a contract version, THEN the system MUST record the usage; WHEN a library clause is marked deprecated, THEN the system MUST surface a warning on every active contract that still references it.

### REQ-CLM-008: Portfolio search and reporting
GIVEN the contract portfolio, WHEN a user searches or filters by counterparty, status, value band, date range, owner, or clause, THEN the system MUST return matching contracts within 2 seconds for portfolios up to 50.000 records and MUST allow export to CSV / Excel respecting per-row ACL.

## Standards

- ISO 14533 (Long-term signature profiles).
- eIDAS (qualified electronic signatures).
- xCBL / UBL contract message subsets for procurement contracts.
- AVG/GDPR Article 30 register linkage (contracts with verwerkers must surface in the verwerkersregister).

## Cross-app

- **openregister**: Contract entity stored as a register schema; counterparty links resolve via openregister relations.
- **openconnector**: e-signature provider connectors (DocuSign, SignHey, Itsme, ZorgID for healthcare).
- **mydash**: Contract portfolio dashboards (expiring contracts, value-by-type, supplier concentration).
- **pipelinq**: Sales contract creation from won opportunities.
- **procest**: Procurement contracts created from awarded tenders.
- **financeq**: Contract value feeds budget commitments and accrual schedules.

## Target users

Legal counsel, procurement officers, sales operations, finance controllers, HR (employment contracts), gemeente inkoopadviseurs, MKB ondernemers managing supplier and customer contracts.

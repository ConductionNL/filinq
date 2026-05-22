status: draft

# E-Discovery and Legal Hold

## Purpose

Provide an e-discovery and legal-hold capability inside docudesk so that organisations facing litigation, regulatory investigation, Woo-verzoeken, AVG access requests, or internal investigations can preserve, search, review, and produce relevant documents in a defensible manner. Today docudesk has a retention engine that automatically deletes or archives content according to a policy. That is exactly the wrong behaviour when a matter is under legal hold: every byte must be preserved untouched until the hold is released, even if the routine retention schedule says "delete after 90 days". Without a first-class hold mechanism, organisations either disable retention globally (creating storage and AVG-compliance problems) or risk spoliation claims and dwangsommen.

This spec introduces a Matter and Hold entity, a custodian-tracking workflow with formal hold-notice acknowledgement, a suspension hook into the retention engine, a search-and-review surface that lets reviewers tag documents as responsive / privileged / not-responsive, redaction integration for privileged passages, a production-set workflow that bundles the final responsive-and-not-privileged set into an encrypted ZIP with a load file, and an immutable access audit. The system follows the EDRM (Electronic Discovery Reference Model) phase model so that legal teams familiar with Relativity, Everlaw, or Logikcull can map their existing process onto docudesk.

For the Dutch market the same engine serves Woo-verzoeken: the verzoek becomes a matter, the deadline becomes a target date, and the production-set becomes the public-disclosure bundle. For gemeente AVG-inzageverzoeken the production-set becomes the subject's personal-data export.

## Data Model

**Matter**: matterNumber, title, description, matterType (litigation, regulatory, woo, avg_inzage, internal_investigation), openedAt, openedBy, leadReviewer, status (open, on_hold, in_review, producing, closed), externalCaseReference, jurisdiction, dueDate.

**LegalHold**: matterId, scopeDescription, scopeFilter (structured: dateRange, custodians[], registers[], schemas[], keywords[], tags[]), issuedAt, issuedBy, releasedAt, releasedBy, releaseReason, status (active, released).

**HoldNotice**: holdId, custodianUser, deliveredAt, acknowledgedAt, acknowledgementText, reminderCount, escalatedTo.

**Custodian**: matterId, user (FK), role (employee, manager, third_party, system), startedAt, endedAt, dataSources[].

**ReviewTag**: documentId, matterId, tag (responsive, not_responsive, privileged, hot, needs_redaction, confidential), taggedBy, taggedAt, notes.

**ProductionSet**: matterId, name, createdAt, createdBy, format (pdf_bundle, native_with_loadfile, encrypted_zip), passphrase (hashed), documentIds[], exportedAt, exportedBy, recipientName, recipientOrg, deliveryMethod.

**AccessAudit**: matterId, documentId, userId, action (viewed, downloaded, tagged, redacted, exported), occurredAt, ipAddress, userAgent.

## Requirements

### REQ-EDL-001: Matter creation and scoping
GIVEN a user with the legal_hold_admin role, WHEN they create a matter and define a hold scope, THEN the system MUST persist the matter, evaluate the scope filter against the corpus, and report the count of documents that will fall under hold before the hold is activated.

### REQ-EDL-002: Retention suspension
GIVEN a document under an active hold, WHEN the retention engine evaluates that document for deletion or archival, THEN the engine MUST skip the action, log the skip with the hold reference, and notify the retention administrator if the policy would otherwise have triggered.

### REQ-EDL-003: Custodian hold-notice
GIVEN a hold with named custodians, WHEN the hold is activated, THEN the system MUST deliver a hold-notice to each custodian via in-app notification and email, MUST require explicit acknowledgement, MUST send reminders on a configurable cadence until acknowledged, and MUST escalate to the custodian's manager after the configured threshold.

### REQ-EDL-004: Search and review
GIVEN documents in scope of a matter, WHEN a reviewer queries the review surface with keyword, metadata, or date filters, THEN the system MUST return results within 3 seconds for matters up to 100.000 documents and MUST allow the reviewer to apply ReviewTags and add notes per document.

### REQ-EDL-005: Privilege redaction
GIVEN a document tagged "privileged" or "needs_redaction", WHEN included in a production set, THEN the system MUST require that redaction has been applied and approved before the document can be included; unredacted privileged documents MUST be hard-blocked from production export.

### REQ-EDL-006: Production-set export
GIVEN a finalised production set, WHEN the reviewer triggers export, THEN the system MUST generate the bundle in the configured format, encrypt the output with a per-set passphrase, attach a load file describing each document (bates number, original path, hash, tags), and store the export artefact with its own retention rule.

### REQ-EDL-007: Immutable access audit
GIVEN any interaction with a held document or matter, WHEN the action occurs, THEN the system MUST write an AccessAudit row that is append-only (no UPDATE or DELETE permitted) and MUST be exportable as a separate audit report per matter.

### REQ-EDL-008: Hold release
GIVEN an active hold, WHEN a legal_hold_admin releases it, THEN the system MUST record the release reason and releaser, MUST resume normal retention evaluation on the previously-held documents at the next retention pass, and MUST notify previously-noticed custodians that the hold is lifted.

## Standards

- EDRM (Electronic Discovery Reference Model) — phase alignment.
- Federal Rules of Civil Procedure (FRCP) Rule 37(e) — spoliation defensibility (for US-touching matters).
- Sedona Conference Principles for Electronic Document Production.
- AVG Article 15 (right of access) and Woo (Wet open overheid) procedural deadlines.
- NEN 2082 / ISO 16175 records-management compatibility for the audit trail.

## Cross-app

- **openregister**: documents under hold flagged in the underlying object store; retention metadata tagged.
- **docudesk retention engine**: explicit suspension hook honoured by this spec.
- **docudesk redaction-at-scale**: privileged passages redacted through that pipeline before production.
- **opencatalogi / Woo publicatie**: Woo production-sets can flow directly to the public catalogue.
- **openconnector**: optional sync to specialist e-discovery tools (Relativity, Everlaw) for very large matters.

## Target users

In-house legal counsel, compliance officers, gemeente Woo-coördinatoren, AVG functionarissen gegevensbescherming, HR investigators, external counsel granted scoped reviewer access.

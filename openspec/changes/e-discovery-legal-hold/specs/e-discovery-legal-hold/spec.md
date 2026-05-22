## ADDED Requirements

### Requirement: Matter creation and scoping (REQ-EDL-001)

The system SHALL allow a user with the `legal_hold_admin` role to create a Matter object and define a Hold with a scope filter. The scope filter SHALL be evaluated against the document corpus to determine which documents fall under the hold before activation.

#### Scenario: Matter is created with required fields
- **WHEN** a `legal_hold_admin` user POSTs `/api/objects/matter` with `matterNumber`, `title`, `matterType`, `dueDate`
- **THEN** the response is 201 Created with a `uuid` and the matter is stored in OpenRegister

#### Scenario: Hold is defined with a scope filter
- **GIVEN** a matter exists with uuid `matter-uuid`
- **WHEN** a `legal_hold_admin` POSTs `/api/objects/legal-hold` with `matterId`, `scopeDescription`, and `scopeFilter` (containing dateRange, custodians, registers, keywords)
- **THEN** the hold is created and the system evaluates `scopeFilter` against the corpus and reports count of matching documents before the hold is activated

#### Scenario: Scope filter with date range, custodians, and registers
- **WHEN** a hold defines scopeFilter with `dateRange: {from: "2024-01-01", to: "2024-12-31"}`, `custodians: [uuid1, uuid2]`, `registers: ["document"]`
- **THEN** the hold scope matches only documents created between those dates AND created by those custodians AND in the document register

#### Scenario: Hold can be activated to suspend retention
- **GIVEN** a hold with scopeFilter evaluated to N matching documents
- **WHEN** the `legal_hold_admin` sets the hold `status` to "active"
- **THEN** those N documents are marked as "under hold" and the retention engine will skip deletion/archival for those documents

### Requirement: Retention suspension (REQ-EDL-002)

The system SHALL suspend retention evaluation for any document under an active hold. When the retention engine evaluates a document for deletion or archival, it MUST check if the document is under hold; if yes, skip the action and log the skip with the hold reference.

#### Scenario: Retention engine respects active hold
- **GIVEN** a document `doc-id` is under an active hold (scope filter matches the document)
- **WHEN** the retention engine evaluates `doc-id` for deletion according to its retention policy
- **THEN** the engine skips the deletion, logs "Hold-" + hold-uuid in the skip reason, and does not delete the document

#### Scenario: Retention resumes after hold is released
- **GIVEN** a document `doc-id` is under an active hold
- **WHEN** the hold is released (status changes to "released")
- **THEN** on the next retention evaluation pass, the document is no longer exempted and normal retention rules apply

#### Scenario: Multiple overlapping holds are respected
- **GIVEN** document `doc-id` matches scopes of two active holds (hold-1 and hold-2)
- **WHEN** retention engine evaluates `doc-id`
- **THEN** the document is preserved if ANY hold is active (OR-semantics)

### Requirement: Custodian hold-notice (REQ-EDL-003)

The system SHALL deliver a hold notice to each custodian named in the hold, require explicit acknowledgement, send reminders on a configurable cadence, and escalate to the custodian's manager after the configured threshold is exceeded.

#### Scenario: Hold notice is delivered when hold is activated
- **GIVEN** a hold with `custodians: [user-uuid-alice, user-uuid-bob]` is activated
- **WHEN** the hold status changes to "active"
- **THEN** a HoldNotice object is created for each custodian with `deliveredAt` timestamp and state "awaiting acknowledgement"
- **AND** each custodian receives an in-app notification saying "You have a legal hold to acknowledge"
- **AND** each custodian receives an email with the hold description and a link to acknowledge

#### Scenario: Custodian acknowledges the hold
- **GIVEN** a HoldNotice exists with status "awaiting acknowledgement"
- **WHEN** the custodian logs into DocuDesk and clicks "I Acknowledge" in the notification
- **THEN** the HoldNotice `acknowledgedAt` is set to current timestamp
- **AND** the `acknowledgedBy` field is set to the custodian's user ID
- **AND** an AuditTrail entry records the acknowledgement with the custodian and timestamp

#### Scenario: Reminder is sent if custodian does not acknowledge within configured days
- **GIVEN** a HoldNotice created 5 days ago with `acknowledgedAt: null` and `reminderCount: 0` and the system is configured to send reminders after 3 days
- **WHEN** a background job evaluates overdue notices
- **THEN** a reminder notification and email are sent to the custodian
- **AND** `reminderCount` is incremented to 1

#### Scenario: Escalation occurs after threshold reminders
- **GIVEN** a HoldNotice with `acknowledgedAt: null`, `reminderCount: 2`, and escalation threshold is 2
- **WHEN** another reminder becomes due
- **THEN** instead of sending another reminder to the custodian, the notice is escalated to `custodian.managerId` (the custodian's manager)
- **AND** `escalatedTo` is set to the manager's user ID
- **AND** the manager receives a notification and email saying "Custodian [name] has not acknowledged legal hold after 2 reminders"

### Requirement: Search and review (REQ-EDL-004)

The system SHALL provide a search and review surface where reviewers can query documents in scope of a matter using keyword, metadata, or date filters. The system MUST return results within 3 seconds for matters up to 100,000 documents. Reviewers MUST be able to apply ReviewTags (responsive, not_responsive, privileged, hot, needs_redaction, confidential) and add notes per document.

#### Scenario: Reviewer queries documents by keyword
- **GIVEN** a matter with 50k documents in scope
- **WHEN** the reviewer searches for keyword "contract" in the review surface
- **THEN** matching documents are returned within 3 seconds with highlights showing context around the keyword

#### Scenario: Reviewer applies a responsive tag
- **GIVEN** a document is displayed in the review surface for a matter
- **WHEN** the reviewer clicks the "Responsive" tag button and optionally adds notes
- **THEN** a ReviewTag is created with `tag: "responsive"`, `taggedBy: reviewer-uuid`, `taggedAt: timestamp`, `notes: "Directly on point"` (if provided)
- **AND** an AuditTrail entry records the tagging

#### Scenario: Reviewer can apply multiple tags to the same document
- **GIVEN** a document in review
- **WHEN** the reviewer applies both "responsive" and "hot" tags to the same document
- **THEN** two ReviewTag objects are created (both reference the same documentId + matterId but different `tag` values)

#### Scenario: Reviewer notes are searchable and visible
- **GIVEN** a ReviewTag with notes "Settlement amount $500k"
- **WHEN** another reviewer searches for "settlement", the notes field is included in full-text search
- **THEN** the document is surfaced in results and the notes are visible in the detail view

#### Scenario: Filters are combinable (AND-semantics)
- **WHEN** a reviewer filters by `dateRange: {from: "2024-01-01", to: "2024-06-30"}` AND `tag: "responsive"`
- **THEN** only documents created in that date range AND tagged as responsive are shown

### Requirement: Privilege redaction (REQ-EDL-005)

The system SHALL enforce that documents tagged "privileged" or "needs_redaction" cannot be included in a production set unless redaction has been applied and approved. Unredacted privileged documents SHALL be hard-blocked from production export.

#### Scenario: Production export is blocked if any privileged document is unredacted
- **GIVEN** a ProductionSet with `documentIds` including a document tagged "privileged" where `redactionApprovedAt: null`
- **WHEN** the reviewer attempts to export the production set
- **THEN** the system returns a 400 error citing the unredacted privileged document and blocking the export
- **AND** a list of all unredacted privileged documents is shown to the reviewer

#### Scenario: Production export succeeds after redaction is approved
- **GIVEN** a ProductionSet with a document tagged "privileged" and `redactionApprovedAt: "2026-05-22T10:00:00Z"` (redaction completed and approved)
- **WHEN** the reviewer exports the production set
- **THEN** the document is included in the export with its redacted version (passages marked [REDACTED] or blacked out per redaction-at-scale pipeline)

#### Scenario: Document with needs_redaction tag also blocks export if not redacted
- **GIVEN** a ProductionSet including a document tagged "needs_redaction" with no redaction approval
- **WHEN** export is triggered
- **THEN** the document is blocked from export with the same error as privileged documents

### Requirement: Production-set export (REQ-EDL-006)

The system SHALL generate a production set in the configured format (encrypted ZIP or native with load file). The system MUST include a load file listing each document (bates number, original path, hash, tags), encrypt the output with a per-set passphrase, and store the export artefact with its own retention rule.

#### Scenario: ProductionSet is created from tagged documents
- **GIVEN** a matter with documents tagged "responsive" or left untagged (not explicitly marked not_responsive)
- **WHEN** a reviewer creates a ProductionSet with `name: "Production Set 1 - Responsive Docs"` and `format: "encrypted_zip"`
- **THEN** a ProductionSet object is created with `createdAt`, `createdBy`, `documentIds: [list of responsive/untagged docs]`

#### Scenario: Production set generates encrypted ZIP with load file
- **GIVEN** a ProductionSet with 1,000 responsive documents
- **WHEN** the reviewer triggers export (POST `/api/objects/production/<uuid>/export`)
- **THEN** the system generates:
  - A ZIP file containing native documents (PDFs, Office, images, etc.)
  - A load file (CSV or XML) with columns: [bates_number, original_path, md5_hash, responsiveness_tag, privileged_flag]
  - The ZIP is encrypted with a generated passphrase
  - `exportedAt` and `exportedBy` are recorded on the ProductionSet

#### Scenario: Passphrase is hashed, key delivered separately
- **GIVEN** an encrypted ProductionSet has been created
- **WHEN** the legal team downloads the ZIP
- **AND** they request the passphrase
- **THEN** the passphrase is delivered via a separate secure channel (email, SMS, phone call) — NOT embedded in the ZIP or in the download response

#### Scenario: Load file correlates documents by hash
- **GIVEN** an opposing party has the same documents natively (they obtained them from other sources)
- **WHEN** they compute MD5 hashes of their copies and match them against the load file
- **THEN** they can verify that the produced set is identical to the original (no tampering, no missing pages)

#### Scenario: Production set is stored with retention rule
- **GIVEN** a ProductionSet export is complete
- **WHEN** it is stored in the production archive (OpenRegister or file system)
- **THEN** the system assigns a retention rule (e.g., "retain for 7 years, then delete") so the produced set does not persist indefinitely

### Requirement: Immutable access audit (REQ-EDL-007)

The system SHALL record an AccessAudit entry for every interaction with a held document or matter. The AccessAudit table SHALL be append-only (no UPDATE or DELETE permitted). All audit entries SHALL be exportable as a separate audit report per matter.

#### Scenario: Document view is audited
- **GIVEN** a user views a document in a matter under hold
- **WHEN** they open the document detail view
- **THEN** an AccessAudit entry is created with `action: "viewed"`, `userId`, `documentId`, `matterId`, `occurredAt`, `ipAddress`, `userAgent`

#### Scenario: Document tag application is audited
- **GIVEN** a reviewer tags a document as "responsive"
- **WHEN** the tag is saved
- **THEN** an AccessAudit entry is created with `action: "tagged"`, `documentId`, `matterId`, `userId`, and the tag value is recorded

#### Scenario: Document download is audited
- **GIVEN** a user downloads a native document from the review surface
- **WHEN** the download completes
- **THEN** an AccessAudit entry is created with `action: "downloaded"`, `documentId`, `matterId`, `userId`, `ipAddress`, `userAgent`

#### Scenario: AccessAudit rows are immutable
- **GIVEN** an AccessAudit entry exists
- **WHEN** a user (even admin) attempts to UPDATE or DELETE that row via API or database
- **THEN** the operation is rejected with a 405 Method Not Allowed (or 403 Forbidden)
- **AND** the row remains unchanged

#### Scenario: Audit report is generated per matter
- **GIVEN** a matter has 100+ AccessAudit entries
- **WHEN** the legal_hold_admin requests an audit export for that matter
- **THEN** the system generates a CSV or JSON report with all AccessAudit entries for that `matterId`, sorted by `occurredAt`
- **AND** the report includes summary statistics (documents viewed, tagged, downloaded, exported; unique users; date range)

#### Scenario: Audit export is itself audited
- **GIVEN** an audit report is generated for a matter
- **WHEN** the export completes
- **THEN** an AccessAudit entry is created with `action: "exported"`, `matterId` (no specific documentId, as the action is matter-level)

### Requirement: Hold release (REQ-EDL-008)

The system SHALL record the release of a hold with the release reason and releaser. The system MUST resume normal retention evaluation on previously held documents at the next retention pass. The system MUST notify previously noticed custodians that the hold is lifted.

#### Scenario: Hold is released by legal_hold_admin
- **GIVEN** an active hold with status "active"
- **WHEN** a `legal_hold_admin` user PUTs the hold with `status: "released"`, `releasedAt: timestamp`, `releasedBy: admin-uuid`, `releaseReason: "Matter settled"`
- **THEN** the hold state changes and is recorded in the audit trail

#### Scenario: Documents are evaluated for retention after hold release
- **GIVEN** documents that were under hold are now released
- **WHEN** the next retention evaluation pass runs (daily, configurable)
- **THEN** those documents are no longer exempted; if their retention policy says "delete after 90 days" and they are over 90 days old, they are deleted or archived per policy

#### Scenario: Custodians are notified of hold release
- **GIVEN** previously-noticed custodians who acknowledged the hold
- **WHEN** the hold is released
- **THEN** each custodian receives a notification (in-app + email) saying "The legal hold [matter title] has been lifted. You are no longer required to preserve relevant documents."
- **AND** a log entry records the release notification sent to each custodian

#### Scenario: Release reason is recorded for audit
- **GIVEN** a hold is released with `releaseReason: "Matter settled. No further discovery required."`
- **WHEN** the audit trail is queried
- **THEN** the release reason and releaser are visible in the audit trail for future compliance review

### Requirement: Matter status lifecycle

The system SHALL support the following matter status transitions: `open` → `on_hold` (hold activated) → `in_review` (documents being reviewed) → `producing` (production sets being created) → `closed` (matter concluded). These are recommendations, not enforced constraints, to guide users through the EDRM phases.

#### Scenario: Matter status progresses through phases
- **WHEN** a matter is created, status is initially "open"
- **AND** a hold is activated for that matter, status can transition to "on_hold"
- **AND** reviewers begin tagging documents, status can transition to "in_review"
- **AND** production sets are created, status can transition to "producing"
- **AND** the matter is concluded, status is set to "closed"
- **THEN** the audit trail records each status transition with timestamp and actor

### Requirement: Custodian role classification

The system SHALL support custodian roles: `employee` (full-time employee), `manager` (supervisory role), `third_party` (external consultant, former employee), `system` (automated account). The role affects hold-notice delivery logic (e.g., third_party custodians may use email token instead of Nextcloud login).

#### Scenario: Third-party custodian receives email token for acknowledgement
- **GIVEN** a custodian with `role: "third_party"` is added to a hold
- **WHEN** the hold is activated and hold notice is to be delivered
- **THEN** instead of sending a Nextcloud login link, a one-time email token is generated
- **AND** the email includes a link like `https://docudesk.example.com/holds/acknowledge?token=abc123` that requires no Nextcloud login

### Requirement: Matter type classification

The system SHALL support matter types: `litigation` (court case), `regulatory` (government investigation), `woo` (Wet open overheid request), `avg_inzage` (AVG Art. 15 data subject access request), `internal_investigation` (HR, compliance, audit). The matter type influences default scope assumptions and export format recommendations.

#### Scenario: Woo matter defaults to public production format
- **WHEN** a matter with `matterType: "woo"` is created
- **AND** a ProductionSet is created for that matter
- **THEN** the system recommends `format: "native_with_loadfile"` (not encrypted; suitable for public disclosure)

#### Scenario: Litigation matter defaults to encrypted production format
- **WHEN** a matter with `matterType: "litigation"` is created
- **AND** a ProductionSet is created
- **THEN** the system recommends `format: "encrypted_zip"` (encrypted; suitable for opposing counsel delivery)

### Requirement: Custodian data source tracking

The system SHALL allow recording which data sources (email, file share, mobile device, database, etc.) a custodian has data in. This information helps reviewers understand the custodian's data footprint and estimate the scope of collection.

#### Scenario: Custodian is created with data sources
- **GIVEN** a custodian "Alice Smith" working for Gemeente Demostad
- **WHEN** the custodian is created with `dataSources: ["email", "sharepoint", "finance-system", "laptop"]`
- **THEN** these sources are stored and visible in custodian detail view

#### Scenario: Data sources inform search strategy
- **WHEN** a reviewer searches for documents matching a hold scope
- **AND** knows custodian Alice has data in Sharepoint + Finance system
- **THEN** the search can be narrowed to those systems to avoid searching Alice's personal laptop (which may not be connected to network)

---

## ADDED Capabilities

- `matter-lifecycle`: Matter creation, type classification, status transitions, due date, lead reviewer assignment.
- `legal-hold-scope-definition`: Hold scope via custodians, date range, registers, schemas, keywords; scope evaluation and document counting.
- `retention-suspension-hook`: Integration with retention engine to skip deletion/archival of documents under active hold.
- `custodian-hold-notice-delivery`: In-app + email notification, acknowledgement tracking, reminder cadence, escalation to manager.
- `document-review-and-tagging`: Search surface, ReviewTag application per document per matter, notes, full audit trail.
- `privilege-redaction-enforcement`: Block production export if privileged/needs_redaction documents are unredacted.
- `production-set-export-encrypted-zip`: Generate encrypted ZIP with load file (bates, path, hash, tags), passphrase protection, retention rule.
- `access-audit-immutable`: Append-only audit trail for viewed, downloaded, tagged, redacted, exported actions.
- `hold-release-with-notification`: Release hold, resume retention, notify custodians.
- `custodian-role-classification`: employee, manager, third_party, system roles with role-specific notice delivery.
- `matter-type-classification`: litigation, regulatory, woo, avg_inzage, internal_investigation with type-specific defaults.

## MODIFIED Capabilities

- **retention-engine** (docudesk): Honor suspension hook before deletion/archival.
- **redaction-at-scale** (docudesk): Integrate with privilege-redaction-enforcement.
- **notification-service** (platform): Deliver hold notices, reminders, escalations, release notifications.

## ADDED Requirements

### Requirement: Unredact role access with audit trail and notification

Users with the `unredact` role MUST be able to request and retrieve the original (non-redacted) document from a `RedactedDocument` record. Every unredact access MUST be logged in `RedactionAudit` with the accessing user, timestamp, and reason (if provided). The system MUST notify the job's `requestedBy` user of the unredact access.

#### Scenario: User with unredact role can retrieve original
- **GIVEN** a `RedactedDocument` with `accessibleTo: ["unredact"]` and `retainOriginalUntil: "2027-05-20T23:59:59+02:00"`
- **WHEN** a user with the `unredact` role requests `GET /api/redactions/documents/<redactedDocumentId>/original`
- **THEN** the response is 200 with the original (non-redacted) document
- **AND** a `RedactionAudit` entry is created with `action: "original_accessed"`, actor set to the requesting user, and timestamp

#### Scenario: User without unredact role cannot retrieve original
- **GIVEN** a `RedactedDocument` with `accessibleTo: ["unredact"]`
- **WHEN** a user without the `unredact` role requests the original
- **THEN** the response is 403 Forbidden

#### Scenario: Job owner is notified of unredact access
- **WHEN** a user accesses the original document via the unredact API
- **THEN** a notification is sent to the job's `requestedBy` user containing:
  - The document name
  - The accessing user's name/ID
  - The timestamp of access
  - A link to the audit trail for this job

#### Scenario: Retention policy blocks expired original retrieval
- **GIVEN** a `RedactedDocument` with `retainOriginalUntil: "2026-05-15T23:59:59+02:00"` and today's date is 2026-05-20
- **WHEN** a user with the `unredact` role requests the original
- **THEN** the response is 410 Gone
- **AND** the error message cites the retention expiration date

#### Scenario: Unredact access is logged with reason (optional)
- **WHEN** a user accesses the original with a reason field (e.g., "Appeal process, document needed by legal")
- **THEN** the `RedactionAudit` entry includes the reason in a notes field
- **AND** the notification to the job owner includes the reason

### Requirement: Redaction audit trail for compliance

Every action in the redaction lifecycle MUST be logged in `RedactionAudit` with actor, action type, timestamp, and relevant context. Actions include: auto_detected (pattern match), reviewer_added, reviewer_removed, applied (annotation approved for export), exported (export completed), original_accessed (unredact request).

#### Scenario: Audit trail captures full action history
- **GIVEN** a job with the following timeline:
  - 09:32 - system auto-detects 20 annotations
  - 10:15 - alice removes 2 annotations
  - 10:18 - alice adds 1 custom annotation
  - 10:20 - alice approves all remaining annotations
  - 10:21 - system exports the redacted document
- **WHEN** the user requests the audit trail via `GET /api/redactions/jobs/<jobId>/audit`
- **THEN** the response includes all 5 events with actor, action, timestamp, and affected annotation IDs

#### Scenario: Audit trail entries are immutable
- **GIVEN** an audit-trail entry
- **WHEN** the system stores it
- **THEN** the entry cannot be modified or deleted (only appended to)
- **AND** deletion attempts return 405 Method Not Allowed

#### Scenario: Audit trail supports filtering by action and actor
- **WHEN** the user requests `GET /api/redactions/jobs/<jobId>/audit?action=reviewer_added&actor=alice`
- **THEN** the response includes only entries where `action == "reviewer_added"` and `actor == "alice"`

#### Scenario: Original access is auditable
- **GIVEN** a job with an exported redacted document
- **WHEN** user bob (unredact role) accesses the original at 11:30 on 2026-05-20
- **THEN** an audit-trail entry exists with:
  - `jobId`: the job ID
  - `action`: "original_accessed"
  - `actor`: "bob"
  - `occurredAt`: "2026-05-20T11:30:00+02:00"`

### Requirement: Access control on redacted documents

Access to redacted documents is controlled by the `accessibleTo` field on `RedactedDocument`, which lists roles (e.g., "woo_coordinator", "unredact", "document_owner"). Only users with at least one matching role can retrieve the redacted document.

#### Scenario: Role-based access to redacted document
- **GIVEN** a `RedactedDocument` with `accessibleTo: ["woo_coordinator", "unredact"]`
- **WHEN** a user with role `woo_coordinator` requests the document
- **THEN** the response is 200 (access granted)

#### Scenario: Missing role denies access
- **GIVEN** a `RedactedDocument` with `accessibleTo: ["woo_coordinator"]`
- **WHEN** a user without the `woo_coordinator` role requests the document
- **THEN** the response is 403 Forbidden

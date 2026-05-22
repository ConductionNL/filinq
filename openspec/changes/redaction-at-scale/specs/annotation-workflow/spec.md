## ADDED Requirements

### Requirement: Manual annotation review and override

The system SHALL display all auto-detected `RedactionAnnotation` records in a reviewer UI, allowing the reviewer to add new redaction rectangles, remove existing annotations, change category, and leave reviewer notes. Every change MUST be captured in `RedactionAudit` with actor, timestamp, and old/new values. Annotations transition through states: `pending` (auto-detected, awaiting review) → `applied` (approved for export) or `rejected_by_reviewer` (marked for exclusion).

#### Scenario: Reviewer UI displays all auto-detected annotations with source category
- **GIVEN** a job has completed pattern matching and NLP with 15 detected annotations
- **WHEN** a reviewer opens the job in the annotation editor
- **THEN** all 15 annotations are displayed with bounding rectangles on the document page
- **AND** each annotation shows its source category (e.g., "BSN (11-proef)", "PERSON (NLP, 0.92)")

#### Scenario: Reviewer adds a new annotation manually
- **WHEN** a reviewer draws a new rectangle over text that was not auto-detected
- **THEN** a new `RedactionAnnotation` is created with `addedBy: "<reviewer-user-id>"`, `addedAt: "<timestamp>"`, and `status: "pending"`
- **AND** an audit-trail entry logs the addition with the coordinates and category

#### Scenario: Reviewer removes an existing annotation
- **GIVEN** an annotation with `status: "pending"` or `"applied"`
- **WHEN** the reviewer clicks "Remove"
- **THEN** the annotation's `status` transitions to `"rejected_by_reviewer"`
- **AND** an audit-trail entry logs the removal with the reviewer's user ID and timestamp

#### Scenario: Reviewer changes annotation category
- **GIVEN** an annotation marked as "email" by the pattern matcher
- **WHEN** the reviewer changes the category to "custom" and adds a note "administrative code, not PII"
- **THEN** the annotation's `category` is updated to `"custom"`
- **AND** the audit trail records the old value ("email"), new value ("custom"), and the reviewer note

#### Scenario: Reviewer notes are attached to annotations
- **WHEN** a reviewer adds a note like "context suggests this is a fake email; keep it"
- **THEN** the `RedactionAnnotation.reviewerNotes` field is set
- **AND** the note is visible in both the UI and in subsequent re-reviews

#### Scenario: Batch actions on annotations
- **WHEN** a reviewer selects multiple annotations of the same category (e.g., all "email")
- **THEN** the reviewer can approve/reject them in one action
- **AND** each annotation generates a separate audit-trail entry

### Requirement: Annotation state transitions with approval workflow

An annotation transitions from `pending` (awaiting approval) to `applied` (approved for export) or `rejected_by_reviewer` (will not be exported). Only annotations in `applied` state are included in the exported redacted document.

#### Scenario: Pending annotations block export
- **GIVEN** a job with 10 annotations: 8 applied, 2 pending
- **WHEN** the reviewer attempts to export the document
- **THEN** the system rejects the export request and shows which annotations are still pending
- **AND** the reviewer must approve or reject each pending annotation before export proceeds

#### Scenario: Rejected annotations are excluded from export
- **GIVEN** a job with annotations marked as `rejected_by_reviewer`
- **WHEN** the export phase runs
- **THEN** the rejected annotations are NOT redacted in the output
- **AND** the export summary includes "X annotations applied, Y rejected"

### Requirement: Annotation persistence across job states

Annotations SHALL persist through job state transitions (queued, running, completed, pending_review, export_ready, exported). A reviewer can modify annotations even after the job status changes to `completed`, and the export phase uses the final approved state.

#### Scenario: Reviewing and modifying annotations after pattern matching completes
- **WHEN** a job is in state `completed` and annotations exist in state `pending`
- **THEN** the reviewer can add/remove/change annotations
- **AND** the job state advances to `pending_review` while awaiting final approval
- **AND** once all annotations are approved/rejected, the job is ready for export

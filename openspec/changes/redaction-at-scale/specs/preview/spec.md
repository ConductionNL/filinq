## ADDED Requirements

### Requirement: Side-by-side preview with annotation toggle

The system SHALL display the original document on the left side and the proposed redacted output on the right side, with redacted regions highlighted. The reviewer MUST be able to toggle individual annotations on and off without re-running the auto-mask phase to see how the document appears with different combinations of redactions applied.

#### Scenario: Preview renders original and redacted side-by-side
- **GIVEN** a job with auto-detected annotations
- **WHEN** the reviewer opens the preview via `GET /api/redactions/jobs/<jobId>/preview`
- **THEN** the response includes two views:
  - Left: original document (scanned for visual preview, not text-selectable to prevent copying)
  - Right: proposed redacted output with all approved annotations applied
- **AND** both views are synchronized (same page visible on both sides)

#### Scenario: Redacted regions are visually highlighted
- **WHEN** the preview displays the right-side redacted document
- **THEN** regions where text will be redacted are highlighted with:
  - Black rectangles with slight transparency (so text is still barely visible)
  - A label or annotation marker (e.g., "[GEREDACTEERD-BSN]")
- **AND** the highlighting is distinct from the approved/unapproved state

#### Scenario: Individual annotation toggle
- **GIVEN** 5 approved annotations on page 3
- **WHEN** the reviewer toggles annotation #2 (email address)
- **THEN** the redacted view on the right updates immediately
- **AND** annotation #2's rectangle disappears (showing the original text beneath)
- **AND** the other 4 annotations remain applied

#### Scenario: Toggling does not require re-running pattern matching
- **WHEN** a reviewer toggles annotations on and off multiple times
- **THEN** the preview updates instantly (< 500ms)
- **AND** pattern matching/NLP is not re-executed

#### Scenario: Preview shows annotation metadata on hover
- **WHEN** the reviewer hovers over a redacted region
- **THEN** a tooltip shows:
  - Category (e.g., "BSN", "Email", "PERSON")
  - Origin (e.g., "11-proef validator", "NLP@0.87")
  - Approval status (approved, pending, rejected)
- **AND** if approved, the reviewer can click to unapprove; if pending, they can click to approve

#### Scenario: Full-document preview with page navigation
- **WHEN** a job has multiple pages
- **THEN** the preview UI includes:
  - Page navigator (1 of 12)
  - "Previous" / "Next" buttons
  - Page thumbnail view (optional)
- **AND** scrolling in one view scrolls the other view synchronously (if possible)

#### Scenario: Preview is available at any job state
- **GIVEN** a job in state "completed" (pattern matching done, annotations pending review)
- **WHEN** the reviewer opens the preview
- **THEN** annotations are shown and toggleable
- **AND** the same preview is available if the job later transitions to "pending_review" or "export_ready"

#### Scenario: Preview handles multi-page documents efficiently
- **GIVEN** a 100-page PDF
- **WHEN** the preview is loaded
- **THEN** only the current page (and optionally adjacent pages) are rendered to memory
- **AND** scrolling to another page loads that page on-demand
- **AND** total preview load time is < 2 seconds per page

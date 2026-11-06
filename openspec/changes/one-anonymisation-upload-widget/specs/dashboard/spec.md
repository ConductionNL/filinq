---
status: draft
---

# Dashboard: delta for one upload widget

## MODIFIED Requirements

### Requirement: Filinq Dashboard View (REQ-DASH-01)

**Priority:** MUST

The dashboard serves as the default landing page displaying consent statistics, recent activity, and quick anonymization access.

#### Scenario: View dashboard with consent data
- GIVEN a logged-in user opens Filinq
- AND 12 consent records exist (3 pending, 7 approved, 2 objected)
- WHEN the dashboard page loads
- THEN stat cards display Total: 12, Pending: 3, Approved: 7, Objected: 2
- AND cards are color-coded: Pending (warning/orange), Approved (success/green), Objected (error/red)

#### Scenario: View recent consent activity
- GIVEN 15 consent records exist
- WHEN the dashboard loads
- THEN the 10 most recent consent records are displayed
- AND each item shows entity text and consent status badge

#### Scenario: Dashboard with no data
- GIVEN no consent records exist
- WHEN the dashboard loads
- THEN stat cards show 0 for all categories
- AND the recent activity section shows "No consent records yet" with guidance text
- AND the quick anonymization widget is still available

#### Scenario: Quick anonymization from dashboard
- GIVEN the dashboard is displayed
- WHEN the user scrolls to the Quick Anonymization section
- THEN the AnonymizationWidget is embedded with drag-and-drop upload
- AND a confirmed upload opens the first file on My documents, as on the Anonymisation page

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-001 | Display consent statistics as cards: Total, Pending, Approved, Objected | MUST | Implemented |
| DASH-002 | Stats cards use color coding: Pending (orange), Approved (green), Objected (red) | MUST | Implemented |
| DASH-003 | Show recent consent activity (up to 10 most recent) | MUST | Implemented |
| DASH-004 | Each recent item displays entity text and consent status badge | MUST | Implemented |
| DASH-005 | Include a "Quick Anonymization" section embedding the AnonymizationWidget | MUST | Implemented |
| DASH-006 | Show loading state while fetching consent data | MUST | Implemented |
| DASH-007 | Show empty state when no consent records exist | MUST | Implemented |
| DASH-008 | Dashboard is the default landing page for the Filinq app | MUST | Implemented |

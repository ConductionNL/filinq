# document-register Specification (delta)

---
status: proposed
---

## Purpose

Surface the OR contacts, activity, and shares integration leaves on the document/report detail
record via the integration registry. References the leaves
(`integration-contacts`, `integration-activity`, `integration-shares`), ADR-019, and ADR-022.

## ADDED Requirements

### Requirement: Document Detail Renders Integration Leaf Tabs

The document/report detail surface SHALL render the contacts, activity, and shares integration
leaf tabs/widgets for the document object via the integration registry
(`IntegrationRegistry::getEnabled()`), when the corresponding NC apps / leaves are enabled. No
bespoke per-document sidebar-tab or widget system SHALL be introduced for these capabilities.

#### Scenario: Contacts, activity, and shares tabs appear on the document record

- GIVEN a document/report record open on its detail page
- AND NC Contacts, the activity leaf, and NC sharing are available
- WHEN the detail page renders
- THEN the contacts leaf tab (role-grouped person chips) SHALL be present
- AND the activity leaf tab/widget SHALL be present
- AND the shares leaf tab/widget SHALL be present
- AND all three SHALL be sourced from the integration registry, not from an app-local tab system

#### Scenario: A leaf is hidden when its app is absent

- GIVEN a document/report record whose host instance does not have NC Contacts installed
- WHEN the detail page renders
- THEN the contacts leaf tab SHALL NOT be present
- AND the page SHALL render the remaining enabled leaf tabs without error

#### Scenario: In-app document surfaces are untouched

- GIVEN the document detail page with its app-owned `Anonimisatie`, `Redactie`, and
  `Handtekeningen` tabs
- WHEN the leaf tabs are added
- THEN the app-owned tabs SHALL remain present and unchanged
- AND no leaf SHALL replace Filinq's in-app PDF/letter, eIDAS signing, or anonymisation surfaces

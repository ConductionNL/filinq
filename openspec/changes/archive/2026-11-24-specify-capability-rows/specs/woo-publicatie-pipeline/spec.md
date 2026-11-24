# woo-publicatie-pipeline Specification (delta)

## Purpose

The list of documents that must be published actively, and taking a publication offline when its source is destroyed.

## ADDED Requirements

### Requirement: Documents that must be published actively are listed with what they still lack (REQ-SCR-008)

Filinq MUST list every document whose type falls in a Woo information category that must be published actively. The list MUST say for each document whether it is published and, when it is not, what blocks it: no checked redacted copy, a pending consent, or missing metadata.

Rows: `woo-active` (filinq matrix)

#### Scenario: One document still lacks a redacted copy

- GIVEN three documents in a category that must be published actively, two published and one without a checked redacted copy
- WHEN the Woo coordinator opens the active publication list
- THEN all three are listed
- AND the third says it waits for a checked redacted copy
- @e2e exclude specification only; the change that builds it adds the Playwright test

### Requirement: A publication goes offline when its source document is destroyed (REQ-SCR-009)

When the source document of a published record is destroyed, filinq MUST withdraw the publication on the publication platform with the destruction as its reason, and MUST log it on the record. A held document is not destroyed, so its publication stays.

Rows: `woo-unpublish-destroyed` (filinq matrix)

#### Scenario: A destroyed document leaves the platform

- GIVEN a published record whose source document is destroyed through the destruction list
- WHEN the destruction is confirmed
- THEN the publication is withdrawn on the platform
- AND the record log shows the withdrawal with the destruction as its reason
- @e2e exclude specification only; the change that builds it adds the Playwright test

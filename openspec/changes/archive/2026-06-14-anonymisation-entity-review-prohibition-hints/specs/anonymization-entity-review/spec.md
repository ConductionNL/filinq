---
status: draft
---

# Anonymization Entity Review — Delta for Prohibition Flag and Suggested Bases

This delta extends the existing `anonymization-entity-review` capability so the consolidated-entities endpoint exposes per-entity `prohibitionMatch` (matching the field added on extract by the `anonymization` delta) and `suggestedBases[]` (auto-populated from the dossier's `bases[]`). Both additions are non-breaking.

## ADDED Requirements

### Requirement: The consolidated-entities endpoint response MUST include `prohibitionMatch` per entity

The endpoint `GET /api/anonymization/batch/{batchId}/entities` (per the existing capability) MUST include a `prohibitionMatch` field on every entity entry. The field follows the same shape as defined in the `anonymization` delta:

- `null` — no prohibition rule matches the entity, OR
- `{ ruleId, ruleName, highConfidence }` — a prohibition rule matched.

The matcher consulted at this endpoint MUST be the same `PolicyMatchService` used by the extract endpoint and the gate. Confidence used for `highConfidence` SHOULD be the entity's `highestConfidence` across the batch (already exposed by the existing endpoint).

#### Scenario: Entity with no prohibition match returns null

- **GIVEN** a batch with an entity that no prohibition rule matches
- **WHEN** the consolidated-entities endpoint is called
- **THEN** the entity entry has `prohibitionMatch: null`

#### Scenario: High-confidence prohibition match is reported

- **GIVEN** a batch entity with `highestConfidence: 0.93` matching prohibition rule `R-X` (primaryName "Beschermde Getuige A")
- **WHEN** the endpoint is called
- **THEN** the entity entry has `prohibitionMatch: {ruleId: "R-X", ruleName: "Beschermde Getuige A", highConfidence: true}`

#### Scenario: Highest-confidence reading is used across the batch

- **GIVEN** an entity detected in three files within a batch at confidences 0.62, 0.78, 0.91
- **AND** the entity matches a prohibition rule
- **AND** the configured threshold is 0.85
- **WHEN** the endpoint is called
- **THEN** `prohibitionMatch.highConfidence` is `true` (because the highest confidence — 0.91 — is above threshold)

### Requirement: The consolidated-entities endpoint response MUST include `suggestedBases[]` per entity

Each entity entry MUST include a `suggestedBases` field — an array of UUIDs auto-derived from the `bases[]` of the dossier the batch's files belong to. If a file does not belong to a dossier, or the dossier has empty `bases[]`, `suggestedBases` MUST be an empty array.

The field is a hint for the review UI's grondslag picker. The actual `bases[]` chosen by the operator is sent on the anonymise request and may differ from `suggestedBases`.

#### Scenario: Dossier-bound files inherit the dossier's bases

- **GIVEN** a batch whose files all belong to a dossier with `bases: ["uuid-base-a", "uuid-base-b"]`
- **WHEN** the consolidated-entities endpoint is called
- **THEN** every entity entry has `suggestedBases: ["uuid-base-a", "uuid-base-b"]`

#### Scenario: Files not in a dossier yield empty suggestedBases

- **GIVEN** a batch whose files do not belong to any dossier
- **WHEN** the endpoint is called
- **THEN** every entity entry has `suggestedBases: []`

#### Scenario: Dossier with empty bases yields empty suggestedBases

- **GIVEN** a batch whose files belong to a dossier with `bases: []` (e.g. a draft dossier)
- **WHEN** the endpoint is called
- **THEN** every entity entry has `suggestedBases: []`

#### Scenario: Files spread across dossiers — union of dossier bases

- **GIVEN** a batch whose files belong to two different dossiers, with bases `["A"]` and `["B", "C"]` respectively
- **WHEN** the endpoint is called
- **THEN** every entity entry has `suggestedBases: ["A", "B", "C"]` (union, deduplicated)

### Requirement: The change MUST be additive and non-breaking

Pre-change clients reading only the existing fields (type, value, highestConfidence, fileCount, included) MUST continue to work without modification.

#### Scenario: Pre-change client continues to work

- **GIVEN** a pre-change client reading the consolidated-entities response
- **WHEN** the client receives a response with new `prohibitionMatch` and `suggestedBases` fields
- **THEN** the client's existing code reading the unchanged fields works without modification
- **AND** the response is a strict superset of the pre-change shape

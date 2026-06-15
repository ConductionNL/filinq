---
status: draft
---

# Anonymization — Delta for Prohibition Flag (Bases Pass-Through Removed)

This delta extends the existing `anonymization` capability so the extract endpoint surfaces prohibition matches in its response. **Bases pass-through was removed from this delta in the post-explore-mode rework (2026-05-12)** — bases are now set per-relation on OR's own `PATCH /api/entity-relations/{id}` endpoint, not threaded through DocuDesk's anonymise payload. The `entities[]` shape on DocuDesk's anonymise endpoint stays at `{text, entityType, key, ...}` — no `bases` field is added to it.

## ADDED Requirements

### Requirement: The DocuDesk anonymise endpoint MUST NOT carry `bases` per entity in its payload

The endpoint payload's `entities[]` array MUST NOT introduce a `bases` field on entries. Callers that wish to attach legal bases to a detected entity occurrence MUST do so via OpenRegister's `PATCH /api/entity-relations/{id}` endpoint (or the equivalent DI mapper method `EntityRelationMapper::updateDecisionMetadata`) BEFORE invoking DocuDesk's anonymise endpoint.

DocuDesk MUST ignore any `bases` field that erroneously appears on incoming payload entries — silently drop it (do NOT 400). This preserves backwards-compatibility with any caller still on the old contract; the field becomes a no-op rather than a hard failure.

DocuDesk MUST NOT persist bases locally. Single source of truth: the `EntityRelation` row, written via OR's audited PATCH endpoint.

#### Scenario: Anonymise request without bases works exactly as before

- **GIVEN** an anonymise request payload with entities that have no `bases` field
- **WHEN** DocuDesk's controller processes it
- **THEN** the call MUST succeed
- **AND** behaviour MUST match the pre-change `anonymization` capability exactly

#### Scenario: Stray `bases` field on a payload entry is silently ignored

- **GIVEN** a caller still using the old contract sends `entities: [{text: "Jan Janssen", entityType: "PERSON", key: "x", bases: ["uuid-a"]}]`
- **WHEN** DocuDesk's controller processes it
- **THEN** the call MUST succeed
- **AND** no `bases` value MUST be written to any EntityRelation row by DocuDesk's code path (bases-set is via OR's PATCH, which the caller has not invoked)
- **AND** no error MUST be raised

#### Scenario: Bases-attached entities are redacted under their bases when those were set via OR's PATCH first

- **GIVEN** an authorized caller PATCHes OR with `{bases: ["uuid-a"]}` for an EntityRelation row R
- **AND** subsequently calls DocuDesk's anonymise endpoint without any `bases` field
- **WHEN** the call processes
- **THEN** R's `bases` value MUST remain `["uuid-a"]` (set via OR's PATCH; not overwritten by the anonymise call)
- **AND** R MUST be redacted (no `skipAnonymization=true`)

### Requirement: The extract endpoint response MUST include a `prohibitionMatch` field per detected entity

The extract endpoint's response (currently returns `entities[]` per detected entity with `text`, `entityType`, `score`) MUST include a new field `prohibitionMatch` per entity. The field is either:

- `null` — no `publicationProhibition` rule matches this entity, OR
- An object `{ ruleId, ruleName, highConfidence }` where:
  - `ruleId` is the matched rule's UUID,
  - `ruleName` is the rule's `primaryName`,
  - `highConfidence` is `true` when the entity's `score` ≥ the configured high-confidence threshold (default 0.85), `false` otherwise.

The matcher used MUST be the same `PolicyMatchService` consulted by the prohibition gate (see `anonymisation-prohibition-gate` capability). The matcher invocation at extract time is read-only and MUST NOT modify any state.

#### Scenario: No prohibition matches — field is null

- **GIVEN** a file whose detected entities match no `publicationProhibition` rule
- **WHEN** the extract endpoint returns the entity list
- **THEN** every entry has `prohibitionMatch: null`

#### Scenario: High-confidence prohibition match is flagged

- **GIVEN** a detected entity at confidence 0.96 matching prohibition rule `R-X` whose `primaryName` is "Beschermde Getuige A"
- **WHEN** the extract endpoint returns the entity
- **THEN** the entry's `prohibitionMatch` is `{ruleId: "R-X", ruleName: "Beschermde Getuige A", highConfidence: true}`

#### Scenario: Low-confidence prohibition match is flagged with highConfidence false

- **GIVEN** a detected entity at confidence 0.62 matching prohibition rule `R-Y`
- **AND** the configured high-confidence threshold is 0.85
- **WHEN** the extract endpoint returns the entity
- **THEN** the entry's `prohibitionMatch.highConfidence` is `false`

#### Scenario: Same threshold is applied at extract and at the gate

- **GIVEN** the threshold is configured at 0.85
- **AND** an entity is detected at confidence 0.85 exactly
- **WHEN** the extract endpoint returns the entity
- **THEN** `highConfidence: true` (the threshold is inclusive — ≥ 0.85)
- **AND** the gate (per `anonymisation-prohibition-gate`) also treats this match as high-confidence

### Requirement: The change MUST be additive and non-breaking for existing consumers

Pre-change clients that don't send `bases` and don't read `prohibitionMatch` MUST continue to work without modification. No existing field is removed, renamed, or repurposed.

#### Scenario: Pre-change client continues to work

- **GIVEN** a pre-change client constructing payloads without `bases` and reading responses without `prohibitionMatch`
- **WHEN** the client sends an extract request followed by an anonymise request
- **THEN** both succeed with behaviour identical to before this change
- **AND** the response shape is a strict superset of the pre-change shape (new fields added, none removed)

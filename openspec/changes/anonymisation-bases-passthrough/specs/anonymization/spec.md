---
status: draft
---

# Anonymization — Delta for Grondslagen Pass-Through and Prohibition Flag

This delta extends the existing `anonymization` capability so the per-document anonymise endpoint can carry per-entity legal bases (grondslagen) through to OpenRegister, and so the extract endpoint surfaces prohibition matches in its response. No existing requirement is changed in behaviour; both additions are non-breaking and additive.

## ADDED Requirements

### Requirement: The anonymise endpoint MUST accept optional `bases[]` per entity in the request payload

The endpoint payload's `entities[]` array MUST accept an optional `bases` field on each entry — either absent, `null`, or an array of strings (UUIDs referencing `base` schema objects in DocuDesk's `dossier` register, per the in-flight `add-dossier-schema` change). When present, the field MUST be forwarded verbatim to OpenRegister's anonymise endpoint (which persists it on `EntityRelation` and strips it before forwarding to OpenAnonymiser, per the paired `entity-relation-grondslagen` change).

DocuDesk MUST NOT validate that the UUIDs resolve. DocuDesk MUST NOT persist `bases` locally (single source of truth: the `EntityRelation` row).

#### Scenario: Anonymise request without bases preserves today's behaviour

- **GIVEN** an anonymise request payload with entities that have no `bases` field
- **WHEN** DocuDesk's controller processes it
- **THEN** the request is forwarded to OpenRegister with no `bases` field on any entry
- **AND** behaviour matches the pre-change `anonymization` capability exactly

#### Scenario: Anonymise request with bases is forwarded verbatim to OpenRegister

- **GIVEN** a payload with `entities: [{entityId: 42, text: "Jan Janssen", bases: ["uuid-base-a"]}]`
- **WHEN** DocuDesk's controller processes it
- **THEN** the request body forwarded to OpenRegister contains the same `bases` field on the same entry
- **AND** DocuDesk does NOT inspect or validate the UUID

#### Scenario: Empty bases array is forwarded as empty array

- **GIVEN** a payload entry with `bases: []`
- **WHEN** DocuDesk forwards the request
- **THEN** the forwarded body contains `bases: []` on that entry (not omitted, not null)

#### Scenario: Mixed payload — some entries with bases, some without

- **GIVEN** a payload with three entries, one of which has `bases` populated
- **WHEN** DocuDesk forwards the request
- **THEN** the forwarded body has the `bases` field on the one entry that supplied it
- **AND** the other two entries have no `bases` field (or null, depending on JSON encoder defaults)

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

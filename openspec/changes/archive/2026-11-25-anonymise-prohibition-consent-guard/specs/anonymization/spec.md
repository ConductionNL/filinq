---
status: draft
---

# Anonymization — Delta for Prohibition Guard and Standing-Consent Auto-Skip

Extends the `anonymization` capability so the generic anonymise flow enforces `publicationProhibition` rules and honours `standingPublicationConsent` rules, using a compute-at-guard design (no persisted Filinq flag on OpenRegister's `EntityRelation`). The matcher is the existing `PolicyMatchService`. The prohibition guard runs at the moment an operator records a skip decision — per occurrence — not deferred to anonymise time. This flow is read-only with respect to the consent register: it MUST NOT create or modify `publicationConsent` records and MUST NOT invoke the publication-clearance workflow.

## ADDED Requirements

### Requirement: Standing-consent matches MUST be auto-skipped at analysis

When entities are detected for a file (extract), each detected entity MUST be matched against the policy layer via `PolicyMatchService` (prohibition takes precedence over standing consent). For a detected entity whose winning match is a `standingPublicationConsent` rule, the analysis MUST set `skip_anonymization = true` on that entity's `EntityRelation` (via OpenRegister's `updateDecisionMetadata`). The operator MAY subsequently re-include the entity to anonymise it anyway.

A detected entity whose winning match is a prohibition MUST NOT be auto-skipped.

#### Scenario: Standing-consent entity is skipped on load

- **GIVEN** an active `standingPublicationConsent` rule matching "Woordvoerder Jansen"
- **AND** a document containing "Woordvoerder Jansen" is analysed
- **WHEN** extraction completes
- **THEN** the matching `EntityRelation` has `skip_anonymization = true`
- **AND** the review UI shows the entity pre-skipped

#### Scenario: Prohibition precedence over standing consent

- **GIVEN** an entity matches both a `publicationProhibition` rule and a `standingPublicationConsent` rule
- **WHEN** extraction completes
- **THEN** the entity is treated as a prohibition (NOT auto-skipped)

#### Scenario: No standing consents configured — no auto-skip

- **GIVEN** zero active `standingPublicationConsent` records
- **WHEN** extraction completes
- **THEN** no `EntityRelation` is auto-skipped by this flow

### Requirement: The extract response MUST include a read-only `prohibitionMatch` per detected entity

Each detected entity in the extract response MUST carry a `prohibitionMatch` field: either `null` (no prohibition rule matches), or `{ ruleId, ruleName, highConfidence }` where `ruleId` is the rule UUID, `ruleName` is the rule's `primaryName`, and `highConfidence` is `true` when the entity's confidence ≥ the configured threshold. Computing this field MUST NOT modify any state. The review UI uses it to render the entity's lock state without waiting for a skip attempt.

#### Scenario: No prohibition match — field is null

- **GIVEN** a detected entity matching no prohibition rule
- **WHEN** the extract response is built
- **THEN** its `prohibitionMatch` is `null`

#### Scenario: High-confidence prohibition match is flagged

- **GIVEN** a detected entity at confidence 0.96 matching prohibition rule `R-X` whose `primaryName` is "Beschermde Getuige A"
- **AND** the threshold is 0.85
- **WHEN** the extract response is built
- **THEN** its `prohibitionMatch` is `{ruleId: "R-X", ruleName: "Beschermde Getuige A", highConfidence: true}`

#### Scenario: Sub-threshold prohibition match sets highConfidence false

- **GIVEN** a detected entity at confidence 0.62 matching a prohibition rule
- **AND** the threshold is 0.85
- **WHEN** the extract response is built
- **THEN** its `prohibitionMatch.highConfidence` is `false`

### Requirement: The skip decision for a prohibited entity MUST be guarded at decision time, per occurrence

Filinq MUST expose a per-relation skip-decision endpoint (`PATCH /apps/filinq/api/anonymization/relations/{id}`) that the review UI calls to record a skip/include decision, in place of PATCHing OpenRegister's `/api/entity-relations/{id}` directly. Setting `skipAnonymization = true` on a relation whose entity matches an active `publicationProhibition` rule is guarded:

- confidence ≥ threshold → the decision MUST be rejected with **HTTP 422** (absolute; `force` does NOT release it);
- confidence < threshold → the decision MUST be rejected with **HTTP 422** unless the request sets `force = true`.

Including an entity (setting `skipAnonymization = false`, or a non-skip decision such as setting `bases`) MUST always be allowed. When the decision is allowed, the endpoint MUST forward it to OpenRegister via `EntityRelationMapper::updateDecisionMetadata` (so OR's audit-trail records the flip and its actor). When rejected, no OpenRegister write MUST occur.

The guard evaluates a single occurrence (one relation), so the operator gets the error at the moment they attempt the skip — not at anonymise time. The threshold is app config `filinq.prohibition.high_confidence_threshold` (default `0.85`), read at request time; the same threshold governs `highConfidence` in the extract response.

#### Scenario: Skipping a non-prohibited entity is allowed

- **GIVEN** a relation whose entity matches no prohibition rule
- **WHEN** a skip decision (`skipAnonymization = true`) is sent to the endpoint
- **THEN** the endpoint forwards it to OpenRegister and returns success

#### Scenario: Including a prohibited entity is always allowed

- **GIVEN** a relation whose entity matches a prohibition rule (any confidence)
- **WHEN** an include decision (`skipAnonymization = false`) is sent
- **THEN** the endpoint forwards it to OpenRegister and returns success

#### Scenario: Skipping a high-confidence prohibited entity — 422, force does not help

- **GIVEN** a relation whose entity "Beschermde Getuige A" is detected at confidence 0.97 matching prohibition rule `R-X`
- **AND** the request sets `force = true`
- **WHEN** a skip decision is sent
- **THEN** the response is HTTP 422 marking the entity absolute
- **AND** no OpenRegister write occurs

#### Scenario: Skipping a sub-threshold prohibited entity without force — 422

- **GIVEN** a relation whose entity is detected at confidence 0.62 matching prohibition rule `R-Y`
- **AND** the request does NOT set `force`
- **WHEN** a skip decision is sent
- **THEN** the response is HTTP 422 marking the entity releasable via `force`
- **AND** no OpenRegister write occurs

#### Scenario: Skipping a sub-threshold prohibited entity with force — allowed

- **GIVEN** the same sub-threshold match
- **AND** the request sets `force = true`
- **WHEN** a skip decision is sent
- **THEN** the endpoint forwards `skipAnonymization = true` to OpenRegister and returns success

#### Scenario: Configurable threshold at 0.90 reclassifies a 0.87 match

- **GIVEN** `filinq.prohibition.high_confidence_threshold = 0.90`
- **AND** a relation whose entity matches a prohibition at confidence 0.87, with `force = true`
- **WHEN** a skip decision is sent
- **THEN** the match is treated as sub-threshold and released by `force` (skip forwarded, no 422)

### Requirement: The skip-endpoint 422 body MUST identify the blocked occurrence by canonical name and releasability

The 422 body from the skip endpoint MUST be JSON of shape:

```json
{
  "error": "<localised string>",
  "threshold": <float>,
  "prohibitionMatch": {
    "entityId": <int>,
    "entityName": "<canonical name from the OpenRegister Entity record>",
    "ruleId": "<uuid>",
    "ruleName": "<primaryName of the prohibition rule>",
    "confidence": <float>,
    "absolute": <bool>
  }
}
```

`entityName` MUST be the OpenRegister `Entity` record's canonical name, NOT the literal detected text. `threshold` is the high-confidence threshold in effect. `absolute` is `true` when `confidence >= threshold` (not releasable by `force`) and `false` otherwise. The frontend uses `absolute` to decide whether to offer the `force` option — offered only when `absolute: false`. Application logs for a 422 MUST use `ruleId`, `entityId`, and the relation/file id, and MUST NOT log the literal detected text.

#### Scenario: Body uses canonical entity name and marks the tier

- **GIVEN** literal text "P. Jansen" (confidence 0.91, canonical Entity name "Pieter Jansen") matching rule `R-X` (`primaryName` "Politiemedewerker undercover (Jansen)"), threshold 0.85
- **WHEN** a skip decision on that relation is rejected
- **THEN** `prohibitionMatch` is `{entityId, entityName: "Pieter Jansen", ruleId: "R-X", ruleName: "Politiemedewerker undercover (Jansen)", confidence: 0.91, absolute: true}`

### Requirement: The anonymise flow MUST keep a defence-in-depth prohibition backstop

OpenRegister's generic `/api/entity-relations/{id}` PATCH remains open, so a caller could bypass the Filinq skip endpoint and skip a prohibited relation directly. The Filinq anonymise flow therefore MUST re-check before redaction: if a relation left un-redacted matches a prohibition at confidence ≥ threshold, the anonymise request MUST fail with HTTP 422 (absolute), regardless of `force`. This is a backstop for the primary decision-time guard, not the main enforcement point, so it only enforces the non-releasable (≥ threshold) tier.

#### Scenario: Direct-OR bypass is caught at anonymise

- **GIVEN** a prohibited relation at confidence 0.97 was skipped by PATCHing OpenRegister directly (bypassing the Filinq skip endpoint)
- **WHEN** a Filinq anonymise request for the file is processed
- **THEN** the response is HTTP 422 and no redaction occurs

### Requirement: The guard MUST NOT create or modify `publicationConsent` records

The guard consults `publicationProhibition` (and, at analysis, `standingPublicationConsent`) records read-only. It MUST NOT create, modify, or query `publicationConsent` records, and MUST NOT invoke the publication-clearance workflow.

#### Scenario: Skip decisions and anonymise both leave the consent register untouched

- **GIVEN** any skip decision or anonymise request (allowed or rejected)
- **WHEN** it completes
- **THEN** zero `publicationConsent` records are created or modified
- **AND** `publicationProhibition` records are not modified

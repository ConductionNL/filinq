---
status: done
---

# anonymisation-prohibition-gate Specification

## Purpose
Enforces publication-prohibition policies during anonymisation by checking every detected entity against active prohibition rules before forwarding a request to OpenRegister. High-confidence prohibited entities must be included in the set to be anonymised or the request is rejected with HTTP 422, while low-confidence matches can be released through acknowledged overrides that record an audit entry and flag the entity to skip redaction. This ensures legally protected entities cannot be left unredacted without an explicit, audited operator decision.
## Requirements
### Requirement: The anonymise endpoint MUST consult the prohibition cache before forwarding the request

When the anonymise endpoint is called, the controller / service MUST resolve every detected entity in the file via the same `PolicyMatchService` cache used by the publication-clearance flow, restricted to the prohibition portion of the cache. The matcher MUST NOT consult standing-consent records (those are publication-clearance-only). The matcher MUST NOT create `publicationConsent` records.

#### Scenario: No prohibitions configured — gate is a no-op

- **GIVEN** an anonymise request with a payload of detected entities
- **AND** zero active `publicationProhibition` records exist in the consent register
- **WHEN** the endpoint processes the request
- **THEN** the gate runs but matches nothing
- **AND** the request is forwarded to OpenRegister unchanged
- **AND** no `publicationConsent` records are created

#### Scenario: Prohibitions exist but no detected entity matches

- **GIVEN** active `publicationProhibition` records exist
- **AND** the file's detected entities match none of them
- **WHEN** the endpoint processes the request
- **THEN** the request is forwarded to OpenRegister unchanged
- **AND** no `publicationConsent` records are created

### Requirement: High-confidence prohibition matches MUST be present in the to-be-anonymised set

For every detected entity in the file with confidence ≥ 0.85 that matches an active `publicationProhibition` rule, the anonymise request payload's `entities[]` MUST include that entity (i.e. it must be in the set the operator chose to anonymise). If any such entity is missing, the endpoint MUST respond with HTTP 422.

The threshold (0.85) MUST be configurable via app config key `filinq.prohibition.high_confidence_threshold` (default 0.85). Reads MUST happen at request time; runtime config changes propagate without restart.

#### Scenario: All high-confidence prohibitions are included — request proceeds

- **GIVEN** a file with two detected entities matching prohibitions, both at confidence ≥ 0.85
- **AND** the anonymise payload includes both
- **WHEN** the endpoint processes the request
- **THEN** the gate passes
- **AND** the request is forwarded to OpenRegister

#### Scenario: A high-confidence prohibition is missing — 422

- **GIVEN** a file with a detected entity "Beschermde Getuige A" (confidence 0.97) matching prohibition rule `R-PROHIBIT-1`
- **AND** the anonymise payload does NOT include this entity
- **WHEN** the endpoint processes the request
- **THEN** the response is HTTP 422
- **AND** the response body contains the missing entity per the response-shape requirement below
- **AND** no request is forwarded to OpenRegister
- **AND** no `EntityRelation` rows are written

#### Scenario: Multiple high-confidence prohibitions missing

- **GIVEN** a file with three detected entities matching prohibitions at high confidence
- **AND** the anonymise payload includes only one of the three
- **WHEN** the endpoint processes the request
- **THEN** the response is HTTP 422
- **AND** the response body lists all two missing entities (not just the first)

#### Scenario: Configurable threshold at 0.90

- **GIVEN** the app config sets `filinq.prohibition.high_confidence_threshold` to 0.90
- **AND** a detected entity matches a prohibition at confidence 0.87
- **AND** the anonymise payload does not include this entity
- **WHEN** the endpoint processes the request
- **THEN** the gate treats the match as low-confidence (below threshold) — no 422
- **AND** the request proceeds (the entity is not anonymised)

### Requirement: 422 response body MUST list missing entities by OpenRegister Entity canonical name

The 422 response body MUST be JSON with shape:

```json
{
  "error": "<localised string>",
  "missingProhibitionMatches": [
    {
      "entityId": <int>,
      "entityName": "<canonical name from OpenRegister Entity record>",
      "ruleId": "<uuid>",
      "ruleName": "<primaryName from publicationProhibition rule>",
      "confidence": <float>
    },
    ...
  ]
}
```

The `entityName` MUST be the canonical name from the OpenRegister `Entity` record, NOT the literal detected text in the document, NOT the prohibition rule's `primaryName`. The `ruleName` MUST be the prohibition rule's `primaryName`, included to help the operator understand WHY the entity is required to be anonymised.

#### Scenario: 422 body uses canonical entity names

- **GIVEN** a file containing the literal text "P. Jansen" (detected at confidence 0.91), where the OpenRegister Entity record's canonical name is "Pieter Jansen", matching prohibition rule `R-PROHIBIT-1` whose `primaryName` is "Politiemedewerker undercover (Jansen)"
- **AND** the anonymise payload does not include this entity
- **WHEN** the endpoint processes the request
- **THEN** the 422 response body's `missingProhibitionMatches[0]` contains:
  - `entityName: "Pieter Jansen"` (canonical from Entity record, NOT "P. Jansen")
  - `ruleName: "Politiemedewerker undercover (Jansen)"` (from rule's primaryName)
  - `confidence: 0.91`
  - `ruleId` matching `R-PROHIBIT-1`

#### Scenario: Application logs use IDs, not literal text

- **GIVEN** the gate fires a 422
- **WHEN** the application log records the event
- **THEN** the log entry contains `ruleId` and `entityId` and the file ID
- **AND** the log entry does NOT contain the literal detected text from the document
- **AND** the log entry MAY contain the canonical entity name (for operator debugging — same name surfaced in the response)

### Requirement: `acknowledgedOverrides` MUST be accepted on every request

The anonymise request payload MUST accept an optional top-level `acknowledgedOverrides` array. Each entry MUST have shape `{ ruleId: string, entityId: int, reason?: string }`. The array MAY be sent on the first request; no special "retry" flag is required. The endpoint MUST treat a request with no `acknowledgedOverrides` field as equivalent to one with an empty array. The `reason` field is optional but recommended.

#### Scenario: Override array is optional

- **GIVEN** a request payload with no `acknowledgedOverrides` field
- **WHEN** the endpoint processes the request
- **THEN** the request is treated as having an empty override array
- **AND** processing continues normally

#### Scenario: Override array sent on first request

- **GIVEN** a request payload that includes `acknowledgedOverrides: [{ruleId: "R-X", entityId: 7, reason: "low confidence false positive"}]`
- **AND** the operator has not previously hit a 422 for this file
- **WHEN** the endpoint processes the request
- **THEN** the override is evaluated normally (per the validation rules below)

### Requirement: Overrides MUST only release low-confidence prohibition matches

An `acknowledgedOverrides` entry MUST validate as follows:

1. The `(ruleId, entityId)` combination MUST correspond to an actual match in the current extraction (the entity is detected in the file AND the rule is in the active prohibition cache AND the rule matches the entity).
2. The match's confidence MUST be < the high-confidence threshold (per the previous requirement; default 0.85). Overrides for matches at confidence ≥ threshold are rejected.

When an override validates, the corresponding prohibition match is treated as "released" — the gate does NOT require this entity to be in the to-be-anonymised set, and the entity is not anonymised (unless the operator chose to anonymise it independently).

#### Scenario: Valid override releases a low-confidence match

- **GIVEN** a detected entity at confidence 0.62 matching prohibition rule `R-X` (corresponding to EntityRelation row `R7`)
- **AND** the anonymise payload omits this entity
- **AND** the payload includes `acknowledgedOverrides: [{ruleId: "R-X", entityId: 7, reason: "false positive — public figure"}]`
- **WHEN** the endpoint processes the request
- **THEN** Filinq MUST persist a side-by-side audit entry capturing `{ruleId: "R-X", entityRelationId: <R7's id>, fileId: <file>, reason: "false positive — public figure", acknowledgedBy: <user UID>, acknowledgedAt: <ISO-8601>}`
- **AND** Filinq MUST call `EntityRelationMapper::updateDecisionMetadata(R7.id, ['skipAnonymization' => true])` via OR DI
- **AND** the gate MUST release the match
- **AND** the request MUST be forwarded to OpenRegister
- **AND** the entity MUST NOT be redacted (OR's anonymise flow honours the skip flag — per OR's `entity-relation-grondslagen` spec)
- **AND** OR's audit-trail MUST record one entry for the skip-flip on R7
- **AND** Filinq's audit store MUST record one entry capturing the override's reason

#### Scenario: Override for high-confidence match is rejected with 422

- **GIVEN** a detected entity at confidence 0.94 matching prohibition rule `R-X`
- **AND** the payload includes `acknowledgedOverrides: [{ruleId: "R-X", entityId: 7}]`
- **WHEN** the endpoint processes the request
- **THEN** the response is HTTP 422
- **AND** the body contains an error citing "override not allowed for high-confidence matches"
- **AND** the body lists `R-X` / entityId 7 in a separate `rejectedOverrides` array (alongside any `missingProhibitionMatches`)

#### Scenario: Override for non-matching combination is silently ignored

- **GIVEN** an `acknowledgedOverrides` entry where `(ruleId, entityId)` does NOT correspond to an active prohibition match
- **WHEN** the endpoint processes the request
- **THEN** the override is silently ignored
- **AND** processing continues normally
- **AND** no error or warning is raised

### Requirement: The gate MUST NOT create `publicationConsent` records

This change is read-only with respect to the consent register. The gate consults `publicationProhibition` records but MUST NOT create, modify, or query `publicationConsent` records. Generic anonymisation flows continue to be outside the publication-clearance workflow.

#### Scenario: Successful anonymise creates no publicationConsent

- **GIVEN** a successful anonymise request that passes the gate
- **WHEN** the request completes
- **THEN** zero `publicationConsent` records are created or modified
- **AND** the `publicationProhibition` records are not modified

#### Scenario: 422 creates no publicationConsent

- **GIVEN** an anonymise request that fails the gate with 422
- **WHEN** the response is returned
- **THEN** zero `publicationConsent` records are created or modified

### Requirement: Acknowledged overrides MUST persist a Filinq-side audit entry AND PATCH OpenRegister with `skipAnonymization=true`

For every `acknowledgedOverrides` entry that validates per the previous Requirement, Filinq's controller MUST:

1. **Persist a Filinq-side audit entry** capturing `{ruleId, entityRelationId, fileId, reason, acknowledgedBy, acknowledgedAt}`. Implementations MUST use a `prohibitionOverrideAudit` schema in `filinq_register.json` for this entry, alongside the existing schemas. (Alternative persistent stores — dedicated audit-log table, structured-logger payload — were considered and rejected: keeping audit on the register surface keeps DD's audit volume queryable via the existing `objects` endpoints and reuses the standard OpenRegister retention/RBAC story. Implementations MAY add an additional sink, but the register-backed entry is the mandated one.) This entry records *why* the operator chose to release a flagged entity from anonymisation — operator-commentary metadata that OpenRegister's audit-trail does not carry (OR's PATCH whitelist is decision-only).
2. **PATCH OpenRegister's matching `EntityRelation` row** with `{skipAnonymization: true}` via `EntityRelationMapper::updateDecisionMetadata` (via OR's DI lookup). The Filinq-side audit entry MUST be written BEFORE the corresponding OR PATCH so a failure of the OR call doesn't leave the override unrecorded on the DD side. Each override is processed sequentially (per-relation audit + PATCH pair). Best-effort semantics: if one OR PATCH fails, Filinq MUST stop processing further overrides in the same request and respond with HTTP 500. Already-committed DD audit entries and already-applied OR PATCHes from earlier overrides in the same request are NOT rolled back — they remain on disk as a per-relation audit trail of operator intent. The skip flag is idempotent (PATCH-ing the same relation again with `skipAnonymization: true` is a semantic no-op on OR's side), so a retry replays cleanly.
3. **Proceed with the anonymise call** to OpenRegister. OR's `markAsAnonymized` will already exclude the skip-flagged row from the redaction set — no further Filinq work is needed for execution.

The two persistence side-effects (DD audit + OR PATCH) MUST happen for every validated override. They MUST happen on the same request that carried the override; there is no deferred or asynchronous commit. There is NO request-level transaction — atomicity is per-override (audit then PATCH for ONE relation), not all-overrides-or-nothing.

#### Scenario: Override acknowledgement writes both audit and skip flag

- **GIVEN** an anonymise request with `acknowledgedOverrides: [{ruleId: "R-X", entityId: 7, reason: "low-confidence false positive"}]` releasing a low-confidence match
- **WHEN** Filinq's controller processes the request
- **THEN** a new Filinq audit entry MUST exist with `{ruleId: "R-X", entityRelationId: <R7.id>, fileId: <file>, reason: "low-confidence false positive", acknowledgedBy: <UID>, acknowledgedAt: <ISO-8601>}`
- **AND** OR's row R7 MUST have `skipAnonymization = true`
- **AND** OR's audit-trail MUST record one entry for the skip-flip on R7 with the acting user UID
- **AND** the anonymise call to OR MUST succeed
- **AND** the file MUST be redacted with R7 not appearing in the redacted output

#### Scenario: OR PATCH fails during override processing

- **GIVEN** an anonymise request with three validated overrides for relations R7, R8, R9 (in that order)
- **AND** OR's `updateDecisionMetadata` succeeds for R7
- **AND** OR's `updateDecisionMetadata` raises an exception for R8 (e.g. relation no longer exists)
- **WHEN** Filinq processes the request
- **THEN** the response MUST be HTTP 500
- **AND** R7's Filinq audit entry MUST be persisted (already committed before the failure)
- **AND** R7's OR `skipAnonymization` MUST be `true` (already committed before the failure)
- **AND** R8's Filinq audit entry MUST be persisted (written BEFORE its OR PATCH per the per-override audit-first ordering — see Requirement above)
- **AND** R9 MUST NOT have been PATCHed (sequential processing stops at the first failure)
- **AND** R9's Filinq audit entry MUST NOT exist
- **AND** the anonymise call MUST NOT have been forwarded to OpenRegister

#### Scenario: Multiple overrides commit sequentially (happy path)

- **GIVEN** an anonymise request with two validated overrides
- **AND** OR's `updateDecisionMetadata` succeeds for both
- **WHEN** the request processes
- **THEN** two Filinq audit entries MUST be persisted, in submission order
- **AND** two OR PATCH operations MUST have committed, in submission order
- **AND** the anonymise call MUST be issued with both relations excluded from redaction

#### Scenario: Retry after partial failure is idempotent

- **GIVEN** an earlier anonymise request committed the override for R7 (skip=true) and then failed at R8
- **AND** the operator retries the same request with the same overrides for R7, R8, R9
- **AND** the underlying R8 issue has been resolved on the next attempt
- **WHEN** Filinq processes the retry
- **THEN** R7's repeat PATCH MUST be a semantic no-op on OR (skip is already true; no duplicate OR audit entry per OR's `entity-relation-grondslagen` spec)
- **AND** R7's Filinq audit entry from the previous attempt MUST remain, AND a second DD audit entry for the same retry MUST also be written (each acknowledged override on a request gets its own audit row — the retry is a separate operator intent event)
- **AND** R8 and R9 MUST succeed and produce their DD audit entries + OR PATCHes
- **AND** the anonymise call MUST be forwarded with all three relations excluded from redaction

### Requirement: Prohibition matcher reuses the `PolicyMatchService` from `entity-publication-policies`

This capability MUST NOT introduce a parallel matcher implementation. The matching logic is the one specced in `entity-publication-policies` (`PolicyMatchService` with `matchProhibition()` or equivalent). If `PolicyMatchService` does not yet exist as code at apply time, this change's apply phase scaffolds it from spec; if `entity-publication-policies` apply lands first, this change consumes the existing implementation.

#### Scenario: Matcher implementation is shared

- **GIVEN** both this change and `entity-publication-policies` are applied (in either order)
- **WHEN** code is inspected
- **THEN** there is exactly one `PolicyMatchService` (or equivalent) in the codebase
- **AND** both this gate AND the publication-clearance flow consult it


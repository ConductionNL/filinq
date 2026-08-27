# Anonymisation Prohibition Gate

## Overview

The prohibition gate is a server-side safety check on the anonymise endpoint
that prevents `publicationProhibition`-listed entities from slipping through the
anonymisation pipeline unredacted.

The gate runs **after** the operator has selected which entities to anonymise
and **before** the request is forwarded to OpenRegister. It consults the
`PolicyMatchService` prohibition cache but does **not** create
`publicationConsent` records and does **not** participate in the
publication-clearance workflow — it is read-only safety, layered on top of
generic anonymisation.

## How the Gate Works

1. The anonymise endpoint receives the list of entities the operator selected
   to redact (`entities[]`).
2. The gate loads every detected entity in the file via
   `EntityRelationMapper::findEntitiesForFile`.
3. For each detected entity, it calls `PolicyMatchService::matchProhibition` to
   check against active `publicationProhibition` rules.
4. For each **high-confidence** match (confidence ≥ threshold, default 0.85):
   - If the entity **is** in `entities[]` → gate passes for this entity.
   - If the entity **is not** in `entities[]` → gate adds it to
     `missingProhibitionMatches` and will return HTTP 422.
5. For **low-confidence** matches (confidence < threshold):
   - The gate does not block the call by default.
   - The operator may explicitly release such a match via
     `acknowledgedOverrides[]` (see below).

## Configuration

| Config key | Default | Description |
|---|---|---|
| `filinq.prohibition.high_confidence_threshold` | `0.85` | Inclusive threshold above which a prohibition match is treated as high-confidence and must be present in `entities[]`. Reads happen at request time; no restart required. |

Set the threshold via Nextcloud's app config:

```bash
occ config:app:set filinq prohibition.high_confidence_threshold --value 0.90
```

## HTTP 422 Response Body

When the gate fires, the endpoint responds with HTTP 422 and a JSON body:

```json
{
  "error": "<localised message>",
  "missingProhibitionMatches": [
    {
      "entityId": 42,
      "entityName": "Pieter Jansen",
      "ruleId": "some-rule-uuid",
      "ruleName": "Politiemedewerker undercover (Jansen)",
      "confidence": 0.91
    }
  ],
  "rejectedOverrides": []
}
```

- `entityName` — canonical name from the OpenRegister `Entity` record, **not**
  the literal detected text in the document and **not** the rule's `primaryName`.
- `ruleName` — the `primaryName` from the `publicationProhibition` rule,
  included so the operator understands **why** the entity is required to be
  anonymised.
- `confidence` — the detection confidence at the time of the gate evaluation.

## Override Mechanism (`acknowledgedOverrides`)

The operator can release **low-confidence** prohibition matches by including an
`acknowledgedOverrides[]` array in the request payload. This array may be sent
on the **first** request — no special retry flag is needed.

### Request shape

```json
{
  "entities": [ ... ],
  "acknowledgedOverrides": [
    {
      "ruleId": "some-rule-uuid",
      "entityId": 7,
      "reason": "Public figure — no protection required"
    }
  ]
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `ruleId` | string | yes | UUID of the `publicationProhibition` rule to override. |
| `entityId` | int | yes | ID of the OR Entity record. |
| `reason` | string | no | Operator-provided rationale (recommended for audit). |

### Validation rules

| Case | Outcome |
|---|---|
| `(ruleId, entityId)` does not match any active prohibition match | Silently ignored. |
| Match confidence < threshold | Override is **released** — entity not required in `entities[]`. |
| Match confidence ≥ threshold | Override **rejected** with 422; listed in `rejectedOverrides`. |

### Side-effects of a valid override

For every released override:

1. A **Filinq-side audit entry** is written to the
   `prohibitionOverrideAudit` schema in the consent register, capturing
   `{ruleId, entityRelationId, fileId, reason, acknowledgedBy, acknowledgedAt}`.
2. OpenRegister's matching `EntityRelation` row is **PATCHed** with
   `{skipAnonymization: true}` so OR's anonymise flow honours the skip flag.

Both steps happen synchronously in the same request. The Filinq audit entry
is always written **before** the OR PATCH. If the OR PATCH fails, the request
responds with HTTP 500; already-committed audit entries are not rolled back.

## Existing Callers

Existing callers with no `publicationProhibition` records configured see **no
behaviour change**. The gate matches nothing and the request proceeds as before.

## CHANGELOG

### Added

- Prohibition gate on the anonymise endpoint: high-confidence
  `publicationProhibition` matches must be present in `entities[]` or the call
  is rejected with HTTP 422.
- `acknowledgedOverrides[]` request field for releasing low-confidence matches.
- `prohibitionOverrideAudit` schema in the `consent` register (10-year
  retention).

### Behavior changes

- The anonymise endpoint (`POST /apps/filinq/api/anonymize/{fileId}`) may now
  respond HTTP 422 when prohibition-listed entities are missing from the
  submitted `entities[]` set. **Existing callers with no prohibition records
  configured are unaffected.**

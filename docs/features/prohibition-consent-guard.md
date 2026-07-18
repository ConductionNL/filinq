---
id: prohibition-consent-guard
title: Prohibition Guard & Standing-Consent Auto-Skip
sidebar_label: Prohibition Guard
sidebar_position: 9
description: Enforces publication prohibitions and honours standing consents in the anonymise flow — prohibited entities cannot be skipped, standing-consent entities are skipped automatically
keywords:
  - anonymization
  - prohibition
  - standing consent
  - WOO
  - GDPR
  - policy
---

# Prohibition Guard & Standing-Consent Auto-Skip

## Status: Proposed

Wires the `publicationProhibition` and `standingPublicationConsent` policies (from `entity-publication-policies`) into the generic anonymise/redaction flow. Previously these policies were enforced only in the WOO publication-consent workflow; the anonymise path was policy-unaware, so a prohibited entity could be skipped and silently left un-redacted, and standing-consent entities had to be re-decided on every document.

Design is intentionally lightweight — **compute-at-guard**, no persisted DocuDesk flag on OpenRegister's `EntityRelation` and no OpenRegister schema change. It supersedes the prohibition-gate portion of `anonimisation-grondslagen-and-prohibition-gate`.

## Behaviour

### Standing consent → auto-skip at analysis

During extraction, each detected entity is matched via `PolicyMatchService` (prohibition takes precedence over standing consent). An entity whose winning match is a `standingPublicationConsent` rule has `skip_anonymization = true` set on its `EntityRelation` (via OpenRegister's `updateDecisionMetadata`). The operator may still re-include it to anonymise it anyway. Prohibition matches are **not** auto-skipped.

### Prohibition → guarded skip, per occurrence

The review UI records skip/include decisions through a DocuDesk endpoint that runs the guard at decision time (per relation), so the operator is stopped at the moment they try to skip — not deferred to anonymise time. Skipping a prohibition-matched entity is refused based on the detection confidence:

- **confidence ≥ threshold** → **absolute**: the skip is rejected and `force` cannot release it (court-order witnesses, undercover officers, minor-protection entries must never leak).
- **confidence < threshold** → the skip is rejected **unless** the request sets `force`.

Including an entity (or any non-skip decision such as setting bases) is always allowed. Allowed decisions are forwarded to OpenRegister. The review UI hard-locks the toggle for absolute matches and shows a lock indicator with the rule name; a sub-threshold block opens a dialog that offers a `force` override.

### Backstop at anonymise time

OpenRegister's generic relation PATCH endpoint stays open, so a caller could skip a prohibited relation by bypassing the DocuDesk endpoint. As defence-in-depth, the anonymise flow re-checks before redaction: if a relation left un-redacted matches a prohibition at confidence ≥ threshold, the request fails with HTTP 422 regardless of `force`.

### Read-only with respect to consent

The guard consults `publicationProhibition` (and, at analysis, `standingPublicationConsent`) records read-only. It never creates or modifies `publicationConsent` records and never invokes the publication-clearance workflow. Accountability for a forced sub-threshold skip comes from OpenRegister's `EntityRelation` audit-trail, which records the actor and timestamp of the `skip_anonymization` flip.

## Configuration

| App config key | Default | Meaning |
| --- | --- | --- |
| `docudesk.prohibition.high_confidence_threshold` | `0.85` | Confidence at or above which a prohibition match is **absolute** (not releasable by `force`). Read at request time, so runtime changes take effect without a restart. The same threshold governs the `highConfidence` flag on the extract response. |

## API

### Extract response

Each detected entity gains a read-only `prohibitionMatch`:

```json
"prohibitionMatch": null
```

or

```json
"prohibitionMatch": { "ruleId": "<uuid>", "ruleName": "<primaryName>", "highConfidence": true }
```

`highConfidence` is `true` when the entity's confidence is at or above the threshold. Standing-consent entities are returned with `skipAnonymization: true`.

### Skip-decision endpoint

`PATCH /apps/docudesk/api/anonymization/relations/{id}`

Request body:

```json
{ "skipAnonymization": true, "bases": ["<uuid>"], "force": false }
```

- `skipAnonymization` (bool) — the requested decision. `bases` and `force` are optional.
- Allowed → `200 { "status": "ok", "skipAnonymization": <bool> }`, forwarded to OpenRegister.
- Blocked → `422`:

```json
{
  "error": "<message>",
  "threshold": 0.85,
  "prohibitionMatch": {
    "entityId": 42,
    "entityName": "<canonical entity name>",
    "ruleId": "<uuid>",
    "ruleName": "<primaryName>",
    "confidence": 0.91,
    "absolute": true
  }
}
```

The frontend offers a `force` retry only when `absolute` is `false`.

### Anonymise endpoint

`POST /apps/docudesk/api/anonymization/anonymize/{fileId}` may now return `422` with `{ "error": "...", "missingProhibitionMatches": [ ... ] }` when the backstop finds an absolute prohibition entity left un-redacted. Callers with no `publicationProhibition` records configured see no behaviour change.

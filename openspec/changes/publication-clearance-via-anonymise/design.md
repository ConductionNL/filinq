## Context

`ConsentService::createConsentRequest()` exists and is correct — given `{documentId, entityType, entityText, register, schema, extra}` it persists a publicationConsent record with `consentStatus: "pending"`, computes `objectionDeadline` from configuration, and (per `entity-publication-policies`) consults `PolicyMatchService` to apply pre-emption. The canonical `consent-management` spec (REQ-CONS-07: CONS-048 and CONS-050) flagged that this method has no automated caller — invocation is programmatic via direct `POST /api/consents` only. The publication-clearance stack is plumbing without a tap.

Building a separate "publication-prep" entry point with its own controller, session state, and stages (start → notify → wait → decide → publish) is a tempting design but over-engineered for the actual operator decision: per detected entity, what should we do? That's already what extract → review → anonymise expresses. We just need the anonymise endpoint to accept "publish unredacted under this basis" decisions alongside "anonymise these" decisions, and to call `createConsentRequest` for the unredacted ones.

This change is an additive extension of the existing endpoint plus a small idempotency upgrade to `createConsentRequest`. No new endpoints, no new state, no session lifecycle. Statelessness aligns with the rest of the anonymisation pipeline and reuses the operator's existing review-then-submit pattern.

The change interacts with three already-specced or in-flight changes:

- `entity-relation-grondslagen` (OR) — the per-entity anonymisation grondslag (`bases[]`) lands on `EntityRelation` rows for entities in `entities[]`. Independent of the new `unredactedEntities[]` work.
- `anonymisation-grondslagen-and-prohibition-gate` (DocuDesk) — provides `PolicyMatchService` (or scaffolds it). The prohibition gate on `entities[]` runs before this change's logic.
- `entity-publication-policies` (DocuDesk) — defines the policy-pre-emption logic that fires inside `createConsentRequest`. Standing-consent matches resolve to `consent_given`; prohibition matches CAN'T appear in `unredactedEntities[]` (this change rejects them at the gate).

## Goals / Non-Goals

**Goals:**

- Extend the per-document and batch anonymise endpoints with `unredactedEntities[]`. For each entry, create or update a publicationConsent record via `createConsentRequest`.
- Make `createConsentRequest` idempotent on `(documentId, entityKey)`. Re-submits update the operator-set fields, preserve workflow state.
- Reject prohibited entities placed in `unredactedEntities[]` with HTTP 422 — surfacing the privacy bug to the operator instead of silently anonymising or unmasking.
- Serialise multiple bases into the existing `legalBasis` (single string) + `notes` (markdown) fields using a sentinel-tagged region for clean re-submits.
- Stub notification dispatch in v1 — record `notificationStatus: pending` and the objection deadline; do not actually send email or postal notification.

**Non-Goals:**

- Build a separate publication-prep controller / endpoint. Out of scope.
- Real notification dispatch (SMTP, postal address handling, retry logic). Separate change.
- Multi-document publication-prep in a single call. Separate change.
- A structured publication-grounds vocabulary (Woo Art. 3 / AVG Art. 6 as `base` records). Separate change.
- Auto-resolution of contact information from external directories. Separate change.
- Final publication action (output assembly, push to overheid.nl). Separate change — large scope.

## Decisions

### D1. Stateless extension, not a new endpoint

Adding an `unredactedEntities[]` field to the existing anonymise endpoint and routing the per-entity decisions through `createConsentRequest` collapses what would otherwise be a multi-stage workflow (start prep → review → submit decisions → ...) into the existing review-then-submit pattern. No session state, no preparation lifecycle, no separate controller.

**Rationale:**

- Operators already expect the anonymise call to express their per-entity intent. Splitting "anonymise" and "decide-on-publication" into separate endpoints would force them to make the same decision twice in two different shapes.
- Statelessness simplifies error semantics — the call either succeeds atomically (entities anonymised, publicationConsent records created) or fails with no partial side-effects to clean up.
- A separate endpoint would replicate plumbing (auth, document loading, file resolution) that the anonymise endpoint already has.

**Trade-off:** the anonymise endpoint becomes more complex (two responsibilities: redact-this and clear-that). Mitigated by keeping the responsibilities cleanly separable in the controller / service layer — `unredactedEntities[]` processing happens after the existing anonymise pipeline completes.

### D2. Idempotency on `(documentId, entityKey)` for createConsentRequest

`createConsentRequest` is upgraded to:

1. Look up an existing publicationConsent record matching `(documentId = $documentId, entityKey = $entityKey)` (or `entityText` if `entityKey` isn't supplied).
2. If found:
   - Update operator-controlled fields: `legalBasis`, `notes`, `contactEmail`, `contactAddress`, `entityType` (rare update — only if the detector's classification changed).
   - Preserve workflow state: `notificationStatus`, `notificationSentAt`, `objectionDeadline`, `objectionReceivedAt`, `objectionReason`, `consentStatus`, `policyMatch`, `publicationDecision`.
   - Re-run `PolicyMatchService` and update `policyMatch` only if the existing `policyMatch` is null and a match now exists (don't downgrade an existing match — that's a separate rule-mutation event handled by `entity-publication-policies` retroactive logic).
3. If not found: create a new record (existing behaviour).

**Why `entityKey` matches by, not `entityText`?** `entityKey` is the stable per-entity identifier. Two records for "Jan Janssen" in different roles (different keys) should be independent. If `entityKey` is null on legacy records, fall back to `entityText` matching.

**Rationale:**

- Operators re-submitting the same anonymise call (same set of unredacted entities) shouldn't accumulate duplicate records. Without idempotency: each re-submit would spawn a parallel record, polluting the consent register.
- Preserving workflow state means the WOO timer keeps running across re-submits — operators don't accidentally restart the 28-day clock by re-issuing the call.
- Updating operator-controlled fields keeps the system responsive to corrections (operator changes the basis, fixes a contact email).

**Trade-off:** the lookup adds one query per `unredactedEntities[]` entry. Negligible.

### D3. Hard 422 on prohibition match in `unredactedEntities[]`

Per Q1' from the exploration: prohibited entities cannot be in `unredactedEntities[]`. The check runs as part of input validation:

```
   for each entry in unredactedEntities[]:
     match = PolicyMatchService::matchProhibition(entry.entityText, entry.entityType, ...)
     if match: collect into rejection list
   if rejection list non-empty:
     return 422 with body listing rejected entries
```

The check fires regardless of confidence threshold (unlike the gate on `entities[]` which uses 0.85). The operator made an explicit decision to publish a prohibited person — there's no ambiguity to resolve via low-confidence override.

**Response body shape on 422:**

```json
{
  "error": "Prohibited entities cannot be published unredacted.",
  "rejectedUnredacted": [
    {
      "entityId": 5,
      "entityText": "Beschermde Getuige A",
      "ruleId": "<uuid>",
      "ruleName": "<rule.primaryName>"
    }
  ],
  "fallback": "Move these entities into entities[] (anonymise) and re-submit."
}
```

**Rationale:**

- Privacy-fail-loud principle. The whole point of `publicationProhibition` is "this person must not appear unredacted in publications" — silently overriding the operator's mistake or auto-anonymising is misleading.
- Symmetric with the existing prohibition gate on `entities[]` (where missing-from-anonymise-set fails 422). Same UX pattern.
- 422 is the right code: the input is structurally valid but semantically rejected.

### D4. Multiple-bases via sentinel-tagged notes serialisation

`publicationBases: string[]` from the request becomes:

```php
$record->setLegalBasis($publicationBases[0] ?? null);
if (count($publicationBases) > 1) {
    $record->setNotes($this->serialiseAdditionalBases($existingNotes, array_slice($publicationBases, 1)));
}
```

`serialiseAdditionalBases` produces (Markdown):

```
<existing operator-authored notes content, if any>

<!-- docudesk:additional-publication-bases:begin -->
**Aanvullende publicatiegrondslagen:**
- <basis 2>
- <basis 3>
<!-- docudesk:additional-publication-bases:end -->
```

On re-submit, the helper:

1. Strips the existing bracketed region (begin → end) from current `notes`.
2. Appends the new bracketed region with the current additional bases.

If `publicationBases` shrinks to one element, the bracketed region is removed entirely (notes return to operator-authored content only).

**Rationale:**

- `publicationConsent.legalBasis` is `string` (max 500). Single field. Multiple bases need a home.
- A new schema field (`legalBases[]`) would require a schema migration and ripple changes through every consumer of publicationConsent. Out of proportion to the rare multi-basis case.
- Sentinel-tagged regions in markdown are a well-understood pattern (used by config managers, doc generators, etc.) — operators see exactly which content is auto-managed and don't accidentally overwrite their own notes.

**Edge cases:**

- Operator manually edits the bracketed region: their changes are overwritten on next re-submit. Document this as a known caveat — manual edits inside the brackets are not preserved.
- Operator removes the closing tag: helper falls back to "preserve all current notes; append new region at the end". A linter at apply time can detect malformed sentinels.

### D5. Notification dispatch stays stubbed in v1

Per Q2' answer (stub): created publicationConsent records with `consentStatus: pending` carry `notificationStatus: pending` and a computed `objectionDeadline`. The system does NOT send any email or postal notification automatically.

**What the operator sees:**

- The publicationConsent record exists with `notificationStatus: pending`.
- `objectionDeadline` is set (28 days from creation by default; configurable per CONS-031).
- The 28-day clock starts ticking — that part isn't stubbed; only the dispatch is.
- Operators advance status manually via `PUT /api/consents/{id}` to `notificationStatus: sent` once they've actually sent the notification by their out-of-band means (printed letter, email from a tracked address, whatever).

**Rationale:**

- Real dispatch involves: SMTP integration with the tenant's mail server, postal address handling (printing? digital→postal services?), template rendering for notification content, dispatch retry, delivery confirmation, bounce handling. Each is a real piece of work; bundling them with this change would 5x the scope.
- The publication-clearance pipeline is operationally useful even with stub dispatch: operators send notifications via their existing channels (email signed by a privacy officer, printed mail, etc.), then mark the record's status. The structured record + the WOO timer + the operator's eventual `publicationDecision` are what compliance reporting cares about; how the notification was sent is a (recorded) detail, not a structural requirement.

**Future work:** a separate change `publicationconsent-notification-dispatch` that adds the real email + postal stack. This change's stub doesn't need to be replaced when that lands; it just becomes "do real dispatch instead of stubbing".

### D6. `createdConsents[]` aggregation in the response

The anonymise response gains an optional `createdConsents[]` array. For each entry in `unredactedEntities[]` that resulted in a successful create or update:

```json
{
  "anonymizedFileId": ...,
  "anonymizedFilePath": ...,
  "createdConsents": [
    {
      "consentId": "<uuid>",
      "entityId": 5,
      "entityText": "Burgemeester De Vries",
      "consentStatus": "consent_given",
      "policyMatch": "<standing-consent uuid>",
      "notificationStatus": "skipped",
      "objectionDeadline": null,
      "wasUpdated": true
    },
    {
      "consentId": "<uuid>",
      "entityId": 7,
      "entityText": "Anneke Jansen",
      "consentStatus": "pending",
      "policyMatch": null,
      "notificationStatus": "pending",
      "objectionDeadline": "2026-06-04T11:00:00Z",
      "wasUpdated": false
    }
  ]
}
```

`wasUpdated` is true if an existing record was matched-and-updated; false if a new record was created. Frontends use this to render a per-entity confirmation in the UI.

**Rationale:** the operator submitted decisions; they need feedback on what each decision became. Without `createdConsents[]`, the frontend has to re-query `GET /api/consents/document/{documentId}` to see the result, which adds round-trips and can race with concurrent updates.

### D7. Batch flow honours the same shape

`POST /api/anonymization/batch/{batchId}/anonymize` accepts `unredactedEntities[]` per file in the batch. The response aggregates `createdConsents[]` per file. A 422 from one file's prohibition violation does NOT block the rest of the batch — it surfaces as that file's per-file outcome (multi-status response shape).

**Rationale:** consistency with the per-doc endpoint. Operators driving batch publications shouldn't have to flip back to per-doc just to use the unredacted-entities flow.

## Risks / Trade-offs

- **[Anonymise endpoint complexity creep]** → Mitigation: keep the new logic cleanly separable in the controller / service. The existing anonymise pipeline runs first; the publicationConsent creation runs after, with its own input validation and error path.
- **[Idempotency edge: entityKey is null on legacy records]** → Mitigation: fall back to `entityText` matching when `entityKey` is null. Document the limitation. Future cleanup can backfill `entityKey` if needed.
- **[Sentinel-tagged notes overwrites operator content]** → Mitigation per D4: documented; operators see the brackets and know the region is auto-managed. Linter at apply time catches malformed sentinels.
- **[Stub notification means real WOO compliance is operator-driven]** → Mitigation: documented in CHANGELOG; until real dispatch lands, operators send by their existing channels and mark status manually. The 28-day clock starts; the structural record is correct; only automated dispatch is missing.
- **[Operator submits an entity in BOTH entities[] and unredactedEntities[]]** → Edge case; logically incoherent. Server rejects with 400 ("entity X cannot be both anonymised and published unredacted") at input validation.
- **[Re-submit while a notification was previously sent]** → Idempotency preserves `notificationStatus: sent` (workflow state). Operator-controlled fields update; no double-send.
- **[Cross-change ordering — PolicyMatchService doesn't exist yet]** → Mitigation: hard fail-closed if `PolicyMatchService` isn't available (treat as "no policy matches"). The existing WOO workflow path keeps working. When `entity-publication-policies` apply lands the matcher, this change picks it up automatically.

## Migration Plan

1. Land the idempotency upgrade in `ConsentService::createConsentRequest()` (with backward-compatible behaviour for existing direct callers — they don't pass `entityKey`, so the lookup falls back to `entityText` and works the same).
2. Land the controller / service changes for `unredactedEntities[]` plus the 422 prohibition gate plus the `createdConsents[]` response aggregation.
3. Land the sentinel-tagged notes serialisation helper.
4. Update batch endpoints with the same additions.
5. Release. Operators see the new field on per-doc and batch anonymise; the response carries the publicationConsent results.

**Rollback:** Remove the `unredactedEntities[]` handling — the field is silently ignored on the way in, no consent records are created. Existing consent records are unaffected. The endpoint reverts to its pre-change shape for `unredactedEntities[]`-passing callers; their next call still creates consents via direct `POST /api/consents`.

## Seed Data

Not applicable — this change introduces no new schemas or seed objects. publicationConsent records are created at runtime via the new flow; no fixtures needed beyond the existing test data.

## Open Questions

- **`PolicyMatchService` availability at apply time** — confirm whether `entity-publication-policies` apply has scaffolded it, or whether this change's apply phase needs to scaffold it. Either way, this change consumes it; whichever change lands first builds it.
- **Sentinel-tagged region malformation handling** — when the operator manually edits notes and breaks the sentinel pair, what's the expected recovery? Provisional: log a warning, treat as "no managed region present", append fresh region at the end of notes. Confirm during apply.
- **Should `createConsentRequest` upgrade to also accept an explicit `policyMatch` parameter** for callers that want to bypass `PolicyMatchService` (e.g. tests, or a future hook from other apps)? Provisional: no — the matcher is the single source of truth; bypass would create inconsistent records.
- **Decision on `(documentId, entityKey)` matching when entityKey is null on the input but exists on a record** — for v1 we match by `entityText` in that case; should we instead require entityKey on input? Provisional: accept entityText fallback for v1 (operators / frontend may not always have entityKey at hand). Resolve at apply time if it causes confusion.

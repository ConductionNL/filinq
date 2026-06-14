## Context

The publication-clearance stack lives in `ConsentService` + `PolicyMatchService` + the `publicationConsent` / `publicationProhibition` schemas. No automated caller drives it today. Rather than build a separate "publication-prep" controller with session state and a multi-stage workflow (over-engineering), extend the existing anonymise endpoint with a parallel input — `unredactedEntities[]` — that triggers one `createConsentRequest` per entry after the anonymise pipeline succeeds.

The idempotency + notes serialisation of `createConsentRequest` itself lives in the sibling change `consent-create-idempotency-and-notes`.

## Goals / Non-Goals

**Goals:**

- `unredactedEntities[]` on the per-document anonymise endpoint, additive + non-breaking.
- Per-entry validation: `entityId`, `entityText`, `entityType`, `publicationBases[]` required; `contactEmail`, `contactAddress` optional.
- Hard prohibition gate (any-confidence) that fails the request with HTTP 422 listing offending entries.
- Per-entry `createConsentRequest` call after the existing anonymise pipeline succeeds.
- `createdConsents[]` response field aggregating the resulting publicationConsent records.
- Batch endpoint per-file parity with per-file 422 surfaced as HTTP 207.

**Non-Goals:**

- `createConsentRequest` idempotency + notes serialisation — sibling.
- Real notification dispatch — separate change (this v1 leaves `notificationStatus: "pending"` records without auto-send).
- Multi-document publication.
- A separate publication-prep controller.

## Decisions

### D1. No new endpoint

The stateless extension of the existing anonymise endpoint covers it. A separate controller with session state would force operators through a multi-stage workflow that buys nothing the per-entity decision doesn't already encode.

### D2. Hard prohibition gate at any confidence

The existing `anonymisation-prohibition-gate` discriminates by confidence (≥ 0.85 hard, < 0.85 overridable). For `unredactedEntities[]`, the operator made an explicit decision to publish unredacted — any-confidence prohibition match is a contradiction. Always 422. No `acknowledgedOverrides` here; the operator must move the entity to `entities[]` (anonymise) and re-submit.

### D3. Post-anonymise ordering

The publication-consent creation runs AFTER the anonymise pipeline (entities + prohibition gate + bases passthrough) completes successfully. If the anonymise pipeline fails (gate, conversion, OR error), no consents are created. Keeps the atomicity story simple: anonymise + consent creation are sequential, not interleaved.

### D4. Per-entry response aggregation

`createdConsents[]` carries one entry per `unredactedEntities[]` entry — either a "created" record (new publicationConsent UUID + status) or an "updated" record (existing UUID + status) per the idempotency story owned by the sibling.

### D5. Batch per-file multi-status

HTTP 207 multi-status when some files succeeded and some failed; HTTP 422 when none succeeded; HTTP 200 when all succeeded. Mirrors existing batch behaviour.

## Risks / Trade-offs

- **Operator confusion: "why was my request 422'd"** — the 422 body lists the offending entries with the matched rule, telling the operator exactly what to do.
- **Performance: many unredactedEntities per call** — linear in entity count, dominated by OR writes. For typical handful of entities, negligible. Documented.
- **`PolicyMatchService` not yet present** — sibling changes scaffold it. Until then, the standing-consent + prohibition gate paths inside `createConsentRequest` are no-ops; the WOO workflow takes over for every entry.

## Migration Plan

1. Ensure `consent-create-idempotency-and-notes` ships (or land alongside).
2. Add `unredactedEntities[]` payload validation + 422 prohibition gate on the per-document anonymise endpoint.
3. Wire the post-anonymise consent-creation orchestration.
4. Mirror on the batch endpoint.

**Rollback:** Strip the new field handler (early-return without consent creation). Anonymise endpoint behaves as pre-change.

## Seed Data

Not applicable.

## Open Questions

- Should `publicationBases[]` empty array be rejected at validation time or allowed as a "no basis provided" record? Provisional: rejected — every unredacted entity must declare at least one basis.

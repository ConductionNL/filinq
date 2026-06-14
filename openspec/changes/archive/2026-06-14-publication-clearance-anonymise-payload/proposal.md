## Why

`ConsentService::createConsentRequest()` is the entry point for publication-clearance (per `entity-publication-policies`), but it has no automated caller — consents can only be created by directly hitting `POST /api/consents`. The whole publication-clearance stack (prohibition matching, standing consents, WOO 28-day workflow) sits behind a method that nothing calls. The operator's per-entity decision — anonymise / publish unredacted under what basis / leave alone — is a stateless choice the existing extract → anonymise loop already drives.

This change extends the per-document anonymise endpoint with `unredactedEntities[]`. Each entry triggers a `publicationConsent` record via `createConsentRequest()`. Prohibited entities cannot appear in `unredactedEntities[]` — the request fails with HTTP 422 instructing the operator to move them to `entities[]`. The idempotent `createConsentRequest` + sentinel-tagged notes serialisation are owned by the sibling change `consent-create-idempotency-and-notes`.

## What Changes

- **MODIFIED:** `anonymization` capability — the per-document anonymise endpoint payload accepts optional `unredactedEntities[]`. Each entry: `entityId`, `entityText`, `entityType`, `publicationBases[]` (array of strings), optional `contactEmail` and `contactAddress`.
- **MODIFIED:** For each entry in `unredactedEntities[]`, the controller calls `ConsentService::createConsentRequest()` AFTER the anonymise pipeline (entities + prohibition gate + bases passthrough) completes successfully; one `publicationConsent` record per unredacted entity; status driven by `PolicyMatchService` (standing-consent match → `consent_given` + `policyMatch` populated; no match → `pending` + objection deadline).
- **MODIFIED:** Hard prohibition gate on `unredactedEntities[]`. If any entry matches an active `publicationProhibition` rule (any confidence — the operator made an explicit decision; the existing prohibition gate's threshold semantics do NOT apply here), the request fails with HTTP 422 listing the offending entries; operator must move them to `entities[]` and re-submit. No auto-redirect.
- **MODIFIED:** Response shape gains `createdConsents[]` describing which `publicationConsent` records were created or updated.
- **MODIFIED:** Batch endpoint accepts the same `unredactedEntities[]` per file; per-file consent creation; per-file 422 surfaced as multi-status (HTTP 207).
- **NO new schemas, no new endpoints.** Additive on existing endpoints; non-breaking for callers that don't supply `unredactedEntities`.

### Out of scope

- Idempotency + sentinel-tagged notes serialisation in `createConsentRequest()` — sibling change `consent-create-idempotency-and-notes`.
- Real notification dispatch — separate change.
- Multi-document publication (a single call clearing publication for a folder of files at once) — per-file v1.
- A separate "publication-prep" controller / endpoint.
- A structured publication-grounds vocabulary — `publicationBases[]` accepts any string in v1.
- Auto-resolution of contact info from BRP / KvK directories.
- Decision-endpoint changes (`publicationDecision` updates continue through existing `PUT /api/consents/{id}`).
- Final publication action.

## Capabilities

### Modified Capabilities

- `anonymization`

## Cross-app Dependencies

- **Hard** — `docudesk:consent-create-idempotency-and-notes` — provides the idempotent `createConsentRequest()` that this change calls per `unredactedEntities[]` entry.
- **Soft** — `docudesk:publication-prohibition-schema` + `docudesk:publication-consent-policy-fields` — together provide `PolicyMatchService` and the policy-pre-emption logic inside `createConsentRequest`. Until those apply, the flow falls through to the existing WOO workflow only.
- **Soft** — `docudesk:anonymisation-prohibition-gate` — the prohibition matcher / gate / PolicyMatchService scaffold land there; either change can ship first; the second consumes.

## Impact

- **Code (docudesk):** `lib/Controller/AnonymizationController.php` (accept `unredactedEntities[]`; validate; 422 on prohibition; call into ConsentService after the anonymise flow succeeds), `lib/Service/AnonymizationService.php` (orchestration + `createdConsents[]` aggregation), batch controller + service mirror.
- **API contract:** payload gains optional `unredactedEntities[]`; response gains optional `createdConsents[]`; HTTP 422 added with structured body listing prohibition-violating entries; batch surface may return HTTP 207. No new endpoints.
- **Privacy / compliance:** closes the gap from canonical `consent-management` REQ-CONS-07 / CONS-048 / CONS-050 — `publicationConsent` creation now has an automated trigger via the anonymise endpoint.
- **Performance:** one `createConsentRequest` call per `unredactedEntities[]` entry, post-anonymise. Linear in entity count; dominated by OR object writes.
- **Migration:** None.

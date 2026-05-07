## Why

The `entity-publication-policies` change established the publicationConsent / publicationProhibition / standing-consent data model and stated that `ConsentService::createConsentRequest()` is the entry point. The canonical `consent-management` spec (REQ-CONS-07: CONS-048 / CONS-050) flagged that this method has no automated caller — consents can only be created by directly hitting `POST /api/consents`. The whole publication-clearance stack (prohibition matching, standing consents, WOO 28-day workflow) sits behind a method that nothing calls.

Building a separate "publication-prep" entry point with session state and a multi-stage workflow would be over-engineering. The operator's per-entity decision — anonymise this / publish unredacted under what basis / leave alone — is a stateless choice the existing extract → anonymise loop already drives. We just need the anonymise endpoint to accept "publish unredacted" decisions alongside the existing "anonymise" decisions, and call `createConsentRequest()` for each.

This change extends the per-document anonymise endpoint with `unredactedEntities[]`. Each entry triggers a publicationConsent record via `createConsentRequest()`. The existing `PolicyMatchService` inside `createConsentRequest` (per `entity-publication-policies`) handles the pre-emption logic automatically — standing-consent matches resolve immediately to `consent_given` + `policyMatch`, no-match cases enter the WOO workflow with `pending` + computed objection deadline. Prohibited entities cannot appear in `unredactedEntities[]` — the request fails with HTTP 422 instructing the operator to move them to `entities[]` (anonymise).

## What Changes

- **NEW:** `unredactedEntities[]` field on the per-document anonymise endpoint payload. Each entry: `entityId`, `entityText`, `entityType`, `publicationBases[]` (array of strings — Q3' answer), optional `contactEmail` and `contactAddress`. The field is optional; omitting it preserves pre-change behaviour exactly.
- **NEW:** For each entry in `unredactedEntities[]`, the controller calls `ConsentService::createConsentRequest()` AFTER the anonymise pipeline (entities + prohibition gate + bases passthrough) completes successfully. The result is one publicationConsent record per unredacted entity, with status driven by `PolicyMatchService` (standing-consent match → `consent_given` / `policyMatch` populated; no match → `pending` + objection deadline).
- **NEW:** `ConsentService::createConsentRequest()` becomes idempotent on `(documentId, entityKey)`. Re-submitting the same anonymise call updates an existing record's operator-set fields (`legalBasis`, `notes`, `contactEmail`, `contactAddress`) while preserving workflow state (`notificationStatus`, `notificationSentAt`, `objectionReceivedAt`, `objectionReason`, `consentStatus` for non-pre-empted records).
- **NEW:** Hard prohibition gate on `unredactedEntities[]`. If any entry's `(entityType, entityText, resolvedIdentifiers)` matches an active `publicationProhibition` rule (any confidence — the operator made an explicit decision; threshold semantics from the existing prohibition gate don't apply here), the request fails with HTTP 422 listing the offending entries. The operator must move them to `entities[]` (anonymise) and re-submit. No auto-redirect.
- **NEW:** Multiple-bases serialisation. `publicationBases[]` is an array of strings; the first element goes verbatim to `publicationConsent.legalBasis` (existing string field, max 500 chars). Remaining elements are serialised into `publicationConsent.notes` under a sentinel-tagged markdown section so re-submits replace the auto-managed region cleanly without disturbing operator-authored notes content.
- **STUB:** Notification dispatch in v1. publicationConsent records created with `consentStatus: "pending"` carry `notificationStatus: "pending"` but the system MUST NOT send any email or postal notification automatically. Operators advance status manually (or via a follow-up change that adds real dispatch). The objection-deadline is still computed and recorded — the workflow timer starts; only the dispatch is stubbed.
- **NO new schemas, no new endpoints, no new state.** The change is a non-breaking extension of an existing endpoint plus a small refinement to an existing service method. publicationConsent records (already an OR object), `EntityRelation.bases` (paired OR change), and the existing dossier vocabulary are reused.

### Sentinel-tagged notes serialisation

When `publicationBases` has more than one element, the additional bases are written into `publicationConsent.notes` as:

```
<existing operator-authored notes content, if any>

<!-- docudesk:additional-publication-bases:begin -->
**Aanvullende publicatiegrondslagen:**
- <basis 2>
- <basis 3>
<!-- docudesk:additional-publication-bases:end -->
```

On a re-submit, the controller replaces ONLY the bracketed region (matching the begin/end sentinels) with a fresh rendering. Operator-authored content outside the brackets is preserved. If the array shrinks back to one element, the bracketed region is removed entirely.

### Out of scope

- **Real notification dispatch** — email or postal templates, dispatch retry, postal-address handling. Tracked as a separate change.
- **Multi-document publication** (a single call clearing publication for a folder of files at once) — per-file v1; multi-document is a follow-up.
- **A separate "publication-prep" controller / endpoint** — this change deliberately does NOT introduce one. Stateless extension of the anonymise endpoint covers it.
- **A structured publication-grounds vocabulary** (Woo Art. 3 / AVG Art. 6 grounds as `base` schema records or a separate schema) — `publicationBases[]` accepts any string in v1, which is enough to start.
- **Auto-resolution of contact information** from BRP / KvK directories — operator supplies `contactEmail` and `contactAddress` directly or fills them later via existing `PUT /api/consents/{id}`.
- **Decision endpoint changes** — `publicationDecision` updates continue through the existing `PUT /api/consents/{id}` path. No new decision-time API in this change.
- **Final publication action** (output assembly, push to overheid.nl, etc.) — out of scope; substantial separate work.

## Capabilities

### New Capabilities

(none — this change extends existing capabilities without introducing new ones.)

### Modified Capabilities

- `anonymization`: the per-document anonymise endpoint accepts the new `unredactedEntities[]` field; the response shape gains a `createdConsents[]` array describing which publicationConsent records were created or updated; HTTP 422 added as a possible response when prohibition-listed entities appear in `unredactedEntities[]`.
- `consent-management`: `ConsentService::createConsentRequest()` becomes idempotent on `(documentId, entityKey)`; the additional-bases serialisation into `notes` follows the sentinel-tagged convention; notification dispatch stays stubbed (existing CONS-049 documents this; this change reaffirms — `notificationStatus: pending` records do NOT automatically send email).

## Impact

- **Code (docudesk):**
  - `lib/Controller/AnonymizationController.php` — accept `unredactedEntities[]`; validate shape; reject prohibited entities with 422; call into ConsentService for each entry after the existing anonymise flow succeeds.
  - `lib/Service/AnonymizationService.php` — orchestrate the post-anonymise consent-creation step; aggregate the `createdConsents[]` for the response.
  - `lib/Service/ConsentService.php` — make `createConsentRequest()` idempotent on `(documentId, entityKey)`; implement the sentinel-tagged notes serialisation; preserve workflow state on update.
  - `lib/Controller/BatchAnonymizationController.php` and `lib/Service/BatchAnonymizeService.php` — accept the same `unredactedEntities[]` per file in the batch; per-file consent creation; per-file 422 surfaced as multi-status.
- **API contract:**
  - Per-document anonymise endpoint: payload gains optional `unredactedEntities[]` field. Response gains optional `createdConsents[]` array (new field; absent for callers that don't supply unredactedEntities). HTTP 422 added with a structured body listing prohibition-violating entries.
  - Batch anonymise endpoint: same additions, with per-file `createdConsents[]` aggregation in the multi-file response.
  - No new endpoints. The new `unredactedEntities[]` and `createdConsents[]` are additive on existing endpoints.
- **Cross-app:**
  - Soft dep on `entity-publication-policies` (DocuDesk) — provides `PolicyMatchService` and the policy-pre-emption logic inside `createConsentRequest`. Until that change applies, the publication-clearance flow falls through to the existing WOO workflow only (no standing-consent matches; no prohibitions).
  - Soft dep on `anonymisation-grondslagen-and-prohibition-gate` (DocuDesk) — the prohibition gate and PolicyMatchService scaffold land there. Either change can ship first; the second consumes the work.
  - No OR-side change needed.
- **Privacy / compliance:** Closes the gap from canonical `consent-management` REQ-CONS-07 / CONS-048 / CONS-050 — publicationConsent creation now has an automated trigger via the anonymise endpoint. WOO publication clearance becomes an operationally-driveable workflow.
- **Performance:** One `createConsentRequest` call per `unredactedEntities[]` entry, post-anonymise. For typical documents with handful of unredacted entities, negligible. Larger document with hundreds of unredacted entities: linear in entity count, dominated by OR object writes.
- **Migration:** None. publicationConsent records created from the new flow have the same shape as records created via direct `POST /api/consents`. Existing records are unaffected. Idempotency on re-submit preserves prior workflow state.
- **Tests:** Unit tests for the idempotent createConsentRequest (new record, update existing, sentinel-tagged notes); controller tests for the new field shape, the 422 path, and the response aggregation; integration tests for end-to-end "submit anonymise call with mixed entities and unredactedEntities — verify expected publicationConsent records exist after the call".

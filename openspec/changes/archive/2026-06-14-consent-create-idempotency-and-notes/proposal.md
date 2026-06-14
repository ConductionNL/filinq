## Why

`ConsentService::createConsentRequest()` is the entry point for publication-clearance — once the sibling change `publication-clearance-anonymise-payload` ships, the anonymise endpoint will call this method once per `unredactedEntities[]` entry. Two refinements are needed to make that integration sane:

1. **Idempotency on `(documentId, entityKey)`** — re-submitting the same anonymise call must update an existing record's operator-set fields without disturbing workflow state. Otherwise every re-submit creates duplicate records (and risks confusing the WOO timer).
2. **Multi-bases serialisation into `publicationConsent.notes`** — `publicationBases[]` is an array of strings. The first element fits in the existing `legalBasis` string field (max 500 chars). Remaining elements need a home, and the cleanest one — without a schema migration — is a sentinel-tagged region inside the existing `notes` markdown field that re-submits can replace cleanly without disturbing operator-authored content.

This change refines `ConsentService::createConsentRequest()` to handle both. The anonymise-endpoint extension that calls it lives in `publication-clearance-anonymise-payload`.

## What Changes

- **MODIFIED:** `consent-management` capability — `ConsentService::createConsentRequest()` becomes idempotent on `(documentId, entityKey)`. Re-submitting the same anonymise call updates an existing record's operator-set fields (`legalBasis`, `notes`, `contactEmail`, `contactAddress`) while preserving workflow state (`notificationStatus`, `notificationSentAt`, `objectionReceivedAt`, `objectionReason`, `consentStatus` for non-pre-empted records).
- **MODIFIED:** Multi-bases serialisation. When `publicationBases[]` has more than one element, the additional bases are written into `publicationConsent.notes` under a sentinel-tagged markdown section so re-submits replace the auto-managed region cleanly without disturbing operator-authored notes content.
- **REAFFIRMED:** Notification dispatch stays stubbed (existing CONS-049). `publicationConsent` records created with `consentStatus: "pending"` carry `notificationStatus: "pending"` but the system MUST NOT send any email or postal notification automatically. The objection-deadline is still computed and recorded — only the dispatch is stubbed.
- **NO new schemas, no new endpoints.** Behaviour change on an existing service method.

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

On a re-submit, the service replaces ONLY the bracketed region with a fresh rendering. Operator-authored content outside the brackets is preserved. If the array shrinks back to one element, the bracketed region is removed entirely.

### Out of scope

- The anonymise-endpoint extension that invokes `createConsentRequest` — sibling `publication-clearance-anonymise-payload`.
- Real notification dispatch.
- A structured publication-grounds vocabulary.

## Capabilities

### Modified Capabilities

- `consent-management`

## Cross-app Dependencies

- **Soft** — `docudesk:publication-prohibition-schema` + `docudesk:publication-consent-policy-fields` — provide the `PolicyMatchService` and policy-pre-emption logic inside `createConsentRequest`. Either order is fine; this change's idempotency + notes serialisation work standalone.

## Impact

- **Code (docudesk):** `lib/Service/ConsentService.php` — idempotent `createConsentRequest()` on `(documentId, entityKey)`; sentinel-tagged notes serialisation helper; workflow-state preservation on update.
- **API contract:** the externally-visible behaviour of the existing `POST /api/consents` is unchanged for callers that don't supply duplicate `(documentId, entityKey)`. Callers that do supply a duplicate now see an UPDATE rather than a 409/duplicate error. Backward compatible for the intended use case.
- **Migration:** None. Existing records are valid as-is. New writes through the path benefit from idempotency.

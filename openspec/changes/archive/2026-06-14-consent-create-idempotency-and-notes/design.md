## Context

`ConsentService::createConsentRequest()` is the entry point for publication-clearance. When the anonymise-endpoint extension (`publication-clearance-anonymise-payload`) starts calling it per `unredactedEntities[]` entry, two things must work cleanly: re-submits must not duplicate records (idempotency), and multi-element `publicationBases[]` must round-trip through the existing `legalBasis` (string ≤500) + `notes` (markdown) fields without disturbing operator-authored content.

This change is the consent-service refinement. The anonymise-endpoint extension is the sibling.

## Goals / Non-Goals

**Goals:**

- Idempotent `createConsentRequest()` keyed on `(documentId, entityKey)`. New `(documentId, entityKey)` → create. Existing → update operator-set fields, preserve workflow state.
- Sentinel-tagged region in `publicationConsent.notes` for additional `publicationBases[]` (basis 2..N). Clean re-render on re-submit. Preserved operator-authored content outside the brackets.
- Stubbed notification dispatch (reaffirmation of existing CONS-049).

**Non-Goals:**

- Anonymise-endpoint extension — sibling.
- Real notification dispatch.
- A structured publication-grounds vocabulary.

## Decisions

### D1. Idempotency key: `(documentId, entityKey)`

`entityKey` is the OR `Entity` UUID resolved at extract time (the same identifier used by the prohibition gate / extract-time `prohibitionMatch`). One publicationConsent per (document, entity) pair. Re-submits update; no new record is created.

### D2. What's preserved on update

Workflow state: `notificationStatus`, `notificationSentAt`, `objectionReceivedAt`, `objectionReason`, `consentStatus` (for non-pre-empted records — pre-empted records keep their policyMatch-driven status). Operator-set fields are updated: `legalBasis`, `notes` (only the bracketed region), `contactEmail`, `contactAddress`. The pre-emption discriminator (`policyMatch` + `notificationStatus: "skipped"`) is re-evaluated; if the policy layer's view of the entity changed (e.g. a prohibition was added between the original submit and the re-submit), the existing retroactive handler in `publication-prohibition-schema` already handles that path.

### D3. Sentinel format

```
<existing operator-authored notes content, if any>

<!-- docudesk:additional-publication-bases:begin -->
**Aanvullende publicatiegrondslagen:**
- <basis 2>
- <basis 3>
<!-- docudesk:additional-publication-bases:end -->
```

HTML-comment sentinels are markdown-invisible (they don't render in NC's markdown viewers). The begin/end pair uniquely brackets the auto-managed region; the service regex matches `<!-- docudesk:additional-publication-bases:begin -->.*?<!-- docudesk:additional-publication-bases:end -->` (single line, multi-line via `s` flag) and replaces. On shrink-to-one-element, the bracketed region (and any leading blank line preceding it that was inserted by the prior write) is removed entirely.

### D4. First basis goes to `legalBasis`; the rest to `notes`

`legalBasis` is the existing string field (max 500 chars). Most operators supply one basis; multi-basis is the edge case. Truncate the first element at 500 chars (UI warns) but don't split mid-word.

### D5. Notification dispatch stays stubbed

Per existing CONS-049: publicationConsent records created with `consentStatus: "pending"` carry `notificationStatus: "pending"` but the system MUST NOT auto-send. The objection-deadline is still computed and recorded — the workflow timer starts; only dispatch is stubbed. Real dispatch is a separate change.

## Risks / Trade-offs

- **Sentinel collision with operator-authored content** — the sentinel string is specific (`docudesk:additional-publication-bases`); operator unlikely to type it verbatim. If they do, behaviour is undefined; documented.
- **Re-submit during in-flight WOO workflow** — preserved workflow state means a re-submit can't accidentally reset `notificationSentAt`. Tests cover.
- **Re-submit after retroactive prohibition force-resolve** — the existing publicationConsent is already `anonymized` + `policyMatch → prohibition`. The anonymise endpoint's hard prohibition gate (per sibling) blocks the re-submit at HTTP 422 before this service ever sees it. Documented as the safe path.

## Migration Plan

1. Implement idempotent `createConsentRequest()` keyed on `(documentId, entityKey)`.
2. Implement the sentinel-tagged notes serialisation helper.
3. Add tests for both.

**Rollback:** Revert `createConsentRequest()` to non-idempotent (existing 409/duplicate behaviour on `(documentId, entityKey)`). Sentinel-tagged region becomes a no-op (single-basis only). Acceptable for emergency rollback.

## Seed Data

Not applicable.

## Open Questions

- Should the truncation of `legalBasis` at 500 chars happen silently with a UI warning, or fail loudly server-side? Provisional: truncate server-side + warn in UI; future enhancement may schema-bump the field length once we know real usage patterns.

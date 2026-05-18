---
status: draft
---

# Consent Management — Delta for Idempotent createConsentRequest and Sentinel-Tagged Notes

This delta extends the existing `consent-management` capability so `ConsentService::createConsentRequest()` is idempotent on `(documentId, entityKey)`. Re-submits update operator-controlled fields and preserve workflow state. Multiple publication bases supplied per entity are serialised into the existing `legalBasis` (first) and `notes` (rest, in a sentinel-tagged region) fields. Notification dispatch remains stubbed (existing CONS-049 reaffirmed).

## ADDED Requirements

### Requirement: `createConsentRequest` MUST be idempotent on `(documentId, entityKey)`

When `createConsentRequest` is called with a `documentId` and an `entityKey` (or, if `entityKey` is not supplied, an `entityText`) that matches an existing publicationConsent record, the method MUST update that record rather than create a duplicate. The match MUST honour scope: only `scope: "document"` records are considered for matching (the entity-scope flavour of `publicationConsent` records standing consents and is unrelated to per-document workflow).

The fields updated on match MUST be: `entityType`, `legalBasis`, `notes`, `contactEmail`, `contactAddress`. The fields PRESERVED on match (i.e. NOT overwritten) MUST be: `notificationStatus`, `notificationSentAt`, `objectionDeadline`, `objectionReceivedAt`, `objectionReason`, `consentStatus`, `policyMatch`, `publicationDecision`. The exception: `policyMatch` MAY be set if it was previously null and a match now exists; it MUST NOT be cleared if a match previously existed.

#### Scenario: First call creates a new record

- **GIVEN** no existing publicationConsent record matches `(documentId, entityKey)`
- **WHEN** `createConsentRequest($documentId, "PERSON", "Burgemeester De Vries", $register, $schema, [entityKey: "X"])` is called
- **THEN** a new record is created
- **AND** the response contains the new record's UUID and `wasUpdated: false`

#### Scenario: Re-submit with same key updates the existing record

- **GIVEN** a publicationConsent record exists with `(documentId, entityKey) = ("doc-1", "X")`, `legalBasis: "Old basis"`, `notificationStatus: "sent"`, `notificationSentAt: "2026-04-01T10:00:00Z"`
- **WHEN** `createConsentRequest("doc-1", "PERSON", "Burgemeester De Vries", $register, $schema, [entityKey: "X", legalBasis: "New basis"])` is called
- **THEN** the existing record is updated: `legalBasis: "New basis"`
- **AND** `notificationStatus: "sent"` and `notificationSentAt: "2026-04-01T10:00:00Z"` are PRESERVED
- **AND** the response contains the existing record's UUID and `wasUpdated: true`
- **AND** no second record is created

#### Scenario: Workflow state is preserved across re-submits

- **GIVEN** a publicationConsent record with `consentStatus: "objection_received"`, `objectionReceivedAt: "2026-04-15T..."`, `objectionReason: "..."`
- **WHEN** `createConsentRequest` is called for the same `(documentId, entityKey)` with operator-updated `legalBasis` and `notes`
- **THEN** the `consentStatus`, `objectionReceivedAt`, `objectionReason` fields are preserved
- **AND** only `legalBasis`, `notes` are updated
- **AND** the WOO workflow timer (`objectionDeadline`) is NOT reset

#### Scenario: Match falls back to entityText when entityKey is null

- **GIVEN** a legacy publicationConsent record with `entityKey: null` and `entityText: "Karin de Vries"`
- **WHEN** `createConsentRequest` is called for the same `documentId` with `entityKey: null` and `entityText: "Karin de Vries"`
- **THEN** the lookup matches by `entityText`
- **AND** the record is updated rather than duplicated

#### Scenario: scope=entity records are not matched

- **GIVEN** a `scope: "entity"` `publicationConsent` record (a standing consent) for "Karin de Vries"
- **WHEN** `createConsentRequest` is called for documentId X (per-doc, scope=document) for the same entity
- **THEN** the standing consent record is NOT matched as a duplicate
- **AND** a new `scope: "document"` record is created
- **AND** the standing consent record is consulted by `PolicyMatchService` and may produce a `policyMatch` reference on the new record (per `entity-publication-policies`)

### Requirement: Multiple publication bases MUST serialise into legalBasis + notes

When `createConsentRequest` is called with an `extra.publicationBases` array (or equivalent), the bases MUST be serialised:

1. The first element MUST be set as `legalBasis` (verbatim, max 500 chars per existing schema constraint).
2. If more than one element is supplied, the remaining elements MUST be serialised into `notes` as a markdown bullet list inside a sentinel-tagged region.

The sentinel-tagged region MUST use the markers `<!-- docudesk:additional-publication-bases:begin -->` and `<!-- docudesk:additional-publication-bases:end -->` (literal HTML comment syntax — preserved by the markdown renderer used by the consent UI).

The body of the region MUST be:

```
<!-- docudesk:additional-publication-bases:begin -->
**Aanvullende publicatiegrondslagen:**
- <basis 2>
- <basis 3>
<!-- docudesk:additional-publication-bases:end -->
```

On a re-submit, the helper MUST locate the existing bracketed region (matching begin/end sentinels) and replace ONLY that region with the freshly-rendered content. Operator-authored content outside the brackets MUST be preserved. If the new submit has only one element in `publicationBases`, the bracketed region MUST be removed entirely (notes return to operator-authored content only).

#### Scenario: Single basis populates legalBasis only

- **WHEN** `createConsentRequest` is called with `publicationBases: ["Woo art. 3.1"]`
- **THEN** the record has `legalBasis: "Woo art. 3.1"`
- **AND** `notes` does NOT contain any sentinel-tagged region

#### Scenario: Multiple bases populate legalBasis + sentinel-tagged notes

- **WHEN** `createConsentRequest` is called with `publicationBases: ["Woo art. 3.1", "AVG art. 6 lid 1 sub a", "Prior consent dd. 2026-04-12"]`
- **THEN** the record has `legalBasis: "Woo art. 3.1"`
- **AND** `notes` contains the sentinel-tagged region with bullets for the other two bases

#### Scenario: Re-submit replaces the bracketed region only

- **GIVEN** a record with operator-authored notes content "Reviewed by privacy officer 2026-04-12." followed by a sentinel-tagged region with two additional bases
- **WHEN** the call is re-submitted with `publicationBases: ["Woo art. 3.1", "AVG art. 6 lid 1 sub e"]` (one additional basis instead of two)
- **THEN** the operator's note about the privacy officer is PRESERVED
- **AND** the bracketed region is replaced with the new single bullet
- **AND** no duplicate sentinel pairs exist in the resulting notes

#### Scenario: Shrinking to one basis removes the bracketed region

- **GIVEN** a record with a sentinel-tagged region in notes
- **WHEN** the call is re-submitted with `publicationBases: ["Woo art. 3.1"]` (single element)
- **THEN** the bracketed region is removed entirely
- **AND** any operator-authored content outside it is preserved

#### Scenario: Malformed sentinel region falls back to append

- **GIVEN** a record where the operator manually broke the sentinel pair (e.g. removed the closing tag)
- **WHEN** a re-submit happens
- **THEN** the helper logs a warning
- **AND** treats the situation as "no managed region present"
- **AND** appends a fresh region at the end of the existing notes
- **AND** the resulting record has at most one valid bracketed pair

### Requirement: Notification dispatch MUST stay stubbed in v1

This delta does NOT add automated notification dispatch. publicationConsent records created with `consentStatus: "pending"` MUST have `notificationStatus: "pending"` and a computed `objectionDeadline`, but NO email or postal notification is sent automatically. Operators advance `notificationStatus` manually via the existing `PUT /api/consents/{id}` endpoint.

This reaffirms existing requirement CONS-049 from the canonical `consent-management` spec.

#### Scenario: New pending record does not trigger SMTP

- **GIVEN** a fresh DocuDesk install
- **WHEN** `createConsentRequest` creates a new record with `consentStatus: "pending"`
- **THEN** no SMTP activity is observed
- **AND** no entry is added to any notification-dispatch log
- **AND** `notificationStatus` is `pending` and `notificationSentAt` is null

### Requirement: The change MUST be additive and non-breaking

Existing direct callers of `createConsentRequest()` (e.g. via `POST /api/consents`) MUST see identical behaviour for inputs that don't match any existing record. The idempotency upgrade is invisible for callers that always provide unique `(documentId, entityKey)` combinations. The serialisation logic only runs when `publicationBases[]` has more than one element; single-basis or no-basis callers are unaffected.

#### Scenario: Direct API caller with unique key creates a record as before

- **GIVEN** a pre-change client calling `POST /api/consents` with a fresh `(documentId, entityKey)` that doesn't match any record
- **WHEN** the call is made
- **THEN** a new record is created
- **AND** behaviour is identical to pre-change

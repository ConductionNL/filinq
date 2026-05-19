---
status: draft
---

# Anonymization — Delta for Publication-Clearance-via-Anonymise

This delta extends the existing `anonymization` capability so the per-document and batch anonymise endpoints accept an optional `unredactedEntities[]` field. Entries in this field trigger publicationConsent records via `ConsentService::createConsentRequest()` — completing the "no automated caller" gap flagged in `consent-management` REQ-CONS-07. Prohibited entities placed in this field are rejected with HTTP 422; the response gains a `createdConsents[]` aggregation describing the records created or updated.

## ADDED Requirements

### Requirement: The anonymise endpoint MUST accept an optional `unredactedEntities[]` field

The per-document anonymise endpoint payload MUST accept an optional top-level `unredactedEntities[]` array. Each entry MUST have shape:

```json
{
  "entityId": <int|null>,
  "entityText": "<string>",
  "entityType": "PERSON" | "ORGANIZATION",
  "entityKey": "<string, optional>",
  "publicationBases": ["<string>", ...],
  "contactEmail": "<string, optional>",
  "contactAddress": "<string, optional>"
}
```

Required fields per entry: `entityText`, `entityType`, `publicationBases` (MUST be a non-empty array of strings). Optional: `entityId`, `entityKey`, `contactEmail`, `contactAddress`.

Behaviour when `unredactedEntities[]` is omitted or empty MUST be identical to the pre-change anonymise endpoint.

The same field MUST be accepted on the batch anonymise endpoint, per-file in the batch payload.

#### Scenario: Empty / omitted unredactedEntities preserves pre-change behaviour

- **GIVEN** an anonymise request with no `unredactedEntities[]` field (or an empty array)
- **WHEN** the request completes
- **THEN** no publicationConsent records are created
- **AND** the response shape is the pre-change shape (no `createdConsents[]` field)

#### Scenario: Single unredacted entity creates a publicationConsent record

- **GIVEN** an anonymise request with `unredactedEntities: [{entityId: 5, entityText: "Burgemeester De Vries", entityType: "PERSON", publicationBases: ["Woo art. 3.1"]}]`
- **AND** no policy rule matches this entity
- **WHEN** the request completes
- **THEN** a new publicationConsent record exists with `documentId` matching the request's file, `entityText: "Burgemeester De Vries"`, `consentStatus: "pending"`, `legalBasis: "Woo art. 3.1"`, `notificationStatus: "pending"`, and a non-null `objectionDeadline`
- **AND** the response includes a `createdConsents[]` array with one entry containing the new record's `consentId`, `entityId: 5`, `consentStatus: "pending"`, and `wasUpdated: false`

#### Scenario: Standing-consent match resolves to consent_given

- **GIVEN** an `unredactedEntities[]` entry whose entity matches an active `scope: "entity"` `publicationConsent` (standing consent)
- **WHEN** the anonymise request processes the entry
- **THEN** the resulting per-document publicationConsent record has `consentStatus: "consent_given"`, `notificationStatus: "skipped"`, `policyMatch` referencing the standing consent
- **AND** `objectionDeadline` is null
- **AND** the response's `createdConsents[]` entry reflects this state

#### Scenario: Required field missing rejects with 400

- **WHEN** an `unredactedEntities[]` entry is missing `entityText` or `entityType` or has empty `publicationBases`
- **THEN** the request is rejected with HTTP 400 citing the missing field

### Requirement: Prohibited entities in `unredactedEntities[]` MUST be rejected with HTTP 422

If any `unredactedEntities[]` entry's `(entityType, entityText, resolvedIdentifiers)` matches an active `publicationProhibition` rule, the entire request MUST be rejected with HTTP 422. The response body MUST list each rejected entry with the matching `ruleId` and `ruleName`. No partial work MUST be performed — the anonymise pipeline MUST NOT run for any of the request's `entities[]`, and no publicationConsent records MUST be created for any of the request's `unredactedEntities[]`. The check fires at any confidence threshold (no override path on this gate).

The 422 body shape MUST be:

```json
{
  "error": "<localised string>",
  "rejectedUnredacted": [
    {
      "entityId": <int|null>,
      "entityText": "<string>",
      "ruleId": "<uuid>",
      "ruleName": "<string>"
    }
  ],
  "fallback": "<localised hint pointing operator to move entries into entities[]>"
}
```

#### Scenario: Prohibited entity rejected loudly

- **GIVEN** an active `publicationProhibition` rule matching "Beschermde Getuige A"
- **AND** an anonymise request with `unredactedEntities: [{entityText: "Beschermde Getuige A", entityType: "PERSON", publicationBases: ["..."]}]`
- **WHEN** the request is processed
- **THEN** the response is HTTP 422
- **AND** the body's `rejectedUnredacted[]` lists the entry with the matching rule UUID and name
- **AND** no anonymise pipeline runs (no files written, no EntityRelation rows updated)
- **AND** no publicationConsent records are created

#### Scenario: Both prohibited and unproblematic entries — entire request rejected

- **GIVEN** an `unredactedEntities[]` array with one prohibited entry and three unproblematic entries
- **WHEN** the request is processed
- **THEN** the response is HTTP 422
- **AND** `rejectedUnredacted[]` lists only the prohibited entry
- **AND** none of the three unproblematic entries result in publicationConsent records
- **AND** the operator's path forward is to remove the prohibited entry (or move it to `entities[]`) and re-submit

#### Scenario: Confidence threshold does not apply on this gate

- **GIVEN** an `unredactedEntities[]` entry whose entity matches a prohibition rule at any (even low) confidence
- **WHEN** the request is processed
- **THEN** the entry is rejected
- **AND** the operator cannot use `acknowledgedOverrides` to bypass (the override mechanism applies only to the prohibition gate on `entities[]`, not on `unredactedEntities[]`)

### Requirement: An entity MUST NOT appear in BOTH `entities[]` and `unredactedEntities[]`

The two sets MUST be disjoint per call. If the same entity appears in both, the request MUST be rejected with HTTP 400 ("entity cannot be both anonymised and published unredacted in the same call"). The error response MUST identify the conflicting entity.

#### Scenario: Same entity in both sets is rejected

- **GIVEN** a request where entity 5 appears in both `entities[]` and `unredactedEntities[]`
- **WHEN** the request is processed
- **THEN** the response is HTTP 400
- **AND** the body identifies entity 5 as the conflict

### Requirement: The response MUST include a `createdConsents[]` aggregation

When `unredactedEntities[]` is supplied and the request succeeds (HTTP 200), the response MUST include a top-level `createdConsents[]` array. Each entry MUST report:

- `consentId` (UUID of the publicationConsent record)
- `entityId` (matching the request's entry, or null if not provided)
- `entityText`
- `consentStatus`
- `policyMatch` (UUID of the matched rule, or null)
- `notificationStatus`
- `objectionDeadline` (or null when not applicable)
- `wasUpdated` (boolean: true if an existing record was updated, false if newly created)

The array's order MUST match the order of `unredactedEntities[]` in the request.

#### Scenario: Aggregation reports per-entity outcome

- **GIVEN** a request with three entries in `unredactedEntities[]` — one matching a standing consent, one not matching anything, one matching a record from a previous submit
- **WHEN** the response is returned
- **THEN** `createdConsents[]` has exactly three entries in the same order
- **AND** entry 1 has `consentStatus: "consent_given"`, `policyMatch: <uuid>`, `wasUpdated: false`
- **AND** entry 2 has `consentStatus: "pending"`, `policyMatch: null`, `wasUpdated: false`
- **AND** entry 3 has `wasUpdated: true` (matched on documentId+entityKey)

#### Scenario: Pre-change clients ignore the new field

- **GIVEN** a pre-change client that doesn't read `createdConsents[]`
- **WHEN** the response includes the new field
- **THEN** the client's existing code is unaffected
- **AND** the response is a strict superset of the pre-change shape

### Requirement: Notification dispatch in v1 MUST be stubbed

When a publicationConsent record is created with `consentStatus: "pending"` (the no-match case), the system MUST set `notificationStatus: "pending"` and compute the `objectionDeadline` per existing CONS-005 rules. The system MUST NOT automatically send any email or postal notification in v1.

Operators MAY advance `notificationStatus` manually via the existing `PUT /api/consents/{id}` endpoint once they have sent the notification by their out-of-band means.

#### Scenario: Pending record carries unset notificationStatus

- **GIVEN** a successful unredactedEntities flow producing a `pending` publicationConsent
- **WHEN** the record is inspected
- **THEN** `notificationStatus: "pending"` and `notificationSentAt: null`
- **AND** no email has been sent (no SMTP activity, no log entry indicating a send attempt)
- **AND** `objectionDeadline` is set to the configured period from creation

#### Scenario: Operator marks notification sent manually

- **GIVEN** a pending publicationConsent record
- **WHEN** the operator PUTs `{notificationStatus: "sent", notificationSentAt: "<timestamp>"}` to `/api/consents/{id}`
- **THEN** the record is updated per the existing `consent-management` REQ-CONS-02 transitions
- **AND** the WOO workflow proceeds normally from that point

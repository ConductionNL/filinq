---
status: draft
---

# Anonymization — Delta for Publication-Clearance-via-Anonymise

This delta adds a defensive runtime check to the anonymise endpoint: a file MUST NOT be anonymised while any of its `skipAnonymization: true` relations still have a corresponding publicationConsent record in a blocking state. The endpoint payload and the success response shape are unchanged. HTTP 422 becomes a new failure response listing the blocking consent records.

This delta does NOT add new request fields, new endpoints, or new consent-creation logic to the anonymise call. Consent creation is event-driven (see the `consent-management` delta of this change); the anonymise call only verifies that all decisions are resolved before mutating the file.

## ADDED Requirements

### Requirement: The anonymise endpoint MUST reject calls when any skip-marked relation has a blocking consent record

Before delegating to OpenRegister's `anonymizeDocument`, the anonymise endpoint MUST verify that every `EntityRelation` row for the target file with `skipAnonymization: true` has either no corresponding `publicationConsent` record OR a record in a non-blocking state. The classification:

| consentStatus | publicationDecision | objectionDeadline | Blocking? |
|---|---|---|---|
| `consent_given` | (any) | (any) | No |
| `anonymized` | (any) | (any) | No |
| `pending` | (any) | past | No |
| `pending` | (any) | future | **YES** |
| `objection_received` | `anonymize` | (any) | No |
| `objection_received` | `publish_with_consent` | (any) | No |
| `objection_received` | `pending` | (any) | **YES** |
| `no_response` | (any) | (any) | No |

When at least one blocking record is found, the request MUST return HTTP 422 with a structured body listing every blocking consent. No file mutation MUST occur. No EntityRelation row MUST be modified.

The 422 body shape MUST be:

```json
{
  "error": "<localised string>",
  "blockingConsents": [
    {
      "consentId": "<uuid>",
      "entityText": "<string>",
      "consentStatus": "<enum>",
      "objectionDeadline": "<ISO-8601 timestamp or null>",
      "reason": "<one of: objection_window_open | objection_under_review>"
    }
  ]
}
```

#### Scenario: File with no skip-marked relations passes the check

- **GIVEN** a file whose EntityRelations all have `skipAnonymization: false`
- **WHEN** the anonymise endpoint is called
- **THEN** the check passes
- **AND** anonymisation proceeds as before

#### Scenario: Skip-marked relation with an auto-resolved consent passes the check

- **GIVEN** a file with a `skipAnonymization: true` relation
- **AND** a publicationConsent record for the entity has `consentStatus: "consent_given"` (standing-consent match)
- **WHEN** the anonymise endpoint is called
- **THEN** the check passes
- **AND** anonymisation proceeds

#### Scenario: Skip-marked relation with a pending consent in window blocks the call

- **GIVEN** a file with a `skipAnonymization: true` relation for entity "Anneke Jansen"
- **AND** a publicationConsent record for that entity has `consentStatus: "pending"` and `objectionDeadline` 10 days in the future
- **WHEN** the anonymise endpoint is called
- **THEN** the response is HTTP 422
- **AND** `blockingConsents[]` lists exactly one entry referencing the consent record with `reason: "objection_window_open"`
- **AND** no file mutation occurs

#### Scenario: Skip-marked relation with a pending consent past window passes

- **GIVEN** a file with a `skipAnonymization: true` relation
- **AND** the publicationConsent's `objectionDeadline` has already passed
- **WHEN** the anonymise endpoint is called
- **THEN** the check passes (window closed; "no objection received" is the operator's go-ahead)
- **AND** anonymisation proceeds

#### Scenario: Skip-marked relation with objection received, decision pending, blocks

- **GIVEN** a publicationConsent record with `consentStatus: "objection_received"` and `publicationDecision: "pending"`
- **WHEN** the anonymise endpoint is called for the associated file
- **THEN** the response is HTTP 422 with `reason: "objection_under_review"`

#### Scenario: Skip-marked relation with objection received and decision = anonymize passes

- **GIVEN** a publicationConsent record with `consentStatus: "objection_received"` and `publicationDecision: "anonymize"`
- **WHEN** the anonymise endpoint is called for the associated file
- **THEN** the check passes (operator decided to anonymise despite the skip flag — the decision overrides)

#### Scenario: Skip-marked relation with no consent record proceeds with a warning

- **GIVEN** a `skipAnonymization: true` relation whose corresponding publicationConsent record is missing (likely listener failure)
- **WHEN** the anonymise endpoint is called
- **THEN** the check logs a warning identifying the relation
- **AND** the relation is treated as not-blocking
- **AND** anonymisation proceeds (the operator's skip decision stands; the missing consent record is a system bug to investigate separately, not a reason to block the user)

#### Scenario: Multiple skip-marked relations, mixed states

- **GIVEN** three `skipAnonymization: true` relations:
  - Relation A → consent `consent_given`
  - Relation B → consent `pending` in window (blocking)
  - Relation C → consent `pending` past window (not blocking)
- **WHEN** the anonymise endpoint is called
- **THEN** the response is HTTP 422
- **AND** `blockingConsents[]` lists only relation B's consent
- **AND** no file mutation occurs

### Requirement: The anonymise endpoint's success-path shape MUST be unchanged

This delta MUST NOT modify the anonymise endpoint's request payload or its successful (HTTP 200) response shape. Existing callers that pass the same payload they pass today MUST receive the same response they receive today.

#### Scenario: Pre-change client is unaffected on the happy path

- **GIVEN** a pre-change client that sends an anonymise request with the existing payload (no `skipAnonymization: true` relations on the target file)
- **WHEN** the request succeeds
- **THEN** the response body matches the pre-change shape exactly (no new fields)

#### Scenario: HTTP 422 is the only new failure response

- **WHEN** any other anonymise error condition arises (file not found, permission denied, OR rejection, etc.)
- **THEN** the response code and shape remain whatever the pre-change behaviour produced
- **AND** the new 422 path applies ONLY to the blocking-consent case described above

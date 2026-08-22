# Publication Consent Process

## Overview

The Publication Consent Process is a GDPR and Dutch Wet Open Overheid (Open Government Act) compliant workflow for managing the publication of documents containing personal data. This process ensures that organizations and persons mentioned in documents are properly informed and have the opportunity to object before publication.

## Legal Framework

### GDPR Requirements

Under the General Data Protection Regulation (GDPR), personal data can only be processed (including publication) if there is a legal basis. For public sector organizations, relevant legal bases include:

- **Article 6(1)(e)**: Processing is necessary for the performance of a task carried out in the public interest
- **Article 6(1)(f)**: Processing is necessary for the purposes of legitimate interests

### Dutch Wet Open Overheid

The Dutch Wet Open Overheid (Open Government Act) requires:

- **Article 3.1**: Government information should be proactively published
- **Article 3.2**: Personal data must be protected unless there is a legal basis for publication
- **Article 3.3**: Affected parties must be informed before publication
- **Minimum objection period**: 4 weeks (28 days) for parties to respond

## Process Flow

The following diagram illustrates the complete publication consent workflow:

```mermaid
flowchart TD
    Start([Document Ready for Publication]) --> Detect[Detect Entities in Document]
    Detect --> Check{Entities Found?}
    Check -->|No| Publish[Publish Document]
    Check -->|Yes| CreateConsent[Create Publication Consent Records]
    
    CreateConsent --> ForEach{For Each Entity}
    ForEach --> CheckContact{Contact Info Available?}
    
    CheckContact -->|Yes| SendNotification[Send Notification]
    CheckContact -->|No| SkipNotification[Mark as Skipped]
    
    SendNotification --> SetDeadline[Set Objection Deadline<br/>+28 days]
    SkipNotification --> SetDeadline
    
    SetDeadline --> Wait[Wait for Response]
    Wait --> CheckDeadline{Deadline Passed?}
    
    CheckDeadline -->|No| CheckResponse{Response Received?}
    CheckResponse -->|Yes| ProcessResponse[Process Response]
    CheckResponse -->|No| Wait
    
    CheckDeadline -->|Yes| NoResponse[Mark as No Response]
    
    ProcessResponse --> CheckType{Response Type?}
    CheckType -->|Consent| ConsentGiven[Mark Consent Given]
    CheckType -->|Objection| ObjectionReceived[Mark Objection Received]
    
    ConsentGiven --> DecisionConsent{Publication Decision}
    ObjectionReceived --> DecisionObjection{Publication Decision}
    NoResponse --> DecisionNoResponse{Publication Decision}
    
    DecisionConsent -->|Publish with Consent| PublishWithConsent[Publish Document<br/>with Entity Info]
    DecisionConsent -->|Anonymize| Anonymize[Anonymize Entity]
    
    DecisionObjection -->|Anonymize| Anonymize
    DecisionObjection -->|Reject| Reject[Reject Publication]
    
    DecisionNoResponse -->|Anonymize Default| Anonymize
    DecisionNoResponse -->|Publish with Consent| PublishWithConsent
    
    Anonymize --> ApplyAnonymization[Apply Anonymization<br/>to Document]
    ApplyAnonymization --> PublishAnonymized[Publish Anonymized<br/>Document]
    
    Publish --> End([Publication Complete])
    PublishWithConsent --> End
    PublishAnonymized --> End
    Reject --> End
```

## Detailed Process Steps

### Step 1: Entity Detection

When a document is prepared for publication:

1. **Text Extraction**: OpenRegister extracts text from the document (if not already done)
2. **Entity Detection**: Filinq uses Presidio to detect entities:
   - **PERSON**: Names of individuals
   - **ORGANIZATION**: Names of organizations
   - Other PII types (EMAIL_ADDRESS, PHONE_NUMBER, etc.) may also trigger the process
3. **Entity Filtering**: Only PERSON and ORGANIZATION entities trigger the consent process

### Step 2: Create Publication Consent Records

For each detected PERSON or ORGANIZATION entity:

1. **Create Record**: A new `publicationConsent` object is created in OpenRegister
2. **Store Entity Information**:
   - `entityType`: PERSON or ORGANIZATION
   - `entityText`: The detected text
   - `entityKey`: Unique identifier for anonymization
   - `documentId`: Reference to the document
3. **Initialize Status**:
   - `notificationStatus`: Set to "pending"
   - `consentStatus`: Set to "pending"
   - `publicationDecision`: Set to "pending"

### Step 3: Contact Information Lookup

For each entity, attempt to find contact information:

1. **Check Existing Records**: Look for existing contact information in:
   - OpenRegister entity records
   - Organization databases
   - Contact management systems
2. **Store Contact Info**:
   - `contactEmail`: Email address (if found)
   - `contactAddress`: Postal address (if found)
3. **Update Status**: If no contact info found, `notificationStatus` is set to "skipped"

### Step 4: Notification

Entities must be notified about pending publication:

1. **Notification Methods**:
   - **Email**: If `contactEmail` is available
   - **Postal Mail**: If only `contactAddress` is available
   - **Skipped**: If no contact information is available
2. **Notification Content**:
   - Document title and description
   - Where the entity is mentioned
   - Publication date
   - Objection deadline (minimum 4 weeks)
   - How to object
   - Legal basis for publication
3. **Update Status**:
   - `notificationStatus`: Set to "sent" or "delivered"
   - `notificationSentAt`: Record timestamp

### Step 5: Set Objection Deadline

According to Wet Open Overheid, entities must have at least 4 weeks to respond:

1. **Calculate Deadline**: 
   - `objectionDeadline` = `notificationSentAt` + 28 days (minimum)
   - Can be extended based on organizational policy
2. **Configuration**: The deadline period can be configured via:
   - `publication_objection_period_days` setting (default: 28 days)

### Step 6: Wait for Response

During the objection period:

1. **Monitor Responses**: Check for:
   - Consent given (via email, portal, or other method)
   - Objection received (via email, portal, or other method)
2. **Update Status**: As responses are received:
   - `consentStatus`: Updated to "consent_given" or "objection_received"
   - `objectionReceivedAt`: Timestamp (if objection)
   - `objectionReason`: Reason for objection (if provided)

### Step 7: Process Responses

After the deadline or when responses are received:

#### 7a. Consent Given

If an entity gives consent:

1. **Update Status**: `consentStatus` = "consent_given"
2. **Decision Options**:
   - **Publish with Consent**: Document can be published with entity information visible
   - **Anonymize Anyway**: Organization may still choose to anonymize for other reasons

#### 7b. Objection Received

If an entity objects:

1. **Update Status**: 
   - `consentStatus` = "objection_received"
   - `objectionReceivedAt` = current timestamp
   - `objectionReason` = reason provided
2. **Decision Options**:
   - **Anonymize**: Remove entity information before publication (recommended)
   - **Reject Publication**: Do not publish the document
   - **Override**: Only if there is a strong legal basis (rare)

#### 7c. No Response

If no response is received by the deadline:

1. **Update Status**: `consentStatus` = "no_response"
2. **Decision Options**:
   - **Anonymize (Default)**: Default to anonymization for privacy protection
   - **Publish with Consent**: Only if there is a clear legal basis

### Step 8: Make Publication Decision

For each entity, a final decision is made:

1. **Decision Types**:
   - `anonymize`: Remove entity information before publication
   - `publish_with_consent`: Publish with entity information visible
   - `publish_anonymized`: Publish anonymized version
   - `reject`: Do not publish the document
2. **Update Record**: `publicationDecision` is set to the chosen option
3. **Legal Basis**: `legalBasis` field documents the legal justification

### Step 9: Apply Anonymization (if needed)

If the decision is to anonymize:

1. **Retrieve Entities**: Get all entities marked for anonymization
2. **Apply Anonymization**: Use OpenRegister DocumentService to replace entity text
3. **Create Anonymized Version**: New file is created with `_anonymized` suffix
4. **Update Document**: Document metadata is updated with anonymization results

### Step 10: Publish Document

Final publication step:

1. **Check All Consents**: Ensure all publication consent records are resolved
2. **Publication Status**: Update document `publicationStatus`:
   - `published`: Document is published
   - `anonymized`: Anonymized version is published
   - `rejected`: Publication is rejected
3. **Audit Trail**: All decisions and timestamps are recorded for compliance

## Scope Discriminator

The `publicationConsent` schema supports two types of records, distinguished by the `scope` field:

| Scope | Description |
|-------|-------------|
| `document` | Per-document WOO workflow record (default — backward compatible). Linked to a specific document via `documentId`. |
| `entity` | Standing-consent record. Grants blanket prior consent for an entity; consulted before starting the WOO workflow. |

Existing records without a `scope` field are treated as `scope: "document"` automatically.

## Standing-Consent Pre-emption

When detection creates a per-document `publicationConsent` record, the consent service first checks for matching standing-consent records **before** starting the WOO notification workflow:

1. **Prohibition match** (sibling change `publication-prohibition-schema`): `consentStatus: "anonymized"`, `notificationStatus: "skipped"`, `policyMatch → publicationProhibition`.
2. **Standing-consent match**: `consentStatus: "consent_given"`, `notificationStatus: "skipped"`, `publicationDecision: "publish_with_consent"`, `policyMatch → publicationConsent (scope=entity)`.
3. **No match**: existing WOO workflow runs unchanged.

### Override-Up Flow

When an operator decides to anonymise a standing-consent-matched entity anyway:

1. The per-document record's `publicationDecision` transitions to `"anonymize"`.
2. `consentStatus` stays `"consent_given"` — the standing consent still matched.
3. `policyMatch` is preserved — the audit trail remains complete.
4. An audit event is emitted.

## Publication Consent Schema Fields

### Core Fields (all scopes)

- `scope`: `document` (default) or `entity` — discriminator
- `entityType`: PERSON or ORGANIZATION
- `entityText`: The detected entity text
- `legalBasis`: Legal justification
- `notes`: Internal notes

### Document-Scope Fields (`scope: "document"`)

- `documentId`: Reference to the document being published (required for document scope)
- `notificationStatus`: pending, sent, delivered, failed, skipped
- `consentStatus`: pending, consent_given, objection_received, no_response, anonymized
- `publicationDecision`: pending, anonymize, publish_with_consent, publish_anonymized, reject
- `notificationSentAt`: When notification was sent
- `objectionDeadline`: Deadline for objection (minimum 28 days)
- `objectionReceivedAt`: When objection was received (if applicable)
- `objectionReason`: Reason for objection (if provided)
- `contactEmail`: Email address for notification
- `contactAddress`: Postal address for notification
- `policyMatch`: Polymorphic reference to the rule that pre-empted this record (either a `publicationProhibition` or a `scope: "entity"` `publicationConsent`)

### Entity-Scope Fields (`scope: "entity"`)

- `matchRules`: Array of `{type, value}` matching rules. Supported types: `exact`, `normalized`, `bsn`, `kvk`
- `validFrom`: Start of the standing consent's validity (null = immediate)
- `validUntil`: End of the standing consent's validity (null = open-ended; UI warns when blank)
- `active`: Boolean — inactive records are kept for audit but not consulted
- `consentMethod`: How consent was obtained: `paper`, `digital_signature`, `verbal_recorded`, `opt_in_form` (required for entity scope)
- `consentDocument`: File reference to the signed consent document (optional)
- `consentScope`: Free text describing which publications the consent applies to

## Configuration

### Application Settings

Configure the publication consent process via Filinq settings:

- `publication_objection_period_days`: Number of days for objection period (default: 28, minimum: 28)
- `publication_notification_email_template`: Email template for notifications
- `publication_notification_postal_template`: Postal mail template for notifications
- `publication_default_decision`: Default decision when no response (default: "anonymize")
- `publication_legal_basis_default`: Default legal basis for publication

### Register Configuration

The `publicationConsent` schema is configured in `filinq_register.json`:

- Register: `document`
- Schema: `publicationConsent`
- Required fields: `documentId`, `entityType`, `entityText`

## API Endpoints

### Create Publication Consent Records

```http
POST /apps/filinq/api/publication-consent/create
Content-Type: application/json

{
  "documentId": "uuid-of-document",
  "entities": [
    {
      "entityType": "PERSON",
      "entityText": "John Doe",
      "entityKey": "abc123"
    }
  ]
}
```

### Update Consent Status

```http
PUT /apps/filinq/api/publication-consent/{id}
Content-Type: application/json

{
  "consentStatus": "consent_given",
  "objectionReason": null
}
```

### Get Consent Records for Document

```http
GET /apps/filinq/api/publication-consent/document/{documentId}
```

### Make Publication Decision

```http
POST /apps/filinq/api/publication-consent/{id}/decision
Content-Type: application/json

{
  "publicationDecision": "anonymize",
  "legalBasis": "Wet Open Overheid art. 3.1",
  "notes": "Entity objected, anonymizing before publication"
}
```

## Best Practices

### 1. Early Detection

- Detect entities as soon as documents are uploaded
- Create consent records immediately
- Start notification process early

### 2. Clear Communication

- Provide clear information about:
  - What document is being published
  - Where the entity is mentioned
  - Why publication is necessary
  - How to object

### 3. Adequate Time

- Always provide at least 4 weeks for response
- Consider extending for complex cases
- Document any deadline extensions

### 4. Document Decisions

- Always document the legal basis
- Record reasons for decisions
- Maintain audit trail

### 5. Default to Privacy

- When in doubt, anonymize
- Only publish with consent when legally justified
- Respect objections unless legally overridden

## Compliance Checklist

Before publishing a document, ensure:

- [ ] All entities (PERSON/ORGANIZATION) have been detected
- [ ] Publication consent records have been created
- [ ] All entities have been notified (or marked as skipped with reason)
- [ ] Objection deadline has been set (minimum 28 days)
- [ ] All responses have been processed
- [ ] Publication decisions have been made for all entities
- [ ] Legal basis has been documented
- [ ] Anonymization has been applied (if decision requires it)
- [ ] Audit trail is complete

## Example Scenarios

### Scenario 1: Consent Given

1. Document contains "John Doe" (PERSON)
2. Notification sent to john.doe@example.com
3. John responds: "I consent to publication"
4. Decision: `publish_with_consent`
5. Document published with "John Doe" visible

### Scenario 2: Objection Received

1. Document contains "Acme Corporation" (ORGANIZATION)
2. Notification sent to legal@acme.com
3. Acme responds: "We object due to commercial sensitivity"
4. Decision: `anonymize`
5. Document published with "[ORGANIZATION: abc123]" instead of "Acme Corporation"

### Scenario 3: No Response

1. Document contains "Jane Smith" (PERSON)
2. Notification sent to jane.smith@example.com
3. No response received within 28 days
4. Decision: `anonymize` (default)
5. Document published with "[PERSON: xyz789]" instead of "Jane Smith"

### Scenario 4: No Contact Information

1. Document contains "Unknown Organization" (ORGANIZATION)
2. No contact information available
3. Notification status: `skipped`
4. Decision: `anonymize` (default for skipped)
5. Document published with anonymized entity

## Admin Pages

### Consent Workflow

The **Consent Workflow** admin page (`/consent`) shows only `scope: "document"` records — the per-document WOO notification workflow. It filters out standing-consent records so operators see only the active notification queue.

Per-entity toggle behaviour:
- `policyMatch → publicationConsent (scope=entity)` — toggle is OFF by default, enabled. Operator may flip ON to anonymise anyway (override-up flow).
- `policyMatch: null` — existing UX based on `consentStatus`.

### Standing Publication Consents

The **Standing Publication Consents** admin page (`/standing-consents`) shows only `scope: "entity"` records. It allows authorised operators (members of `docudesk-standing-consent-admins`) to:

- List all active and inactive standing consents
- Create new standing consents (requires `consentMethod`; UI warns when `validUntil` is blank)
- Expire a consent (sets `active: false`, preserves for audit)
- Revoke a consent (sets `consentStatus: "anonymized"`, `active: false`)

Creating a standing consent requires membership in the `docudesk-standing-consent-admins` group.

## Publication-Clearance via the Anonymise Endpoint

> **Added:** `unredactedEntities[]` on the per-document and batch anonymise endpoints.

### Overview

Operators can trigger `publicationConsent` record creation directly from the anonymise call.
Rather than submitting entities separately via `POST /api/consents`, include them in the
`unredactedEntities[]` array of the same anonymise request:

```json
POST /index.php/apps/filinq/api/v1/anonymize/{fileId}
{
  "entities": [
    { "entityId": 1, "text": "Amsterdam", "type": "LOCATION" }
  ],
  "unredactedEntities": [
    {
      "entityId": 7,
      "entityText": "Jan Jansen",
      "entityType": "PERSON",
      "publicationBases": ["woo-artikel-3.1"],
      "contactEmail": "j.jansen@example.nl",
      "contactAddress": "Dorpsstraat 1, 1234 AB"
    }
  ]
}
```

### Required fields per `unredactedEntities[]` entry

| Field              | Type     | Required | Description                                   |
|--------------------|----------|----------|-----------------------------------------------|
| `entityId`         | integer  | yes      | Nextcloud entity relation ID                  |
| `entityText`       | string   | yes      | Literal text of the entity                    |
| `entityType`       | string   | yes      | Entity type (e.g. `PERSON`, `ORGANIZATION`)   |
| `publicationBases` | string[] | yes      | One or more legal publication bases; non-empty |
| `contactEmail`     | string   | no       | Optional contact e-mail for the data subject  |
| `contactAddress`   | string   | no       | Optional postal address for the data subject  |

### Prohibition gate (any-confidence, hard 422)

Before creating consents, every `unredactedEntities[]` entry is checked against
active `publicationProhibition` rules (via `PolicyMatchService`). Unlike the regular
prohibition gate (which only blocks at ≥ 0.85 confidence), the publication-clearance
gate is **any-confidence** — the operator made an explicit decision to publish unredacted,
so any prohibition match is a contradiction.

If any entry matches, the request fails with **HTTP 422** and no file mutation occurs:

```json
{
  "error": "One or more unredacted entities match a publication-prohibition rule. ...",
  "prohibitedEntries": [
    { "entityId": 7, "entityText": "Jan Jansen", "ruleId": "rule-1", "ruleName": "..." }
  ]
}
```

The operator must either move the prohibited entity to `entities[]` (to anonymise it)
and re-submit, or remove it entirely.

### `createdConsents[]` response field

On success the response gains a `createdConsents[]` field (absent when
`unredactedEntities` was not supplied):

```json
{
  "replacementCount": 1,
  "anonymizedFileId": "...",
  "createdConsents": [
    {
      "entityId": 7,
      "entityText": "Jan Jansen",
      "consentId": "<uuid>",
      "consentStatus": "pending",
      "action": "created"
    }
  ]
}
```

### Batch endpoint multi-status semantics

The batch `POST /api/v1/batch/{batchId}/anonymize` accepts the same
`unredactedEntities[]` (applied to every file in the batch). Per-file prohibition
violations are recorded but do not abort the batch:

| Outcome              | HTTP status |
|----------------------|-------------|
| All files processed  | **200**     |
| Some files violated  | **207**     |
| All files violated   | **422**     |

Files that violated the prohibition gate get status `prohibitionViolation` in the
batch response; successfully processed files carry `createdConsents[]` alongside the
usual `replacementCount` and `anonymizedFileId`.

## Entity-Level Policy Layer

Beyond the per-document workflow, Filinq supports two **entity-level** policy surfaces that pre-empt the workflow at detection time:

### Publication Prohibitions (`publicationProhibition` schema)

Deny-list rules. A matched entity is **always anonymised**, regardless of any per-document workflow or standing consent. Use cases:

- Court order to anonymise a witness ("witness A in case 2024/01")
- Statutory minor protection
- Undercover officers
- Categorical AVG exemptions decided by the Autoriteit Persoonsgegevens

A prohibition stores a real-world identity (preferably a stable identifier — BSN/KvK) plus a `reason`, `legalAuthority`, `severity`, `validFrom`/`validUntil`, and `active` flag.

### Standing Publication Consents (`publicationConsent` with `scope: "entity"`)

Allow-list rules. A matched entity may be published **without** running the 28-day objection workflow, because explicit consent was already obtained out-of-band. Use cases:

- Mayor signs a blanket consent for municipal decisions
- Organisation files an opt-in form for press releases
- Council member's standing consent for committee minutes

A standing consent stores a `consentMethod` (paper / digital_signature / verbal_recorded / opt_in_form), an optional `consentDocument` reference (audit-trail file), `consentScope` (textual scope description), and `validFrom`/`validUntil`/`active`.

### Three-layer evaluation order

For every detected entity in a publication-clearance flow, the consent service evaluates rules in this order:

1. **Publication prohibitions** — deny-list. If matched: `consentStatus: "anonymized"`, `notificationStatus: "skipped"`, `publicationDecision: "anonymize"`, `policyMatch` references the rule. Workflow ends.
2. **Standing publication consents** — allow-list. If matched (and no prohibition matches): `consentStatus: "consent_given"`, `notificationStatus: "skipped"`, `publicationDecision: "publish_with_consent"`, `policyMatch` references the rule. Workflow ends.
3. **Existing WOO workflow** — only reached when neither policy surface matches. Notification is sent, 28-day deadline starts, decision drives publication.

**Conflict resolution is deterministic and non-configurable**: prohibition wins. Multi-prohibition match is broken by lowest UUID (lexicographic).

### UI toggle semantics (per-document publication-prep screen)

The per-entity anonymisation toggle is keyed off the **referent type** of `policyMatch`, not the `consentStatus` enum:

| `policyMatch` referent | Toggle default | Overridable? |
|---|---|---|
| `null` | per existing UX based on `consentStatus` | yes |
| `publicationProhibition` | **ON, locked** | no |
| `publicationConsent` (scope=entity) | **OFF, defaulted** | yes — flipping ON anonymises anyway |

Overriding a standing-consent match is captured as a `publicationDecision: "anonymize"` change while `consentStatus` stays `"consent_given"` and `policyMatch` is preserved. The override is audit-logged via OpenRegister's mapper-level history.

### Retroactive behaviour

| Trigger | Effect on in-flight `scope: "document"` records |
|---|---|
| New prohibition created / activated / matchRules widened / validUntil extended | All matching in-flight records (status in `{pending, consent_given, objection_received, no_response}`) are **force-resolved to anonymised**. `notificationSentAt` and `objectionReceivedAt` are preserved for audit; `objectionDeadline` is cleared. |
| New standing consent created / activated | **No retroactive effect**. Standing consent applies to future detections only — past decisions are respected (privacy default wins on retroactive sweep). |
| Rule deletion / deactivation / expiry | **No retroactive effect**. Past records keep their final state with `policyMatch` intact (the reference becomes dangling; OpenRegister's referential-integrity surface governs how this is exposed). |
| Already-published documents | **Never modified**. Audit reports MAY surface "documents containing now-prohibited entities" for human review; the system does not initiate automatic redaction or republication. |

### Three admin surfaces

The Vue UI surfaces these in three separate admin pages — they are **not** consolidated:

| Page | Filter | Purpose |
|---|---|---|
| **Consent Workflow** | `publicationConsent` where `scope: "document"` | Per-document workflow records (the existing surface), now with a "policy pre-empted" indicator on rows whose `policyMatch` is non-null. |
| **Standing Publication Consents** | `publicationConsent` where `scope: "entity"` | List, edit, expire, revoke standing consents. The create-form requires `consentMethod` and warns when `validUntil` is left blank. |
| **Publication Prohibitions** | all `publicationProhibition` records | CRUD for prohibitions. The create-form encourages stable identifiers (BSN/KvK) and warns when only name-based rules are present. |

### RBAC defaults

| Surface | Read | Write |
|---|---|---|
| `publicationProhibition` | Authenticated users (no restriction by default) | `docudesk-policy-admins` group |
| `publicationConsent` (scope=document) | Existing consent-officer role | Existing consent-officer role |
| `publicationConsent` (scope=entity, "standing consent") | Existing consent-officer role can read | Service-level gate: `docudesk-standing-consent-admins` group is required. A consent-officer without this membership can still write `scope: "document"` records, but writes to `scope: "entity"` return 403. |

Admin users implicitly belong to both groups (NC convention). Adjust group memberships via the OpenRegister authorization UI.

## Idempotency Contract

### createConsentRequest() is Idempotent on (documentId, entityKey)

`ConsentService::createConsentRequest()` is idempotent on the composite key `(documentId, entityKey, scope: "document")`.

**Behaviour:**
- First call with a fresh `(documentId, entityKey)` → creates a new `publicationConsent` record, returns `wasUpdated: false`.
- Subsequent call with the same `(documentId, entityKey)` → updates the existing record, returns `wasUpdated: true`.
- When `entityKey` is `null`, the fallback key is `(documentId, entityText, scope: "document")`.
- `scope: "entity"` standing-consent records are **never** matched as duplicates of a per-document call.

**Preserved on update (workflow state — NOT overwritten):**

| Field | Reason |
|---|---|
| `notificationStatus` | Prevents resetting already-sent notifications |
| `notificationSentAt` | WOO audit trail timestamp |
| `objectionDeadline` | Timer must not be reset after the fact |
| `objectionReceivedAt` | Legal record |
| `objectionReason` | Legal record |
| `consentStatus` | In-flight workflow state |
| `publicationDecision` | Operator decision |

**Updated on re-submit (operator-set fields):**
`entityType`, `legalBasis`, `notes` (sentinel region only — see below), `contactEmail`, `contactAddress`.

**policyMatch re-evaluation:** If `policyMatch` was previously `null` and `PolicyMatchService::match()` now returns a standing-consent match, `policyMatch` is set. If it was previously set and the rule no longer matches, it is **not** cleared (the prior decision stands).

**Prohibition rejection:** If `PolicyMatchService::match()` returns a prohibition match, `createConsentRequest()` throws `OCA\Filinq\Exception\PolicyRejectedException` carrying the rule UUID and name. No record is created or updated.

### Sentinel-Tagged Additional-Bases Serialisation in notes

When `publicationBases[]` contains more than one element:
- `publicationBases[0]` → written to the `legalBasis` field (truncated at 500 chars at the last word boundary before the limit).
- `publicationBases[1..N]` → rendered inside a sentinel-tagged region in `notes`.

**Sentinel format:**

```markdown
<existing operator-authored notes content, if any>

<!-- docudesk:additional-publication-bases:begin -->
**Aanvullende publicatiegrondslagen:**
- <basis 2>
- <basis 3>
<!-- docudesk:additional-publication-bases:end -->
```

**Guarantees:**
- Sentinel comments are HTML-comment syntax — they are **markdown-invisible** and do not render in Nextcloud's markdown viewers.
- Operator-authored content outside the sentinel brackets is **never modified**.
- Re-submitting with the same `publicationBases[]` is a no-op on `notes` (idempotent render).
- Shrinking to a single basis (or zero) removes the bracketed region and its preceding blank line entirely.

**Sentinel collision risk:** The sentinel string `docudesk:additional-publication-bases` is highly specific. If an operator types it verbatim in their own notes, behaviour is undefined (documented trade-off).

## Related Documentation

- [Architecture Overview](../architecture.md)
- [Anonymization Features](./gdpr-anonymization.md)
- [API Documentation](../api/)



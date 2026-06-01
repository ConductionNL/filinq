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
2. **Entity Detection**: DocuDesk uses Presidio to detect entities:
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

Configure the publication consent process via DocuDesk settings:

- `publication_objection_period_days`: Number of days for objection period (default: 28, minimum: 28)
- `publication_notification_email_template`: Email template for notifications
- `publication_notification_postal_template`: Postal mail template for notifications
- `publication_default_decision`: Default decision when no response (default: "anonymize")
- `publication_legal_basis_default`: Default legal basis for publication

### Register Configuration

The `publicationConsent` schema is configured in `docudesk_register.json`:

- Register: `document`
- Schema: `publicationConsent`
- Required fields: `documentId`, `entityType`, `entityText`

## API Endpoints

### Create Publication Consent Records

```http
POST /apps/docudesk/api/publication-consent/create
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
PUT /apps/docudesk/api/publication-consent/{id}
Content-Type: application/json

{
  "consentStatus": "consent_given",
  "objectionReason": null
}
```

### Get Consent Records for Document

```http
GET /apps/docudesk/api/publication-consent/document/{documentId}
```

### Make Publication Decision

```http
POST /apps/docudesk/api/publication-consent/{id}/decision
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

## Related Documentation

- [Architecture Overview](../architecture.md)
- [Anonymization Features](./gdpr-anonymization.md)
- [API Documentation](../api/)



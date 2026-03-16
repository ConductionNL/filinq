---
status: reviewed
---

# Consent Management

## Purpose

Provides GDPR-compliant publication consent tracking for entities (persons and organizations) detected in documents. When a document is destined for publication under the Wet Open Overheid (WOO), affected entities must be notified and given an objection period (minimum 4 weeks per WOO). This feature manages the full consent lifecycle: creation, notification tracking, objection handling, and publication decision-making. All consent records are stored as OpenRegister objects using the PublicationConsent schema.

## Requirements

### Consent Record Creation

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-001 | Consent records can be created for detected entities in documents via ConsentService | MUST | Implemented |
| CONS-002 | Each consent record links to a document via documentId | MUST | Implemented |
| CONS-003 | Each consent record captures entityType (PERSON or ORGANIZATION) and entityText | MUST | Implemented |
| CONS-004 | Consent records are initialized with status `pending` for notificationStatus, consentStatus, and publicationDecision | MUST | Implemented |
| CONS-005 | An objection deadline is automatically calculated based on the configurable objection period (default 28 days) | MUST | Implemented |
| CONS-006 | Consent records are stored in OpenRegister via ObjectService using the configured register and schema | MUST | Implemented |

### Consent Status Lifecycle

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-010 | consentStatus transitions: `pending` -> `consent_given`, `objection_received`, `no_response`, or `anonymized` | MUST | Implemented |
| CONS-011 | notificationStatus transitions: `pending` -> `sent` -> `delivered` or `failed`; can also be `skipped` | MUST | Implemented |
| CONS-012 | publicationDecision options: `pending`, `anonymize`, `publish_with_consent`, `publish_anonymized`, `reject` | MUST | Implemented |
| CONS-013 | Consent status can be updated via `PUT /api/consents/{id}` | MUST | Implemented |
| CONS-014 | Objection deadline expiry can be checked programmatically via ConsentService::checkObjectionDeadline() | MUST | Implemented |

### Consent Listing and Querying

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-020 | All consent records can be listed via `GET /api/consents` | MUST | Implemented |
| CONS-021 | A specific consent record can be retrieved via `GET /api/consents/{id}` | MUST | Implemented |
| CONS-022 | Consent records for a specific document can be queried via `GET /api/consents/document/{documentId}` | MUST | Implemented |
| CONS-023 | Consent listing requires the publicationConsent register and schema to be configured in settings | MUST | Implemented |
| CONS-024 | If register/schema is not configured, a 400 error is returned with a descriptive message | MUST | Implemented |

### WOO Compliance

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-030 | The objection period defaults to 28 days (4 weeks) per Wet Open Overheid requirements | MUST | Implemented |
| CONS-031 | The objection period is configurable via admin settings (publication_objection_period_days) | MUST | Implemented |
| CONS-032 | The objection deadline is stored as an ISO 8601 datetime on each consent record | MUST | Implemented |

## Data Model

### PublicationConsent Schema

Defined in `lib/Settings/docudesk_register.json` and stored in OpenRegister.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| documentId | string | Yes | Reference to the document being published |
| entityType | string (enum) | Yes | `PERSON` or `ORGANIZATION` |
| entityText | string | Yes | Detected entity text (max 500 chars) |
| entityKey | string | No | Unique anonymization key (max 50 chars) |
| contactEmail | string (email) | No | Notification email address |
| contactAddress | string | No | Postal notification address (max 500 chars) |
| notificationStatus | string (enum) | Yes | `pending`, `sent`, `delivered`, `failed`, `skipped` |
| notificationSentAt | datetime | No | When notification was sent |
| consentStatus | string (enum) | Yes | `pending`, `consent_given`, `objection_received`, `no_response`, `anonymized` |
| objectionDeadline | datetime | No | Deadline for submitting objections (WOO: min 4 weeks) |
| objectionReceivedAt | datetime | No | When objection was received |
| objectionReason | string (markdown) | No | Reason for objection (max 2000 chars) |
| publicationDecision | string (enum) | Yes | `pending`, `anonymize`, `publish_with_consent`, `publish_anonymized`, `reject` |
| legalBasis | string | No | Legal basis for publication (e.g., "Wet Open Overheid art. 3.1", max 500 chars) |
| notes | string (markdown) | No | Internal process notes (max 2000 chars) |

### Consent Register

| Property | Value |
|----------|-------|
| Slug | `consent` |
| Title | Consent Register |
| Description | Register voor GDPR publicatie toestemmingen |
| Schemas | `publicationConsent` |

## User Interface

### Consent Index View (`ConsentIndex.vue`)

- **Stats cards**: Total, Pending, Approved, Objected counts with color-coded borders
- **Consent table**: Columns for Entity, Type, Consent Status, Notification, Deadline, Decision
- **Entity type badges**: Color-coded (PERSON = warning/orange, ORGANIZATION = primary/blue)
- **Status badges**: Color-coded per status value
- **Click-to-detail**: Clicking a row navigates to ConsentDetail view
- **Empty state**: NcEmptyContent with AccountCheck icon when no records exist
- **Loading state**: NcLoadingIcon while fetching

### Consent Detail View (`ConsentDetail.vue`)

- **Entity Information section**: Entity text, type, key, contact email, contact address
- **Consent Status section**: Editable dropdowns for consent status, notification status, publication decision; read-only objection deadline and received date
- **Objection Reason section**: Displayed when objection reason exists
- **Notes section**: Displayed when notes exist
- **Save button**: Updates consent record via PUT endpoint
- **Back button**: Returns to consent list

### Frontend Store (`consent.js`)

- Pinia store with `consents` array and `consentItem` for detail view
- Getters: `pendingConsents`, `approvedConsents`, `objectedConsents`, `consentStats` (total/pending/approved/objected/noResponse/anonymized)
- Actions: `fetchConsents`, `fetchConsent`, `updateConsent`, `fetchConsentsByDocument`, `setConsentItem`, `clearConsentItem`

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/consents` | List all consent records |
| GET | `/api/consents/{id}` | Get a specific consent record |
| PUT | `/api/consents/{id}` | Update a consent record |
| GET | `/api/consents/document/{documentId}` | Get consents for a specific document |

## Scenarios

### Create Consent for Detected Entity

```
GIVEN a document with detected PERSON entities
WHEN a consent request is created for an entity
THEN a PublicationConsent object is stored in OpenRegister
AND the consentStatus is set to "pending"
AND the notificationStatus is set to "pending"
AND the publicationDecision is set to "pending"
AND the objectionDeadline is set to current date + configured objection period days
```

### Update Consent Status

```
GIVEN a pending consent record
WHEN an administrator updates the consent status to "consent_given"
THEN the consent record is updated in OpenRegister
AND the updated record is returned
AND the local store is updated
```

### View Consents for a Document

```
GIVEN a document with multiple detected entities
WHEN the user queries consents by document ID
THEN all consent records linked to that document are returned
AND each record includes its current notification, consent, and publication status
```

### Check Objection Deadline

```
GIVEN a consent record with an objection deadline of 28 days from creation
WHEN the deadline date has passed
THEN ConsentService::checkObjectionDeadline() returns true
AND the publication decision can proceed
```

### Handle Unconfigured Register

```
GIVEN the publicationConsent register and schema are not configured in settings
WHEN any consent endpoint is called
THEN a 400 error is returned with message "PublicationConsent register/schema not configured"
```

## Internal Implementation Details

### Controller Bypasses ConsentService for Read Operations (Gap 5)

`ConsentController::index()` and `ConsentController::show()` do **not** use ConsentService for reading consent records. Instead, they:

1. Call `$this->settingsService->getAllSettings()` to get the configured register/schema
2. Call `$this->settingsService->getObjectService()` to get the ObjectService directly
3. Query ObjectService directly (via `searchObjects()` for index, `find()` for show)

Only `ConsentController::update()` and `ConsentController::byDocument()` delegate to ConsentService.

**Rationale**: The ConsentService read methods (`getConsentsByDocument()`) are less flexible than the direct ObjectService calls used by the controller. The service was designed primarily for write operations and document-scoped queries.

**Implication**: ConsentService is not a complete abstraction layer -- read paths bypass it entirely in the controller.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-040 | `ConsentController::index()` queries ObjectService directly via SettingsService, bypassing ConsentService | MUST | Implemented |
| CONS-041 | `ConsentController::show()` queries ObjectService directly via SettingsService, bypassing ConsentService | MUST | Implemented |
| CONS-042 | `ConsentController::update()` delegates to ConsentService::updateConsentStatus() | MUST | Implemented |
| CONS-043 | `ConsentController::byDocument()` delegates to ConsentService::getConsentsByDocument() | MUST | Implemented |

### RBAC/Multitenancy Bypass (Gap 6)

**All** OpenRegister ObjectService calls in ConsentService and ConsentController pass `_rbac: false` and `_multitenancy: false`. This means:

- **No role-based access control**: Any authenticated user can read/update any consent record regardless of their role or permissions
- **No organization scoping**: Consent records are not filtered by the user's organization/tenant
- **Security implication**: In a multi-tenant deployment, users from Organization A could view and modify consent records belonging to Organization B

**Affected calls** (all in ConsentService):
- `createConsentRequest()`: `saveObject(..., _rbac: false, _multitenancy: false)`
- `updateConsentStatus()`: `find(..., _rbac: false, _multitenancy: false)` and `saveObject(..., _rbac: false, _multitenancy: false)`
- `checkObjectionDeadline()`: `find(..., _rbac: false, _multitenancy: false)`
- `getConsentsByDocument()`: `searchObjects()` (no RBAC params on search)

**Additionally** in ConsentController:
- `show()`: `find(..., _rbac: false, _multitenancy: false)` via SettingsService::getObjectService()

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-044 | All ConsentService ObjectService calls bypass RBAC (`_rbac: false`) | MUST | Bug |
| CONS-045 | All ConsentService ObjectService calls bypass multitenancy (`_multitenancy: false`) | MUST | Bug |
| CONS-046 | ConsentController::show() bypasses RBAC when querying directly via SettingsService | MUST | Bug |

**Recommended fix**: Enable RBAC and multitenancy for production use, or document this as intentional for single-tenant deployments only.

### createConsentRequest() Has No API Endpoint (Gap 7)

`ConsentService::createConsentRequest()` is a public method but has **no corresponding API endpoint** in ConsentController and **no automated trigger**. The event listener (`DocuDeskEventListener`) does NOT call this method -- it only performs metadata enrichment.

This means consent records can only be created by:
1. Internal PHP code calling ConsentService directly
2. Future automation that has not been implemented yet

**There is no way for frontend users or API consumers to create consent records through the REST API.**

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-047 | `ConsentService::createConsentRequest()` exists as a public method | MUST | Implemented |
| CONS-048 | No API endpoint exists for creating consent records (no POST /api/consents) | MUST | Dead Code |
| CONS-049 | The event listener does NOT trigger consent creation -- only metadata enrichment | MUST | Implemented |
| CONS-050 | Consent records cannot currently be created via the REST API or UI | MUST | Bug |

### Objection Period Read From IAppConfig Directly (Gap 8)

`ConsentService::getObjectionPeriodDays()` reads the objection period directly from `IAppConfig` using `$this->config->getValueString($this->appName, 'publication_objection_period_days', '28')`.

This bypasses SettingsService, which also reads this same value in `getAllSettings()`. The duplication means:
- ConsentService has a hard dependency on the config key name
- If the config key name changes in SettingsService, ConsentService would break silently

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-051 | ConsentService reads objection period directly from IAppConfig, not via SettingsService | MUST | Implemented |
| CONS-052 | Default objection period is 28 days (hardcoded in getValueString default) | MUST | Implemented |

### Duplicated getObjectService() Pattern (Gap 9)

ConsentService has its own private `getObjectService()` method that duplicates the same pattern found in SettingsService and MetadataService:

```php
private function getObjectService(): \OCA\OpenRegister\Service\ObjectService
{
    if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
        return $this->container->get('OCA\OpenRegister\Service\ObjectService');
    }
    throw new \RuntimeException('OpenRegister service is not available.');
}
```

This identical code exists in 3 services (ConsentService, MetadataService, and as a public method in SettingsService). See also Gap 10 in metadata-enrichment spec.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-053 | ConsentService has its own private `getObjectService()` duplicating SettingsService pattern | MUST | Implemented |
| CONS-054 | The duplicated method uses the same `getInstalledApps()` + `container->get()` pattern | MUST | Implemented |

**Recommended fix**: Consolidate to use SettingsService::getObjectService() (which is public) instead of each service maintaining its own copy.

### Event Listener Does NOT Trigger Consent Creation (Gap 18)

The `DocuDeskEventListener` handles `ObjectCreatedEvent`, `ObjectUpdatedEvent`, and `ObjectDeletedEvent`, but performs **metadata enrichment only**. It does NOT:
- Create consent records for detected entities
- Call `ConsentService::createConsentRequest()`
- Interact with the consent system in any way

The listener only imports `MetadataService` and `SettingsService` -- `ConsentService` is not imported or referenced at all. The consent flow is entirely disconnected from the event-driven pipeline.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-055 | Event listener processes only metadata enrichment, not consent creation | MUST | Implemented |
| CONS-056 | ~~ConsentService is imported but unused in the event listener~~ ConsentService is NOT imported in the event listener -- only MetadataService and SettingsService are imported | MUST | Verified |
| CONS-057 | No automated consent creation exists -- consent records must be created manually or via future automation | MUST | Implemented |

## Dependencies

- **OpenRegister ObjectService**: CRUD operations on consent records
- **Nextcloud IAppConfig**: Storing objection period and register/schema configuration (read directly by ConsentService)
- **SettingsService**: Retrieving register/schema configuration for consent endpoints; provides ObjectService access for controller read operations
- **ConsentService**: Write operations (create, update) and document-scoped queries

### Current Implementation Status
- **Implemented** with file paths:
  - `lib/Service/ConsentService.php` -- `createConsentRequest()`, `updateConsentStatus()`, `checkObjectionDeadline()`, `getConsentsByDocument()`, `getObjectionPeriodDays()`, private `getObjectService()`
  - `lib/Controller/ConsentController.php` -- REST API: `index()`, `show()`, `update()`, `byDocument()` (no `create` endpoint)
  - `src/views/consent/ConsentIndex.vue` -- consent listing with stats cards and table
  - `src/views/consent/ConsentDetail.vue` -- consent detail/edit view with status dropdowns
  - `src/store/modules/consent.js` -- Pinia store with consent CRUD and stats getters
  - `lib/Settings/docudesk_register.json` -- PublicationConsent schema definition
  - `appinfo/routes.php` -- routes for GET/PUT consents and document-scoped query
- **Not yet implemented**:
  - **No POST /api/consents endpoint** (CONS-048/CONS-050) -- consent records cannot be created via REST API or UI
  - **No automated consent creation** (CONS-057) -- the event listener only does metadata enrichment, not consent creation
  - **RBAC/multitenancy disabled** (CONS-044/045/046) -- all ObjectService calls bypass access control
- **Partial**: The consent system is structurally complete but functionally disconnected -- `createConsentRequest()` is dead code with no trigger

### Standards & References
- **GDPR/AVG**: Consent management for personal data publication (Articles 6, 7, and 21 -- right to object)
- **WOO (Wet open overheid)**: Article 4.4 requires minimum 4-week objection period before publication. The configurable `publication_objection_period_days` (default 28) satisfies this.
- **ISO 8601**: Objection deadlines stored in ISO 8601 datetime format

### Specificity Assessment
- **Specific enough**: The data model and API are well-specified, but the creation flow is a critical gap.
- **Missing/Ambiguous**: How are consent records actually created? The spec documents `createConsentRequest()` as implemented but there's no trigger. The intended workflow (manual creation? automated from entity detection? triggered from WOO case?) is not specified.
- **Open questions**:
  1. Should a `POST /api/consents` endpoint be added?
  2. Should the event listener trigger consent creation when entities are detected?
  3. Is the RBAC bypass intentional for single-tenant deployments, or a bug that needs fixing for production?

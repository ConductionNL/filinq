---
status: implemented
---

# Consent Management

## Purpose

Provides GDPR-compliant publication consent tracking for entities (persons and organizations) detected in documents. When a document is destined for publication under the Wet Open Overheid (WOO), affected entities must be notified and given an objection period (minimum 4 weeks per WOO). This feature manages the full consent lifecycle: creation, notification tracking, objection handling, and publication decision-making. All consent records are stored as OpenRegister objects using the PublicationConsent schema.

## Requirements

### Requirement: Consent Record Creation

**ID:** REQ-CONS-01
**Priority:** Must

Consent records are created for detected entities in documents, initialized with pending status and an automatic objection deadline.

#### Scenario: Create consent for a detected person
- GIVEN a document with detected PERSON entities
- WHEN a consent request is created via ConsentService for entity "Jan de Vries"
- THEN a PublicationConsent object is stored in OpenRegister
- AND the consentStatus is set to "pending"
- AND the notificationStatus is set to "pending"
- AND the publicationDecision is set to "pending"
- AND the objectionDeadline is set to current date + configured objection period days

#### Scenario: Create consent with extra data
- GIVEN a document entity with additional contact information
- WHEN createConsentRequest is called with extra fields (contactEmail, contactAddress)
- THEN the extra fields are merged into the consent record
- AND the base consent data (statuses, deadline) is preserved

#### Scenario: Custom objection period
- GIVEN the admin has configured an objection period of 42 days
- WHEN a consent request is created
- THEN the objectionDeadline is set to current date + 42 days
- AND the deadline is stored in ISO 8601 datetime format

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-001 | Consent records can be created for detected entities via ConsentService | MUST | Implemented |
| CONS-002 | Each consent record links to a document via documentId | MUST | Implemented |
| CONS-003 | Each consent record captures entityType (PERSON or ORGANIZATION) and entityText | MUST | Implemented |
| CONS-004 | Consent records are initialized with status `pending` for notificationStatus, consentStatus, and publicationDecision | MUST | Implemented |
| CONS-005 | An objection deadline is automatically calculated based on the configurable objection period (default 28 days) | MUST | Implemented |
| CONS-006 | Consent records are stored in OpenRegister via ObjectService using the configured register and schema | MUST | Implemented |

### Requirement: Consent Status Lifecycle

**ID:** REQ-CONS-02
**Priority:** Must

Consent records progress through defined status transitions for consent, notification, and publication decision fields.

#### Scenario: Update consent status to consent_given
- GIVEN a pending consent record
- WHEN an administrator updates the consent status to "consent_given"
- THEN the consent record is updated in OpenRegister
- AND the updated record is returned with the new status

#### Scenario: Record an objection
- GIVEN a consent record with pending status
- WHEN the entity submits an objection with a reason
- THEN consentStatus transitions to "objection_received"
- AND objectionReceivedAt is set to the current datetime
- AND objectionReason stores the provided text

#### Scenario: Notification delivery tracking
- GIVEN a consent record with notificationStatus "pending"
- WHEN the notification is sent successfully
- THEN notificationStatus transitions to "sent"
- AND when delivery is confirmed, it transitions to "delivered"
- AND notificationSentAt is set to the send datetime

#### Scenario: Publication decision after objection period
- GIVEN a consent record where the objection deadline has passed
- AND consentStatus is "no_response"
- WHEN the administrator makes a publication decision
- THEN publicationDecision can be set to "publish_anonymized" or "publish_with_consent"

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-010 | consentStatus transitions: `pending` -> `consent_given`, `objection_received`, `no_response`, or `anonymized` | MUST | Implemented |
| CONS-011 | notificationStatus transitions: `pending` -> `sent` -> `delivered` or `failed`; can also be `skipped` | MUST | Implemented |
| CONS-012 | publicationDecision options: `pending`, `anonymize`, `publish_with_consent`, `publish_anonymized`, `reject` | MUST | Implemented |
| CONS-013 | Consent status can be updated via `PUT /api/consents/{id}` | MUST | Implemented |
| CONS-014 | Objection deadline expiry can be checked via ConsentService::checkObjectionDeadline() | MUST | Implemented |

### Requirement: Consent Listing and Querying

**ID:** REQ-CONS-03
**Priority:** Must

Consent records can be listed, queried by ID, and filtered by document.

#### Scenario: List all consent records
- GIVEN multiple consent records exist across several documents
- WHEN GET /api/consents is called
- THEN all consent records are returned as serialized arrays
- AND each record includes all status fields and entity information

#### Scenario: Get consent by ID
- GIVEN a consent record with UUID "abc-123"
- WHEN GET /api/consents/abc-123 is called
- THEN the specific consent record is returned with all fields

#### Scenario: Get consents for a specific document
- GIVEN a document with 5 detected entities and 5 consent records
- WHEN GET /api/consents/document/{documentId} is called
- THEN all 5 consent records linked to that document are returned

#### Scenario: Register/schema not configured
- GIVEN the publicationConsent register and schema are not configured in settings
- WHEN any consent endpoint is called
- THEN a 400 error is returned with message "PublicationConsent register/schema not configured"

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-020 | All consent records can be listed via `GET /api/consents` | MUST | Implemented |
| CONS-021 | A specific consent record can be retrieved via `GET /api/consents/{id}` | MUST | Implemented |
| CONS-022 | Consent records for a document can be queried via `GET /api/consents/document/{documentId}` | MUST | Implemented |
| CONS-023 | Consent listing requires the publicationConsent register and schema to be configured | MUST | Implemented |
| CONS-024 | If register/schema is not configured, a 400 error is returned | MUST | Implemented |

### Requirement: WOO Objection Period Compliance

**ID:** REQ-CONS-04
**Priority:** Must

The objection period complies with Wet Open Overheid requirements for a minimum 4-week notification period before publication.

#### Scenario: Default 28-day objection period
- GIVEN DocuDesk is configured with the default objection period
- WHEN a consent record is created
- THEN the objectionDeadline is 28 days from creation date
- AND this satisfies the WOO minimum 4-week requirement

#### Scenario: Check if objection deadline has passed
- GIVEN a consent record created 30 days ago with a 28-day objection period
- WHEN checkObjectionDeadline() is called
- THEN it returns true (deadline has passed)
- AND the publication decision can proceed

#### Scenario: Objection deadline not yet passed
- GIVEN a consent record created 14 days ago with a 28-day objection period
- WHEN checkObjectionDeadline() is called
- THEN it returns false (deadline has not passed)
- AND publication should wait until the deadline expires

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-030 | The objection period defaults to 28 days per WOO requirements | MUST | Implemented |
| CONS-031 | The objection period is configurable via admin settings | MUST | Implemented |
| CONS-032 | The objection deadline is stored as ISO 8601 datetime | MUST | Implemented |

### Requirement: Controller Read Path Architecture

**ID:** REQ-CONS-05
**Priority:** Must

ConsentController uses different service paths for read vs. write operations -- reading directly via ObjectService, writing via ConsentService.

#### Scenario: Controller lists consents via ObjectService
- GIVEN the consent endpoint is called for listing
- WHEN ConsentController::index() processes the request
- THEN it calls `settingsService->getAllSettings()` to get register/schema
- AND calls `settingsService->getObjectService()` to get ObjectService directly
- AND queries ObjectService via `searchObjects()` bypassing ConsentService

#### Scenario: Controller updates consent via ConsentService
- GIVEN a consent update request is received
- WHEN ConsentController::update() processes the request
- THEN it delegates to `ConsentService::updateConsentStatus()`
- AND the update goes through the full service layer

#### Scenario: Controller queries by document via ConsentService
- GIVEN a document-specific consent query is received
- WHEN ConsentController::byDocument() processes the request
- THEN it delegates to `ConsentService::getConsentsByDocument()`

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-040 | `ConsentController::index()` queries ObjectService directly via SettingsService | MUST | Implemented |
| CONS-041 | `ConsentController::show()` queries ObjectService directly via SettingsService | MUST | Implemented |
| CONS-042 | `ConsentController::update()` delegates to ConsentService | MUST | Implemented |
| CONS-043 | `ConsentController::byDocument()` delegates to ConsentService | MUST | Implemented |

### Requirement: RBAC and Multitenancy Configuration

**ID:** REQ-CONS-06
**Priority:** Must

All consent ObjectService calls currently bypass RBAC and multitenancy, which is a known security concern for multi-tenant deployments.

#### Scenario: Single-tenant deployment
- GIVEN a single-tenant Nextcloud deployment
- WHEN consent operations are performed with `_rbac: false` and `_multitenancy: false`
- THEN all authenticated users can access all consent records
- AND this is acceptable for single-tenant use

#### Scenario: Multi-tenant deployment concern
- GIVEN a multi-tenant Nextcloud deployment with multiple organizations
- WHEN consent operations bypass RBAC and multitenancy
- THEN users from Organization A could view and modify consent records of Organization B
- AND this is a security concern that must be addressed

#### Scenario: RBAC bypass scope
- GIVEN ConsentService and ConsentController perform consent operations
- WHEN any create, read, update operation is executed
- THEN `_rbac: false` and `_multitenancy: false` are passed to ObjectService
- AND no role-based access control is applied

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-044 | All ConsentService ObjectService calls bypass RBAC (`_rbac: false`) | MUST | Bug |
| CONS-045 | All ConsentService ObjectService calls bypass multitenancy (`_multitenancy: false`) | MUST | Bug |
| CONS-046 | ConsentController::show() bypasses RBAC when querying directly | MUST | Bug |

### Requirement: Consent Creation API Gap

**ID:** REQ-CONS-07
**Priority:** Must

ConsentService::createConsentRequest() exists but has no REST API endpoint or automated trigger, making consent records impossible to create via the frontend.

#### Scenario: Attempt to create consent via API
- GIVEN a frontend user wants to create a consent record for a detected entity
- WHEN they look for a POST /api/consents endpoint
- THEN no such endpoint exists
- AND consent records cannot be created via the REST API

#### Scenario: Event listener does not create consents
- GIVEN the DocuDeskEventListener handles ObjectCreated events
- WHEN an object is created in OpenRegister
- THEN only metadata enrichment is performed
- AND ConsentService is NOT imported or called by the event listener

#### Scenario: Programmatic consent creation works
- GIVEN internal PHP code has access to ConsentService
- WHEN createConsentRequest() is called directly
- THEN a consent record is created successfully in OpenRegister
- AND all status fields are initialized to "pending"

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-047 | `ConsentService::createConsentRequest()` exists as a public method | MUST | Implemented |
| CONS-048 | No API endpoint exists for creating consent records | MUST | Dead Code |
| CONS-049 | The event listener does NOT trigger consent creation | MUST | Implemented |
| CONS-050 | Consent records cannot currently be created via REST API or UI | MUST | Bug |

### Requirement: Objection Period Configuration Reading

**ID:** REQ-CONS-08
**Priority:** Must

ConsentService reads the objection period directly from IAppConfig, bypassing SettingsService.

#### Scenario: Read objection period from config
- GIVEN the objection period is configured as 42 days in IAppConfig
- WHEN ConsentService::getObjectionPeriodDays() is called (via ObjectionDeadlineChecker)
- THEN it reads directly from IAppConfig key 'publication_objection_period_days'
- AND returns 42

#### Scenario: Default objection period
- GIVEN no custom objection period is configured
- WHEN getObjectionPeriodDays() is called
- THEN it returns the default of 28 days (hardcoded in getValueString)

#### Scenario: Config key duplication risk
- GIVEN SettingsService also reads the same config key in getAllSettings()
- WHEN the config key name would change in one place
- THEN the other place would break silently
- AND this duplication is a maintenance risk

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-051 | ConsentService reads objection period directly from IAppConfig | MUST | Implemented |
| CONS-052 | Default objection period is 28 days (hardcoded in getValueString default) | MUST | Implemented |

### Requirement: Duplicated ObjectService Resolution Pattern

**ID:** REQ-CONS-09
**Priority:** Must

ConsentService and ObjectionDeadlineChecker have their own private getObjectService() methods duplicating the pattern found in SettingsService.

#### Scenario: ConsentService resolves ObjectService
- GIVEN ConsentService needs to create a consent record
- WHEN it calls its private `getObjectService()`
- THEN the same `getInstalledApps()` + `container->get()` pattern is used
- AND this duplicates the public SettingsService::getObjectService()

#### Scenario: ObjectionDeadlineChecker resolves ObjectService
- GIVEN ObjectionDeadlineChecker needs to check a deadline
- WHEN it calls its private `getObjectService()`
- THEN the same pattern is used again
- AND this is the third duplication of the same code

#### Scenario: OpenRegister unavailable
- GIVEN OpenRegister is not installed
- WHEN any duplicated getObjectService() is called
- THEN a RuntimeException is thrown with the same pattern across all services

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-053 | ConsentService has its own private `getObjectService()` duplicating SettingsService pattern | MUST | Implemented |
| CONS-054 | ObjectionDeadlineChecker has its own private `getObjectService()` with the same pattern | MUST | Implemented |

### Requirement: Consent UI

**ID:** REQ-CONS-10
**Priority:** Must

The consent management UI provides a list view with statistics and a detail view for editing consent records.

#### Scenario: View consent statistics
- GIVEN 12 consent records exist (3 pending, 7 approved, 2 objected)
- WHEN the ConsentIndex view loads
- THEN stat cards display Total: 12, Pending: 3, Approved: 7, Objected: 2
- AND cards are color-coded (pending: orange, approved: green, objected: red)

#### Scenario: Click consent to view details
- GIVEN the consent list is displayed
- WHEN the user clicks on a consent row
- THEN the ConsentDetail view is displayed
- AND entity information, consent status dropdowns, and objection details are shown
- AND a Save button allows updating the consent record

#### Scenario: Empty consent list
- GIVEN no consent records exist
- WHEN the ConsentIndex view loads
- THEN NcEmptyContent is displayed with AccountCheck icon
- AND guidance text indicates no records exist yet

#### Scenario: Consent store state management
- GIVEN the consent Pinia store is initialized
- WHEN consents are fetched
- THEN the store provides getters: pendingConsents, approvedConsents, objectedConsents, consentStats
- AND actions: fetchConsents, fetchConsent, updateConsent, fetchConsentsByDocument

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-055 | Consent list view shows statistics with color-coded cards | MUST | Implemented |
| CONS-056 | Consent detail view shows editable status dropdowns | MUST | Implemented |
| CONS-057 | No automated consent creation exists -- manual or future automation needed | MUST | Implemented |

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
| objectionDeadline | datetime | No | Deadline for objections (WOO: min 4 weeks) |
| objectionReceivedAt | datetime | No | When objection was received |
| objectionReason | string (markdown) | No | Reason for objection (max 2000 chars) |
| publicationDecision | string (enum) | Yes | `pending`, `anonymize`, `publish_with_consent`, `publish_anonymized`, `reject` |
| legalBasis | string | No | Legal basis for publication (max 500 chars) |
| notes | string (markdown) | No | Internal process notes (max 2000 chars) |

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/consents` | List all consent records |
| GET | `/api/consents/{id}` | Get a specific consent record |
| PUT | `/api/consents/{id}` | Update a consent record |
| GET | `/api/consents/document/{documentId}` | Get consents for a specific document |

## Dependencies

- **OpenRegister ObjectService**: CRUD operations on consent records
- **Nextcloud IAppConfig**: Storing objection period and register/schema configuration
- **SettingsService**: Register/schema configuration and ObjectService access
- **ConsentService**: Write operations and document-scoped queries
- **ObjectionDeadlineChecker**: Deadline calculation and checking
- **ConsentUpdateHandler**: Update and query delegation
- **ConsentCrudService**: Controller-level CRUD operations

### Current Implementation Status
- **Implemented** with file paths:
  - `lib/Service/ConsentService.php` -- consent creation, update, deadline checking
  - `lib/Service/ConsentCrudService.php` -- controller-level CRUD operations
  - `lib/Service/ConsentUpdateHandler.php` -- update and query delegation
  - `lib/Service/ObjectionDeadlineChecker.php` -- deadline calculation and checking
  - `lib/Controller/ConsentController.php` -- REST API endpoints
  - `src/views/consent/ConsentIndex.vue` -- consent listing with stats
  - `src/views/consent/ConsentDetail.vue` -- consent detail/edit view
  - `src/store/modules/consent.js` -- Pinia store
  - `lib/Settings/docudesk_register.json` -- PublicationConsent schema
- **Known issues**:
  - No POST /api/consents endpoint (CONS-048/050)
  - RBAC/multitenancy disabled (CONS-044/045/046)
  - No automated consent creation from entity detection

### Standards & References
- **GDPR/AVG Articles 6, 7, 21**: Consent management and right to object
- **WOO (Wet open overheid) Article 4.4**: Minimum 4-week objection period
- **ISO 8601**: Objection deadline datetime format

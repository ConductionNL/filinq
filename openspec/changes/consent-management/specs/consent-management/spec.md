---
status: draft
---

# Consent Management

## Purpose

Defines GDPR-compliant publication consent tracking for entities (persons and organizations) detected in documents. When a document is destined for publication under the Wet Open Overheid (WOO), affected entities must be notified and given an objection period (minimum 4 weeks per WOO Art. 4.4). This spec covers the full consent lifecycle: creation, notification tracking, objection handling, and publication decision-making. All consent records are stored as `PublicationConsent` objects in OpenRegister. This spec also captures three known gaps and their fixes: RBAC bypass on ObjectService calls, missing creation REST endpoint, and duplicated ObjectService resolution infrastructure.

## Data Model

### PublicationConsent schema

Stored in OpenRegister via the register and schema configured in admin settings (`publicationConsentRegister`, `publicationConsentSchema`). Defined in `lib/Settings/docudesk_register.json`.

| Field | Type | Required | Description |
|---|---|---|---|
| `documentId` | string | Yes | Reference to the document being published |
| `entityType` | string (enum) | Yes | `PERSON` or `ORGANIZATION` |
| `entityText` | string (max 500) | Yes | Detected entity text from the document |
| `entityKey` | string (max 50) | No | Stable anonymization key for this entity |
| `contactEmail` | string (email) | No | Email address for notification |
| `contactAddress` | string (max 500) | No | Postal address for notification |
| `notificationStatus` | string (enum) | Yes | `pending` \| `sent` \| `delivered` \| `failed` \| `skipped` |
| `notificationSentAt` | datetime | No | ISO 8601 datetime when notification was sent |
| `consentStatus` | string (enum) | Yes | `pending` \| `consent_given` \| `objection_received` \| `no_response` \| `anonymized` |
| `objectionDeadline` | datetime | No | ISO 8601 deadline for objections (WOO: min 4 weeks) |
| `objectionReceivedAt` | datetime | No | ISO 8601 datetime when objection was received |
| `objectionReason` | string/markdown (max 2000) | No | Reason for objection |
| `publicationDecision` | string (enum) | Yes | `pending` \| `anonymize` \| `publish_with_consent` \| `publish_anonymized` \| `reject` |
| `legalBasis` | string (max 500) | No | Legal basis for publication |
| `notes` | string/markdown (max 2000) | No | Internal process notes |

## API Endpoints

| Method | Path | Auth | Description |
|---|---|---|---|
| `GET` | `/api/consents` | User | List all consent records |
| `POST` | `/api/consents` | Admin | Create a consent record |
| `GET` | `/api/consents/{id}` | User | Get a specific consent record |
| `PUT` | `/api/consents/{id}` | Admin | Update a consent record |
| `GET` | `/api/consents/document/{documentId}` | User | Get all consents for a document |

## Requirements

### REQ-CONS-01: Consent record creation (Priority: Must)

Consent records are created for detected entities in documents, initialized with `pending` status on all three status fields and an automatic objection deadline.

#### Scenario: Create consent for a detected person

- GIVEN a document with detected PERSON entities
- WHEN `ConsentService::createConsentRequest()` is called for entity "Jan de Vries"
- THEN a `PublicationConsent` object is stored in OpenRegister
- AND `consentStatus` is `pending`
- AND `notificationStatus` is `pending`
- AND `publicationDecision` is `pending`
- AND `objectionDeadline` is set to current date + configured objection period days

#### Scenario: Create consent with extra contact data

- GIVEN a document entity with additional contact information
- WHEN `createConsentRequest()` is called with extra fields `contactEmail` and `contactAddress`
- THEN the extra fields are merged into the consent record
- AND the base consent data (statuses, deadline) is preserved

#### Scenario: Custom objection period

- GIVEN the admin has configured an objection period of 42 days
- WHEN a consent request is created
- THEN `objectionDeadline` is set to current date + 42 days
- AND the deadline is stored in ISO 8601 datetime format

| ID | Requirement | Priority | Status |
|---|---|---|---|
| CONS-001 | Consent records can be created for detected entities via `ConsentService` | Must | Implemented |
| CONS-002 | Each consent record links to a document via `documentId` | Must | Implemented |
| CONS-003 | Each consent record captures `entityType` (PERSON or ORGANIZATION) and `entityText` | Must | Implemented |
| CONS-004 | Consent records are initialized with `pending` for `notificationStatus`, `consentStatus`, and `publicationDecision` | Must | Implemented |
| CONS-005 | An objection deadline is automatically calculated based on the configurable objection period (default 28 days) | Must | Implemented |
| CONS-006 | Consent records are stored in OpenRegister via `ObjectService` using the configured register and schema | Must | Implemented |

### REQ-CONS-02: Consent status lifecycle (Priority: Must)

Consent records progress through defined status transitions for `consentStatus`, `notificationStatus`, and `publicationDecision`.

#### Scenario: Update consent status to consent_given

- GIVEN a pending consent record
- WHEN an administrator updates `consentStatus` to `consent_given`
- THEN the consent record is updated in OpenRegister
- AND the updated record is returned with the new status

#### Scenario: Record an objection

- GIVEN a consent record with `consentStatus` `pending`
- WHEN the entity submits an objection with a reason
- THEN `consentStatus` transitions to `objection_received`
- AND `objectionReceivedAt` is set to the current datetime
- AND `objectionReason` stores the provided text

#### Scenario: Notification delivery tracking

- GIVEN a consent record with `notificationStatus` `pending`
- WHEN the notification is sent successfully
- THEN `notificationStatus` transitions to `sent`
- AND when delivery is confirmed it transitions to `delivered`
- AND `notificationSentAt` is set to the send datetime

#### Scenario: Publication decision after objection period

- GIVEN a consent record where the objection deadline has passed
- AND `consentStatus` is `no_response`
- WHEN the administrator makes a publication decision
- THEN `publicationDecision` can be set to `publish_anonymized` or `publish_with_consent`

| ID | Requirement | Priority | Status |
|---|---|---|---|
| CONS-010 | `consentStatus` transitions: `pending` → `consent_given`, `objection_received`, `no_response`, or `anonymized` | Must | Implemented |
| CONS-011 | `notificationStatus` transitions: `pending` → `sent` → `delivered` or `failed`; can also be `skipped` | Must | Implemented |
| CONS-012 | `publicationDecision` options: `pending`, `anonymize`, `publish_with_consent`, `publish_anonymized`, `reject` | Must | Implemented |
| CONS-013 | Consent status can be updated via `PUT /api/consents/{id}` | Must | Implemented |
| CONS-014 | Objection deadline expiry can be checked via `ConsentService::checkObjectionDeadline()` | Must | Implemented |

### REQ-CONS-03: Consent listing and querying (Priority: Must)

Consent records can be listed, retrieved by ID, and filtered by document. The `publicationConsent` register and schema must be configured in settings before any consent endpoint can respond successfully.

#### Scenario: List all consent records

- GIVEN multiple consent records exist across several documents
- WHEN `GET /api/consents` is called
- THEN all consent records are returned as serialized arrays
- AND each record includes all status fields and entity information

#### Scenario: Get consent by ID

- GIVEN a consent record with UUID "abc-123"
- WHEN `GET /api/consents/abc-123` is called
- THEN the specific consent record is returned with all fields

#### Scenario: Get consents for a specific document

- GIVEN a document with 5 detected entities and 5 consent records
- WHEN `GET /api/consents/document/{documentId}` is called
- THEN all 5 consent records linked to that document are returned

#### Scenario: Register/schema not configured

- GIVEN the `publicationConsent` register and schema are not configured in admin settings
- WHEN any consent endpoint is called
- THEN the response is HTTP 400
- AND the body contains message `PublicationConsent register/schema not configured`

| ID | Requirement | Priority | Status |
|---|---|---|---|
| CONS-020 | All consent records can be listed via `GET /api/consents` | Must | Implemented |
| CONS-021 | A specific consent record can be retrieved via `GET /api/consents/{id}` | Must | Implemented |
| CONS-022 | Consent records for a document can be queried via `GET /api/consents/document/{documentId}` | Must | Implemented |
| CONS-023 | Consent listing requires the `publicationConsent` register and schema to be configured | Must | Implemented |
| CONS-024 | If register/schema is not configured, a 400 error is returned | Must | Implemented |

### REQ-CONS-04: WOO objection period compliance (Priority: Must)

The objection period satisfies Wet Open Overheid Art. 4.4 requirements: minimum 4 weeks (28 days) before publication.

#### Scenario: Default 28-day objection period

- GIVEN DocuDesk is configured with the default objection period
- WHEN a consent record is created
- THEN `objectionDeadline` is 28 days from the creation date
- AND this satisfies the WOO minimum 4-week requirement

#### Scenario: Objection deadline has passed

- GIVEN a consent record created 30 days ago with a 28-day objection period
- WHEN `checkObjectionDeadline()` is called
- THEN it returns `true` (deadline has passed)
- AND the publication decision can proceed

#### Scenario: Objection deadline not yet passed

- GIVEN a consent record created 14 days ago with a 28-day objection period
- WHEN `checkObjectionDeadline()` is called
- THEN it returns `false` (deadline has not passed)
- AND publication must wait until the deadline expires

| ID | Requirement | Priority | Status |
|---|---|---|---|
| CONS-030 | The objection period defaults to 28 days per WOO requirements | Must | Implemented |
| CONS-031 | The objection period is configurable via admin settings | Must | Implemented |
| CONS-032 | `objectionDeadline` is stored as ISO 8601 datetime | Must | Implemented |

### REQ-CONS-05: Controller read path architecture (Priority: Must)

`ConsentController` uses different service paths for read vs. write operations: reading directly via `ObjectService` (obtained through `SettingsService`), writing via `ConsentService`.

#### Scenario: Controller lists consents via ObjectService

- GIVEN the consent list endpoint is called
- WHEN `ConsentController::index()` processes the request
- THEN it calls `settingsService->getAllSettings()` to obtain the register/schema configuration
- AND calls `settingsService->getObjectService()` to get `ObjectService` directly
- AND queries `ObjectService` via `searchObjects()` without going through `ConsentService`

#### Scenario: Controller updates consent via ConsentService

- GIVEN a consent update request is received
- WHEN `ConsentController::update()` processes the request
- THEN it delegates to `ConsentService::updateConsentStatus()`
- AND the update goes through the full service layer

#### Scenario: Controller queries by document via ConsentService

- GIVEN a document-specific consent query is received
- WHEN `ConsentController::byDocument()` processes the request
- THEN it delegates to `ConsentService::getConsentsByDocument()`

| ID | Requirement | Priority | Status |
|---|---|---|---|
| CONS-040 | `ConsentController::index()` queries `ObjectService` directly via `SettingsService` | Must | Implemented |
| CONS-041 | `ConsentController::show()` queries `ObjectService` directly via `SettingsService` | Must | Implemented |
| CONS-042 | `ConsentController::update()` delegates to `ConsentService` | Must | Implemented |
| CONS-043 | `ConsentController::byDocument()` delegates to `ConsentService` | Must | Implemented |

### REQ-CONS-06: RBAC and multitenancy enforcement (Priority: Must)

All consent ObjectService calls MUST enforce RBAC and multitenancy. The prior behavior of bypassing both is a security bug (see CONS-044/045/046).

#### Scenario: RBAC-enforced consent access in multi-tenant deployment

- GIVEN a multi-tenant Nextcloud deployment with two organizations
- WHEN consent operations are performed with `_rbac: true` and `_multitenancy: true`
- THEN users from Organization A can only access consent records scoped to their tenant
- AND users from Organization B cannot view or modify Organization A's consent records

#### Scenario: Consent operations pass RBAC and multitenancy flags

- GIVEN `ConsentService`, `ConsentCrudService`, and `ConsentController` perform consent operations
- WHEN any create, read, or update operation calls `ObjectService`
- THEN `_rbac: true` and `_multitenancy: true` are passed on every call

#### Scenario: Single-tenant deployment is unaffected

- GIVEN a single-tenant Nextcloud deployment with no RBAC groups configured
- WHEN consent operations are performed with `_rbac: true` and `_multitenancy: true`
- THEN all authenticated users can access all consent records (same behavior as before)
- AND no functional regression occurs

| ID | Requirement | Priority | Status |
|---|---|---|---|
| CONS-044 | All `ConsentService` ObjectService calls pass `_rbac: true` | Must | **Fix required** |
| CONS-045 | All `ConsentService` ObjectService calls pass `_multitenancy: true` | Must | **Fix required** |
| CONS-046 | `ConsentController::show()` passes `_rbac: true` when querying directly | Must | **Fix required** |

### REQ-CONS-07: Consent creation REST endpoint (Priority: Must)

`ConsentService::createConsentRequest()` MUST be exposed via `POST /api/consents`. The absence of this endpoint makes consent records impossible to create from the frontend.

#### Scenario: Create consent record via POST /api/consents

- GIVEN a frontend user with admin role wants to create a consent record
- WHEN they call `POST /api/consents` with body `{documentId, entityType, entityText}`
- THEN `ConsentService::createConsentRequest()` is called
- AND a `PublicationConsent` object is created in OpenRegister
- AND the response is HTTP 201 with the created object

#### Scenario: Missing required fields returns 400

- GIVEN a `POST /api/consents` request with body missing `entityType`
- WHEN the controller validates the request
- THEN the response is HTTP 400
- AND the body identifies the missing required field

#### Scenario: Event listener does not create consents

- GIVEN the `DocuDeskEventListener` handles ObjectCreated events from OpenRegister
- WHEN an object is created in OpenRegister
- THEN only metadata enrichment is performed
- AND `ConsentService` is NOT called by the event listener

| ID | Requirement | Priority | Status |
|---|---|---|---|
| CONS-047 | `ConsentService::createConsentRequest()` exists as a public method | Must | Implemented |
| CONS-048 | `POST /api/consents` endpoint is available for creating consent records | Must | **Fix required** |
| CONS-049 | The event listener does NOT trigger consent creation | Must | Implemented |
| CONS-050 | Consent records can be created via REST API | Must | **Fix required** |

### REQ-CONS-08: Objection period configuration via SettingsService (Priority: Must)

`ConsentService` MUST read the objection period through `SettingsService::getAllSettings()`. Reading directly from `IAppConfig` duplicates the same key read already performed by `SettingsService` and creates a silent breakage risk when the config key is renamed.

#### Scenario: Objection period read via SettingsService

- GIVEN the objection period is configured as 42 days
- WHEN `ConsentService::getObjectionPeriodDays()` is called
- THEN it reads from `SettingsService::getAllSettings()['publicationObjectionPeriodDays']`
- AND returns 42

#### Scenario: Default objection period when not configured

- GIVEN no custom objection period is configured
- WHEN `getObjectionPeriodDays()` is called
- THEN it returns the default of 28 days

#### Scenario: Config key renamed in one place

- GIVEN `SettingsService` and `ConsentService` both delegate to `SettingsService::getAllSettings()`
- WHEN the config key name is updated in `SettingsService`
- THEN both callers receive the change automatically
- AND no silent divergence occurs

| ID | Requirement | Priority | Status |
|---|---|---|---|
| CONS-051 | `ConsentService` reads objection period via `SettingsService::getAllSettings()` | Must | **Fix required** |
| CONS-052 | Default objection period is 28 days when not configured | Must | Implemented |

### REQ-CONS-09: ObjectService resolution via SettingsService (Priority: Must)

`ConsentService` and `ObjectionDeadlineChecker` MUST obtain `ObjectService` by delegating to `SettingsService::getObjectService()`. Private `getObjectService()` implementations that duplicate the `getInstalledApps()` + `container->get()` pattern MUST be removed.

#### Scenario: ConsentService delegates ObjectService resolution

- GIVEN `ConsentService` needs to create a consent record
- WHEN it needs an `ObjectService` instance
- THEN it calls `settingsService->getObjectService()` directly
- AND there is no private `getObjectService()` method in `ConsentService`

#### Scenario: ObjectionDeadlineChecker delegates ObjectService resolution

- GIVEN `ObjectionDeadlineChecker` needs to check a deadline
- WHEN it needs an `ObjectService` instance
- THEN it calls `settingsService->getObjectService()` directly
- AND there is no private `getObjectService()` method in `ObjectionDeadlineChecker`

#### Scenario: OpenRegister unavailable

- GIVEN OpenRegister is not installed
- WHEN `SettingsService::getObjectService()` is called
- THEN a `RuntimeException` is thrown with a consistent message
- AND `ConsentService` and `ObjectionDeadlineChecker` propagate this exception rather than suppressing it

| ID | Requirement | Priority | Status |
|---|---|---|---|
| CONS-053 | `ConsentService` delegates ObjectService resolution to `SettingsService::getObjectService()` | Must | **Fix required** |
| CONS-054 | `ObjectionDeadlineChecker` delegates ObjectService resolution to `SettingsService::getObjectService()` | Must | **Fix required** |

### REQ-CONS-10: Consent management UI (Priority: Must)

The consent management UI provides a list view with statistics and a detail view for editing consent records. All UI strings MUST be internationalised (ADR-007: English keys, Dutch translations in `nl.json`). Color-coded status indicators MUST use NL Design System tokens (ADR-010).

#### Scenario: View consent statistics

- GIVEN 12 consent records exist (3 pending, 7 consent_given, 2 objection_received)
- WHEN the `ConsentIndex` view loads
- THEN stat cards display Total: 12, Pending: 3, Approved: 7, Objected: 2
- AND cards are color-coded using NL Design System tokens (pending: warning, approved: success, objected: error)

#### Scenario: Click consent to view details

- GIVEN the consent list is displayed
- WHEN the user clicks on a consent row
- THEN the `ConsentDetail` view is displayed
- AND entity information, consent status dropdowns, and objection details are shown
- AND a Save button allows updating the consent record via `PUT /api/consents/{id}`

#### Scenario: Empty consent list

- GIVEN no consent records exist
- WHEN the `ConsentIndex` view loads
- THEN `NcEmptyContent` is displayed with an AccountCheck icon
- AND guidance text indicates no consent records exist yet

#### Scenario: Consent store state management

- GIVEN the Pinia consent store is initialized
- WHEN consents are fetched
- THEN the store provides computed getters: `pendingConsents`, `approvedConsents`, `objectedConsents`, `consentStats`
- AND actions: `fetchConsents`, `fetchConsent`, `updateConsent`, `fetchConsentsByDocument`

| ID | Requirement | Priority | Status |
|---|---|---|---|
| CONS-055 | Consent list view shows statistics with NL Design System color-coded cards | Must | Implemented |
| CONS-056 | Consent detail view shows editable status dropdowns | Must | Implemented |
| CONS-057 | All UI strings are internationalised with English keys and Dutch translations | Must | Implemented |

## Standards and References

- **GDPR/AVG Articles 6, 7, 21** — Consent management and right to object
- **WOO (Wet open overheid) Article 4.4** — Minimum 4-week objection period before publication
- **ISO 8601** — Objection deadline and notification datetime format
- **ADR-002** — API design rules (URL patterns, error responses, auth)
- **ADR-003** — Backend architecture (controller → service, DI, admin auth via `IGroupManager`)
- **ADR-007** — i18n (English keys, Dutch translations, sentence case)
- **ADR-010** — NL Design System tokens for UI colors
- **ADR-011** — Schema standards (no custom property names when schema.org equivalent exists)
- **ADR-015** — Common patterns (ObjectService 3-arg API, RBAC/auth enforcement, error responses)

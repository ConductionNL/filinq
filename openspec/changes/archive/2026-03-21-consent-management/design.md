# Design: Consent Management

## Architecture

### Backend
- `ConsentController` provides CRUD endpoints for consent records
- `ConsentCrudService` handles controller-level CRUD operations
- `ConsentService` implements consent business logic (create, update status, lifecycle)
- `ConsentUpdateHandler` validates consent status transitions
- `ObjectionDeadlineChecker` calculates and validates objection deadlines
- All consent records stored as OpenRegister objects (PublicationConsent schema)

### Frontend
- `ConsentIndex.vue` displays consent records with stats cards (Total, Pending, Approved, Objected)
- `ConsentDetail.vue` shows single consent record with editable status fields
- Pinia store `consent.js` manages consent state

### Data Model (PublicationConsent Schema)
- `documentId`: Reference to the analyzed document
- `entityType`: PERSON or ORGANIZATION
- `entityText`: The detected entity text
- `consentStatus`: pending | consent_given | no_response | anonymized
- `notificationStatus`: pending | sent | delivered | failed | skipped
- `publicationDecision`: pending | publish_with_consent | publish_anonymized | reject
- `objectionDeadline`: ISO 8601 datetime (current date + configured days)

## ADR Compliance
- ADR-001: All data via OpenRegister ObjectService
- ADR-003: NL Design tokens for status color coding
- ADR-008: Controller -> CrudService -> ConsentService layering

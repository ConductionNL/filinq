# document-signing Specification

## Purpose
TBD - created by archiving change digital-signing-integration. Update Purpose after archive.

@e2e exclude Backend signing API + eIDAS crypto + status machine + OR schema/audit contracts; no navigable UI surface. Covered by PHPUnit (SignatureService, status transitions, audit immutability) and Newman (/api/signing/* contracts).

## Requirements
### Requirement: Signing request creation
The system SHALL allow authenticated users to create a signing request for a document. A signing request specifies the document (Nextcloud file ID), the signature level (SES, AdES, or QES), the signing mode (sequential or parallel), and an ordered list of signers. The signing request SHALL be stored as an OpenRegister object via ObjectService using the SigningRequest schema.

#### Scenario: Create a signing request for a single signer
- **WHEN** a user submits a POST to `/api/signing/requests` with a file ID, signature level "SES", mode "sequential", and one signer
- **THEN** a SigningRequest object is created in OpenRegister with status "PENDING"
- **AND** a SignerRecord object is created with status "PENDING"
- **AND** the document file is locked for editing via ILockManager
- **AND** a SigningAuditEntry is created recording the initiation

#### Scenario: Create a signing request with sequential multi-signer
- **WHEN** a user creates a signing request with signers [A, B, C] in sequential mode
- **THEN** signer A receives a Nextcloud notification to sign
- **AND** signers B and C do not receive notifications until the previous signer completes

#### Scenario: Create a signing request with parallel multi-signer
- **WHEN** a user creates a signing request with signers [A, B, C] in parallel mode
- **THEN** all three signers receive Nextcloud notifications simultaneously
- **AND** each signer can sign independently of the others

### Requirement: Signing request lifecycle management
The system SHALL enforce a strict status machine for signing requests: DRAFT -> PENDING -> IN_PROGRESS -> COMPLETED | DECLINED | EXPIRED | CANCELLED. Invalid transitions SHALL be rejected with an error response.

#### Scenario: Sequential signing progresses through signers
- **WHEN** signer A signs in a sequential request with signers [A, B, C]
- **THEN** signer A's SignerRecord status changes to "SIGNED" with timestamp
- **AND** signer B receives a notification to sign
- **AND** the SigningRequest status changes to "IN_PROGRESS"
- **AND** a SigningAuditEntry records signer A's signature

#### Scenario: All signers complete
- **WHEN** the last signer in a request signs the document
- **THEN** the SigningRequest status changes to "COMPLETED"
- **AND** the document file lock is released
- **AND** the signed PDF is stored as a new file version in Nextcloud

#### Scenario: Signer declines
- **WHEN** a signer declines a signing request with a reason
- **THEN** the SignerRecord status changes to "DECLINED" with the reason
- **AND** the SigningRequest status changes to "DECLINED"
- **AND** the request initiator receives a notification with the decline reason
- **AND** the document file lock is released

#### Scenario: Signing request expires
- **WHEN** a signing request passes its deadline without all signatures
- **THEN** the SigningRequest status changes to "EXPIRED"
- **AND** all pending SignerRecords are marked "EXPIRED"
- **AND** the document file lock is released

#### Scenario: Initiator cancels a signing request
- **WHEN** the initiator sends a DELETE to `/api/signing/requests/{id}`
- **THEN** the SigningRequest status changes to "CANCELLED"
- **AND** all pending SignerRecords are marked "CANCELLED"
- **AND** a SigningAuditEntry records the cancellation

### Requirement: Signature levels
The system SHALL support three eIDAS signature levels. The signature level is specified per signing request and determines the authentication and signing method used.

#### Scenario: Simple Electronic Signature (SES)
- **WHEN** a signer signs a document with level "SES"
- **THEN** the NativeSigningProvider applies a signature using the signer's Nextcloud user identity, current timestamp, and IP address
- **AND** the signature is embedded in the PDF document

#### Scenario: Advanced Electronic Signature (AdES)
- **WHEN** a signer signs a document with level "AdES"
- **THEN** the configured external signing provider handles the authentication (email verification + SMS OTP)
- **AND** an AdES-compliant signature with certificate is embedded in the PDF

#### Scenario: Qualified Electronic Signature (QES)
- **WHEN** a signer signs a document with level "QES"
- **THEN** the configured external signing provider handles PKIoverheid/eHerkenning authentication
- **AND** a QES signature with TSA timestamp is applied
- **AND** the signature is PAdES compliant

### Requirement: Pluggable signing provider interface
The system SHALL define a SigningProviderInterface that all signing providers implement. The active provider is selected based on admin configuration. The interface SHALL define methods for: initiating a signing flow, checking signing status, downloading the signed document, and cancelling a signing flow.

#### Scenario: Native provider handles SES signing
- **WHEN** the signing provider is set to "native" and a SES signing request is created
- **THEN** NativeSigningProvider processes the signing locally without external API calls
- **AND** the signed PDF is returned directly

#### Scenario: ValidSign provider handles external signing
- **WHEN** the signing provider is set to "validsign" and a signing request is created
- **THEN** ValidSignProvider sends the document to ValidSign via OpenConnector
- **AND** the signer receives an email from ValidSign with a signing link
- **AND** upon completion, the signed document is retrieved and stored in Nextcloud

#### Scenario: Provider not configured
- **WHEN** a signing request requires an external provider but none is configured
- **THEN** the system returns an error indicating the signing provider is not configured
- **AND** a warning is logged

### Requirement: Signing status tracking
The system SHALL provide a REST API endpoint to retrieve the current status of a signing request, including the status of each individual signer.

#### Scenario: View signing request status
- **WHEN** a user sends a GET to `/api/signing/requests/{id}`
- **THEN** the response includes: request status, document reference, signature level, list of signers with their individual status and timestamps, and any decline reasons

#### Scenario: List all signing requests
- **WHEN** a user sends a GET to `/api/signing/requests`
- **THEN** the response includes all signing requests the user has initiated or is a signer on
- **AND** each request includes summary status information

### Requirement: Bulk signing
The system SHALL allow users to sign multiple pending signing requests in a single authenticated session.

#### Scenario: Bulk sign multiple documents
- **WHEN** a user sends a POST to `/api/signing/bulk` with an array of signing request IDs
- **THEN** each document is signed in sequence using the user's current authentication
- **AND** each individual signing creates its own SigningAuditEntry
- **AND** the response includes the result (success/failure) for each request

### Requirement: Signature verification
The system SHALL provide the ability to verify signatures embedded in PDF documents, including checking certificate validity, detecting document tampering, and displaying signature details.

#### Scenario: Verify a signed document
- **WHEN** a user sends a GET to `/api/signing/verify/{fileId}`
- **THEN** the response lists all signatures with: signer identity, timestamp, signature level, and validity status
- **AND** tampered documents show an "INVALID" status with details

### Requirement: Signing audit trail
The system SHALL maintain an immutable audit trail for all signing-related events. Audit entries SHALL be stored as OpenRegister objects and SHALL NOT be modifiable or deletable through the API. The retention period SHALL be minimum 10 years per Archiefwet 1995.

#### Scenario: Audit trail records signing events
- **WHEN** any signing action occurs (create, sign, decline, cancel, expire)
- **THEN** a SigningAuditEntry is created with: action type, actor identity, timestamp, IP address, signature level, provider used, and document reference

#### Scenario: Audit trail is immutable
- **WHEN** a user attempts to update or delete a SigningAuditEntry via the API
- **THEN** the request is rejected with a 403 Forbidden response

#### Scenario: Retrieve audit trail for a signing request
- **WHEN** a user sends a GET to `/api/signing/requests/{id}/audit`
- **THEN** the response includes all audit entries for that signing request in chronological order

### Requirement: Signing request data model
The system SHALL store signing data using three OpenRegister schemas defined in `docudesk_register.json`.

#### Scenario: SigningRequest schema
- **WHEN** a signing request is created
- **THEN** the object contains: id, documentFileId, documentName, initiatorUserId, signatureLevel (SES|AdES|QES), signingMode (sequential|parallel), status (DRAFT|PENDING|IN_PROGRESS|COMPLETED|DECLINED|EXPIRED|CANCELLED), provider (native|validsign|docusign|adobesign|libresign), deadline (ISO 8601), signerIds (array), createdAt, updatedAt

#### Scenario: SignerRecord schema
- **WHEN** a signer record is created
- **THEN** the object contains: id, signingRequestId, userId, displayName, email, order (for sequential), status (PENDING|SIGNED|DECLINED|EXPIRED|CANCELLED), signedAt, declineReason, ipAddress, signatureData (base64)

#### Scenario: SigningAuditEntry schema
- **WHEN** an audit entry is created
- **THEN** the object contains: id, signingRequestId, action (CREATED|SIGNED|DECLINED|CANCELLED|EXPIRED|COMPLETED|VIEWED), actorUserId, actorDisplayName, timestamp, ipAddress, signatureLevel, provider, metadata (JSON)

### Requirement: Signing REST API
The system SHALL expose signing functionality via REST API endpoints registered in `appinfo/routes.php`.

#### Scenario: API endpoints are registered
- **WHEN** the DocuDesk app is loaded
- **THEN** the following routes are available:
  - POST `/api/signing/requests` (create signing request)
  - GET `/api/signing/requests` (list signing requests)
  - GET `/api/signing/requests/{id}` (get signing request details)
  - DELETE `/api/signing/requests/{id}` (cancel signing request)
  - POST `/api/signing/requests/{id}/sign` (sign a document)
  - POST `/api/signing/requests/{id}/decline` (decline a signing request)
  - POST `/api/signing/bulk` (bulk sign)
  - GET `/api/signing/verify/{fileId}` (verify signatures)
  - GET `/api/signing/requests/{id}/audit` (get audit trail)


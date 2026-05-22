# Design: eIDAS Qualified Electronic Signature (QES)

## Architecture Overview

The QES implementation is modeled as a state machine on three core entities plus configuration and audit objects, all stored in the `docudesk_signatures` OpenRegister:

1. **signature_request**: Top-level request object tracking the overall signing flow, document being signed, assurance level, and state
2. **signature_invitation**: Per-signer invitation object tracking authentication status, role, and signing order
3. **signature_artefact**: Immutable completed signature with embedded LTV data
4. **qtsp_configuration**: QTSP adapter configuration (QuoVadis, KPN ID, Digidentity, Connectis, extensible)
5. **audit_event**: Append-only event log for compliance and verification

The QTSP adapter layer (`IQtspAdapter`) abstracts authentication ceremony orchestration, certificate retrieval, and signature-format normalization. Each QTSP exposes a different user journey (embedded iframe, redirect, popup) configured at instance setup.

**Data Flow:**
1. Requester creates a `signature_request` in draft state with document reference and frozen hash
2. Requester previews signer list and confirms; invitations are created in `pending` state
3. Invitations are dispatched via email with one-time signing links
4. Signer clicks link, views document preview, is redirected to QTSP ceremony (embedded/redirect/popup)
5. QTSP authenticates signer at declared LoA, obtains signature from QSCD, returns completion callback
6. Docudesk webhook handler receives callback, obtains qualified timestamp from TSA, creates `signature_artefact`
7. Background job refreshes LTV revocation info periodically; verification job re-validates on schedule
8. Requester or compliance officer views audit trail and verification history

## Entity Definitions (OpenRegister Schemas)

### SignatureRequest

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "SignatureRequest",
    "slug": "signature-request"
  },
  "id": "uuid",
  "documentId": "uuid (frozen at creation)",
  "documentVersion": "string (immutable docudesk version pointer)",
  "documentHash": "string (SHA-256 hex of bytes being signed)",
  "previewUrl": "string (expires after signing, QTSP access only)",
  "assuranceLevel": "enum: qes | aes",
  "signatureFormat": "enum: pades-b-lta | xades-b-lta | cades-b-lta",
  "signingOrder": "enum: parallel | sequential",
  "qtspId": "string (quovadis | kpnid | digidentity | connectis | custom)",
  "state": "enum: draft | awaitingSigners | partiallySigned | completed | cancelled | failed | expired",
  "expiresAt": "ISO 8601 timestamp",
  "createdBy": "string (Nextcloud user ID)",
  "createdAt": "ISO 8601 timestamp",
  "completedAt": "ISO 8601 timestamp (null if not completed)",
  "auditChain": "array of audit_event references"
}
```

### SignatureInvitation

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "SignatureInvitation",
    "slug": "invitation"
  },
  "id": "uuid",
  "signatureRequestId": "uuid (relation to SignatureRequest)",
  "signerName": "string",
  "signerEmail": "string",
  "signerLoa": "enum: substantial | high",
  "signerOrder": "integer (1-based; meaningful only in sequential mode)",
  "role": "string (free-form, e.g., gemeentesecretaris, wethouder, inwoner, advocaat)",
  "state": "enum: pending | notified | viewed | authenticated | signed | declined | expired",
  "qtspSessionId": "string (QTSP's transaction ID for this signer)",
  "signedAt": "ISO 8601 timestamp (null if not signed)",
  "declinedReason": "string (null if not declined)"
}
```

### SignatureArtefact

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "SignatureArtefact",
    "slug": "artefact"
  },
  "id": "uuid",
  "signatureRequestId": "uuid (relation to SignatureRequest)",
  "invitationId": "uuid (relation to SignatureInvitation)",
  "signerName": "string",
  "signerCertificateSubject": "string (full DN, e.g., CN=Alice De Vos,O=Gemeente,ST=Noord-Holland,C=NL)",
  "signerCertificateFingerprint": "string (SHA-256 hex)",
  "signatureFormat": "enum: pades-b-lta | xades-b-lta | cades-b-lta",
  "assuranceLevel": "enum: qes | aes",
  "signedAt": "ISO 8601 timestamp",
  "qualifiedTimestamp": {
    "tsa": "string (TSA issuer name)",
    "tsaCertificateFingerprint": "string (SHA-256 hex)",
    "timestampedAt": "ISO 8601 timestamp",
    "nonce": "string"
  },
  "revocationInfo": {
    "crl": "array of CRL distribution points and their content (captured at sign time)",
    "ocsp": "OCSP response (captured at sign time)"
  },
  "signatureBytes": "binary blob (embedded in container for PAdES; separate for XAdES detached)",
  "signedDocumentRef": "uuid (docudesk file ID containing signed document)",
  "ltvSealedAt": "ISO 8601 timestamp (when LTV embedding was completed)",
  "verificationStatus": "enum: valid | valid-ltv | unknown | invalid"
}
```

### QtspConfiguration

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "QtspConfiguration",
    "slug": "qtsp-config"
  },
  "id": "uuid",
  "qtspId": "string (quovadis | kpnid | digidentity | connectis | ...)",
  "displayName": "string (human-readable, e.g., 'KPN ID - Remote Signing')",
  "adapterVersion": "string (semver, e.g., 1.0.0)",
  "openconnectorSourceId": "uuid (reference to OpenConnector source; credentials stored there)",
  "supportedFormats": "array of string (pades-b-lta, xades-b-lta, cades-b-lta variants)",
  "supportedLoa": "array of enum (substantial, high)",
  "defaultSignatureFormat": "enum: pades-b-lta | xades-b-lta | cades-b-lta",
  "userJourney": "enum: embedded | redirect | popup",
  "enabled": "boolean"
}
```

### AuditEvent

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "AuditEvent",
    "slug": "audit-event"
  },
  "id": "uuid",
  "signatureRequestId": "uuid (relation to SignatureRequest)",
  "invitationId": "uuid | null (relation to SignatureInvitation, null for request-level events)",
  "eventType": "enum: requestCreated | invitationSent | invitationViewed | signerAuthenticated | signatureCompleted | signatureFailed | requestCancelled | ltvSealed | verificationRefreshed",
  "timestamp": "ISO 8601 timestamp (qualified-timestamped where applicable)",
  "actor": "string (signer email or system identifier, e.g., 'alice@gemeente.nl' or 'system:ltv-refresh-job')",
  "ipAddress": "string (CIDR notation, null for system events)",
  "userAgent": "string (null for system events)",
  "evidence": "JSON object (QTSP response excerpt, hash of artefact produced, LoA context)"
}
```

## Entity Relationships

All entities use OpenRegister's relation mechanism (register + schema + objectId). NO foreign keys or embedded objects.

- `SignatureInvitation.signatureRequestId` → `SignatureRequest` (many-to-one)
- `SignatureArtefact.signatureRequestId` → `SignatureRequest` (many-to-one)
- `SignatureArtefact.invitationId` → `SignatureInvitation` (one-to-one)
- `AuditEvent.signatureRequestId` → `SignatureRequest` (many-to-one)
- `AuditEvent.invitationId` → `SignatureInvitation` (many-to-one, nullable)
- `SignatureRequest.documentId` → Document (external reference to docudesk base)

## Data Integrity

- `documentHash` is captured at request creation and verified before each QTSP ceremony begins
- `signatureArtefact.signatureBytes` is immutable post-creation
- `auditChain` is append-only; no modifications or deletions
- `verificationStatus` is recomputed by the verification refresh job; not manually settable

## Seed Data

### Sample Signature Requests (parallel flow, QES, PAdES)

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "SignatureRequest",
    "slug": "req-gemeente-contract-001"
  },
  "id": "a1b2c3d4-e5f6-47a8-9b0c-1d2e3f4a5b6c",
  "documentId": "d1e2f3a4-b5c6-47d8-9e0f-1a2b3c4d5e6f",
  "documentVersion": "v1.2.3",
  "documentHash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
  "previewUrl": "https://sign.docudesk.nl/preview/a1b2c3d4-e5f6-47a8-9b0c-1d2e3f4a5b6c",
  "assuranceLevel": "qes",
  "signatureFormat": "pades-b-lta",
  "signingOrder": "parallel",
  "qtspId": "digidentity",
  "state": "completed",
  "expiresAt": "2026-07-15T15:00:00Z",
  "createdBy": "juriste@gemeente-den-haag.nl",
  "createdAt": "2026-05-22T10:30:00Z",
  "completedAt": "2026-05-23T14:15:00Z"
}
```

### Sample Invitations (sequential flow, KPN ID)

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "SignatureInvitation",
    "slug": "inv-gemeentesecretaris-001"
  },
  "id": "i1i2i3i4-i5i6-47i8-9i0-1i2i3i4i5i6",
  "signatureRequestId": "r1r2r3r4-r5r6-47r8-9r0-1r2r3r4r5r6",
  "signerName": "Drs. Jan de Wit",
  "signerEmail": "jan.dewit@gemeente-rotterdam.nl",
  "signerLoa": "substantial",
  "signerOrder": 1,
  "role": "gemeentesecretaris",
  "state": "signed",
  "qtspSessionId": "kpnid-sess-xyz123",
  "signedAt": "2026-05-23T09:45:00Z",
  "declinedReason": null
}
```

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "SignatureInvitation",
    "slug": "inv-wethouder-001"
  },
  "id": "i5i6i7i8-i9i0-47i1-9i2-1i3i4i5i6i7",
  "signatureRequestId": "r1r2r3r4-r5r6-47r8-9r0-1r2r3r4r5r6",
  "signerName": "Dhr. Michel Bos",
  "signerEmail": "michel.bos@gemeente-rotterdam.nl",
  "signerLoa": "high",
  "signerOrder": 2,
  "role": "wethouder",
  "state": "pending",
  "qtspSessionId": null,
  "signedAt": null,
  "declinedReason": null
}
```

### Sample Signature Artefact (completed PAdES-B-LTA)

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "SignatureArtefact",
    "slug": "art-rotterdam-contract-001"
  },
  "id": "a5a6a7a8-a9a0-47a1-9a2-1a3a4a5a6a7",
  "signatureRequestId": "r1r2r3r4-r5r6-47r8-9r0-1r2r3r4r5r6",
  "invitationId": "i1i2i3i4-i5i6-47i8-9i0-1i2i3i4i5i6",
  "signerName": "Drs. Jan de Wit",
  "signerCertificateSubject": "CN=Drs. Jan de Wit,O=Gemeente Rotterdam,ST=Zuid-Holland,C=NL",
  "signerCertificateFingerprint": "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1",
  "signatureFormat": "pades-b-lta",
  "assuranceLevel": "qes",
  "signedAt": "2026-05-23T09:45:00Z",
  "qualifiedTimestamp": {
    "tsa": "NLTime Qualified Time Service Authority",
    "tsaCertificateFingerprint": "b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2",
    "timestampedAt": "2026-05-23T09:45:15Z",
    "nonce": "nonce-xyz123-abc456"
  },
  "revocationInfo": {
    "crl": [
      "http://crl.digidentity.eu/certs/DigiD_KP.crl",
      "http://crl.pkioverheid.nl/Chain-KP.crl"
    ],
    "ocsp": "OCSP response binary (base64 encoded)"
  },
  "signatureBytes": "binary blob (PAdES container with embedded signature)",
  "signedDocumentRef": "d5d6d7d8-d9d0-47d1-9d2-1d3d4d5d6d7",
  "ltvSealedAt": "2026-05-23T09:45:20Z",
  "verificationStatus": "valid-ltv"
}
```

### Sample QTSP Configuration (Digidentity, Embedded)

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "QtspConfiguration",
    "slug": "qtsp-digidentity"
  },
  "id": "q1q2q3q4-q5q6-47q8-9q0-1q2q3q4q5q6",
  "qtspId": "digidentity",
  "displayName": "Digidentity - Qualified Signatures",
  "adapterVersion": "1.0.0",
  "openconnectorSourceId": "ocs1o2o3o4-o5o6-47o8-9o0-1o2o3o4o5o6",
  "supportedFormats": [
    "pades-b-lta",
    "xades-b-lta",
    "cades-b-lta"
  ],
  "supportedLoa": [
    "substantial",
    "high"
  ],
  "defaultSignatureFormat": "pades-b-lta",
  "userJourney": "embedded",
  "enabled": true
}
```

### Sample QTSP Configuration (KPN ID, Redirect)

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "QtspConfiguration",
    "slug": "qtsp-kpnid"
  },
  "id": "q5q6q7q8-q9q0-47q1-9q2-1q3q4q5q6q7",
  "qtspId": "kpnid",
  "displayName": "KPN ID - Remote Signing",
  "adapterVersion": "1.1.0",
  "openconnectorSourceId": "ocs5o6o7o8-o9o0-47o1-9o2-1o3o4o5o6o7",
  "supportedFormats": [
    "pades-b-lta",
    "cades-b-lta"
  ],
  "supportedLoa": [
    "substantial"
  ],
  "defaultSignatureFormat": "pades-b-lta",
  "userJourney": "redirect",
  "enabled": true
}
```

### Sample Audit Events

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "AuditEvent",
    "slug": "audit-req-created-001"
  },
  "id": "e1e2e3e4-e5e6-47e8-9e0-1e2e3e4e5e6",
  "signatureRequestId": "r1r2r3r4-r5r6-47r8-9r0-1r2r3r4r5r6",
  "invitationId": null,
  "eventType": "requestCreated",
  "timestamp": "2026-05-22T10:30:00Z",
  "actor": "juriste@gemeente-den-haag.nl",
  "ipAddress": "203.0.113.42/32",
  "userAgent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
  "evidence": {
    "documentId": "d1e2f3a4-b5c6-47d8-9e0f-1a2b3c4d5e6f",
    "documentVersion": "v1.2.3",
    "documentHash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
  }
}
```

```json
{
  "@self": {
    "register": "docudesk_signatures",
    "schema": "AuditEvent",
    "slug": "audit-sig-completed-001"
  },
  "id": "e5e6e7e8-e9e0-47e1-9e2-1e3e4e5e6e7",
  "signatureRequestId": "r1r2r3r4-r5r6-47r8-9r0-1r2r3r4r5r6",
  "invitationId": "i1i2i3i4-i5i6-47i8-9i0-1i2i3i4i5i6",
  "eventType": "signatureCompleted",
  "timestamp": "2026-05-23T09:45:15Z",
  "actor": "jan.dewit@gemeente-rotterdam.nl",
  "ipAddress": "203.0.113.99/32",
  "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)",
  "evidence": {
    "artefactId": "a5a6a7a8-a9a0-47a1-9a2-1a3a4a5a6a7",
    "signerLoa": "substantial",
    "tsaIssuer": "NLTime Qualified Time Service Authority",
    "ltvStatus": "enabled"
  }
}
```

## Reuse Analysis

This change leverages the following existing OpenRegister/Nextcloud-Vue platform capabilities:

- **ObjectService**: CRUD for SignatureRequest, SignatureInvitation, SignatureArtefact, QtspConfiguration, AuditEvent
- **RelationService**: Cross-entity relations (request → invitations, request → artefacts, invitation → artefact)
- **AuditTrailService**: Append-only change tracking (integrated with OpenRegister audit)
- **FileService**: Signed document storage as a new file version
- **NotificationService**: Email dispatch for invitation links, verification failure alerts
- **AuthorizationService**: Permission checks for request creation, cancellation, audit log viewing
- **SchemaService**: Schema-driven form generation for admin QTSP configuration
- **CnDetailPage**: Display of signature request status and artefact verification history
- **CnDataTable**: List of active/completed signing requests with filtering and sorting
- **IAppConfig**: QTSP adapter configuration and per-instance settings

NO custom CRUD, list, search, or form components are built; all are provided by the platform.

## Deduplication Check

Similar functionality searches:

- **Document versioning**: Existing docudesk versioning is reused; no duplication
- **Audit logging**: AuditTrailService is platform-standard; integration checked
- **Multi-step workflows**: No conflict with existing task/workflow engine; signing is domain-specific orchestration
- **Notification dispatch**: NotificationService handles email; no duplication
- **QTSP adapter registry**: NEW capability; OpenConnector source mechanism adapted for QTSP credential management
- **LTV refresh job**: NEW background job; no existing LTV infrastructure
- **Verification refresh job**: NEW background job; complements existing audit trail refresh

Result: NO overlap with existing OpenRegister services. QTSP adapter pattern is a new, non-duplicative extension to OpenConnector's source template mechanism.

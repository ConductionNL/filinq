---
id: digital-signing
title: Digital Signing Integration
sidebar_label: Digital Signing
sidebar_position: 2
description: Secure digital document signing, verification, and audit trail within Nextcloud
keywords:
  - signing
  - verification
  - eIDAS
  - digital signature
  - ValidSign
  - audit trail
---

# Digital Signing Integration

DocuDesk provides a complete digital signing workflow — create signing requests, collect
signatures from multiple signers, verify document integrity, and maintain an immutable audit
trail. Signing can be performed natively within Nextcloud or delegated to an external provider
such as ValidSign.

## Overview

The signing feature supports:

- **Signing requests** — Create a request that routes a document to one or more signers
- **Signer actions** — Sign or decline; bulk signing for multiple requests
- **Cancellation** — Cancel an open request at any time
- **Verification** — Verify the signature integrity of a signed document
- **Audit trail** — Immutable log of all signing events per request
- **Pluggable providers** — Native (local) signing or ValidSign external service

## API Endpoints

### Create Signing Request

```
POST /apps/docudesk/api/signing/requests
```

**Request body (JSON):**

| Field         | Type     | Required | Description                                              |
|--------------|----------|----------|----------------------------------------------------------|
| `fileId`      | int      | Yes      | Nextcloud file ID of the document to sign                |
| `signers`     | array    | Yes      | List of signer objects `{ userId, email, name, level }`  |
| `title`       | string   |          | Human-readable title for the request                     |
| `message`     | string   |          | Message shown to signers                                 |
| `dueDate`     | string   |          | ISO 8601 due date for completion                         |
| `provider`    | string   |          | Signing provider: `native` (default) or `validsign`      |

**Response:**

```json
{
  "id": "request-uuid",
  "status": "pending",
  "fileId": 42,
  "signers": [ { "id": "signer-uuid", "userId": "john", "status": "pending" } ],
  "createdAt": "2025-01-15T10:00:00Z"
}
```

---

### List Signing Requests

```
GET /apps/docudesk/api/signing/requests
```

Returns all signing requests visible to the current user.

---

### Get Signing Request

```
GET /apps/docudesk/api/signing/requests/{id}
```

---

### Cancel Signing Request

```
DELETE /apps/docudesk/api/signing/requests/{id}
```

Cancels an open request. Any pending signers are notified.

---

### Sign Document

```
POST /apps/docudesk/api/signing/requests/{id}/sign
```

Records a signature for the current user on the given request.

**Request body (JSON):**

| Field      | Type   | Required | Description                                    |
|-----------|--------|----------|------------------------------------------------|
| `signerId` | string | Yes      | UUID of the signer entry within the request    |
| `pin`      | string |          | PIN for native signing (if required)           |

---

### Decline Signing

```
POST /apps/docudesk/api/signing/requests/{id}/decline
```

**Request body:**

| Field      | Type   | Required | Description                           |
|-----------|--------|----------|---------------------------------------|
| `signerId` | string | Yes      | UUID of the signer                    |
| `reason`   | string | Yes      | Reason for declining                  |

---

### Bulk Sign

```
POST /apps/docudesk/api/signing/bulk
```

Sign multiple requests in a single call.

**Request body:**

```json
{ "requestIds": ["uuid-1", "uuid-2"] }
```

---

### Verify Document

```
GET /apps/docudesk/api/signing/verify/{fileId}
```

Verifies signature integrity of a signed document file.

**Response:**

```json
{
  "valid": true,
  "signers": [
    { "name": "John Doe", "signedAt": "2025-01-16T09:00:00Z", "level": "AES" }
  ],
  "verifiedAt": "2025-01-16T10:00:00Z"
}
```

---

### Get Audit Trail

```
GET /apps/docudesk/api/signing/requests/{id}/audit
```

Returns the full audit trail for a signing request.

**Response:**

```json
[
  { "event": "created", "userId": "admin", "timestamp": "2025-01-15T10:00:00Z" },
  { "event": "signed",  "userId": "john",  "timestamp": "2025-01-16T09:00:00Z" }
]
```

---

## Signing Providers

DocuDesk uses a provider abstraction (`SigningProviderInterface`) to support multiple
backend signing implementations.

### Native Provider (`native`)

Signs documents within Nextcloud using a local key pair. No external service is required.
Suitable for internal workflows and Basic/AES signatures.

### ValidSign Provider (`validsign`)

Delegates signing to the ValidSign external service. Suitable for QES/AES signatures
with legal weight under eIDAS.

**Required configuration:**

| Config key                         | Description                              |
|-----------------------------------|------------------------------------------|
| `docudesk_validsign_api_url`       | ValidSign API base URL                   |
| `docudesk_validsign_api_key`       | ValidSign API key                        |
| `docudesk_validsign_sender_email`  | Sender email for invitation notifications|

Set via DocuDesk admin settings or:

```bash
docker exec nextcloud php occ config:app:set docudesk docudesk_validsign_api_key --value="your-key"
```

---

## Signature Levels

| Level | Name                                  | Description                                       |
|-------|---------------------------------------|---------------------------------------------------|
| `BES` | Basic Electronic Signature            | Simple click-to-sign, no certificate required     |
| `AES` | Advanced Electronic Signature         | Identity-linked, supports local key pairs         |
| `QES` | Qualified Electronic Signature        | Legally equivalent to handwritten (eIDAS Art. 25) |

---

## Request State Machine

```
pending → in-progress → completed
                    ↓
                 declined
                    ↓
                cancelled
```

`isValidTransition()` enforces allowed state transitions.

---

## Audit Trail

`SigningAuditService` records every lifecycle event (created, signed, declined, cancelled,
verified) as an immutable object in OpenRegister. Audit entries cannot be updated or deleted
through the public API.

---

## Services

### `SigningService`

Orchestrates the signing request lifecycle.

| Method                   | Description                                      |
|-------------------------|--------------------------------------------------|
| `createRequest(data)`    | Validate and create a new signing request        |
| `getRequest(id)`         | Retrieve a signing request by ID                 |
| `listRequests()`         | List requests visible to the current user        |
| `sign(id, signerId)`     | Record a signature action                        |
| `decline(id, signerId, reason)` | Decline a signing request               |
| `cancelRequest(id)`      | Cancel an open request                           |
| `bulkSign(requestIds)`   | Sign multiple requests at once                   |
| `isValidTransition()`    | Check whether a status transition is allowed     |

### `SigningVerificationService`

| Method               | Description                                      |
|--------------------|--------------------------------------------------|
| `verifyDocument(fileId, userId)` | Verify signature integrity for a file|

### `SigningAuditService`

| Method              | Description                                      |
|-------------------|--------------------------------------------------|
| `logEvent()`        | Record a signing lifecycle event                 |
| `getAuditTrail(id)` | Retrieve all events for a signing request        |

### `SigningProviderFactory`

Resolves the configured signing provider instance.

### `SigningProviderInterface`

| Method                       | Description                                    |
|-----------------------------|------------------------------------------------|
| `getIdentifier()`            | Provider identifier string                    |
| `initiateSigning()`          | Start a signing session with the provider     |
| `checkStatus(externalId)`    | Poll external provider for signing status     |
| `downloadSignedDocument()`   | Retrieve the signed document binary           |
| `cancelSigning(externalId)`  | Cancel an external signing session            |
| `supportsLevel(level)`       | Check if provider supports a signature level  |

## Dependencies

| Dependency           | Purpose                                         |
|---------------------|-------------------------------------------------|
| `OpenRegister`       | Storage for signing requests and audit entries  |
| `INotificationManager` | Notify signers of pending requests           |
| `IUserSession`       | Identify the current signing user               |
| `IAppConfig`         | Read provider configuration                     |

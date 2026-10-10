---
id: digital-signing
title: Digital Signing Integration
sidebar_label: Digital Signing
sidebar_position: 2
description: Secure digital document signing, verification, and audit trail within Nextcloud
keywords:
  - signing
  - DigiD
  - eHerkenning
  - EUDI wallet
  - verification
  - eIDAS
  - digital signature
  - ValidSign
  - audit trail
---

# Digital Signing Integration

Filinq provides a complete digital signing workflow — create signing requests, collect
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
POST /apps/filinq/api/signing/requests
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
GET /apps/filinq/api/signing/requests
```

Returns all signing requests visible to the current user.

---

### Get Signing Request

```
GET /apps/filinq/api/signing/requests/{id}
```

---

### Cancel Signing Request

```
DELETE /apps/filinq/api/signing/requests/{id}
```

Cancels an open request. Any pending signers are notified.

---

### Sign Document

```
POST /apps/filinq/api/signing/requests/{id}/sign
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
POST /apps/filinq/api/signing/requests/{id}/decline
```

**Request body:**

| Field      | Type   | Required | Description                           |
|-----------|--------|----------|---------------------------------------|
| `signerId` | string | Yes      | UUID of the signer                    |
| `reason`   | string | Yes      | Reason for declining                  |

---

### Bulk Sign

```
POST /apps/filinq/api/signing/bulk
```

Sign multiple requests in a single call.

**Request body:**

```json
{ "requestIds": ["uuid-1", "uuid-2"] }
```

---

### Verify Document

```
GET /apps/filinq/api/signing/verify/{fileId}
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
GET /apps/filinq/api/signing/requests/{id}/audit
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

Filinq uses a provider abstraction (`SigningProviderInterface`) to support multiple
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
| `filinq_validsign_api_url`       | ValidSign API base URL                   |
| `filinq_validsign_api_key`       | ValidSign API key                        |
| `filinq_validsign_sender_email`  | Sender email for invitation notifications|

Set via Filinq admin settings or:

```bash
docker exec nextcloud php occ config:app:set filinq filinq_validsign_api_key --value="your-key"
```

---

## Signature Levels

| Level | Name                                  | Description                                       |
|-------|---------------------------------------|---------------------------------------------------|
| `BES` | Basic Electronic Signature            | Simple click-to-sign, no certificate required     |
| `AES` | Advanced Electronic Signature         | Identity-linked, supports local key pairs         |
| `QES` | Qualified Electronic Signature        | Legally equivalent to handwritten (eIDAS Art. 25) |

---

## Signers under the age of consent

A pupil of 14 cannot sign an ontwikkelingsperspectief (OPP) on their own. Filinq refuses
the signature until a parent or guardian stands beside them on the same request.

**The age.** Set it under *Settings > Digital signing > Guardian consent age*. The default
is 16, the age of consent in Dutch law (UAVG article 5). A request can raise it for its own
signers: send `guardianConsentAge: 18` on a praktijkovereenkomst (POK), because a student
under 18 is a minor under civil law. A request can never lower it.

**Naming the guardian.** Give each signer entry the extra fields below. The app that asks
for the signature knows the pupil's birth date and the parents, so it sends them.

| Field              | On                | Meaning                                                          |
|--------------------|-------------------|------------------------------------------------------------------|
| `birthDate`        | the pupil         | `YYYY-MM-DD`. Decides whether a guardian must sign too.          |
| `role`             | the guardian      | `guardian`. Every other entry is a `signer`.                     |
| `guardianFor`      | the guardian      | The `userId` or `email` of the pupil's entry in the same list.   |
| `guardianAct`      | the guardian      | `co-sign` (default): signs the document too. `consent`: agrees that the pupil signs. |
| `consentStatement` | a `consent` act   | The text the guardian agrees to. Required for `consent`.         |
| `guardianRef`      | the guardian      | Your own reference to the guardian, for example a learniq guardian id. |

**What Filinq checks.**

- A request that names a pupil under the age without a guardian is refused (400).
- The pupil's signature is refused (403) while no guardian on the request points at them.
- A guardian signs through the same sign action as anyone else, in Nextcloud or in the portal.
- A guardian cannot be the pupil, and cannot be under the age themselves.
- The age is checked at the moment the pupil signs, not when the request was made.
- The request completes only once a guardian has acted for every pupil under the age.

**What the signed document records.** The completed request and the signed file carry a
`consentBasis`: per pupil, the signer record, the age that applied, the moment it was
checked, and each guardian who acted, how, and how their identity was established. It never
carries the birth date. In the signed file the basis sits inside the signature seal, so
changing it afterwards makes verification fail.

**Who uses it.** learniq for the OPP and the POK, and portaliq for toestemmingsformulieren,
once they raise their signing requests through `DocumentSigningRequestedEvent`.

Next: set the guardian consent age for your school in the signing settings.

---

## Signer identity: DigiD, eHerkenning and iDIN

A Nextcloud login tells Filinq which account is signing. It does not say how strongly that
person proved who they are. For a signature that needs more, a signer confirms their
identity once more through an identity broker, just before they sign.

**Assurance levels.** Filinq uses the three eIDAS levels: `low`, `substantial` and `high`.
Each signature level has a floor, and a request can ask for more but never less.

| Signature level | Assurance floor |
|-----------------|-----------------|
| SES             | `low`           |
| AdES            | `substantial`   |
| QES             | `high`          |

A QES request that asks for `low` is stored at `high`, and the create response says so in
`assuranceFloor`. A request made before this feature reads as `low`, so nothing changes for
it.

**What reaches which level.**

| Identity means                      | Assurance     |
|-------------------------------------|---------------|
| Nextcloud login                     | `low`         |
| DigiD Midden or Substantieel        | `substantial` |
| DigiD Hoog                          | `high`        |
| eHerkenning EH3                     | `substantial` |
| eHerkenning EH4                     | `high`        |
| iDIN (your broker's value)          | `substantial` |
| Portal signer                       | the portal's verified trust |

An unknown broker value counts as `low`, never higher.

**Connect a broker.** Under *Settings > Signer identity*:

1. Create the broker's client secret in the OpenRegister credential broker, on an inject-only
   provider, and allow the app `filinq`. Copy the credential's ID.
2. Fill in the issuer, the client ID, the authorization and token endpoints (https only) and
   the redirect URI `https://<your-cloud>/index.php/apps/filinq/api/signing/identity/callback`.
3. Paste the credential ID into *Credential reference*. Filinq stores this reference, never
   the secret, and refuses anything that is not a credential ID.
4. Add your broker's own iDIN value under *Extra acr mapping*. DigiD and eHerkenning are
   mapped already.
5. Choose *Identity broker* as the identity provider and save.

**Signing with a stronger identity.** When a request asks for more than the signer has, the
sign action answers 403 with a `stepUp` hint: the level needed and why. In the signing folder
the signer then chooses *Confirm my identity*, logs in at the broker, comes back on the
request and signs. A login stays valid for signing for 15 minutes by default; set another
window in the settings. It counts for that one request and signer only.

![The signing folder after a pass: the document asks for a stronger identity check](/screenshots/signer-identity-step-up-hint.png)

![The dialog that sends the signer to the identity broker](/screenshots/signer-identity-step-up-start.png)

**Parents and guardians.** A request can ask more of a guardian than of the pupil: send
`guardianRequiredAssurance: substantial` and the parent confirms with DigiD while the pupil
signs with their Nextcloud login. An administrator can set a minimum for every guardian under
*Minimum assurance for a parent or guardian*.

![The signer identity settings with the guardian minimum set to substantial](/screenshots/signer-identity-guardian-minimum.png)

**What is recorded.** Each signer record, its audit entry and the signed file carry the
identity evidence: provider, means, level, a pseudonym, the moment and a hash of the broker's
token. Filinq never keeps a BSN or the token itself; the subject is always hashed with a key
unique to your Nextcloud. The completed request carries the weakest level among its signers
as `resolvedAssurance`, and apps that asked for the signature receive that same value.

**EUDI wallet.** Every EU country must offer its citizens an EUDI wallet by December 2026,
with qualified signatures from a phone. Filinq does not ship a wallet integration today. The
identity seam is built so a wallet verifier can plug in as one more provider: it passes the
provider contract tests (`tests/unit/Service/SignerAuth/SignerAuthProviderContractTestCase.php`)
and registers, with no other change.

Next: connect your identity broker under *Settings > Signer identity*.

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
verified) as an immutable entry in OpenRegister's native audit trail
(`openregister_audit_trails`). Events are stored with action types of the form
`filinq.signing.{ACTION}` (e.g. `filinq.signing.SIGNED`). The audit trail is
hash-chained and natively immutable — update and delete operations are rejected by OR with
HTTP 405.

Signing audit events are discoverable via:

```
GET /api/audit-trails?objectUuid={signRequestId}
```

### Supported action types

| Action type                      | Triggered when                    |
|----------------------------------|-----------------------------------|
| `filinq.signing.CREATED`       | Signing request is created        |
| `filinq.signing.START`         | Signer starts a signing session   |
| `filinq.signing.SIGNED`        | Signer signs the document         |
| `filinq.signing.DECLINED`      | Signer declines to sign           |
| `filinq.signing.CANCELLED`     | Initiator cancels the request     |
| `filinq.signing.EXPIRED`       | Request expires before completion |
| `filinq.signing.COMPLETED`     | All signers have signed           |
| `filinq.signing.VIEWED`        | Signer views the document         |

Each entry's `changed` JSON field carries: `signRequestId`, `actorUserId`,
`actorDisplayName`, `ipAddress`, `signatureLevel`, `provider`.

### Archiefwet 1995 retention configuration (required)

> **Administrators MUST configure OR's retention for the signing register to ≥ 3650 days
> (10 years) to comply with Archiefwet 1995.**

This is a **deploy-time configuration** in OpenRegister — not enforced by Filinq
application code. Configure via:

- **OR Admin UI**: Navigate to Settings → OpenRegister → Registers → signing register →
  set retention period to `3650` days or higher.
- **occ command** (if available in your OR version):
  ```bash
  php occ openregister:register:set-retention <registerId> --days 3650
  ```

The legal basis is **Archiefwet 1995**, which requires a minimum 10-year retention period
for signing audit records relating to official documents. Failure to configure this setting
may result in audit entries being purged before the mandatory retention period expires.

### Legacy signingAuditEntry records

Prior to the `migrate-signing-audit-to-or-audit` release, signing events were stored in the
`signingAuditEntry` OR schema. These records remain readable (read-only) for one major
release. No new events are written to `signingAuditEntry`; all new events go to OR's native
audit trail.

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

# Design: document-signing

## Status: pr-created

## Architecture Overview

Document signing in DocuDesk implements eIDAS-compliant digital signatures for Dutch government
documents. 76% of analyzed tenders require digital signing; this change delivers the full signing
lifecycle as specified in specs/document-signing/spec.md.

### Implemented Components

| Component | Path | Purpose |
|---|---|---|
| `SigningController` | `lib/Controller/SigningController.php` | REST API (9 endpoints) |
| `SigningService` | `lib/Service/SigningService.php` | Lifecycle state machine |
| `SigningAuditService` | `lib/Service/SigningAuditService.php` | Immutable audit trail |
| `SigningVerificationService` | `lib/Service/SigningVerificationService.php` | HMAC PDF verification |
| `SigningProviderInterface` | `lib/Service/Signing/SigningProviderInterface.php` | Provider abstraction |
| `SigningProviderFactory` | `lib/Service/Signing/SigningProviderFactory.php` | Provider resolution |
| `NativeSigningProvider` | `lib/Service/Signing/NativeSigningProvider.php` | SES (native) |
| `ValidSignProvider` | `lib/Service/Signing/ValidSignProvider.php` | ValidSign (stub) |
| `SigningExpirationJob` | `lib/BackgroundJob/SigningExpirationJob.php` | Deadline enforcement |

### Data Model (OpenRegister schemas in docudesk_register.json)

- **signingRequest** — lifecycle object with status state machine (DRAFT→PENDING→IN_PROGRESS→COMPLETED/DECLINED/EXPIRED/CANCELLED)
- **signerRecord** — per-signer status (PENDING/SIGNED/DECLINED) with timestamp and IP
- **signingAuditEntry** — immutable append-only audit trail (Archiefwet 1995, 10-year retention)
- **signingSession** — native provider session persistence (issue #287 fix)

### Declarative-vs-Imperative Decision

Per ADR-031:
- **Lifecycle (status transitions)**: Declared via `x-openregister-lifecycle` in the register JSON for `signingRequest`. The `x-openregister-lifecycle` extension handles the basic transition rules; PHP guards implement cross-object authorization checks (C4 security fix, finding #282) which the declarative extension cannot express.
- **Notifications**: `x-openregister-notifications` used for completion and deadline-reminder notifications on `signingRequest` and `signerRecord`.
- **Provider abstraction**: Legitimate PHP service — external API integration (ValidSign, DocuSign, etc.) is explicitly listed as a case requiring PHP in ADR-031.
- **Audit trail**: Legitimate PHP service — immutability enforcement requires application-layer coordination with the OR storage guard (`immutable: true, appendOnly: true`).

### Security Model

- Per-object authorization on all signer mutations (OWASP A01:2021, ADR-005 Rule 3)
- C4 fix: signer record verified to belong to the claimed request ID before any operation
- Finding #282: authenticated user UID matched against signer `userId` before sign/decline
- Audit trail HMAC verification: fail-closed (finding #284)
- Audit trail storage-layer immutability (finding #289)
- Server-side filter on audit reads (finding #290a)

### Signing Expiration

`SigningExpirationJob` runs hourly via `TimedJob`. It scans all PENDING and IN_PROGRESS
requests for past-deadline `deadline` fields and marks them EXPIRED, logging an audit
event per expiration. The job skips gracefully when OpenRegister is not available or
register/schema keys are not yet configured.

### Known Gaps / Follow-ups

- **Native provider pipeline** (issue #304): `NativeSigningProvider::initiateSigning()` has a C1 mitigation guard — the provider↔request wiring is not yet complete. SES signing records status updates but does not embed a PAdES signature in the PDF. The guard throws immediately so administrators see the gap rather than silently receiving unsigned documents.
- **ValidSign integration**: `ValidSignProvider` is a stub. Full integration requires the OpenConnector source configuration and webhook endpoint.
- **Document locking**: The spec requires documents to be locked for editing when a signing request is created. This requires integration with the Nextcloud lock mechanism (WebDAV LOCK). Deferred as a follow-up since the lock API is not available in the current container.
- **Sequential notification**: The `updateRequestStatus` method tracks all-signed completion. Selective next-signer notification in sequential mode is tracked for follow-up (the notification framework requires runtime user resolution that is outside this PR's scope).

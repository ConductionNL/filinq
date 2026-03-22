## Context

DocuDesk is a Nextcloud app for GDPR-compliant document processing. It currently handles anonymization, consent management, metadata enrichment, and PDF generation. It has zero signing capability. 76% of Dutch government tenders require digital signing, making this a critical gap.

The existing architecture follows a service-oriented pattern: controllers delegate to services, services use OpenRegister for data persistence via ObjectService. Configuration is defined in `docudesk_register.json` (OpenAPI 3.0.0 format with `x-openregister` extensions) and auto-imported on boot.

Key constraints:
- All data operations go through OpenRegister (no own database tables)
- Document processing must stay local (no external cloud services for core processing)
- External signing providers are accessed via OpenConnector (infrastructure-level routing)
- Must comply with eIDAS, PAdES, PKIoverheid, and Archiefwet standards

## Goals / Non-Goals

**Goals:**
- Implement signing request lifecycle management (create, track, complete, decline)
- Support three eIDAS signature levels: SES (native), AdES (email+OTP), QES (PKIoverheid)
- Pluggable signing provider interface (ValidSign primary, DocuSign/Adobe Sign/LibreSign planned)
- Native SES signing using Nextcloud user identity and timestamps
- Sequential and parallel multi-signer flows
- PAdES-compliant PDF signature embedding and verification
- Immutable audit trail with 10-year retention (Archiefwet)
- Bulk signing for managers
- Admin settings for provider configuration

**Non-Goals:**
- Certificate management / PKI infrastructure (delegated to external CA)
- Wet signing / physical signatures
- Payment integration for commercial signing services
- Case management integration with Procest (future change, uses events)
- Custom certificate authority operation

## Decisions

### D1: Signing data model in OpenRegister

**Decision:** Store signing requests, signer records, and audit entries as OpenRegister objects using schemas defined in `docudesk_register.json`.

**Rationale:** Follows the existing DocuDesk pattern (consent objects use OpenRegister). Keeps all data operations through ObjectService. Schema definitions in the register JSON enable auto-import on boot.

**Alternative considered:** Own database tables via Nextcloud Entity/Mapper -- rejected because it breaks the established pattern and duplicates persistence logic.

**Schemas to add:**
- `SigningRequest` — the signing flow (document reference, signers, status, type, deadline)
- `SignerRecord` — individual signer within a request (identity, status, signature data, timestamp)
- `SigningAuditEntry` — immutable event log (action, actor, timestamp, IP, metadata)

### D2: SigningProviderInterface for pluggable providers

**Decision:** Define a PHP interface `SigningProviderInterface` with methods: `initiateSigning()`, `checkStatus()`, `downloadSignedDocument()`, `cancelSigning()`. Each provider implements this interface.

**Rationale:** The spec requires ValidSign, DocuSign, Adobe Sign, and LibreSign support. A common interface allows runtime provider selection based on admin configuration.

**Alternative considered:** Direct ValidSign integration only -- rejected because the spec explicitly requires pluggable providers and the market shows multiple providers in use.

**Providers:**
- `NativeSigningProvider` — SES signatures using TCPDF/FPDI for PDF signing with Nextcloud user identity
- `ValidSignProvider` — delegates to ValidSign API via OpenConnector source

### D3: Native SES signing with TCPDF

**Decision:** Use TCPDF (already a common PHP library for PDF operations) for embedding PAdES-compliant simple electronic signatures directly into PDFs.

**Rationale:** SES signatures for internal use (ambtelijke ondertekening) do not require external services. TCPDF supports digital signature embedding with certificate data. This keeps internal signing 100% local.

**Alternative considered:** LibreSign integration for native signing -- viable but adds an external app dependency for basic functionality.

### D4: Signing request lifecycle via status machine

**Decision:** Signing requests follow a strict status machine:
```
DRAFT -> PENDING -> IN_PROGRESS -> COMPLETED
                 -> DECLINED
                 -> EXPIRED
                 -> CANCELLED
```

Signer records follow:
```
PENDING -> SIGNED
        -> DECLINED (with reason)
        -> EXPIRED
```

**Rationale:** Clear lifecycle prevents invalid state transitions. Status changes trigger audit entries. Matches the consent status pattern already in DocuDesk.

### D5: Audit trail as separate OpenRegister objects

**Decision:** Each signing event creates a separate `SigningAuditEntry` object in OpenRegister. Entries are append-only (no update/delete operations exposed).

**Rationale:** Archiefwet requires 10-year retention of immutable records. Separate objects ensure individual events cannot be modified. OpenRegister's ObjectService handles persistence and can enforce retention policies.

### D6: Admin settings extend existing SettingsService

**Decision:** Add signing provider configuration to the existing `SettingsService` and `Settings.vue`. New config keys: `signing_provider`, `signing_provider_config` (JSON), `signing_default_level`, `signing_request_expiry_days`.

**Rationale:** Follows the existing admin settings pattern. No need for a separate settings page.

## Risks / Trade-offs

- **[Risk] TCPDF PAdES compliance depth** -- TCPDF supports basic digital signatures but full PAdES-B/PAdES-T compliance may require additional libraries (e.g., SetaPDF-Signer). Mitigation: Start with SES (basic signatures), add PAdES-T timestamp support iteratively.

- **[Risk] ValidSign API availability** -- ValidSign API integration depends on OpenConnector being configured. Mitigation: NativeSigningProvider works without external dependencies; ValidSignProvider gracefully fails with clear error messages when OpenConnector is unavailable.

- **[Risk] Bulk signing security** -- Single authentication for multiple signatures could be a security concern. Mitigation: Require explicit document review before each signature in bulk mode; log each individual signing action in audit trail.

- **[Risk] PDF file locking during signing** -- Documents must be locked while signing is in progress to prevent modifications. Mitigation: Use Nextcloud's file locking mechanism (ILockManager) and update signing request status if lock is broken.

- **[Trade-off] Local-only vs. full eIDAS compliance** -- Full QES requires hardware security modules or external qualified trust service providers. The native provider can only achieve SES level. AdES and QES require external providers (ValidSign, etc.).

## Migration Plan

1. Add signing schemas to `docudesk_register.json` and bump version -- auto-imported on next boot
2. Deploy backend services (SigningService, SigningController, providers) -- no breaking changes
3. Add frontend components -- new Vue views, no modifications to existing views
4. Add signing routes to `routes.php` -- additive, no route conflicts
5. Add admin settings fields -- extends existing settings section

**Rollback:** Remove signing routes and disable signing provider in settings. Signing request objects remain in OpenRegister but are inert.

## Open Questions

1. Which TCPDF or PDF signing library to use -- TCPDF, FPDI, or setasign/SetaPDF-Signer? (Start with TCPDF, evaluate if PAdES compliance needs SetaPDF)
2. Should signing notifications use Nextcloud's notification system or email via n8n? (Start with Nextcloud notifications, add email as enhancement)
3. ValidSign API version and authentication method -- needs OpenConnector source configuration documentation

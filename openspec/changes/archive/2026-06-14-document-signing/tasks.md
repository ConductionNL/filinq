# Tasks: document-signing

## Task 1: Core Implementation

- [x] Implement service classes
  - [x] SigningService (signing request lifecycle, multi-signer, bulk signing)
  - [x] SigningAuditService (immutable audit trail, Archiefwet 1995 compliance)
  - [x] SigningVerificationService (PDF/PAdES signature verification)
  - [x] SigningProviderFactory (pluggable provider resolution)
  - [x] NativeSigningProvider (SES internal signing)
  - [x] ValidSignProvider (external ValidSign integration stub)
- [x] Add API endpoints
  - [x] POST api/signing/requests (create signing request)
  - [x] GET api/signing/requests (list signing requests)
  - [x] GET api/signing/requests/{id} (show signing request)
  - [x] DELETE api/signing/requests/{id} (cancel signing request)
  - [x] POST api/signing/requests/{id}/sign (sign document)
  - [x] POST api/signing/requests/{id}/decline (decline signing)
  - [x] POST api/signing/bulk (bulk sign)
  - [x] GET api/signing/verify/{fileId} (verify signatures)
  - [x] GET api/signing/requests/{id}/audit (get audit trail)
- [x] Add configuration settings
  - [x] signing_provider (native|validsign)
  - [x] signing_default_level (SES|AdES|QES)
  - [x] signing_request_expiry_days
  - [x] signing_provider_config (JSON for provider options)
  - [x] signingRequest_register / signingRequest_schema
  - [x] signerRecord_register / signerRecord_schema
  - [x] signingAuditEntry_register / signingAuditEntry_schema
- [x] Add OpenRegister schemas
  - [x] signingRequest schema (lifecycle: DRAFT→PENDING→IN_PROGRESS→COMPLETED|DECLINED|CANCELLED|EXPIRED)
  - [x] signerRecord schema (status: PENDING→SIGNED|DECLINED)
  - [x] signingAuditEntry schema (immutable + appendOnly for Archiefwet 1995)
  - [x] signingSession schema (native SES provider session persistence)

## Task 2: Testing

- [x] Unit tests — SigningControllerTest
- [x] Unit tests — SigningAuditServiceTest
- [x] Unit tests — SigningVerificationServiceTest
- [x] Unit tests — NativeSigningProviderTest
- [x] Unit tests — SigningServiceTest (missing)
- [x] Unit tests — ValidSignProviderTest (missing)
- [x] Unit tests — SigningProviderFactoryTest (missing)

## Task 3: Documentation

- [x] API endpoints documented in openapi.json
- [x] Admin guide for signing configuration (`docs/admin/document-signing.md` — covers provider selection, default eIDAS level, expiry, register/schema bindings, ValidSign credentials, operational concerns, troubleshooting, ADR cross-references).

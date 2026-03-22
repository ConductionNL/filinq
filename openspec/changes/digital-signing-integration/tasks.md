## 1. Data Model & Schema Registration

- [x] 1.1 Add SigningRequest, SignerRecord, and SigningAuditEntry schemas to `lib/Settings/docudesk_register.json` with OpenAPI 3.0.0 format and `x-openregister` extensions
- [x] 1.2 Bump the configuration version in `docudesk_register.json` so schemas auto-import on next boot
- [x] 1.3 Add signing-related IAppConfig keys to `SettingsService` (`signing_provider`, `signing_provider_config`, `signing_default_level`, `signing_request_expiry_days`, `signing_enabled`)

## 2. Signing Provider Interface & Implementations

- [x] 2.1 Create `lib/Service/Signing/SigningProviderInterface.php` with methods: `initiateSigning()`, `checkStatus()`, `downloadSignedDocument()`, `cancelSigning()`
- [x] 2.2 Create `lib/Service/Signing/NativeSigningProvider.php` implementing SES signing with TCPDF — embed user identity, timestamp, and IP into PDF signature field
- [x] 2.3 Create `lib/Service/Signing/ValidSignProvider.php` implementing external signing via OpenConnector source — send document, poll status, retrieve signed document
- [x] 2.4 Create `lib/Service/Signing/SigningProviderFactory.php` to resolve the active provider based on `signing_provider` admin setting

## 3. Core Signing Service

- [x] 3.1 Create `lib/Service/SigningService.php` — orchestrates signing request lifecycle (create, track, complete, decline, cancel, expire)
- [x] 3.2 Implement signing request creation with status machine validation (DRAFT -> PENDING -> IN_PROGRESS -> COMPLETED | DECLINED | EXPIRED | CANCELLED)
- [x] 3.3 Implement sequential signing flow — notify next signer only after current signer completes
- [x] 3.4 Implement parallel signing flow — notify all signers simultaneously, complete when all sign
- [x] 3.5 Implement document file locking via ILockManager during active signing requests
- [x] 3.6 Implement signing request expiry detection (check deadline, mark expired requests)

## 4. Audit Trail Service

- [x] 4.1 Create `lib/Service/SigningAuditService.php` — creates immutable SigningAuditEntry objects via ObjectService
- [x] 4.2 Implement audit entry creation for all signing events (CREATED, SIGNED, DECLINED, CANCELLED, EXPIRED, COMPLETED, VIEWED)
- [x] 4.3 Implement read-only enforcement — reject update/delete operations on audit entries with 403

## 5. Signature Verification Service

- [x] 5.1 Create `lib/Service/SigningVerificationService.php` — reads PDF signature fields, validates certificates, detects tampering
- [x] 5.2 Implement signature listing for a document (signer identity, timestamp, level, validity status)

## 6. Signing Controller & Routes

- [x] 6.1 Create `lib/Controller/SigningController.php` with methods: createRequest, listRequests, showRequest, cancelRequest, sign, decline, bulkSign, verify, getAudit
- [x] 6.2 Add signing routes to `appinfo/routes.php`: POST/GET/DELETE `/api/signing/requests`, POST `sign`/`decline`/`bulk`, GET `verify`/`audit`
- [x] 6.3 Register SigningService, SigningAuditService, SigningVerificationService, and SigningProviderFactory in `lib/AppInfo/Application.php` DI container

## 7. Admin Settings Extension

- [x] 7.1 Extend `SettingsService::getAllSettings()` and `loadSettings()` to include signing configuration keys
- [x] 7.2 Add "Digital Signing" section to `src/views/settings/Settings.vue` — provider selector, default level, expiry days, enable toggle
- [x] 7.3 Add signing settings validation (provider must be valid enum, expiry 1-365 days, level must be SES/AdES/QES)

## 8. Frontend Signing Components

- [x] 8.1 Create Pinia signing store (`src/store/signingStore.js`) with CRUD operations for signing requests
- [x] 8.2 Create `src/views/signing/SigningRequestList.vue` — list of signing requests with status badges
- [x] 8.3 Create `src/views/signing/SigningRequestDetail.vue` — request details with signer status, audit trail, and action buttons
- [x] 8.4 Create `src/views/signing/SigningRequestForm.vue` — form to create new signing request (file picker, signer selection, level, mode)
- [x] 8.5 Create `src/views/signing/BulkSigningPanel.vue` — bulk signing interface with document review and batch sign action
- [x] 8.6 Create `src/views/signing/SignatureVerification.vue` — display signature details and verification results for a document
- [x] 8.7 Add signing navigation entry to the DocuDesk sidebar/navigation

## 9. Quality & Testing

- [ ] 9.1 Run `composer check:strict` and fix all PHPCS, PHPMD, Psalm, and PHPStan issues in new PHP files
- [ ] 9.2 Add PHPUnit tests for SigningService status machine transitions
- [ ] 9.3 Add PHPUnit tests for SigningAuditService immutability enforcement
- [ ] 9.4 Verify all new routes are accessible and return expected response shapes
- [ ] 9.5 Test with nldesign theme enabled to verify WCAG AA compliance of signing UI components

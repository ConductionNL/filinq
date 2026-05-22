# Implementation Tasks: eIDAS QES Signature

## Phase 1: Data Model & Entity Setup

- [ ] Create OpenRegister schema definitions for SignatureRequest, SignatureInvitation, SignatureArtefact, QtspConfiguration, AuditEvent in `lib/Settings/docudesk_signatures_register.json`
- [ ] Define entity properties with schema.org types, required flags, and descriptions per ADR-011
- [ ] Create OpenRegister seed data (3-5 realistic objects per schema) in `lib/Settings/docudesk_signatures_register.json` using `@self` envelope
- [ ] Generate OpenRegister migration for docudesk_signatures register with all schemas
- [ ] Verify OpenRegister import is called on app install via `SettingsLoadService`
- [ ] Write migration test validating schema presence and seed data idempotency

## Phase 2: QTSP Adapter Framework & Shipped Adapters

- [ ] Define `IQtspAdapter` interface with methods: `initiateCeremony()`, `validateCallback()`, `retrieveCertificate()`, `supportedFormats()`, `supportedLoa()`, `userJourney()`
- [ ] Create `QtspAdapterRegistry` service to register and look up adapters by `qtspId`
- [ ] Implement `QuoVadisAdapter` with remote-signing API integration (embed credentials from OpenConnector source)
- [ ] Implement `KpnIdAdapter` with KPN ID remote-signing API integration
- [ ] Implement `DigidentityAdapter` with Digidentity API integration
- [ ] Implement `ConnectisAdapter` with Connectis broker API integration
- [ ] Each adapter SHALL fetch credentials from OpenConnector source configuration (no hardcoded keys)
- [ ] Add `QtspAdapterRegistry` to service container with lazy loading per `qtspId`
- [ ] Document adapter interface and integration guide in `docs/QTSP_ADAPTER_INTEGRATION.md`
- [ ] Write unit tests for adapter registry and each shipped adapter's core methods

## Phase 3: Signature Request State Machine

- [ ] Create `SignatureRequestService` with methods: `createRequest()`, `confirmRequest()`, `updateState()`, `cancelRequest()`, `checkDocumentHash()`
- [ ] Implement state machine: draft → awaitingSigners → partiallySigned → completed (or cancelled/failed/expired)
- [ ] In `createRequest()`: capture document ID, version pointer, SHA-256 hash; generate `expiresAt` (default 30 days)
- [ ] In `confirmRequest()`: validate signer list, create SignatureInvitation objects, move request to awaitingSigners, dispatch invitations
- [ ] Implement `checkDocumentHash()`: compare current document hash against frozen hash; if mismatch, move request to failed, cancel all invitations
- [ ] Add `checkDocumentHash()` call before signer initiates QTSP ceremony and before artefact creation
- [ ] Write service tests for state transitions and document-freeze scenarios

## Phase 4: Multi-Party Signing Orchestration

- [ ] Implement sequential signing logic: track `signerOrder` on invitations, block out-of-order ceremony initiation
- [ ] Implement parallel signing logic: allow any signer to sign at any time
- [ ] In `updateState()` for sequential: after a signer completes, check next signer in order; update request to completed only when last signer signs
- [ ] In `updateState()` for parallel: after signer completion, count signed invitations; update request to completed only when all are signed
- [ ] Add validation on SignatureInvitation creation: for sequential, enforce signerOrder is 1-based and contiguous
- [ ] Implement decline handler: if any signer declines in sequential mode, cancel all remaining invitations
- [ ] Write integration tests for sequential and parallel flows with multiple signers

## Phase 5: Signature Invitation Workflow

- [ ] Create `SignatureInvitationService` with methods: `createInvitation()`, `markViewed()`, `markAuthenticated()`, `markSigned()`, `markDeclined()`, `generateSigningLink()`
- [ ] In `generateSigningLink()`: create one-time token (JWT or opaque, 30-day TTL), store in cache or database
- [ ] Implement `getSigningPageUrl()`: return a URL with embedded signing link token
- [ ] Create backend endpoint `GET /api/signatures/{requestId}/invite/{token}` to validate token and return signing page HTML
- [ ] Create backend endpoint `POST /api/signatures/{requestId}/decline` to handle signer decline
- [ ] Add NotificationService integration: dispatch invitation emails with signing links
- [ ] On signer decline: update invitation state, log audit event, check if sequential flow and trigger cancellation
- [ ] Write service tests for invitation state transitions and link generation

## Phase 6: QTSP Ceremony Orchestration

- [ ] Create `SignatureCeremonyController` with endpoint `POST /api/signatures/{requestId}/invitations/{invitationId}/ceremony/init`
- [ ] In ceremony init: validate invitation is pending/viewed, validate signer authentication (per QTSP), validate document hash, call adapter's `initiateCeremony()` method
- [ ] Implement `initiateCeremony()`: determine user journey (embedded/redirect/popup), build ceremony URL with QTSP parameters, return to frontend
- [ ] For embedded journey: return iframe source URL; frontend renders iframe
- [ ] For redirect journey: return redirect URL; frontend navigates to it
- [ ] For popup journey: return popup window URL; frontend opens new window
- [ ] Update invitation state to `authenticated` after ceremony init succeeds
- [ ] Create webhook endpoint `POST /api/webhooks/qtsp/callback` to receive QTSP completion callbacks
- [ ] In webhook handler: validate callback signature (QTSP-specific), extract signer cert and signature bytes, call artefact-creation handler
- [ ] Write controller tests for ceremony init and webhook callback validation

## Phase 7: Qualified Timestamp Integration

- [ ] Create `QualifiedTimestampService` with method `obtainTimestamp(documentHash, certificateChain)`
- [ ] Integrate with configured qualified TSA (e.g., NLTime, Digidentity TSA, KPN TSA)
- [ ] Implement TSA request: POST to TSA endpoint with document hash, retrieve RFC 3161 TimeStampToken
- [ ] Implement retry logic: exponential backoff (1s, 3s, 9s) up to 3 attempts
- [ ] On retry exhaustion: log error, set invitation to failed with reason `timestampUnavailable`
- [ ] Extract timestamp nonce, TSA issuer name, TSA certificate fingerprint from RFC 3161 response
- [ ] Populate `signature_artefact.qualifiedTimestamp` with TSA data
- [ ] Write service tests for TSA communication and retry logic

## Phase 8: Signature Artefact Creation

- [ ] Create `SignatureArtefactService` with method `createArtefact(invitation, qtspCallback, qualifiedTimestamp)`
- [ ] Extract from callback: signature bytes, signer certificate subject DN, certificate fingerprint
- [ ] Call `checkDocumentHash()` to ensure document unchanged
- [ ] Call `QualifiedTimestampService.obtainTimestamp()` to get qualified timestamp
- [ ] Call `LtvEmbeddingService.embedRevocationInfo()` to fetch and embed CRL/OCSP
- [ ] Build PAdES/XAdES/CAdES container with signature, certificate chain, timestamp, revocation info
- [ ] Store signed document as new file version via `FileService`
- [ ] Persist `signature_artefact` via `ObjectService`
- [ ] Update `signature_invitation` state to `signed` and set `signedAt`
- [ ] Update `signature_request` state per signing order (next or completed)
- [ ] Write audit event with artefact details
- [ ] Write service tests for artefact creation and container building

## Phase 9: Long-Term Validation (LTV) Embedding

- [ ] Create `LtvEmbeddingService` with methods: `fetchRevocationInfo()`, `embedRevocationInfo()`, `refreshLtvRevocation()`
- [ ] In `fetchRevocationInfo()`: query certificate CRL distribution points, fetch CRL files; query OCSP responders, obtain OCSP response
- [ ] Cache fetched CRL and OCSP responses for 24 hours to avoid re-fetching within same day
- [ ] In `embedRevocationInfo()`: call adapter method to embed revocation data into signature container (PAdES/XAdES/CAdES format specific)
- [ ] Document adapter responsibility: adapters provide format-specific embedding logic
- [ ] Set `signature_artefact.ltvSealedAt` to timestamp of embedding completion
- [ ] Write service tests for revocation info fetching and embedding

## Phase 10: Background Job — LTV Refresh

- [ ] Create `RefreshLtvJob` (Nextcloud background job)
- [ ] Query all `signature_artefact` objects with `verificationStatus=valid-ltv` and `ltvSealedAt` older than 18 months
- [ ] For each artefact: call `LtvEmbeddingService.refreshLtvRevocation()`
- [ ] In `refreshLtvRevocation()`: fetch fresh revocation info, obtain new document timestamp from qualified TSA, re-embed in signature container
- [ ] Update `signature_artefact.ltvSealedAt` to new timestamp
- [ ] Log refresh event in audit trail with evidence (revocation info freshness)
- [ ] On refresh error: log error, set `verificationStatus` to `unknown` (see Verification Refresh job)
- [ ] Register job in `Application::registerBackgroundJobs()` with default daily schedule (02:00 UTC)
- [ ] Write integration test for LTV refresh job with mock TSA

## Phase 11: Background Job — Verification Refresh

- [ ] Create `RefreshSignatureVerificationJob` (Nextcloud background job)
- [ ] Query all `signature_artefact` objects with `lastVerificationAt` older than 1 month (default)
- [ ] For each artefact: call `SignatureVerificationService.verifyArtefact()`
- [ ] In `verifyArtefact()`: extract chain, timestamp, revocation info from container; validate all components
- [ ] Validate timestamp nonce and signature; validate chain against EU Trusted List (consume DSL URL per member state, cached hourly)
- [ ] Validate OCSP and CRL freshness (check revocation info age against CA policy)
- [ ] Set `verificationStatus` based on validation result:
  - `valid` if signature and timestamp valid, revocation info present but age unclear
  - `valid-ltv` if all chain, timestamp, revocation info present and current
  - `unknown` if any chain validation inconclusive (e.g., intermediate CA cert expired)
  - `invalid` if signature/timestamp verification fails or revocation shows certificate was revoked
- [ ] Update `signature_artefact.lastVerificationAt` and `verificationStatus`
- [ ] If status regressed from `valid-ltv` to `unknown`/`invalid`: dispatch alert notification to request owner with failure detail and remediation hints
- [ ] Log verification event in audit trail with evidence (validation result, chain status)
- [ ] Register job with default monthly schedule (1st of month, 03:00 UTC)
- [ ] Write integration test for verification refresh with mock TSA and trusted list

## Phase 12: Background Job — Request Expiry

- [ ] Create `ExpireSigningRequestsJob` (Nextcloud background job)
- [ ] Query all `signature_request` objects with `state` in (draft, awaitingSigners, partiallySigned) and `expiresAt` < now
- [ ] For each expired request: move state to `expired`, update all invitations to `expired`
- [ ] Log expiry event in audit trail
- [ ] Register job with hourly schedule (every hour at minute 0)

## Phase 13: Audit Trail Integration

- [ ] Create `SignatureAuditService` extending `AuditTrailService`
- [ ] Log audit events for: requestCreated, invitationSent, invitationViewed, signerAuthenticated, signatureCompleted, signatureFailed, requestCancelled, ltvSealed, verificationRefreshed
- [ ] For each event: capture actor (signer email or system identifier), IP address (QTSP ceremony calls only, null for background jobs), user agent, evidence excerpt (relevant metadata)
- [ ] Qualified-timestamp signature-completion events with same timestamp as artefact's `qualifiedTimestamp`
- [ ] Ensure audit events are append-only in OpenRegister (no updates, only inserts)
- [ ] Write audit export endpoint: `GET /api/signatures/{requestId}/audit` returns chronological audit trail
- [ ] Write service tests for audit logging

## Phase 14: Data Integrity & Document Freeze

- [ ] Implement `DocumentFreezeService` with methods: `freezeDocument()`, `isFrozen()`, `unfreeze()`
- [ ] On `createRequest()`: call `freezeDocument()` to mark document as frozen for signing
- [ ] Implement document-edit guard: before allowing edit, check if document is frozen for an active signing request; if yes, reject with message "document is being signed"
- [ ] On request completion, cancellation, or expiry: call `unfreeze()` to allow document edits again
- [ ] On document edit detection: call `checkDocumentHash()` on all active signing requests; move mismatched requests to failed
- [ ] Write integration tests for document freeze and edit detection

## Phase 15: Admin UI — QTSP Configuration

- [ ] Create CnDetailPage view for `QtspConfiguration` schema
- [ ] Display fields: `qtspId`, `displayName`, `adapterVersion`, `supportedFormats`, `supportedLoa`, `defaultSignatureFormat`, `userJourney`, `enabled`
- [ ] Add credential management UI: link to OpenConnector source with `sourceId`; display credential status (authenticated, invalid, expired)
- [ ] Implement enable/disable toggle for each QTSP
- [ ] Add test endpoint per QTSP: button to send test signature request, receive callback, validate round-trip
- [ ] Use existing `CnDetailPage`, `CnDetailGrid`, `CnDetailCard` components from platform
- [ ] Write permission check: only admins can view/edit QTSP configuration

## Phase 16: Requester UI — Draft & Preview

- [ ] Create `CnDetailPage` view for `SignatureRequest` creation
- [ ] Step 1: Document selection and preview
  - [ ] File picker to select document
  - [ ] PDF/document preview
  - [ ] Display immutable version pointer and hash
  - [ ] Allow cancellation
- [ ] Step 2: Signer list entry
  - [ ] Table with columns: Name, Email, Role, LoA, Order (for sequential), Actions
  - [ ] Add/remove signer buttons
  - [ ] Role dropdown (customizable list or free-text)
  - [ ] LoA selector: substantial, high
  - [ ] For sequential: auto-assign signerOrder
  - [ ] Allow reordering (drag-drop for sequential)
- [ ] Step 3: Request confirmation
  - [ ] QTSP selector (dropdown of enabled QTSPs)
  - [ ] Signature format selector (filtered per QTSP's supportedFormats)
  - [ ] Signing order selector: parallel or sequential (hide signerOrder controls if parallel)
  - [ ] Expiry date picker (default 30 days)
  - [ ] Assurance level selector: qes or aes (defaults to qes)
  - [ ] Preview all settings
  - [ ] Confirm button
- [ ] On confirm: call `SignatureRequestService.confirmRequest()`, invitations are dispatched
- [ ] After confirmation: show status page with list of pending signers and signing links (for testing/resend)
- [ ] Write Vue tests for form submission and validation

## Phase 17: Signer UI — Invitation & Ceremony

- [ ] Create `SigningPage.vue` component for public invitation link (no auth required, but invitation state checked)
- [ ] Decode signing link token, fetch associated invitation and request
- [ ] Display document preview and requester info
- [ ] Show current signer name, email, role
- [ ] Button: "Start Signing"
- [ ] On button click: validate invitation state (pending/viewed), validate document hash, call ceremony init endpoint
- [ ] Handle ceremony user journey:
  - [ ] Embedded: render QTSP iframe with ceremony URL
  - [ ] Redirect: navigate to QTSP signing URL
  - [ ] Popup: open new window with QTSP signing URL
- [ ] Poll signing status (or use WebSocket) until ceremony completion callback received
- [ ] Display success/error message
- [ ] For sequential: show "waiting for previous signer" message if out of order
- [ ] For declined invitations: show decline form with optional reason, submit to decline endpoint
- [ ] Write Vue component tests

## Phase 18: Artefact Viewing & Audit Display

- [ ] Extend `CnDetailPage` for `SignatureArtefact` display
- [ ] Display fields: signerName, signerCertificateSubject, signedAt, qualifiedTimestamp, verificationStatus, ltvSealedAt
- [ ] Verification history: show `verificationStatus` timeline, last verification date, next verification date
- [ ] Download button: allows requester/owner to download signed document
- [ ] Download button: allows requester/owner to export audit trail as CSV/JSON
- [ ] Add audit timeline view: chronological display of all audit events with actor, event type, timestamp, evidence
- [ ] Add LTV refresh status: show if LTV refresh has been run, when, success/failure
- [ ] Use `CnDetailPage`, `CnDetailGrid`, `CnTimelineStages` components
- [ ] Write permission check: only requester/owner/admin can view artefact and audit trail

## Phase 19: Endpoint Security & Authorization

- [ ] All mutation endpoints: require authentication + per-request authorization check
- [ ] `POST /api/signatures` (create request): check authenticated user, allow if not read-only role
- [ ] `POST /api/signatures/{requestId}/confirm`: check requester owns request
- [ ] `POST /api/signatures/{requestId}/cancel`: check requester owns request or is admin
- [ ] `POST /api/signatures/{requestId}/invitations/{invitationId}/ceremony/init`: check invitation exists and not yet signed (state-based check, no user ID match needed)
- [ ] `POST /api/webhooks/qtsp/callback`: validate QTSP callback signature (QTSP-specific), no user auth required
- [ ] `GET /api/signatures/{requestId}`: check requester owns request or is admin
- [ ] `GET /api/signatures/{requestId}/audit`: check requester owns request or is admin
- [ ] `GET /api/signatures/{requestId}/invite/{token}`: no auth required, but token validation required
- [ ] Write controller tests for authorization checks

## Phase 20: Error Handling & User Messaging

- [ ] Implement error scenarios:
  - [ ] Document hash mismatch → move request to failed, show user-friendly message
  - [ ] TSA unavailable → log retry attempts, show user message after 3 retries
  - [ ] Signer LoA insufficient → reject signature, show reason to signer
  - [ ] Request expired → show expiry message on signing link
  - [ ] Request cancelled → show cancellation message on signing link
  - [ ] Out-of-order signer (sequential) → block ceremony, show "waiting for X" message
  - [ ] QTSP ceremony error → log callback error, move invitation to failed, notify requester
- [ ] All user-facing error messages: generic, no technical details; log full error server-side
- [ ] Write error scenario tests

## Phase 21: Integration with docudesk Base

- [ ] Integration point: when signing request is confirmed, freeze document (see Phase 14)
- [ ] Integration point: when signature completes, store signed PDF as new file version via `FileService`
- [ ] Integration point: link signature artefact to document version (via relation or metadata)
- [ ] Implement document-version API: `GET /api/document/{docId}/signatures` returns list of signature artefacts on all versions
- [ ] Add document-edit guard: check if document has active signing request; if yes, block edit with message
- [ ] Write integration tests with mock docudesk base APIs

## Phase 22: Reuse Analysis Task (ADR-001 Requirement)

- [ ] Audit existing `ObjectService`, `RelationService`, `AuditTrailService`, `FileService`, `NotificationService`, `AuthorizationService`, `SchemaService` for reuse opportunities
- [ ] Document findings: which services are used, how, and whether custom code is justified
- [ ] Verify NO custom CRUD, list, search, or form components are built
- [ ] Verify NO custom audit logging (use AuditTrailService)
- [ ] Verify NO custom permission system (use AuthorizationService)
- [ ] Write findings in design.md Reuse Analysis section (already done)

## Phase 23: Seed Data Generation Task (ADR-001 Requirement)

- [ ] Verify seed data is included in `lib/Settings/docudesk_signatures_register.json` (already done in Phase 1)
- [ ] Test seed data import on clean install: verify objects are created with correct properties
- [ ] Test idempotency: re-import same seed data, verify no duplicates created
- [ ] Document seed data quality (Dutch values, realistic names, valid postcodes, correct formatting)
- [ ] Write migration test for seed data (already covered in Phase 1)

## Phase 24: Deduplication Check Task (ADR-001 Requirement)

- [ ] Search codebase for existing signature-related code: `grep -r "signature" lib/Service/`
- [ ] Search for QTSP-related code: `grep -r "qtsp\|trust.*service" lib/Service/`
- [ ] Search for LTV-related code: `grep -r "ltv\|revocation\|timestamp" lib/Service/`
- [ ] Verify no overlap with existing implementations
- [ ] Document findings (already covered in design.md Deduplication Check section)

## Phase 25: Testing — Unit & Integration

- [ ] Unit tests for all services: SignatureRequestService, SignatureInvitationService, SignatureArtefactService, QualifiedTimestampService, LtvEmbeddingService, SignatureVerificationService
- [ ] Integration tests for state machine: all state transitions, error cases
- [ ] Integration tests for multi-party signing: sequential and parallel flows
- [ ] Integration tests for QTSP adapters: mock QTSP callbacks, verify artefact creation
- [ ] Integration tests for background jobs: LTV refresh, verification refresh, request expiry
- [ ] Controller tests: all endpoints, authorization checks, error handling
- [ ] End-to-end tests: full signing flow from request creation to completion
- [ ] Test coverage: aim for 80%+ coverage on business logic, 100% on critical paths (state transitions, authorization)

## Phase 26: Browser/Manual Testing

- [ ] Test request creation flow: document selection, signer list entry, preview, confirmation
- [ ] Test sequential signing: confirm signers are enforced in order, out-of-order blocked
- [ ] Test parallel signing: confirm all signers can sign concurrently
- [ ] Test QTSP ceremony: for each shipped QTSP (or mock), confirm ceremony initiates and callback is handled
- [ ] Test signer invitations: confirm email dispatched, link valid, signing page renders
- [ ] Test document freeze: confirm document edits blocked during active request, allowed after completion
- [ ] Test document hash mismatch: edit document after request creation, confirm request moves to failed
- [ ] Test request cancellation: cancel request, confirm invitations invalidated, signing links show cancellation message
- [ ] Test request expiry: confirm request expires after `expiresAt`, signing links show expiration message
- [ ] Test audit trail: confirm events logged for all state transitions, visible in UI
- [ ] Test verification refresh: confirm status refreshed, alerts sent on regression
- [ ] Test multiple QTSPs: confirm QTSP selector works, settings persist per instance
- [ ] Accessibility testing: WCAG 2.1 AA compliance on all public pages (signing page)
- [ ] Performance testing: concurrent signing requests, LTV refresh job scaling

## Phase 27: Documentation

- [ ] Write `QTSP_ADAPTER_INTEGRATION.md`: guide for third-party QTSP adapter developers, interface specification, example adapter
- [ ] Write `QES_USER_GUIDE.md`: guide for requesters (legal/contracts teams) on creating signing requests
- [ ] Write `QES_SIGNER_GUIDE.md`: guide for signers on clicking links and completing ceremonies
- [ ] Write `QES_ADMIN_GUIDE.md`: guide for admins on configuring QTSPs, credentials, schedules
- [ ] Write `QES_COMPLIANCE_GUIDE.md`: audit trail access, verification status interpretation, LTV refresh monitoring
- [ ] Add `ARCHITECTURE.md` section for QES subsystem: state machine diagrams, adapter pattern, integration points
- [ ] Document all API endpoints in OpenAPI schema
- [ ] Document all background job schedules and tuning parameters

## Phase 28: Deployment & Rollout

- [ ] Migrate from dev/test to staging environment
- [ ] Run full test suite on staging with real QTSP endpoints (or sandboxes)
- [ ] Validate OpenConnector source templates for all 4 shipped QTSPs
- [ ] Validate OpenRegister schema registration and seed data import
- [ ] Run background jobs on staging: confirm LTV refresh, verification refresh, expiry jobs work
- [ ] Performance testing: concurrent request creation and signing
- [ ] Staging sign-off: legal, IT security, operations teams
- [ ] Deploy to production
- [ ] Monitor error rates, background job completion, QTSP callback latency
- [ ] Rollout communication: announce QES support to customer base, provide links to guides

---

## Task Dependencies

- Phase 1 (Data Model) must complete before all subsequent phases
- Phase 2 (QTSP Adapters) can run parallel with Phase 3-5, but Phase 6-8 depend on it
- Phase 3-5 (State Machine, Invitations) can run parallel with Phase 2
- Phase 6-9 (Ceremony, Timestamp, Artefact) depend on Phases 2-5
- Phase 10-12 (Background Jobs) depend on Phase 9 (Artefact creation)
- Phase 13-21 (Audit, UI, Integration) can run parallel with Phases 6-12
- Phase 22-24 (Reuse/Dedup checks) should be done during Phase 1-15
- Phase 25-27 (Testing, Docs) can start as soon as Phase 1-21 are drafted, finalize after Phase 21
- Phase 28 (Deployment) depends on all prior phases

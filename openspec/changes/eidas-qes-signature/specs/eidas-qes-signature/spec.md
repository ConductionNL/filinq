# Specification: eIDAS Qualified Electronic Signature (QES)

## REQ-SIG-001 — Pluggable QTSP adapter for QuoVadis, KPN ID, Digidentity, Connectis

The system SHALL expose every QTSP integration as an `IQtspAdapter` implementation registered through OpenConnector, SHALL ship adapters for QuoVadis, KPN ID, Digidentity, and Connectis, and SHALL allow additional adapters to be installed without modifying core docudesk code.

### Scenario: Route signing request to selected QTSP adapter

- **GIVEN** an instance with the four shipped QTSPs configured
- **WHEN** a signing request is created with `qtspId=kpnid`
- **THEN** the request SHALL be routed to the KPN ID adapter and SHALL fail validation if KPN ID's configured `supportedFormats` does not include the requested `signatureFormat`

### Scenario: Third-party QTSP adapter registration

- **GIVEN** a third-party QTSP adapter installed as an app
- **WHEN** the adapter registers a new `qtspId` via OpenConnector's source template mechanism
- **THEN** it SHALL appear in the QTSP selector for new signing requests with no core changes required

## REQ-SIG-002 — Document freeze at request creation

The system SHALL freeze the document content (capturing its immutable version pointer and SHA-256 hash) at the moment the signing request is created, and SHALL refuse to start the QTSP signing ceremony if the document's current hash no longer matches the frozen hash.

### Scenario: Document edited after request creation fails ceremony

- **GIVEN** a signing request created with `documentHash=H1`
- **WHEN** the document is edited after request creation and before any signer signs
- **THEN** the request SHALL be moved to `failed` state with reason `documentChanged` and all pending invitations SHALL be cancelled

### Scenario: Unchanged document allows ceremony to proceed

- **GIVEN** a signing request whose frozen hash still matches the document's current hash
- **WHEN** a signer initiates the QTSP ceremony
- **THEN** the ceremony SHALL proceed and the QTSP SHALL receive the frozen-hash content for signing

## REQ-SIG-003 — Preview → invitation → ceremony signing flow

The system SHALL support a three-step signing flow: requester previews and confirms the document and the signer list; invitations are dispatched; each signer authenticates to the QTSP at their required LoA and completes the signing ceremony on the QSCD.

### Scenario: Requester confirms draft and invitations are dispatched

- **GIVEN** a requester confirming a draft signing request with two signers
- **WHEN** they submit the confirmation
- **THEN** every invitation SHALL be created in `pending` state, the request SHALL move to `awaitingSigners`, and each invitation SHALL be dispatched as an email containing a one-time signing link valid for 30 days

### Scenario: Signer clicks invitation link and views document

- **GIVEN** a signer clicking their signing link
- **WHEN** the link is valid and not expired
- **THEN** the signer SHALL be redirected to a document preview page, the invitation SHALL move to `viewed`, and a preview-viewed event SHALL be logged

### Scenario: Signer redirected to QTSP ceremony

- **GIVEN** a signer confirming they wish to proceed with signing
- **WHEN** they are authenticated and cleared for the QTSP ceremony
- **THEN** the signer SHALL be redirected to the configured QTSP user journey (embedded/redirect/popup per config) and the invitation SHALL move to `authenticated`

### Scenario: QTSP completion callback creates signature artefact

- **GIVEN** a signer who has authenticated at their required LoA and completed the QSCD ceremony
- **WHEN** the QTSP posts the completion callback to the docudesk webhook endpoint
- **THEN** the invitation SHALL move to `signed`, a `signature_artefact` SHALL be persisted, and the request SHALL advance per `signingOrder` (next signer for sequential, or `completed` if all signed for parallel)

## REQ-SIG-004 — Sequential and parallel multi-party signing

The system SHALL support both `sequential` and `parallel` multi-signer flows, SHALL enforce signer order in `sequential` mode, and SHALL allow any signer to sign at any time in `parallel` mode.

### Scenario: Sequential request blocks out-of-order signers

- **GIVEN** a sequential request with signers ordered Alice (signerOrder=1), Bob (signerOrder=2), Carol (signerOrder=3)
- **WHEN** Bob's invitation link is clicked before Alice has signed
- **THEN** the QTSP ceremony SHALL NOT start, the user SHALL see a "waiting for Alice to sign" message, and Bob's invitation SHALL remain `pending` until Alice's signature completes

### Scenario: Parallel request allows concurrent signing

- **GIVEN** a parallel request with three signers (Alice, Bob, Carol)
- **WHEN** Carol completes her signature while Alice and Bob are still pending
- **THEN** the request SHALL move to `partiallySigned` (not `completed`), and Alice and Bob SHALL remain able to sign concurrently without order constraints

### Scenario: Sequential request cancelled when any signer declines

- **GIVEN** a sequential request with three signers
- **WHEN** Bob declines the signing invitation (before signing)
- **THEN** the request SHALL move to `cancelled`, remaining signers SHALL receive a cancellation notification, and no further signatures SHALL be accepted

## REQ-SIG-005 — Long-Term Validation (LTV) embedding

The system SHALL produce signatures in an LTV-capable format (PAdES-B-LTA, XAdES-B-LTA, or CAdES-B-LTA per request) with every certificate in the trust chain, the qualified timestamp, and CRL+OCSP responses embedded at sign time; and SHALL refresh embedded revocation info on a periodic background job so the signature remains LTV-valid through the document's retention period.

### Scenario: Signature verifies as LTV-enabled

- **GIVEN** a completed signing request with `signatureFormat=pades-b-lta`
- **WHEN** the signed PDF is verified by an external eIDAS-aware validator (e.g., via DSS, Swisscom eSignature SDK, or Adobe Reader)
- **THEN** the validator SHALL report `LTV enabled` with all chain certificates, the qualified timestamp, and CRL+OCSP responses present and valid

### Scenario: LTV refresh job extends revocation validity

- **GIVEN** an existing PAdES-B-LTA signature created 18 months ago, approaching the end of its embedded OCSP response validity window
- **WHEN** the LTV refresh background job runs (configured daily at 02:00 UTC)
- **THEN** a new document timestamp SHALL be obtained from a qualified TSA and embedded, extending the validity window, and the artefact's `ltvSealedAt` SHALL be updated to the refresh timestamp

## REQ-SIG-006 — Qualified timestamp from a qualified TSA

The system SHALL obtain a qualified timestamp from a qualified Trust Service Provider Time-Stamping Authority for every completed signature, and SHALL refuse to mark a request `completed` if the timestamp cannot be obtained.

### Scenario: TSA unavailable triggers retry

- **GIVEN** a signing ceremony where the QTSP returns the signature value but the configured qualified TSA is unreachable
- **WHEN** the artefact-build step runs
- **THEN** the artefact SHALL be retried up to three times with exponential backoff (1s, 3s, 9s); on permanent failure the invitation SHALL be moved to `failed` with `reason=timestampUnavailable` and the request SHALL stay `partiallySigned` or `awaitingSigners`

### Scenario: Successful timestamp populates artefact

- **GIVEN** a successfully timestamped signature
- **WHEN** the artefact is persisted
- **THEN** `qualifiedTimestamp.tsa` (TSA issuer name), `qualifiedTimestamp.tsaCertificateFingerprint`, `qualifiedTimestamp.timestampedAt`, and `qualifiedTimestamp.nonce` SHALL all be populated

## REQ-SIG-007 — Immutable audit log with qualified timestamps

The system SHALL write an immutable audit-log entry for every state transition, every signer authentication outcome, and every signature artefact creation; entries SHALL be qualified-timestamped where the source event is signature-related, and SHALL be retrievable per request and per document for the document's full retention period.

### Scenario: State transition logged with metadata

- **GIVEN** any state transition on a signing request (e.g., draft → awaitingSigners)
- **WHEN** the transition completes
- **THEN** an `audit_event` row SHALL be written with `eventType`, `timestamp`, `actor`, `ipAddress` (where applicable), and an evidence excerpt; the row SHALL be append-only and immutable

### Scenario: Signature completion event cross-referenced with timestamp

- **GIVEN** a signature completion event
- **WHEN** the audit row is written
- **THEN** it SHALL include the same qualified timestamp as the artefact's `qualifiedTimestamp` and SHALL be cross-referenced via `audit_event.evidence.artefactId` for traceability

### Scenario: Audit events retrievable by document

- **GIVEN** a document ID with multiple active and completed signing requests
- **WHEN** a compliance officer queries audit events for that document ID
- **THEN** all related events SHALL be returned in chronological order, grouped by request, with full traceability of every state change and authentication outcome

## REQ-SIG-008 — Signer authentication enforced at declared LoA

The system SHALL require each signer to authenticate at or above their invitation's `signerLoa` before the QSCD ceremony can begin, and SHALL refuse to accept a signature whose returned authentication context falls below the required level.

### Scenario: Insufficient LoA rejects signature

- **GIVEN** an invitation with `signerLoa=high`
- **WHEN** the QTSP returns a completion callback with authentication context `loa=substantial` (below the required `high`)
- **THEN** the artefact SHALL NOT be persisted, the invitation SHALL move to `failed` with `reason=insufficientLoa`, and the audit log SHALL capture the LoA mismatch with evidence

### Scenario: Sufficient LoA allows signature

- **GIVEN** an invitation with `signerLoa=substantial`
- **WHEN** the signer authenticates at `loa=substantial` or `loa=high` (at or above)
- **THEN** the ceremony SHALL proceed normally, the invitation SHALL move to `signed`, and the artefact SHALL be created with `assuranceLevel` set to the signer's actual LoA

## REQ-SIG-009 — Request cancellation and expiry

The system SHALL allow the requester (or a user with cancel permission) to cancel a signing request at any point before `completed`, SHALL automatically expire requests past `expiresAt`, and SHALL invalidate all outstanding invitations on either event.

### Scenario: Requester cancels request

- **GIVEN** a signing request in `awaitingSigners` or `partiallySigned` state
- **WHEN** the requester (or an authorized user) cancels it via the UI
- **THEN** the request SHALL move to `cancelled`, every `pending`/`viewed`/`authenticated` invitation SHALL be invalidated, clicking any invitation link SHALL show a "request cancelled" page rather than starting a QTSP ceremony, and a cancellation event SHALL be logged

### Scenario: Automatic expiry invalidates request

- **GIVEN** a signing request whose `expiresAt` timestamp is now in the past
- **WHEN** the expiry background job runs (configured to run every hour at minute 0)
- **THEN** the request SHALL move to `expired`, outstanding invitations SHALL be invalidated, and signers attempting to access their links SHALL see an "request expired" page with the expiry date

## REQ-SIG-010 — Verification refresh and validity reporting

The system SHALL re-verify every completed signature artefact on a configurable schedule (default monthly), SHALL update `verificationStatus`, and SHALL alert via the docudesk notification channel when a signature drops from `valid-ltv` to `unknown` or `invalid`.

### Scenario: LTV signature remains valid

- **GIVEN** a completed artefact with `verificationStatus=valid-ltv` created 6 months ago
- **WHEN** the verification refresh job runs (configured monthly on the 1st at 03:00 UTC)
- **THEN** the embedded chain and revocation info are re-validated, `verificationStatus` SHALL remain `valid-ltv`, the refresh timestamp SHALL be recorded in the audit log, and no alert SHALL be sent

### Scenario: Verification regression triggers alert

- **GIVEN** a completed artefact whose embedded OCSP response chain no longer verifies (e.g., the issuing CA's certificate has expired and LTV refresh failed to extend it in time)
- **WHEN** the verification refresh job runs
- **THEN** `verificationStatus` SHALL move to `unknown` or `invalid` per the failure mode, a notification/alert SHALL be dispatched to the request's owner with the failure detail and suggested remediation, and an audit event SHALL be logged with the verification failure evidence

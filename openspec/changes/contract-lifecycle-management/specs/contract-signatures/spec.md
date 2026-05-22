## ADDED Requirements

### Requirement: Contract signature integration with e-signature providers

The system SHALL define a `contractSignature` schema and integration layer for e-signature providers (DocuSign, SignHey, manual). The schema SHALL capture: `contractId` (FK contract), `versionId` (FK contractVersion), `signatoryName` (string), `signatoryEmail` (string), `party` (enum: us, counterparty), `provider` (enum: docusign, signhey, manual), `externalEnvelopeId` (string, the provider's envelope ID), `status` (enum: pending, opened, signed, declined, expired), `signedAt` (ISO-8601 timestamp, null until signed), `certificateBlob` (optional binary, certificate of completion from provider).

#### Scenario: Signature envelope is created in provider when user sends contract for signature
- **WHEN** a user POSTs `/api/contracts/<id>/send-for-signature` with `provider: "docusign"`, `signatories: [{ name: "John Doe", email: "john@counterparty.nl", party: "counterparty" }]`
- **THEN** the response is 200
- **AND** the system calls DocuSign API to create an envelope with the contract PDF
- **AND** a ContractSignature record is created with status "pending", externalEnvelopeId set to DocuSign's envelope ID
- **AND** the contract transitions to status `awaiting_signature`

#### Scenario: Manual signing option is available (offline signature)
- **WHEN** a user POSTs `/api/contracts/<id>/send-for-signature` with `provider: "manual"`
- **THEN** the system creates a ContractSignature record with status "pending", no externalEnvelopeId
- **AND** the contract transitions to `awaiting_signature`
- **AND** the user can later upload a signed PDF and transition the contract to `active`

#### Scenario: Signature status is tracked via provider webhooks
- **WHEN** a signer signs the document in DocuSign
- **THEN** DocuSign sends a webhook to `/api/webhooks/signature-envelope/docusign`
- **AND** the system updates the ContractSignature record: status = "signed", signedAt = <timestamp>
- **AND** if all signatories have signed, the system transitions the contract to `active`, attaches the signed PDF, and captures the certificate

#### Scenario: Contract transitions to active when all signatures are collected
- **GIVEN** a contract with two signatories (us, counterparty) both of whom have signed
- **WHEN** the second signature is recorded
- **THEN** the contract transitions to status `active`
- **AND** the complete, signed PDF is attached to the contract version

#### Scenario: Missing required signatories are rejected
- **WHEN** a user tries to send for signature without specifying all required signatories
- **THEN** the response is 400 indicating missing signatories
- **AND** no envelope is created

### Requirement: Webhook endpoint handles e-signature provider callbacks securely

The system SHALL expose a webhook endpoint at `/api/webhooks/signature-envelope/{provider}` that accepts POST requests from e-signature providers (DocuSign, SignHey). Each webhook request SHALL include a provider-specific signature header (e.g., X-DocuSign-Signature-1) that MUST be validated using the provider's public key before processing. Invalid or tampered requests SHALL be rejected with 403.

#### Scenario: DocuSign webhook updates signature status
- **WHEN** DocuSign sends a webhook with payload `{ envelopeId: "<id>", status: "completed", signers: [...], certificateBlob: "..." }`
- **AND** the signature is valid (verified using DocuSign's public key)
- **THEN** the system updates the matching ContractSignature record
- **AND** the response is 200 OK

#### Scenario: Invalid webhook signature is rejected
- **WHEN** a webhook is received with an invalid X-DocuSign-Signature-1 header
- **THEN** the response is 403 Forbidden
- **AND** no database changes are made
- **AND** the incident is logged for security audit

#### Scenario: Webhook is idempotent (repeated calls have no side-effect)
- **WHEN** the same DocuSign webhook is delivered twice (due to provider retry)
- **THEN** the first call updates the ContractSignature record
- **AND** the second call recognises the same envelopeId and signature state, returns 200, and makes no changes

### Requirement: Certificate of completion is captured and stored with contract

When a signature envelope is completed (all signers have signed), the e-signature provider MAY include a certificate of completion (ISO 14533 LTV profile, eIDAS-compliant qualified signature certificate). The system SHALL store this blob in the ContractSignature.certificateBlob field and make it available for download via `GET /api/contracts/<id>/signature-certificate`.

#### Scenario: Certificate is stored after completion
- **WHEN** a DocuSign envelope is completed and the webhook includes a `certificateBlob`
- **THEN** the system stores the base64-encoded blob in ContractSignature.certificateBlob
- **AND** the certificate is retrievable via API

#### Scenario: Certificate is downloadable
- **WHEN** a user GETs `/api/contracts/<id>/signature-certificate`
- **THEN** the response is 200 with Content-Type application/octet-stream
- **AND** the certificate blob is returned as a binary file

### Requirement: Signature provider is configurable per tenant

The system SHALL support a configuration section `contract.signature_providers` that lists enabled providers (docusign, signhey, manual) and their credentials (API keys, secrets, auth URLs). Each tenant can enable a subset of providers. The `send-for-signature` endpoint SHALL allow the user to select a provider at runtime.

#### Scenario: Admin configures e-signature providers
- **WHEN** an admin sets `contract.signature_providers['docusign'] = { enabled: true, api_key: '...', api_secret: '...', environment: 'production' }`
- **THEN** DocuSign becomes available as a provider option in the contract UI

#### Scenario: User selects provider when sending for signature
- **WHEN** a user opens the "send for signature" dialog
- **THEN** a dropdown lists all enabled providers (docusign, signhey, manual)
- **AND** the user can select one

### Requirement: Signature envelope status is queryable

The system SHALL expose a GET endpoint `/api/contracts/<id>/signatures` that returns all ContractSignature records for the contract, with their current status (pending, opened, signed, declined, expired).

#### Scenario: Signature status is visible in contract detail
- **WHEN** a user views a contract in status `awaiting_signature`
- **THEN** a signature section shows all signatories, their status (pending/signed), and (if available) the date they signed

#### Scenario: Expired envelopes are detected
- **WHEN** a signature envelope expires (per provider's TTL, e.g., 30 days)
- **THEN** the system receives a webhook indicating expiration
- **AND** the ContractSignature status is set to `expired`
- **AND** the contract owner is notified to resend the envelope

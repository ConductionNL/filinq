---
status: proposed
source: tender-research
tender_demand: 76% (56/74 tenders)
---

# Document Signing

## Purpose

Provide digital document signing within DocuDesk, supporting both internal (ambtelijke) and external (burger/ketenpartner) signing flows. 76% of analyzed Dutch government tenders require digital signing -- typically via ValidSign integration, but also as native capability. The flow is tightly coupled with case handling (Procest) and document creation (DocuDesk templates).

## Standards

- **eIDAS Regulation (EU 910/2014)** -- three signature levels: SES, AdES, QES
- **PAdES (ETSI EN 319 142)** -- PDF-embedded signatures
- **PKIoverheid** -- Dutch government PKI for qualified signatures
- **TSA (RFC 3161)** -- trusted timestamps on signatures

## Requirements

### REQ-SIGN-01: Sign Document from Case Context (Priority: Must)

Documents are submitted for signing from within a case (zaak) context, with support for sequential and parallel multi-signer flows.

#### Scenario: Behandelaar sends document for signing
- GIVEN a case in Procest with a generated besluit document
- WHEN the behandelaar clicks "Ter ondertekening aanbieden"
- THEN a signing request is created in DocuDesk
- AND the document is locked for editing
- AND the designated signer(s) receive a notification

#### Scenario: Sequential multi-signer flow
- GIVEN a signing request with signers [adviseur, manager, wethouder] in sequence
- WHEN adviseur signs
- THEN manager receives the signing request
- AND the document shows adviseur's signature
- AND the next signer cannot be skipped

#### Scenario: Parallel signing
- GIVEN a signing request with 3 signers in parallel
- WHEN any signer signs
- THEN the other signers can still sign independently
- AND the signing request completes when all have signed

#### Scenario: Expired signing request
- GIVEN a signing request with a 7-day deadline
- WHEN the deadline passes without all signatures
- THEN the request is marked as expired
- AND the initiator is notified
- AND the document is unlocked for editing

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SIGN-001 | Create signing request from case context with document locking | MUST | Planned |
| SIGN-002 | Sequential multi-signer flow with ordered notification | MUST | Planned |
| SIGN-003 | Parallel signing flow with independent completion | MUST | Planned |
| SIGN-004 | Signing request expiration with deadline enforcement | MUST | Planned |

### REQ-SIGN-02: Signature Levels per eIDAS (Priority: Must)

Support three eIDAS signature levels for different use cases: internal documents (SES), partner agreements (AdES), and formal decisions (QES).

#### Scenario: Simple electronic signature (internal use)
- GIVEN a user with an active Nextcloud session
- WHEN they sign a document
- THEN a SES signature is applied with user identity, timestamp, and IP
- AND the signed PDF is stored as a new version

#### Scenario: Advanced electronic signature (ketenpartners)
- GIVEN an external signing request
- WHEN the signer authenticates via email verification + SMS OTP
- THEN an AdES signature is applied
- AND a certificate is embedded in the PDF

#### Scenario: Qualified electronic signature (besluiten)
- GIVEN a document requiring QES (e.g., formal besluit)
- WHEN the signer uses a PKIoverheid certificate or eHerkenning
- THEN a QES signature with TSA timestamp is applied
- AND the signature is PAdES compliant

#### Scenario: Signature level selection
- GIVEN a signing request is being created
- WHEN the initiator configures the request
- THEN they can select the required signature level (SES, AdES, QES)
- AND the system enforces the appropriate authentication method

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SIGN-010 | SES signature with user identity, timestamp, and IP | MUST | Planned |
| SIGN-011 | AdES signature with certificate and multi-factor auth | MUST | Planned |
| SIGN-012 | QES signature with PKIoverheid certificate and TSA timestamp | MUST | Planned |
| SIGN-013 | PAdES-compliant PDF-embedded signatures | MUST | Planned |
| SIGN-014 | Signature level selectable per signing request | MUST | Planned |

### REQ-SIGN-03: External Signing Service Integration (Priority: Must)

Pluggable integration with external signing providers via OpenConnector, with ValidSign as the primary Dutch government provider.

#### Scenario: ValidSign integration via OpenConnector
- GIVEN DocuDesk is configured with a ValidSign endpoint in OpenConnector
- WHEN a signing request is initiated
- THEN the document is sent to ValidSign via API
- AND the signer receives an email from ValidSign with a signing link
- AND upon completion, the signed document is returned to DocuDesk
- AND the signed document is stored in the case dossier

#### Scenario: Pluggable signing providers
- GIVEN DocuDesk signing is configured
- WHEN an administrator selects a signing provider
- THEN ValidSign, DocuSign, Adobe Sign, or LibreSign can be configured
- AND each provider implements the same SigningProvider interface

#### Scenario: Provider failover
- GIVEN the primary signing provider is unavailable
- WHEN a signing request is initiated
- THEN the system informs the user of the unavailability
- AND no unsigned requests are silently lost

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SIGN-020 | ValidSign integration via OpenConnector API | MUST | Planned |
| SIGN-021 | Pluggable SigningProvider interface for multiple providers | MUST | Planned |
| SIGN-022 | Signed document returned and stored in case dossier | MUST | Planned |
| SIGN-023 | Provider unavailability communicated to user | MUST | Planned |

### REQ-SIGN-04: Signing Status Tracking (Priority: Must)

Track signing progress across all signers with real-time status updates and decline handling.

#### Scenario: Track signing progress
- GIVEN a signing request sent to 3 signers
- WHEN the case behandelaar views the document
- THEN they see: who has signed (with timestamp), who hasn't yet, and any rejections
- AND expired requests are highlighted

#### Scenario: Signer declines
- GIVEN a signer receives a signing request
- WHEN they decline with a reason
- THEN the signing request is paused
- AND the initiator is notified with the decline reason
- AND the case status is updated to reflect the block

#### Scenario: Signing completion
- GIVEN all designated signers have signed
- WHEN the last signature is applied
- THEN the signing request status changes to "completed"
- AND the case is notified of the completion
- AND the fully signed document is available for download

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SIGN-030 | Track per-signer status: signed (with timestamp), pending, declined | MUST | Planned |
| SIGN-031 | Decline handling with reason and initiator notification | MUST | Planned |
| SIGN-032 | Signing completion triggers case notification | MUST | Planned |
| SIGN-033 | Expired signing requests highlighted in status view | MUST | Planned |

### REQ-SIGN-05: Bulk Signing (Priority: Should)

The system MUST enable managers to sign multiple documents efficiently with a single authentication session.

#### Scenario: Manager signs multiple documents
- GIVEN a manager has 15 pending signing requests
- WHEN they select all and choose "Bulk ondertekenen"
- THEN each document is presented for review
- AND a single authentication applies to all signatures
- AND all signed documents are returned to their respective cases

#### Scenario: Partial bulk signing
- GIVEN a manager reviews 15 documents in bulk
- WHEN they decline 2 documents and sign 13
- THEN 13 documents are signed successfully
- AND 2 are declined with individual reasons
- AND all results are reported

#### Scenario: Bulk signing authentication
- GIVEN SES level is required for bulk signing
- WHEN the manager authenticates once
- THEN the session authentication applies to all signatures in the bulk
- AND no re-authentication is required per document

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SIGN-040 | Bulk signing for multiple pending documents | SHOULD | Planned |
| SIGN-041 | Single authentication for all signatures in bulk | SHOULD | Planned |
| SIGN-042 | Partial signing/decline within a bulk operation | SHOULD | Planned |

### REQ-SIGN-06: Signature Verification (Priority: Must)

Verify the integrity and validity of embedded signatures in signed documents.

#### Scenario: Verify signed document
- GIVEN a document with embedded PAdES signatures
- WHEN a user opens the document details
- THEN all signatures are listed with: signer identity, timestamp, signature level, validity
- AND tampered documents show an invalid signature warning

#### Scenario: Verify certificate chain
- GIVEN a QES signature with a PKIoverheid certificate
- WHEN the certificate chain is verified
- THEN the root CA is confirmed as a trusted PKIoverheid authority
- AND the certificate's validity period is checked

#### Scenario: Tampered document detection
- GIVEN a signed document has been modified after signing
- WHEN signature verification runs
- THEN the signature is marked as invalid
- AND a clear warning is displayed to the user

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SIGN-050 | List all signatures with signer identity, timestamp, level, and validity | MUST | Planned |
| SIGN-051 | Detect tampered documents via signature integrity check | MUST | Planned |
| SIGN-052 | Verify PKIoverheid certificate chains | MUST | Planned |

### REQ-SIGN-07: Signing Audit Trail (Priority: Must)

Maintain an immutable audit trail of all signing activities for legal compliance and accountability.

#### Scenario: Complete signing audit
- GIVEN a completed signing flow
- THEN the audit trail records: who initiated, when sent, who signed/declined (with timestamps), IP addresses, signature level, provider used
- AND the audit trail is immutable and retained for 10 years minimum

#### Scenario: Audit trail immutability
- GIVEN a signing audit trail entry exists
- WHEN any attempt is made to modify or delete it
- THEN the modification is prevented
- AND an alert is generated for the administrator

#### Scenario: Audit trail export
- GIVEN a legal request for signing records
- WHEN the audit trail is exported
- THEN all entries are available in a structured format
- AND the export includes cryptographic proof of integrity

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SIGN-060 | Record complete signing audit: initiator, signers, timestamps, IPs, level, provider | MUST | Planned |
| SIGN-061 | Immutable audit trail retained for minimum 10 years | MUST | Planned |
| SIGN-062 | Audit trail export with integrity proof | MUST | Planned |

## Non-Requirements

- **Wet signing / physical signatures** -- out of scope
- **Certificate management / PKI infrastructure** -- delegated to external CA or PKIoverheid
- **Payment for signing services** -- commercial signing providers handle billing

## Dependencies

- `document-creatie-sjablonen` -- generates documents that need signing
- `openregister:workflow-integration` -- triggers signing flows from case status changes
- `procest:bw-parafering` -- B&W parafering may trigger formal signing
- `openconnector` -- routes to external signing providers
- `openregister:audit-trail-immutable` -- stores signing audit records

### Current Implementation Status
- **Not yet implemented**: Zero implementation exists. No signing-related classes, routes, or components.
- **Dependencies not yet available**: Procest integration, OpenConnector signing, immutable audit trail

### Standards & References
- **eIDAS Regulation (EU 910/2014)**: Three signature levels
- **PAdES (ETSI EN 319 142)**: PDF-embedded signatures
- **PKIoverheid**: Dutch government PKI (maintained by Logius)
- **TSA (RFC 3161)**: Trusted timestamp protocol
- **Wet digitale overheid (Wdo)**: Dutch digital government law
- **eHerkenning**: Dutch business authentication
- **Archiefwet 1995**: 10-year audit trail retention

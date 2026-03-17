---
status: proposed
source: tender-research
tender_demand: 76% (56/74 tenders)
---

# Document Signing Specification

## Purpose

Provide digital document signing within Docudesk, supporting both internal (ambtelijke) and external (burger/ketenpartner) signing flows. 76% of analyzed Dutch government tenders require digital signing — typically via ValidSign integration, but also as native capability.

Dutch municipalities use signing for: vergunningbesluiten, overeenkomsten, verwerkersovereenkomsten, mandaatbesluiten, and B&W collegestukken. The flow is tightly coupled with case handling (Procest) and document creation (Docudesk templates).

**Market context:** ValidSign is widely used in the Dutch municipal market. Alternatives: DocuSign, Adobe Sign. Open-source: LibreSign (Nextcloud app). Many platforms only support external signing services; this spec adds both native and provider-integrated signing.

## Standards

- **eIDAS Regulation** — three signature levels:
  - Simple Electronic Signature (SES) — basic, lowest assurance
  - Advanced Electronic Signature (AdES) — linked to signatory, detects changes
  - Qualified Electronic Signature (QES) — PKIoverheid certificate, legal equivalent of handwritten
- **PAdES** (PDF Advanced Electronic Signatures) — PDF-embedded signatures
- **PKIoverheid** — Dutch government PKI for qualified signatures
- **TSA** (Time Stamp Authority) — trusted timestamp on signatures

## Requirements

### REQ-SIGN-01: Sign document from case context (Priority: Must)

#### Scenario: Behandelaar sends document for signing
- GIVEN a case in Procest with a generated besluit document
- WHEN the behandelaar clicks "Ter ondertekening aanbieden"
- THEN a signing request is created in Docudesk
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

### REQ-SIGN-02: Signature levels (Priority: Must)

#### Scenario: Simple electronic signature (internal use)
- GIVEN a user with Nextcloud session
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

### REQ-SIGN-03: External signing service integration (Priority: Must)

#### Scenario: ValidSign integration via OpenConnector
- GIVEN Docudesk is configured with a ValidSign endpoint in OpenConnector
- WHEN a signing request is initiated
- THEN the document is sent to ValidSign via API
- AND the signer receives an email from ValidSign with a signing link
- AND upon completion, the signed document is returned to Docudesk
- AND the signed document is stored in the case dossier with signing metadata

#### Scenario: Pluggable signing providers
- GIVEN Docudesk signing is configured
- WHEN an administrator selects a signing provider
- THEN ValidSign, DocuSign, Adobe Sign, or LibreSign can be configured
- AND each provider implements the same SigningProvider interface

### REQ-SIGN-04: Signing status tracking (Priority: Must)

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

### REQ-SIGN-05: Bulk signing (Priority: Should)

#### Scenario: Manager signs multiple documents
- GIVEN a manager has 15 pending signing requests
- WHEN they select all and choose "Bulk ondertekenen"
- THEN each document is presented for review
- AND a single authentication applies to all signatures
- AND all signed documents are returned to their respective cases

### REQ-SIGN-06: Signature verification (Priority: Must)

#### Scenario: Verify signed document
- GIVEN a document with embedded PAdES signatures
- WHEN a user opens the document details
- THEN all signatures are listed with: signer identity, timestamp, signature level, validity status
- AND tampered documents show an invalid signature warning

### REQ-SIGN-07: Audit trail (Priority: Must)

#### Scenario: Complete signing audit
- GIVEN a completed signing flow
- THEN the audit trail records: who initiated, when sent, who signed/declined (with timestamps), IP addresses, signature level, provider used
- AND the audit trail is immutable and retained for 10 years minimum

## Non-Requirements

- **Wet signing / physical signatures** — out of scope
- **Certificate management / PKI infrastructure** — delegated to external CA or PKIoverheid
- **Payment for signing services** — commercial signing providers handle their own billing

## Dependencies

- `document-creatie-sjablonen` — generates documents that need signing
- `openregister:workflow-integration` — triggers signing flows from case status changes
- `procest:bw-parafering` — B&W parafering may trigger formal signing after approval
- `openconnector` — routes to external signing providers (ValidSign, DocuSign)
- `openregister:audit-trail-immutable` — stores signing audit records

### Current Implementation Status
- **Not yet implemented**: This is an entirely planned spec. Zero implementation exists in the codebase.
  - No `SigningService`, `SigningController`, or signing-related classes exist in `lib/`
  - No signing-related Vue components exist in `src/`
  - No signing-related routes exist in `appinfo/routes.php`
  - No references to "signing", "ValidSign", "PAdES", or "eIDAS" exist in any PHP, Vue, or JS files
  - No signing provider interface or integration code exists
- **Dependencies not yet available**:
  - Procest integration for case-based signing flows (REQ-SIGN-01)
  - OpenConnector integration for external signing providers (REQ-SIGN-03)
  - OpenRegister audit trail immutable storage (REQ-SIGN-07)

### Standards & References
- **eIDAS Regulation (EU 910/2014)**: Defines three signature levels (SES, AdES, QES) -- legally binding across EU
- **PAdES (ETSI EN 319 142)**: PDF Advanced Electronic Signatures standard for PDF-embedded signatures
- **PKIoverheid**: Dutch government PKI framework for qualified electronic signatures (maintained by Logius)
- **TSA (RFC 3161)**: Time Stamp Authority protocol for trusted timestamps
- **Wet digitale overheid (Wdo)**: Dutch law governing digital government services including electronic signatures
- **eHerkenning**: Dutch business authentication framework, relevant for external signer verification
- **DigiD**: Dutch citizen authentication, relevant for citizen-facing signing flows
- **ETSI EN 319 132 (XAdES)**: XML Advanced Electronic Signatures (alternative to PAdES for non-PDF documents)
- **Archiefwet 1995**: 10-year audit trail retention requirement for signing records

### Specificity Assessment
- **Specific enough to implement**: No -- this is a high-level requirement spec, not an implementation spec.
- **Missing/Ambiguous**:
  - No data model defined for signing requests, signer records, or audit trail objects
  - No API endpoints specified (what routes? what request/response format?)
  - No `SigningProvider` interface defined (what methods? what lifecycle hooks?)
  - ValidSign API specifics not documented (which API version? what authentication?)
  - No Vue component designs or UI wireframes
  - PKIoverheid certificate handling not specified (how does the app access certificates?)
  - Bulk signing authentication mechanism unclear (session-based? certificate-based?)
  - No schema for signing metadata storage in OpenRegister
- **Open questions**:
  1. Will signing be a DocuDesk feature or a separate Nextcloud app?
  2. Should LibreSign (existing Nextcloud app) be used instead of building from scratch?
  3. How will external signing providers be integrated -- via OpenConnector sources or direct API integration?
  4. What is the priority ordering: ValidSign first, then native, or native first?
  5. How does the 10-year audit trail requirement interact with Nextcloud's data retention policies?

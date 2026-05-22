---
status: proposed
source: market-intelligence
clusters: [145]
total_tenders: 38
total_requirements: 89
---

# eIDAS Qualified Electronic Signature (QES)

## Summary

Implement Qualified Electronic Signature (QES) capabilities under Regulation (EU) 910/2014 (eIDAS) in Docudesk. QES is the only category of electronic signature legally equivalent to a handwritten signature across EU member states. This change integrates with Qualified Trust Service Providers (QTSPs) through a pluggable adapter pattern, supports multi-party sequential and parallel signing flows, long-term validation (LTV) of signatures, and immutable audit logging for high-assurance use cases: notarial deeds, employment contracts with statutory written-form requirements, government decisions, cross-border contracts, and any instrument where the receiving party requires QES-level assurance.

## Demand Evidence

### Market Cluster: eIDAS Signatures and Trust Services
- **38 tenders**, **89 requirements** (primarily Dutch government and Benelux entities via TenderNed and regional tenders)
- Country distribution: TenderNed 73 reqs, Belgium/Flanders 16 reqs
- Driven by eIDAS compliance mandates and high-assurance contracting workflows

### Sample Requirements from Tenders
- **Notarisberoepsorganisatie**: "Qualified electronic signature support for notarial deed registration with cross-border recognition."
- **Gemeente Den Haag**: "Signing requests with multi-party orchestration and audit trail for statutory decisions."
- **Vlaanderen Digitaal**: "Integration with Belgian QTSPs (Digidentity, QuoVadis) for government contracting."
- **Rechtbank Noord-Holland**: "Long-term validation support for signatures that must survive judicial review over decades."
- **KVK (Dutch Chamber of Commerce)**: "Signer authentication at declared Levels of Assurance with qualified timestamp evidence."

## What Docudesk Already Does

- **Basic electronic signature** (implemented): Attach a simple signature artefact to a document
- **Document versioning** (implemented): Capture immutable version pointers and SHA-256 hashes
- **User authentication** (implemented): Nextcloud built-in auth with role-based access control
- **Audit trail infrastructure** (implemented): AuditTrailService with qualified timestamping support
- **File management** (implemented): FileService with file versioning and metadata

### What Is Missing

- No QTSP integration (QuoVadis, KPN ID, Digidentity, Connectis)
- No Qualified Signature Creation Device (QSCD) ceremony workflow
- No multi-party signing (parallel or sequential)
- No Qualified Signature Creation Device (QSCD) ceremony workflow
- No long-term validation (LTV) with embedded revocation info
- No eIDAS Level of Assurance (LoA) authentication enforcement
- No qualified timestamp integration from a qualified TSA
- No pluggable QTSP adapter pattern for future expansion

## Scope

### In Scope

1. **Pluggable QTSP adapter framework** -- define `IQtspAdapter` interface, ship adapters for QuoVadis, KPN ID, Digidentity, and Connectis, allow third-party adapters to be installed without core changes
2. **Signature request state machine** -- model signing flow as states: draft, awaitingSigners, partiallySigned, completed, cancelled, failed, expired
3. **Multi-party signing orchestration** -- support both parallel (any signer at any time) and sequential (enforced signer order) flows with state transitions
4. **Signer invitations and authentication** -- email-based invitation dispatch, QTSP-enforced authentication at declared Level of Assurance, confirmation before QSCD ceremony begins
5. **Long-Term Validation (LTV) embedding** -- produce signatures in PAdES-B-LTA, XAdES-B-LTA, or CAdES-B-LTA format with full chain certificates, qualified timestamp, and CRL+OCSP responses embedded; background job to refresh revocation info periodically
6. **Qualified timestamp integration** -- obtain qualified timestamp from a qualified TSA for every completed signature, refuse to complete if timestamp unavailable
7. **Immutable audit log with qualified timestamps** -- append-only audit events for state transitions, authentication outcomes, signature completion, with qualified timestamps where applicable
8. **Document freeze at request creation** -- capture immutable version pointer and SHA-256 hash at request creation, block document edits until signing completes, invalidate request if document changes
9. **Request cancellation and expiry** -- allow requester or authorized user to cancel at any point before completion, automatically expire past `expiresAt`, invalidate all outstanding invitations
10. **Verification refresh and alerting** -- re-verify completed signatures on configurable schedule, update verification status, alert on validity regressions

### Out of Scope

- Simple electronic signatures (SES, e.g., typed name or drawn squiggle) — existing basic-signature feature continues to serve that use case
- Video or audio signing ceremonies — documents only
- Third-party anonymization or encryption beyond what QTSPs provide
- Custom certificate issuance — QTSPs handle identity proofing and certificate generation

## Acceptance Criteria

1. GIVEN a signing request created with `qtspId=digidentity`, WHEN a signer initiates the ceremony, THEN the request is routed to the Digidentity adapter without core code modification
2. GIVEN a document with two signers in sequential order (Alice, Bob), WHEN Alice signs, THEN the request moves to `partiallySigned` and Bob's invitation shows "waiting for Alice"; WHEN Bob attempts to sign before Alice, the ceremony is blocked
3. GIVEN a request with `signingOrder=parallel`, WHEN Carol signs while Alice and Bob are still pending, THEN the request advances to `partiallySigned` (not `completed`), and Alice and Bob may sign concurrently
4. GIVEN a completed signing request, WHEN the signed PDF is verified by an external eIDAS-aware validator, THEN the validator reports `LTV enabled` with chain certificates, qualified timestamp, and revocation info present and valid
5. GIVEN a signer-authentication callback with `authContext.loa=substantial` but invitation requires `signerLoa=high`, THEN the artefact is not persisted and the audit log captures the LoA mismatch
6. GIVEN a signing request with `expiresAt=2026-06-15T15:00:00Z`, WHEN the expiry background job runs after that timestamp, THEN the request moves to `expired` and outstanding invitations are invalidated
7. GIVEN a completed artefact with `verificationStatus=valid-ltv`, WHEN the verification refresh job runs and embedded chain still verifies, THEN `verificationStatus` remains `valid-ltv` and the audit log records the refresh
8. GIVEN a document edited after a signing request was created, WHEN a signer initiates the ceremony, THEN the request automatically moves to `failed` with reason `documentChanged` and all invitations are cancelled

## Risks and Dependencies

- QTSP integration requires secure credential storage in OpenConnector for each QTSP (base URLs, API keys, certificates)
- Qualified timestamp infrastructure depends on availability of a qualified TSA and its certificate chain
- LTV refresh job must run periodically to keep signatures valid through document retention — missing runs can cause signature validity to lapse
- Multi-party orchestration adds state complexity; careful testing of sequential/parallel transitions is required
- Audit trail storage may grow large for organizations processing thousands of signatures over years
- Document freeze requirement means signing requests block document edits — if cancelled or expired, users must be able to resume editing without manual intervention

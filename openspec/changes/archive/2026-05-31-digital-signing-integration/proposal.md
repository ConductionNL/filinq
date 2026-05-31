---
status: proposed
source: market-intelligence
clusters: [97, 108]
total_tenders: 101
total_requirements: 160
---

# Digital Signing Integration

## Summary

Add digital document signing capabilities to DocuDesk, supporting integration with qualified electronic signature providers (ValidSign and others) and internal signing workflows. 76% of analyzed Dutch government tenders require digital signing, making this one of the highest-demand features. This change covers the signing workflow (request, sign, verify), signature levels per eIDAS regulation, and provider integration.

## Demand Evidence

### Cluster 97: Digital signature
- **57 tenders**, **87 requirements** (primarily Dutch government via TenderNed)
- Country distribution: TenderNed 89 reqs, Belgium 1 req

### Cluster 108: ValidSign digital signing
- **44 tenders**, **73 requirements**
- Country distribution: TenderNed 104 reqs, Belgium 7 reqs
- ValidSign is the dominant signature provider in Dutch government

### Sample Requirements from Tenders
- **Gemeente Lelystad**: "De Oplossing moet worden gekoppeld worden met de externe digitale handtekening tool Valid Sign."
- **Omgevingsdienst Noordzeekanaalgebied**: "De oplossing kan brieven en beschikkingen voorzien van een rechtsgeldige elektronische ondertekening."
- **Waterschap Noorderzijlvest**: "Elektronische handtekening" (as explicit requirement)
- **Gemeente Westerkwartier**: "Het moet mogelijk zijn een digitale handtekening te zetten vanuit de meegeleverde sjablonengenerator. Documenten moeten definitief kunnen worden gemaakt."
- **Gemeente Opmeer**: "De Opdrachtnemer levert de adapter en verzorgt de werkzaamheden die aan de kant van de Oplossing noodzakelijk zijn om de koppeling te laten functioneren."

## What Docudesk Already Does

- **Document Signing spec** (proposed, not implemented): A detailed spec already exists at `openspec/specs/document-signing/spec.md` covering:
  - Sign document from case context with sequential and parallel multi-signer flows
  - eIDAS signature levels (SES, AdES, QES)
  - ValidSign provider integration
  - PAdES compliance and PKIoverheid support
  - Signature verification and audit trail
- **PDF Generation** (implemented): Can produce the PDF documents that need signing
- **Template Management** (implemented): Creates documents that feed into signing workflows

### What Is Missing
- The entire signing spec is in "Planned" status -- no implementation exists yet
- No signing provider adapter/integration code
- No signing workflow UI
- No signature verification endpoint

## Scope

This change implements the existing `document-signing` spec with focus on:

### In Scope
1. **ValidSign integration** -- adapter for ValidSign API (dominant provider in Dutch government) for creating signing requests, tracking status, and receiving signed documents
2. **Signing workflow API** -- create signing requests with sequential or parallel signer flows, track progress, handle expiration
3. **eIDAS signature levels** -- SES (simple, for internal documents), AdES (advanced, for external parties), QES (qualified, for formal besluiten via PKIoverheid)
4. **Signature verification** -- verify signature validity, check certificate chain, validate timestamps
5. **Signing UI** -- document preview with signature placement, signer notification, signing status dashboard
6. **Audit trail** -- log all signing events (requested, signed, rejected, expired) with timestamps and signer identity

### Out of Scope
- Building our own certificate authority
- Hardware security module (HSM) integration
- Handwritten signature capture (wet ink digitization)

## Acceptance Criteria

1. GIVEN a generated beschikking PDF, WHEN a signing request is created with 2 sequential signers, THEN the first signer receives a notification, AND after they sign the second signer is notified
2. GIVEN a ValidSign account is configured in admin settings, WHEN a signing request is created, THEN the document is sent to ValidSign via their API, AND the signing status is synchronized
3. GIVEN a signed document, WHEN signature verification is requested, THEN the signature validity, signer identity, and timestamp are returned
4. GIVEN a signing request with a 7-day deadline, WHEN the deadline passes, THEN the request is marked expired, AND the initiator is notified
5. GIVEN an eIDAS QES requirement, WHEN the signer uses a PKIoverheid certificate, THEN the signature is PAdES compliant with TSA timestamp

## Risks and Dependencies

- ValidSign API access requires a commercial agreement per customer
- Provider abstraction needed to support alternative providers (e.g., DocuSign, Evidos)
- QES requires PKIoverheid certificate infrastructure (customer-provided)
- Tight coupling with Procest (case handling) for the case-context signing flow
- The existing proposed spec is comprehensive; implementation should follow it closely

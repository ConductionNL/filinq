# Design: Document Signing

## Status: Proposed (Not Yet Implemented)

## Architecture (Planned)

### Standards
- eIDAS Regulation (EU 910/2014): SES, AdES, QES signature levels
- PAdES (ETSI EN 319 142): PDF-embedded signatures
- PKIoverheid: Dutch government PKI for qualified signatures
- TSA (RFC 3161): Trusted timestamps

### Signing Flows (Planned)
- Internal (ambtelijke) signing by behandelaar/manager/wethouder
- External (burger/ketenpartner) signing via invitation
- Sequential multi-signer flow (ordered chain)
- Parallel multi-signer flow (independent)

### Integration Points
- Procest (case management): Sign documents from case context
- DocuDesk templates: Sign generated documents
- ValidSign: Optional external signing provider

## ADR Compliance
- ADR-001: Signing requests stored via OpenRegister
- ADR-008: Controller -> SigningService layering

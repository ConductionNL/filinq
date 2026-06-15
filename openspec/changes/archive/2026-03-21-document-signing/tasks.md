# Tasks: document-signing

## Status: Proposed (Not Yet Implemented)

## Task 1: Signing Request Service
- [ ] Implement `SigningService` for creating and managing signing requests
- [ ] Support sequential and parallel multi-signer flows
- [ ] Implement signing request expiration handling

## Task 2: Signing API
- [ ] Implement signing request CRUD endpoints
- [ ] Implement sign action endpoint
- [ ] Implement reject/decline endpoint

## Task 3: Signature Levels
- [ ] Implement SES (Simple Electronic Signature)
- [ ] Implement AdES (Advanced Electronic Signature)
- [ ] Plan QES integration with PKIoverheid

## Task 4: Unit Tests (ADR-009)
- [ ] Test signing request creation
- [ ] Test multi-signer flow transitions
- [ ] Test expiration handling

## Task 5: Documentation + Screenshots (ADR-010)
- [ ] Write feature documentation at `docs/features/document-signing.md`

## Task 6: i18n (ADR-005)
- [ ] Add Dutch translations for signing UI
- [ ] Add English translations for signing UI

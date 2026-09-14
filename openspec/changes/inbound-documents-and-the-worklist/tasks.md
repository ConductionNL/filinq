# Tasks: inbound-documents-and-the-worklist

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12. -->

## 1. Schemas

- [ ] 1.1 Extend `intakeDocument` in the `filinq` register with `arrivedWith`, `stampedDefaults`, `defaultRule`, `partySuggestion[]`, `routing`, `acceptance` and the `detached` state (REQ-IDW-01, REQ-IDW-04)
  - `hardValidation: true`; authorization cascade declared, never omitted; descriptor version bumped so `SettingsInitializer` imports it.
- [ ] 1.2 Add `intakeDefaultRule` to the `filinq` register: channel or sender pattern, the metadata it stamps, and its order (REQ-IDW-02)

## 2. Arrival

- [ ] 2.1 One intake record per attachment, each linked to its message through `arrivedWith`; assigning a message offers its attachments (REQ-IDW-01)
- [ ] 2.2 Stamp the declared defaults at creation, before classification, recording which rule stamped them (REQ-IDW-02)
- [ ] 2.3 Declare the NAW extraction as a fifth `x-openregister-processing` activity with purpose, legal basis, data categories and retention (REQ-IDW-03)

## 3. Suggestion and decision

- [ ] 3.1 `PartySuggestionService`: read the entity occurrences for the file, propose a party with confidence and source span, never write one (REQ-IDW-03)
- [ ] 3.2 Record accept, edit and reject as corrections, following the `GlAccountSuggestionService::recordBooking()` corpus shape (REQ-IDW-03)
- [ ] 3.3 Routing and acceptance declared per record type by the consumer, stored against the type reference and applied at arrival (REQ-IDW-05)

## 4. Worklist and search

- [ ] 4.1 Detaching a document returns it to its intake record as `detached` with the reason and the actor; the inbox lists them (REQ-IDW-04)
- [ ] 4.2 Write the recognised text to the document's searchable content through the existing OCR path (REQ-IDW-06)
- [ ] 4.3 Classification and extraction run as background jobs with progress on the intake record, and the inbox shows what is still being read (REQ-IDW-06)

## 5. Quality

- [ ] 5.1 PHPUnit inside the container for arrival, stamping, suggestion, detachment and routing; 75% on new code (ADR-009)
- [ ] 5.2 Playwright `tests/e2e/inbound-documents.spec.ts`; Dutch and English strings; docs in `docs/features/inbound-documents.md` with screenshots; tell dossiq the leaf ids and the promote-to-case contract

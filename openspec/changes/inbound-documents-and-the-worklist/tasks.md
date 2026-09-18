# Tasks: inbound-documents-and-the-worklist

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12. -->

## 1. Schemas

- [x] 1.1 Extend `intakeDocument` in the `filinq` register with `arrivedWith`, `stampedDefaults`, `defaultRule`, `partySuggestion[]`, `routing`, `acceptance` and the `detached` state (REQ-IDW-01, REQ-IDW-04)
  - `hardValidation: true`; authorization cascade declared, never omitted; descriptor version bumped so `SettingsInitializer` imports it.
- [x] 1.2 Add `intakeDefaultRule` to the `filinq` register: channel or sender pattern, the metadata it stamps, and its order (REQ-IDW-02)

## 2. Arrival

- [x] 2.1 One intake record per attachment, each linked to its message through `arrivedWith`; assigning a message offers its attachments (REQ-IDW-01)
- [x] 2.2 Stamp the declared defaults at creation, before classification, recording which rule stamped them (REQ-IDW-02)
- [x] 2.3 Declare the NAW extraction as a fifth `x-openregister-processing` activity with purpose, legal basis, data categories and retention (REQ-IDW-03)

## 3. Suggestion and decision

- [x] 3.1 `PartySuggestionService`: read the entity occurrences for the file, propose a party with confidence and source span, never write one (REQ-IDW-03)
- [x] 3.2 Record accept, edit and reject as corrections, following the `GlAccountSuggestionService::recordBooking()` corpus shape (REQ-IDW-03)
- [x] 3.3 Routing and acceptance declared per record type by the consumer, stored against the type reference and applied at arrival (REQ-IDW-05)

## 4. Worklist and search

- [x] 4.1 Detaching a document returns it to its intake record as `detached` with the reason and the actor; the inbox lists them (REQ-IDW-04)
- [ ] 4.2 Write the recognised text to the document's searchable content through the existing OCR path (REQ-IDW-06)
  - The OCR path is the scan feeder's, so it lands with `scan-intake-with-separator-sheets`.
- [ ] 4.3 Classification and extraction run as background jobs with progress on the intake record, and the inbox shows what is still being read (REQ-IDW-06)
  - `classificationProgress` is on the record and the inbox reads it; the background job that moves it is still to come.

## 5. Quality

- [x] 5.1 PHPUnit inside the container for arrival, stamping, suggestion, detachment and routing; 75% on new code (ADR-009)
- [x] 5.2 Playwright `tests/e2e/workflows/inbound-documents.spec.ts` written and tagged; Dutch and English strings, the feature docs and the note to dossiq are still open

# Tasks: template-engine-renders-through-a-vendor

## 1. Register

- [ ] 1.1 `lib/Settings/filinq_register.json`: optional `template.vendorTemplateId`; `generatedDocument` gains state `pending` and optional `vendorJobId`; demo data for both.

## 2. Backend

- [ ] 2.1 `DocumentService::generateDocument()` reads `options.engine`: absent or `filinq` renders as today; `vendor:<slug>` hands off to the vendor render; anything else is refused with 400 (REQ-TEV-001).
- [ ] 2.2 Vendor render: resolve the source by slug through integriq, dispatch `DocumentRenderRequestedEvent`, record a `pending` `generatedDocument` with the job id; refuse, naming the source, when integriq is absent, the slug is unknown, the template has no `vendorTemplateId`, or the event is refused or unanswered; never fall back (REQ-TEV-002).
- [ ] 2.3 Listener on `DocumentRenderedEvent` for `requestedByApp: filinq`: store the vendor file like a filinq render and mark the entry `generated`, or `failed` with the error (REQ-TEV-003).
- [ ] 2.4 `GET /api/templates/engines` (NoAdminRequired): `filinq` plus `vendor:<slug>` per active integriq document generation source (REQ-TEV-004).
- [ ] 2.5 Unit tests for 2.1 to 2.4 with integriq stubbed at the event boundary.

## 3. Verification

- [ ] 3.1 Newman: a vendor render against integriq's mock-mode SmartDocuments source reaches `generated`.
- [ ] 3.2 Live: a dossiq decision on `vendor:smartdocuments` lands as a file in the case (with dossiq task 2.6).

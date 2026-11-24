# Tasks: template-engine-renders-through-a-vendor

## 1. Register

- [x] 1.1 `lib/Settings/filinq_register.json`: optional `template.vendorTemplateId`; `generatedDocument` gains state `pending` and optional `vendorJobId`; demo data for both.
  - Built: register 8.98.0; `template` 1.10.0 (optional `vendorTemplateId`), `generatedDocument` 1.11.0 (status `pending`, optional `vendorJobId` and `engine`); demo template `besluit-via-documentleverancier` and a demo pending entry. Pinned by `VendorRenderRegisterTest` and `DocumentProductionSchemaTest`.

## 2. Backend

- [x] 2.1 `DocumentService::generateDocument()` reads `options.engine`: absent or `filinq` renders as today; `vendor:<slug>` hands off to the vendor render; anything else is refused with 400 (REQ-TEV-001).
  - Built: `RenderEngine` reads the option; `DocumentService::generateDocument()` refuses an unknown engine before anything renders and hands `vendor:<slug>` to `VendorRenderService` (through `DocumentProduction::$vendor`); an instance without the vendor render refuses every engine but `filinq`. `POST /api/documents/generate` answers a pending render with 202. Pinned by `DocumentServiceVendorEngineTest` and `DocumentControllerTest::testAVendorRenderAnswers202Pending`.
- [x] 2.2 Vendor render: resolve the source by slug through integriq, dispatch `DocumentRenderRequestedEvent`, record a `pending` `generatedDocument` with the job id; refuse, naming the source, when integriq is absent, the slug is unknown, the template has no `vendorTemplateId`, or the event is refused or unanswered; never fall back (REQ-TEV-002).
  - Built: `VendorRenderSources` reads integriq's `source` objects of type `documentGeneration` (enabled, not `disabled`) by slug; `VendorRenderService` dispatches `OCA\Integriq\Event\DocumentRenderRequestedEvent` by name (shape read on integriq `development`: sourceId, templateId, data, requestedBy, requestedByApp; `getJobId()`, `getRefusal()`) and records the `pending` entry with `vendorJobId`. Refusals: integriq absent 503, unknown slug or no `vendorTemplateId` 400, refused, unanswered or throwing 502, each naming the source. Pinned by `VendorRenderServiceTest`.
- [ ] 2.3 Listener on `DocumentRenderedEvent` for `requestedByApp: filinq`: store the vendor file like a filinq render and mark the entry `generated`, or `failed` with the error (REQ-TEV-003).
  - Not built: blocked on integriq. Its `DocumentRenderedEvent` (integriq `development`) carries the job id, status, requestedBy, the vendor's `fileReference` and the error, but no file: integriq fetches the bytes inside `DocumentGenerationService::apply()`, records only their length (`documentBytes`) and drops them, so filinq has nothing to store. Needs integriq to hand over the document (the bytes, or a Nextcloud file it stored) on the event or on a readable job. Also note: a vendor that settles inside the request makes integriq dispatch `DocumentRenderedEvent` before `DocumentRenderRequestedEvent` returns, so the listener must accept a job id it has not recorded yet.
  - Built (failure half): `VendorRenderedListener`, registered by name on integriq's `DocumentRenderedEvent` in `DocumentGenerationRegistrar`, hands the outcome to `VendorRenderOutcome::settle()`, which finds the `pending` generatedDocument by `vendorJobId` and writes `failed` with integriq's error (or a sentence when it gives none). A job filinq has no pending entry for is left alone, and a register failure is logged, never thrown at integriq. A `rendered` outcome leaves the entry `pending` and logs that there is no document to store; that half waits for integriq to hand over the file. Pinned by `VendorRenderOutcomeTest` and `DocumentGenerationRegistrarTest`.
- [x] 2.4 `GET /api/templates/engines` (NoAdminRequired): `filinq` plus `vendor:<slug>` per active integriq document generation source (REQ-TEV-004).
  - Built: `TemplateEnginesController::index()` on `api/templates/engines` (before `api/templates/{id}`), in `openapi.json` and the docs copy; `results` [{id, name, type}]. The RBAC bypass on the source read is recorded in `docs/authorization-decisions.md`. Pinned by `TemplateEnginesControllerTest`.
- [ ] 2.5 Unit tests for 2.1 to 2.4 with integriq stubbed at the event boundary.
  - Built for 2.1, 2.2 and 2.4 (`RenderRequestedEventDouble` stands in for integriq's command); the 2.3 tests wait for 2.3.

## 3. Verification

- [ ] 3.1 Newman: a vendor render against integriq's mock-mode SmartDocuments source reaches `generated`.
- [ ] 3.2 Live: a dossiq decision on `vendor:smartdocuments` lands as a file in the case (with dossiq task 2.6).

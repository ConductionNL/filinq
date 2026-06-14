# Tasks: letter-correspondence-generation

## Task 1: Backend — Correspondence API

- [x] **1.1** `lib/Controller/CorrespondenceController.php` — REST controller
  with `generate()`, `generateBatch()`, and `jobStatus()` methods.
  Auth: `#[NoAdminRequired]` on generate + generateBatch; `#[NoCSRFRequired]`
  on jobStatus. Per-object: auth check via `IUserSession::getUser()`.
- [x] **1.2** `appinfo/routes.php` — register all three correspondence routes
  (`POST api/correspondence/generate`, `POST api/correspondence/generate/batch`,
  `GET api/correspondence/jobs/{jobId}`).

## Task 2: Backend — Correspondence Service

- [x] **2.1** `lib/Service/CorrespondenceService.php` — orchestrates template
  fetch → data resolve → huisstijl apply → Twig render → output → register log.
  Supports pdf / docx / html / email formats.
- [x] **2.2** `lib/Service/DataResolverService.php` — resolves merge fields
  from OpenRegister objects by register/schema/UUID; cache per request; 3-level
  nested resolution.
- [x] **2.3** `lib/BackgroundJob/BatchCorrespondenceJob.php` — `QueuedJob` for
  batches > 10 recipients; updates job status after every recipient.

## Task 3: Register schemas

- [x] **3.1** `lib/Settings/docudesk_register.json` — `correspondence` schema
  (templateId, templateName, recipientId, recipientType, caseReference,
  generatedAt, format, status, generatedBy, errorMessage).
- [x] **3.2** `lib/Settings/docudesk_register.json` — `huisstijl` schema
  (organisationName, logoUrl, primaryColor, headerHtml, footerHtml,
  defaultMargins).

## Task 4: Tests

- [x] **4.1** `tests/unit/Service/CorrespondenceServiceTest.php` — covers
  generate (PDF + HTML), invalid format, batch sync, batch async, register log.
- [x] **4.2** `tests/unit/Controller/CorrespondenceControllerTest.php` — covers
  generate (success, missing params, unauthenticated), generateBatch, jobStatus
  (success, not-found, forbidden for other user).
- [x] **4.3** `tests/unit/BackgroundJob/BatchCorrespondenceJobTest.php` — covers
  successful run, missing args guard, partial error, ownerUserId propagation.

## Task 5: Frontend — Brieven & correspondentie view

- [x] **5.1** `src/views/correspondence/CorrespondenceIndex.vue` — form to
  select template, enter data refs, choose format, trigger generation and
  download. Shows batch mode when multiple recipient IDs supplied.
- [x] **5.2** `src/store/modules/correspondence.js` — Pinia store for
  template options, dataRefs, format, batch state, job polling.
- [x] **5.3** `src/router/index.js` — add `/correspondence` route pointing to
  `CorrespondenceIndex`.
- [x] **5.4** `src/navigation/MainMenu.vue` — add "Brieven & correspondentie"
  nav entry under Sjablonen group.

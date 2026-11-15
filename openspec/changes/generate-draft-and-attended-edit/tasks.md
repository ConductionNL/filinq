# Tasks: generate-draft-and-attended-edit

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12. -->

## 1. Register

- [ ] 1.1 Add `documentDraft` to `lib/Settings/filinq_register.json` with `authorization.scope: private`, an `x-openregister-lifecycle` on `stage`, an `x-openregister-processing` activity and no archival annotation; add `draftId` and `editedBy` to `generatedDocument` and `correspondence`; seed one editing draft; bump the register version (D1, D2, D6) (REQ-GDA-004). Verify: `occ maintenance:repair` imports it, the seed draft shows on `/drafts` for the demo user and not for a second user.

## 2. Service and routes

- [ ] 2.1 Add `lib/Service/DocumentDraftService.php` and `POST api/documents/drafts`: render through `generatePreview()`, save the draft, refuse a template with `plainLanguage` with 409 (D3, D8) (REQ-GDA-001, REQ-GDA-005). Verify: PHPUnit asserts no `generatedDocument` or `correspondence` write and the 409 answer.
- [ ] 2.2 Add public `DocumentService::generateFromHtml()` sharing `produceOutput()`, `storeOutputIfRequested()` and the logger with `generateDocument()`, and `CorrespondenceService::logFinishedDraft()` (D3) (REQ-GDA-002). Verify: PHPUnit asserts one filing and one log per finish, with `draftId` and `editedBy` set.
- [ ] 2.3 Add `POST api/documents/drafts/{id}/finish` with `#[NoAdminRequired]`, owner check through OpenRegister, server-side sanitising, no Twig call, and the move to `finished` (D4, D6) (REQ-GDA-002). Verify: PHPUnit with a draft containing `{{ naam }}` and a `<script>` tag; Newman answers 404 for a non-owner.
- [ ] 2.4 Pass wizard answers from a wizard draft as `options.wizardContext` on finish (REQ-GDA-003). Verify: PHPUnit asserts the wizard validation runs and a missing required answer answers 422.
- [ ] 2.5 Add `lib/BackgroundJob/DocumentDraftExpiryJob.php`, daily, deleting drafts past `expiresAt`, and set `expiresAt` on every save (D2) (REQ-GDA-004). Verify: PHPUnit with a fixed clock deletes the 31-day draft and keeps the 29-day one.

## 3. Screens

- [ ] 3.1 Move the editing area of `TemplateDetail.vue:162-187` into `src/components/HtmlDocumentEditor.vue` and use it from both the template page and the draft editor (D7). Verify: Playwright on `/templates/:id` still edits and saves a template.
- [ ] 3.2 Add `src/views/drafts/DraftIndex.vue` (`/drafts`) and `DraftEditor.vue` (`/drafts/:id`) with save, finish and discard, and their pages in `src/manifest.json` (REQ-GDA-001, REQ-GDA-003). Verify: Playwright `tests/e2e/generation-drafts.spec.ts` covers open, edit, finish and resume.
- [ ] 3.3 Add "Open for editing" beside "Generate letter" on `CorrespondenceIndex.vue`, hidden for a template with `plainLanguage` (REQ-GDA-001, REQ-GDA-005). Verify: Playwright on `/correspondence`.
- [ ] 3.4 Add "Open for editing" to the wizard's review step and "Save and finish later" to every wizard step, resuming at the saved step (REQ-GDA-003). Verify: Playwright resumes a wizard at question four.

## 4. Strings, tests and docs

- [ ] 4.1 Dutch and English strings (ADR-005) and `@spec` tags on every new method. Verify: `npm run lint`, `npm run check:l10n` and `composer check:strict` pass.
- [ ] 4.2 PHPUnit on the new service, controller and job at 75% line coverage or more (ADR-009), and a section in `docs/features/` with a screenshot of the draft editor (ADR-010). Verify: the coverage report for the new files and the docs page render.

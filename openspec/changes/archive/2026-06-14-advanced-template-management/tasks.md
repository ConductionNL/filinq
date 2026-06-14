# Tasks — Advanced Template Management

## 1. Backend: Template versioning

- [x] **1.1** Add `getVersionRegisterAndSchema()` to `OpenRegisterResolver`
- [x] **1.2** Implement `TemplateVersionService` with `createVersion`, `getVersions`, `getVersion`, `getNextVersionNumber`, `restoreVersion`, `getDiff`
- [x] **1.3** Call `createVersion()` inside `TemplateService::updateTemplate()` before each save
- [x] **1.4** Add `versions` and `restoreVersion` endpoints to `TemplatesController`

## 2. Backend: Version diff endpoint

- [x] **2.1** Add `diffVersions` controller method (`GET /api/templates/{id}/versions/diff?from=…&to=…`)
- [x] **2.2** Add `getDiff` method to `TemplateVersionService`

## 3. Backend: Template duplication

- [x] **3.1** Implement `TemplateService::duplicateTemplate()` — copy fields, suffix " (kopie)", new UUID
- [x] **3.2** Add `duplicate` endpoint to `TemplatesController`

## 4. Backend + Frontend: Template locking

- [x] **4.1** Implement `TemplateService::acquireLock()` with 15-minute timeout
- [x] **4.2** Implement `TemplateService::releaseLock()` with ownership check
- [x] **4.3** Add `lock` and `unlock` endpoints to `TemplatesController`
- [x] **4.4** Add `acquireLock` and `releaseLock` actions to `src/store/modules/template.js`
- [x] **4.5** Write `TemplateVersionServiceTest.php` covering `createVersion`, `getVersions`, `restoreVersion`, `getDiff`

## 5. Backend + Frontend: Template preview

- [x] **5.1** Implement `TemplatePreviewService::preview()` and `previewTemplate()`
- [x] **5.2** Add `preview` and `previewTemplate` endpoints to `TemplatesController`
- [x] **5.3** Add `previewTemplate` action to `src/store/modules/template.js`
- [x] **5.4** Write `TemplatePreviewServiceTest.php` covering preview and error cases

## 6. Frontend: Template index with categories and search

- [x] **6.1** Create `src/views/templates/TemplateIndex.vue` with table, category filter dropdown, search input
- [x] **6.2** Add `fetchTemplates`, `fetchTemplate`, `deleteTemplate`, `duplicateTemplate` actions to store
- [x] **6.3** Wire category filter and search to `templateStore.fetchTemplates(filters)`
- [x] **6.4** Register `TemplateIndex` and `TemplateDetail` in `src/views/Views.vue`

## 7. Frontend: WYSIWYG template editor

- [x] **7.1** Create `src/views/templates/TemplateDetail.vue` with Editor / Preview / Versions tabs
- [x] **7.2** Implement contenteditable WYSIWYG toolbar (Bold, Italic, Underline, H1, H2, lists)
- [x] **7.3** Add merge-field insertion (`{{ field }}`) via `src/dialogs/MergeFieldDialog.vue`
- [x] **7.4** Add conditional-section insertion via `src/dialogs/ConditionalSectionDialog.vue`
- [x] **7.5** Wire lock acquisition on mount and release on unmount/back
- [x] **7.6** Add `createTemplate` and `updateTemplate` actions to store
- [x] **7.7** Implement version history table with Restore button

## 8. Seed data for template schema

- [x] **8.1** Add 3+ realistic template seed objects to `lib/Settings/docudesk_register.json`

## 9. Spec artifacts

- [x] **9.1** Create `openspec/changes/advanced-template-management/design.md`
- [x] **9.2** Create `openspec/changes/advanced-template-management/tasks.md`

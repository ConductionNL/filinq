# Tasks: advanced-template-management

## Task 1: Schema — extend template schema with category/tags/locking fields
- [x] Add `category`, `tags`, `lockedBy`, `lockedAt` properties to the `template` schema in `lib/Settings/docudesk_register.json`

## Task 2: Schema — add templateVersion schema to register
- [x] Add `templateVersion` schema and register entry to `lib/Settings/docudesk_register.json`
- [x] Add `templateVersions` register entry referencing `templateVersion` schema

## Task 3: Update OpenRegister stubs for tests
- [x] Add `buildSearchQuery`, `searchObjectsPaginated`, `deleteObject` methods to `tests/stubs/OpenRegisterStubs.php`
- [x] Add `ObjectEntity` stub class to `tests/stubs/OpenRegisterStubs.php`

## Task 4: Unit tests — TemplateVersionService
- [x] Create `tests/unit/Service/TemplateVersionServiceTest.php` with tests for `createVersion`, `getVersions`, `getVersion`, `getNextVersionNumber`, `restoreVersion`, `getDiff`

## Task 5: Unit tests — TemplatePreviewService
- [x] Create `tests/unit/Service/TemplatePreviewServiceTest.php` with tests for `preview` and `previewTemplate`

## Task 6: Frontend — TemplateIndex.vue category/tag filtering
- [x] Add category filter dropdown and search to `src/views/templates/TemplateIndex.vue`
- [x] Add row click handler to navigate to template detail

## Task 7: Frontend — TemplateDetail.vue full WYSIWYG editor
- [x] Implement full editor in `src/views/templates/TemplateDetail.vue` with:
  - WYSIWYG contenteditable toolbar (bold, italic, underline, heading, lists)
  - Edit/Preview tab toggle
  - Version history tab with restore button
  - Conditional section insertion dialog
  - Lock status indicator
  - Save/Cancel buttons with lock acquisition on open and release on save/cancel

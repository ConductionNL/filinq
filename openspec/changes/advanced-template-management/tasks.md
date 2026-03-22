## 1. Schema and Data Model Extensions

- [x] 1.1 Add `templateVersion` schema to `docudesk_register.json` with properties: templateId (string, required, facetable), version (integer, required), content (string, required), name (string, required), description (string), format (string), orientation (string), editor (string, required), changelog (string), created (datetime, required)
- [x] 1.2 Add `templateVersion` to the templates register schemas list in `docudesk_register.json`
- [x] 1.3 Extend `template` schema in `docudesk_register.json` with: category (string, facetable, maxLength 64), tags (array), lockedBy (string, nullable), lockedAt (string, format datetime, nullable)
- [ ] 1.4 Verify schema import works by starting DocuDesk and checking OpenRegister admin for new schema/fields

## 2. Template Versioning Backend

- [x] 2.1 Create `lib/Service/TemplateVersionService.php` with constructor accepting ContainerInterface, IAppManager, OpenRegisterResolver
- [x] 2.2 Implement `createVersion(string $templateId, array $templateState, string $editor, ?string $changelog): array` -- saves previous state as a templateVersion object
- [x] 2.3 Implement `getVersions(string $templateId, int $limit, int $offset): array` -- list versions for a template, ordered by version descending
- [x] 2.4 Implement `getVersion(string $versionId): array` -- get single version by UUID
- [x] 2.5 Implement `getNextVersionNumber(string $templateId): int` -- count existing versions + 1
- [x] 2.6 Modify `TemplateService::updateTemplate()` to call `TemplateVersionService::createVersion()` before saving the update, passing current user ID as editor
- [x] 2.7 Implement `restoreVersion(string $templateId, string $versionId, string $editor): array` in TemplateVersionService -- saves current state as new version, then restores the target version's content/name/description/format/orientation to the template
- [x] 2.8 Implement `getDiff(string $versionIdFrom, string $versionIdTo): array` -- returns both version contents for client-side diff

## 3. Template Categories, Tags, Duplication, and Locking Backend

- [x] 3.1 Update `TemplateService::getTemplates()` to support `category` and `tags` filter parameters
- [x] 3.2 Implement `TemplateService::duplicateTemplate(string $id): array` -- copies template with name + " (kopie)", new UUID, no version history, same namespace/category/tags
- [x] 3.3 Implement `TemplateService::acquireLock(string $id, string $userId): array` -- sets lockedBy/lockedAt if not locked or lock expired (15 min), returns 409 if locked by another user
- [x] 3.4 Implement `TemplateService::releaseLock(string $id, string $userId): array` -- clears lockedBy/lockedAt if locked by this user
- [x] 3.5 Modify `TemplateService::updateTemplate()` to release the lock after successful save

## 4. Template Preview Backend

- [x] 4.1 Create `lib/Service/TemplatePreviewService.php` with constructor accepting TemplateRenderer
- [x] 4.2 Implement `preview(string $content, array $data): string` -- renders template content with sample data via TemplateRenderer, returns HTML string
- [x] 4.3 Implement `previewTemplate(string $templateId, array $data): string` -- fetches template by ID, then calls preview() with its content

## 5. Conditional Section Rendering

- [x] 5.1 Extend `TemplateRenderer` with `convertConditionalSections(string $html): string` method that finds elements with `data-condition-*` attributes and wraps their content in Twig `{% if %}` blocks
- [x] 5.2 Support operators: equals (`==`), not_equals (`!=`), contains (`in`), is_empty (`is empty`), is_not_empty (`is not empty`)
- [x] 5.3 Call `convertConditionalSections()` before Twig rendering in the existing render pipeline
- [x] 5.4 Write unit tests for conditional section conversion with all operator types

## 6. Controller and Routes

- [x] 6.1 Add version endpoints to `TemplatesController`: `versions(string $id)`, `restoreVersion(string $id, string $versionId)`, `diffVersions(string $id)`
- [x] 6.2 Add preview endpoints to `TemplatesController`: `preview()` (raw content), `previewTemplate(string $id)`
- [x] 6.3 Add duplicate endpoint to `TemplatesController`: `duplicate(string $id)`
- [x] 6.4 Add lock endpoints to `TemplatesController`: `lock(string $id)`, `unlock(string $id)`
- [x] 6.5 Add all new routes to `appinfo/routes.php`:
  - `GET /api/templates/{id}/versions`
  - `POST /api/templates/{id}/versions/{versionId}/restore`
  - `GET /api/templates/{id}/versions/diff`
  - `POST /api/templates/preview`
  - `POST /api/templates/{id}/preview`
  - `POST /api/templates/{id}/duplicate`
  - `POST /api/templates/{id}/lock`
  - `DELETE /api/templates/{id}/lock`

## 7. Frontend -- Template Store and Navigation

- [x] 7.1 Create `src/store/modules/template.js` Pinia store with: fetchTemplates, fetchTemplate, createTemplate, updateTemplate, deleteTemplate, duplicateTemplate, fetchVersions, restoreVersion, previewTemplate, acquireLock, releaseLock
- [x] 7.2 Add "Sjablonen" navigation entry in `src/store/modules/navigation.ts` with icon FileDocumentMultiple
- [x] 7.3 Add routes `/templates` and `/templates/:id` to `src/router/index.js`

## 8. Frontend -- Template List View

- [x] 8.1 Create `src/views/templates/TemplateIndex.vue` with template list table showing name, category, namespace, tags, updated date
- [x] 8.2 Add category filter dropdown and tag filter chips
- [x] 8.3 Add search input field
- [x] 8.4 Add action buttons: create new, duplicate, delete (with confirmation dialog)
- [x] 8.5 Show lock indicator (who is editing) on locked templates

## 9. Frontend -- WYSIWYG Template Editor

- [x] 9.1 Install TipTap dependencies: `@tiptap/vue-2`, `@tiptap/starter-kit`, `@tiptap/extension-table`, `@tiptap/extension-image`, `@tiptap/extension-underline`
- [x] 9.2 Create `src/views/templates/TemplateEditor.vue` with TipTap editor instance, toolbar (bold, italic, underline, headings, lists, table, image, merge field, conditional section), and source code toggle
- [x] 9.3 Create `src/views/templates/MergeFieldMenu.vue` -- dropdown that lets user type or select a field name and inserts `{{ fieldName }}` as an inline node
- [x] 9.4 Implement TipTap custom node extension for merge fields that renders as styled pills in the editor but outputs `{{ name }}` in HTML
- [x] 9.5 Create `src/views/templates/ConditionalSectionDialog.vue` -- dialog with field name input, operator select (equals, not equals, contains, is empty, is not empty), value input; wraps selected content in a div with data-condition-* attributes
- [x] 9.6 Implement source code view toggle that shows raw HTML in a textarea/code editor and syncs changes back to the WYSIWYG view

## 10. Frontend -- Template Detail, Preview, and Version History

- [x] 10.1 Create `src/views/templates/TemplateDetail.vue` as the main edit page -- contains TemplateEditor, metadata fields (name, description, category, tags, format, orientation), save/cancel buttons
- [x] 10.2 Create `src/views/templates/TemplatePreview.vue` -- side panel with JSON textarea for sample data input and rendered HTML output iframe/div
- [x] 10.3 Create `src/views/templates/VersionHistory.vue` -- sidebar panel listing versions with restore button and diff viewer
- [x] 10.4 Integrate lock acquisition on page load and release on navigate away or save
- [x] 10.5 Show lock conflict warning when another user holds the lock

## 11. Backend Quality and Tests

- [x] 11.1 Write PHPUnit tests for `TemplateVersionService` (create version, list versions, restore, diff)
- [x] 11.2 Write PHPUnit tests for template locking (acquire, conflict, expiration, release)
- [x] 11.3 Write PHPUnit tests for template duplication
- [x] 11.4 Write PHPUnit tests for `TemplatePreviewService`
- [ ] 11.5 Run `composer check:strict` (PHPCS, PHPMD, Psalm, PHPStan) and fix all issues
- [ ] 11.6 Run `npm run build` and verify frontend compiles without errors

## 12. Verification

- [ ] 12.1 Test template CRUD with new fields (category, tags) via API
- [ ] 12.2 Test version creation on update, version listing, and rollback via API
- [ ] 12.3 Test template preview with sample data via API
- [ ] 12.4 Test template duplication via API
- [ ] 12.5 Test lock acquire/conflict/expiration via API
- [ ] 12.6 Test conditional section rendering with all operator types via API
- [ ] 12.7 Test WYSIWYG editor in browser: formatting, merge fields, conditional sections, source toggle
- [ ] 12.8 Test template management UI end-to-end: create, edit, preview, duplicate, version history, restore

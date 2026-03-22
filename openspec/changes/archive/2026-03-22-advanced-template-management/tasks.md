## 1. Schema and Data Model Extensions

- [x] 1.1 Add `templateVersion` schema to `docudesk_register.json`
- [x] 1.2 Add `templateVersion` to the templates register schemas list
- [x] 1.3 Extend `template` schema with: category, tags, lockedBy, lockedAt

## 2. Template Versioning Backend

- [x] 2.1 Create `lib/Service/TemplateVersionService.php`
- [x] 2.2 Implement `createVersion()`
- [x] 2.3 Implement `getVersions()`
- [x] 2.4 Implement `getVersion()`
- [x] 2.5 Implement `getNextVersionNumber()`
- [x] 2.6 Modify `TemplateService::updateTemplate()` to create versions
- [x] 2.7 Implement `restoreVersion()`
- [x] 2.8 Implement `getDiff()`

## 3. Template Categories, Tags, Duplication, and Locking Backend

- [x] 3.1 Update `TemplateService::getTemplates()` for category/tags filters
- [x] 3.2 Implement `TemplateService::duplicateTemplate()`
- [x] 3.3 Implement `TemplateService::acquireLock()`
- [x] 3.4 Implement `TemplateService::releaseLock()`
- [x] 3.5 Modify `TemplateService::updateTemplate()` to release lock

## 4. Template Preview Backend

- [x] 4.1 Create `lib/Service/TemplatePreviewService.php`
- [x] 4.2 Implement `preview()`
- [x] 4.3 Implement `previewTemplate()`

## 5. Conditional Section Rendering

- [x] 5.1 Extend `TemplateRenderer` with `convertConditionalSections()`
- [x] 5.2 Support operators: equals, not_equals, contains, is_empty, is_not_empty
- [x] 5.3 Call `convertConditionalSections()` before Twig rendering
- [x] 5.4 Write unit tests for conditional section conversion

## 6. Controller and Routes

- [x] 6.1 Add version endpoints to `TemplatesController`
- [x] 6.2 Add preview endpoints to `TemplatesController`
- [x] 6.3 Add duplicate endpoint to `TemplatesController`
- [x] 6.4 Add lock endpoints to `TemplatesController`
- [x] 6.5 Add all new routes to `appinfo/routes.php`

## 7. Frontend -- Template Store and Navigation

- [x] 7.1 Create `src/store/modules/template.js` Pinia store
- [x] 7.2 Add "Templates" navigation entry
- [x] 7.3 Add template views to Views.vue and MainMenu.vue

## 8. Frontend -- Template Views

- [x] 8.1 Create `src/views/templates/TemplateIndex.vue`
- [x] 8.2 Create `src/views/templates/TemplateDetail.vue`

## 9. Backend Tests

- [x] 9.1 Write PHPUnit tests for TemplateRenderer conditional sections

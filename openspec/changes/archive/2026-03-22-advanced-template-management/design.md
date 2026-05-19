## Context

DocuDesk already provides basic template CRUD (`TemplateService`, `TemplatesController`) with OpenRegister-backed storage, namespace scoping, and format/orientation settings. Templates are currently raw HTML/Twig strings managed exclusively via the REST API -- there is no UI for template management, no versioning (edits overwrite permanently), no categorization beyond namespace, and no visual editor.

Market intelligence shows 96 tenders and 240 requirements requesting template versioning, categorization, WYSIWYG editing, and conditional sections. This change adds those capabilities on top of the existing foundation.

### Current Architecture
- `TemplateService` (CRUD) delegates to `OpenRegisterResolver` for register/schema resolution and `ObjectService` for persistence
- `TemplatesController` handles REST endpoints, delegates request parsing to `TemplateRequestHandler`
- `TemplateRenderer` and `PdfService` handle rendering (Twig sandbox + mPDF)
- Template schema in `docudesk_register.json` with fields: name, description, content, namespace, format, orientation
- No Vue views for templates -- all management is API-only

## Goals / Non-Goals

**Goals:**
- Add template versioning with history, rollback, and diff comparison
- Add category and tag fields for template organization
- Build a WYSIWYG template editor (TipTap-based) with merge field insertion
- Add conditional section support (UI for non-technical users, data-attribute storage, runtime Twig conversion)
- Add template preview (render with sample data before saving)
- Add template duplication
- Add optimistic locking for concurrent edit protection
- Build a template management UI in DocuDesk's Vue frontend

**Non-Goals:**
- Template sharing across instances (federation)
- Template marketplace
- AI-assisted template generation
- Real-time collaborative editing (WebSocket-based)
- Template approval workflows

## Decisions

### 1. Version storage: Separate `templateVersion` objects in OpenRegister

**Decision**: Store version history as separate OpenRegister objects in a `templateVersion` schema, not as a version array within the template object.

**Rationale**: Templates can have large HTML content (10KB+). Storing 50+ versions inline would create megabyte-sized objects, degrading performance on every list/read. Separate objects allow efficient pagination of version history and avoid bloating the template object.

**Structure**: Each `templateVersion` links to its parent template via `templateId`. On update, the *previous* state is captured as a version before the template object is modified. This keeps the template object always reflecting the current state.

### 2. WYSIWYG editor: TipTap with custom merge field extension

**Decision**: Use TipTap 1.x (Vue 2 compatible) as the WYSIWYG editor with a custom ProseMirror node for merge fields.

**Rationale**: TipTap is ProseMirror-based, battle-tested in Nextcloud Text, outputs clean HTML, and supports custom extensions. The merge field extension renders `{{ variable }}` as inline node pills in the editor while outputting valid Twig syntax in the HTML. Vue 2 compatibility is required since DocuDesk uses Vue 2.7.

**Alternative considered**: CKEditor 4/5 -- rejected due to licensing complexity and larger bundle size. Plain textarea with syntax highlighting -- rejected because it doesn't meet the "non-technical user" requirement.

### 3. Conditional sections: Data attributes converted to Twig at render time

**Decision**: Store conditional sections as HTML data attributes (`data-condition-field`, `data-condition-op`, `data-condition-value`) in the template content. The `TemplateRenderer` converts these to Twig `{% if %}` blocks before rendering.

**Rationale**: This keeps the stored template as valid HTML (editable by WYSIWYG), avoids exposing Twig syntax to non-technical users, and allows the editor to render condition indicators visually. The conversion happens at render time only, keeping the source-of-truth clean.

**Supported operators**: `equals`, `not_equals`, `contains`, `is_empty`, `is_not_empty`.

### 4. Locking: Optimistic locking with TTL expiration

**Decision**: Use field-level locking on the template object itself (`lockedBy`, `lockedAt` fields) with 15-minute TTL. No WebSocket -- frontend polls lock status or checks on edit attempt.

**Rationale**: WebSocket infrastructure is complex and not justified for template editing frequency. Optimistic locking with TTL handles the common case (one person edits at a time) and degrades gracefully (stale locks auto-expire). The lock fields live on the template object, avoiding a separate lock table.

### 5. Template UI: New "templates" view section in DocuDesk frontend

**Decision**: Add `src/views/templates/` with `TemplateIndex.vue` (list), `TemplateDetail.vue` (view/edit with WYSIWYG), and register a "Sjablonen" navigation entry in the sidebar.

**Rationale**: The existing DocuDesk frontend has views for anonymization, consent, dashboard, and settings. Templates are a natural addition. The template editor is the centerpiece -- it combines the WYSIWYG editor, preview panel, version history, and conditional section tools.

### 6. Preview: Reuse TemplateRenderer with sample data

**Decision**: The preview endpoint passes template content + sample data through the existing `TemplateRenderer` (which already handles Twig rendering in a sandbox). Returns rendered HTML, not PDF -- PDF preview is optional via existing `PdfService.renderPdf()`.

**Rationale**: Reuses existing infrastructure. HTML preview is instant and sufficient for content verification. PDF preview is a secondary action if the user wants to check page layout.

## Component Map

### Backend (PHP)

| Component | File | Purpose |
|-----------|------|---------|
| TemplateService (extended) | `lib/Service/TemplateService.php` | Add version creation on update, category/tag filtering, duplication, lock management |
| TemplateVersionService (new) | `lib/Service/TemplateVersionService.php` | CRUD for templateVersion objects, diff retrieval, restore logic |
| TemplatePreviewService (new) | `lib/Service/TemplatePreviewService.php` | Preview rendering with sample data via TemplateRenderer |
| TemplatesController (extended) | `lib/Controller/TemplatesController.php` | Add version, preview, duplicate, lock endpoints |
| TemplateRenderer (extended) | `lib/Service/TemplateRenderer.php` | Add conditional section data-attribute to Twig conversion |
| docudesk_register.json (extended) | `lib/Settings/docudesk_register.json` | Add templateVersion schema, extend template schema with category/tags/lock fields |
| routes.php (extended) | `appinfo/routes.php` | Add new API routes |

### Frontend (Vue 2)

| Component | File | Purpose |
|-----------|------|---------|
| TemplateIndex.vue (new) | `src/views/templates/TemplateIndex.vue` | Template list with category/tag filters, search, duplicate/delete actions |
| TemplateDetail.vue (new) | `src/views/templates/TemplateDetail.vue` | Template view/edit page with WYSIWYG editor, preview panel, version history sidebar |
| TemplateEditor.vue (new) | `src/views/templates/TemplateEditor.vue` | TipTap WYSIWYG editor component with merge field and conditional section extensions |
| MergeFieldMenu.vue (new) | `src/views/templates/MergeFieldMenu.vue` | Dropdown menu for inserting merge fields |
| ConditionalSectionDialog.vue (new) | `src/views/templates/ConditionalSectionDialog.vue` | Dialog for defining conditional section rules |
| TemplatePreview.vue (new) | `src/views/templates/TemplatePreview.vue` | Preview panel with sample data input and rendered output |
| VersionHistory.vue (new) | `src/views/templates/VersionHistory.vue` | Version list sidebar with restore and diff actions |
| template.js (new) | `src/store/modules/template.js` | Pinia store for template CRUD, versions, locks |
| navigation.ts (extended) | `src/store/modules/navigation.ts` | Add "Sjablonen" menu item |
| router/index.js (extended) | `src/router/index.js` | Add /templates and /templates/:id routes |

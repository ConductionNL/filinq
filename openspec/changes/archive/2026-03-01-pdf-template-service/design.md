## Context

PDF generation currently lives in LarpingApp's `CharacterService.createCharacterPdf()`, which directly instantiates mPDF and Twig to render character data into a PDF. Templates are stored as generic LarpingApp objects via `ObjectService` (or OpenRegister). This approach is tightly coupled — no other app can generate PDFs without duplicating the entire mPDF + Twig stack.

DocuDesk is the document processing hub in the Nextcloud ecosystem, already handling anonymization, metadata enrichment, and consent management. It already depends on OpenRegister for data persistence and uses Nextcloud's filesystem for document storage. Adding PDF generation here makes it the canonical place for all document output.

### Current LarpingApp PDF Flow
1. User clicks "Als pdf downloaden" on a character detail page
2. Frontend opens a modal to select a template (fetched from LarpingApp's template store)
3. Frontend opens `GET /characters/{id}/download/{templateId}` in a new tab
4. `CharactersController.downloadPdf()` fetches the character + template data
5. `CharacterService.createCharacterPdf()` creates a Twig environment, renders HTML, feeds it to mPDF
6. Controller gets the PDF string via `Output('', STRING_RETURN)` and returns a `DataDownloadResponse`

## Goals / Non-Goals

**Goals:**
- Provide a reusable `PdfService` in DocuDesk that any Nextcloud app can call to generate PDFs
- Provide template CRUD in DocuDesk with app-scoping so multiple apps can maintain their own templates
- Migrate LarpingApp's PDF generation to use DocuDesk's service
- Keep inter-app communication simple — use Nextcloud's DI container for same-server calls
- Maintain the existing LarpingApp user experience (same download flow, same URL pattern)

**Non-Goals:**
- Building a WYSIWYG template editor (templates remain raw Twig/HTML)
- Supporting PDF generation formats beyond HTML→PDF (no LaTeX, no Markdown→PDF)
- Making PDF generation available as a public/unauthenticated API
- Migrating existing LarpingApp template data automatically (manual re-creation is acceptable given low template count)
- Adding PDF preview functionality in DocuDesk's UI

## Decisions

### 1. Inter-app communication: DI container injection (not HTTP API)

**Decision**: Consumer apps call `PdfService` directly via Nextcloud's DI container (`\OCP\Server::get(OCA\DocuDesk\Service\PdfService::class)`) rather than making HTTP API calls.

**Rationale**: Both apps run in the same Nextcloud instance. DI injection is zero-overhead, type-safe, and doesn't require authentication handling. HTTP calls would add latency, require API auth tokens, and introduce failure modes (connection refused, timeouts).

**Alternative considered**: REST API via `IClientService` — rejected because it adds unnecessary complexity for same-server communication. If remote PDF generation is needed in the future, an API endpoint can be added on top of the service without changing the service layer.

**Fallback**: If DocuDesk is not installed, LarpingApp catches the DI resolution failure and shows "PDF generation requires DocuDesk" to the user. The download button is hidden when DocuDesk is not available (checked via `IAppManager::isEnabledForUser()`).

### 2. Template storage: OpenRegister objects with app-scoping

**Decision**: Templates are stored as OpenRegister objects in a new `template` schema within DocuDesk's register. Each template has a `namespace` field (e.g., `larpingapp`, `opencatalogi`) so apps can scope their templates.

**Rationale**: Consistent with DocuDesk's existing pattern — all data goes through OpenRegister. The namespace field allows multiple apps to use the same template infrastructure without collisions, and enables DocuDesk's own UI to show/manage templates per-app.

**Alternative considered**: Storing templates in Nextcloud's filesystem as `.twig` files — rejected because it loses metadata (name, description), doesn't support CRUD via API, and breaks the OpenRegister pattern.

### 3. PdfService API: Template object + data context

**Decision**: `PdfService.renderPdf(string $templateContent, array $data, array $options = []): string` takes raw Twig template content (not a template ID), a data context array, and optional mPDF configuration. Returns PDF binary as string.

**Rationale**: Keeping the service stateless (no template lookup) makes it maximally reusable. The caller is responsible for fetching the template — this could be from OpenRegister, from a file, or from inline HTML. The `TemplateService` is a separate concern that manages CRUD.

**Options array** supports: `format` (A4/Letter), `orientation` (portrait/landscape), `margin` (array), `title` (PDF metadata).

### 4. Template CRUD: API endpoints in DocuDesk, consumed cross-app

**Decision**: DocuDesk exposes template CRUD via its own API (`/api/templates`). LarpingApp's frontend fetches templates from DocuDesk's API by filtering on `namespace=larpingapp`.

**Rationale**: Templates belong to DocuDesk's domain. LarpingApp's frontend already makes cross-app API calls (it calls OpenRegister's API for object storage). Adding DocuDesk template API calls follows the same pattern.

**Route pattern**: Standard REST — `GET/POST /api/templates`, `GET/PUT/DELETE /api/templates/{id}`.

### 5. LarpingApp migration: Thin wrapper, same UX

**Decision**: LarpingApp's `CharactersController.downloadPdf()` remains but becomes a thin wrapper:
1. Fetch character data (same as before)
2. Fetch template from DocuDesk's `TemplateService` via DI
3. Call DocuDesk's `PdfService.renderPdf()` with the template content + character data
4. Return `DataDownloadResponse` (same as before)

**Rationale**: The URL pattern and user flow remain identical. The frontend PDF modal switches from LarpingApp's template store to DocuDesk's template API, but the UX is unchanged.

## Risks / Trade-offs

**[Hard dependency on DocuDesk for PDF export]** → LarpingApp can't generate PDFs without DocuDesk installed. Mitigation: Check `IAppManager::isEnabledForUser('docudesk')` and gracefully hide PDF functionality when DocuDesk is absent. This is acceptable because PDF export is a secondary feature.

**[Template migration]** → Existing LarpingApp templates won't automatically appear in DocuDesk. Mitigation: Template count is low (typically < 10); manual re-creation is trivial. Could add a one-time migration command (`occ`) if needed.

**[Twig security]** → Twig templates can execute arbitrary PHP if the sandbox is not configured. Mitigation: Use Twig's `SandboxExtension` with a strict security policy that only allows safe filters/functions/tags. No `system()`, `exec()`, or file operations.

**[mPDF memory usage]** → Large HTML documents can exhaust PHP memory. Mitigation: Keep the existing `ini_set('memory_limit', '2048M')` pattern from LarpingApp, and set mPDF's `tempDir` to `/tmp/mpdf`.

**[Cross-app API versioning]** → If DocuDesk's template schema changes, LarpingApp's frontend may break. Mitigation: Template schema is simple (name, description, content, namespace) and unlikely to change. Use semver on the register schema.

## Migration Plan

### Phase 1: DocuDesk — Add PDF + Template services
1. Add mPDF and Twig to DocuDesk's `composer.json`
2. Create `PdfService` with `renderPdf()` method
3. Create `TemplateService` for CRUD operations via OpenRegister
4. Add `template` schema to `docudesk_register.json`
5. Create `PdfController` with `render` endpoint
6. Create `TemplatesController` with standard CRUD routes
7. Register routes in `routes.php`

### Phase 2: LarpingApp — Migrate to DocuDesk
1. Refactor `CharactersController.downloadPdf()` to use DocuDesk's `PdfService` via DI
2. Remove `CharacterService.createCharacterPdf()` method
3. Update frontend `RenderPdfFromCharacter.vue` to fetch templates from DocuDesk API
4. Remove template entity type from LarpingApp's `ObjectService` routing
5. Remove frontend template views/stores/modals (TemplatesList, TemplateDetails, EditTemplate)
6. Remove mPDF and Twig from LarpingApp's `composer.json`

### Rollback
If issues arise, LarpingApp can temporarily restore its own PDF generation by reverting the composer.json and controller changes. No data migration is involved (templates are recreated, not moved).

## Open Questions

1. **Should DocuDesk expose a frontend UI for template management?** Or is it purely an API service, with each consumer app providing its own template management UI? Recommendation: API-only for now, consumer apps handle UI.
2. **Should the `namespace` field be enforced server-side?** (i.e., LarpingApp can only CRUD templates with `namespace=larpingapp`). Recommendation: Yes, validate against the calling app's ID.
3. **Should we support template versioning?** (keeping old versions when a template is updated). Recommendation: Not in v1 — OpenRegister already has audit logging for change history.

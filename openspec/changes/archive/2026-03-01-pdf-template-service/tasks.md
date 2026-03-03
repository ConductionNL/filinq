## 1. DocuDesk — Dependencies and Schema

- [x] 1.1 Add `mpdf/mpdf: ^8.2` and `twig/twig: ^3.18` to DocuDesk's `composer.json` and run `composer update`
- [x] 1.2 Add `template` schema to `docudesk_register.json` with properties: name (string, required), description (string), content (string, required), namespace (string, required), format (string), orientation (string)
- [x] 1.3 Add `template` register entry in `docudesk_register.json` referencing the template schema

## 2. DocuDesk — PdfService

- [x] 2.1 Create `lib/Service/PdfService.php` with `renderPdf(string $templateContent, array $data, array $options = []): string` method
- [x] 2.2 Implement Twig environment setup with `SandboxExtension` and strict security policy (allowed filters, functions, tags only)
- [x] 2.3 Implement mPDF initialization with configurable options (format, orientation, margin, title) and `/tmp/mpdf` temp directory management
- [x] 2.4 Implement HTML rendering pipeline: Twig render → mPDF WriteHTML → Output(STRING_RETURN)

## 3. DocuDesk — TemplateService

- [x] 3.1 Create `lib/Service/TemplateService.php` with CRUD methods wrapping OpenRegister ObjectService
- [x] 3.2 Implement `getTemplates()`, `getTemplate()`, `createTemplate()`, `updateTemplate()`, `deleteTemplate()` methods
- [x] 3.3 Implement `getTemplatesByNamespace(string $namespace)` convenience method
- [x] 3.4 Add namespace validation: lowercase alphanumeric only, required on create, immutable on update

## 4. DocuDesk — Controllers and Routes

- [x] 4.1 Create `lib/Controller/PdfController.php` with `render()` endpoint accepting template, data, options, filename in JSON body
- [x] 4.2 Create `lib/Controller/TemplatesController.php` with `index()`, `show()`, `create()`, `update()`, `destroy()` methods
- [x] 4.3 Add PDF and template routes to `appinfo/routes.php`: `POST /api/pdf/render`, `GET/POST /api/templates`, `GET/PUT/DELETE /api/templates/{id}`

## 5. LarpingApp — Backend Migration

- [x] 5.1 Refactor `CharactersController.downloadPdf()` to resolve DocuDesk's `PdfService` and `TemplateService` via DI container
- [x] 5.2 Add `IAppManager::isEnabledForUser('docudesk')` check — return 424 JSONResponse if DocuDesk not available
- [x] 5.3 Remove `CharacterService.createCharacterPdf()` method and all mPDF/Twig imports from CharacterService
- [x] 5.4 Remove `template` entity type from ObjectService `getMapper()` routing and ObjectsController type matching
- [x] 5.5 Remove `mpdf/mpdf` and `twig/twig` from LarpingApp's `composer.json` and run `composer update`

## 6. LarpingApp — Frontend Migration

- [x] 6.1 Update `RenderPdfFromCharacter.vue` to fetch templates from DocuDesk API (`/apps/docudesk/api/templates?namespace=larpingapp`) instead of LarpingApp template store
- [x] 6.2 Add DocuDesk availability check — hide "Als pdf downloaden" action button when DocuDesk is not installed
- [x] 6.3 Remove template navigation entry from LarpingApp sidebar (`src/views/` or navigation config)
- [x] 6.4 Remove `TemplatesList.vue`, `TemplateDetails.vue`, `EditTemplate.vue` components
- [x] 6.5 Remove Pinia template store and TypeScript Template entity class
- [x] 6.6 Run `npm run build` to verify frontend compiles without template references

## 7. Verification

- [x] 7.1 Test DocuDesk PDF render endpoint: POST template + data, verify PDF output
- [x] 7.2 Test DocuDesk template CRUD: create, list (with namespace filter), get, update, delete
- [x] 7.3 Test LarpingApp PDF download flow end-to-end: create template in DocuDesk → download character PDF in LarpingApp
- [x] 7.4 Test LarpingApp graceful degradation: disable DocuDesk → verify PDF button is hidden and download returns 424
- [x] 7.5 Run PHPCS and PHPMD on both DocuDesk and LarpingApp to verify code quality

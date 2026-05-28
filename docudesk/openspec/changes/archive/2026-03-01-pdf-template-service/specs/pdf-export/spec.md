## MODIFIED Requirements

### Requirement: PDF Generation
The system SHALL generate PDFs by delegating to DocuDesk's `PdfService` via Nextcloud's DI container instead of directly using mPDF and Twig. `CharactersController.downloadPdf()` SHALL:

1. Fetch the character data via ObjectService (unchanged)
2. Fetch the template from DocuDesk's `TemplateService` via DI
3. Call `PdfService::renderPdf()` with the template content and character data
4. Return a `DataDownloadResponse` with the PDF binary (unchanged response format)

The controller SHALL check `IAppManager::isEnabledForUser('docudesk')` before attempting PDF generation. If DocuDesk is not available, return a 424 (Failed Dependency) JSONResponse.

#### Scenario: Download a Character PDF (via DocuDesk)
- **WHEN** a user navigates to `GET /characters/{id}/download/{templateId}`
- **THEN** `CharactersController.downloadPdf()` resolves DocuDesk's `PdfService` and `TemplateService` from the DI container, fetches the template, renders the PDF, and returns a `DataDownloadResponse` with filename `{characterName}_character_sheet.pdf`

#### Scenario: DocuDesk not installed
- **WHEN** a user navigates to the PDF download URL but DocuDesk is not installed/enabled
- **THEN** the controller returns a 424 JSONResponse with `{"error": "PDF generation requires the DocuDesk app to be installed and enabled"}`

#### Scenario: Character or Template Not Found
- **WHEN** either the character ID or template ID does not exist
- **THEN** the controller catches `\Exception` and returns a 404 JSONResponse with `{"error": "Character or template not found"}`

## REMOVED Requirements

### Requirement: Local mPDF and Twig rendering
**Reason**: PDF rendering is now handled by DocuDesk's PdfService. LarpingApp no longer directly uses mPDF or Twig.
**Migration**: Remove `CharacterService.createCharacterPdf()` method. Remove `mpdf/mpdf` and `twig/twig` from LarpingApp's `composer.json`.

### Requirement: Template Management
**Reason**: Templates are now managed by DocuDesk's TemplateService. LarpingApp no longer stores templates as its own objects.
**Migration**: Remove `template` entity type from ObjectService routing. Remove frontend template views (`TemplatesList.vue`, `TemplateDetails.vue`, `EditTemplate.vue`), Pinia template store, and TypeScript Template entity. Update `RenderPdfFromCharacter.vue` to fetch templates from DocuDesk's API (`GET /apps/docudesk/api/templates?namespace=larpingapp`).

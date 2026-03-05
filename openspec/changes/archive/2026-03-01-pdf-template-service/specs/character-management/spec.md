## MODIFIED Requirements

### Requirement: PDF Download Flow
The PDF download action on the character detail page SHALL check whether DocuDesk is available before showing the download button. The frontend SHALL call LarpingApp's capabilities endpoint (or check app list) to determine if DocuDesk is installed.

The template selector in the PDF download modal SHALL fetch templates from DocuDesk's API (`GET /apps/docudesk/api/templates?namespace=larpingapp`) instead of from LarpingApp's template store.

#### Scenario: PDF download with DocuDesk available
- **WHEN** the user views a character detail page and DocuDesk is installed
- **THEN** the "Als pdf downloaden" action button is visible
- **WHEN** the user clicks the button
- **THEN** a modal opens with templates fetched from DocuDesk's template API filtered by `namespace=larpingapp`

#### Scenario: PDF download with DocuDesk not available
- **WHEN** the user views a character detail page and DocuDesk is NOT installed
- **THEN** the "Als pdf downloaden" action button is hidden

#### Scenario: No templates exist for LarpingApp
- **WHEN** the user opens the PDF download modal but no templates exist with `namespace=larpingapp` in DocuDesk
- **THEN** the modal shows an empty state message indicating no templates are available and the download button is disabled

## REMOVED Requirements

### Requirement: Template navigation and management UI
**Reason**: Template CRUD is now managed by DocuDesk. LarpingApp no longer maintains its own template views.
**Migration**: Remove the "Templates" navigation entry from LarpingApp's sidebar. Remove `TemplatesList.vue`, `TemplateDetails.vue`, `EditTemplate.vue` components. Remove the Pinia template store (`src/store/modules/template.js`). Remove the TypeScript Template entity class. Users manage templates through DocuDesk's interface or API.

## ADDED Requirements

### Requirement: Template versioning with full history

The system SHALL store a version history for each template. Each version SHALL capture:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| version | integer | Yes | Auto-incrementing version number |
| content | string | Yes | Template HTML content at this version |
| name | string | Yes | Template name at this version |
| description | string | No | Template description at this version |
| format | string | No | Page format at this version |
| orientation | string | No | Orientation at this version |
| editor | string | Yes | Nextcloud user ID who made the edit |
| created | datetime | Yes | Timestamp when this version was saved |
| changelog | string | No | Optional note describing the change |

Versions SHALL be stored as OpenRegister objects in a new `templateVersion` schema within the templates register. Each version SHALL reference the parent template via a `templateId` field. The current/active version SHALL always be the template object itself; versions capture the state before each update.

#### Scenario: Version created on template update
- **GIVEN** a template "abc-123" exists with version 1
- **WHEN** a user updates the template content
- **THEN** the previous state is saved as a version object with version=1
- **AND** the template object is updated to the new state

#### Scenario: Version history listing
- **GIVEN** a template has been edited 5 times
- **WHEN** `GET /api/templates/{id}/versions` is called
- **THEN** all 5 version objects are returned, ordered by version number descending
- **AND** each version includes the editor, timestamp, and changelog

#### Scenario: Version rollback
- **GIVEN** a template has versions 1 through 5
- **WHEN** `POST /api/templates/{id}/versions/{versionId}/restore` is called
- **THEN** the current template state is saved as a new version (version 6)
- **AND** the template content is restored to the state captured in the specified version
- **AND** the response returns the restored template object

#### Scenario: Version diff comparison
- **GIVEN** a template has versions 3 and 5
- **WHEN** `GET /api/templates/{id}/versions/diff?from={v3Id}&to={v5Id}` is called
- **THEN** the response includes both version contents for client-side diff rendering

### Requirement: Template categories and tags

The template schema SHALL be extended with:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| category | string | No | Primary category (e.g., beschikkingen, brieven, notities, rapporten) |
| tags | array of strings | No | Freeform tags for additional classification |

Categories SHALL be facetable for efficient filtering. Tags SHALL be searchable.

#### Scenario: Filter by category
- **GIVEN** 50 templates exist across 5 categories
- **WHEN** `GET /api/templates?category=beschikkingen` is called
- **THEN** only templates with category "beschikkingen" are returned

#### Scenario: Search by tag
- **GIVEN** templates tagged with "woo", "intern", "extern"
- **WHEN** `GET /api/templates?tags=woo` is called
- **THEN** only templates containing the tag "woo" are returned

#### Scenario: Multiple filters combined
- **GIVEN** templates with various categories, tags, and namespaces
- **WHEN** `GET /api/templates?namespace=docudesk&category=brieven&tags=woo` is called
- **THEN** only templates matching all three filters are returned

### Requirement: WYSIWYG template editor

The system SHALL provide a browser-based rich text editor for creating and editing templates without requiring HTML or Twig knowledge. The editor SHALL:

1. Use TipTap (ProseMirror-based) as the editor framework, consistent with Nextcloud Text
2. Support standard formatting: bold, italic, underline, headings (H1-H4), bullet/numbered lists, tables, horizontal rules
3. Provide a "merge field" insertion UI that lets users pick from available data fields (e.g., `{{ naam }}`, `{{ datum }}`) and inserts the corresponding Twig variable
4. Support image insertion via Nextcloud file picker
5. Output clean HTML compatible with Twig rendering and mPDF conversion
6. Provide a source code view toggle for advanced users who want to edit raw HTML/Twig

The editor SHALL be implemented as a Vue 2 component at `src/views/templates/TemplateEditor.vue`.

#### Scenario: Non-technical user creates a template
- **GIVEN** a user with no HTML knowledge opens the template editor
- **WHEN** they type text, apply bold formatting, insert a table, and add a merge field
- **THEN** the editor produces valid Twig/HTML that renders correctly via PdfService

#### Scenario: Merge field insertion
- **GIVEN** the user is editing a template
- **WHEN** they click the merge field button and select "naam"
- **THEN** `{{ naam }}` is inserted at the cursor position
- **AND** it renders as a styled placeholder pill in the editor (not raw Twig syntax)

#### Scenario: Source code toggle
- **GIVEN** a template with WYSIWYG content
- **WHEN** the user toggles to source view
- **THEN** the raw HTML/Twig content is shown in a code editor
- **AND** changes in source view are reflected back in WYSIWYG view

### Requirement: Conditional sections

The system SHALL support conditional template sections that show or hide content based on data values. Conditions SHALL be stored as structured metadata within the template content (HTML data attributes), not as raw Twig `{% if %}` blocks that require coding knowledge.

The editor SHALL provide a "conditional section" toolbar button that:
1. Wraps the selected content in a conditional block
2. Opens a dialog to define the condition: field name, operator (equals, not equals, contains, is empty, is not empty), and value
3. Stores the condition as a data attribute on a wrapper element
4. Displays conditional sections with a visual indicator (colored border + condition label)

The `TemplateRenderer` SHALL parse these data attributes and convert them to Twig `{% if %}` blocks before rendering.

#### Scenario: Define a conditional section
- **GIVEN** a user selects a paragraph in the template editor
- **WHEN** they click "conditional section" and set condition `zaaktype equals omgevingsvergunning`
- **THEN** the paragraph is wrapped with a visual condition indicator
- **AND** the stored HTML includes `data-condition-field="zaaktype" data-condition-op="equals" data-condition-value="omgevingsvergunning"`

#### Scenario: Conditional rendering
- **GIVEN** a template has a section conditioned on `zaaktype == omgevingsvergunning`
- **WHEN** rendered with data `{"zaaktype": "omgevingsvergunning"}`
- **THEN** the section is included in the output
- **WHEN** rendered with data `{"zaaktype": "bouwvergunning"}`
- **THEN** the section is omitted from the output

### Requirement: Template preview

The system SHALL provide a preview endpoint and UI that renders a template with sample data without saving.

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/templates/preview` | Render template content with provided sample data, returns HTML |
| POST | `/api/templates/{id}/preview` | Render existing template with provided sample data, returns HTML |

The template editor SHALL include a "Preview" button/panel that:
1. Sends current editor content + user-provided sample data to the preview endpoint
2. Displays the rendered HTML in a side panel or modal
3. Optionally generates a PDF preview via the existing PdfService

#### Scenario: Preview with sample data
- **GIVEN** a template containing `{{ naam }}` and `{{ datum }}`
- **WHEN** the user clicks "Preview" with sample data `{"naam": "Jan de Vries", "datum": "2026-03-22"}`
- **THEN** the preview panel shows the rendered output with "Jan de Vries" and "2026-03-22" substituted

#### Scenario: Preview unsaved template
- **GIVEN** a user is creating a new template (not yet saved)
- **WHEN** they click "Preview"
- **THEN** the current editor content is sent to `POST /api/templates/preview` for rendering

### Requirement: Template duplication

The system SHALL provide a duplicate endpoint:

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/templates/{id}/duplicate` | Create a copy of the template with name suffixed " (kopie)" |

The duplicate SHALL copy all fields except: the UUID (new one generated), name (appended " (kopie)"), and version history (starts fresh).

#### Scenario: Duplicate a template
- **GIVEN** a template "Beschikking WOO" exists
- **WHEN** `POST /api/templates/{id}/duplicate` is called
- **THEN** a new template "Beschikking WOO (kopie)" is created with identical content
- **AND** it has a new UUID and no version history

### Requirement: Template locking

The system SHALL provide optimistic locking to prevent concurrent edit conflicts:

| Field | Type | Description |
|-------|------|-------------|
| lockedBy | string or null | Nextcloud user ID holding the lock |
| lockedAt | datetime or null | When the lock was acquired |

Lock behavior:
- When a user opens a template for editing, the frontend sends `POST /api/templates/{id}/lock`
- Lock expires after 15 minutes of inactivity (configurable)
- Lock is released on save, on explicit unlock (`DELETE /api/templates/{id}/lock`), or on expiration
- If another user tries to edit a locked template, the UI shows who holds the lock and when it was acquired

#### Scenario: Lock acquired on edit
- **GIVEN** template "abc-123" is not locked
- **WHEN** user A opens it for editing
- **THEN** the template is locked by user A with the current timestamp

#### Scenario: Lock conflict
- **GIVEN** template "abc-123" is locked by user A
- **WHEN** user B tries to acquire a lock
- **THEN** the lock request returns 409 Conflict with `lockedBy` and `lockedAt` details

#### Scenario: Lock expiration
- **GIVEN** template "abc-123" was locked by user A 16 minutes ago
- **WHEN** user B tries to acquire a lock
- **THEN** the stale lock is released and user B acquires the lock

## CHANGED Requirements

### Template schema extended (TMPL-002)
The existing template schema properties are extended with the following additional fields:
- `category` (string, facetable, max 64 chars)
- `tags` (array of strings)
- `lockedBy` (string or null)
- `lockedAt` (datetime or null)

These are added to `docudesk_register.json` alongside existing fields. The `version` field is NOT added to the template object itself; version data lives in the separate `templateVersion` schema.

### Template API extended (TMPL-010 through TMPL-014)
Existing CRUD endpoints gain:
- `category` and `tags` as additional filter parameters on `GET /api/templates`
- Lock metadata (`lockedBy`, `lockedAt`) included in template responses

### New API endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/templates/{id}/versions` | List version history |
| POST | `/api/templates/{id}/versions/{versionId}/restore` | Restore a previous version |
| GET | `/api/templates/{id}/versions/diff` | Compare two versions |
| POST | `/api/templates/preview` | Preview template content |
| POST | `/api/templates/{id}/preview` | Preview existing template |
| POST | `/api/templates/{id}/duplicate` | Duplicate a template |
| POST | `/api/templates/{id}/lock` | Acquire edit lock |
| DELETE | `/api/templates/{id}/lock` | Release edit lock |

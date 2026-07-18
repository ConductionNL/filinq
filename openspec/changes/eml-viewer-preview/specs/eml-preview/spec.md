---
status: draft
---

# EML Preview — original-message preview and upload acceptance

Adds the `eml-preview` capability: EML files can be uploaded into the anonymisation UI, and the original (un-redacted) message can be previewed in the in-app file viewer as a server-rendered PDF/A-3b. The preview reuses `eml-pdf-assembly`'s assembly pipeline with an empty entity set, so there is no separate original-render path.

## ADDED Requirements

### Requirement: The anonymisation upload widget MUST accept EML files

The anonymisation upload widget MUST accept `message/rfc822` (`.eml`) files for upload, alongside the existing `docx` / `txt` / `pdf` formats. Acceptance MUST hold both for the file picker (`accept` attribute) and for the drag-and-drop / selection allow-list, matching on MIME type OR filename extension (drag-and-drop may omit the MIME type).

#### Scenario: An EML file is accepted by the upload widget

- **GIVEN** the anonymisation upload widget
- **WHEN** the user selects or drops a file whose MIME is `message/rfc822` or whose name ends in `.eml`
- **THEN** the file is accepted (added to the pending-upload set), not rejected

#### Scenario: Unsupported formats are still rejected

- **GIVEN** the anonymisation upload widget
- **WHEN** the user selects a file that is neither `docx`/`txt`/`pdf` nor `.eml`/`message/rfc822`
- **THEN** the file is rejected

### Requirement: A backend endpoint MUST render an original-EML preview as PDF

DocuDesk MUST expose `GET /api/anonymization/eml-preview/{fileId}` that returns a PDF/A-3b rendering of the ORIGINAL (un-redacted) content of the `message/rfc822` file identified by `fileId`. The endpoint MUST require an authenticated user (`#[NoAdminRequired]`). The render MUST be produced by calling OpenRegister's anonymise-EML API with an EMPTY entity set (so nothing is redacted) and assembling the result via `eml-pdf-assembly`'s `EmlPdfAssemblyService`. On a render failure the endpoint MUST return a `422` JSON error and MUST NOT write any file.

#### Scenario: Preview returns the rendered original message

- **GIVEN** an uploaded `.eml` file with id `F`
- **WHEN** an authenticated user requests `GET /api/anonymization/eml-preview/F`
- **THEN** the response is a `application/pdf` download whose content renders the original headers, body and renderable attachments
- **AND** no entity is redacted (an empty entity set was used)
- **AND** no file is written to storage

#### Scenario: Render failure surfaces as 422

- **GIVEN** a file id for which the anonymise-EML API or assembly fails
- **WHEN** the preview endpoint is called
- **THEN** it returns a `422` response with a JSON error body
- **AND** no partial file is written

#### Scenario: OpenRegister unavailable

- **GIVEN** an install where OpenRegister (or its anonymise-EML API) is not present
- **WHEN** the preview endpoint is called
- **THEN** it returns a `422` response rather than a server error

### Requirement: The file viewer MUST render EML via the server-rendered preview

The in-app file viewer MUST render a `message/rfc822` / `.eml` file by loading the server-rendered preview PDF for that file's id, using the same PDF viewer component used for native PDFs. The viewer's original/anonymised toggle MUST treat this preview as the "original" side for EML, with the anonymised PDF (when present) as the other side.

#### Scenario: Opening an EML shows the rendered preview

- **GIVEN** an `.eml` file opened in the DocuDesk file viewer
- **WHEN** the viewer resolves the file type
- **THEN** it renders the PDF preview fetched from `/api/anonymization/eml-preview/{fileId}`
- **AND** it does not show the "cannot be previewed" fallback

#### Scenario: Toggle switches between original preview and anonymised PDF

- **GIVEN** an `.eml` whose anonymised PDF variant has been produced
- **WHEN** the user toggles between original and anonymised
- **THEN** the original side shows the EML preview endpoint's PDF
- **AND** the anonymised side shows the anonymised PDF loaded from its WebDAV path
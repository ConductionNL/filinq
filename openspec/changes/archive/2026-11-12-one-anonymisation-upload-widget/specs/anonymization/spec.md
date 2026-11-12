---
status: draft
---

# Anonymization: delta for one upload widget

Filinq shows one anonymisation upload widget wherever a user can start an anonymisation by uploading files.

## ADDED Requirements

### Requirement: One anonymisation upload widget MUST serve every upload surface

The Anonymisation page, the quick anonymisation section of the Filinq dashboard and the Nextcloud dashboard tile MUST render the same upload widget (`AnonymizationWidget`). No second upload widget with its own file list, dialog or routing MAY exist.

#### Scenario: Every surface renders the same widget

- **GIVEN** the Anonymisation page, the Filinq dashboard and the Nextcloud dashboard tile
- **WHEN** each renders its upload area
- **THEN** each mounts `AnonymizationWidget`
- @e2e exclude component wiring, covered by tests/vitest/anonymizationWidgetMerged.spec.js

### Requirement: The upload widget MUST accept .docx, .odt, .pdf, .txt and .eml everywhere

The widget MUST accept Word (.docx), ODT, PDF, plain text and email (.eml) files on every surface, by MIME type or by file extension. Any other file MUST be skipped with the "supported formats" message, which names the skipped files.

#### Scenario: An ODT and an email dropped on the Nextcloud dashboard are accepted

- **GIVEN** the widget on the Nextcloud dashboard
- **WHEN** the user drops `brief.odt` and `melding.eml`
- **THEN** both files are accepted and the upload dialog opens for them
- @e2e exclude the Nextcloud dashboard page is outside the app's e2e harness, covered by tests/vitest/anonymizationWidgetMerged.spec.js

#### Scenario: An unsupported file is skipped with a message

- **GIVEN** the widget on any surface
- **WHEN** the user drops `begroting.xlsx` together with `brief.pdf`
- **THEN** `brief.pdf` goes to the upload dialog
- **AND** an error names `begroting.xlsx` as skipped
- @e2e exclude covered by tests/vitest/anonymizationWidgetMerged.spec.js

### Requirement: Every upload MUST go through the one upload dialog

Each drop or file selection with at least one accepted file MUST open the upload dialog (`AnonymizationUploadDialog`) before anything is uploaded. With one file, the dialog shows its name and the grondslagen choice. With several files, it asks for an optional dossier name and the grondslagen choice. Cancel uploads nothing.

#### Scenario: One file asks before it uploads

- **GIVEN** the widget on the Filinq dashboard
- **WHEN** the user selects one PDF
- **THEN** the upload dialog opens in its single-file form
- **AND** nothing is uploaded until the user confirms
- @e2e exclude covered by tests/vitest/anonymizationWidgetMerged.spec.js

#### Scenario: Several files ask for a dossier name

- **GIVEN** the widget on any surface
- **WHEN** the user drops three files and confirms with dossier name "Bezwaar 12"
- **THEN** the files are uploaded as dossier "Bezwaar 12" and the dossier is bound
- @e2e exclude covered by tests/vitest/anonymizationWidgetMerged.spec.js

### Requirement: After an upload the user MUST land on My documents with the first file open

After a confirmed upload, inside the app the widget MUST open the first uploaded file in the file viewer, with the grondslagen choice from the dialog, and route to My documents. A dossier upload MUST also open the dossier folder. On the Nextcloud dashboard, where the app's router is absent, the widget MUST send the browser to Filinq's My documents page.

#### Scenario: Inside the app the first file opens on My documents

- **GIVEN** the widget on the Filinq dashboard
- **WHEN** the user confirms the upload of one file
- **THEN** the file viewer opens that file
- **AND** the app routes to My documents
- @e2e exclude covered by tests/vitest/anonymizationWidgetMerged.spec.js

#### Scenario: On the Nextcloud dashboard the browser goes to My documents

- **GIVEN** the widget on the Nextcloud dashboard
- **WHEN** the user confirms the upload of one file
- **THEN** the browser opens `/apps/filinq/my-documents`
- @e2e exclude covered by tests/vitest/anonymizationWidgetMerged.spec.js

#### Scenario: Outside the app the widget links to Filinq

- **GIVEN** the widget on the Nextcloud dashboard
- **WHEN** it renders
- **THEN** it shows an "Open Filinq" link to the app
- **AND** inside the app that link is not shown
- @e2e exclude covered by tests/vitest/anonymizationWidgetMerged.spec.js

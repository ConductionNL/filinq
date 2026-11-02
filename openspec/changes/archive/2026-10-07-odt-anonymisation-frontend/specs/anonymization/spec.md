## ADDED Requirements

### Requirement: The anonymisation upload widget MUST accept ODT files

The Filinq anonymisation upload surface (`AnonymizationWidget.vue`) MUST accept `.odt` (OpenDocument Text, MIME `application/vnd.oasis.opendocument.text`) files for anonymisation, alongside the existing `docx`/`txt`/`pdf`/`eml` formats. Acceptance MUST hold both when the browser supplies the MIME type and when only the filename extension is available (drag-and-drop). The file input's `accept` attribute, the upload allow-list, and the user-facing "supported formats" copy MUST all include ODT, and the copy MUST have NL and EN translations.

#### Scenario: An ODT selected from the file picker is accepted

- **GIVEN** a file `brief.odt` with MIME `application/vnd.oasis.opendocument.text`
- **WHEN** it is partitioned by the upload allow-list
- **THEN** it is accepted (not rejected)

#### Scenario: An ODT dropped without a MIME type is accepted by extension

- **GIVEN** a file `brief.odt` whose browser-supplied MIME is empty
- **WHEN** it is partitioned by the upload allow-list
- **THEN** it is accepted on its `.odt` extension

#### Scenario: Previously-supported formats still pass and unsupported formats are still rejected

- **GIVEN** a batch containing `.docx`, `.odt`, `.pdf`, `.txt`, `.eml`, and an unsupported `.xlsx`
- **WHEN** the batch is partitioned
- **THEN** the docx/odt/pdf/txt/eml files are accepted
- **AND** the `.xlsx` file is rejected and reported in the "supported formats" skip message

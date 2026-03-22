## ADDED Requirements

### Requirement: OCR enable/disable toggle
The admin settings panel SHALL include a toggle to enable or disable OCR processing globally, stored as `ocr_enabled` in IAppConfig.

#### Scenario: OCR toggle displayed in admin settings
- **WHEN** an administrator opens the DocuDesk admin settings
- **THEN** the settings panel SHALL display an "OCR Document Scanning" section with an NcCheckboxRadioSwitch to enable/disable OCR

#### Scenario: OCR disabled via toggle
- **WHEN** an administrator disables the OCR toggle
- **THEN** the system SHALL skip all OCR processing regardless of file type, and the `ocr_enabled` config key SHALL be set to "0"

#### Scenario: OCR enabled by default
- **WHEN** the OCR settings have never been configured
- **THEN** the `ocr_enabled` setting SHALL default to "1" (enabled)

### Requirement: OCR language selection
The admin settings panel SHALL allow administrators to select which Tesseract language models to use for OCR processing.

#### Scenario: Language selection in admin settings
- **WHEN** an administrator opens the OCR settings section
- **THEN** the settings panel SHALL display checkboxes for available languages: Dutch (nld), English (eng), German (deu), French (fra)

#### Scenario: Language configuration stored
- **WHEN** an administrator selects Dutch and English
- **THEN** the `ocr_languages` config key SHALL be stored as "nld+eng" in IAppConfig

#### Scenario: Default language selection
- **WHEN** no language selection has been made
- **THEN** the system SHALL default to "nld+eng" (Dutch and English)

### Requirement: OCR DPI configuration
The admin settings panel SHALL allow administrators to configure the DPI used for PDF-to-image conversion during OCR.

#### Scenario: DPI input in admin settings
- **WHEN** an administrator opens the OCR settings section
- **THEN** the settings panel SHALL display a numeric input for DPI with a default value of 300

#### Scenario: DPI value range
- **WHEN** an administrator enters a DPI value
- **THEN** the input SHALL accept values between 72 and 600

#### Scenario: DPI stored in config
- **WHEN** an administrator sets DPI to 400
- **THEN** the `ocr_dpi` config key SHALL be stored as "400" in IAppConfig

### Requirement: Tesseract availability status
The admin settings panel SHALL display the Tesseract installation status so administrators can verify OCR is available.

#### Scenario: Tesseract installed
- **WHEN** the Tesseract binary is found on the system
- **THEN** the OCR settings section SHALL display a success indicator with the Tesseract version number

#### Scenario: Tesseract not installed
- **WHEN** the Tesseract binary is not found on the system
- **THEN** the OCR settings section SHALL display a warning NcNoteCard explaining that Tesseract must be installed for OCR to work

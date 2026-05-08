## ADDED Requirements

### Requirement: OCR text extraction from image-based documents
The system SHALL extract text from image-based documents (scanned PDFs, TIFF, PNG, JPG) using Tesseract OCR. All OCR processing SHALL run locally on the server with no external cloud service calls, in compliance with DocuDesk's 100% local processing standard.

#### Scenario: OCR extracts text from a scanned PDF
- **WHEN** a user triggers entity extraction on a PDF file that contains no embedded text (scanned document)
- **THEN** the system SHALL run Tesseract OCR on each page of the PDF, extract the text content, and pass it to the entity detection pipeline

#### Scenario: OCR extracts text from an image file
- **WHEN** a user triggers entity extraction on an image file (PNG, JPG, TIFF)
- **THEN** the system SHALL run Tesseract OCR on the image and extract text content for entity detection

#### Scenario: OCR is skipped for digital-born documents
- **WHEN** a user triggers entity extraction on a PDF that already contains embedded text
- **THEN** the system SHALL skip OCR and use the existing text extraction path via OpenRegister's TextExtractionService

### Requirement: OCR detection determines processing path
The system SHALL automatically detect whether a file requires OCR based on its MIME type and text content availability, without requiring user intervention.

#### Scenario: Image MIME types always trigger OCR
- **WHEN** a file has MIME type image/png, image/jpeg, or image/tiff
- **THEN** the system SHALL always attempt OCR text extraction

#### Scenario: PDF files use fallback OCR
- **WHEN** a file has MIME type application/pdf and OpenRegister's TextExtractionService returns empty text
- **THEN** the system SHALL fall back to OCR text extraction

#### Scenario: Non-image non-PDF files skip OCR
- **WHEN** a file has a MIME type other than image/* or application/pdf (e.g., application/msword, text/plain)
- **THEN** the system SHALL skip OCR and use standard text extraction only

### Requirement: OCR language configuration
The system SHALL support configurable Tesseract language models for OCR processing, defaulting to Dutch and English.

#### Scenario: Default language configuration
- **WHEN** no OCR language configuration has been set by an administrator
- **THEN** the system SHALL use "nld+eng" (Dutch and English) as the default Tesseract language string

#### Scenario: Custom language configuration
- **WHEN** an administrator has configured custom OCR languages (e.g., "nld+eng+deu+fra")
- **THEN** the system SHALL pass the configured language string to Tesseract for all OCR operations

### Requirement: OCR DPI configuration
The system SHALL support configurable DPI for PDF-to-image conversion during OCR, affecting scan quality and processing speed.

#### Scenario: Default DPI
- **WHEN** no DPI configuration has been set
- **THEN** the system SHALL use 300 DPI for PDF page-to-image conversion

#### Scenario: Custom DPI
- **WHEN** an administrator has configured a custom DPI value
- **THEN** the system SHALL use the configured DPI for all PDF-to-image conversions

### Requirement: Graceful degradation when Tesseract is unavailable
The system SHALL continue to function normally when the Tesseract binary is not installed, skipping OCR with appropriate logging.

#### Scenario: Tesseract binary not found
- **WHEN** the Tesseract binary is not installed on the system
- **THEN** the system SHALL skip OCR processing, log a warning, and continue with standard text extraction

#### Scenario: Tesseract availability check in admin settings
- **WHEN** an administrator views the OCR settings section
- **THEN** the system SHALL display whether Tesseract is installed and which version is available

### Requirement: OCR confidence scoring
The system SHALL report OCR confidence scores so users can assess the reliability of extracted text.

#### Scenario: Confidence score returned after OCR
- **WHEN** OCR processing completes successfully on a document
- **THEN** the system SHALL include an `ocrConfidence` field (0-100) in the extraction response indicating Tesseract's mean confidence for the extracted text

### Requirement: OCR status tracking per file
The system SHALL track whether a file was processed with OCR and expose this in the file listing.

#### Scenario: File listing shows OCR status
- **WHEN** a user views the processed file listing
- **THEN** each file that was processed with OCR SHALL display an `ocrProcessed` boolean flag and the `ocrConfidence` score

#### Scenario: Non-OCR files show no OCR metadata
- **WHEN** a user views the processed file listing for a digital-born document
- **THEN** the file SHALL show `ocrProcessed: false` with no confidence score

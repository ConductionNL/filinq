## MODIFIED Requirements

### Requirement: Entity Extraction with OCR Pre-processing
Text extraction is performed via OpenRegister's `TextExtractionService::extractFile()`. Before calling TextExtractionService, the system SHALL check if the file requires OCR (image MIME types or PDFs without embedded text) and run Tesseract OCR to extract text. The OCR-extracted text SHALL be made available to TextExtractionService for entity detection.

#### Scenario: Extraction with OCR for scanned PDF
- **WHEN** a user calls `POST /api/anonymization/extract/{fileId}` for a scanned PDF (no embedded text)
- **THEN** the system SHALL run OCR first, then pass the extracted text through entity detection, returning the entities array and entityCount as before

#### Scenario: Extraction without OCR for digital PDF
- **WHEN** a user calls `POST /api/anonymization/extract/{fileId}` for a digital-born PDF
- **THEN** the system SHALL skip OCR and extract entities via TextExtractionService as currently implemented

#### Scenario: Extraction with OCR for image files
- **WHEN** a user calls `POST /api/anonymization/extract/{fileId}` for an image file (PNG, JPG, TIFF)
- **THEN** the system SHALL run OCR on the image, then detect entities in the OCR-extracted text

### Requirement: Extraction response includes OCR metadata
The extraction response SHALL include OCR processing metadata alongside the existing entities data.

#### Scenario: OCR metadata in extraction response
- **WHEN** OCR was used during entity extraction
- **THEN** the response SHALL include `ocrProcessed: true` and `ocrConfidence` (0-100) in addition to the existing `entities` and `entityCount` fields

#### Scenario: No OCR metadata for standard extraction
- **WHEN** OCR was not needed during entity extraction
- **THEN** the response SHALL include `ocrProcessed: false` with no ocrConfidence field

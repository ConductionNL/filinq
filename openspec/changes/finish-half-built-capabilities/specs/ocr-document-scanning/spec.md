# ocr-document-scanning Specification (delta)

## ADDED Requirements

### Requirement: An OCR run makes the file itself searchable (REQ-FHB-01)

When OCR recognises text in a scanned PDF, Filinq SHALL write that text into the file as an invisible text layer behind the page images, so a search in Nextcloud or OpenRegister finds the file by its words. The page images SHALL stay unchanged and the file before the run SHALL stay available as a file version. An image file (PNG, JPEG, TIFF) SHALL get a searchable PDF beside it.

#### Scenario: A scanned letter is found by a word on its page

- **GIVEN** a scanned PDF without a text layer that reads "Bezwaar tegen besluit"
- **WHEN** OCR runs on the file
- **THEN** the file has a text layer with "Bezwaar tegen besluit"
- **AND** a search for "bezwaar" finds the file
- **AND** the previous version of the file is still in its version history

#### Scenario: A photographed page gets a searchable copy

- **GIVEN** a JPEG of a printed page
- **WHEN** OCR runs on the file
- **THEN** a PDF with the image and its text layer is stored beside the JPEG

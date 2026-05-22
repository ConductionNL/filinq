## ADDED Requirements

### Requirement: True PDF text removal (stream mutation, not visual cover)

When a set of approved annotations exists on a PDF, the export phase SHALL mutate the PDF content stream to remove text objects corresponding to the redaction rectangles (not merely draw a black rectangle over them). The system MUST flatten all vector annotations so they cannot be removed by downstream viewers, and MUST verify the output by re-extracting text and asserting zero matches against the source text fragments.

#### Scenario: Text is removed from PDF content stream
- **GIVEN** a PDF with visible text "Burgerservicenummer 123456789" and an approved annotation on that text
- **WHEN** the export phase runs
- **THEN** the output PDF's content stream has the text object removed (not just covered)
- **AND** text extraction tools (pdftotext, PyPDF2, etc.) on the output do NOT return "123456789" or surrounding context

#### Scenario: Annotations are flattened to prevent removal
- **GIVEN** annotations on the output PDF (black rectangles drawn by the redaction service)
- **WHEN** the export phase completes
- **THEN** the annotations are flattened into the page raster
- **AND** a downstream user cannot delete or modify the redaction rectangle in a PDF viewer

#### Scenario: Export verification re-extracts and asserts zero matches
- **GIVEN** an output PDF after text removal and flattening
- **WHEN** the export phase runs verification
- **THEN** the system re-extracts all text from the output
- **AND** compares extracted text against the source text fragments from the annotations
- **AND** asserts zero substring matches (0% match rate)
- **AND** logs pass/fail status in the job record

#### Scenario: Export fails if verification does not pass
- **GIVEN** re-extraction finds a match (e.g., partial text recovery)
- **WHEN** verification fails
- **THEN** the export is aborted and the job status is set to `failed`
- **AND** the error message cites the verification failure and suggests manual review

#### Scenario: Content hash is stored for post-export verification
- **WHEN** the export phase completes successfully
- **THEN** the system computes `contentHash: SHA256(output-pdf-bytes)` and stores it in the `RedactedDocument` record
- **AND** subsequent reads can re-verify integrity by comparing hashes

#### Scenario: Redaction details are stored separately from the PDF
- **GIVEN** an exported redacted document
- **WHEN** a user downloads the PDF, they receive only the redacted version (with removed text)
- **AND** the original annotations, reviewer notes, and audit trail remain in the job record and are accessible only to users with appropriate permissions

### Requirement: Replacement text strategy

Redacted regions in the PDF MAY include replacement text (e.g., "[GEREDACTEERD-BSN]") or MAY be left blank, depending on the pattern's `replacement` setting.

#### Scenario: Replacement text is inserted for pattern-specific categories
- **GIVEN** a pattern for BSN with `replacement: "[GEREDACTEERD-BSN]"`
- **WHEN** text is removed and the region is filled, replacement text is added
- **THEN** the output shows "[GEREDACTEERD-BSN]" in the redacted region
- **AND** the redaction is transparent to the reader (clear that redaction occurred)

#### Scenario: Blank redaction for generic custom patterns
- **GIVEN** an annotation added manually with no predefined replacement text
- **WHEN** text is removed
- **THEN** the region is left blank (or filled with a neutral colour like black/gray)
- **AND** no placeholder text is inserted

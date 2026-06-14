---
status: draft
---

# Anonymization — Delta for PDF Output by Default

This delta extends the existing `anonymization` capability so the anonymise endpoint defaults to producing PDF/A-3b output (regardless of input format), via the new `pdf-conversion` capability. An `outputFormat: "preserve"` opt-out keeps the pre-change behaviour available for callers that need native-format output. Conversion failures surface as structured 422 responses with no partial output written to Nextcloud Files.

## ADDED Requirements

### Requirement: The anonymise endpoint MUST accept an optional `outputFormat` field

The anonymise endpoint payload MUST accept an optional top-level `outputFormat` field with allowed values `"pdf"` and `"preserve"`. When omitted, the endpoint MUST use the tenant default (`docudesk.anonymisation.default_output_format`, default `pdf`). Any other value MUST be rejected with HTTP 400.

#### Scenario: Default behaviour produces PDF

- **GIVEN** an anonymise request with no `outputFormat` specified
- **AND** the tenant default is `pdf`
- **WHEN** the endpoint processes the request
- **THEN** the resulting file written to Nextcloud Files is a PDF/A-3b
- **AND** the response indicates success with the file's metadata

#### Scenario: Explicit `outputFormat: "preserve"` keeps native format

- **GIVEN** an anonymise request with `outputFormat: "preserve"`
- **AND** an input DOCX file
- **WHEN** the endpoint processes the request
- **THEN** the resulting file written to Nextcloud Files is a DOCX (the native format)
- **AND** no conversion is attempted

#### Scenario: Invalid value is rejected

- **GIVEN** an anonymise request with `outputFormat: "rtf"`
- **WHEN** the endpoint processes the request
- **THEN** the response is HTTP 400
- **AND** the body cites the allowed values: `"pdf"`, `"preserve"`

#### Scenario: Tenant default `preserve` reverses the default

- **GIVEN** the tenant default is `preserve` (admin override)
- **AND** an anonymise request without `outputFormat`
- **WHEN** the endpoint processes the request
- **THEN** the resulting file is in the native input format

### Requirement: When `outputFormat: "pdf"`, the endpoint MUST invoke `PdfConversionService` after OpenRegister returns

The anonymise pipeline MUST follow this order when the resolved `outputFormat` is `pdf`:

1. Forward the anonymise request to OpenRegister (existing behaviour).
2. Receive the anonymised file in its native format.
3. Pass the file to `PdfConversionService::convertToPdf()`.
4. On success, replace the native-format file in Nextcloud Files with the converted PDF/A-3b file (atomic — the operator never sees both).
5. On failure (the service throws `ConversionFailedException`), see the next requirement.

#### Scenario: Successful conversion replaces the native file with the PDF

- **GIVEN** an anonymise request with `outputFormat: "pdf"` and a DOCX input
- **AND** at least one conversion backend is available and capable
- **WHEN** the request completes
- **THEN** the file at the input's original NC path is replaced with the converted PDF/A-3b
- **AND** the file extension is updated to `.pdf`
- **AND** no DOCX intermediate is left behind

#### Scenario: Native file is not written when conversion is requested but fails

- **GIVEN** an anonymise request with `outputFormat: "pdf"`
- **AND** the conversion will fail (no backend handles the input)
- **WHEN** the request is processed
- **THEN** the native-format intermediate is NOT left in Nextcloud Files
- **AND** the original (pre-anonymisation) file is unchanged
- **AND** the response is HTTP 422 (per the next requirement)

### Requirement: Conversion failure MUST return HTTP 422 with structured body

When `PdfConversionService::convertToPdf()` throws, the anonymise endpoint MUST:

1. Roll back any intermediate state — delete the un-converted anonymised file from Nextcloud Files if it was written.
2. Return HTTP 422 with a JSON body of the documented shape (see scenarios).
3. NOT silently fall back to native-format output (the operator must explicitly opt in via `outputFormat: "preserve"`).

The 422 body MUST include:

```json
{
  "error": "<localised string>",
  "conversionAttempts": [
    {"backend": "<name>", "available": <bool>, "supports": <bool>, "reason": "<string>"}
  ],
  "outputFormat": "pdf",
  "fallback": "<localised hint mentioning outputFormat: 'preserve'>"
}
```

#### Scenario: 422 lists every backend that was tried

- **GIVEN** an anonymise request with `outputFormat: "pdf"` and an input no backend can handle
- **WHEN** the request is processed
- **THEN** the response is HTTP 422
- **AND** `conversionAttempts` lists each backend in cascade order with `{backend, available, supports, reason}`
- **AND** `fallback` mentions `outputFormat: "preserve"` as the explicit escape hatch

#### Scenario: 422 does not happen for `preserve`

- **GIVEN** an anonymise request with `outputFormat: "preserve"`
- **WHEN** the request completes
- **THEN** the response is the existing pre-change shape (HTTP 200, file metadata)
- **AND** no conversion failure can fire (no conversion is attempted)

### Requirement: The change MUST be additive and non-breaking for callers that supply `outputFormat: "preserve"`

Pre-change callers that begin sending `outputFormat: "preserve"` MUST see behaviour identical to the pre-change anonymise endpoint. Existing pre-change clients that do NOT send `outputFormat` MUST observe the new PDF default — this is a deliberate behaviour change documented in the CHANGELOG.

#### Scenario: `preserve` callers see pre-change behaviour

- **GIVEN** a pre-change client that sends `outputFormat: "preserve"` on every call
- **WHEN** the client interacts with the anonymise endpoint
- **THEN** behaviour is identical to before this change
- **AND** the response shape is unchanged

#### Scenario: Pre-change client without `outputFormat` sees PDF default

- **GIVEN** a pre-change client that sends payloads without `outputFormat` and a DOCX input
- **WHEN** the client receives the response
- **THEN** the file written is now PDF (behaviour change)
- **AND** the response remains HTTP 200 with file metadata in the existing shape (the file extension and MIME on the metadata reflect the new PDF type)

### Requirement: Batch anonymise MUST honour `outputFormat` per request

The batch anonymise endpoint (`POST /api/anonymization/batch/{batchId}/anonymize`) MUST accept the same top-level `outputFormat` field. When `pdf`, every file in the batch is converted; if any single file's conversion fails, the operator gets a 422 listing the failed file(s) but the batch's already-converted files remain in NC.

#### Scenario: Batch with mixed-format inputs all converted to PDF

- **GIVEN** a batch with DOCX, PDF, and TXT inputs
- **AND** `outputFormat: "pdf"` (or default)
- **WHEN** the batch anonymise endpoint processes the request
- **THEN** all three files are anonymised AND converted to PDF/A-3b
- **AND** the response indicates per-file outcomes

#### Scenario: Partial-failure batch returns per-file status

- **GIVEN** a batch where one file's conversion fails (e.g. an unsupported XLSX with no Office app installed)
- **WHEN** the batch endpoint processes the request
- **THEN** the response is HTTP 422 (or HTTP 207 multi-status) with per-file outcomes
- **AND** files that converted successfully remain in NC as PDFs
- **AND** the failed file is NOT written to NC in any format

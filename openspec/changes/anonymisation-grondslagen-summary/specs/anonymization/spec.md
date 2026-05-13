---
status: draft
---

# Anonymization — Delta for appendBasisSummary

This delta extends the existing `anonymization` capability so the per-document anonymise endpoint accepts an optional `appendBasisSummary` flag. When set, the controller invokes the rendering subsystem from the `anonymisation-grondslagen-summary` capability after the file has been written. Behaviour for callers that don't set the flag is unchanged.

## ADDED Requirements

### Requirement: The anonymise endpoint MUST accept an optional `appendBasisSummary` flag

The endpoint payload MUST accept an optional top-level boolean field `appendBasisSummary`. When omitted or `false`, behaviour matches pre-change exactly. When `true`, the endpoint MUST invoke the summary-append flow (per the `anonymisation-grondslagen-summary` capability) after the anonymised file has been written to Nextcloud Files.

The flag MUST be honoured for both the per-document anonymise endpoint and the batch anonymise endpoint. In the batch case, the flag applies to every file in the batch.

#### Scenario: appendBasisSummary omitted preserves pre-change behaviour

- **GIVEN** an anonymise request with no `appendBasisSummary` field (or `appendBasisSummary: false`)
- **WHEN** the endpoint processes the request
- **THEN** no summary is rendered
- **AND** no summary PDF is appended or saved
- **AND** the response shape is identical to pre-change

#### Scenario: appendBasisSummary true with PDF output appends a summary page

- **GIVEN** `outputFormat: "pdf"` (or default) and `appendBasisSummary: true`
- **WHEN** the request completes successfully
- **THEN** the resulting file's last page is the rendered summary
- **AND** the file is PDF/A-3b

#### Scenario: appendBasisSummary true with preserve mode produces a separate PDF

- **GIVEN** `outputFormat: "preserve"` and `appendBasisSummary: true` and a non-PDF input
- **WHEN** the request completes
- **THEN** the anonymised native-format file is written normally (per Change A's preserve path)
- **AND** a separate `<original-base>_anonymized_grondslagen.pdf` is written alongside in the same folder
- **AND** the response indicates both files (file metadata for the anonymised file and a `summaryFileId` / `summaryFilePath` reference for the separate summary)

#### Scenario: Batch anonymise honours flag per batch

- **GIVEN** a batch anonymise request with `appendBasisSummary: true`
- **WHEN** the batch completes
- **THEN** every file's anonymised output (or its accompanying summary PDF in preserve mode) carries the rendered summary
- **AND** files in the batch that have no `EntityRelation.bases` data still get a summary page that lists their anonymised entities with the `⟨geen grondslag vastgelegd⟩` placeholder

### Requirement: Summary append failure MUST NOT discard the anonymised file

If the summary rendering or append step throws (e.g. mPDF import error, base resolution timeout), the anonymised file itself MUST be preserved as-is in Nextcloud Files. The endpoint MUST return HTTP 200 with the anonymised file metadata AND a structured warning indicating the summary failed. The operator can re-attempt summary generation later (no API surface for this in v1; it requires re-running the anonymise call or — once available in a follow-up — a standalone summary-render endpoint).

#### Scenario: Append failure surfaces as a warning, not an error

- **GIVEN** an anonymise request with `appendBasisSummary: true`
- **AND** the summary rendering fails internally
- **WHEN** the response is returned
- **THEN** the response is HTTP 200
- **AND** the anonymised file is in Nextcloud Files at its expected path
- **AND** the response body contains a `warning` field describing the summary failure (with a stable error code suitable for the frontend to display)
- **AND** no partial summary PDF is left in NC

### Requirement: The change MUST be additive and non-breaking

Pre-change callers that don't set `appendBasisSummary` MUST see behaviour identical to the pre-change anonymise endpoint. The response shape adds the `warning` field only when a summary failure occurs; pre-change clients that don't read it are unaffected.

#### Scenario: Pre-change client unaffected

- **GIVEN** a pre-change client that doesn't send `appendBasisSummary`
- **WHEN** the client performs an anonymise call
- **THEN** the response shape is unchanged
- **AND** no summary work runs

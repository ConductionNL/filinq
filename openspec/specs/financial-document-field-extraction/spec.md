---
capability: financial-document-field-extraction
status: done
built_by: openspec/changes/archive/2026-07-12-financial-document-field-extraction
---

# financial-document-field-extraction Specification

**Status**: done
**Scope**: filinq
**OpenSpec changes**:
- [financial-document-field-extraction](../../changes/archive/2026-07-12-financial-document-field-extraction/) _(done)_ — structured "scan-en-herken" financial field extraction (supplier/IBAN/KvK/BTW, invoice number, dates, currency, VAT breakdown, line items) from receipts and supplier invoices on top of the existing OCR/enrichment machinery, with per-field confidence, deterministic heuristic extractors, optional NC-Assistant AI enhancement, the canonical `nl.conduction.filinq.extraction.completed` event contract, and a correction-feedback path (kind: code)

## Purpose

Turns the raw text Filinq already obtains from receipts and supplier invoices
(via `ocr-document-scanning`) into the structured financial fields a bookkeeping
consumer needs — supplierName, supplierIban, supplierKvk, supplierVatId,
invoiceNumber, issueDate, dueDate, currency, totalExcl, totalVat, totalIncl,
vatBreakdown[], and lines[] — each with a per-field confidence score.

A deterministic heuristic/regex layer (IBAN mod-97, Dutch KvK, BTW-nummer, date,
and amount patterns, plus totals reconciliation) forms the testable confidence
floor; an optional Nextcloud Assistant text-processing provider refines or fills
low-confidence fields when available, degrading gracefully to heuristics-only
when absent. Results are persisted on a `financialExtraction` object in the
`filinq` register and — when requested — published on the Nextcloud event bus.

This capability is the **canonical home** of the
`nl.conduction.filinq.extraction.completed` event contract (full payload in the
active change's spec delta), consumed by the fleet bookkeeping app shillinq
(`receipt-extraction-consume`, separate repo, event-decoupled per ADR-022). A
correction-feedback endpoint captures human-corrected field values as a future
model-tuning / heuristic-calibration corpus.

The full requirements land via the active change's spec delta at
`openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md`
and are folded into this file on archive.

@e2e exclude backend extraction pipeline and JSON API contract, no browser surface — covered by PHPUnit (tests/unit/Service/FinancialExtractionServiceTest.php, tests/unit/Controller/ExtractionControllerTest.php)

## Requirements
### Requirement: Financial Extraction Endpoint (REQ-FIN-01)

**Priority:** MUST

The system SHALL expose `POST /api/extraction/financial` accepting a JSON body of
`{fileId | documentUri, docType: 'receipt'|'supplier-invoice', callbackEvent: boolean}`. It SHALL
resolve the file text via `ocr-document-scanning` (`OcrService`) — running OCR for image/scanned
input and reusing embedded text for digital-born PDFs — run the extraction pipeline, persist the
result on a `financialExtraction` object in the `filinq` register, and return the extracted
fields with per-field confidence. When `callbackEvent` is `true`, it SHALL also publish
`nl.conduction.filinq.extraction.completed` (REQ-FIN-05).

#### Scenario: Extract a supplier invoice PDF

- **WHEN** `POST /api/extraction/financial` is called with `{documentUri, docType: "supplier-invoice", callbackEvent: true}`
- **THEN** the referenced file text SHALL be obtained via `OcrService` (embedded text reused, OCR only if needed)
- **AND** the extraction pipeline SHALL populate the `fields` object (REQ-FIN-03)
- **AND** a `financialExtraction` object SHALL be persisted in the `filinq` register
- **AND** the response SHALL include `fields`, `fieldConfidence`, and `overallConfidence`

#### Scenario: Extract a receipt photo

- **WHEN** `POST /api/extraction/financial` is called with `{fileId, docType: "receipt", callbackEvent: false}`
- **THEN** the image SHALL be OCR'd via `OcrService` before extraction
- **AND** the result SHALL be persisted and returned
- **AND** no `nl.conduction.filinq.extraction.completed` event SHALL be published (callbackEvent false)

#### Scenario: Neither fileId nor documentUri supplied

- **WHEN** the request body contains neither `fileId` nor `documentUri`
- **THEN** the endpoint SHALL return HTTP 400 with a validation error
- **AND** no extraction object SHALL be persisted

#### Scenario: Invalid docType

- **WHEN** `docType` is a value other than `receipt` or `supplier-invoice`
- **THEN** the endpoint SHALL return HTTP 400
- **AND** no extraction SHALL run

### Requirement: Deterministic Heuristic Extractors (REQ-FIN-02)

**Priority:** MUST

The system SHALL provide side-effect-free, unit-testable heuristic extractors that form the
confidence floor when no AI backend is available: IBAN (validated by the ISO 13616 mod-97
checksum), Dutch KvK number (8 digits), Dutch BTW-nummer / VAT id (`NL` + 9 digits + `B` + 2
check digits), dates (ISO `YYYY-MM-DD` and Dutch `DD-MM-YYYY` / `D MMMM YYYY` forms normalised to
ISO 8601), and monetary amounts (both Dutch `1.234,56` and Anglo `1,234.56` groupings, optional
`€`/`EUR`). Each extractor SHALL be pure: given the same text it returns the same value and
assigns a deterministic confidence.

#### Scenario: IBAN passes mod-97 check

- **WHEN** the text contains `NL91ABNA0417164300` (a checksum-valid IBAN)
- **THEN** `supplierIban` SHALL be extracted as `NL91ABNA0417164300`
- **AND** its field confidence SHALL be high (checksum-validated)

#### Scenario: IBAN fails mod-97 check is rejected

- **WHEN** the text contains a 18-character `NL..` string that fails the mod-97 checksum
- **THEN** it SHALL NOT be returned as `supplierIban`
- **AND** the extractor SHALL prefer no value over an invalid one

#### Scenario: BTW-nummer format recognised

- **WHEN** the text contains `NL001234567B01`
- **THEN** `supplierVatId` SHALL be `NL001234567B01`
- **AND** an 8-digit `KvK: 12345678` token SHALL populate `supplierKvk`

#### Scenario: Dutch amount grouping parsed

- **WHEN** the text contains `Totaal € 1.234,56`
- **THEN** the parsed amount SHALL be the numeric `1234.56`
- **AND** the same value SHALL be produced from the Anglo form `1,234.56`

#### Scenario: Dutch date normalised to ISO 8601

- **WHEN** the text contains `Factuurdatum: 15-03-2024`
- **THEN** `issueDate` SHALL be normalised to `2024-03-15`

### Requirement: Extracted Field Set and Totals Reconciliation (REQ-FIN-03)

**Priority:** MUST

An extraction result SHALL populate the field set: `supplierName`, `supplierIban`, `supplierKvk`,
`supplierVatId`, `invoiceNumber`, `issueDate`, `dueDate`, `currency`, `totalExcl`, `totalVat`,
`totalIncl`, `vatBreakdown[]` (each `{rate, base, amount}`), and `lines[]` (each `{description,
qty, unitPrice, vatRate, amountExcl}`). Any field the pipeline cannot determine SHALL be `null`
(never omitted). When `totalExcl`, `totalVat`, and `totalIncl` are all present, the pipeline SHALL
reconcile them (`totalExcl + totalVat ≈ totalIncl` within a rounding tolerance) and boost the
confidence of the amount fields when they reconcile.

#### Scenario: Full field set is always shaped

- **WHEN** an extraction completes on a receipt lacking a KvK number
- **THEN** the result `fields` object SHALL contain every declared key
- **AND** `supplierKvk` SHALL be `null` rather than absent

#### Scenario: Totals reconcile and boost confidence

- **GIVEN** `totalExcl = 100.00`, `totalVat = 21.00`, `totalIncl = 121.00`
- **WHEN** the totals reconciler runs
- **THEN** the sum SHALL reconcile within tolerance
- **AND** the confidence of `totalExcl`, `totalVat`, and `totalIncl` SHALL be boosted

#### Scenario: Totals do not reconcile

- **GIVEN** `totalExcl = 100.00`, `totalVat = 21.00`, `totalIncl = 130.00`
- **WHEN** the reconciler runs
- **THEN** the amount fields SHALL NOT receive a reconciliation boost
- **AND** their confidence SHALL reflect the heuristic-only signal

#### Scenario: VAT breakdown captured per rate

- **WHEN** an invoice lists a 21% line and a 9% line
- **THEN** `vatBreakdown` SHALL contain two entries with distinct `rate` values
- **AND** each entry SHALL carry its `base` and `amount`

### Requirement: Per-Field and Overall Confidence (REQ-FIN-04)

**Priority:** MUST

Every extracted field SHALL carry a confidence score in `[0..1]` reported in `fieldConfidence`,
and the result SHALL carry an aggregate `overallConfidence` in `[0..1]`. Checksum-validated fields
(IBAN, reconciled totals) SHALL score high; heuristic-only pattern matches SHALL score moderate;
AI-only fills SHALL score by the provider's signal. `overallConfidence` SHALL be the aggregate of
the populated field confidences.

#### Scenario: Confidence is bounded

- **WHEN** any extraction result is produced
- **THEN** every value in `fieldConfidence` SHALL be within `[0..1]`
- **AND** `overallConfidence` SHALL be within `[0..1]`

#### Scenario: Null field has no confidence contribution

- **GIVEN** `dueDate` could not be determined (`null`)
- **WHEN** `overallConfidence` is aggregated
- **THEN** `dueDate` SHALL NOT contribute to the aggregate
- **AND** `fieldConfidence.dueDate` SHALL be `0` or absent

### Requirement: Extraction Completed Event — Canonical Contract (REQ-FIN-05)

**Priority:** MUST

This spec is the **canonical home** of the `nl.conduction.filinq.extraction.completed` event.
When an extraction completes and the request set `callbackEvent: true`, Filinq SHALL dispatch it
on the Nextcloud event bus via `OCP\EventDispatcher\IEventDispatcher`. The payload SHALL be
exactly:

```
{
  "documentUri": "<uri of the source document/file>",
  "requestedBy": "<nextcloud user id that initiated the extraction>",
  "sourceApp": "<app id of the requester, e.g. 'shillinq'>",
  "docType": "receipt" | "supplier-invoice",
  "fields": {
    "supplierName": "<string|null>",
    "supplierIban": "<string|null>",
    "supplierKvk": "<string|null>",
    "supplierVatId": "<string|null>",
    "invoiceNumber": "<string|null>",
    "issueDate": "<ISO 8601 date|null>",
    "dueDate": "<ISO 8601 date|null>",
    "currency": "<ISO 4217 code, e.g. 'EUR'|null>",
    "totalExcl": "<number|null>",
    "totalVat": "<number|null>",
    "totalIncl": "<number|null>",
    "vatBreakdown": [ { "rate": "<number>", "base": "<number>", "amount": "<number>" } ],
    "lines": [ { "description": "<string>", "qty": "<number>", "unitPrice": "<number>", "vatRate": "<number>", "amountExcl": "<number>" } ]
  },
  "fieldConfidence": { "<fieldName>": "<number 0..1>" },
  "overallConfidence": "<number 0..1>"
}
```

Consumer apps (e.g. shillinq `receipt-extraction-consume`) subscribe to this event; the coupling
is event-only per ADR-022 (no direct RPC). The event carries the same `fields` shape returned by
the API so a consumer can act on either the synchronous response or the async event.

#### Scenario: Event dispatched with the canonical payload

- **GIVEN** an extraction requested with `callbackEvent: true` by `sourceApp: "shillinq"`
- **WHEN** the extraction completes
- **THEN** `nl.conduction.filinq.extraction.completed` SHALL be dispatched on the NC event bus
- **AND** its payload SHALL carry `documentUri`, `requestedBy`, `sourceApp`, `docType`, `fields`, `fieldConfidence`, and `overallConfidence`
- **AND** the `fields` shape SHALL match the field set of REQ-FIN-03

#### Scenario: No event when callback not requested

- **GIVEN** an extraction requested with `callbackEvent: false`
- **WHEN** the extraction completes
- **THEN** the result SHALL still be persisted on the `financialExtraction` object
- **AND** no `nl.conduction.filinq.extraction.completed` event SHALL be dispatched

#### Scenario: Provenance carried to the consumer

- **WHEN** the event is dispatched
- **THEN** `sourceApp` SHALL equal the requesting app id
- **AND** `requestedBy` SHALL equal the Nextcloud user id that initiated the request
- **AND** a consumer SHALL be able to correlate the result back to its originating request via `documentUri`

### Requirement: Optional AI-Backend Enhancement with Graceful Degradation (REQ-FIN-06)

**Priority:** SHOULD for the AI enhancement itself; the graceful-degradation guarantee MUST hold.

When a Nextcloud Assistant text-processing provider (`OCP\TextProcessing\IManager`, or the newer
TaskProcessing manager where available) is installed, the pipeline SHALL run a structured
extraction task to refine or fill fields the heuristics left `null` or low-confidence. When no
provider is available, the pipeline SHALL return the heuristic-only result WITHOUT error. No field
value SHALL leave the local server for an external cloud service — AI runs only through the local
NC Assistant provider.

#### Scenario: AI provider present refines low-confidence fields

- **GIVEN** a text-processing provider is registered
- **AND** the heuristics left `supplierName` `null`
- **WHEN** the AI enhancement step runs
- **THEN** it MAY populate `supplierName` with a provider-signalled confidence
- **AND** it SHALL NOT overwrite a checksum-validated field (e.g. a mod-97-valid `supplierIban`)

#### Scenario: No AI provider — graceful degradation

- **GIVEN** no text-processing provider is registered
- **WHEN** an extraction runs
- **THEN** the heuristic-only result SHALL be returned
- **AND** no error SHALL be raised
- **AND** `overallConfidence` SHALL reflect the heuristic-only signal

#### Scenario: Local processing guarantee

- **WHEN** the AI enhancement step runs
- **THEN** the request SHALL be dispatched only through the local NC Assistant provider
- **AND** no field value SHALL be sent to an external cloud API

### Requirement: Correction-Feedback Endpoint (REQ-FIN-07)

**Priority:** MUST

The system SHALL expose `POST /api/extraction/{id}/corrections` accepting human-corrected field
values for a prior extraction. Corrections SHALL be stored paired with the original extraction (on
or alongside the `financialExtraction` object) to build a model-tuning / heuristic-calibration
corpus. This change SHALL NOT auto-retrain any model — it only captures the corrections.

#### Scenario: Corrections stored against the original extraction

- **GIVEN** an existing `financialExtraction` object with id `<id>`
- **WHEN** `POST /api/extraction/<id>/corrections` is called with `{fields: {supplierName: "ACME B.V."}}`
- **THEN** the corrected value SHALL be stored paired with the original extracted value for `supplierName`
- **AND** the original extraction result SHALL remain intact (correction is additive, not destructive)

#### Scenario: Correction for an unknown extraction id

- **WHEN** corrections are posted for an id with no `financialExtraction` object
- **THEN** the endpoint SHALL return HTTP 404
- **AND** no correction SHALL be stored

#### Scenario: Corrections are captured, not applied to a model

- **WHEN** a correction is stored
- **THEN** it SHALL be persisted for future tuning
- **AND** no model retraining SHALL be triggered by this change


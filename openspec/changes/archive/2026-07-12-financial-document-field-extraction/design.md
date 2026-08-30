# Design: financial-document-field-extraction

## Context

DocuDesk already owns the two upstream halves of a "scan-en-herken" flow:

- **`ocr-document-scanning`** — `OCA\DocuDesk\Service\OcrService` wraps Tesseract. Real seams:
  `needsOcr(string $mimeType, ?string $existingText)`, `extractTextFromImage(...)`,
  `extractTextFromPdf(...)`, `isTesseractAvailable()`. It already reuses embedded PDF text and only
  OCRs when needed, and it reports a Tesseract mean-confidence.
- **`metadata-enrichment`** — `MetadataService::enhanceMetadata(array $objectData)`,
  `TextAnalysisService` (`detectLanguage`, `extractKeywords`, `countWordOccurrences`),
  `EnrichmentRunner::enrichObject(...)`. These are free-text enrichers; none of them turn text into
  typed financial fields.

What is missing is the layer that maps OCR/extracted text → structured invoice fields. The fleet
bookkeeping app **shillinq** (`receipt-extraction-consume`, separate repo) wants exactly this and
must not re-implement OCR + Dutch-format parsing. Per ADR-022, that parsing belongs in the app that
already owns the OCR machinery (DocuDesk), exposed via an event/API contract — not via direct RPC.

Constraints: ADR-001 (all data via OpenRegister, no custom tables), ADR-008 (Controller → Service →
Mapper), local-processing standard (no external cloud calls), NC event-bus dialect
(`OCP\EventDispatcher\IEventDispatcher`, already imported in `SigningService`), and `info.xml`
config-version-gated register import (`ConfigurationService::importFromApp()` at boot).

## Goals / Non-Goals

**Goals:**
- A `POST /api/extraction/financial` endpoint producing the REQ-FIN-03 field set with per-field
  confidence, reusing `OcrService` for text.
- Deterministic, pure, unit-testable heuristic extractors (IBAN mod-97, KvK, BTW-nummer, dates,
  amounts, totals reconciliation) — the confidence floor.
- Optional AI enhancement via the local NC Assistant provider, absent-safe.
- The **canonical** `nl.conduction.docudesk.extraction.completed` event contract (payload verbatim
  in the spec), dispatched on the NC event bus AND persisted on an OR object.
- A correction-feedback endpoint capturing human corrections as a tuning corpus.

**Non-Goals:**
- No model training/retraining (corrections are captured only).
- No new OCR engine — text acquisition is delegated to `OcrService`.
- No DocuDesk UI — the scan-en-herken screen lives in shillinq.
- No external cloud OCR/AI.
- No change to `ocr-document-scanning` or `metadata-enrichment` requirements (read-only consume).

## Decisions

### D1 — Extraction is an imperative service, not an OR calculation
`FinancialExtractionService` orchestrates OCR + heuristics + optional AI. See the
Declarative-vs-imperative section below. In short: this is external-integration + document-parsing
work triggered by an explicit API call (not a derived field on object write), which ADR-031
explicitly allows as imperative. The *result* is stored on an OR object; the *computation* is a
service. Alternative considered: model each field as an `x-openregister-calculations` expression on
a schema (as `metadata-enrichment` does). Rejected — those calculations fire on object create/update
of an already-shaped object; here the shaping itself is the work, gated on OCR and an optional async
AI task, and initiated by a consumer request. It does not fit the calculation trigger model.

### D2 — Heuristics first, AI second, checksum wins
Pipeline order: (1) obtain text via `OcrService`; (2) run pure heuristic extractors; (3) if a
provider exists, run the AI enhancement to fill `null`/low-confidence fields ONLY; (4) reconcile
totals; (5) aggregate confidence. A checksum-validated field (mod-97 IBAN, reconciled totals) is
never overwritten by an AI guess (REQ-FIN-06). Rationale: determinism and testability floor +
graceful degradation. Alternative (AI-first, heuristics as fallback) rejected — non-deterministic
core, harder to test, and needlessly cloud/provider-dependent for fields a checksum settles.

### D3 — Extractors are pure functions under `lib/Service/Extraction/`
`IbanExtractor`, `KvkExtractor`, `VatIdExtractor`, `DateExtractor`, `AmountExtractor`,
`TotalsReconciler` — each `(string $text) → {value, confidence}` with no I/O. This is the unit-test
seam. Rationale: REQ-FIN-02/03/04 are almost entirely testable without OCR or NC bootstrap.
**ADR-011 check:** before adding BSN/date/amount helpers, reuse OpenRegister `lib/Formats/`
(`BsnFormat.php` etc.) where an equivalent exists; IBAN mod-97 and BTW-nummer parsing are
invoice-specific and not present in OR — documented here as justified new utilities.

### D4 — Event contract mirrors `docudesk-signing-events`
Reuse the proven pattern: `FinancialExtractionCompletedEvent extends OCP\EventDispatcher\Event`,
dispatched via the injected `IEventDispatcher::dispatchTyped()` (already the mechanism in
`SigningService`). The wire name `nl.conduction.docudesk.extraction.completed` is the fleet
cloud-event id the consumer subscribes to. Provenance (`sourceApp`, `requestedBy`) is carried so the
consumer correlates back. Alternative (OR `x-openregister-notifications`) rejected — notifications
target NC users/groups (staff routing, per `docudesk-notifications`), not a cross-app data payload.

### D5 — Result + corrections stored as one OR schema
A single `financialExtraction` schema in the `document` register holds the extraction result and an
appended `corrections[]` array. Rationale: ADR-001 (no custom tables); keeping corrections on the
same object keeps the original/corrected pair co-located for the future tuning corpus. Register
import is version-gated via `info.xml` bump + `ConfigurationService::importFromApp()`.

## Declarative-vs-imperative decision (ADR-031)

**Default is declarative.** Here the computation is justified imperative because it is
(a) **document-parsing / external-integration** work (OCR text acquisition + optional NC Assistant
task) and (b) **request-triggered** via an explicit endpoint, not a derived field computed on object
write. ADR-031 permits imperative services for external integration and document generation/parsing.

What stays declarative:
- **Storage** — the `financialExtraction` schema lives in `lib/Settings/docudesk_register.json` with
  full `required`/`properties`/`hardValidation: true` (OR Adoption Decision 3), and an
  `x-openregister-archival.retention` annotation (financial source documents; see Seed Data note).
- **No ad-hoc field writes bypassing OR** — the service persists via OR `ObjectService`, never a
  custom table.

What is imperative (justified): `FinancialExtractionService` orchestration, the pure extractor
helpers, the optional AI task dispatch, and the event emission.

## Seed Data

Realistic MKB / consultancy-flavour examples for the `financialExtraction` schema (`document`
register). Placeholder UUIDs/keys only.

**Example 1 — supplier invoice (consultancy hosting bill), high confidence:**
```json
{
  "id": "00000000-0000-0000-0000-000000000000",
  "documentUri": "openregister://document/file/00000000-0000-0000-0000-000000000000",
  "docType": "supplier-invoice",
  "requestedBy": "admin",
  "sourceApp": "shillinq",
  "fields": {
    "supplierName": "Hostbaar B.V.",
    "supplierIban": "NL91ABNA0417164300",
    "supplierKvk": "12345678",
    "supplierVatId": "NL001234567B01",
    "invoiceNumber": "2024-0042",
    "issueDate": "2024-03-15",
    "dueDate": "2024-04-14",
    "currency": "EUR",
    "totalExcl": 100.00,
    "totalVat": 21.00,
    "totalIncl": 121.00,
    "vatBreakdown": [ { "rate": 21, "base": 100.00, "amount": 21.00 } ],
    "lines": [ { "description": "Managed hosting maart 2024", "qty": 1, "unitPrice": 100.00, "vatRate": 21, "amountExcl": 100.00 } ]
  },
  "fieldConfidence": { "supplierIban": 0.99, "supplierVatId": 0.95, "totalIncl": 0.97, "supplierName": 0.82, "invoiceNumber": 0.88 },
  "overallConfidence": 0.9,
  "corrections": []
}
```

**Example 2 — receipt photo (lunch, low confidence, later corrected):**
```json
{
  "id": "00000000-0000-0000-0000-000000000000",
  "documentUri": "openregister://document/file/00000000-0000-0000-0000-000000000000",
  "docType": "receipt",
  "requestedBy": "annemarie",
  "sourceApp": "shillinq",
  "fields": {
    "supplierName": null, "supplierIban": null, "supplierKvk": null, "supplierVatId": null,
    "invoiceNumber": null, "issueDate": "2024-03-12", "dueDate": null, "currency": "EUR",
    "totalExcl": null, "totalVat": null, "totalIncl": 18.50,
    "vatBreakdown": [ { "rate": 9, "base": 16.97, "amount": 1.53 } ],
    "lines": []
  },
  "fieldConfidence": { "totalIncl": 0.71, "issueDate": 0.6 },
  "overallConfidence": 0.55,
  "corrections": [
    { "field": "supplierName", "original": null, "corrected": "Lunchroom De Hoek", "correctedBy": "annemarie", "correctedAt": "2024-03-13T09:12:00+00:00" }
  ]
}
```

## Risks / Trade-offs

- **OCR quality drives extraction quality** → heuristics are checksum-guarded (invalid IBAN dropped
  rather than returned); low-confidence fields surface to the consumer for human review.
- **Locale ambiguity in amounts (`1.234` = 1234 or 1.234?)** → decide grouping by the decimal-comma
  vs decimal-point heuristic and prefer the reading that reconciles totals; unreconciled amounts get
  lower confidence.
- **AI provider variance / absence** → strictly optional; heuristics-only path is the guaranteed
  floor and is what CI tests without a provider.
- **PII on receipts (names, IBANs)** → data stays on OR under the app's ACLs; archival retention
  annotation set on the schema; no external transmission (local-processing standard). GDPR Art. 5(2)
  accountability is served by keeping the original + corrections auditable.
- **Consumer coupling** → event-only (ADR-022); a missing consumer is a no-op, the result is still
  persisted and returned synchronously.

## Migration Plan

1. Add the `financialExtraction` schema to `docudesk_register.json`; bump `info.xml` version so
   `ConfigurationService::importFromApp()` re-imports on boot (register-import is version-gated).
2. Ship the service + pure extractors + event + controller; register the route in
   `appinfo/routes.php` with explicit auth attributes.
3. No data migration — additive schema, additive endpoints. Rollback = disable the routes and drop
   the (empty) schema; no existing data shape changes.

## Open Questions

- Exact `x-openregister-archival` selectielijst category for scanned financial source documents —
  ship as an explicit placeholder string pending selectielijst-manager sign-off (same pattern as
  `document-register` REQ-DREG-ALINK-01). Recorded as a DECISION, not a blocker.
- Which provider API to prefer when both `OCP\TextProcessing\IManager` (legacy) and the newer
  TaskProcessing manager are present — prefer TaskProcessing where available, fall back to
  TextProcessing; both absent-safe.

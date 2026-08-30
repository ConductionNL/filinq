---
kind: code
---

# Proposal: financial-document-field-extraction

## Why

DocuDesk already extracts text from receipts and supplier invoices (Tesseract OCR via
`ocr-document-scanning`) and enriches free-text metadata (language, keywords, topic via
`metadata-enrichment`). What it does NOT do is turn that raw text into the **structured
financial fields** a bookkeeping consumer needs — supplier name, IBAN, KvK, BTW-nummer,
invoice number, dates, currency, and the VAT/line breakdown. Fleet bookkeeping app **shillinq**
wants a "scan-en-herken" (scan-and-recognise) flow: photograph a receipt or drop a supplier PDF,
and get back typed fields it can post as a purchase invoice — with per-field confidence so a
human only reviews the low-confidence ones. Today shillinq would have to re-implement OCR and
Dutch-format parsing itself; that logic belongs in DocuDesk, on top of the OCR/enrichment
machinery it already owns.

## What Changes

- **New `POST /api/extraction/financial` endpoint** — accepts `{fileId | documentUri, docType:
  'receipt'|'supplier-invoice', callbackEvent: bool}`, runs extraction, stores the result on the
  document-register object, and (when `callbackEvent: true`) publishes the completion event.
- **Deterministic heuristic/regex baseline extractors** — IBAN (mod-97), Dutch KvK (8-digit),
  BTW-nummer (`NL` + 9 digits + `B` + 2), ISO/Dutch date formats, and amount patterns (`1.234,56`
  vs `1,234.56`), plus totals reconciliation (`totalExcl + totalVat ≈ totalIncl`). These are pure,
  side-effect-free, unit-testable functions — the confidence floor when no AI backend is present.
- **Optional AI-backend enhancement** — when a Nextcloud Assistant / `ITextProcessingManager`
  provider is available, a structured-extraction task refines/fills fields the heuristics left
  low-confidence. Graceful degradation: no provider → heuristics-only result, never an error.
- **Per-field + overall confidence** — every field carries a `0..1` confidence; overall is the
  aggregate. Heuristic hits (checksum-valid IBAN) score high; AI-only guesses score by provider
  signal; reconciled totals boost the amount fields.
- **Canonical event contract** — this spec is the **canonical home** of
  `nl.conduction.docudesk.extraction.completed` (full payload written out in the spec delta).
  Result is BOTH persisted on the register object AND published on the NC event bus.
- **Correction-feedback endpoint** — `POST /api/extraction/{id}/corrections` lets a consumer post
  human-corrected field values; corrections are stored (paired with the original extraction) as a
  future model-tuning / heuristic-calibration corpus. No auto-retrain in this change.

Consumer note: shillinq's `receipt-extraction-consume` change is the read side of the
`nl.conduction.docudesk.extraction.completed` event and the `POST /api/extraction/financial`
request path. It lives in the shillinq repo; no `depends_on` is declared cross-repo.

## Capabilities

### New Capabilities

- `financial-document-field-extraction`: structured financial field extraction (supplier/IBAN/
  KvK/BTW, invoice number, dates, currency, VAT breakdown, line items) from receipts and supplier
  invoices, with per-field confidence, a heuristic baseline, optional AI-backend enhancement, the
  canonical `nl.conduction.docudesk.extraction.completed` event contract, and a correction-feedback
  path.

### Modified Capabilities

<!-- None. This change extends ocr-document-scanning and metadata-enrichment by consuming their
     services (OcrService text, TextAnalysisService heuristics); it does not change their
     requirements. Reads only, per ADR-022. -->

## Impact

- **New code**: `lib/Service/FinancialExtractionService.php` (orchestration), heuristic extractor
  helpers under `lib/Service/Extraction/` (IbanExtractor, KvkExtractor, VatIdExtractor,
  DateExtractor, AmountExtractor, TotalsReconciler), `lib/Event/FinancialExtractionCompletedEvent.php`,
  `lib/Controller/ExtractionController.php`.
- **Consumes (read-only, ADR-022)**: `OcrService` (text from image/PDF), `TextAnalysisService`
  (language/tokenisation seams), OR `ObjectService` (persist result on the document register).
- **New OR schema**: `financialExtraction` in the `document` register (result + corrections corpus).
- **Optional dependency**: `OCP\TextProcessing\IManager` (Nextcloud Assistant) — absent-safe.
- **Cross-app**: publishes `nl.conduction.docudesk.extraction.completed`; consumed by shillinq
  `receipt-extraction-consume` (separate repo, event-decoupled).
- **No external cloud calls** — heuristics local; AI runs through the local NC Assistant provider.

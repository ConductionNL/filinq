---
capability: financial-document-field-extraction
status: in-progress
built_by: openspec/changes/financial-document-field-extraction
---

# financial-document-field-extraction Specification

**Status**: in-progress
**Scope**: docudesk
**OpenSpec changes**:
- [financial-document-field-extraction](../../changes/financial-document-field-extraction/) _(active)_ — structured "scan-en-herken" financial field extraction (supplier/IBAN/KvK/BTW, invoice number, dates, currency, VAT breakdown, line items) from receipts and supplier invoices on top of the existing OCR/enrichment machinery, with per-field confidence, deterministic heuristic extractors, optional NC-Assistant AI enhancement, the canonical `nl.conduction.docudesk.extraction.completed` event contract, and a correction-feedback path (kind: code)

## Purpose

Turns the raw text DocuDesk already obtains from receipts and supplier invoices
(via `ocr-document-scanning`) into the structured financial fields a bookkeeping
consumer needs — supplierName, supplierIban, supplierKvk, supplierVatId,
invoiceNumber, issueDate, dueDate, currency, totalExcl, totalVat, totalIncl,
vatBreakdown[], and lines[] — each with a per-field confidence score.

A deterministic heuristic/regex layer (IBAN mod-97, Dutch KvK, BTW-nummer, date,
and amount patterns, plus totals reconciliation) forms the testable confidence
floor; an optional Nextcloud Assistant text-processing provider refines or fills
low-confidence fields when available, degrading gracefully to heuristics-only
when absent. Results are persisted on a `financialExtraction` object in the
`document` register and — when requested — published on the Nextcloud event bus.

This capability is the **canonical home** of the
`nl.conduction.docudesk.extraction.completed` event contract (full payload in the
active change's spec delta), consumed by the fleet bookkeeping app shillinq
(`receipt-extraction-consume`, separate repo, event-decoupled per ADR-022). A
correction-feedback endpoint captures human-corrected field values as a future
model-tuning / heuristic-calibration corpus.

The full requirements land via the active change's spec delta at
`openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md`
and are folded into this file on archive.

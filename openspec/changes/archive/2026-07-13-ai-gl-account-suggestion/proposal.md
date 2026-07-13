---
kind: code
---

# Proposal: ai-gl-account-suggestion

## Why

Dutch bookkeeping competitors (Yuki "Robot", InformerAI, e-Boekhouden/Moneybird auto-booking)
already go one step further than field extraction: they **predict the grootboekrekening (GL
account)** a scanned document should be booked to, learning from what the tenant booked last
time. DocuDesk ships `financial-document-field-extraction` (the WHAT — supplier, IBAN, VAT,
totals, lines) but stops there; fleet bookkeeping app **shillinq** must currently guess the
booking account itself with no history signal. Per ADR-022, the extraction/AI surface belongs to
DocuDesk (it already owns OCR + the financial field pipeline); this change adds the WHERE-TO-BOOK
on top of it.

## What Changes

- **Deterministic, history-ranked baseline (works with zero AI)** — given a resolved supplier
  identity (KvK > IBAN > normalised name, in that preference order) DocuDesk ranks candidate GL
  account codes by how often the tenant historically booked that supplier to each code, over a
  bounded recency window, with a `confidence` and a human-readable `rationale` (e.g. "booked to
  4300 in 8 of the last 10 invoices from this supplier").
- **Cold-start fallback** — when no booking history exists for a supplier, an admin-editable
  keyword/category → account mapping table (`glAccountMappingRule`, a plain OR schema, opaque
  account codes) supplies a lower-confidence suggestion. No history and no rule match ⇒ an empty,
  honest "no suggestion" result, never a guess.
- **Optional AI enhancement, gracefully degrading** — when a Nextcloud TaskProcessing/
  TextProcessing provider is available, it may re-rank/annotate the deterministic candidates
  (never invent a code outside the candidate set); absent, the deterministic path stands alone
  (mirrors `FinancialExtractionService::applyAiEnhancement`'s absent-safe pattern exactly).
- **Correction feedback loop** — extends the *existing* `POST /api/extraction/{id}/corrections`
  endpoint: a correction whose `fields` map includes `glAccountCode` is (in addition to being
  stored in `corrections[]` as today) recorded as a booking-history data point for that
  extraction's resolved supplier identity, so future suggestions improve. No new endpoint for
  this.
- **New suggestion endpoint** — `POST /api/extraction/{id}/suggest-account` computes and returns
  ranked candidates for a prior extraction (callable independently of extraction timing, e.g.
  re-run after more history accrues), optionally accepting a consumer-supplied
  `candidateAccounts[]` to constrain/seed ranking.
- **Sibling event, not an additive change to the shipped contract** — publishes
  `nl.conduction.docudesk.gl-account.suggested` rather than extending the already-shipped
  `nl.conduction.docudesk.extraction.completed` payload. Justified in design.md; the shillinq
  follow-up (`gl-account-suggestion-consume`, out of scope here) subscribes to the new event.
- **Boundary: DocuDesk never learns a chart of accounts.** Account codes/labels are opaque
  strings the consumer supplies at correction time (or in `candidateAccounts`); DocuDesk only
  counts frequency per `(supplierIdentity, accountCode)` pair. No Dutch RGS chart is hardcoded.

## Capabilities

### New Capabilities

- `ai-gl-account-suggestion`: deterministic history-ranked GL account suggestion for a financial
  extraction, admin-editable cold-start keyword/category mapping rules, optional absent-safe AI
  re-ranking, a correction-feedback loop that extends the existing corrections endpoint, and the
  `nl.conduction.docudesk.gl-account.suggested` event contract.

### Modified Capabilities

<!-- None at the requirement level. This change extends financial-document-field-extraction's
     correction endpoint (REQ-FIN-07) additively — a new recognised key (`glAccountCode`) inside
     the existing `fields` map — without changing that capability's requirements or contract;
     see design.md for why this is additive glue, not a spec-level modification. -->

## Impact

- **New code**: `lib/Service/GlAccountSuggestionService.php` (orchestration), pure ranking helpers
  under `lib/Service/Suggestion/` (`HistoryRanker`, `SupplierIdentityResolver`,
  `CategoryKeywordMapper`), `lib/Event/GlAccountSuggestedEvent.php`,
  `lib/Controller/GlAccountSuggestionController.php` (the new `suggest-account` route), and a
  small addition to `ExtractionController::corrections()` to feed the history store.
- **New OR schemas** in the `document` register: `glAccountBooking` (opaque per-tenant history,
  fed by corrections), `glAccountMappingRule` (admin-editable cold-start rules).
- **Consumes (read-only, ADR-022)**: `FinancialExtractionService` (resolves the source
  extraction/fields for supplier-identity resolution), OR `ObjectService` (history/rule storage).
- **Optional dependency**: `OCP\TaskProcessing\IManager` / `OCP\TextProcessing\IManager` —
  absent-safe, same resolution order as `financial-document-field-extraction`.
- **Cross-app**: publishes `nl.conduction.docudesk.gl-account.suggested`; the shillinq consumer
  (`gl-account-suggestion-consume`) is a named follow-up, not part of this change.
- **No external cloud calls** — ranking is local arithmetic; AI (when used) runs through the
  local NC Assistant provider only.

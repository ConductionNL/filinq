---
capability: ai-gl-account-suggestion
status: in-progress
built_by: openspec/changes/ai-gl-account-suggestion
---

# ai-gl-account-suggestion Specification

**Status**: in-progress
**Scope**: docudesk
**OpenSpec changes**:
- [ai-gl-account-suggestion](../../changes/ai-gl-account-suggestion/) _(in-progress)_ — deterministic, history-ranked GL-account ("grootboekrekening") suggestion for a financial extraction, with an admin-editable cold-start keyword/category fallback, optional absent-safe AI re-ranking, a correction-feedback loop that extends the existing corrections endpoint, and the sibling `nl.conduction.docudesk.gl-account.suggested` event contract (kind: code)

## Purpose

Extends `financial-document-field-extraction` (the WHAT — supplier, IBAN, VAT,
totals, lines) with the WHERE-TO-BOOK: given a resolved supplier identity
(KvK > IBAN > normalised name), ranks candidate GL account codes by what the
tenant historically booked for that supplier, with a confidence and a
human-readable rationale. Falls back to admin-editable keyword/category rules
for suppliers with no booking history yet, and never fabricates a suggestion
when neither history nor a rule matches.

DocuDesk never hardcodes a chart of accounts (e.g. the Dutch RGS schema) —
every account code/label is an opaque string supplied by the consumer, via a
correction, a `candidateAccounts` allow-list, or an admin-authored mapping
rule.

## Follow-up (out of scope here)

shillinq must build `gl-account-suggestion-consume`, subscribing to
`nl.conduction.docudesk.gl-account.suggested` and supplying its own chart of
accounts as the opaque candidate/history data this capability operates on.

See [design.md](../../changes/ai-gl-account-suggestion/design.md) for the
full decision record (identity resolution, ranking algorithm, cold-start
fallback, AI graceful degradation, the sibling-event boundary decision, and
the declarative-vs-imperative rationale).

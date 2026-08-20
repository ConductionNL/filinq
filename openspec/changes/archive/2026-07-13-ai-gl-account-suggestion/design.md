# Design: ai-gl-account-suggestion

## Context

DocuDesk owns `financial-document-field-extraction`: `FinancialExtractionService` resolves text
via `OcrService`, runs pure heuristic extractors, optionally refines low-confidence fields through
a local NC Assistant provider (absent-safe), persists a `financialExtraction` OR object, and
optionally dispatches `nl.conduction.docudesk.extraction.completed`. A `POST
/api/extraction/{id}/corrections` endpoint already lets a consumer post human-corrected field
values, stored additively in `corrections[]` on the same object (never mutating the original
result).

Fleet bookkeeping app **shillinq** wants the next step: given the extracted supplier identity,
suggest which GL account ("grootboekrekening") to book the document to, ranked by what was booked
for that supplier before, with a confidence and a "why" — the market pattern set by Yuki/
InformerAI/Moneybird. Per ADR-022, this parsing/ranking logic belongs in DocuDesk (owns the
extraction pipeline and the correction corpus); the chart of accounts itself belongs to the
consumer.

**Hard constraint driving this design:** DocuDesk must NEVER encode a chart of accounts (e.g. the
Dutch RGS schema). It can store and count opaque strings a consumer gives it, but it must not
interpret, validate, or hardcode what those strings mean.

## Goals / Non-Goals

**Goals:**
- A deterministic, zero-AI, fully unit-testable ranking of candidate GL accounts for a supplier,
  driven by DocuDesk's own booking-history corpus (built from corrections).
- A cold-start fallback (admin-editable keyword/category → account rules) for suppliers with no
  history yet.
- Optional, absent-safe AI re-ranking of the deterministic candidate set.
- A correction-feedback loop that is an *extension* of the existing corrections endpoint, not a
  new endpoint for that purpose.
- A new `nl.conduction.docudesk.gl-account.suggested` event, decoupled from the shipped
  `extraction.completed` contract.

**Non-Goals:**
- No chart-of-accounts modelling, validation, or Dutch RGS data in DocuDesk.
- No model training — corrections are captured as frequency data, not used to train anything.
- No DocuDesk UI — shillinq owns the booking screen.
- No change to `financial-document-field-extraction`'s requirements (REQ-FIN-01..06 untouched);
  REQ-FIN-07's endpoint gains one additionally-recognised field key, which is additive glue (see
  Decision D5), not a requirement change.

## Decisions

### D1 — Supplier identity resolution order: KvK > IBAN > normalised name
A booking is only as good as the key it's grouped by. `SupplierIdentityResolver` (pure function)
picks the first available identity from a `financialExtraction` object's `fields`, in this
preference order: `supplierKvk` (most stable, legally unique) → `supplierIban` (stable, but a
supplier can have several) → a normalised `supplierName` (whitespace/case/`B.V.`-suffix
normalised; least stable — falls back only when neither KvK nor IBAN is known). The resolved
identity plus its `identityType` (`kvk`|`iban`|`name`) is what `glAccountBooking` rows are grouped
by. Rationale: a stable key is what makes "8 of the last 10 invoices from this supplier"
meaningful; falling back gracefully means suggestions still work for receipts with no KvK/IBAN.

### D2 — History ranking is a pure, windowed frequency count
`HistoryRanker::rank(array $bookings, array $candidateAccounts = []): array` takes the (already
identity-filtered) booking history — each `{accountCode, accountLabel, bookedAt}` — takes the most
recent `HISTORY_WINDOW = 10` by `bookedAt`, counts occurrences per `accountCode`, and returns
`confidence = count / windowSize` with `rationale = "Booked to {code} in {count} of the last
{windowSize} invoices from this supplier"`. When `candidateAccounts` is supplied, ranking is
restricted to that set (a consumer can seed/constrain to currently-valid accounts); when omitted,
every historically-seen code is eligible. Sorted descending by confidence, capped to the top 3
(`MAX_SUGGESTIONS`). Pure, no I/O — fully unit-testable with hand-built fixture arrays, no OCR, no
NC bootstrap, no AI. This is the confidence floor the spec (REQ-GLS-02) requires.

### D3 — Cold start: admin-editable keyword/category rules, not a hardcoded chart
`CategoryKeywordMapper::match(string $supplierName, array $rules): ?array` is a second pure
function: given the (already-lowercased) supplier name/description text and an ordered list of
admin-authored rules `{keywords: string[], accountCode, accountLabel, priority}`, it returns the
first rule (sorted by descending `priority`) whose keyword substring-matches, at a fixed lower
confidence (`COLD_START_CONFIDENCE = 0.4`) than any history-backed suggestion, with rationale
`"Keyword '{keyword}' matched mapping rule → {code}"`. Rules live in a new `glAccountMappingRule`
OR schema (plain CRUD via OpenRegister's own generic object endpoints — no bespoke admin UI/
controller needed in this change) so an admin edits them per-tenant without DocuDesk ever shipping
example RGS codes. When no rule matches and no history exists, `GlAccountSuggestionService`
returns `suggestedAccounts: []` — an honest empty result, never a guess (REQ-GLS-03).

### D4 — AI enhancement re-ranks only within the candidate set, absent-safe
Mirrors `FinancialExtractionService::applyAiEnhancement()` exactly: `resolveAiManager()` (same
TaskProcessing→TextProcessing→null resolution, same try/catch-and-log-and-continue), and when a
provider IS available, the AI prompt is given ONLY the deterministic candidate list (codes +
labels + document excerpt) and asked to re-order/annotate it — it may not introduce a code absent
from the candidate set (enforced by filtering the AI's response against `array_column($candidates,
'code')` before merging), and it may not run when the candidate set is empty (no AI is asked to
invent a chart of accounts from nothing). No provider, any AI failure, or an empty candidate set →
the deterministic ranking stands unchanged, no error (REQ-GLS-04).

### D5 — Correction feedback extends the existing endpoint; new suggestion endpoint is separate
`ExtractionController::corrections()` is extended (not replaced): after
`FinancialExtractionService::addCorrection()` returns, if the posted `fields` map contained
`glAccountCode`, the controller additionally calls
`GlAccountSuggestionService::recordBooking($extractionId, $accountCode, $accountLabel,
$correctedBy)`, which resolves the extraction's supplier identity (D1) and appends one
`glAccountBooking` row. This is the "extend the existing corrections endpoint pattern rather than
inventing a new one" the brief requires — no new endpoint for capturing the correction itself.

A genuinely new endpoint, `POST /api/extraction/{id}/suggest-account`, is still needed, because
*producing* a suggestion is a distinct, independently-triggerable operation from posting a
correction — e.g. shillinq may want to re-request a suggestion after ten more corrections have
landed for that supplier, without re-running OCR/extraction. Conflating "post a correction" and
"get a suggestion" into one endpoint would force every correction call to pay for a ranking
computation it may not need, and would prevent a suggestion-only re-query.

### D6 — Sibling event, not an additive extension of `extraction.completed`
Two options were considered for surfacing the suggestion to shillinq:

1. **Extend `nl.conduction.docudesk.extraction.completed` additively** with a new
   `suggestedAccounts` key.
2. **A sibling event**, `nl.conduction.docudesk.gl-account.suggested`, dispatched by the new
   `suggest-account` endpoint.

**Chosen: (2), the sibling event.** Rationale:
- **Timing mismatch.** `extraction.completed` fires once, at extraction time. A GL suggestion is
  frequently *not* ready then — cold-start suppliers have no history and no matching rule, so the
  "suggestion" at extraction time would routinely be empty; the useful suggestion often only
  exists later, after enough corrections have accrued, and needs re-computation independent of any
  extraction event. Bolting a field onto a one-shot event does not fit a value that changes over
  time on its own trigger.
- **Zero risk to the shipped contract.** `financial-document-field-extraction` is already merged
  and its event is the canonical, documented contract shillinq's `receipt-extraction-consume`
  subscribes to today. Even an additive field is a payload-shape change to a live producer;
  emitting a new event with its own name means the existing consumer needs zero changes and can
  never be broken by this work.
- **Looser coupling (ADR-022).** A consumer that only wants extraction data (not GL suggestions)
  should not need to filter fields out of a combined payload; a consumer that wants both
  subscribes to both events.

The follow-up shillinq must build (out of scope here, named explicitly): a
`gl-account-suggestion-consume` change subscribing to `nl.conduction.docudesk.gl-account.suggested`
and (optionally) calling `POST /api/extraction/{id}/suggest-account` directly when it wants a
synchronous re-rank.

## Declarative-vs-imperative decision (ADR-031)

**Default is declarative.** The ranking/matching computation here is justified imperative for the
same reason `financial-document-field-extraction` D1 gives: it is (a) triggered by an explicit,
consumer-initiated request (`suggest-account`, or implicitly via a correction), not a derived field
recomputed on every object write, and (b) involves an optional external-integration step (the local
AI provider call). `x-openregister-calculations` fires on create/update of an already-shaped
object; ranking a variable-length, time-windowed history against admin-edited rules and an optional
AI pass does not fit that trigger model.

What stays declarative:
- **Storage** — both new schemas (`glAccountBooking`, `glAccountMappingRule`) are declared in
  `lib/Settings/docudesk_register.json` with full `required`/`properties`/`hardValidation: true`,
  consistent with `financialExtraction`.
- **Admin editing of `glAccountMappingRule`** — no bespoke controller/UI in this change; an admin
  manages rules via OpenRegister's own generic object CRUD (same as any other OR-backed
  configuration schema), keeping DocuDesk's own code surface to the ranking/orchestration logic
  only.
- **No ad-hoc field writes bypassing OR** — history and rules are persisted only via OR
  `ObjectService`.

What is imperative (justified): `GlAccountSuggestionService` orchestration, `HistoryRanker`,
`SupplierIdentityResolver`, `CategoryKeywordMapper` (all pure), the optional AI re-rank dispatch,
and the `gl-account.suggested` event emission.

## Seed Data

Realistic MKB-flavour examples. Placeholder UUIDs only.

**`glAccountBooking` — history rows fed by corrections (supplier: Hostbaar B.V., KvK-keyed):**
```json
[
  {
    "id": "00000000-0000-0000-0000-000000000000",
    "supplierIdentity": "12345678",
    "identityType": "kvk",
    "accountCode": "4300",
    "accountLabel": "Kantoorkosten",
    "bookedAt": "2024-01-15T09:00:00+00:00",
    "source": "correction",
    "extractionId": "00000000-0000-0000-0000-000000000001",
    "sourceApp": "shillinq"
  },
  {
    "id": "00000000-0000-0000-0000-000000000002",
    "supplierIdentity": "12345678",
    "identityType": "kvk",
    "accountCode": "4300",
    "accountLabel": "Kantoorkosten",
    "bookedAt": "2024-02-15T09:00:00+00:00",
    "source": "correction",
    "extractionId": "00000000-0000-0000-0000-000000000003",
    "sourceApp": "shillinq"
  }
]
```

**`glAccountMappingRule` — admin-edited cold-start rule:**
```json
{
  "id": "00000000-0000-0000-0000-000000000000",
  "keywords": ["hosting", "domeinnaam", "server"],
  "accountCode": "4300",
  "accountLabel": "Kantoorkosten",
  "priority": 10,
  "enabled": true
}
```

**Suggestion response (`POST /api/extraction/{id}/suggest-account`), history-backed:**
```json
{
  "extractionId": "00000000-0000-0000-0000-000000000001",
  "supplierIdentity": "12345678",
  "identityType": "kvk",
  "suggestedAccounts": [
    {
      "code": "4300",
      "label": "Kantoorkosten",
      "confidence": 0.8,
      "rationale": "Booked to 4300 in 8 of the last 10 invoices from this supplier"
    }
  ],
  "source": "history"
}
```

**Suggestion response, cold start (no history, rule match):**
```json
{
  "extractionId": "00000000-0000-0000-0000-000000000004",
  "supplierIdentity": "lunchroom de hoek",
  "identityType": "name",
  "suggestedAccounts": [
    {
      "code": "4400",
      "label": "Representatiekosten",
      "confidence": 0.4,
      "rationale": "Keyword 'lunch' matched mapping rule → 4400"
    }
  ],
  "source": "keyword-rule"
}
```

## Risks / Trade-offs

- **Sparse history for new/rare suppliers** → cold-start rules fill the gap; an honest empty
  result is always preferred over a fabricated one (no rule, no history ⇒ `[]`).
- **Identity drift (supplier renames, changes IBAN)** → KvK-first resolution minimizes this; a
  name-only fallback can fragment history across name variants, which is a known, documented
  limitation, not silently hidden.
- **Consumer-supplied `candidateAccounts` narrows ranking** → if a consumer passes a stale/wrong
  candidate set, a well-supported historical code could be excluded; this is a consumer input
  validation concern, not something DocuDesk arbitrates (it does not know the true chart).
  Omitting `candidateAccounts` avoids the risk entirely.
- **AI re-rank could reorder confidently-wrong** → constrained to never introduce a code outside
  the deterministic candidate set, and any failure/absence falls back to the deterministic order
  unchanged.
- **Event proliferation** → accepted trade-off for D6; documented explicitly rather than silently
  bolted onto a shipped contract.

## Migration Plan

1. Add `glAccountBooking` and `glAccountMappingRule` schemas to `docudesk_register.json`'s
   `document` register; bump `info.xml`/register version so
   `ConfigurationService::importFromApp()` re-imports on boot (version-gated, same as
   `financialExtraction`).
2. Ship `GlAccountSuggestionService` + pure helpers + event + the new controller/route; extend
   `ExtractionController::corrections()` with the `glAccountCode` recognition.
3. No data migration — fully additive schemas/endpoints/event. Rollback = disable the new route,
   drop the (empty) schemas; no existing data shape changes.

## Open Questions

- Whether `HISTORY_WINDOW` (10) and `MAX_SUGGESTIONS` (3) should become tenant-configurable — kept
  as fixed constants in this change; recorded as a DECISION, not a blocker, revisit if shillinq
  needs tuning per-tenant.

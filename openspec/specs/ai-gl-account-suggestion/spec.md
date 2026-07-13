---
capability: ai-gl-account-suggestion
status: done
built_by: openspec/changes/archive/2026-07-13-ai-gl-account-suggestion
---

# ai-gl-account-suggestion Specification

**Status**: done
**Scope**: docudesk
**OpenSpec changes**:
- [ai-gl-account-suggestion](../../changes/archive/2026-07-13-ai-gl-account-suggestion/) _(done)_ — deterministic, history-ranked GL-account ("grootboekrekening") suggestion for a financial extraction, with an admin-editable cold-start keyword/category fallback, optional absent-safe AI re-ranking, a correction-feedback loop that extends the existing corrections endpoint, and the sibling `nl.conduction.docudesk.gl-account.suggested` event contract (kind: code)

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

See [design.md](../../changes/archive/2026-07-13-ai-gl-account-suggestion/design.md) for the
full decision record (identity resolution, ranking algorithm, cold-start
fallback, AI graceful degradation, the sibling-event boundary decision, and
the declarative-vs-imperative rationale).
## Requirements
### Requirement: Supplier Identity Resolution (REQ-GLS-01)

**Priority:** MUST

Given a `financialExtraction` object's `fields`, the system SHALL resolve a single supplier
identity key used to group booking history, preferring `supplierKvk` first, then `supplierIban`,
then a normalised `supplierName` (trimmed, case-folded, whitespace-collapsed). The resolution
SHALL also report which identity type was used (`kvk`|`iban`|`name`). When none of the three
fields are populated, the system SHALL report no resolvable identity rather than fabricate one.

#### Scenario: KvK preferred over IBAN and name

- **WHEN** an extraction's fields carry `supplierKvk`, `supplierIban`, and `supplierName` all populated
- **THEN** the resolved identity SHALL be the KvK value
- **AND** `identityType` SHALL be `kvk`

#### Scenario: IBAN used when KvK absent

- **WHEN** `supplierKvk` is `null` but `supplierIban` is populated
- **THEN** the resolved identity SHALL be the IBAN value
- **AND** `identityType` SHALL be `iban`

#### Scenario: Normalised name as last resort

- **WHEN** both `supplierKvk` and `supplierIban` are `null` but `supplierName` is `"  Lunchroom De Hoek  "`
- **THEN** the resolved identity SHALL be the normalised form `"lunchroom de hoek"`
- **AND** `identityType` SHALL be `name`

#### Scenario: No resolvable identity

- **WHEN** `supplierKvk`, `supplierIban`, and `supplierName` are all `null`
- **THEN** the system SHALL report no resolvable identity
- **AND** SHALL NOT attempt to rank against a fabricated key

### Requirement: History-Ranked Baseline Suggestion (REQ-GLS-02)

**Priority:** MUST

Given a resolved supplier identity and its `glAccountBooking` history, the system SHALL rank
candidate GL account codes by frequency over the most recent 10 bookings (or fewer when history is
shorter), producing for each candidate a `confidence` (`occurrences / windowSize`) and a
human-readable `rationale` naming the code, the occurrence count, and the window size. This
ranking SHALL be computable as a pure function with no I/O, network access, or AI dependency — it
is the confidence floor. Results SHALL be sorted by descending confidence and capped to the 3
highest-ranked candidates.

#### Scenario: Dominant account ranked first with rationale

- **GIVEN** the last 10 bookings for a supplier include account `4300` eight times and account
  `4200` twice
- **WHEN** the history ranker runs
- **THEN** `4300` SHALL be the top-ranked candidate with `confidence = 0.8`
- **AND** its rationale SHALL state it was booked to `4300` in `8` of the last `10` invoices from
  this supplier

#### Scenario: Fewer than 10 bookings uses the available window

- **GIVEN** a supplier has only 3 recorded bookings, all to account `5100`
- **WHEN** the history ranker runs
- **THEN** `5100` SHALL be ranked with `confidence = 1.0`
- **AND** the rationale SHALL reference a window size of `3`, not `10`

#### Scenario: No history yields no history-based candidates

- **GIVEN** a supplier identity with zero `glAccountBooking` rows
- **WHEN** the history ranker runs
- **THEN** it SHALL return an empty candidate list
- **AND** SHALL NOT raise an error

#### Scenario: Consumer-supplied candidate set constrains ranking

- **GIVEN** history includes bookings to accounts `4300` and `9999`
- **AND** the request supplies `candidateAccounts` containing only `4300`
- **WHEN** the history ranker runs
- **THEN** only `4300` SHALL appear in the ranked result
- **AND** `9999` SHALL be excluded even though it has history

### Requirement: Cold-Start Keyword/Category Mapping Fallback (REQ-GLS-03)

**Priority:** MUST

When a resolved supplier identity has no booking history, the system SHALL consult an
admin-editable, per-tenant `glAccountMappingRule` table: an ordered list of `{keywords[],
accountCode, accountLabel, priority}` rules. The first rule (by descending `priority`) whose
keyword substring-matches the supplier name or document text SHALL produce a single suggestion at
a fixed, lower confidence than any history-backed suggestion, with a rationale naming the matched
keyword and the target account. DocuDesk SHALL NOT ship any pre-populated chart-of-accounts data —
the rule table starts empty and is populated by the tenant admin. When no history AND no rule
match exist, the system SHALL return an empty `suggestedAccounts` list rather than guess.

#### Scenario: Keyword rule matches on cold start

- **GIVEN** no booking history exists for a supplier named "Lunchroom De Hoek"
- **AND** an enabled rule `{keywords: ["lunch"], accountCode: "4400", accountLabel: "Representatiekosten"}` exists
- **WHEN** a suggestion is requested
- **THEN** the result SHALL contain one suggestion for account `4400`
- **AND** its confidence SHALL be lower than any history-backed suggestion's confidence
- **AND** the rationale SHALL name the matched keyword and the rule's target account

#### Scenario: Higher-priority rule wins over a lower-priority match

- **GIVEN** two enabled rules both match the same document text, with priorities `10` and `5`
- **WHEN** a suggestion is requested
- **THEN** the rule with priority `10` SHALL be the one applied

#### Scenario: No history and no rule match returns an honest empty result

- **GIVEN** no booking history and no `glAccountMappingRule` matches the supplier/text
- **WHEN** a suggestion is requested
- **THEN** `suggestedAccounts` SHALL be an empty list
- **AND** no fabricated suggestion SHALL be returned

### Requirement: Optional AI Re-Ranking with Graceful Degradation (REQ-GLS-04)

**Priority:** SHOULD for the AI step itself; the graceful-degradation guarantee MUST hold.

When a Nextcloud Assistant text-processing provider (`OCP\TaskProcessing\IManager`, falling back to
`OCP\TextProcessing\IManager`) is available and the deterministic candidate set is non-empty, the
system MAY invoke it to re-order or annotate the existing candidates. The AI step SHALL NOT
introduce any account code absent from the deterministic candidate set. When no provider is
available, the candidate set is empty, or the AI call fails for any reason, the system SHALL return
the deterministic ranking unchanged, without error.

#### Scenario: AI re-ranks within the existing candidate set

- **GIVEN** a text-processing provider is registered
- **AND** the deterministic ranking produced candidates `4300` and `4200`
- **WHEN** the AI re-rank step runs
- **THEN** it MAY reorder `4300` and `4200`
- **AND** it SHALL NOT add any account code not already in the candidate set

#### Scenario: No AI provider — graceful degradation

- **GIVEN** no text-processing provider is registered
- **WHEN** a suggestion is requested
- **THEN** the deterministic ranking SHALL be returned unchanged
- **AND** no error SHALL be raised

#### Scenario: AI step never runs on an empty candidate set

- **GIVEN** the deterministic ranking (history + cold-start) produced no candidates
- **WHEN** a suggestion is requested
- **THEN** the AI re-rank step SHALL NOT be invoked
- **AND** the result SHALL remain an empty `suggestedAccounts` list

### Requirement: Correction Feedback Extends the Existing Corrections Endpoint (REQ-GLS-05)

**Priority:** MUST

The system SHALL NOT introduce a new endpoint to capture booking corrections. Instead, the
existing `POST /api/extraction/{id}/corrections` endpoint (financial-document-field-extraction,
REQ-FIN-07) SHALL additionally recognise a `glAccountCode` key inside the posted `fields` map:
when present, alongside being stored in `corrections[]` as today, the system SHALL resolve the
extraction's supplier identity (REQ-GLS-01) and append one `glAccountBooking` history row so
future suggestions reflect it. An optional `glAccountLabel` key, when present, SHALL be stored
alongside the code.

#### Scenario: Correction with a GL account feeds history

- **GIVEN** an existing `financialExtraction` object with a resolvable supplier identity
- **WHEN** `POST /api/extraction/{id}/corrections` is called with `{fields: {glAccountCode: "4300", glAccountLabel: "Kantoorkosten"}}`
- **THEN** the correction SHALL be stored in `corrections[]` as before (REQ-FIN-07)
- **AND** a new `glAccountBooking` row SHALL be recorded for the resolved supplier identity with `accountCode: "4300"`

#### Scenario: Correction without a GL account does not touch history

- **WHEN** `POST /api/extraction/{id}/corrections` is called with `{fields: {supplierName: "ACME B.V."}}` (no `glAccountCode`)
- **THEN** the correction SHALL be stored as before
- **AND** no `glAccountBooking` row SHALL be created

#### Scenario: Correction with an unresolvable supplier identity does not crash

- **GIVEN** the extraction's fields carry no `supplierKvk`, `supplierIban`, or `supplierName`
- **WHEN** a correction with `glAccountCode` is posted
- **THEN** the correction SHALL still be stored in `corrections[]`
- **AND** no `glAccountBooking` row SHALL be created (no key to group it by)
- **AND** no error SHALL be raised

### Requirement: Suggestion Endpoint and Sibling Event Contract (REQ-GLS-06)

**Priority:** MUST

The system SHALL expose `POST /api/extraction/{id}/suggest-account`, accepting an optional
`candidateAccounts: [{code, label}]` array, returning `{extractionId, supplierIdentity,
identityType, suggestedAccounts: [{code, label, confidence, rationale}], source}` where `source` is
`history`, `keyword-rule`, or `none`. On success, the system SHALL dispatch
`nl.conduction.docudesk.gl-account.suggested` on the Nextcloud event bus via
`OCP\EventDispatcher\IEventDispatcher`, carrying the same fields as the response plus `sourceApp`
and `requestedBy`. This is a **sibling** event to `nl.conduction.docudesk.extraction.completed` —
it SHALL NOT be merged into that event's payload, and this change SHALL NOT modify the
`extraction.completed` contract in any way.

#### Scenario: Suggestion for an unknown extraction id

- **WHEN** `POST /api/extraction/{id}/suggest-account` is called for an id with no
  `financialExtraction` object
- **THEN** the endpoint SHALL return HTTP 404
- **AND** no event SHALL be dispatched

#### Scenario: Sibling event carries the suggestion payload

- **GIVEN** a valid extraction id with a history-backed suggestion
- **WHEN** the suggestion endpoint completes successfully
- **THEN** `nl.conduction.docudesk.gl-account.suggested` SHALL be dispatched
- **AND** its payload SHALL carry `extractionId`, `supplierIdentity`, `identityType`,
  `suggestedAccounts`, `source`, `sourceApp`, and `requestedBy`

#### Scenario: extraction.completed contract is untouched

- **WHEN** a financial extraction completes (financial-document-field-extraction, REQ-FIN-05)
- **THEN** its `nl.conduction.docudesk.extraction.completed` payload SHALL remain exactly the
  shape defined in that spec, with no `suggestedAccounts` key added

### Requirement: No Hardcoded Chart of Accounts (REQ-GLS-07)

**Priority:** MUST

DocuDesk SHALL treat every GL account code and label as an opaque string supplied by the consumer
(via a correction's `glAccountCode`/`glAccountLabel`, a `candidateAccounts` entry, or an admin-
authored `glAccountMappingRule`). DocuDesk SHALL NOT ship, seed, or hardcode any Dutch RGS or other
chart-of-accounts data, and SHALL NOT validate account codes against any built-in chart — it only
counts and matches the strings it is given.

#### Scenario: Unrecognised-looking account code is still counted

- **WHEN** a correction posts `glAccountCode: "CUSTOM-XYZ-001"` (not a valid RGS code)
- **THEN** the system SHALL record it as a `glAccountBooking` row exactly as given
- **AND** SHALL NOT reject it for failing to match a chart-of-accounts format

#### Scenario: No built-in mapping rules ship with the app

- **WHEN** DocuDesk is freshly installed with no admin-authored `glAccountMappingRule` objects
- **THEN** the cold-start fallback (REQ-GLS-03) SHALL have zero rules to match against
- **AND** SHALL correctly fall through to an empty `suggestedAccounts` result


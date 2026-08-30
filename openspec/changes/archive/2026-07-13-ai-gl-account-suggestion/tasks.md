## 1. Schemas (declarative, OR)

- [x] 1.1 Add `glAccountBooking` schema (opaque supplier-identity/accountCode/label/bookedAt/source/extractionId/sourceApp) to the `document` register in `docudesk_register.json`; bump register + `info.xml` version
- [x] 1.2 Add `glAccountMappingRule` schema (keywords[]/accountCode/accountLabel/priority/enabled) to the same register, no seeded rows

## 2. Pure ranking helpers (lib/Service/Suggestion/)

- [x] 2.1 `SupplierIdentityResolver` — KvK > IBAN > normalised-name resolution (REQ-GLS-01), unit tests incl. no-identity case
- [x] 2.2 `HistoryRanker` — windowed (last 10) frequency ranking + confidence + rationale + candidateAccounts constraint + top-3 cap (REQ-GLS-02), unit tests incl. short-window and empty-history cases
- [x] 2.3 `CategoryKeywordMapper` — priority-ordered keyword match, fixed lower confidence, empty-rules case (REQ-GLS-03), unit tests

## 3. Orchestration service

- [x] 3.1 `GlAccountSuggestionService::suggest()` — resolve identity, load history via OR `ObjectService::searchObjects`, rank, fall back to keyword rules, shape `{extractionId, supplierIdentity, identityType, suggestedAccounts, source}` (REQ-GLS-02/03)
- [x] 3.2 `GlAccountSuggestionService::recordBooking()` — resolve identity from a `financialExtraction` id, persist one `glAccountBooking` row, absent-identity no-op (REQ-GLS-05)
- [x] 3.3 Optional AI re-rank step mirroring `FinancialExtractionService::applyAiEnhancement()`'s absent-safe TaskProcessing→TextProcessing→null resolution, filtered to the existing candidate codes only, never run on an empty candidate set (REQ-GLS-04)
- [x] 3.4 Unit tests for the orchestration service: history path, cold-start path, empty-result path, AI-present/absent/failure paths, recordBooking with/without resolvable identity

## 4. Event + controller + routes

- [x] 4.1 `GlAccountSuggestedEvent` (sibling event, immutable, `toPayload()`) + unit test (REQ-GLS-06)
- [x] 4.2 `GlAccountSuggestionController::suggestAccount()` — `POST /api/extraction/{id}/suggest-account`, 404 on unknown id, dispatches the sibling event on success; route registered with explicit `@NoAdminRequired`/`@NoCSRFRequired` docblock tags (matching `ExtractionController`'s existing convention)
- [x] 4.3 Extend `ExtractionController::corrections()` to call `GlAccountSuggestionService::recordBooking()` when `fields.glAccountCode` is present, without altering REQ-FIN-07's existing response/behaviour
- [x] 4.4 Controller unit tests: suggestion success, 404, corrections-with-glAccountCode delegates to recordBooking, corrections-without-glAccountCode does not

## 5. Verification

- [x] 5.1 `php -l` every changed/new PHP file
- [x] 5.2 Run `vendor/bin/phpunit -c phpunit-unit.xml` in a PHP 8.3 container; compare against the recorded baseline, zero new failures
- [x] 5.3 Run `vendor/bin/phpcs` + `vendor/bin/phpstan analyse` on changed paths; fix findings in new code
- [x] 5.4 Confirm `financial-document-field-extraction`'s `extraction.completed` payload/tests are unmodified (REQ-GLS-06 boundary)

## 6. Docs

- [x] 6.1 Create `openspec/specs/ai-gl-account-suggestion/spec.md` (status: in-progress), linking this change and naming the `gl-account-suggestion-consume` shillinq follow-up explicitly (no `openspec/ROADMAP.md` exists in docudesk to update)

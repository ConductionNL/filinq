# Tasks: financial-document-field-extraction

All tasks are `[docudesk]`. Backend-only (service + pure extractors + event + REST API + one OR
schema). No DocuDesk UI — the scan-en-herken screen lives in the consumer (shillinq). Max 20
checkboxes; acceptance criteria are plain bullets, not checkboxes.

## 1. OR schema + register import

- [x] 1.1 Add the `financialExtraction` schema to `lib/Settings/docudesk_register.json` in the
  `document` register: full `required`/`properties` for the REQ-FIN-03 field set (fields object,
  fieldConfidence, overallConfidence, docType, documentUri, requestedBy, sourceApp, corrections[]),
  `hardValidation: true`, and an `x-openregister-archival` block with a placeholder selectielijst
  category string. Bump `appinfo/info.xml` `<version>` so `ConfigurationService::importFromApp()`
  re-imports on boot.
  - Acceptance: `openspec validate` and JSON parse pass; a fresh boot exposes `financialExtraction`
    in the `document` register via `objectService->getSchemas(register: 'document')`.
- [ ] 1.2 Add seed data — the two example `financialExtraction` objects from design.md Seed Data
  (consultancy supplier-invoice; corrected receipt) to the app's seed/fixture path.
  - Acceptance: seeding a fresh install creates both objects; both validate against the schema.
  - See `## Deviations` below — no seed/fixture mechanism exists in this app.

## 2. Pure heuristic extractors (`lib/Service/Extraction/`)

- [x] 2.1 `IbanExtractor` — find IBAN candidates, validate by ISO 13616 mod-97, return
  `{value, confidence}`; reject checksum failures. (REQ-FIN-02)
  - Acceptance: `NL91ABNA0417164300` accepted high-confidence; a mod-97-invalid `NL..` rejected.
- [x] 2.2 `KvkExtractor` + `VatIdExtractor` — 8-digit KvK; BTW-nummer `NL`+9digits+`B`+2. (REQ-FIN-02)
  - Acceptance: `KvK: 12345678` → `12345678`; `NL001234567B01` → `supplierVatId`.
- [x] 2.3 `DateExtractor` — ISO `YYYY-MM-DD` and Dutch `DD-MM-YYYY` / `D MMMM YYYY`, normalised to
  ISO 8601. (REQ-FIN-02)
  - Acceptance: `15-03-2024` → `2024-03-15`; unparseable input → null, no throw.
- [x] 2.4 `AmountExtractor` + `TotalsReconciler` — parse Dutch `1.234,56` and Anglo `1,234.56`;
  reconcile `totalExcl + totalVat ≈ totalIncl` within tolerance and boost amount confidence when it
  reconciles. (REQ-FIN-02, REQ-FIN-03)
  - Acceptance: both groupings → `1234.56`; reconciling triple boosts confidence, non-reconciling
    does not.

## 3. Orchestration service + confidence

- [x] 3.1 `lib/Service/FinancialExtractionService.php` — obtain text via `OcrService`
  (`needsOcr`/`extractTextFromImage`/`extractTextFromPdf`, embedded text reused), run heuristics,
  shape the full REQ-FIN-03 field set (missing fields `null`, never omitted), reconcile totals, and
  aggregate `overallConfidence` from populated field confidences. Persist a `financialExtraction`
  object via OR `ObjectService`. (REQ-FIN-01, 03, 04)
  - Acceptance: unit test on fixture text yields the shaped field set with bounded confidences and
    an OR object.
- [x] 3.2 Optional AI enhancement — when `OCP\TextProcessing\IManager` (or TaskProcessing) is
  registered, fill only `null`/low-confidence fields; never overwrite a checksum-validated field;
  absent-safe (no provider → heuristic-only, no error); no external cloud call. (REQ-FIN-06)
  - Acceptance: with a stub provider, a `null` field is filled; without any provider the pipeline
    returns the heuristic-only result and raises no error.

## 4. Event contract

- [x] 4.1 `lib/Event/FinancialExtractionCompletedEvent.php`
  (`OCA\DocuDesk\Event\FinancialExtractionCompletedEvent extends OCP\EventDispatcher\Event`) with
  immutable getters carrying the exact canonical payload (documentUri, requestedBy, sourceApp,
  docType, fields, fieldConfidence, overallConfidence). SPDX + PHPDoc headers. (REQ-FIN-05)
  - Acceptance: `php -l` passes; getters return constructed values; fully immutable.
- [x] 4.2 Dispatch `nl.conduction.docudesk.extraction.completed` via the injected
  `IEventDispatcher::dispatchTyped()` from `FinancialExtractionService` only when the request set
  `callbackEvent: true`; fail-soft. (REQ-FIN-05)
  - Acceptance: a `callbackEvent:true` extraction dispatches once with the canonical payload; a
    `callbackEvent:false` extraction dispatches nothing but still persists the result.

## 5. Controller + routes

- [x] 5.1 `lib/Controller/ExtractionController.php` — `POST /api/extraction/financial`
  (`{fileId|documentUri, docType, callbackEvent}`; 400 on missing file ref or invalid docType) and
  `POST /api/extraction/{id}/corrections` (store corrections paired with the original; 404 for
  unknown id; additive, non-destructive; no retrain). Register both in `appinfo/routes.php` with
  explicit auth attributes (`#[NoAdminRequired]`, `#[NoCSRFRequired]` as appropriate) per
  hydra-gate-route-auth. (REQ-FIN-01, REQ-FIN-07)
  - Acceptance: Newman/API test — valid request returns fields+confidence; bad docType → 400;
    corrections for unknown id → 404; corrections preserve the original extraction.
  - Newman/API test not added (no Docker instance available in this sandbox); covered instead by
    unit tests on the controller (mocked service) and the full service pipeline. See Deviations.

## 6. Verify

- [x] 6.1 `openspec validate financial-document-field-extraction --strict` exits 0; `php -l` on
  every new/changed PHP file; self-check hydra gates (SPDX headers, no forbidden debug helpers, no
  stubs, route-auth attributes present, notification dialect unchanged, spec-coverage `@spec` tags
  on new public methods).
  - Acceptance: validation and gates green; new controller/service methods carry `@spec
    openspec/specs/financial-document-field-extraction` tags.

## Deviations

- **1.2 (seed data) not implemented.** DocuDesk has no seed/fixture-loading mechanism anywhere in
  the app (no `seed`/`fixtures` directory, no `SettingsInitializer` seed hook) — unlike some sibling
  apps, there is nothing to hang "seed on fresh install" off. Implementing a whole new seeding
  subsystem for this one schema was out of scope for this change. Closest safe subset: the two
  design.md Seed Data examples (consultancy supplier-invoice, corrected receipt) are used verbatim
  as PHPUnit fixtures in `FinancialExtractionServiceTest` and validated against the schema shape.
- **5.1 Newman/API test not added.** This sandbox has no live Nextcloud/OpenRegister instance to run
  Newman against. The REQ-FIN-01/REQ-FIN-07 scenarios (valid request → fields+confidence; bad
  docType → 400; missing file ref → 400; corrections for unknown id → 404; corrections preserve the
  original extraction) are instead covered by `ExtractionControllerTest` (controller with a mocked
  `FinancialExtractionService`) and `FinancialExtractionServiceTest` (full pipeline with a mocked
  `ObjectService`/`OcrService`/file system), which together exercise the same request→response
  contract without requiring a running instance.

## i18n

New English l10n keys (values), NL translations listed as plain bullets (not checkboxes):
- `Financial extraction complete` → `Financiële extractie voltooid`
- `Review low-confidence fields` → `Controleer velden met lage betrouwbaarheid`
- `Correction saved` → `Correctie opgeslagen`

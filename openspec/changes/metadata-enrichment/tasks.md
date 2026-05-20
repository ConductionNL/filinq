## Tasks

- [x] 1. **Deduplication check** — search `openspec/specs/` and `openregister/lib/Service/` for overlap with ObjectService, TextExtractionService, and any existing language/keyword services. Document findings: OpenRegister's `TextExtractionService` extracts text from raw files (PDFs/Office docs); this change extracts text from already-ingested object fields — no overlap. No existing language detection or keyword extraction in the platform. `getObjectService()` pattern duplicated across MetadataService, ConsentService, ObjectionDeadlineChecker — documented in META-070/071 as follow-up consolidation.

- [x] 2. **DocumentTextExtractor** — implement `lib/Service/DocumentTextExtractor.php`:
  - `extractText(array $objectData): string` — returns content → text → description (first non-empty), empty string if none.
  - `normalizeDates(array $objectData): array` — iterates fields [created, modified, date, creationDate, modificationDate]; parses each with `\DateTime::createFromFormat` / `strtotime`; writes ISO 8601 (`c`) on success; logs debug and skips on parse failure; returns mutated array.
  - Add `@spec openspec/changes/metadata-enrichment/tasks.md#task-2` to file and method docblocks.

- [x] 3. **LanguageClassifier** — implement `lib/Service/LanguageClassifier.php`:
  - `detectLanguage(string $text): ?string` — word-frequency analysis against 10 Dutch and 10 English stop/common words; threshold >5 matches; Dutch wins ties; returns "nl", "en", or null.
  - `classifyTopic(string $text): ?string` — keyword vocabulary scoring for legal, financial, medical, technical (see spec vocabularies); returns topic with highest score or null if all scores are 0.
  - Add `@spec openspec/changes/metadata-enrichment/tasks.md#task-3` to docblocks.

- [x] 4. **TextAnalysisService** — implement `lib/Service/TextAnalysisService.php`:
  - `extractKeywords(string $text): array` — tokenize, remove Dutch+English stop words, count frequencies, sort descending, return top 10.
  - `standardizeDocumentType(string $type): string` — map doc/docx→word, xls/xlsx/excel→spreadsheet, ppt/pptx→presentation, htm/html→html; pass unknowns through unchanged; canonical set: pdf, word, spreadsheet, presentation, text, html, image.
  - Add `@spec openspec/changes/metadata-enrichment/tasks.md#task-4` to docblocks.

- [x] 5. **MetadataService** — implement `lib/Service/MetadataService.php`:
  - `enhance(array $objectData): array` — orchestrates the enrichment pipeline: extract text → language (skip if set) → keywords (skip if set) → topic (skip if set) → type standardization → date normalization; returns enriched array.
  - `enrichObject(string $objectId, string $register, string $schema, ?array $objectData = null): array` — fetches object from OpenRegister if `$objectData` is null; calls `enhance()`; saves result via `ObjectService::saveObject()`; returns ['enrichedFields' => [...], 'object' => [...]].
  - Private `getObjectService()` — resolves OpenRegister ObjectService via `getInstalledApps()` + `container->get()` (NOTE: duplicates SettingsService pattern — see META-070; consolidation is a follow-up).
  - Add `@spec openspec/changes/metadata-enrichment/tasks.md#task-5` to docblocks.

- [x] 6. **MetadataController** — implement `lib/Controller/MetadataController.php`:
  - `enrich(Request $request): JSONResponse` — thin: read `objectId`, `register`, `schema`, optional `objectData`; validate required fields (HTTP 400 on missing); delegate to `MetadataService::enrichObject()`; return JSON response with message, enrichedFields, object.
  - Register route: `POST /api/metadata/enrich` in `appinfo/routes.php`.
  - Add `@spec openspec/changes/metadata-enrichment/tasks.md#task-6` to docblocks.

- [x] 7. **Event listener and runner** — implement event-driven enrichment:
  - `lib/EventListener/DocuDeskEventListener.php` — empty constructor; `handle(Event $event)` resolves MetadataService and LoggerInterface via `\OC::$server->get()` at handle-time; delegates to `DocuDeskEventHandler`; wraps in nested try/catch (outer: log + swallow; inner logger-resolution guard).
  - `lib/EventListener/DocuDeskEventHandler.php` — dispatches by event type: ObjectCreatedEvent → enrich; ObjectUpdatedEvent → check content fields then enrich; ObjectDeletedEvent → log only.
  - `lib/EventListener/EnrichmentRunner.php` — `shouldEnrich(Event $event): bool` detects content field changes (content, text, description, title) between old and new object state for update events; `run(object $eventObject): void` calls MetadataService.
  - Register listener in `appinfo/info.xml` or `lib/AppInfo/Application.php` for the three OpenRegister event types.
  - Add `@spec openspec/changes/metadata-enrichment/tasks.md#task-7` to docblocks.

- [x] 8. **Unit tests** — write PHPUnit tests per ADR-008:
  - `tests/unit/Service/LanguageClassifierTest.php` — Dutch detection, English detection, insufficient text (null), pre-populated skip, topic: financial win, legal win, no match (null), tie-breaking.
  - `tests/unit/Service/TextAnalysisServiceTest.php` — keyword extraction with stop-word removal, frequency ranking, max-10 cap, pre-populated skip, type standardization (docx→word, xlsx→spreadsheet, pptx→presentation, unknown passthrough).
  - `tests/unit/Service/DocumentTextExtractorTest.php` — content field priority, text fallback, description fallback, empty result, ISO 8601 normalization, unparseable date skip.
  - Add `@spec openspec/changes/metadata-enrichment/tasks.md#task-8` to test class docblocks.

- [x] 9. **i18n** — add translation strings per ADR-007:
  - `l10n/nl.js` / `l10n/nl.json` — Dutch labels for metadata enrichment admin settings (language detection, keyword extraction, topic classification toggle labels and descriptions).
  - `l10n/en_GB.js` / `l10n/en_GB.json` — English equivalents.

- [x] 10. **Documentation** — write `docs/features/metadata-enrichment.md` per ADR-009:
  - Purpose and enrichment pipeline overview.
  - Admin settings toggle reference.
  - API endpoint reference (`POST /api/metadata/enrich`) with request/response shapes.
  - Known limitations (heuristic accuracy, language support, `\OC::$server` anti-pattern debt).
  - CHANGELOG "Added" entry.

- [x] 11. **Quality + verification** — `composer check:strict` clean on all touched files; manual smoke test: create a document object via OpenRegister, verify enriched fields appear; trigger `POST /api/metadata/enrich` for an existing object, verify response; test with all toggles disabled to confirm no processing; `openspec validate metadata-enrichment` clean.

## Tasks

- [x] Task 1: Add `riskLevel` field to `AnonymizationService::extractAndDetectEntities()` response by injecting `FileEntityStatsService` and calling `getFileRiskLevel`
- [x] Task 2: Fix pre-existing test failures in `GrondslagenSummaryServiceTest` — `resolveBaseLabels` now returns placeholder format and `aggregateForDossier` defaults entity count to 1 when `count` key is absent
- [x] Task 3: Add `perDocument` and `perBasis` aggregation keys to `aggregateForDossier` return value in `GrondslagenSummaryService`
- [x] Task 4: Write `WooProfileServiceTest` covering getProfile defaults, stored profile, shouldAnonymize, saveProfile, and invalid JSON fallback
- [x] Task 5: Write `EntityConsolidationServiceTest` covering consolidation structure, minConfidence parameter, sort order, and deduplication key
- [x] Task 6: Extend `AnonymizationServiceTest` with `riskLevel` presence assertions and constructor update for new `FileEntityStatsService` dep
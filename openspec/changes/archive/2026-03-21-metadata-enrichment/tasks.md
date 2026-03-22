# Tasks: metadata-enrichment

## Task 1: Metadata API
- [x] Implement `POST /api/metadata/enrich` endpoint
- [x] Validate required fields (objectId, register, schema)
- [x] Return enriched fields and updated object

## Task 2: Language Detection
- [x] Implement `LanguageClassifier::detectLanguage()` with nl/en word frequency
- [x] Return null for insufficient text (threshold: 5 word matches)
- [x] Skip detection if language field pre-populated

## Task 3: Keyword Extraction
- [x] Implement `TextAnalysisService::extractKeywords()` with stop word removal
- [x] Return top 10 keywords by frequency
- [x] Support both Dutch and English stop words

## Task 4: Topic Classification
- [x] Implement `LanguageClassifier::classifyTopic()` for legal, financial, medical, technical
- [x] Score topics by keyword occurrence count

## Task 5: Event-Driven Enrichment
- [x] Register listener for ObjectCreated/Updated/Deleted events
- [x] Implement `EnrichmentRunner` for automatic processing
- [x] Respect feature toggle settings

## Task 6: Unit Tests (ADR-009)
- [x] Write `LanguageClassifierTest` for language detection and topic classification
- [x] Write `TextAnalysisServiceTest` for keywords, document types, word counting

## Task 7: Documentation (ADR-010)
- [x] Write feature documentation at `docs/features/metadata-enrichment.md`

## Task 8: i18n (ADR-005)
- [x] Add Dutch translations for metadata enrichment settings labels
- [x] Add English translations for metadata enrichment settings labels

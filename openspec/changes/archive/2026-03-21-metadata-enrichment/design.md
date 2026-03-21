# Design: Metadata Enrichment

## Architecture

### Backend
- `MetadataController::enrich()` provides REST endpoint `POST /api/metadata/enrich`
- `MetadataService` orchestrates enrichment and saves results back to OpenRegister
- `TextAnalysisService` handles keyword extraction and document type standardization
- `LanguageClassifier` detects language (nl/en) and classifies topics
- `DocumentTextExtractor` extracts text content and normalizes date fields
- `EnrichmentRunner` handles event-driven enrichment from OpenRegister events

### Event-Driven
- `DocuDeskEventListener` listens to ObjectCreated/Updated/Deleted events
- Enrichment runs automatically when documents change in OpenRegister
- Feature toggles control which enrichments are active

### Enrichment Pipeline
1. Extract text content from document object
2. Language detection (nl/en) via word frequency analysis
3. Keyword extraction (top 10 non-stop-words)
4. Topic classification (legal, financial, medical, technical)
5. Document type standardization
6. Date field normalization

## ADR Compliance
- ADR-001: Results saved via OpenRegister ObjectService
- ADR-008: Controller -> MetadataService -> TextAnalysisService layering

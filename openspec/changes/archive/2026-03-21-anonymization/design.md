# Design: Anonymization Pipeline

## Architecture

### Backend
- `AnonymizationController` provides 3 endpoints: upload, extract, anonymize
- `AnonymizationService` orchestrates the pipeline via OpenRegister services
- `FileListingService` handles file upload to user-scoped DocuDesk/ folder
- `EntityDetectionService` normalizes and maps entities for anonymization
- `AnonymizationResultParser` parses anonymization output
- `FileUploadService` handles multipart file upload with duplicate naming

### Frontend
- `AnonymizationIndex.vue` implements the 4-step wizard (Upload, Analyze, Anonymize, Done)
- `AnonymizationWidget.vue` provides the embeddable widget for dashboard
- Drag-and-drop file upload with progress indicators

### Data Flow
1. User uploads file -> stored in `DocuDesk/` folder via IRootFolder
2. Extract: OpenRegister `TextExtractionService::extractFile()` + `EntityRelationMapper::findEntitiesForFile()`
3. Anonymize: OpenRegister `FileService::anonymizeDocument()` replaces entities with placeholders

## ADR Compliance
- ADR-001: Files in Nextcloud filesystem, entities via OpenRegister
- ADR-008: Controller -> Service -> OpenRegister layering
- ADR-012: Uses standard Nextcloud components

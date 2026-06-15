# Coverage Report — docudesk

Generated: 2026-05-24 08:30 UTC
Branch: development
Scanner: opsx-coverage-scan v1

## Summary

| Bucket | Count | Next action |
|---|---|---|
| annotated | 0 | — (no pre-existing `@spec` tags — clean retrofit target) |
| plumbing | 99 | — (never tagged: DI constructors, error helpers, OR-resolution helpers, listener dispatch) |
| 1 — REQ matched | 213 | `/opsx-annotate docudesk` |
| 2a — existing capability, no REQ | 22 (2 clusters: `template-management`, `metadata-enrichment`) | `/opsx-reverse-spec docudesk --extend template-management` then `--extend metadata-enrichment` |
| 2b — no capability owner | 44 (2 clusters: `digital-signing`, `openregister-bridge`) | `/opsx-reverse-spec docudesk --cluster digital-signing` then `--cluster openregister-bridge` |
| 3a — REQ broken (code removed) | 1 (low-confidence) | Spot-check; likely re-classify to 3b |
| 3b — REQ never implemented (in PHP) | 40 | Mark deferred — most are Vue-side / schema-level / info.xml |
| 4 — ADR conformance | 67 findings across 2 rules | Follow-up issue (66 × missing-`@spec`, 1 × missing-`@copyright`) |

**Total PHP files scanned**: 66
**Total REQs in inventory**: 76 across 14 specs
**Total methods enumerated**: 207 (excluding interface-only signatures)

⚠️ **Large Bucket 1 (213 entries across 50+ files)** — once `/opsx-annotate` supports `--capability <cap>`, consider annotating one capability at a time. Recommended order: `anonymization` (38) → `consent-management` (16) → `letter-correspondence-generation` (24) → `metadata-enrichment` (12) → `template-management` (16) → `ocr-document-scanning` (12) → `pdf-generation` (10) → `admin-settings` (16) → `print-preview` (7) → `batch-anonymization` (28) → `prometheus-metrics` (5) → `dashboard` (14). For now the bucket is shipped whole.

## Bucket 1 — Ready to annotate (via ghost change `retrofit-2026-05-24-annotate-docudesk`)

Grouped by capability. Methods marked `NEEDS-REVIEW` (8 total, all confidence 0.65–0.78) should be human-verified before annotation. Pass-B helpers (private methods inheriting from a public caller) are marked with `← inherits_from`.

### capability: anonymization → task-1

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Controller/AnonymizationController.php | files | REQ-ANON-04 | 0.93 | controller maps to processed-file-listing REQ |
| lib/Controller/AnonymizationController.php | upload | REQ-ANON-01 | 0.92 | user-scoped folder upload endpoint |
| lib/Controller/AnonymizationController.php | extract | REQ-ANON-02 | 0.92 | text-extraction + entity-detection endpoint |
| lib/Controller/AnonymizationController.php | anonymize | REQ-ANON-03 | 0.93 | document anonymization endpoint |
| lib/Controller/AnonymizationController.php | filterByExcludeTypes | REQ-ANON-03 | 0.78 | Pass-B ← anonymize |
| lib/Controller/AnonymizationController.php | filterByConfidence | REQ-ANON-03 | 0.78 | Pass-B ← anonymize |
| lib/Service/AnonymizationService.php | getOpenRegisterService | REQ-ANON-05 | 0.95 | lazy OR resolution |
| lib/Service/AnonymizationService.php | extractAndDetectEntities | REQ-ANON-02 | 0.96 | text-extraction + entity-detection service |
| lib/Service/AnonymizationService.php | anonymizeDocument | REQ-ANON-03 | 0.96 | document anonymization service |
| lib/Service/AnonymizationResultParser.php | parseResult | REQ-ANON-03 | 0.86 | downstream of anonymize pipeline |
| lib/Service/EntityDetectionService.php | normalizeEntities | REQ-ANON-02 | 0.88 | entity normalization |
| lib/Service/EntityDetectionService.php | mapEntitiesForAnonymization | REQ-ANON-03 | 0.88 | entity mapping for replacement |
| lib/Service/EntityDetectionService.php | parseAnonymizationResult | REQ-ANON-03 | 0.85 | tool-output parser |
| lib/Service/FileEntityStatsService.php | tryGetEntityRelationMapper | REQ-ANON-09 | 0.93 | EntityRelationMapper resolver |
| lib/Service/FileEntityStatsService.php | tryGetRiskLevelService | REQ-ANON-04 | 0.86 | risk-level service resolver |
| lib/Service/FileEntityStatsService.php | getEntityStats | REQ-ANON-04 | 0.90 | per-file entity stats |
| lib/Service/FileEntityStatsService.php | determineFileStatus | REQ-ANON-04 | 0.86 | file-status derivation |
| lib/Service/FileEntityStatsService.php | getFileRiskLevel | REQ-ANON-04 | 0.92 | risk-level lookup |
| lib/Service/FileListingService.php | listProcessedFiles | REQ-ANON-04 | 0.96 | processed-file listing |
| lib/Service/FileListingService.php | uploadFile | REQ-ANON-01 | 0.88 | file upload pathway |
| lib/Service/FileListingService.php | buildFileInfo | REQ-ANON-04 | 0.78 | Pass-B ← listProcessedFiles |
| lib/Service/FileUploadService.php | getDocuDeskFolder | REQ-ANON-01 | 0.95 | user-scoped folder access |
| lib/Service/FileUploadService.php | resolveUniqueFileName | REQ-ANON-01 | 0.78 | Pass-B (file name dedup) |
| lib/Service/FileUploadService.php | uploadFile | REQ-ANON-01 | 0.95 | upload to user-scoped folder |

### capability: batch-anonymization → task-2

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Controller/BatchAnonymizationController.php | batchUpload | Batch creation via multi-file upload | 0.95 | direct REQ-name match |
| lib/Controller/BatchAnonymizationController.php | folderBatch | Batch creation via multi-file upload | 0.85 | folder-source variant per spec scenarios |
| lib/Controller/BatchAnonymizationController.php | batchExtract | Sequential batch extraction | 0.95 | direct REQ-name match |
| lib/Controller/BatchAnonymizationController.php | batchStatus | Batch status endpoint | 0.97 | direct REQ-name match |
| lib/Controller/BatchAnonymizationController.php | batchAnonymize | Batch anonymization | 0.96 | direct REQ-name match |
| lib/Controller/BatchAnonymizationController.php | batchReport | Batch completion report | 0.95 | direct REQ-name match |
| lib/Controller/BatchAnonymizationController.php | getProfiles | WOO entity category profiles | 0.90 | WOO profile read |
| lib/Controller/BatchAnonymizationController.php | updateProfiles | WOO entity category profiles | 0.90 | WOO profile update |
| lib/Controller/BatchAnonymizationController.php | validateFolderParams | Batch creation via multi-file upload | 0.78 | Pass-B ← folderBatch |
| lib/Service/BatchAnonymizeService.php | anonymizeBatch | Batch anonymization | 0.95 | direct REQ-name match |
| lib/Service/BatchExtractionService.php | extractNext | Sequential batch extraction | 0.94 | per-file sequential REQ scenario |
| lib/Service/BatchReportService.php | generateReport | Batch completion report | 0.94 | direct REQ-name match |
| lib/Service/BatchStateService.php | getMaxFiles | Batch creation via multi-file upload | 0.75 | **NEEDS-REVIEW** — config-limit applied during create |
| lib/Service/BatchStateService.php | createBatch | Batch creation via multi-file upload | 0.92 | batch persistence |
| lib/Service/BatchStateService.php | getBatch | Batch status endpoint | 0.82 | underlying read for status endpoint |
| lib/Service/BatchStateService.php | updateBatch | Sequential batch extraction | 0.75 | **NEEDS-REVIEW** — state mutation during extract/anonymize |
| lib/Service/BatchStateService.php | deleteBatch | Batch creation via multi-file upload | 0.70 | **NEEDS-REVIEW** — lifecycle cleanup, no explicit REQ |
| lib/Service/BatchUploadService.php | collectFiles | Batch creation via multi-file upload | 0.90 | multi-file collection |
| lib/Service/BatchUploadService.php | processBatchUpload | Batch creation via multi-file upload | 0.92 | batch upload processing |
| lib/Service/FolderBatchService.php | createFolderBatch | Batch creation via multi-file upload | 0.86 | folder-source variant |
| lib/Service/FolderBatchService.php | resolveFolderNode | Batch creation via multi-file upload | 0.78 | Pass-B ← createFolderBatch |
| lib/Service/FolderBatchService.php | pickPreferredNode | Batch creation via multi-file upload | 0.72 | **NEEDS-REVIEW** — Pass-B ← resolveFolderNode |
| lib/Service/FolderBatchService.php | scheduleExtraction | Sequential batch extraction | 0.88 | queues FolderExtractionJob |
| lib/Service/FolderBatchService.php | enumerateFiles | Batch creation via multi-file upload | 0.78 | Pass-B ← createFolderBatch |
| lib/Service/WooProfileService.php | getProfile | WOO entity category profiles | 0.94 | WOO profile read |
| lib/Service/WooProfileService.php | saveProfile | WOO entity category profiles | 0.94 | WOO profile write |
| lib/Service/WooProfileService.php | shouldAnonymize | WOO entity category profiles | 0.88 | WOO profile evaluation |
| lib/BackgroundJob/FolderExtractionJob.php | run | Sequential batch extraction | 0.94 | BackgroundJob entry point |

### capability: anonymization-entity-review → task-3

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Controller/BatchAnonymizationController.php | batchEntities | Consolidated entity list endpoint | 0.92 | consolidated entity REST endpoint |
| lib/Service/EntityConsolidationService.php | consolidateEntities | Consolidated entity list endpoint | 0.93 | entity consolidation across files |
| lib/Service/EntityConsolidationService.php | mergeEntity | Consolidated entity list endpoint | 0.78 | Pass-B ← consolidateEntities |
| lib/Service/EntityConsolidationService.php | getEntitiesForFile | Consolidated entity list endpoint | 0.78 | Pass-B ← consolidateEntities |

### capability: consent-management → task-4

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Controller/ConsentController.php | index | REQ-CONS-03 | 0.94 | consent listing endpoint |
| lib/Controller/ConsentController.php | create | REQ-CONS-01 | 0.95 | consent creation endpoint |
| lib/Controller/ConsentController.php | show | REQ-CONS-03 | 0.84 | consent get-by-id |
| lib/Controller/ConsentController.php | update | REQ-CONS-02 | 0.92 | consent status lifecycle |
| lib/Controller/ConsentController.php | byDocument | REQ-CONS-03 | 0.88 | consent query by document |
| lib/Service/ConsentCrudService.php | getConsentConfig | REQ-CONS-06 | 0.82 | RBAC + multitenancy config |
| lib/Service/ConsentCrudService.php | listConsents | REQ-CONS-03 | 0.94 | consent listing |
| lib/Service/ConsentCrudService.php | getConsent | REQ-CONS-03 | 0.85 | single-consent read |
| lib/Service/ConsentCrudService.php | createFromRequest | REQ-CONS-01 | 0.93 | consent creation |
| lib/Service/ConsentCrudService.php | getConsentsByDocument | REQ-CONS-03 | 0.88 | consent query by document |
| lib/Service/ConsentCrudService.php | updateConsentStatus | REQ-CONS-02 | 0.94 | consent status lifecycle |
| lib/Service/ConsentService.php | createConsentRequest | REQ-CONS-01 | 0.92 | consent creation |
| lib/Service/ConsentService.php | buildConsentData | REQ-CONS-01 | 0.78 | Pass-B ← createConsentRequest |
| lib/Service/ConsentService.php | updateConsentStatus | REQ-CONS-02 | 0.94 | consent status lifecycle |
| lib/Service/ConsentService.php | checkObjectionDeadline | REQ-CONS-04 | 0.93 | WOO objection compliance |
| lib/Service/ConsentService.php | getConsentsByDocument | REQ-CONS-03 | 0.88 | consent query by document |
| lib/Service/ConsentUpdateHandler.php | updateConsentStatus | REQ-CONS-02 | 0.92 | consent status lifecycle (handler) |
| lib/Service/ConsentUpdateHandler.php | getConsentsByDocument | REQ-CONS-03 | 0.86 | consent query by document (handler) |
| lib/Service/ObjectionDeadlineChecker.php | getObjectionPeriodDays | REQ-CONS-08 | 0.94 | objection period config read |
| lib/Service/ObjectionDeadlineChecker.php | calculateDeadline | REQ-CONS-04 | 0.93 | WOO objection compliance |
| lib/Service/ObjectionDeadlineChecker.php | checkObjectionDeadline | REQ-CONS-04 | 0.93 | WOO objection compliance |

### capability: letter-correspondence-generation → task-5

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Controller/CorrespondenceController.php | generate | Correspondence REST endpoint | 0.96 | POST /api/correspondence/generate |
| lib/Controller/CorrespondenceController.php | generateBatch | Batch correspondence REST endpoints | 0.95 | POST /api/correspondence/generate/batch |
| lib/Controller/CorrespondenceController.php | jobStatus | Batch correspondence REST endpoints | 0.95 | GET /api/correspondence/jobs/{jobId} |
| lib/Controller/CorrespondenceController.php | parseGenerateParams | Correspondence REST endpoint | 0.78 | Pass-B ← generate |
| lib/Controller/CorrespondenceController.php | formatGenerateResponse | Output format selection | 0.82 | branches response on format |
| lib/Controller/CorrespondenceController.php | buildDownloadResponse | Correspondence REST endpoint | 0.78 | Pass-B ← generate |
| lib/Service/CorrespondenceService.php | generate | Correspondence generation API | 0.97 | direct scenario match |
| lib/Service/CorrespondenceService.php | generateBatch | Batch correspondence generation | 0.96 | sync/async split per scenarios |
| lib/Service/CorrespondenceService.php | generateBatchSync | Batch correspondence generation | 0.85 | sync branch |
| lib/Service/CorrespondenceService.php | dispatchBatchJob | Batch correspondence generation | 0.85 | async branch dispatches background job |
| lib/Service/CorrespondenceService.php | getJobStatus | Batch correspondence REST endpoints | 0.87 | backing read |
| lib/Service/CorrespondenceService.php | storeJobStatus | Batch correspondence generation | 0.82 | job-status persistence |
| lib/Service/CorrespondenceService.php | loadJobStatus | Batch correspondence REST endpoints | 0.78 | Pass-B ← getJobStatus |
| lib/Service/CorrespondenceService.php | validateFormat | Output format selection | 0.88 | format validation |
| lib/Service/CorrespondenceService.php | loadHuisstijl | Huisstijl default configuration | 0.95 | huisstijl loader |
| lib/Service/CorrespondenceService.php | buildPdfOptions | Huisstijl default configuration | 0.82 | huisstijl margins → PDF options |
| lib/Service/CorrespondenceService.php | renderWithHuisstijl | Huisstijl default configuration | 0.92 | huisstijl header/footer/logo at render time |
| lib/Service/CorrespondenceService.php | produceOutput | Output format selection | 0.90 | HTML / PDF / DOCX branch |
| lib/Service/CorrespondenceService.php | stripPageStyling | Email body generation | 0.86 | strips @page CSS for email format |
| lib/Service/CorrespondenceService.php | convertToDocx | Output format selection | 0.88 | LibreOffice DOCX conversion |
| lib/Service/CorrespondenceService.php | logCorrespondence | Correspondence register logging | 0.95 | per-generation register write |
| lib/Service/DataResolverService.php | resolve | Correspondence generation API | 0.78 | data-resolution dependency cited by spec |
| lib/Service/DataResolverService.php | validateReference | Correspondence generation API | 0.70 | **NEEDS-REVIEW** — Pass-B ← resolve |
| lib/Service/DataResolverService.php | resolveReference | Correspondence generation API | 0.70 | **NEEDS-REVIEW** — Pass-B ← resolve |
| lib/Service/DataResolverService.php | resolveNestedReferences | Correspondence generation API | 0.78 | 3-level-deep nested REQ scenario |
| lib/Service/DataResolverService.php | isUuid | Correspondence generation API | 0.65 | **NEEDS-REVIEW** — Pass-B ← resolveReference |
| lib/Service/DataResolverService.php | clearCache | Correspondence generation API | 0.60 | **NEEDS-REVIEW** — cache reset, no scenario |
| lib/BackgroundJob/BatchCorrespondenceJob.php | run | Batch correspondence generation | 0.93 | async job entry |
| lib/BackgroundJob/BatchCorrespondenceJob.php | initializeJobStatus | Batch correspondence generation | 0.78 | Pass-B ← run |
| lib/BackgroundJob/BatchCorrespondenceJob.php | processRecipients | Batch correspondence generation | 0.86 | per-recipient partial-failure loop |

### capability: metadata-enrichment → task-6

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Controller/MetadataController.php | enrich | REQ-META-07 | 0.94 | API on-demand enrichment |
| lib/EventListener/DocuDeskEventHandler.php | handleObjectCreated | REQ-META-06 | 0.93 | event-driven enrichment (create) |
| lib/EventListener/DocuDeskEventHandler.php | handleObjectUpdated | REQ-META-06 | 0.93 | event-driven enrichment (update) |
| lib/EventListener/DocuDeskEventHandler.php | handleObjectDeleted | REQ-META-06 | 0.85 | event-driven cleanup |
| lib/EventListener/DocuDeskEventHandler.php | hasContentChanged | REQ-META-06 | 0.78 | Pass-B ← handleObjectUpdated |
| lib/EventListener/EnrichmentRunner.php | isEnrichmentEnabled | REQ-META-09 | 0.83 | event-listener service-resolution check |
| lib/EventListener/EnrichmentRunner.php | enrichObject | REQ-META-06 | 0.90 | event-driven orchestrator |
| lib/Service/DocumentTextExtractor.php | extractTextContent | REQ-META-10 | 0.95 | text-content extraction from object data |
| lib/Service/DocumentTextExtractor.php | normalizeDateFields | REQ-META-05 | 0.94 | date normalization |
| lib/Service/MetadataService.php | enhanceMetadata | REQ-META-07 | 0.86 | API on-demand orchestrator |
| lib/Service/MetadataService.php | enhanceTextMetadata | REQ-META-01 | 0.78 | Pass-B ← enhanceMetadata |
| lib/Service/MetadataService.php | saveEnrichedMetadata | REQ-META-07 | 0.82 | persists enriched object |
| lib/Service/TextAnalysisService.php | detectLanguage | REQ-META-01 | 0.95 | language detection |
| lib/Service/TextAnalysisService.php | extractKeywords | REQ-META-02 | 0.95 | keyword extraction |
| lib/Service/TextAnalysisService.php | classifyTopic | REQ-META-03 | 0.95 | topic classification |
| lib/Service/TextAnalysisService.php | standardizeDocumentType | REQ-META-04 | 0.95 | document-type standardisation |
| lib/Service/TextAnalysisService.php | countWordOccurrences | REQ-META-01 | 0.70 | **NEEDS-REVIEW** — Pass-B ← detectLanguage |

### capability: template-management → task-7

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Controller/TemplatesController.php | index | REQ-TMPL-02 | 0.93 | CRUD list |
| lib/Controller/TemplatesController.php | show | REQ-TMPL-02 | 0.93 | CRUD read |
| lib/Controller/TemplatesController.php | create | REQ-TMPL-02 | 0.94 | CRUD create |
| lib/Controller/TemplatesController.php | update | REQ-TMPL-02 | 0.94 | CRUD update |
| lib/Controller/TemplatesController.php | destroy | REQ-TMPL-02 | 0.94 | CRUD delete |
| lib/Service/TemplateService.php | getTemplates | REQ-TMPL-06 | 0.93 | search + pagination |
| lib/Service/TemplateService.php | getTemplate | REQ-TMPL-04 | 0.92 | TemplateService programmatic access |
| lib/Service/TemplateService.php | createTemplate | REQ-TMPL-02 | 0.93 | CRUD create |
| lib/Service/TemplateService.php | updateTemplate | REQ-TMPL-02 | 0.93 | CRUD update |
| lib/Service/TemplateService.php | updateTemplateWithoutVersion | REQ-TMPL-02 | 0.78 | CRUD update (no version snapshot) |
| lib/Service/TemplateService.php | deleteTemplate | REQ-TMPL-02 | 0.93 | CRUD delete |
| lib/Service/TemplateService.php | getTemplatesByNamespace | REQ-TMPL-03 | 0.94 | namespace enforcement |
| lib/Service/TemplatePreviewService.php | preview | REQ-TMPL-04 | 0.86 | programmatic preview |
| lib/Service/TemplatePreviewService.php | previewTemplate | REQ-TMPL-04 | 0.86 | programmatic preview-by-id |

### capability: pdf-generation → task-8

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Controller/PdfController.php | render | REQ-PDF-05 | 0.96 | PDF rendering API endpoint |
| lib/Service/PdfService.php | renderPdf | REQ-PDF-01 | 0.96 | PDF rendering service |
| lib/Service/PdfService.php | ensureTempDirectory | REQ-PDF-04 | 0.93 | mPDF temp-directory management |
| lib/Service/PdfService.php | buildMpdfConfig | REQ-PDF-02 | 0.93 | page configuration options |
| lib/Service/PdfService.php | getFontDirectory | REQ-PDF-02 | 0.70 | **NEEDS-REVIEW** — Pass-B ← buildMpdfConfig |
| lib/Service/PdfService.php | generatePdf | REQ-PDF-03 | 0.86 | applies Twig sandbox + mPDF render |
| lib/Service/TemplateRenderer.php | renderTemplate | REQ-PDF-03 | 0.87 | Twig sandbox rendering |
| lib/Service/TemplateRenderer.php | convertConditionalSections | REQ-PDF-07 | 0.70 | **NEEDS-REVIEW** — sandbox config details |
| lib/Service/TemplateRenderer.php | replaceConditionalSection | REQ-PDF-07 | 0.70 | **NEEDS-REVIEW** — Pass-B ← convertConditionalSections |
| lib/Service/TemplateRenderer.php | buildTwigCondition | REQ-PDF-07 | 0.70 | **NEEDS-REVIEW** — Pass-B ← replaceConditionalSection |
| lib/Service/TemplateRenderer.php | escapeTwigString | REQ-PDF-07 | 0.70 | **NEEDS-REVIEW** — Pass-B ← buildTwigCondition |

### capability: print-preview → task-9

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Controller/PrintController.php | preview | PRINT-001 | 0.97 | POST /api/print/preview |
| lib/Controller/PrintController.php | downloadPdfA | PRINT-010 | 0.97 | POST /api/print/pdf-a |
| lib/Controller/PrintController.php | resolveTemplate | PRINT-002 | 0.85 | templateId vs inline template resolution |
| lib/Controller/PrintController.php | getRequestOptions | PRINT-001 | 0.75 | **NEEDS-REVIEW** — Pass-B ← preview/downloadPdfA |
| lib/Controller/PdfController.php | renderPdfA | PRINT-010 | 0.82 | PDF/A download endpoint (alternate route) |
| lib/Controller/TemplatesController.php | preview | PRINT-001 | 0.70 | **NEEDS-REVIEW** — may belong in template-management 2a |
| lib/Controller/TemplatesController.php | previewTemplate | PRINT-002 | 0.70 | **NEEDS-REVIEW** — may belong in template-management 2a |
| lib/Service/PdfService.php | renderHtmlPreview | PRINT-004 | 0.88 | HTML preview with print CSS |
| lib/Service/PdfService.php | buildPrintCss | PRINT-004 | 0.93 | print-optimised CSS |

### capability: admin-settings → task-10

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Sections/DocuDeskAdmin.php | getIcon | REQ-SET-01 | 0.85 | admin panel section icon |
| lib/Sections/DocuDeskAdmin.php | getID | REQ-SET-01 | 0.85 | admin panel section id |
| lib/Sections/DocuDeskAdmin.php | getName | REQ-SET-01 | 0.85 | admin panel section name |
| lib/Sections/DocuDeskAdmin.php | getPriority | REQ-SET-01 | 0.85 | admin panel section order |
| lib/Settings/DocuDeskAdmin.php | getForm | REQ-SET-01 | 0.95 | admin panel form template |
| lib/Settings/DocuDeskAdmin.php | getSection | REQ-SET-01 | 0.95 | admin panel section binding |
| lib/Settings/DocuDeskAdmin.php | getPriority | REQ-SET-01 | 0.90 | settings order |
| lib/Controller/SettingsController.php | index | REQ-SET-06 | 0.95 | settings REST API GET |
| lib/Controller/SettingsController.php | create | REQ-SET-06 | 0.92 | settings REST API write |
| lib/Controller/SettingsController.php | getObjectService | REQ-SET-11 | 0.86 | TypeError catch fallback for OR |
| lib/Controller/SettingsController.php | getConfigurationService | REQ-SET-11 | 0.86 | TypeError catch fallback for OR |
| lib/Service/RegisterDiscoveryService.php | fetchAvailableRegisters | REQ-SET-02 | 0.92 | OR integration discovery |
| lib/Service/RegisterDiscoveryService.php | filterSchemas | REQ-SET-02 | 0.78 | Pass-B ← fetchAvailableRegisters |
| lib/Service/RegisterDiscoveryService.php | filterSchemaProperties | REQ-SET-02 | 0.78 | Pass-B ← fetchAvailableRegisters |
| lib/Service/RegisterDiscoveryService.php | loadObjectTypeConfiguration | REQ-SET-02 | 0.86 | object-type config loading |
| lib/Service/SettingsInitializer.php | loadSettings | REQ-SET-10 | 0.92 | config file resolution + validation |
| lib/Service/SettingsInitializer.php | initialize | REQ-SET-03 | 0.95 | auto-initialization on boot |
| lib/Service/SettingsService.php | getObjectService | REQ-SET-07 | 0.86 | SettingsService public helper |
| lib/Service/SettingsService.php | initialize | REQ-SET-03 | 0.95 | auto-initialization on boot |
| lib/Service/SettingsService.php | loadFeatureToggles | REQ-SET-05 | 0.93 | metadata-enrichment feature toggles |
| lib/Service/SettingsService.php | getAllSettings | REQ-SET-07 | 0.88 | SettingsService public helper |
| lib/Service/SettingsService.php | convertValueToString | REQ-SET-07 | 0.70 | **NEEDS-REVIEW** — Pass-B ← getAllSettings |
| lib/Service/SettingsService.php | updateSettings | REQ-SET-06 | 0.90 | settings write backing REST API |

### capability: prometheus-metrics → task-11

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Controller/HealthController.php | index | REQ-PROM-06 | 0.96 | health check endpoint |
| lib/Controller/MetricsController.php | index | REQ-PROM-01 | 0.96 | metrics endpoint text/plain |
| lib/Controller/MetricsCollector.php | countDocuments | REQ-PROM-03 | 0.88 | app-specific metric |
| lib/Controller/MetricsCollector.php | countTemplates | REQ-PROM-03 | 0.88 | app-specific metric |
| lib/Controller/MetricsCollector.php | countObjects | REQ-PROM-03 | 0.78 | Pass-B ← count* |

### capability: dashboard → task-12

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Controller/DashboardController.php | page | REQ-DASH-04 | 0.97 | TemplateResponse for main app page |
| lib/Dashboard/AnonymizationWidget.php | getId | REQ-DASH-02 | 0.86 | widget id |
| lib/Dashboard/AnonymizationWidget.php | getTitle | REQ-DASH-02 | 0.86 | widget title |
| lib/Dashboard/AnonymizationWidget.php | getOrder | REQ-DASH-02 | 0.84 | widget order |
| lib/Dashboard/AnonymizationWidget.php | getIconClass | REQ-DASH-02 | 0.84 | widget icon class |
| lib/Dashboard/AnonymizationWidget.php | getIconUrl | REQ-DASH-06 | 0.78 | icon file differentiation |
| lib/Dashboard/AnonymizationWidget.php | getUrl | REQ-DASH-02 | 0.82 | widget click target |
| lib/Dashboard/AnonymizationWidget.php | load | REQ-DASH-02 | 0.85 | widget asset loader |
| lib/Dashboard/FileEntitiesWidget.php | getId | REQ-DASH-02 | 0.86 | widget id |
| lib/Dashboard/FileEntitiesWidget.php | getTitle | REQ-DASH-02 | 0.86 | widget title |
| lib/Dashboard/FileEntitiesWidget.php | getOrder | REQ-DASH-02 | 0.84 | widget order |
| lib/Dashboard/FileEntitiesWidget.php | getIconClass | REQ-DASH-02 | 0.84 | widget icon class |
| lib/Dashboard/FileEntitiesWidget.php | getIconUrl | REQ-DASH-06 | 0.78 | icon file differentiation |
| lib/Dashboard/FileEntitiesWidget.php | getUrl | REQ-DASH-02 | 0.82 | widget click target |
| lib/Dashboard/FileEntitiesWidget.php | load | REQ-DASH-02 | 0.85 | widget asset loader |

### capability: ocr-document-scanning → task-13

| File | Method | REQ | Confidence | Signal |
|---|---|---|---|---|
| lib/Service/OcrService.php | isTesseractAvailable | OCR-030 | 0.95 | graceful degradation check |
| lib/Service/OcrService.php | getTesseractVersion | OCR-032 | 0.94 | admin-settings version display |
| lib/Service/OcrService.php | needsOcr | OCR-010 | 0.95 | MIME + text-content detection |
| lib/Service/OcrService.php | isOcrEnabled | OCR-020 | 0.82 | config toggle |
| lib/Service/OcrService.php | getOcrLanguages | OCR-020 | 0.94 | language config (nld+eng) |
| lib/Service/OcrService.php | getOcrDpi | OCR-021 | 0.94 | DPI config (300) |
| lib/Service/OcrService.php | extractTextFromImage | OCR-004 | 0.95 | image-file OCR |
| lib/Service/OcrService.php | extractTextFromPdf | OCR-003 | 0.95 | scanned-PDF OCR (Imagick + Tesseract) |
| lib/Service/OcrService.php | processFile | OCR-001 | 0.93 | primary OCR entry |
| lib/Service/OcrService.php | getFileById | OCR-001 | 0.70 | **NEEDS-REVIEW** — Pass-B ← processFile |
| lib/Service/OcrService.php | writeToTemp | OCR-001 | 0.70 | **NEEDS-REVIEW** — Pass-B ← processFile |
| lib/Service/OcrService.php | getConfidenceScore | OCR-040 | 0.93 | OCR confidence score |

## Bucket 2a — Existing capability, no REQ (`/opsx-reverse-spec --extend`)

### cluster: template-management (19 methods)

The template-management spec covers CRUD, namespace, search/pagination, and programmatic access. The codebase additionally implements **versioning** (create/list/get/restore/diff/next-number), **locking** (acquire/release/expiration), **duplication**, and **shared request-parsing helpers** — none of which are in the spec.

- `lib/Controller/TemplatesController.php::versions()` — list versions of a template
- `lib/Controller/TemplatesController.php::restoreVersion()` — restore a prior version
- `lib/Controller/TemplatesController.php::diffVersions()` — diff two versions
- `lib/Controller/TemplatesController.php::duplicate()` — clone a template
- `lib/Controller/TemplatesController.php::lock()` — acquire edit lock
- `lib/Controller/TemplatesController.php::unlock()` — release edit lock
- `lib/Service/TemplateService.php::duplicateTemplate()` — duplicate-template service
- `lib/Service/TemplateService.php::acquireLock()` — acquire edit lock service
- `lib/Service/TemplateService.php::releaseLock()` — release edit lock service
- `lib/Service/TemplateService.php::isLockExpired()` — lock expiration helper
- `lib/Service/TemplateVersionService.php::createVersion()` — version snapshotting
- `lib/Service/TemplateVersionService.php::getVersions()` — list template versions
- `lib/Service/TemplateVersionService.php::getVersion()` — fetch single version
- `lib/Service/TemplateVersionService.php::getNextVersionNumber()` — version increment
- `lib/Service/TemplateVersionService.php::restoreVersion()` — restore version
- `lib/Service/TemplateVersionService.php::getDiff()` — diff between versions
- `lib/Controller/TemplateRequestHandler.php::parseListParams()` — shared list-param parser
- `lib/Controller/TemplateRequestHandler.php::parseBodyParams()` — shared body parser
- `lib/Controller/TemplateRequestHandler.php::buildErrorResponse()` — shared error builder

In-flight change `openspec/changes/advanced-template-management/` may already cover this — verify before reverse-spec.

### cluster: metadata-enrichment (3 methods, refactor candidate)

`LanguageClassifier.php` duplicates `TextAnalysisService` (`detectLanguage` + `classifyTopic`) without a spec explaining why. Likely refactor to consolidate.

- `lib/Service/LanguageClassifier.php::detectLanguage()`
- `lib/Service/LanguageClassifier.php::classifyTopic()`
- `lib/Service/LanguageClassifier.php::countWordOccurrences()`

## Bucket 2b — No capability owner (`/opsx-reverse-spec --cluster`)

### cluster: digital-signing (41 methods, LARGE)

A complete signing surface (controller + service + audit + verification + 3 providers) ships in the code, but no `openspec/specs/digital-signing/spec.md` exists. **Three in-flight changes** address parts of this gap: `document-signing/`, `migrate-signing-audit-to-or-audit/`, `migrate-signing-to-or-approval-workflow/`. Before running `/opsx-reverse-spec docudesk --cluster digital-signing`, check whether the in-flight changes' tasks should be archived first.

**Controller (9 methods)**:
- `lib/Controller/SigningController.php::createRequest()` — POST /api/signing/requests
- `lib/Controller/SigningController.php::listRequests()` — GET /api/signing/requests
- `lib/Controller/SigningController.php::showRequest()` — GET /api/signing/requests/{id}
- `lib/Controller/SigningController.php::cancelRequest()` — cancel a request
- `lib/Controller/SigningController.php::sign()` — apply signature
- `lib/Controller/SigningController.php::decline()` — decline to sign
- `lib/Controller/SigningController.php::bulkSign()` — bulk-sign endpoint
- `lib/Controller/SigningController.php::verify()` — verify uploaded signature
- `lib/Controller/SigningController.php::getAudit()` — fetch audit trail

**Provider abstraction (3 providers × ~5 methods + factory)**:
- `lib/Service/Signing/SigningProviderFactory.php::getActiveProvider()`
- `lib/Service/Signing/SigningProviderFactory.php::getProvider()`
- `lib/Service/Signing/SigningProviderFactory.php::getAvailableProviders()`
- `lib/Service/Signing/NativeSigningProvider.php::{getIdentifier,initiateSigning,checkStatus,downloadSignedDocument,cancelSigning,supportsLevel}()`
- `lib/Service/Signing/ValidSignProvider.php::{getIdentifier,initiateSigning,checkStatus,downloadSignedDocument,cancelSigning,supportsLevel}()`

**Orchestration service (~10 methods)**:
- `lib/Service/SigningService.php::{createRequest,getRequest,listRequests,sign,decline,cancelRequest,bulkSign,isValidTransition,validateRequestData,updateRequestStatus}()`

**Audit (4 methods)**:
- `lib/Service/SigningAuditService.php::{logEvent,getAuditTrail,rejectUpdate,rejectDelete}()`

**Verification (3 methods)**:
- `lib/Service/SigningVerificationService.php::{verifyDocument,extractSignatures,allSignaturesValid}()`

### cluster: openregister-bridge (3 methods)

`OpenRegisterResolver` is a tiny utility that resolves app-config-driven OR register/schema slugs. No spec covers it. It's small enough that the reverse-spec could be a single REQ, or it could be merged into `admin-settings` (since the config it reads is admin-defined).

- `lib/Service/OpenRegisterResolver.php::getRegisterAndSchema()`
- `lib/Service/OpenRegisterResolver.php::getVersionRegisterAndSchema()`
- `lib/Service/OpenRegisterResolver.php::validateNamespace()`

## Bucket 3 — Surfaced for human triage

### 3a — possibly broken / code-removed (1)

- `letter-correspondence-generation` (overall) — removed-lines cache matched 52 occurrences of `correspondence`/`huisstijl`. **Best read as historical churn, not a broken feature** — the API still ships and most scenarios match Bucket 1. No action needed.

### 3b — never implemented (40)

Most of these are *expected gaps* (Vue-side, JSON schema, info.xml, or below method granularity), not real implementation gaps:

**Out-of-PHP-scope (Vue-side, schema, info.xml — 23 REQs)**:
- `dashboard#REQ-DASH-01/03/05`, `consent-management#REQ-CONS-10`, `anonymization#REQ-ANON-08/10`, `anonymization-entity-review` × 5 UI REQs, `print-preview#PRINT-020..025` (6 Vue REQs)
- `document-register#REQ-DREG-01..07` (entire spec — JSON schema, no PHP)
- `template-management#REQ-TMPL-01` (JSON schema)
- `admin-settings#REQ-SET-08/09` (info.xml / static URLs)
- `pdf-generation#REQ-PDF-06` (composer dependencies)

**Below method granularity — REQ scenarios covered transparently inside larger methods (12 REQs)**:
- `ocr-document-scanning#OCR-002/005/011..013/022..023/031/041..042`
- `print-preview#PRINT-003/011/012`
- `prometheus-metrics#REQ-PROM-02/07`
- `template-management#REQ-TMPL-05/07`

**Architectural / negative / planned REQs (5 REQs)**:
- `consent-management#REQ-CONS-05/06/07/09` (architectural / gap / anti-pattern observations)
- `anonymization#REQ-ANON-06/07` (covered implicitly)
- `metadata-enrichment#REQ-META-08` (anti-pattern observation)
- `prometheus-metrics#REQ-PROM-04/05` (explicitly `Priority: Should` + `Planned`)
- `dashboard#REQ-DASH-07` (meta-REQ verifying removal)
- `admin-settings#REQ-SET-04` (likely in templates/admin*.php form, not lib/)

## Bucket 4 — ADR conformance findings

### missing `@spec` in file docblock (66 files)

Every single `lib/**/*.php` file (66/66) lacks an `@spec openspec/changes/...` tag in its file header. This is expected — the app has never been annotated. The `/opsx-annotate docudesk` workflow will fix this in one ghost change.

### missing `@copyright` in file docblock (1 file)

- `lib/Service/FolderBatchService.php` — file docblock has `@author`, `@license`, `@link` but no `@copyright` line. Trivial fix.

### forbidden-patterns (0)

No `var_dump`, `dd(`, `die(`, `print_r(`, `error_log(` outside tests.

### direct SQL (0)

No `$this->db->query(` or `->prepare(` calls. All data access goes through OpenRegister / NC entity mappers.

## Notes for the human reviewer

1. **Clean retrofit target.** Zero `@spec` annotations exist anywhere in `lib/`, so the entire annotation surface is greenfield. There are no pre-existing tags to deconflict.

2. **Large Bucket 1 (213 entries).** Once `/opsx-annotate docudesk --capability <cap>` lands, prefer per-capability annotation. Today the bucket ships whole; reviewer effort scales with that.

3. **Two reverse-spec clusters dominate**:
   - **`digital-signing`** (41 methods) — the largest unspecced surface. Three in-flight changes (`document-signing`, `migrate-signing-audit-to-or-audit`, `migrate-signing-to-or-approval-workflow`) may already cover most of this — *verify in-flight changes' state before reverse-spec, or you'll duplicate work.*
   - **`template-management` extension** (19 methods) — versioning + locking + duplication. In-flight change `advanced-template-management` likely covers this — *check before reverse-spec.*

4. **2a `metadata-enrichment` is a refactor signal, not a missing spec.** `LanguageClassifier` duplicates `TextAnalysisService`. Recommend consolidation issue rather than reverse-spec.

5. **`TemplatesController::preview()` / `previewTemplate()` are ambiguous.** They were tentatively bucketed under `print-preview` (PRINT-001/002) with `NEEDS-REVIEW`. They could equally extend `template-management` (preview API). Pick one when annotating.

6. **8 `NEEDS-REVIEW` flags** all sit at 0.65–0.78 confidence — mostly Pass-B helpers and edge cases. Skim before annotating.

7. **The 40 Bucket-3b REQs are mostly NOT real gaps** — 35/40 are Vue-side / schema-level / info.xml / sub-method scenarios. Only 5 are genuine PHP-locus gaps (REQ-CONS-07 documented gap, REQ-SET-04 form-level config write, REQ-PROM-02 standard metrics, REQ-CONS-05/06 architectural). Do **not** treat the 3b count as "40 missing features".

8. **No namespace-word cluster warnings emitted.** Both Bucket 2b labels (`digital-signing`, `openregister-bridge`) are behavioral, suitable for `--cluster` targets.

9. **Reverse-pass cache built successfully** (48,759 removed lines in 1.5s, well under the 30s budget). Only one weak 3a match (correspondence/huisstijl churn) and it's most likely historical refactoring, not breakage.

10. **No `.opsx-ignore` present.** All findings shown.

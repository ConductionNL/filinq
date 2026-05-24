---
status: implemented
---

# Metadata Enrichment

## Purpose

Provides automatic metadata enrichment for documents stored in OpenRegister. When documents are created or updated, DocuDesk detects language, extracts keywords, classifies topics, standardizes document types, and normalizes date fields. Enrichment runs both on-demand via the API and automatically via the OpenRegister event listener. All processing is performed locally using heuristic algorithms -- no external NLP services are required.

## Requirements

### Requirement: Language Detection

**ID:** REQ-META-01
**Priority:** Must

Detect document language from text content using word frequency analysis for Dutch and English.

#### Scenario: Detect Dutch document
- GIVEN a document with text containing frequent Dutch words (de, het, een, en, van)
- AND Dutch word count exceeds 5 and is higher than English
- WHEN language detection runs
- THEN the language is detected as "nl"

#### Scenario: Detect English document
- GIVEN a document with text containing frequent English words (the, be, to, of, and)
- AND English word count exceeds 5
- WHEN language detection runs
- THEN the language is detected as "en"

#### Scenario: Insufficient text for detection
- GIVEN a document with very short text (few words)
- AND neither Dutch nor English word counts exceed 5
- WHEN language detection runs
- THEN null is returned (language undetermined)

#### Scenario: Skip detection for pre-populated field
- GIVEN a document object already has a `language` field set to "nl"
- WHEN metadata enrichment runs
- THEN language detection is skipped
- AND the existing value is preserved

#### Scenario: Feature toggle disabled
- GIVEN `enable_language_detection` is set to "0" in admin settings
- WHEN an ObjectCreatedEvent fires
- THEN language detection is skipped entirely

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-001 | Detect document language from text content | MUST | Implemented |
| META-002 | Support Dutch (nl) using 10 common Dutch words | MUST | Implemented |
| META-003 | Support English (en) using 10 common English words | MUST | Implemented |
| META-004 | Require minimum threshold (>5 matches) before declaring language | MUST | Implemented |
| META-005 | Return null if language undetermined | MUST | Implemented |
| META-006 | Skip detection if object already has `language` populated | MUST | Implemented |
| META-007 | Toggle via admin settings (`enable_language_detection`) | MUST | Implemented |

### Requirement: Keyword Extraction

**ID:** REQ-META-02
**Priority:** Must

Extract top keywords from document text using word frequency analysis with stop word filtering.

#### Scenario: Extract keywords from a legal document
- GIVEN a document about a contract with terms like "contract", "party", "obligation" appearing frequently
- WHEN keyword extraction runs
- THEN the top 10 most frequent non-stop words are returned
- AND common stop words (the, de, het, and, en, etc.) are filtered out

#### Scenario: Keywords ranked by frequency
- GIVEN a document where "budget" appears 15 times and "plan" appears 8 times
- WHEN keywords are extracted
- THEN "budget" ranks higher than "plan" in the results

#### Scenario: Maximum 10 keywords
- GIVEN a document with 50 unique non-stop words
- WHEN keywords are extracted
- THEN exactly 10 keywords are returned (the top 10 by frequency)

#### Scenario: Skip extraction for pre-populated field
- GIVEN a document object already has a `keywords` field with values
- WHEN metadata enrichment runs
- THEN keyword extraction is skipped
- AND the existing keywords are preserved

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-010 | Extract top keywords from document text | MUST | Implemented |
| META-011 | Filter out Dutch and English stop words | MUST | Implemented |
| META-012 | Rank keywords by frequency (most frequent first) | MUST | Implemented |
| META-013 | Return maximum 10 keywords | MUST | Implemented |
| META-014 | Skip if `keywords` already populated | MUST | Implemented |
| META-015 | Toggle via admin settings (`enable_keyword_extraction`) | MUST | Implemented |

### Requirement: Topic Classification

**ID:** REQ-META-03
**Priority:** Must

Classify documents into topic categories using keyword matching against predefined vocabularies.

#### Scenario: Classify a financial document
- GIVEN a document containing words "invoice", "payment", "budget" frequently
- WHEN topic classification runs
- THEN the document is classified as "financial"

#### Scenario: Classify a legal document
- GIVEN a document containing words "contract", "agreement", "law", "court"
- WHEN topic classification runs
- THEN the document is classified as "legal"

#### Scenario: No matching topic
- GIVEN a document about gardening with no words matching any topic vocabulary
- WHEN topic classification runs
- THEN null is returned (no topic matches)

#### Scenario: Highest score wins
- GIVEN a document with 5 legal keywords and 3 financial keywords
- WHEN topic scores are calculated
- THEN "legal" wins because it has the highest score

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-020 | Classify documents into topic categories | MUST | Implemented |
| META-021 | Support topics: legal, financial, medical, technical | MUST | Implemented |
| META-022 | Classification uses keyword matching with predefined vocabularies | MUST | Implemented |
| META-023 | Return topic with highest score | MUST | Implemented |
| META-024 | Return null if no topic matches | MUST | Implemented |
| META-025 | Skip if `topic` already populated | MUST | Implemented |
| META-026 | Toggle via admin settings (`enable_topic_classification`) | MUST | Implemented |

### Requirement: Document Type Standardization

**ID:** REQ-META-04
**Priority:** Must

Standardize document type strings to canonical categories by mapping file extensions and names.

#### Scenario: Standardize "docx" to "word"
- GIVEN a document with documentType "docx"
- WHEN type standardization runs
- THEN the type is normalized to "word"

#### Scenario: Map spreadsheet extensions
- GIVEN documents with types "xls", "xlsx", "excel"
- WHEN type standardization runs
- THEN all are normalized to "spreadsheet"

#### Scenario: Unknown type passed through
- GIVEN a document with documentType "custom_format"
- WHEN type standardization runs
- THEN "custom_format" is returned unchanged

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-030 | Standardize document type strings to canonical categories | MUST | Implemented |
| META-031 | Canonical types: pdf, word, spreadsheet, presentation, text, html, image | MUST | Implemented |
| META-032 | Map extensions: doc/docx -> word, xls/xlsx -> spreadsheet, ppt/pptx -> presentation | MUST | Implemented |
| META-033 | Unknown types passed through unchanged | MUST | Implemented |

### Requirement: Date Normalization

**ID:** REQ-META-05
**Priority:** Must

Normalize date fields to ISO 8601 format across standard field names.

#### Scenario: Normalize created date
- GIVEN an object with `created: "March 15, 2024"`
- WHEN date normalization runs
- THEN the field is normalized to ISO 8601 format: "2024-03-15T00:00:00+00:00"

#### Scenario: Unparseable date skipped
- GIVEN an object with `date: "not a real date"`
- WHEN date normalization runs
- THEN the field is skipped gracefully (debug log, no exception)
- AND other date fields continue to be processed

#### Scenario: Multiple date fields normalized
- GIVEN an object with both `created` and `modified` fields
- WHEN date normalization runs
- THEN both fields are normalized independently

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-040 | Normalize date fields to ISO 8601 format | MUST | Implemented |
| META-041 | Normalize: created, modified, date, creationDate, modificationDate | MUST | Implemented |
| META-042 | Skip unparseable dates gracefully (log debug, no throw) | MUST | Implemented |

### Requirement: Event-Driven Enrichment

**ID:** REQ-META-06
**Priority:** Must

Automatically enrich metadata when objects are created or updated in OpenRegister, with content change detection on updates.

#### Scenario: Auto-enrich on object creation
- GIVEN a document object is created in OpenRegister with text content
- AND all enrichment features are enabled
- WHEN ObjectCreatedEvent is dispatched
- THEN DocuDeskEventListener processes the event
- AND MetadataService enhances the metadata
- AND enriched fields are saved back to the object

#### Scenario: Re-enrich on content update
- GIVEN a document object exists in OpenRegister
- WHEN the `content` field is updated and ObjectUpdatedEvent fires
- THEN the event listener detects the content change
- AND metadata is re-enriched based on the new content

#### Scenario: Skip re-enrichment on non-content update
- GIVEN a document object exists
- WHEN a non-content field (e.g., status) is updated
- THEN the listener checks content fields: content, text, description, title
- AND finds no changes, so enrichment is skipped
- AND a debug log records the skip

#### Scenario: Object deletion logged
- GIVEN a document object is deleted from OpenRegister
- WHEN ObjectDeletedEvent fires
- THEN the deletion is logged
- AND no additional processing occurs

#### Scenario: All features disabled
- GIVEN all enrichment features are toggled off in admin settings
- WHEN an ObjectCreatedEvent fires
- THEN the listener checks settings and finds all features disabled
- AND no enrichment processing occurs

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-050 | Auto-enrich on ObjectCreatedEvent | MUST | Implemented |
| META-051 | Re-enrich on ObjectUpdatedEvent when content fields changed | MUST | Implemented |
| META-052 | Content change detection checks: content, text, description, title | MUST | Implemented |
| META-053 | Skip re-enrichment if no content fields changed | MUST | Implemented |
| META-054 | Log object deletion events without processing | MUST | Implemented |
| META-055 | Check admin settings before running enrichment | MUST | Implemented |
| META-056 | Save enriched metadata back to OpenRegister | MUST | Implemented |

### Requirement: API On-Demand Enrichment

**ID:** REQ-META-07
**Priority:** Must

On-demand metadata enrichment via REST API for specific objects.

#### Scenario: Trigger enrichment for an object
- GIVEN a document object in OpenRegister with objectId, register, and schema
- WHEN POST /api/metadata/enrich is called with these identifiers
- THEN metadata is enhanced
- AND enriched fields are saved back to the object
- AND the response includes the list of enriched field names

#### Scenario: Enrichment with direct data
- GIVEN objectData is provided in the request body
- WHEN enrichment is triggered
- THEN the provided data is used directly (avoids extra lookup)

#### Scenario: No enrichment needed
- GIVEN an object with all metadata fields already populated
- WHEN enrichment is triggered
- THEN no fields are enriched
- AND a success message indicates nothing was needed

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-060 | On-demand enrichment via `POST /api/metadata/enrich` | MUST | Implemented |
| META-061 | Requires objectId, register, and schema in request | MUST | Implemented |
| META-062 | Optionally accepts objectData for direct enrichment | MUST | Implemented |
| META-063 | Returns enriched fields list and updated object data | MUST | Implemented |
| META-064 | Returns success if no enrichment needed | MUST | Implemented |

### Requirement: Duplicated ObjectService Resolution

**ID:** REQ-META-08
**Priority:** Must

MetadataService has its own private getObjectService() duplicating the pattern found in other services.

#### Scenario: MetadataService resolves ObjectService
- GIVEN MetadataService needs to save enriched metadata
- WHEN it calls its private `getObjectService()`
- THEN the same `getInstalledApps()` + `container->get()` pattern is used
- AND this duplicates SettingsService::getObjectService() (public)

#### Scenario: Pattern exists in 3+ services
- GIVEN MetadataService, ConsentService, and ObjectionDeadlineChecker all have this pattern
- WHEN the code is inspected
- THEN identical code exists in each
- AND consolidation to SettingsService::getObjectService() is recommended

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-070 | MetadataService has private `getObjectService()` duplicating SettingsService | MUST | Implemented |
| META-071 | Same `getInstalledApps()` + `container->get()` pattern used | MUST | Implemented |

### Requirement: Event Listener Service Resolution

**ID:** REQ-META-09
**Priority:** Must

The event listener resolves services via `\OC::$server->get()` at handle time rather than constructor DI to avoid circular dependencies.

#### Scenario: Lazy service resolution at handle time
- GIVEN DocuDeskEventListener has an empty constructor
- WHEN an event is dispatched
- THEN services are resolved via `\OC::$server->get()` inside handle()
- AND this avoids premature service resolution during app registration

#### Scenario: Error handling with nested try/catch
- GIVEN an exception occurs during event processing
- WHEN the outer catch block runs
- THEN it attempts to re-resolve the logger from `\OC::$server`
- AND if the logger resolution also fails, the error is silently swallowed

#### Scenario: Enrichment failures are non-fatal
- GIVEN metadata enrichment fails during event handling
- WHEN the exception is caught
- THEN the error is logged
- AND the event is considered "handled" (non-fatal)
- AND the system continues operating

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-072 | Event listener resolves services via `\OC::$server->get()` at handle time | MUST | Implemented |
| META-073 | Empty constructor avoids circular dependency | MUST | Implemented |
| META-074 | `\OC::$server` usage reduces testability (anti-pattern) | MUST | Bug |
| META-075 | ConsentService is NOT imported in the event listener | MUST | Verified |
| META-076 | Event handler errors caught and logged without propagating | MUST | Implemented |
| META-077 | Logger re-resolved during error handling; failures silently swallowed | MUST | Implemented |
| META-078 | Enrichment failures are non-fatal | MUST | Implemented |
| META-079 | Nested try/catch re-resolves logger for error scope safety | MUST | Implemented |

### Requirement: Text Content Extraction from Object Data

**ID:** REQ-META-10
**Priority:** Must

MetadataService extracts text content from object data fields in a defined priority order for analysis.

#### Scenario: Extract from content field
- GIVEN an object with `content: "This is a legal contract..."`
- WHEN text extraction runs
- THEN the content field is used for analysis

#### Scenario: Fallback to text field
- GIVEN an object with no `content` field but `text: "Financial report..."`
- WHEN text extraction runs
- THEN the text field is used as fallback

#### Scenario: Fallback to description field
- GIVEN an object with no content or text but `description: "Medical document..."`
- WHEN text extraction runs
- THEN the description field is used as second fallback

#### Scenario: No text content available
- GIVEN an object with no content, text, or description fields
- WHEN text extraction runs
- THEN an empty string is returned
- AND text-based enrichment (language, keywords, topic) is skipped

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-080 | Extract text from object fields in priority: content, text, description | MUST | Implemented |
| META-081 | Skip text-based enrichment when no text content available | MUST | Implemented |

## Data Model

### Enrichment Output Fields

| Field | Type | Description |
|-------|------|-------------|
| language | string | ISO language code (nl, en, or null) |
| keywords | array[string] | Top 10 keywords by frequency |
| topic | string | Topic category (legal, financial, medical, technical) |
| documentType | string | Standardized document type |
| created, modified, etc. | string (ISO 8601) | Normalized date fields |

### Topic Vocabularies

| Topic | Keywords |
|-------|----------|
| legal | contract, agreement, law, legal, court, judge |
| financial | invoice, payment, budget, financial, account, money |
| medical | patient, diagnosis, treatment, medical, health, doctor |
| technical | system, software, technical, code, development, api |

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/metadata/enrich` | Trigger metadata enrichment for an object |

## Dependencies

- **OpenRegister ObjectService**: Find and save document objects
- **OpenRegister Events**: ObjectCreatedEvent, ObjectUpdatedEvent, ObjectDeletedEvent
- **SettingsService**: Feature toggle checking
- **TextAnalysisService**: Language detection, keyword extraction, topic classification
- **LanguageClassifier**: Word frequency-based language and topic analysis
- **DocumentTextExtractor**: Text extraction from object fields and date normalization

### Current Implementation Status
- **Fully implemented** with file paths:
  - `lib/Service/MetadataService.php` -- core enrichment orchestration
  - `lib/Service/TextAnalysisService.php` -- keyword extraction and type standardization
  - `lib/Service/LanguageClassifier.php` -- language detection and topic classification
  - `lib/Service/DocumentTextExtractor.php` -- text extraction and date normalization
  - `lib/Controller/MetadataController.php` -- REST API endpoint
  - `lib/EventListener/DocuDeskEventListener.php` -- event handling
  - `lib/EventListener/DocuDeskEventHandler.php` -- event dispatch logic
  - `lib/EventListener/EnrichmentRunner.php` -- enrichment execution

### Standards & References
- **ISO 639-1**: Language codes (nl, en)
- **Dublin Core (ISO 15836)**: Metadata fields align with Dublin Core
- **ISO 8601**: Date normalization format
- **DCAT-AP**: EU metadata requirements
- **OWMS**: Dutch government metadata standard

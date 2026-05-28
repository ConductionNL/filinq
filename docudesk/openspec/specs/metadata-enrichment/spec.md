---
status: reviewed
---

# Metadata Enrichment

## Purpose

Provides automatic metadata enrichment for documents stored in OpenRegister. When documents are created or updated, DocuDesk detects language, extracts keywords, classifies topics, standardizes document types, and normalizes date fields. Enrichment runs both on-demand via the API and automatically via the OpenRegister event listener. All processing is performed locally using heuristic algorithms -- no external NLP services are required.

## Requirements

### Language Detection

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-001 | Detect document language from text content | MUST | Implemented |
| META-002 | Support Dutch (nl) language detection using common Dutch word frequency | MUST | Implemented |
| META-003 | Support English (en) language detection using common English word frequency | MUST | Implemented |
| META-004 | Require a minimum word count threshold (>5 matches) before declaring a language | MUST | Implemented |
| META-005 | Return null if language cannot be determined confidently | MUST | Implemented |
| META-006 | Skip language detection if the object already has a `language` field populated | MUST | Implemented |
| META-007 | Language detection can be toggled on/off via admin settings (`enable_language_detection`) | MUST | Implemented |

### Keyword Extraction

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-010 | Extract top keywords from document text content | MUST | Implemented |
| META-011 | Filter out Dutch and English stop words before ranking | MUST | Implemented |
| META-012 | Rank keywords by frequency (most frequent first) | MUST | Implemented |
| META-013 | Return a maximum of 10 keywords | MUST | Implemented |
| META-014 | Skip keyword extraction if the object already has a `keywords` field populated | MUST | Implemented |
| META-015 | Keyword extraction can be toggled on/off via admin settings (`enable_keyword_extraction`) | MUST | Implemented |

### Topic Classification

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-020 | Classify documents into topic categories based on text content | MUST | Implemented |
| META-021 | Support topic categories: `legal`, `financial`, `medical`, `technical` | MUST | Implemented |
| META-022 | Classification uses keyword matching with predefined topic vocabularies | MUST | Implemented |
| META-023 | Return the topic with the highest keyword match score | MUST | Implemented |
| META-024 | Return null if no topic matches (all scores are 0) | MUST | Implemented |
| META-025 | Skip topic classification if the object already has a `topic` field populated | MUST | Implemented |
| META-026 | Topic classification can be toggled on/off via admin settings (`enable_topic_classification`) | MUST | Implemented |

### Document Type Standardization

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-030 | Standardize document type strings to canonical categories | MUST | Implemented |
| META-031 | Supported canonical types: `pdf`, `word`, `spreadsheet`, `presentation`, `text`, `html`, `image` | MUST | Implemented |
| META-032 | Map common extensions and names (e.g., doc/docx -> word, xls/xlsx -> spreadsheet) | MUST | Implemented |
| META-033 | Pass through unknown document types unchanged | MUST | Implemented |

### Date Normalization

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-040 | Normalize date fields to ISO 8601 format | MUST | Implemented |
| META-041 | Normalize fields: created, modified, date, creationDate, modificationDate | MUST | Implemented |
| META-042 | Skip fields that fail date parsing gracefully (log debug, do not throw) | MUST | Implemented |

### Event-Driven Enrichment

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-050 | Automatically enrich metadata when an object is created in OpenRegister (ObjectCreatedEvent) | MUST | Implemented |
| META-051 | Re-enrich metadata when an object is updated in OpenRegister (ObjectUpdatedEvent) and content fields have changed | MUST | Implemented |
| META-052 | Content change detection checks fields: content, text, description, title | MUST | Implemented |
| META-053 | Skip re-enrichment if no content fields have changed on update | MUST | Implemented |
| META-054 | Log object deletion events (ObjectDeletedEvent) without additional processing | MUST | Implemented |
| META-055 | Check admin settings to determine which enrichment features are enabled before running | MUST | Implemented |
| META-056 | Save enriched metadata back to OpenRegister via MetadataService::saveEnrichedMetadata() | MUST | Implemented |

### API Enrichment

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-060 | On-demand metadata enrichment via `POST /api/metadata/enrich` | MUST | Implemented |
| META-061 | Requires objectId, register, and schema in request body | MUST | Implemented |
| META-062 | Optionally accepts objectData for direct enrichment (avoids extra lookup) | MUST | Implemented |
| META-063 | Returns enriched fields list and updated object data | MUST | Implemented |
| META-064 | Returns success message if no enrichment is needed | MUST | Implemented |

## Data Model

### Enrichment Input

Text content is extracted from object data fields in priority order:
1. `content`
2. `text`
3. `description`

### Enrichment Output Fields

| Field | Type | Description |
|-------|------|-------------|
| language | string | ISO language code (`nl`, `en`, or null) |
| keywords | array[string] | Top 10 keywords by frequency |
| topic | string | Topic category (`legal`, `financial`, `medical`, `technical`) |
| documentType | string | Standardized document type |
| created, modified, etc. | string (ISO 8601) | Normalized date fields |

### Topic Vocabularies

| Topic | Keywords |
|-------|----------|
| legal | contract, agreement, law, legal, court, judge |
| financial | invoice, payment, budget, financial, account, money |
| medical | patient, diagnosis, treatment, medical, health, doctor |
| technical | system, software, technical, code, development, api |

## User Interface

Metadata enrichment has no dedicated UI view. It operates:
1. Automatically via the event listener (invisible to the user)
2. On-demand via the API endpoint
3. Configurable via admin settings toggles (see admin-settings spec)

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/metadata/enrich` | Trigger metadata enrichment for an object |

## Scenarios

### Auto-Enrich on Object Creation

```
GIVEN a document object is created in OpenRegister with text content
AND language detection, keyword extraction, and topic classification are all enabled
WHEN the ObjectCreatedEvent is dispatched
THEN the DocuDeskEventListener receives the event
AND MetadataService::enhanceMetadata() analyzes the text
AND detected language, keywords, and topic are saved back to the object
```

### Re-Enrich on Content Update

```
GIVEN a document object exists in OpenRegister
WHEN the content field is updated and ObjectUpdatedEvent fires
THEN the event listener detects the content change
AND metadata is re-enriched based on the new content
AND updated fields are saved back to OpenRegister
```

### Skip Re-Enrichment on Non-Content Update

```
GIVEN a document object exists in OpenRegister
WHEN a non-content field (e.g., status) is updated and ObjectUpdatedEvent fires
THEN the event listener detects no content change
AND metadata re-enrichment is skipped
AND a debug log message is recorded
```

### API-Triggered Enrichment

```
GIVEN a document object in OpenRegister with objectId, register, and schema
WHEN POST /api/metadata/enrich is called with the object identifiers and data
THEN the metadata is enhanced
AND the enriched fields are saved back to the object
AND the response includes the list of enriched field names
```

### Enrichment with All Features Disabled

```
GIVEN all three enrichment features are disabled in admin settings
WHEN an ObjectCreatedEvent fires
THEN the event listener checks settings
AND no enrichment processing occurs
```

## Internal Implementation Details

### Duplicated getObjectService() in MetadataService (Gap 10)

MetadataService has its own private `getObjectService()` method that duplicates the same pattern found in ConsentService and SettingsService:

```php
private function getObjectService(): \OCA\OpenRegister\Service\ObjectService
{
    if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
        return $this->container->get('OCA\OpenRegister\Service\ObjectService');
    }
    throw new \RuntimeException('OpenRegister service is not available.');
}
```

This identical code exists in 3 services:
1. **MetadataService** (private) -- used by `saveEnrichedMetadata()`
2. **ConsentService** (private) -- used by all consent CRUD operations
3. **SettingsService** (public) -- used by the controller and available for reuse

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-070 | MetadataService has its own private `getObjectService()` duplicating SettingsService pattern | MUST | Implemented |
| META-071 | The duplicated method uses the same `getInstalledApps()` + `container->get()` pattern | MUST | Implemented |

**Recommended fix**: Inject SettingsService into MetadataService and use `SettingsService::getObjectService()` (public method) instead of maintaining a private copy.

### Event Listener Service Resolution via OC::$server (Gap 16)

The `DocuDeskEventListener` resolves all its dependencies via `\OC::$server->get()` inside the `handle()` method rather than through constructor dependency injection:

```php
public function __construct()
{
    // Empty constructor - services are retrieved from the server container.
}

public function handle(Event $event): void
{
    $logger = \OC::$server->get(LoggerInterface::class);
    $metadataService = \OC::$server->get(MetadataService::class);
    $settingsService = \OC::$server->get(SettingsService::class);
    // ...
}
```

**This is an anti-pattern** because:
- It bypasses the Nextcloud DI container's constructor injection
- Makes the class harder to unit test (cannot mock dependencies via constructor)
- Relies on the global `\OC::$server` static accessor

**Likely reason**: The empty constructor avoids circular dependency issues. Event listeners are registered early in the app lifecycle, and injecting MetadataService/SettingsService via constructor might trigger premature resolution of OpenRegister services (which may not be initialized yet). By resolving at handle-time, the listener ensures all services are fully booted.

**Note**: The listener only imports `MetadataService` and `SettingsService`. `ConsentService` is NOT imported or referenced in the event listener.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-072 | Event listener resolves services via `\OC::$server->get()` at handle time, not via constructor DI | MUST | Implemented |
| META-073 | Empty constructor is intentional to avoid circular dependency during app registration | MUST | Implemented |
| META-074 | `\OC::$server` usage is an anti-pattern that reduces testability | MUST | Bug |
| META-075 | ~~ConsentService is imported but never used in the event listener~~ ConsentService is NOT imported in the event listener -- only MetadataService and SettingsService are imported | MUST | Verified (not dead code) |

### Nested Try/Catch Error Handling in Event Listener (Gap 17)

The event listener uses a nested try/catch pattern where logging failures are silently swallowed:

```php
public function handle(Event $event): void
{
    try {
        // Main event processing...
    } catch (\Exception $e) {
        try {
            $logger = \OC::$server->get(LoggerInterface::class);
            $logger->error('DocuDesk: Error in event handler', [...]);
        } catch (\Exception $logException) {
            // Silently fail if logging fails.
        }
    }
}
```

**Behavior**:
1. **Outer try**: Catches any exception from event processing (service resolution, metadata enrichment, etc.)
2. **Inner try**: Attempts to log the error. Re-resolves the logger from `\OC::$server` because it may not have been resolved yet (the error could have occurred during initial service resolution)
3. **Inner catch**: Silently swallows logging failures -- if the logger itself cannot be resolved, the error is completely lost

**Additionally**, within `handleObjectCreated()` and `handleObjectUpdated()`, metadata enrichment failures are caught and logged but do NOT prevent the event from being considered "handled":

```php
} catch (\Exception $e) {
    $logger->error('DocuDesk: Failed to enrich metadata for new object', [...]);
}
```

This means enrichment failures are non-fatal and the system continues operating.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| META-076 | Event handler errors are caught and logged without propagating to the event dispatcher | MUST | Implemented |
| META-077 | If the logger cannot be resolved during error handling, the error is silently swallowed | MUST | Implemented |
| META-078 | Metadata enrichment failures within event handlers are non-fatal (logged, not thrown) | MUST | Implemented |
| META-079 | The nested try/catch re-resolves the logger because it may not have been resolved in the outer scope | MUST | Implemented |

## Dependencies

- **OpenRegister ObjectService**: Find and save document objects
- **OpenRegister Events**: ObjectCreatedEvent, ObjectUpdatedEvent, ObjectDeletedEvent
- **SettingsService**: Check enabled enrichment feature toggles
- **Nextcloud IAppConfig**: Enrichment feature toggle persistence
- **Nextcloud IAppManager**: Checking OpenRegister installation status (in duplicated getObjectService)
- **PSR ContainerInterface**: Lazy service resolution (in duplicated getObjectService)
- **\OC::$server**: Global service container used by event listener for runtime service resolution

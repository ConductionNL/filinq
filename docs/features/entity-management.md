# Entity Management

DocuDesk provides advanced entity management capabilities to handle detected sensitive information across multiple documents. This system ensures consistent handling of entities and provides detailed tracking and anonymization controls.

## Overview

The entity management system consists of:
- **Entity Objects**: Centralized storage for detected entities
- **Entity Processing**: Automatic deduplication and enhancement
- **Entity Statistics**: Tracking occurrence counts and confidence scores
- **Anonymization Control**: Per-entity anonymization settings stored on reports
- **Robust Error Handling**: Comprehensive error handling and fallback mechanisms
- **Performance Optimization**: Automatic skipping of anonymized files to prevent unnecessary processing
- **Frontend Interface**: Comprehensive web interface for entity management

## Entity Objects

Entity objects are stored separately from reports and contain:

- `text`: The actual text content of the entity
- `entityType`: The type of entity (PERSON, EMAIL_ADDRESS, etc.)
- `occurrenceCount`: Number of times this entity has been detected
- `averageConfidence`: Average confidence score across all detections
- `firstDetected`: Timestamp of first detection
- `lastDetected`: Timestamp of most recent detection

## Entity Processing

When a document is processed:

1. **Text Extraction**: Document content is extracted
2. **Entity Detection**: Presidio analyzes the text for sensitive entities
3. **Entity Deduplication**: Identical entities are merged
4. **Statistics Update**: Occurrence counts and confidence scores are updated
5. **Report Association**: Entities are linked to the report

## Entity Types

DocuDesk supports detection of various entity types:

- **PERSON**: Names of individuals
- **EMAIL_ADDRESS**: Email addresses
- **PHONE_NUMBER**: Phone numbers
- **CREDIT_CARD**: Credit card numbers
- **IBAN_CODE**: International Bank Account Numbers
- **ORGANIZATION**: Organization names
- **LOCATION**: Geographic locations
- **IP_ADDRESS**: IP addresses
- **URL**: Web addresses

## Frontend Interface

### Entities Overview

The entities interface provides:

- **Entity List**: Table view of all detected entities
- **Filtering**: Filter by entity type, confidence level
- **Statistics**: Overview of entity counts and types
- **Search**: Find specific entities by text content

### Entity Details

Individual entity views show:

- **Entity Information**: Text content and type
- **Statistics**: Occurrence count, confidence scores, detection dates
- **Related Reports**: List of reports containing this entity
- **Actions**: Edit or delete entity

### Sidebars

Two sidebar types are available:

1. **Entities Sidebar**: Overview statistics and filters
2. **Entity Sidebar**: Details for a specific entity

## Performance Optimizations

### Anonymized File Skipping

To prevent unnecessary processing overhead, the system automatically skips creating reports for files that end with '_anonymized'. This optimization:

- Reduces server load
- Prevents duplicate processing
- Improves overall system performance

The check is implemented at multiple levels:
- File event listener level (primary check)
- Report service level (safeguard)

### Error Handling

The system includes comprehensive error handling:

- **Configuration Consistency**: Ensures proper app name usage across services
- **Entity Creation**: Handles race conditions in entity creation
- **Report Configuration**: Maintains proper ObjectService configuration
- **Logging**: Detailed logging for debugging and monitoring

## Configuration

Entity management is configured through app settings:

```php
// Entity configuration
$entityRegisterType = $this->appConfig->getValueString('docudesk', 'entity_register', 'document');
$entitySchemaType = $this->appConfig->getValueString('docudesk', 'entity_schema', 'entity');
```

## API Endpoints

Entity management provides REST API endpoints:

- `GET /entity` - List all entities
- `GET /entity/{id}` - Get specific entity
- `PUT /entity/{id}` - Update entity
- `DELETE /entity/{id}` - Delete entity
- `GET /entity/{id}/used` - Get reports containing entity

## Best Practices

1. **Regular Monitoring**: Monitor entity detection accuracy
2. **Performance Tuning**: Adjust confidence thresholds as needed
3. **Data Cleanup**: Regularly review and clean up false positives
4. **Privacy Compliance**: Ensure entity handling meets privacy requirements

## Troubleshooting

### Common Issues

1. **Entity Not Found Errors**: Usually caused by configuration inconsistencies
2. **Performance Issues**: Check for processing of anonymized files
3. **Missing Entities**: Verify Presidio service connectivity

### Debug Information

Enable debug logging to see detailed entity processing information:

```php
$this->logger->debug('Entity processing details', [
    'entityId' => $entityId,
    'entityType' => $entityType,
    'confidence' => $confidence
]);
```

## Report Entity Structure

Entities in reports now include enhanced information:

- `text`: The detected text
- `score`: Confidence score from Presidio
- `entityType`: Type of entity detected
- `start` and `end`: Position in document
- `key`: Unique key for anonymization
- `anonymize`: Boolean flag for anonymization control
- `entityObjectId`: Reference to the centralized entity object (null if creation failed)

## Configuration Consistency

All services now use consistent configuration naming:
- App name: `'docudesk'` (lowercase) across all services
- Entity register: Configurable via `entity_register` setting
- Entity schema: Configurable via `entity_schema` setting

## Anonymization Integration

Anonymization results are now stored directly on report objects in the `anonymization` property:

```json
{
  'anonymization': {
    'fileHash': 'abc123...',
    'anonymizedFileName': 'document_anonymized.docx',
    'anonymizedFilePath': '/path/to/anonymized/file',
    'replacements': [...],
    'startTime': 1234567890.123,
    'endTime': 1234567890.456,
    'processingTime': 0.333,
    'status': 'completed',
    'message': 'Anonymization completed successfully'
  }
}
```

## Entity Deduplication

The system automatically deduplicates entities by text content within each document, keeping the highest confidence score when duplicates are found.

## Privacy by Design

- Entities default to `anonymize: true` for security-first approach
- Individual entity anonymization can be toggled per document
- Entity objects track statistics without storing sensitive content context

## Benefits

1. **Consistency**: Same entities across documents are handled uniformly
2. **Efficiency**: Deduplication reduces storage and processing overhead  
3. **Intelligence**: Statistics help identify frequently occurring entities
4. **Control**: Fine-grained anonymization control per entity
5. **Tracking**: Complete audit trail of entity detections

## API Integration

The system integrates with:
- **Presidio**: For entity detection
- **OpenRegister**: For entity object storage
- **Reporting Service**: For enhanced entity processing
- **Anonymization Service**: For selective anonymization 
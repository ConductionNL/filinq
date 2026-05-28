---
sidebar_position: 6
title: Anonymization Logs
---

# Anonymization Logs

DocuDesk provides comprehensive anonymization capabilities to help you protect personal data in your documents. This page explains how anonymization logs work and how they can help you maintain GDPR compliance.

## Overview

The Anonymization Logs system in DocuDesk enables you to:

- Track all anonymization operations performed on documents
- Store detailed information about detected personal data
- Maintain a record of text replacements for potential de-anonymization
- Ensure GDPR compliance through proper documentation
- Generate audit trails for privacy-related actions

## Anonymization Object Structure

The `Anonymization` object is the core component for tracking document anonymization. It contains detailed information about the anonymization process, including the original document, anonymized document, and replacements made.

### Key Properties

| Property | Type | Description |
|----------|------|-------------|
| id | string | Unique identifier for the anonymization log |
| nodeId | number | Nextcloud node ID of the original document |
| fileHash | string | Hash of the file content for change detection |
| originalFileName | string | Name of the original document |
| anonymizedFileName | string | Name of the anonymized document |
| anonymizedFilePath | string | Path to the anonymized document |
| status | string | Status of the anonymization operation ('pending', 'processing', 'completed', 'failed') |
| message | string | Message about the anonymization process |
| entities | array | List of entities found during anonymization |
| replacements | array | List of entity replacements made during anonymization |
| startTime | number | Timestamp when the anonymization process started |
| endTime | number | Timestamp when the anonymization process ended |
| processingTime | number | Duration of the anonymization process in seconds |

### Entity Object

Each entity found during anonymization has the following structure:

```json
{
  "entityType": "PERSON",
  "text": "John Doe",
  "score": 0.95,
  "startPosition": 10,
  "endPosition": 18
}
```

### Replacement Object

Each replacement made during anonymization has the following structure:

```json
{
  "entityType": "PERSON",
  "originalText": "John Doe",
  "replacementText": "[PERSON: abc123]",
  "key": "abc123",
  "start": 10,
  "end": 18
}
```

## Anonymization Process

When a document is submitted for anonymization, DocuDesk follows these steps:

1. **Document Analysis**: The document is analyzed to detect personal data
2. **Entity Detection**: Personal data entities are identified and categorized
3. **Replacement Generation**: Replacement text is generated for each entity
4. **Text Replacement**: The original text is replaced with anonymized text
5. **Key Generation**: A secure key is generated for each entity for potential de-anonymization
6. **Log Creation**: An anonymization log is created with all relevant information
7. **Output Generation**: An anonymized document is generated

## Entity Types

DocuDesk can detect and anonymize various types of personal data entities:

- **PERSON**: Names of individuals
- **LOCATION**: Physical locations (cities, countries, addresses)
- **ORGANIZATION**: Names of organizations
- **DATE**: Dates that could identify individuals
- **ID**: Identification numbers (passport, SSN, etc.)
- **EMAIL**: Email addresses
- **PHONE**: Phone numbers
- **ADDRESS**: Physical addresses
- **FINANCIAL**: Financial information (bank accounts, credit cards, etc.)
- **MEDICAL**: Medical information
- **OTHER**: Other types of personal data

## Presidio Integration

DocuDesk integrates with Microsoft Presidio for entity detection and anonymization. The response from Presidio is processed and stored in the anonymization log. Here's an example of a Presidio response:

```json
{
    "original_text": "Mijn naam is Jan de Vries, mijn BSN is 123456789 en ik woon in Amsterdam.",
    "anonymized_text": "Mijn naam is [PERSOON], mijn [PERSOON] is 123456789 en ik woon in [LOCATIE].",
    "entities_found": [
        {
            "entity_type": "PERSON",
            "text": "Jan de Vries",
            "score": 0.9999995231628418
        },
        {
            "entity_type": "LOCATION",
            "text": "Amsterdam",
            "score": 0.9999994039535522
        },
        {
            "entity_type": "PERSON",
            "text": "BSN",
            "score": 0.85
        }
    ]
}
```

This response is transformed into the `Anonymization` object, with additional information such as:

- Unique identifiers for each replacement
- Positions of entities in the document
- A secure key for each entity
- Metadata about the document and operation

## Complete Anonymization Example

Here's a complete example of an anonymization object:

```json
{
  "id": "230ea667-4f66-4040-8b9d-c2bfab86282d",
  "nodeId": 12673,
  "fileHash": "293bc95ff577f0d8faaf54477fc45304",
  "originalFileName": "test25.txt",
  "anonymizedFileName": "test25_anonymized.txt",
  "anonymizedFilePath": "/path/to/test25_anonymized.txt",
  "status": "completed",
  "message": "Anonymization completed successfully",
  "entities": [
    {
      "entityType": "PERSON",
      "text": "John Doe",
      "score": 0.95,
      "startPosition": 10,
      "endPosition": 18
    },
    {
      "entityType": "EMAIL",
      "text": "john@example.com",
      "score": 0.98,
      "startPosition": 25,
      "endPosition": 41
    }
  ],
  "replacements": [
    {
      "entityType": "PERSON",
      "originalText": "John Doe",
      "replacementText": "[PERSON: abc123]",
      "key": "abc123",
      "start": 10,
      "end": 18
    },
    {
      "entityType": "EMAIL",
      "originalText": "john@example.com",
      "replacementText": "[EMAIL: def456]",
      "key": "def456",
      "start": 25,
      "end": 41
    }
  ],
  "startTime": 1742178348.186755,
  "endTime": 1742178349.186755,
  "processingTime": 1.0
}
```

## De-anonymization

DocuDesk supports de-anonymization of documents using the replacement keys. This feature is useful in scenarios where:

- The original document is needed for legal proceedings
- The anonymization was too aggressive and removed non-personal data
- The document needs to be shared with authorized parties who require the full information

De-anonymization is a secure process that requires:

1. The anonymized document (identified by its node ID)
2. The anonymization object containing the replacements

## API Endpoints

DocuDesk provides the following API endpoints for managing anonymization logs:

### List Anonymization Logs

```
GET /apps/docudesk/api/v1/anonymization/logs
```

Returns a list of anonymization logs. You can filter the logs by:

- `nodeId`: Filter logs by Nextcloud node ID
- `status`: Filter logs by status

### Create Anonymization Log

```
POST /apps/docudesk/api/v1/anonymization/logs
```

Creates a new anonymization log. You need to specify:

- `nodeId`: Nextcloud node ID of the document to anonymize
- `originalFileName`: Name of the document
- `confidenceThreshold`: (Optional) Confidence threshold for entity detection

### Get Anonymization Log

```
GET /apps/docudesk/api/v1/anonymization/logs/{logId}
```

Returns a specific anonymization log by ID.

### Update Anonymization Log

```
PUT /apps/docudesk/api/v1/anonymization/logs/{logId}
```

Updates a specific anonymization log.

### Get Latest Anonymization Log for Node

```
GET /apps/docudesk/api/v1/anonymization/logs/node/{nodeId}
```

Returns the latest anonymization log for a specific Nextcloud node.

### De-anonymize Document

```
POST /apps/docudesk/api/v1/anonymization/deanonymize
```

De-anonymizes a document using the anonymization log.

## Examples

### Anonymizing a Document

```php
// Get the anonymization service
$anonymizationService = \OC::$server->get(OCA\DocuDesk\Service\AnonymizationService::class);

// Process anonymization for a file node
$fileNode = $rootFolder->get('path/to/document.txt');
$anonymization = $anonymizationService->processAnonymization($fileNode);

// Check anonymization status
if ($anonymization['status'] === 'completed') {
    // Get the anonymized document path
    $anonymizedFilePath = $anonymization['anonymizedFilePath'];
    
    // Check anonymization results
    $totalEntitiesFound = count($anonymization['entities']);
    $totalEntitiesReplaced = count($anonymization['replacements']);
    
    echo "Anonymization completed: Found $totalEntitiesFound entities, replaced $totalEntitiesReplaced entities.";
}
```

### Retrieving Anonymization Data

```php
// Get anonymization data for a file node
$anonymizationService = \OC::$server->get(OCA\DocuDesk\Service\AnonymizationService::class);
$anonymization = $anonymizationService->getAnonymization($fileNode);

// Or retrieve anonymization by ID
$anonymization = $anonymizationService->getAnonymizationById('anonymization-id');
```

## Best Practices

1. **Regular Audits**: Regularly audit anonymization logs to ensure personal data is being properly protected
2. **Confidence Threshold**: Adjust the confidence threshold based on your needs (higher for more precision, lower for more coverage)
3. **Review Anonymized Documents**: Always review automatically anonymized documents
4. **Customize Entity Types**: Configure custom entity recognizers for domain-specific data
5. **Implement Access Controls**: Restrict access to de-anonymization functionality to authorized users only
6. **Retention Policy**: Implement a retention policy for anonymization logs in line with your GDPR compliance requirements

## Integration with Document Reports

The anonymization logs system integrates with DocuDesk's document reports:

- Anonymization results are included in document reports
- Document reports can trigger anonymization operations
- Anonymization logs provide detailed information for privacy compliance reporting

For more information on document reports, see the [Document Reports](./document-reports.md) documentation. 
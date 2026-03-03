---
sidebar_position: 7
title: Entity Recognition Integration
---

# Entity Recognition Integration

DocuDesk integrates with entity recognition engines like Microsoft Presidio for powerful entity recognition and anonymization capabilities. This page explains how DocuDesk processes entity recognition output and transforms it into the AnonymizationLog object.

## Overview

DocuDesk supports integration with various entity recognition engines, with [Microsoft Presidio](https://github.com/microsoft/presidio) as the default implementation. These engines enable DocuDesk to:

- Detect personal data in documents
- Classify entities by type (PERSON, LOCATION, etc.)
- Assign confidence scores to detected entities
- Generate anonymized versions of documents

## Entity Recognition Response Format

When DocuDesk sends a document to an entity recognition engine like Presidio for analysis, it expects a response in the following format:

```json
{
    "text": "Mijn naam is Jan de Hooglander, mijn BSN is 123456789 en ik woon in Amsterdam.",
    "entities_found": [
        {
            "entity_type": "PERSON",
            "text": "Jan de Hoog",
            "score": 0.9999997019767761
        },
        {
            "entity_type": "LOCATION",
            "text": "Amsterdam",
            "score": 0.9999990463256836
        },
        {
            "entity_type": "PERSON",
            "text": "BSN",
            "score": 0.85
        }
    ]
}
```

This response includes:
- The original text that was analyzed
- A list of entities found, each with:
  - Entity type (PERSON, LOCATION, etc.)
  - The text that was identified as that entity
  - A confidence score (0-1) indicating how certain the engine is about the classification

## API Configuration Options

When calling the entity recognition API, you can configure various options:

### Request Parameters

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `text` | string | The text to analyze | (required) |
| `language` | string | The language of the text (ISO code) | `"nl"` |
| `score_threshold` | float | Minimum confidence score (0-1) for entity detection | `0.7` |
| `return_decision_process` | boolean | Whether to return detailed decision process information | `false` |
| `entities` | array | List of entity types to detect | All supported types |
| `allow_list` | object | Dictionary of allowed values for specific entity types | `{}` |
| `deny_list` | object | Dictionary of denied values for specific entity types | `{}` |
| `context` | string | Additional context to improve detection accuracy | `null` |
| `regex_pattern_groups` | array | Custom regex patterns for entity detection | `[]` |

### Example Request

```json
{
    "text": "Mijn naam is Jan de Hooglander, mijn BSN is 123456789 en ik woon in Amsterdam.",
    "language": "nl",
    "score_threshold": 0.7,
    "return_decision_process": false,
    "entities": ["PERSON", "LOCATION", "ID_NUMBER"],
    "allow_list": {
        "LOCATION": ["Amsterdam", "Rotterdam", "Den Haag"]
    },
    "deny_list": {
        "PERSON": ["Test", "Example"]
    }
}
```

### Response Options

The entity recognition engine can return various additional information based on the request parameters:

| Field | Type | Description | When Included |
|-------|------|-------------|---------------|
| `text` | string | The original text that was analyzed | Always |
| `entities_found` | array | List of entities found | Always |
| `language` | string | The detected or specified language | When language detection is enabled |
| `decision_process` | array | Detailed information about the decision process | When `return_decision_process` is `true` |
| `statistics` | object | Performance statistics for the analysis | When statistics are enabled |
| `version` | string | Version of the entity recognition engine | When version info is enabled |

### Extended Response Example

```json
{
    "text": "Mijn naam is Jan de Hooglander, mijn BSN is 123456789 en ik woon in Amsterdam.",
    "language": "nl",
    "entities_found": [
        {
            "entity_type": "PERSON",
            "text": "Jan de Hoog",
            "score": 0.9999997019767761,
            "start": 12,
            "end": 23,
            "analysis_explanation": {
                "recognizer": "NlpEngineRecognizer",
                "pattern_name": "PERSON_PATTERN",
                "pattern_length": 11,
                "original_score": 0.9999997019767761,
                "score": 0.9999997019767761,
                "textual_explanation": "Identified as person name by NLP model"
            }
        },
        {
            "entity_type": "LOCATION",
            "text": "Amsterdam",
            "score": 0.9999990463256836,
            "start": 58,
            "end": 67,
            "analysis_explanation": {
                "recognizer": "NlpEngineRecognizer",
                "pattern_name": "LOCATION_PATTERN",
                "pattern_length": 9,
                "original_score": 0.9999990463256836,
                "score": 0.9999990463256836,
                "textual_explanation": "Identified as location by NLP model and allow list"
            }
        },
        {
            "entity_type": "PERSON",
            "text": "BSN",
            "score": 0.85,
            "start": 30,
            "end": 33,
            "analysis_explanation": {
                "recognizer": "PatternRecognizer",
                "pattern_name": "BSN_PATTERN",
                "pattern_length": 3,
                "original_score": 0.85,
                "score": 0.85,
                "textual_explanation": "Matched BSN identifier pattern"
            }
        }
    ],
    "statistics": {
        "processing_time_ms": 125,
        "entities_count": 3
    },
    "version": "2.0.0"
}
```

## Transformation to AnonymizationLog

DocuDesk transforms the entity recognition response into an `AnonymizationLog` object, which provides more comprehensive tracking of the anonymization process. Here's how the transformation works:

### 1. Entity Mapping

DocuDesk maps the entity recognition engine's entity types to its own standardized entity types:

| Engine Entity Type | DocuDesk Entity Type |
|----------------------|----------------------|
| PERSON               | PERSON               |
| LOCATION             | LOCATION             |
| ORGANIZATION         | ORGANIZATION         |
| NRP                  | ID                   |
| DATE_TIME            | DATE                 |
| EMAIL_ADDRESS        | EMAIL                |
| PHONE_NUMBER         | PHONE                |
| IBAN_CODE            | FINANCIAL            |
| MEDICAL_LICENSE      | MEDICAL              |
| US_SSN               | ID                   |
| US_DRIVER_LICENSE    | ID                   |
| ...                  | ...                  |

### 2. Position Calculation

DocuDesk calculates the position of each entity in the document:

```php
/**
 * Calculates the position of an entity in the document
 *
 * @param string $text The full document text
 * @param string $entityText The entity text
 * @param int $occurrence Which occurrence of the entity to find (0-based)
 * @return array{start_offset: int, end_offset: int, page: int|null}
 */
public function calculatePosition(string $text, string $entityText, int $occurrence = 0): array
{
    // Find all occurrences of the entity text
    $positions = [];
    $offset = 0;
    
    while (($pos = strpos($text, $entityText, $offset)) !== false) {
        $positions[] = $pos;
        $offset = $pos + 1;
    }
    
    // If we found the requested occurrence
    if (isset($positions[$occurrence])) {
        $startOffset = $positions[$occurrence];
        $endOffset = $startOffset + strlen($entityText);
        
        // For multi-page documents, calculate the page number
        $page = null;
        if ($this->pageBreakPositions) {
            $page = $this->calculatePageNumber($startOffset);
        }
        
        return [
            'start_offset' => $startOffset,
            'end_offset' => $endOffset,
            'page' => $page
        ];
    }
    
    // Default return if entity not found
    return [
        'start_offset' => 0,
        'end_offset' => 0,
        'page' => null
    ];
}
```

### 3. Replacement Generation

For each entity, DocuDesk generates a replacement text based on the entity type:

```php
/**
 * Generates replacement text for an entity
 *
 * @param string $entityType The type of entity
 * @param string $originalText The original text to replace
 * @return string The replacement text
 */
public function generateReplacement(string $entityType, string $originalText): string
{
    switch ($entityType) {
        case 'PERSON':
            return '[PERSOON]';
        case 'LOCATION':
            return '[LOCATIE]';
        case 'ORGANIZATION':
            return '[ORGANISATIE]';
        case 'DATE':
            return '[DATUM]';
        case 'ID':
            return '[ID-NUMMER]';
        case 'EMAIL':
            return '[E-MAIL]';
        case 'PHONE':
            return '[TELEFOONNUMMER]';
        case 'ADDRESS':
            return '[ADRES]';
        case 'FINANCIAL':
            return '[FINANCIËLE INFORMATIE]';
        case 'MEDICAL':
            return '[MEDISCHE INFORMATIE]';
        default:
            return '[GEANONIMISEERD]';
    }
}
```

### 4. Anonymization Key Generation

DocuDesk generates a secure key that can be used to de-anonymize the document:

```php
/**
 * Generates a secure anonymization key
 *
 * @param array $entityReplacements The entity replacements
 * @return string The encrypted anonymization key
 */
public function generateAnonymizationKey(array $entityReplacements): string
{
    // Create a mapping of replacements to original text
    $replacementMap = [];
    foreach ($entityReplacements as $replacement) {
        $replacementMap[$replacement['replacement_text']] = $replacement['original_text'];
    }
    
    // Serialize and encrypt the map
    $serialized = json_encode($replacementMap);
    return $this->encryptionService->encrypt($serialized);
}
```

### 5. Creating the AnonymizationLog

Finally, DocuDesk creates an `AnonymizationLog` object with all the information:

```php
/**
 * Creates an AnonymizationLog from an entity recognition response
 *
 * @param array $recognitionResponse The response from the entity recognition engine
 * @param string $nodeId The Nextcloud node ID
 * @param string $fileName The file name
 * @return AnonymizationLog The created anonymization log
 */
public function createAnonymizationLog(array $recognitionResponse, string $nodeId, string $fileName): AnonymizationLog
{
    // Extract data from entity recognition response
    $originalText = $recognitionResponse['text'];
    $entitiesFound = $recognitionResponse['entities_found'];
    
    // Process entities
    $processedEntities = [];
    $entityReplacements = [];
    
    foreach ($entitiesFound as $index => $entity) {
        // Map entity type
        $entityType = $this->mapEntityType($entity['entity_type']);
        
        // Calculate position
        $position = $this->calculatePosition($originalText, $entity['text']);
        
        // Generate replacement
        $replacementText = $this->generateReplacement($entityType, $entity['text']);
        
        // Create entity found object
        $processedEntities[] = [
            'entity_type' => $entityType,
            'text' => $entity['text'],
            'score' => $entity['score'],
            'position' => $position
        ];
        
        // Create entity replacement object
        $entityReplacements[] = [
            'replacement_id' => $this->generateUuid(),
            'entity_type' => $entityType,
            'original_text' => $entity['text'],
            'replacement_text' => $replacementText,
            'position' => $position,
            'confidence' => $entity['score']
        ];
    }
    
    // Generate anonymized text
    $anonymizedText = $this->generateAnonymizedText($originalText, $entityReplacements);
    
    // Generate anonymization key
    $anonymizationKey = $this->generateAnonymizationKey($entityReplacements);
    
    // Create anonymization log
    $log = new AnonymizationLog();
    $log->setId($this->generateUuid());
    $log->setNodeId($nodeId);
    $log->setFileName($fileName);
    $log->setStatus('completed');
    $log->setOriginalText($originalText);
    $log->setAnonymizedText($anonymizedText);
    $log->setEntityReplacements($entityReplacements);
    $log->setEntitiesFound($processedEntities);
    $log->setTotalEntitiesFound(count($processedEntities));
    $log->setTotalEntitiesReplaced(count($entityReplacements));
    $log->setAnonymizationKey($anonymizationKey);
    $log->setStartedAt(new \DateTime());
    $log->setCompletedAt(new \DateTime());
    $log->setCreatedAt(new \DateTime());
    $log->setUpdatedAt(new \DateTime());
    
    return $log;
}
```

## Example: Processing an Entity Recognition Response

Let's walk through a complete example of processing an entity recognition response:

### Input: Entity Recognition Response

```json
{
    "text": "Mijn naam is Jan de Hooglander, mijn BSN is 123456789 en ik woon in Amsterdam.",
    "entities_found": [
        {
            "entity_type": "PERSON",
            "text": "Jan de Hoog",
            "score": 0.9999997019767761
        },
        {
            "entity_type": "LOCATION",
            "text": "Amsterdam",
            "score": 0.9999990463256836
        },
        {
            "entity_type": "PERSON",
            "text": "BSN",
            "score": 0.85
        }
    ]
}
```

### Output: AnonymizationLog

```json
{
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "node_id": "file123",
    "file_name": "document.txt",
    "status": "completed",
    "original_text": "Mijn naam is Jan de Hooglander, mijn BSN is 123456789 en ik woon in Amsterdam.",
    "anonymized_text": "Mijn naam is [PERSOON], mijn [PERSOON] is 123456789 en ik woon in [LOCATIE].",
    "entity_replacements": [
        {
            "replacement_id": "550e8400-e29b-41d4-a716-446655440001",
            "entity_type": "PERSON",
            "original_text": "Jan de Hoog",
            "replacement_text": "[PERSOON]",
            "position": {
                "start_offset": 12,
                "end_offset": 23,
                "page": null
            },
            "confidence": 0.9999997019767761
        },
        {
            "replacement_id": "550e8400-e29b-41d4-a716-446655440002",
            "entity_type": "LOCATION",
            "original_text": "Amsterdam",
            "replacement_text": "[LOCATIE]",
            "position": {
                "start_offset": 58,
                "end_offset": 67,
                "page": null
            },
            "confidence": 0.9999990463256836
        },
        {
            "replacement_id": "550e8400-e29b-41d4-a716-446655440003",
            "entity_type": "PERSON",
            "original_text": "BSN",
            "replacement_text": "[PERSOON]",
            "position": {
                "start_offset": 30,
                "end_offset": 33,
                "page": null
            },
            "confidence": 0.85
        }
    ],
    "entities_found": [
        {
            "entity_type": "PERSON",
            "text": "Jan de Hoog",
            "score": 0.9999997019767761,
            "position": {
                "start_offset": 12,
                "end_offset": 23,
                "page": null
            }
        },
        {
            "entity_type": "LOCATION",
            "text": "Amsterdam",
            "score": 0.9999990463256836,
            "position": {
                "start_offset": 58,
                "end_offset": 67,
                "page": null
            }
        },
        {
            "entity_type": "PERSON",
            "text": "BSN",
            "score": 0.85,
            "position": {
                "start_offset": 30,
                "end_offset": 33,
                "page": null
            }
        }
    ],
    "total_entities_found": 3,
    "total_entities_replaced": 3,
    "anonymization_key": "encrypted-key-data",
    "started_at": "2023-06-15T10:30:00Z",
    "completed_at": "2023-06-15T10:30:05Z",
    "created_at": "2023-06-15T10:30:00Z",
    "updated_at": "2023-06-15T10:30:05Z"
}
```

## Handling Partial Matches

In the example above, the entity recognition engine detected "Jan de Hoog" while the full name was "Jan de Hooglander". DocuDesk handles these partial matches by:

1. Using the exact text and position provided by the entity recognition engine
2. Applying replacements in order from longest to shortest to avoid conflicts
3. Providing context in the anonymization log for manual review if needed

## Confidence Threshold

DocuDesk allows you to set a confidence threshold when creating an anonymization log:

```php
// Only consider entities with a confidence score >= 0.7
$anonymizationLog = new AnonymizationLogCreate();
$anonymizationLog->setNodeId('file123');
$anonymizationLog->setFileName('document.txt');
$anonymizationLog->setConfidenceThreshold(0.7);
```

Entities with a confidence score below the threshold will not be anonymized. The default threshold is 0.7.

## Customizing Entity Replacements

You can customize how DocuDesk generates replacement text for different entity types:

```php
// In your configuration
$config = [
    'anonymization' => [
        'replacements' => [
            'PERSON' => '[NAAM]',
            'LOCATION' => '[PLAATS]',
            'ORGANIZATION' => '[BEDRIJF]',
            'DATE' => '[DATUM]',
            'ID' => '[IDENTIFICATIE]',
            'EMAIL' => '[E-MAIL]',
            'PHONE' => '[TELEFOON]',
            'ADDRESS' => '[ADRES]',
            'FINANCIAL' => '[FINANCIEEL]',
            'MEDICAL' => '[MEDISCH]',
            'OTHER' => '[GEANONIMISEERD]'
        ]
    ]
];
```

## Supported Entity Recognition Engines

DocuDesk supports the following entity recognition engines:

1. **Microsoft Presidio** (default): Open-source PII detection and anonymization
2. **Amazon Comprehend**: AWS service for entity recognition
3. **Google Cloud Natural Language**: Google Cloud service for entity recognition
4. **Custom Engines**: You can implement your own entity recognition engine

To configure which engine to use:

```php
// In your configuration
$config = [
    'entity_recognition' => [
        'engine' => 'presidio', // Options: 'presidio', 'amazon', 'google', 'custom'
        'api_url' => 'http://presidio-api:8080/analyze',
        'api_key' => 'your-api-key', // For cloud services
        'custom_engine_class' => 'Your\\Custom\\EngineClass' // For custom engines
    ]
];
```

## Conclusion

DocuDesk's integration with entity recognition engines provides powerful entity recognition and anonymization capabilities. By transforming the engine's output into comprehensive AnonymizationLog objects, DocuDesk ensures that all anonymization operations are properly tracked and can be audited for GDPR compliance.

The AnonymizationLog object provides detailed information about:
- What entities were detected and with what confidence
- What replacements were made and where
- How to de-anonymize the document if needed
- The overall effectiveness of the anonymization process

This integration is a key component of DocuDesk's privacy and compliance features, helping organizations protect personal data while maintaining the ability to access the original information when necessary. 
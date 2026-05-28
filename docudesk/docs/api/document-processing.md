---
sidebar_position: 4
title: Document Processing
---

# Document Processing

DocuDesk provides powerful document processing capabilities that allow you to transform, analyze, and manage documents in various formats. This page explains how document processing works in DocuDesk.

## Overview

The Document Processing system in DocuDesk enables you to:

- Generate documents from templates
- Convert documents between formats
- Extract text and metadata from documents
- Anonymize personal data in documents
- Check documents for accessibility compliance
- Validate documents against templates or schemas

## Processing Workflow

A typical document processing workflow in DocuDesk consists of the following steps:

1. **Initiation**: A user or system initiates a processing operation
2. **Queuing**: The operation is queued for processing
3. **Processing**: The document is processed according to the requested operation
4. **Logging**: The operation is logged in the parsing logs
5. **Privacy Tracking**: If applicable, privacy-related metadata is updated
6. **Report Generation**: A document report is generated with analysis results
7. **Notification**: The user is notified of the operation's completion

## Integration with Other Systems

Document processing in DocuDesk is tightly integrated with several other systems:

- **[Document Reports](./document-reports.md)**: Stores analysis results for documents
- **[Anonymization Logs](./anonymization-logs.md)**: Tracks anonymization operations and replacements
- **[Presidio Integration](./presidio-integration.md)**: Provides entity recognition and anonymization capabilities

This integration ensures that you have a complete audit trail of all document operations and can demonstrate GDPR compliance.

## Processing Operations

### Text Extraction

Text extraction allows you to extract the textual content from documents in various formats (PDF, Word, etc.). This is useful for:

- Indexing documents for search
- Analyzing document content
- Preparing documents for anonymization

### Metadata Extraction

Metadata extraction allows you to extract metadata from documents, such as:

- Author information
- Creation and modification dates
- Document properties
- Embedded metadata

### Anonymization

Anonymization allows you to remove or mask personal data in documents. DocuDesk uses Microsoft Presidio for powerful entity recognition and anonymization:

- Named entity recognition to identify personal data (PERSON, LOCATION, etc.)
- Redaction of personal data with customizable replacement text
- Confidence scoring for detected entities
- Secure key generation for potential de-anonymization
- Comprehensive tracking of all anonymization operations

The results of anonymization operations are stored in the [Anonymization Log](./anonymization-logs.md) object, which includes:

- Original and anonymized text
- Detailed information about detected entities
- A secure key for potential de-anonymization
- A list of all text replacements made

For more information on anonymization, see:
- [Anonymization Logs](./anonymization-logs.md) for details on the anonymization log object
- [Presidio Integration](./presidio-integration.md) for details on how DocuDesk processes Presidio's output

#### Example Presidio Response

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

DocuDesk transforms this response into a comprehensive AnonymizationLog object that tracks all aspects of the anonymization process.

### Format Conversion

Format conversion allows you to convert documents between various formats:

- PDF to Word
- Word to PDF
- HTML to PDF
- Excel to CSV
- And many more

### Accessibility Check

Accessibility checking allows you to verify that documents comply with accessibility standards (WCAG):

- Checking for proper document structure
- Verifying alt text for images
- Ensuring proper color contrast
- Checking for other accessibility issues

The results of accessibility checks are stored in the [Document Report](./document-reports.md) object.

### Language Level Analysis

Language level analysis allows you to assess the readability and complexity of document text:

- Readability scores (Flesch-Kincaid, SMOG Index, etc.)
- Text complexity metrics
- Education level assessment
- Language improvement suggestions

The results of language level analysis are stored in the [Document Report](./document-reports.md) object.

### Validation

Validation allows you to verify that documents conform to templates or schemas:

- Checking for required fields
- Validating field formats
- Ensuring document structure matches templates
- Verifying document integrity

## API Integration

Document processing operations can be initiated through the DocuDesk API. The typical flow is:

1. Create a parsing log entry with the desired operation type
2. Monitor the parsing log for status updates
3. Retrieve the document report or anonymization log when the operation is complete

For detailed API documentation, see the [OpenAPI Specification](./openapi.md).

## Best Practices

1. **Batch Processing**: For large numbers of documents, use batch processing to improve efficiency
2. **Error Handling**: Implement proper error handling to deal with failed processing operations
3. **Monitoring**: Regularly monitor parsing logs to identify issues and bottlenecks
4. **Privacy Compliance**: Always update privacy tracking records when processing documents with personal data
5. **Performance Optimization**: Configure processing operations for optimal performance based on your needs
6. **Report Analysis**: Regularly review document reports to identify compliance issues
7. **Secure Key Management**: Store anonymization keys securely for potential de-anonymization
8. **Confidence Thresholds**: Adjust confidence thresholds for entity detection based on your needs

## Examples

### Anonymizing a Document

```php
/**
 * Anonymizes a document and creates the necessary logs and reports
 *
 * @param string $fileId The ID of the file to anonymize
 * @param string $fileName The name of the file
 * @param float $confidenceThreshold The confidence threshold for entity detection (default: 0.7)
 * @return array The anonymization results
 * @throws \Exception If anonymization fails
 */
public function anonymizeDocument(string $fileId, string $fileName, float $confidenceThreshold = 0.7): array
{
    // Create a parsing log entry for anonymization
    $parsingLog = new ParsingLog();
    $parsingLog->setFileId($fileId);
    $parsingLog->setFileName($fileName);
    $parsingLog->setParsingType('anonymization');
    $parsingLog->setStatus('pending');
    $parsingLog->save();

    try {
        // Process the document (this would typically be handled by a background job)
        $processor = new DocumentProcessor();
        $result = $processor->anonymize($fileId, $confidenceThreshold);

        // Update the parsing log
        $parsingLog->setStatus('completed');
        $parsingLog->setCompletedAt(new \DateTime());
        $parsingLog->setOutputFileId($result->getOutputFileId());
        $parsingLog->save();

        // Update the privacy tracking record
        $privacyFile = PrivacyFile::findByFileId($fileId);
        $privacyFile->setAnonymizationStatus('completed');
        $privacyFile->setAnonymizationDate(new \DateTime());
        $privacyFile->save();

        // Create an anonymization log
        $anonymizationLog = new AnonymizationLog();
        $anonymizationLog->setNodeId($fileId);
        $anonymizationLog->setFileName($fileName);
        $anonymizationLog->setStatus('completed');
        $anonymizationLog->setOriginalText($result->getOriginalText());
        $anonymizationLog->setAnonymizedText($result->getAnonymizedText());
        $anonymizationLog->setEntitiesFound($result->getEntitiesFound());
        $anonymizationLog->setEntityReplacements($result->getEntityReplacements());
        $anonymizationLog->setAnonymizationKey($result->generateSecureKey());
        $anonymizationLog->setOutputNodeId($result->getOutputNodeId());
        $anonymizationLog->setTotalEntitiesFound(count($result->getEntitiesFound()));
        $anonymizationLog->setTotalEntitiesReplaced(count($result->getEntityReplacements()));
        $anonymizationLog->save();

        // Create a document report
        $reportData = [
            'node_id' => $fileId,
            'file_name' => $fileName,
            'file_hash' => hash_file('sha256', $result->getOutputFilePath()),
            'analysis_types' => ['anonymization']
        ];

        $reportService = new DocumentReportService();
        $report = $reportService->createReport($reportData);
        $report->setAnonymizationResults($result->getAnonymizationResults());
        $report->setStatus('completed');
        $report->save();

        return [
            'success' => true,
            'anonymization_log_id' => $anonymizationLog->getId(),
            'report_id' => $report->getId(),
            'entities_found' => $anonymizationLog->getTotalEntitiesFound(),
            'entities_replaced' => $anonymizationLog->getTotalEntitiesReplaced()
        ];
    } catch (\Exception $e) {
        // Update the parsing log with error information
        $parsingLog->setStatus('failed');
        $parsingLog->setErrorMessage($e->getMessage());
        $parsingLog->save();
        
        throw $e;
    }
}
```

### Converting a Document

```php
/**
 * Converts a document from one format to another
 *
 * @param string $fileId The ID of the file to convert
 * @param string $fileName The name of the file
 * @param string $outputFormat The desired output format
 * @return array The conversion results
 * @throws \Exception If conversion fails
 */
public function convertDocument(string $fileId, string $fileName, string $outputFormat): array
{
    // Create a parsing log entry for format conversion
    $parsingLog = new ParsingLog();
    $parsingLog->setFileId($fileId);
    $parsingLog->setFileName($fileName);
    $parsingLog->setParsingType('format_conversion');
    $parsingLog->setOutputFormat($outputFormat);
    $parsingLog->setStatus('pending');
    $parsingLog->save();

    try {
        // Process the document
        $processor = new DocumentProcessor();
        $result = $processor->convert($fileId, $outputFormat);

        // Update the parsing log
        $parsingLog->setStatus('completed');
        $parsingLog->setCompletedAt(new \DateTime());
        $parsingLog->setOutputFileId($result->getOutputFileId());
        $parsingLog->save();

        return [
            'success' => true,
            'output_file_id' => $result->getOutputFileId(),
            'output_format' => $outputFormat
        ];
    } catch (\Exception $e) {
        // Update the parsing log with error information
        $parsingLog->setStatus('failed');
        $parsingLog->setErrorMessage($e->getMessage());
        $parsingLog->save();
        
        throw $e;
    }
}
```

### Analyzing Document Accessibility

```php
/**
 * Analyzes a document for WCAG compliance
 *
 * @param string $fileId The ID of the file to analyze
 * @param string $fileName The name of the file
 * @return array The analysis results
 * @throws \Exception If analysis fails
 */
public function analyzeAccessibility(string $fileId, string $fileName): array
{
    // Create a document report for WCAG compliance analysis
    $reportData = [
        'node_id' => $fileId,
        'file_name' => $fileName,
        'file_hash' => hash_file('sha256', '/path/to/document.pdf'),
        'analysis_types' => ['wcag_compliance']
    ];

    $reportService = new DocumentReportService();
    $report = $reportService->createReport($reportData);

    try {
        // Process the document
        $processor = new DocumentProcessor();
        $result = $processor->analyzeAccessibility($fileId);

        // Update the report
        $report->setWcagComplianceResults($result->getWcagResults());
        $report->setStatus('completed');
        $report->save();

        return [
            'success' => true,
            'report_id' => $report->getId(),
            'compliance_level' => $result->getWcagResults()['compliance_level'],
            'total_issues' => $result->getWcagResults()['total_issues']
        ];
    } catch (\Exception $e) {
        // Update the report with error information
        $report->setStatus('failed');
        $report->setErrorMessage($e->getMessage());
        $report->save();
        
        throw $e;
    }
}
```

## Conclusion

Document processing is a core feature of DocuDesk that enables powerful document transformation and analysis capabilities. By integrating with privacy tracking, parsing logs, document reports, and anonymization logs, DocuDesk ensures that all document operations are properly tracked and comply with privacy and accessibility regulations.

For more detailed information on specific aspects of document processing, refer to the following documentation:

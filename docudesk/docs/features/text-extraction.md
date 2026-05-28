---
sidebar_position: 8
---

# Text Extraction

DocuDesk provides powerful text extraction capabilities that allow you to extract and process text content from various document formats. This feature serves as the foundation for many other DocuDesk capabilities, including document analysis, reporting, and anonymization.

## Supported File Formats

The text extraction service supports a wide range of document formats:

- **PDF Documents** - Extract text from PDF files using the Smalot PDF Parser
- **Word Documents** - Process .doc and .docx files using PHPWord
- **Excel Spreadsheets** - Extract data from .xls, .xlsx, and .csv files using PHPSpreadsheet
- **PowerPoint Presentations** - Process .ppt and .pptx files using PHPPresentation
- **Plain Text Files** - Handle .txt, .md, .html, .xml, and other text-based formats

## How It Works

The text extraction process follows these steps:

1. **File Detection**: The system identifies the file type based on extension and MIME type
2. **Content Extraction**: Specialized extractors process the file to retrieve text content
3. **Text Normalization**: The extracted text is normalized to ensure consistent processing
4. **Metadata Extraction**: Additional metadata is extracted from the document

## Using Text Extraction

Text extraction is typically used as part of a larger workflow, but you can also use it directly:

```php
// Example: Extract text from a document
$extractionService = \OC::$server->get(OCA\DocuDesk\Service\ExtractionService::class);
$text = $extractionService->extractText('/path/to/document.pdf');

// Example: Extract metadata from a document
$metadata = $extractionService->extractMetadata('/path/to/document.docx');
```

## Metadata Extraction

In addition to text content, the extraction service can retrieve valuable metadata from documents:

- **Basic Metadata**: Filename, file size, MIME type, last modified date
- **PDF Metadata**: Title, author, subject, keywords, creation date, page count
- **Office Document Metadata**: Creator, last modified by, creation date, title, description
- **Spreadsheet Metadata**: Sheet count, cell counts, worksheet names
- **Presentation Metadata**: Slide count, shape counts

## Performance Considerations

Text extraction can be resource-intensive, especially for large documents. Consider these best practices:

- Process documents asynchronously for large files
- Implement caching for frequently accessed documents
- Set appropriate memory limits for your server

## Integration with Other Features

Text extraction integrates with several other DocuDesk features:

- **Document Analysis**: Extracted text is analyzed for sensitive information
- **Reporting**: Text content is used to generate document reports
- **Anonymization**: Extracted text is processed to identify and anonymize sensitive data
- **Search Indexing**: Extracted text improves document searchability

## Configuration

No specific configuration is required for basic text extraction functionality. The necessary libraries are included with DocuDesk.

## Limitations

While the text extraction service is powerful, be aware of these limitations:

- Complex document formatting may be lost during extraction
- Some heavily encrypted documents may not be fully extractable
- Image-based PDFs require OCR (not included) for text extraction
- Very large documents may require additional memory allocation

## Troubleshooting

If you encounter issues with text extraction:

1. Verify the document is not corrupted or password-protected
2. Check that the file format is supported
3. Ensure your server has sufficient memory allocated
4. Review the DocuDesk logs for specific error messages 
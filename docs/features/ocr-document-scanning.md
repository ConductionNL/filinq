---
id: ocr-document-scanning
title: OCR Document Scanning
sidebar_label: OCR Document Scanning
sidebar_position: 11
description: Extract text from scanned documents and images using Tesseract OCR
keywords:
  - OCR
  - scanning
  - Tesseract
  - image
  - text extraction
---

# OCR Document Scanning

DocuDesk integrates Tesseract OCR to extract searchable text from scanned documents and
image-based files. OCR is transparent to the rest of the pipeline — once text is extracted,
it feeds into the existing entity detection and anonymization workflows.

## Overview

The OCR feature:

1. **Detects** whether a file needs OCR (image-based or text-less PDF)
2. **Extracts** text using Tesseract (configurable languages and DPI)
3. **Returns** the extracted text for downstream processing (entity detection, anonymization)
4. **Degrades gracefully** when Tesseract is not installed — the service reports unavailability
   rather than crashing

## Supported File Types

### Images

- `image/png`
- `image/jpeg`
- `image/jpg`
- `image/tiff`
- `image/bmp`
- `image/gif`
- `image/webp`

### PDFs

- `application/pdf` — when the PDF contains no embedded text (i.e. a scanned PDF)

## API Endpoints

OCR processing is exposed through the document processing pipeline. The `OcrService` is
invoked automatically by `DocumentTextExtractor` when it encounters an image or text-less PDF.

Direct OCR triggering uses the standard file processing endpoint:

```
POST /apps/docudesk/api/anonymization/extract/{fileId}
```

The response includes an `ocrApplied: true` flag when OCR was used.

## Configuration Options

Configured via the DocuDesk admin settings page or `occ config:app:set`:

| Config key                   | Default      | Description                                       |
|-----------------------------|--------------|---------------------------------------------------|
| `docudesk_ocr_enabled`       | `true`       | Enable or disable OCR processing globally         |
| `docudesk_ocr_languages`     | `nld+eng`    | Tesseract language codes (e.g. `nld+eng+fra`)     |
| `docudesk_ocr_dpi`           | `300`        | Resolution for image extraction (higher = better quality, slower) |

### Setting OCR Language

```bash
docker exec nextcloud php occ config:app:set docudesk docudesk_ocr_languages --value="nld+eng+fra"
```

Available language packs depend on which Tesseract language data files are installed in the
container. Install via `apt-get install tesseract-ocr-nld tesseract-ocr-eng`.

## Installation Requirements

Tesseract OCR must be installed on the Nextcloud host or container:

```bash
apt-get install tesseract-ocr tesseract-ocr-nld tesseract-ocr-eng
```

The service checks for Tesseract availability on each call to `isTesseractAvailable()`. If
Tesseract is missing, processing continues without OCR and returns empty text rather than
throwing.

## Services

### `OcrService`

Main OCR service.

| Method                    | Description                                                              |
|--------------------------|--------------------------------------------------------------------------|
| `isTesseractAvailable()`  | Check whether the Tesseract binary is available on the system           |
| `getTesseractVersion()`   | Return the installed Tesseract version string, or `null`                |
| `needsOcr()`              | Determine if a file type/content requires OCR                           |
| `isOcrEnabled()`          | Check whether OCR is enabled in app configuration                       |
| `getOcrLanguages()`       | Return the configured Tesseract language string                         |
| `getOcrDpi()`             | Return the configured scan DPI                                          |
| `extractTextFromImage()`  | Run Tesseract on a Nextcloud `File` object of image type                |
| `extractTextFromPdf()`    | Run Tesseract on each page of a scanned PDF                             |
| `processFile()`           | Determine file type, apply OCR if needed, return extracted text and metadata |

## Integration with Text Extraction

`DocumentTextExtractor` calls `OcrService::processFile()` when a file cannot yield text
through standard means (e.g. pdftotext). The extracted text is then passed to
`EntityDetectionService` for NER analysis.

## Dependencies

| Dependency                          | Purpose                                       |
|------------------------------------|-----------------------------------------------|
| `thiagoalessio/tesseract-ocr`       | PHP wrapper around the Tesseract binary       |
| `OCP\Files\IRootFolder`             | Access Nextcloud files by file ID             |
| `OCP\IAppConfig`                    | Read OCR configuration settings               |
| `OCP\IUserSession`                  | Determine current user for file access        |

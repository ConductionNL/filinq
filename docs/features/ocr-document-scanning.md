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

Filinq integrates Tesseract OCR to extract searchable text from scanned documents and
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

## Run OCR yourself

On **My documents** and in the file viewer, an image or PDF has a **Run OCR** action when an admin has OCR switched on and Tesseract is installed on the server. The action shows that it is busy, and when it is done the file gets a badge with the confidence, such as *OCR 91%*. The page does not reload.

The same run is an API call:

| Call | Answer |
|---|---|
| `POST /apps/filinq/api/ocr/{fileId}` | 200 with `ocrProcessed`, `confidence`, `textLength`, `languages`, `dpi`. Never the text. |
| `GET /apps/filinq/api/ocr/{fileId}` | The file's last result, or `ocrProcessed: false`. |
| `GET /apps/filinq/api/ocr?fileIds=1,2` | Whether OCR can run here (`capability`) and the results for those files. |

The POST answers 409 when an admin switched OCR off, 503 when Tesseract is not installed, 400 for a file type OCR does not read, and 404 for a file that is not in your own files. It answers 200 with `ocrProcessed: false` when OCR found no text.

Every run that recovers text stores an `ocrResult` row for the file: confidence, languages, DPI, text length, when, whether a person or the anonymisation pipeline started it, and the Tesseract version. A new run updates the row. The row never holds the text.

## Scans in the anonymisation pipeline

OpenRegister extracts text and detects entities, but it does no OCR. A scanned PDF comes back from OpenRegister without text, and used to read as "nothing to redact".

Now, after OpenRegister's extraction, filinq checks whether the file needed OCR (an image, or a PDF without text). If it did, filinq runs OCR on it and hands the text to OpenRegister so the entities are detected in it. The extract answer says what happened:

| Field | Meaning |
|---|---|
| `ocr` | `ran`, `ingested`, `confidence`, `textLength`. |
| `ocrSkipped` | OCR could not run: `ocr_disabled`, `tesseract_unavailable`, `no_text_recovered` or `ocr_failed`. |
| `ocrDetectionPending` | OCR read the text, but OpenRegister could not take it yet. |

OpenRegister does not have the seam to take the text yet (ConductionNL/openregister#2033). Until it does, every scan reads `ocrDetectionPending: true`. The review shows a warning on that document and keeps it open, so an empty entity list never marks a scan as done. Reopening the document does not run OCR again.

## Configuration Options

Configured via the Filinq admin settings page or `occ config:app:set`:

| Config key                   | Default      | Description                                       |
|-----------------------------|--------------|---------------------------------------------------|
| `filinq_ocr_enabled`       | `true`       | Enable or disable OCR processing globally         |
| `filinq_ocr_languages`     | `nld+eng`    | Tesseract language codes (e.g. `nld+eng+fra`)     |
| `filinq_ocr_dpi`           | `300`        | Resolution for image extraction (higher = better quality, slower) |

### Setting OCR Language

```bash
docker exec nextcloud php occ config:app:set filinq filinq_ocr_languages --value="nld+eng+fra"
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

## Who does what

Filinq runs OCR, on your own server, with Tesseract. OpenRegister owns chunking and entity detection, and does no OCR. Filinq never runs its own entity detection on OCR text; it hands the text to OpenRegister. Born-digital files whose extraction found text never go through OCR.

## Dependencies

| Dependency                          | Purpose                                       |
|------------------------------------|-----------------------------------------------|
| `thiagoalessio/tesseract-ocr`       | PHP wrapper around the Tesseract binary       |
| `OCP\Files\IRootFolder`             | Access Nextcloud files by file ID             |
| `OCP\IAppConfig`                    | Read OCR configuration settings               |
| `OCP\IUserSession`                  | Determine current user for file access        |

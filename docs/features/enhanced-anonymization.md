---
id: enhanced-anonymization
title: Enhanced Anonymization (Batch Processing)
sidebar_label: Enhanced Anonymization
sidebar_position: 9
description: Batch anonymization workflow for processing multiple documents in a single session
keywords:
  - anonymization
  - batch
  - GDPR
  - privacy
  - bulk processing
---

# Enhanced Anonymization

DocuDesk extends its GDPR anonymization capabilities with a batch processing workflow that
allows users to upload, review, and anonymize multiple documents in a single guided session.
The pipeline is designed as a stepwise state machine backed by a distributed cache.

## Overview

The batch workflow proceeds through these stages:

1. **Upload** — Upload one or more files as a named batch
2. **Extract** — Step through files one at a time to extract entities via Presidio
3. **Review** — Inspect detected entities and select which types to anonymize
4. **Anonymize** — Apply anonymization to all extracted files with selected entity types
5. **Report** — Download a CSV/JSON report of replacements per file

## API Endpoints

### Upload Batch

```
POST /apps/docudesk/api/anonymization/batch/upload
```

Uploads multiple files and creates a batch in the cache. The batch is identified by a
server-generated UUID and expires after 2 hours of inactivity.

**Form fields:**

| Field    | Type          | Description                                  |
|---------|---------------|----------------------------------------------|
| `files` | multipart[]   | One or more files to include in the batch    |

**Response:**

```json
{
  "batchId": "550e8400-e29b-41d4-a716-446655440000",
  "status": "uploading",
  "fileCount": 3
}
```

---

### Extract Next File

```
POST /apps/docudesk/api/anonymization/batch/{batchId}/extract
```

Extracts entities from the next unprocessed file in the batch. Call this endpoint repeatedly
until `batchStatus` is `review`.

**Response:**

```json
{
  "batchStatus": "extracting",
  "fileId": 42,
  "fileName": "report.pdf",
  "entityCount": 7,
  "filesExtracted": 1,
  "totalFiles": 3
}
```

When all files are extracted: `batchStatus` becomes `review`.

---

### Get Batch Status

```
GET /apps/docudesk/api/anonymization/batch/{batchId}/status
```

Returns the current state of a batch including all file statuses.

---

### Get Detected Entities

```
GET /apps/docudesk/api/anonymization/batch/{batchId}/entities
```

Returns the aggregated entity types detected across all files in the batch, for use in the
review step before anonymization.

---

### Anonymize Batch

```
POST /apps/docudesk/api/anonymization/batch/{batchId}/anonymize
```

Applies anonymization to all extracted files using the selected entity types.

**Request body (JSON):**

| Field      | Type     | Description                                       |
|-----------|----------|---------------------------------------------------|
| `entities` | string[] | Entity type labels to anonymize (e.g. `PERSON`, `PHONE_NUMBER`) |

**Response:**

```json
{
  "batchId": "550e8400-...",
  "batchStatus": "completed",
  "processedFiles": 3,
  "skippedFiles": [],
  "totalFiles": 3
}
```

---

### Download Report

```
GET /apps/docudesk/api/anonymization/batch/{batchId}/report
```

Returns a summary report of all replacements made per file.

---

### Anonymization Profiles

```
GET  /apps/docudesk/api/anonymization/profiles
PUT  /apps/docudesk/api/anonymization/profiles
```

Manage named entity type profiles (preset selections of entity types for repeated use).

---

## Configuration Options

| Config key                         | Default | Description                                     |
|-----------------------------------|---------|-------------------------------------------------|
| `docudesk_batch_max_files`         | `100`   | Maximum files per batch session                 |

Set via the DocuDesk admin settings or `occ config:app:set docudesk docudesk_batch_max_files`.

## Batch State Machine

```
uploading → extracting → review → anonymizing → completed
                              ↓
                           (per-file: error)
```

Files that fail extraction or anonymization are marked `error` and skipped; the batch
continues with the remaining files.

## Services

### `BatchStateService`

Manages batch lifecycle in the distributed cache (APCu or Redis).

| Method          | Description                                   |
|----------------|-----------------------------------------------|
| `createBatch()` | Create a new batch and persist to cache       |
| `getBatch()`    | Retrieve batch by ID; returns `null` if expired |
| `updateBatch()` | Update batch state in cache                   |
| `deleteBatch()` | Remove a batch from the cache                 |
| `getMaxFiles()` | Read configured max-files limit               |

### `BatchExtractionService`

Steps through a batch one file at a time, calling `AnonymizationService::extractAndDetectEntities()`.

| Method         | Description                                                  |
|---------------|--------------------------------------------------------------|
| `extractNext()` | Extract entities from the next `uploaded` file in the batch |

### `BatchAnonymizeService`

Anonymizes all `extracted` files in a batch.

| Method              | Description                                             |
|--------------------|----------------------------------------------------------|
| `anonymizeBatch()`  | Apply anonymization to extracted files with given entity types |

### `BatchUploadService`

Handles file upload and batch initialization.

### `BatchReportService`

Generates the post-anonymization report.

### `EntityConsolidationService`

Aggregates detected entity types across all files for the review step.

## Dependencies

| Dependency           | Purpose                                     |
|---------------------|---------------------------------------------|
| `AnonymizationService` | Single-file entity extraction and anonymization |
| `ICacheFactory`      | Distributed cache (APCu/Redis) for batch state |
| `IAppConfig`         | Read batch configuration limits             |

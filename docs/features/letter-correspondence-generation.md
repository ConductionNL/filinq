---
id: letter-correspondence-generation
title: Letter & Correspondence Generation
sidebar_label: Correspondence Generation
sidebar_position: 10
description: Generate personalized letters and correspondence from templates with resolved recipient data
keywords:
  - correspondence
  - letter
  - generation
  - template
  - PDF
  - email
---

# Letter & Correspondence Generation

Filinq provides an API for generating personalized correspondence — letters, notifications,
decisions — by combining a stored Twig template with data resolved from one or more
OpenRegister objects. Output formats include PDF, DOCX, HTML, and email.

## Overview

The correspondence pipeline:

1. **Fetch template** — Load a stored template by UUID via `TemplateService`
2. **Resolve data** — Fetch recipient and case data from OpenRegister objects
3. **Render** — Render the Twig template with the merged data context
4. **Produce output** — Convert to the requested format (PDF, DOCX, HTML, email)
5. **Log** — Record the generated correspondence in an OpenRegister log register

For batches larger than 10 recipients, the work is offloaded to a `BatchCorrespondenceJob`
Nextcloud background job so the HTTP request returns immediately with a job ID.

## API Endpoints

### Generate Single Correspondence

```
POST /apps/filinq/api/correspondence/generate
```

Generates a single correspondence document for one or more data references and returns the
rendered output inline.

**Request body (JSON):**

| Field            | Type     | Required | Description                                                         |
|-----------------|----------|----------|---------------------------------------------------------------------|
| `templateId`     | string   | Yes      | UUID of the stored template to use                                  |
| `dataRefs`       | array    | Yes      | Array of OpenRegister references `{ register, schema, id }`         |
| `options`        | object   |          | Generation options (see below)                                      |

**`options` fields:**

| Field            | Type   | Default | Description                                              |
|-----------------|--------|---------|----------------------------------------------------------|
| `format`         | string | `pdf`   | Output format: `pdf`, `docx`, `html`, `email`            |
| `huisstijlId`    | string |         | UUID of a huisstijl/branding object to apply             |
| `caseReference`  | string |         | Case or dossier reference for the correspondence log     |
| `recipientType`  | string |         | Recipient category label for logging                     |
| `userId`         | string |         | Nextcloud user ID to attribute the generation to         |

**Response:**

```json
{
  "content": "<base64 or HTML string>",
  "format": "pdf",
  "warnings": [],
  "registerEntry": { "id": "...", "status": "logged" }
}
```

---

### Generate Batch Correspondence

```
POST /apps/filinq/api/correspondence/generate/batch
```

Generates correspondence for multiple recipients. Batches of more than 10 recipients are
processed asynchronously and a job ID is returned immediately.

**Request body (JSON):**

| Field        | Type    | Required | Description                                         |
|-------------|---------|----------|-----------------------------------------------------|
| `templateId` | string  | Yes      | UUID of the stored template                         |
| `recipients` | array   | Yes      | Array of `dataRefs` sets — one entry per recipient  |
| `options`    | object  |          | Same options as single generate                     |

**Synchronous response (≤ 10 recipients):**

```json
{
  "results": [
    { "recipientIndex": 0, "content": "...", "format": "pdf", "warnings": [] }
  ]
}
```

**Asynchronous response (> 10 recipients):**

```json
{
  "jobId": "job-uuid",
  "status": "queued",
  "message": "Batch queued for background processing"
}
```

---

### Get Job Status

```
GET /apps/filinq/api/correspondence/jobs/{jobId}
```

Returns the current status of a batch background job.

**Response:**

```json
{
  "jobId": "job-uuid",
  "status": "completed",
  "processedCount": 25,
  "totalCount": 25,
  "errors": []
}
```

---

## Data Resolution

`DataResolverService` resolves referenced OpenRegister objects into a merged data context:

- Accepts an array of `{ register, schema, id }` references
- Resolves nested object references up to **3 levels** deep
- Merges all resolved objects into a single flat Twig context
- Uses a per-request in-memory cache to avoid duplicate lookups

**Example `dataRefs`:**

```json
[
  { "register": "woo-register", "schema": "person", "id": "person-uuid" },
  { "register": "cases-register", "schema": "case", "id": "case-uuid" }
]
```

## Output Formats

| Format  | Description                                               |
|--------|-----------------------------------------------------------|
| `pdf`   | PDF/A-3b via mPDF with embedded fonts (default)          |
| `docx`  | Microsoft Word document via PhpOffice/PhpWord             |
| `html`  | Rendered HTML string                                      |
| `email` | Sends the rendered content as an email (requires SMTP)   |

## Background Processing

When a batch request exceeds 10 recipients, `CorrespondenceService` schedules a
`BatchCorrespondenceJob` via `IJobList`. The job runs in the next Nextcloud cron cycle and
stores progress in the cache under the returned job ID.

## Services

### `CorrespondenceService`

Main orchestration service.

| Method             | Description                                                  |
|-------------------|--------------------------------------------------------------|
| `generate()`       | Generate a single correspondence document                   |
| `generateBatch()`  | Generate correspondence for multiple recipients             |
| `getJobStatus()`   | Retrieve background job status by job ID                    |
| `storeJobStatus()` | Persist job status to the cache                             |

### `DataResolverService`

Resolves OpenRegister object data for use as Twig context.

| Method           | Description                                              |
|-----------------|----------------------------------------------------------|
| `resolve()`      | Resolve one or more data references to a merged array   |

### `BatchCorrespondenceJob`

Nextcloud `QueuedJob` for large batch correspondence processing.

## Dependencies

| Dependency         | Purpose                                       |
|-------------------|-----------------------------------------------|
| `TemplateService`  | Load stored templates by UUID                 |
| `DataResolverService` | Resolve OpenRegister objects to Twig data  |
| `TemplateRenderer` | Twig sandboxed rendering                     |
| `PdfService`       | PDF/A-3b generation                          |
| `IJobList`         | Schedule background jobs for large batches   |

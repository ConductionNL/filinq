---
id: batch-anonymization
title: Batch Anonymization
sidebar_label: Batch Anonymization
sidebar_position: 5
description: Upload, extract, review, and anonymize multiple documents in a single guided session
keywords:
  - batch
  - anonymization
  - GDPR
  - bulk processing
  - WOO
---

# Batch Anonymization

## Status: Proposed

Batch anonymization extends the single-document anonymization pipeline to support processing multiple files in one guided session. The workflow follows a state-machine model: upload → extract → review → anonymize → completed.

## Overview

Users can upload up to 100 files (admin-configurable) in a single request. DocuDesk processes them sequentially, extracting text and entities from each file, then presenting a consolidated entity review before applying anonymization. A CSV audit report is available for download after completion.

Batch state is persisted in Nextcloud `ICache` with a 2-hour TTL. No batch data is stored permanently; only the anonymized output files are saved to the user's DocuDesk folder.

## Workflow Steps

1. **Upload** — `POST /api/anonymization/batch/upload` — upload multiple files, receive `batchId`
2. **Extract** — `POST /api/anonymization/batch/{batchId}/extract` — process one file per call until all are extracted
3. **Review** — [Entity review](./anonymization-entity-review.md) — consolidated entity list with toggle controls
4. **Anonymize** — `POST /api/anonymization/batch/{batchId}/anonymize` — apply anonymization with reviewed entity list
5. **Report** — `GET /api/anonymization/batch/{batchId}/report` — download CSV audit report

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/anonymization/batch/upload` | Upload multiple files; returns `batchId` |
| `POST` | `/api/anonymization/batch/{batchId}/extract` | Extract next unprocessed file in batch |
| `GET` | `/api/anonymization/batch/{batchId}/status` | Polling endpoint — returns batch status and per-file progress |
| `GET` | `/api/anonymization/batch/{batchId}/entities` | Consolidated entity list for review |
| `POST` | `/api/anonymization/batch/{batchId}/anonymize` | Apply anonymization with reviewed entity list. Body supports `entities`, `outputFormat`, `appendBasisSummary`, and `scope` (default `dossier`). |
| `GET` | `/api/anonymization/batch/{batchId}/report` | Download CSV audit report (post-completion) |

### Placeholder-numbering scope

A batch **is** a folder/dossier, so `POST /api/anonymization/batch/{batchId}/anonymize` defaults to `scope: "dossier"`: a given entity gets the same scope-local placeholder number across **all** files in the batch, so the redacted set reads as one unit. Pass `scope: "document"` to number each file independently instead. See [Placeholder-numbering scope](./anonymization.md#placeholder-numbering-scope-scope) on the anonymization page for the full semantics.

## Audit Report

The CSV report includes: `fileName`, `originalFileId`, `anonymizedFileId`, `entityCount`, `replacementCount`, `status`, `timestamp`. Entity values are excluded (GDPR data minimization, Recital 26).

## Standards

- **GDPR / AVG** — Batch state is transient (ICache TTL 2h); entity values excluded from audit report
- **WOO** — Anonymization profiles aligned with WOO publication requirements
- **GEMMA** Media-behandelingcomponent
- **TEC-DMS-7** (Workflow Management)

## Limits

| Parameter | Default | Config Key |
|-----------|---------|------------|
| Max files per batch | 100 | `docudesk_batch_max_files` (IAppConfig) |
| Batch TTL | 2 hours | Hardcoded (ICache) |

## Related Features

- [Anonymization Entity Review](./anonymization-entity-review.md)
- [Enhanced Anonymization](./enhanced-anonymization.md)
- [Anonymization Pipeline](./anonymization.md)

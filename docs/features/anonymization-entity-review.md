---
id: anonymization-entity-review
title: Anonymization Entity Review
sidebar_label: Entity Review
sidebar_position: 6
description: Consolidated entity review and selective inclusion/exclusion before batch anonymization is applied
keywords:
  - anonymization
  - entity review
  - GDPR
  - batch
  - WOO
---

# Anonymization Entity Review

## Status: Proposed

This feature is part of the batch anonymization workflow. It provides a consolidated view of all detected entities across a batch of documents, allowing users to selectively include or exclude entities before anonymization is applied.

## Overview

After text extraction is complete for all files in a batch, DocuDesk presents a unified entity list deduplicated by value (case-insensitive). Each entity shows its type, highest confidence score, and the number of files in which it appears. Entities are pre-selected based on the active WOO anonymization profile.

Users can toggle individual entities on or off. The final selection is sent to the backend when the user triggers anonymization.

## Key Capabilities

- Consolidated, deduplicated entity list across all batch files
- Pre-selection based on active WOO anonymize/keep profiles
- Confidence threshold filter (default: entities above 0.7 included)
- Per-entity toggle (frontend-only state, no intermediate API call)
- Batch anonymization triggered with the reviewed entity list

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/anonymization/batch/{batchId}/entities` | Retrieve consolidated entity list for review (batch must be in "review" status) |
| `POST` | `/api/anonymization/batch/{batchId}/anonymize` | Start anonymization with the reviewed entity list |

### GET `/api/anonymization/batch/{batchId}/entities` response shape

Each entry in the `entities` array carries the following fields:

| Field | Type | Description |
|-------|------|-------------|
| `type` | string | Entity type (e.g. `PERSON`, `ORGANIZATION`) |
| `value` | string | Entity text value |
| `highestConfidence` | float | Highest confidence score across all files in the batch |
| `fileCount` | int | Number of files in which this entity was detected |
| `included` | bool | Whether the entity is pre-selected for anonymization |
| `prohibitionMatch` | object\|null | `null` when no prohibition rule matches; `{ruleId, ruleName, highConfidence}` when a `publicationProhibition` rule matches. `highConfidence` is `true` when `highestConfidence ≥ docudesk.prohibition.high_confidence_threshold` (default 0.85, inclusive). |
| `suggestedBases` | string[] | Deduplicated union of `bases[]` from the dossier(s) the batch's files belong to; `[]` when the files are not in any dossier or the dossier has no bases configured. Used to pre-fill the grondslag picker in the review UI. |

The response is a strict superset of the pre-`anonymisation-entity-review-prohibition-hints` shape — clients reading only `type`, `value`, `highestConfidence`, `fileCount`, and `included` continue to work without modification.

## Standards

- **GDPR / AVG** — Entity data is not persisted after anonymization; reviewed list is transient
- **WOO** — Default entity profiles align with WOO publication anonymization requirements
- **TEC-DMS-7** (Workflow Management) — Entity review is a step in the document workflow

## Related Features

- [Batch Processing](./batch-processing.md) — Provides the batch context
- [Enhanced Anonymization](./enhanced-anonymization.md) — Full batch anonymization workflow
- [Anonymization Pipeline](./anonymization.md) — Single-document anonymization

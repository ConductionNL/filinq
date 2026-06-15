---
id: document-comparison
title: Document Comparison
sidebar_label: Document Comparison
sidebar_position: 7
description: Compare two documents or two versions of one document, with redaction-aware annotation
keywords:
  - comparison
  - diff
  - version control
  - track changes
  - redaction
---

# 🔍 Document Comparison

## Overview

Post-hoc, read-only comparison of two document subjects — two versions of one
Nextcloud file, or two distinct files — producing a server-computed word-level
structured diff rendered side by side. When the compared pair is an original
document and its anonymised output, change hunks are annotated with redaction
metadata from the OpenRegister NER pipeline and the response carries a
redaction-completeness signal ("show me what was redacted, and prove nothing
flagged was missed").

This capability is distinct from template version diff (`template-management`)
and from the in-pipeline before/after anonymisation preview
(`enhanced-anonymization`). It compares stored artifacts after the fact and
never copies, persists, or indexes their content — search stays with
OpenRegister, per ADR-022. Comparison is ephemeral: no new schema, no migration,
no persistence.

## User flow

1. On **My documents**, open the row actions for a file and choose **Compare…**.
   If the file has an anonymised output, it is preselected as the right subject.
2. The side-by-side comparison view opens. Pick file IDs and, optionally,
   version timestamps for each side, then **Compare**.
3. Change hunks are highlighted (added / removed / changed) with synchronized
   scrolling. For an original-vs-anonymised pair, redacted spans carry an
   entity-type badge.
4. An advisory panel lists `unredactedEntities` — entities recorded for the
   source file that produced no change hunk (potential missed redactions). The
   panel is advisory only; it never blocks.
5. A notice is shown when the two subjects have different source formats
   (`crossFormat`), warning about layout-derived noise.

## API

`POST /apps/docudesk/api/comparison/compare`

Request body:

```json
{
  "left":  { "fileId": 42, "versionTimestamp": 1700000000 },
  "right": { "fileId": 88 }
}
```

`versionTimestamp` is optional; omit it to compare the file's current content.

Response (200):

```json
{
  "crossFormat": false,
  "hunks": [
    { "type": "unchanged", "left": {"offset":0,"length":12}, "right": {"offset":0,"length":12}, "leftText": "...", "rightText": "..." },
    { "type": "removed", "left": {"offset":13,"length":9}, "right": null, "leftText": "P. Jansen", "redaction": {"entityId": 1, "entityType": "PERSON", "matchedBy": "value"} }
  ],
  "summary": { "changedHunks": 1, "totalHunks": 2 },
  "redactionAnnotation": "annotated",
  "unredactedEntities": [ { "entityId": 2, "entityName": "Anna de Vries" } ]
}
```

### Status codes

| Code | Reason                | Meaning |
|------|-----------------------|---------|
| 200  | —                     | Comparison computed. |
| 400  | —                     | Missing `left`/`right` subject or `fileId`. |
| 401  | —                     | Not authenticated. |
| 404  | `not-found`           | A subject (or requested version) is not resolvable for this user. No existence disclosure. |
| 413  | `too-large`           | A subject's extracted text exceeds `docudesk.comparison.max_text_bytes` (default 5 MB). |
| 415  | `unsupported-format`  | A subject's mime type is not text-extractable. The body names the offending subject. |
| 422  | `versions-unavailable`| A version was requested but `files_versions` is disabled. |

## Authorization

Both subjects are resolved through the requesting user's folder
(`IRootFolder::getUserFolder()->getById()`), so a file the user cannot read is
indistinguishable from a non-existent one (IDOR-safe per ADR-005). The endpoint
is `#[NoAdminRequired]`.

## Redaction annotation

When the right subject is the anonymised output of the left, change hunks whose
inserted span maps to a replacement key are annotated `matchedBy: "key"`;
otherwise removed spans matching an `Entity` canonical value are annotated
`matchedBy: "value"`. OpenRegister mappers are resolved lazily; when OR is
unavailable the comparison still succeeds as a plain diff with
`redactionAnnotation: "unavailable"`. Skip-flagged relations (operator-released
overrides) are excluded from the completeness signal, which reports canonical
entity names only — never literal document text.

:::tip Local Processing
All comparison operations happen locally; nothing new is persisted or logged
beyond the two subjects' file IDs and version timestamps.
:::

## Configuration

| Key | Default | Meaning |
|-----|---------|---------|
| `docudesk.comparison.max_text_bytes` | `5242880` | Maximum extracted text per subject (bytes). |

## Use Cases
- Original vs anonymised verification (prove nothing flagged was missed)
- Document version control
- Compliance verification
- Legal document review
- Content validation

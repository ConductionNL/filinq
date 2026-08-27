---
id: document-validation
title: Document Validation
sidebar_label: Document Validation
sidebar_position: 9
description: Automated quality control and validation of documents
keywords:
  - validation
  - quality control
  - compliance
  - verification
---

# ✅ Document Validation

## Overview

Automatic quality control on documents entering Filinq: a fixed catalogue of
file-level checks (format, integrity, encryption, text-layer presence) and a
record-level check (metadata completeness), configured through per-document-type
**validation profiles**, producing a `validationStatus` verdict and
`validationFindings[]` on the document record via the ADR-031 calculation
pattern (computation backend service + `x-openregister-calculations`, mirroring
metadata enrichment).

Validation **judges and never mutates** — enrichment (deriving values) stays
with metadata enrichment, object-shape validation stays with OpenRegister schema
validation, virus scanning stays with OpenRegister file attachments. Defaults
are **warn-only**; blocking intake is an explicit per-check admin opt-in.

It closes the "zero entities because zero text" silent-failure mode: a scan-only
PDF with no text layer yields zero detected entities, which an operator could
mistake for "nothing to redact" and publish PII.

## Check catalogue

| `checkId` | Fires when |
|-----------|------------|
| `format-not-allowed` | The file mime type is not in the profile's allowlist. |
| `extension-mime-mismatch` | The file extension contradicts the detected content type. |
| `file-unreadable` | The file could not be read/parsed. |
| `pdf-encrypted` | The PDF is encrypted/password-protected (cannot be anonymised). |
| `text-layer-missing` | A page-bearing format yields fewer than `filinq.validation.text_layer_min_chars_per_page` (default 32) extractable chars/page. Carries `suggestedAction: "ocr"`. |
| `metadata-incomplete` | A required metadata field for the profile is absent/empty. Names the `field`. |

A finding contains only `checkId`, `severity`, a localised `message` (+ params),
optional `field`, and optional `suggestedAction` — never document content.

## Verdict aggregation

`validationStatus` aggregates findings: any `blocking` finding → `failed`;
otherwise any `warning` → `warnings`; otherwise `passed`. Records never validated
render as **not yet validated** (absent value); there is no backfill migration.

## Profiles

Profiles live in app config `filinq.validation.profiles` (JSON):

```json
{
  "default": {
    "allowedMimes": ["application/pdf", "text/plain"],
    "requiredFields": [],
    "severities": { "pdf-encrypted": "warning" }
  },
  "factuur": {
    "allowedMimes": ["application/pdf"],
    "requiredFields": ["invoiceNumber"],
    "severities": { "pdf-encrypted": "blocking" }
  }
}
```

Per document type: an allowed-mime list, required metadata fields, and a severity
per check (`off | warning | blocking`). Unknown document types resolve to the
`default` profile. Shipped defaults set every check to `warning` (no blocking out
of the box). Profile reads happen at validation time, so config changes propagate
without a restart.

## On-demand endpoint

`POST /apps/filinq/api/validation/validate`

```json
{ "fileId": 42, "documentType": "factuur" }
```

Response (200):

```json
{
  "validationStatus": "warnings",
  "validationFindings": [
    { "checkId": "pdf-encrypted", "severity": "warning", "message": "…", "params": {} }
  ]
}
```

`#[NoAdminRequired]`; the file is resolved through the requesting user's folder
(404 when not resolvable, no existence disclosure — IDOR-safe per ADR-005). The
endpoint computes findings **without persisting** anything.

## Stored verdict (calculation)

`validationStatus` and `validationFindings` are declared as
`x-openregister-calculations` on the `generatedDocument` schema in
`filinq_register.json`, with `DocumentValidationService` (backend
`filinq.validation`) as the computation backend. Until OpenRegister's ADR-031
calculation runtime invokes the service directly, the Filinq event-listener
fallback (`ValidationRunner`) computes and stores the verdict on object
create/update. The listener contains no validation logic.

## Configuration

| Key | Default | Meaning |
|-----|---------|---------|
| `filinq.validation.profiles` | `{}` (defaults apply) | Per-type validation profiles. |
| `filinq.validation.text_layer_min_chars_per_page` | `32` | Text-layer threshold. |

## Note: full-text search

Full-text search across documents is **OpenRegister's domain** (ADR-022) and is
surfaced through Nextcloud's unified search — Filinq does not ship a separate
search integration. (The previous Apache Solr document was removed; no Solr
integration exists in the codebase.)

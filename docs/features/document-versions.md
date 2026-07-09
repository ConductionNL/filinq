---
id: document-versions
title: Document Versions
sidebar_label: Document Versions
sidebar_position: 8
description: List, open, restore and compare a document's Nextcloud file versions
keywords:
  - versions
  - version history
  - files_versions
  - restore
  - rollback
---

# 🕑 Document Versions

## Overview

The **Versies** (Versions) view surfaces the Nextcloud file-version history of a
document — read directly from the platform's `files_versions` capability. DocuDesk
introduces **no** version storage of its own: every version is read through
Nextcloud's `IVersionManager`, so retention, deduplication and storage remain
Nextcloud's responsibility (ADR-022).

Reached from a document's row action (**Versions**) in *My Documents*, the view is a
document-scoped page (`/versions?fileId=…`) in the ADR-001 document-detail family,
alongside comparison — not a new top-level menu.

## What you can do

- **List** every version of the document, newest first, showing timestamp, author
  and size, with the current version distinguished.
- **Open / download** the bytes of any specific version.
- **Restore** a prior version (requires write access). Nextcloud first preserves the
  current state as a new version, then rolls back — you confirm before it happens.
- **Compare** a version with the current document, handing the `fileId` +
  `versionTimestamp` pair to the existing [Document Comparison](document-comparison.md)
  flow. Compare is offered only for text-extractable documents.

## Graceful degradation

When the `files_versions` app is disabled on the instance, the view stays present in
the detail-tab family and renders an informative *"File versions are not available on
this instance"* notice rather than an error, keeping the information architecture
stable.

## Authorization

Both listing and restore are guarded per-object through the requesting user's folder:
a document whose underlying Nextcloud file the caller cannot read is indistinguishable
from a non-existent one (IDOR-safe, ADR-005). Restore additionally requires write
access; a read-only caller is rejected.

## Endpoints

| Method | Route | Purpose |
| ------ | ----- | ------- |
| `GET`  | `/api/documents/{fileId}/versions` | List versions (paginated: `limit`, `offset`) |
| `GET`  | `/api/documents/{fileId}/versions/{versionTimestamp}/download` | Download a version's bytes (`0` = current) |
| `POST` | `/api/documents/{fileId}/versions/{versionTimestamp}/restore` | Restore a prior version (write required) |

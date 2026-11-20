# Document Register

## Overview

The document register defines the data model for Filinq's document analysis results, stored separately from the consent register. It is defined in `lib/Settings/document_register.json` and contains three schemas for different document management aspects.

## Schemas

| Schema | Purpose |
|--------|---------|
| **report** | Stores document analysis results: file metadata, detected entities, risk assessment, processing status |
| **template** | Stores document template definitions for reusable templates |
| **entity** | Manages cross-document entity tracking |

## Register Details

- **Slug**: `document`
- **Version**: `0.0.1`
- **Location**: `lib/Settings/document_register.json`
- **Validation**: Soft (no enforced property definitions)

## A document's activity

Every action on a document lands in the OpenRegister audit trail of the
document's record, with who did it and when (REQ-SCR-007). The entry is written
where the action happens:

| Action | Written by |
| --- | --- |
| Opened | Filinq's file viewer, through `POST api/documents/{fileId}/opened`; the server checks the reader may read the file |
| Downloaded | Filinq's own download routes, and Nextcloud's direct download (the Files app, a direct link) with route `files` |
| Edited, deleted, shared | Nextcloud's file and share events |
| Version restored | The version restore |
| Anonymised | The anonymisation, once its link is recorded |
| Signed | A completed signing request, whichever provider completed it |

Previews, thumbnails and sync are not written, so the trail stays readable. A
file without a document record gets no entry: filinq keeps no second store.

The file viewer shows the trail as **Activity** beside the notes, newest first,
read from `GET api/documents/{fileId}/audit`.

## Note

The document register is separate from `filinq_register.json` which handles consent-related schemas. The document register is not auto-loaded on boot and needs manual import.

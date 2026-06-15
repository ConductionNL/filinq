# Document Register

## Overview

The document register defines the data model for DocuDesk's document analysis results, stored separately from the consent register. It is defined in `lib/Settings/document_register.json` and contains three schemas for different document management aspects.

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

## Note

The document register is separate from `docudesk_register.json` which handles consent-related schemas. The document register is not auto-loaded on boot and needs manual import.

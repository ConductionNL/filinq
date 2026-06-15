---
status: pr-created
issue: 153
change: advanced-template-management
kind: code
---

# Design: advanced-template-management

## Summary

Extend DocuDesk's template management with versioning, categories/tags, a WYSIWYG editor, conditional section UI, preview, duplication, and edit locking.

## Architecture Overview

The backend services (TemplateService, TemplateVersionService, TemplatePreviewService, TemplateRenderer) and controller (TemplatesController) are already implemented. This change focuses on:

1. Extending the OpenRegister schema for templates (category, tags, lockedBy, lockedAt fields).
2. Adding the templateVersion schema to the register.
3. Implementing the full TemplateDetail.vue with WYSIWYG editor, preview, version history, conditional section UI, and lock support.
4. Adding comprehensive unit tests for TemplateVersionService and TemplatePreviewService.

## Backend Design

### TemplateService (existing)
- CRUD, lock/unlock, duplication — already implemented.

### TemplateVersionService (existing)
- Version snapshots, listing, restore, diff — already implemented.

### TemplatePreviewService (existing)
- Renders template with sample data via Twig sandbox — already implemented.

### TemplateRenderer (existing)
- Converts `data-condition-field/op/value` HTML attributes to Twig `{% if %}` blocks — already implemented.

## Frontend Design

### TemplateDetail.vue
Rich template editor with four panels/tabs:
1. **Editor** — contenteditable WYSIWYG with formatting toolbar (bold, italic, underline, lists, headings, tables). Outputs valid HTML that can contain Twig merge fields.
2. **Preview** — rendered HTML preview using POST /api/templates/{id}/preview or POST /api/templates/preview.
3. **Versions** — list of version history with restore and diff links.
4. **Conditional sections** — UI to insert `data-condition-*` attributes into selected content.

### Locking
- On mount, acquire lock via POST /api/templates/{id}/lock.
- On unmount, release lock via DELETE /api/templates/{id}/lock.
- Display lock owner and timestamp if locked by another user.

## Schema Changes

### template schema (extend existing)
- `category` (string) — categorisation (e.g. "beschikkingen", "brieven")
- `tags` (array of string) — free-form tags for search
- `lockedBy` (string, nullable) — UID of user holding edit lock
- `lockedAt` (string, date-time, nullable) — ISO 8601 timestamp of lock acquisition

### templateVersion schema (new)
- `templateId` (string, required) — parent template UUID
- `version` (integer) — sequential version number
- `content` (string) — snapshot of template HTML content
- `name` (string) — template name at time of snapshot
- `description` (string)
- `format` (string)
- `orientation` (string)
- `editor` (string) — Nextcloud user ID
- `changelog` (string) — optional change note

## API Endpoints (existing, no changes needed)

| Method | URL | Description |
|--------|-----|-------------|
| GET | /api/templates | List templates |
| POST | /api/templates | Create template |
| GET | /api/templates/{id} | Get template |
| PUT | /api/templates/{id} | Update template (creates version) |
| DELETE | /api/templates/{id} | Delete template |
| GET | /api/templates/{id}/versions | List version history |
| GET | /api/templates/{id}/versions/diff | Compare two versions |
| POST | /api/templates/{id}/versions/{versionId}/restore | Restore version |
| POST | /api/templates/{id}/preview | Preview with sample data |
| POST | /api/templates/preview | Preview raw content |
| POST | /api/templates/{id}/duplicate | Duplicate template |
| POST | /api/templates/{id}/lock | Acquire edit lock |
| DELETE | /api/templates/{id}/lock | Release edit lock |

## Declarative-vs-imperative decision

The versioning, locking, and categorisation requirements are all implemented as imperative service code rather than schema-register declarative blocks because:
- Versioning requires cross-object writes (create version, update template) that cannot be expressed as a single `x-openregister-lifecycle` transition.
- Locking requires timed expiry logic that is not supported by schema extensions.
- Categories/tags are simple data fields that fit the standard schema property model.

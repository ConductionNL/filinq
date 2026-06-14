---
status: pr-created
kind: code
change: advanced-template-management
issue: 30
---

# Advanced Template Management — Design

## Summary

Extend DocuDesk's existing template management with versioning, categories, search, a
WYSIWYG template editor, and conditional section support. The existing template management
provides basic CRUD for Twig/HTML templates; this change adds the lifecycle and editing
capabilities that government organisations require for managing large template collections.

## What Was Already There

- `TemplatesController` — CRUD endpoints (`GET/POST/PUT/DELETE /api/templates`)
- `TemplateService` — OpenRegister-backed template CRUD
- `TemplateRenderer` — Twig sandbox for safe template rendering
- Schema `template` and register `templates` in `docudesk_register.json`

## Reuse Analysis

- **ObjectService** (OpenRegister) — used directly for all data storage; no custom DB tables.
- **TemplateRenderer** — existing Twig sandbox extended with `convertConditionalSections()`.
- **OpenRegisterResolver** — existing config resolver extended with `getVersionRegisterAndSchema()`.
- No OpenRegister shared Vue components used here; template editing is a specialised
  WYSIWYG surface not covered by `CnFormDialog`.

## Declarative-vs-imperative decision

- Lock logic (`acquireLock` / `releaseLock`) is implemented imperatively in `TemplateService`
  because OpenRegister's `ObjectService::lockObject()` targets files, not OpenRegister objects,
  so a custom timeout-aware field approach was chosen.
- Version storage is imperative (each version = an OpenRegister `templateVersion` object)
  because OpenRegister does not natively snapshot full object states on edit.

## Architecture

### Backend

| Class | Purpose |
|---|---|
| `TemplatesController` | HTTP layer — thin methods delegating to services |
| `TemplateService` | CRUD + lock + duplicate business logic |
| `TemplateVersionService` | Version snapshot creation, retrieval, restore, diff |
| `TemplatePreviewService` | Preview with Twig rendering + conditional section conversion |
| `TemplateRenderer` | Sandboxed Twig rendering + `convertConditionalSections()` |
| `OpenRegisterResolver` | Register/schema ID resolution from settings |

### Endpoints Added / Extended

| Method | Path | Description |
|--------|------|-------------|
| `GET`  | `/api/templates` | List templates with namespace/category/tag/search filters |
| `POST` | `/api/templates` | Create template |
| `GET`  | `/api/templates/{id}` | Get single template |
| `PUT`  | `/api/templates/{id}` | Update template (auto-creates version snapshot) |
| `DELETE` | `/api/templates/{id}` | Delete template |
| `GET`  | `/api/templates/{id}/versions` | List version history |
| `GET`  | `/api/templates/{id}/versions/diff` | Get two versions for comparison |
| `POST` | `/api/templates/{id}/versions/{versionId}/restore` | Restore to prior version |
| `POST` | `/api/templates/preview` | Preview raw content with sample data |
| `POST` | `/api/templates/{id}/preview` | Preview existing template with sample data |
| `POST` | `/api/templates/{id}/duplicate` | Duplicate template |
| `POST` | `/api/templates/{id}/lock` | Acquire edit lock |
| `DELETE` | `/api/templates/{id}/lock` | Release edit lock |

### Frontend

| File | Purpose |
|------|---------|
| `src/views/templates/TemplateIndex.vue` | Template list with category filter and search |
| `src/views/templates/TemplateDetail.vue` | WYSIWYG editor with preview and version history tabs |
| `src/dialogs/MergeFieldDialog.vue` | Dialog for inserting Twig merge-field tokens |
| `src/dialogs/ConditionalSectionDialog.vue` | Dialog for defining conditional sections |
| `src/store/modules/template.js` | Pinia store — all API calls and state |

## Conditional Sections

The UI represents conditional sections as `<div data-condition-field="…" data-condition-op="…"
data-condition-value="…">` attributes. On render, `TemplateRenderer::convertConditionalSections()`
parses these attributes and emits Twig `{% if … %}…{% endif %}` blocks before handing off to
the Twig sandbox.

Supported operators: `equals`, `not_equals`, `contains`, `is_empty`, `is_not_empty`.

## Template Locking

Lock state is stored in `lockedBy` / `lockedAt` fields on the template object. Lock expires
after 15 minutes (configurable via `TemplateService::LOCK_TIMEOUT_MINUTES`). Acquiring a lock
on an expired lock from another user succeeds transparently. Releasing a lock owned by another
user returns HTTP 403.

## Seed Data

Three realistic template objects covering categories used by Dutch government organisations.

## MCP coverage

No MCP surface — template management is an editor UI feature with no programmatic automation
use case suitable for LLM tool calling at this time.

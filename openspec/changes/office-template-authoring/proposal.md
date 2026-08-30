---
kind: code
---

# Proposal: office-template-authoring

## Why

Filinq templates today are Twig/HTML strings (`template.content`, `format:
html` — verified in `lib/Settings/filinq_register.json` and
`lib/Service/TemplateRenderer.php`). That makes template authoring a
developer activity, while the market baseline is office-file-native
authoring: **8+ competitors** let the communications department own templates
inside real Word/LibreOffice files — Xential, SmartDocuments (drag-drop
editor, 150+ gemeenten), iWRITER/Templafy, Docmosis, Carbone, docxtemplater,
Templater and Fluent all author in `.docx`/`.odt` with merge tags
(research-competitors.md, feature theme #1: "Template authoring IN
Word/Office/LO files — 8+ competitors. Twig/HTML = developer-facing").

Dutch-government demand is concrete and tendered:

- **Den Helder 306597/297564**: document-generator tender requiring migration
  of **486 existing house-style templates + 433 text fragments** (wens
  3.3.4.3 adds version rollback), i.e. bulk import of office files is an
  award criterion, not a nicety.
- **Noord-Brabant 422049** (2026-04-21): "sjabloonvoorziening" explicitly
  including template migration.
- User-wishes #7: central library, versioning/rollback, huisstijl, and
  **migration tooling** rank as a top-10 deduplicated wish (GH #82, NC forum
  threads).

Filinq already has the two halves this change joins: a template register
with versioning/diff/restore/lock/duplicate/preview
(`TemplateService`/`TemplateVersionService`/`TemplatesController`, spec
`template-management` REQ-TMPL-01..12) and a file-to-PDF conversion cascade
(`PdfConversionService` walking OfficeApp → LibreOffice headless → PhpWord →
mPDF → EML, verified in `lib/Service/Conversion/`). What is missing is the
bridge: accepting a real DOCX/ODT with merge tags as a first-class template,
validating its tags against a bound register schema, rendering it through the
cascade, and importing existing template estates in bulk.

## What Changes

- **Office-file templates as a first-class template type.** The `template`
  schema gains `templateType` (`twig` | `office`), a reference to the stored
  office source file, extracted merge-field metadata, and an optional bound
  register/schema. Existing Twig/HTML templates are untouched (`templateType`
  defaults to `twig`); no breaking change.
- **Merge-tag syntax: PhpWord `${field}` macros.** Tags are authored in Word/
  LibreOffice as plain `${field}` / `${block}` text runs and processed with
  `phpoffice/phpword`'s `TemplateProcessor` (already a composer dependency,
  `^1.2`). Rationale vs Carbone `{d.field}` / docxtemplater in design.md —
  short version: fully local PHP, zero new dependencies, no Node sidecar, no
  external API (config.yaml local-only rule).
- **Tag validation + error reporting on upload**: extracted tags are checked
  against the bound register schema's properties; unknown tags produce a
  structured validation report (block or warn, admin-configurable).
- **Rendering through the existing conversion cascade**: fill tags with
  `TemplateProcessor`, then convert the filled DOCX via `PdfConversionService`
  (LibreOffice headless etc.) for PDF/PDF-A output; DOCX passthrough output is
  also supported.
- **Reusable text fragments (bouwstenen)**: a new `textFragment` schema in the
  templates register; fragments are referenced from office templates
  (`${fragment:slug}`) and resolved in a pre-processing pass.
- **Bulk import / migration tooling**: upload a ZIP (or point at a folder) of
  DOCX/ODT house-style templates; an async job creates office templates,
  extracts tags, and produces an import report; an interactive mapping step
  lets the operator map unknown tags to schema properties (Den Helder-scale:
  hundreds of templates).
- **Versioning / lock / preview / duplicate parity**: the existing
  `template-management` capabilities keep working for office templates
  (version snapshots reference the office source revision; preview renders
  via the cascade).

## Capabilities

### New Capabilities

- `office-template-authoring`: DOCX/ODT files with `${field}` merge tags as
  first-class templates — upload, tag extraction and validation against a
  bound register schema, rendering through the conversion cascade, reusable
  text fragments (bouwstenen), and bulk import/migration tooling.

### Modified Capabilities

- `template-management`: the template data model is extended with
  `templateType`, office-source references, merge-field metadata, and schema
  binding; versioning, locking, preview, and duplication requirements are
  extended to cover office templates. Existing Twig/HTML requirements are
  unchanged.

## Impact

- **Backend**: new `OfficeTemplateService` (tag extraction/validation/fill via
  PhpWord `TemplateProcessor`), new `TemplateImportService` (ZIP/folder bulk
  import job), extensions to `TemplateService`/`TemplateVersionService`/
  `TemplatePreviewService`; render path reuses `PdfConversionService` and
  `DocumentService`.
- **Register**: `lib/Settings/filinq_register.json` — `template` and
  `templateVersion` schema extensions, new `textFragment` schema, templates
  register version bump (currently `2.0.0`).
- **Routes**: new upload/validate/import endpoints under `api/templates/…`
  (`appinfo/routes.php`).
- **Frontend**: template index/detail (`src/views/templates/`) gain office
  upload, tag report, fragment management, and import wizard using
  `@conduction/nextcloud-vue` components (ADR-012).
- **Dependencies**: none new — `phpoffice/phpword ^1.2` already shipped
  (verified in `composer.json`); LibreOffice headless already the conversion
  backbone.
- **Sibling boundaries**: no OpenRegister/OpenConnector changes; data
  resolution keeps using the existing `DataResolverService` contract.

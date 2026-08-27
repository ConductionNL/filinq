# Design: office-template-authoring

## Context

Verified current state (HEAD of this worktree):

- Templates are OpenRegister objects in the `templates` register (v2.0.0),
  schemas `template` + `templateVersion`
  (`lib/Settings/filinq_register.json`). `template.content` is a Twig/HTML
  string (`format: html`); required fields are `name`, `content`,
  `namespace`.
- `TemplateService` (CRUD, advisory lock, duplicate), `TemplateVersionService`
  (snapshot per content change, paginated history, diff, restore),
  `TemplatePreviewService` (Twig → HTML preview), `TemplateRenderer`
  (sandboxed Twig) — all resolve register/schema via `OpenRegisterResolver`
  and store exclusively through OR `ObjectService` (ADR-001, no custom
  tables).
- `PdfConversionService` walks an ordered backend cascade
  (`lib/Service/Conversion/`): OfficeApp → LibreOffice headless → PhpWord →
  mPDF → EML; `LibreOfficeHeadlessBackend` accepts DOCX/ODT/RTF/… (verified
  `SUPPORTED_MIMES`) and emits PDF via
  `pdf:writer_pdf_Export:UseTaggedPDF=true,SelectPdfVersion=2`.
- `DocumentService::generateDocument()` renders a Twig template + resolved
  `dataRefs` (`DataResolverService`) to pdf/odf/html and logs a
  `generatedDocument` object.
- `composer.json` already requires `phpoffice/phpword: ^1.2` (used by
  `PhpWordBackend`), `mpdf/mpdf: ^8.2`, `twig/twig: ^3.27`.
- UI: `src/views/templates/TemplateIndex.vue` + `TemplateDetail.vue`.

Constraint (openspec/config.yaml): all document processing stays local — no
external API calls. GDPR note: template *data* filling happens at generation
time with the same data-resolution path as today; this change introduces no
new personal-data storage (templates and fragments are content, not subject
data).

## Goals / Non-Goals

**Goals:**

- A communications-department user uploads a DOCX with `${field}` tags and it
  becomes a working, versioned, previewable template — no developer involved.
- Tag errors are caught at upload time, against the register schema the
  template is bound to, not at first failed generation.
- Bulk migration of an existing house-style estate (hundreds of DOCX files +
  text fragments) is a supported, reportable operation.
- Text fragments (bouwstenen) are managed once and reused across templates.
- Every existing template feature (versions, diff, restore, lock, duplicate,
  preview, namespaces) works identically for office templates.

**Non-Goals:**

- No in-browser WYSIWYG DOCX editor — authoring happens in Word/LibreOffice/
  Collabora; Filinq manages, validates, and renders. (Collabora editing of
  the stored source file is a natural follow-up, not this change.)
- No charts/dynamic-image modules (competitor theme #9) this wave.
- No change to the Twig template type, the Twig sandbox, or `PdfService`.
- No guided-interview wizard (separate gap; SmartDocuments/docassemble
  territory).
- No multi-format simultaneous output (XLSX/PPTX) — DOCX and PDF only.

## Decisions

### D1 — Merge-tag syntax: PhpWord `${field}` macros (TemplateProcessor)

**Chosen:** the `${field}` macro syntax processed by
`\PhpOffice\PhpWord\TemplateProcessor` — `getVariables()` for extraction,
`setValue()`/`cloneRow()`/`cloneBlock()` for filling. Dotted paths
(`${applicant.name}`) are supported by treating the full dotted string as the
macro name and resolving it against the data context with the existing
`adbario/php-dot-notation` dependency. Repeating content uses
TemplateProcessor's native block/row cloning with `${block}`…`${/block}`
delimiters.

**Rejected alternatives:**

- *Carbone `{d.field}`*: Carbone's renderer is a Node.js engine (open-core,
  CCL); embedding it means a Node sidecar or their SaaS API — the latter
  violates the local-only processing rule outright, the former adds an ExApp
  for something PHP already does.
- *docxtemplater `{tag}`*: browser/Node JS library (MIT core + paid modules);
  server-side rendering in a PHP Nextcloud app would push template filling
  into the client or a sidecar, and paid modules gate loops/images.
- *Word content controls (structured document tags)*: robust but authorable
  only via Word's developer ribbon — precisely the developer dependency this
  change removes; PhpWord also has no first-class content-control API.

`${field}` is visible plain text any Word/LibreOffice user can type, it
survives ODT→DOCX conversion, and the processor is an existing, already-vetted
dependency. This satisfies ADR-011 (reuse before build): PhpWord is already in
`composer.json` and no OpenRegister `lib/Formats/`/`lib/Service/` utility
covers DOCX templating (checked — OR owns text *extraction*, not document
*assembly*).

### D2 — Office source storage: Nextcloud file referenced by `sourceFileId`

The DOCX source is binary; OR object properties are JSON. **Chosen:** store
the uploaded source in an app-managed folder (same pattern as anonymization
outputs — `anonymizationLink` stores `sourceFileId`/`anonymizedFileId`
NC file ids, verified in the register), and reference it from the template
object as `sourceFileId` plus a `contentHash` (sha256) for version integrity.
`template.content` for office templates holds the extracted *text projection*
(for search/diff display), not the binary.

**Rejected:** base64-embedding the DOCX in `template.content` (bloats every
list/search response and version snapshot; OR objects are not blob stores).

Version snapshots (`templateVersion`) gain `sourceFileId` + `contentHash` so a
restore can re-point to the exact office revision; the version service keeps
one immutable copy per version in the app folder (office sources are small —
tens of KB — and Den Helder-scale estates are hundreds, not millions).

### D3 — ODT handling: accepted at upload, normalised to DOCX

`TemplateProcessor` operates on DOCX (Word2007) only. ODT uploads are
accepted and converted to DOCX once, at upload time, via the existing
LibreOffice headless backend (which supports ODT input, verified); the DOCX
becomes the canonical source, the original ODT is retained alongside for
provenance. The upload response flags the conversion so the author can check
fidelity. Rationale: one canonical fill path instead of two engines; LO
round-trips `${field}` text runs losslessly.

### D4 — Tag validation against a bound schema

Templates gain optional `boundRegister`/`boundSchema` (slugs). On upload (and
on every new office version) the service extracts tags with `getVariables()`
and classifies each against the bound schema's `properties` (read through
OR's schema API via the container, same access pattern as
`OpenRegisterResolver`): `known` (property or dotted sub-path exists),
`fragment` (`fragment:` prefix), `unknown`. Unknown tags produce a structured
report on the template object (`tagReport`). Whether unknown tags block or
warn is admin-configurable (`filinq.templates.unknown_tag_severity`,
default `warning` — migration estates must be importable before mapping).
Templates without a binding get a `not validated against a schema` notice,
never a block (Twig templates today have no binding either).

### D5 — Rendering path: fill, then cascade

`DocumentService::generateDocument()` branches on `templateType`:

- `twig` (existing): Twig render → HTML → `PdfService`/mPDF — unchanged.
- `office` (new): resolve data (same `DataResolverService` + fragments
  pre-pass) → `TemplateProcessor` fill into a temp DOCX → output `docx`
  as-is, or `pdf`/`pdfa` via `PdfConversionService::convertToPdf()` (the
  LibreOffice cascade). Huisstijl for office templates is the document's own
  house style (that is the point of office-native authoring); the
  `huisstijlId` option is ignored for `office` templates with a warning.

**Declarative vs imperative (ADR-031):** document generation is an explicitly
listed valid imperative exception (external-binary conversion, file
assembly), matching how the existing generation/conversion services already
work — no `x-openregister-*` behaviour annotation can express "run soffice".
The *data* side stays declarative: the schema extensions, the new
`textFragment` schema, and the register bump are pure `filinq_register.json`
edits; no lifecycle/aggregation/notification annotations are added or needed.

### D6 — Text fragments (bouwstenen): schema + pre-pass resolution

New `textFragment` schema in the `templates` register: `name`, `slug`
(unique per namespace), `content` (plain text/limited HTML), `namespace`,
`category`, `tags`, `language`. A template references a fragment as
`${fragment:slug}`. Resolution is a **pre-processing pass** in the render
path (office: `setValue('fragment:slug', …)` before field filling; Twig: a
string substitution before `TemplateRenderer::renderTemplate()` so the Twig
sandbox's fixed function whitelist is untouched). Fragments may contain
`${field}` tags themselves (one level — no recursive fragment nesting, cycle
risk). Missing fragments render as a visible `[ontbrekende bouwsteen: slug]`
marker and a generation warning, never silent emptiness.

### D7 — Bulk import: async job + interactive mapping, OR-native state

`POST /api/templates/import` accepts a ZIP (or a Files-app folder path). An
import job (queued via `IJobList`, same pattern as
`DocumentService::generateBulk()` jobs) unpacks, creates one `office`
template per DOCX/ODT (namespace, category from folder structure), runs tag
extraction/validation per file, and writes a per-file import report. Job
state and the report live on a new `templateImportJob` schema (templates
register) — not in memory (lesson: GH #287 in-memory sessions). The UI wizard
then walks unresolved tags: the operator maps tag → schema property (stored
as `fieldMap` on the template, applied as an aliasing layer at fill time) or
accepts them as ad-hoc data fields. `.txt`/`.html` files in a `fragments/`
ZIP folder import as `textFragment` objects (Den Helder: 433 fragments).

### D8 — Security: no macro execution, escaped values, sandbox parity

- Macro-enabled uploads (`.docm`, `.dotm`, or a DOCX containing
  `vbaProject.bin`) are **rejected** at upload (a template store must never
  become a macro-distribution vector).
- Filled values are inserted with `setValue()` (XML-escaped by
  TemplateProcessor) — data can never inject WordprocessingML or trigger the
  Twig path.
- The fill step executes no template-authored logic (unlike Twig): the office
  path is data-substitution only, so it needs no sandbox — conditionality
  stays declarative via block cloning.
- Uploads are size-capped (`filinq.templates.max_upload_bytes`, default
  20 MB) and mime-sniffed (reuse the extension/mime approach proven in
  `DocumentValidationService`).

### D9 — Frontend (ADR-012)

`TemplateIndex.vue` gains a type column/filter and the import-wizard entry;
`TemplateDetail.vue` gains office-specific panels (source download, tag
report, field mapping, preview via cascade). All new UI uses
`@conduction/nextcloud-vue` components (`CnIndexPage`, `CnDataTable`,
`CnFormDialog`) and NL Design System tokens via NC CSS variables (ADR-003);
dialogs live in `src/modals/` per modal-isolation rules. Fragment management
is a tab on the template index (same `CnDataTable` pattern).

## OpenRegister usage (ADR-001)

| Operation | OR service |
|---|---|
| Template/fragment/import-job CRUD | `ObjectService` (`saveObject`/`find`/`searchObjectsPaginated`/`deleteObject`) via `OpenRegisterResolver`, exactly as `TemplateService` does today |
| Office source + version binaries | NC app folder files referenced by file id (pattern of `anonymizationLink.sourceFileId`) |
| Bound-schema property lookup (tag validation) | OR schema read via the container (no schema duplication in Filinq) |
| Generation audit | existing `generatedDocument` objects — `templateType` is added to the logged metadata |

No custom database tables. Register import stays via
`ConfigurationService::importFromApp()` on boot; templates register version
bumps `2.0.0 → 2.1.0`.

## Seed Data

Shipped as demo/seed objects (municipality-flavoured, nil-UUID pattern):

```json
{
  "template": {
    "id": "00000000-0000-0000-0000-000000000101",
    "name": "Beschikking parkeervergunning",
    "namespace": "filinq",
    "templateType": "office",
    "sourceFileId": 0,
    "contentHash": "<sha256-of-seed-docx>",
    "boundRegister": "dossier",
    "boundSchema": "dossier",
    "mergeFields": ["aanvrager.naam", "aanvrager.adres", "besluit.datum", "fragment:ondertekening-burgemeester"],
    "fieldMap": {},
    "category": "beschikkingen",
    "tags": ["parkeren", "huisstijl-2026"]
  },
  "textFragment": {
    "id": "00000000-0000-0000-0000-000000000102",
    "name": "Ondertekening burgemeester",
    "slug": "ondertekening-burgemeester",
    "namespace": "filinq",
    "content": "Hoogachtend,\nde burgemeester van Demostad,\nnamens deze,",
    "category": "ondertekening",
    "language": "nl"
  },
  "templateImportJob": {
    "id": "00000000-0000-0000-0000-000000000103",
    "status": "completed",
    "totalFiles": 3,
    "imported": 3,
    "failed": 0,
    "startedBy": "demo-communicatie",
    "report": [{"file": "beschikking-parkeervergunning.docx", "tags": 4, "unknownTags": 0}]
  }
}
```

A seed DOCX with the four tags above ships under `tests/sample-documents/`
(also used by unit tests). Seeding follows the existing register-import
mechanism; the demo consultancy flavour ("Demostad") matches the shipped
dossier demo data (`demostad-woo-2025-017` etc., verified in the register).

## Risks / Trade-offs

- [Word splits `${field}` across XML runs when authors edit tag text
  incrementally] → TemplateProcessor's `fixBrokenMacros()` handles the common
  cases; the upload tag report is the safety net — a tag the author expects
  but extraction misses is visible immediately. Documented in the user docs.
- [ODT→DOCX normalisation can shift layout] → conversion is flagged in the
  upload response + preview is one click; original ODT retained.
- [LibreOffice fidelity for exotic house styles] → preview via the same
  cascade used at generation time, so what the author previews is what
  citizens get; OfficeApp (Collabora) backend ranks above LO in the cascade
  when installed.
- [Bulk import of hundreds of files under the serialized soffice lock is
  slow] → import validates tags (PhpWord, no soffice) synchronously per file
  but renders nothing; conversion only happens on preview/generation. ODT
  normalisation is the only soffice work at import and is queued.
- [Fragment changes silently alter future renders of many templates] →
  generation metadata logs fragment slugs + their object versions
  (`generatedDocument.dataRefs` already records sources); fragment edits are
  OR-audited.
- [`content` text projection drifts from binary source] → `contentHash`
  recomputed on every upload; version restore re-points both together.

## Migration Plan

1. Register bump ships the schema extensions (additive, no data migration —
   existing templates read as `templateType: twig` via default).
2. Backend services + routes land behind no flag (new endpoints, no changed
   contracts).
3. UI ships last; until then office endpoints are API-only (parity with how
   template management itself shipped).
4. Rollback = revert app release; additive schema fields are inert for old
   code.

## Open Questions

- Should `boundSchema` binding be *required* for office templates once an
  organisation enables blocking tag validation? (Provisional: no — ad-hoc
  data templates stay legal; the admin severity knob only governs unknown
  tags on *bound* templates.)
- Whether Collabora in-place editing of the stored source (richdocuments
  integration) lands as a fast-follow — depends on ecosystem priority, not
  this spec.

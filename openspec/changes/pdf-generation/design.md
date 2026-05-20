## Context

DocuDesk needed a shared PDF generation subsystem. The requirements converged on two existing PHP libraries already present in the broader Conduction ecosystem:

- **mPDF ^8.2** — battle-tested HTML-to-PDF conversion, wide CSS support, configurable page formats and metadata.
- **Twig ^3.18** — mature templating engine with a first-class `SandboxExtension` that supports policy-based whitelisting of functions, filters, and tags.

The design question was not *which* libraries to use, but *how to structure them* inside DocuDesk's layered backend so the service is injectable, testable, and safely isolated from template injection attacks.

## Goals / Non-Goals

**Goals:**

- An injectable `PdfService` that any co-installed Nextcloud app can call by resolving `OCA\DocuDesk\Service\PdfService::class` from the DI container.
- A completely stateless rendering path — template content is provided by the caller on every call; nothing is stored.
- A strict Twig sandbox that blocks code-execution primitives (`system`, `exec`, object method calls, property access on objects) while allowing common formatting filters (`upper`, `date`, `number_format`, etc.) and control structures (`if`, `for`, `set`).
- Configurable page output: A4/A3/Letter/Legal, portrait/landscape, per-side margins in mm, document title metadata.
- A thin HTTP endpoint so non-PHP callers can generate PDFs via a REST call.
- Predictable error propagation: invalid Twig → `Exception` with "Template rendering failed"; mPDF failure → `Exception` with code 500.

**Non-Goals:**

- PDF/A-3b or any archival PDF conformance — separate follow-up.
- WCAG 2.1 AA tagged PDF output.
- Template storage, versioning, or retrieval — callers own their templates.
- Rate limiting or quota enforcement on the render endpoint.
- EN/NL i18n for templates — the service is content-agnostic; callers embed their own language in the template string.

## Decisions

### D1. `TemplateRenderer` is extracted from `PdfService`

`PdfService` handles the mPDF lifecycle (temp-directory setup, mPDF instantiation, PDF output capture). `TemplateRenderer` handles the Twig lifecycle (sandbox configuration, `ArrayLoader`, `Environment`, `render()`). Each can be unit-tested in isolation.

**Rationale:** The Twig sandbox configuration is complex enough to warrant its own test surface. Mixing it into `PdfService` would make tests harder to scope and would conflate two independently-replaceable concerns.

**Trade-off:** Two classes instead of one. Acceptable — both are small and cohesive.

### D2. `ArrayLoader` with key `"document"`, `strict_variables: false`

Template content is loaded into a Twig `ArrayLoader` under the fixed key `"document"`. The `Environment` is configured with `strict_variables: false` so undefined variables render as empty strings rather than throwing a `RuntimeError`.

**Rationale:** Callers pass ad-hoc templates that may reference optional fields (e.g. a middle-name field that not all data records include). Throwing on missing keys would require callers to pre-populate every possible key — an unreasonable coupling. The operator sees an empty cell, not a 500 error.

**Trade-off:** Silent empty-string substitution can hide typos in template variable names. Accepted — the template author is expected to validate output in a test environment.

### D3. `SandboxExtension` with `sandboxed: true` (always-on)

The sandbox is instantiated with `sandboxed: true` and cannot be bypassed by the template author. The `SecurityPolicy` allows:
- **23 filters**: `escape`, `e`, `upper`, `lower`, `trim`, `nl2br`, `date`, `number_format`, `join`, `split`, `first`, `last`, `length`, `default`, `raw`, `sort`, `reverse`, `keys`, `values`, `merge`, `slice`, `batch`, `column`, `round`, `abs`
- **5 functions**: `range`, `cycle`, `date`, `max`, `min`
- **10 tags**: `if`, `for`, `set`, `block`, `extends`, `include`, `macro`, `spaceless`, `apply`, `autoescape`
- **0 allowed methods or properties** on objects — only plain array data is permitted in templates

**Rationale:** Templates come from operator-supplied strings, not from trusted source code. Allowing any object method access would let a template traverse the PHP object graph and access server internals. Restricting to arrays-only is the conservative default; callers convert their domain objects to arrays before passing them in.

**Alternative considered:** Trusting only known service-internal templates (not sandbox). Rejected: the HTTP endpoint explicitly supports arbitrary operator-supplied templates; a "trusted caller" whitelist would not survive the REST API surface.

### D4. mPDF temp directory at `/tmp/mpdf`, permissions 0777

mPDF requires a writable temp directory for font caches and scratch files. The service creates `/tmp/mpdf` on first use and ensures `0777` permissions on every invocation.

**Rationale:** Nextcloud processes may run as different OS users depending on deployment (web server user vs. cron user). `0777` ensures all of these can write to the same temp directory without coordination. A more restrictive approach (e.g. `0750` with a shared group) would require deployment-specific configuration that the service cannot assume.

**Trade-off:** World-writable directory. Acceptable in `/tmp` (already world-writable per POSIX) — the content is ephemeral font cache and scratch data, not application secrets.

### D5. Page options via an `array $options` parameter

`renderPdf(string $template, array $data = [], array $options = []): string` accepts options as a plain associative array. Supported keys: `format` (string, default `"A4"`), `orientation` (string `"P"` or `"L"`, default `"P"`), `margin` (array with `top`/`right`/`bottom`/`left` in mm, default `15` all), `title` (string, optional).

**Rationale:** An array is the smallest change — no new value objects, no BC-breaking signature revision to add a new option. The option set is small and well-bounded by mPDF's API.

### D6. HTTP endpoint returns `DataDownloadResponse`

`PdfController::render()` returns Nextcloud's `DataDownloadResponse` with the PDF binary, `application/pdf` MIME type, and the caller-supplied filename (defaulting to `"document.pdf"`).

**Rationale:** `DataDownloadResponse` is the Nextcloud-canonical way to stream binary downloads with a `Content-Disposition: attachment` header. Using it avoids manual header manipulation and integrates cleanly with Nextcloud's response middleware.

## Risks / Trade-offs

**[mPDF memory usage on large HTML]** — mPDF loads the entire HTML into memory before rendering. Very large templates (e.g. a table with 10,000 rows) can exhaust PHP's `memory_limit`. Mitigation: document the recommendation to paginate or chunk large data sets in the caller; mPDF's built-in page-break handling covers most practical cases.

**[/tmp/mpdf cross-process contention]** — Multiple concurrent PHP workers all writing to `/tmp/mpdf`. mPDF uses sub-directories per render, so contention is minimal. Accepted.

**[Twig sandbox escape via `raw` filter]** — `raw` is in the allowed-filter list, which marks a value as safe and skips HTML escaping. A template author could pipe untrusted data through `raw` and inject HTML into the output PDF. Mitigation: document that `raw` should only be applied to content the template author controls; the sandbox cannot enforce this at the data level.

**[No authentication on co-installed app callers]** — When a co-installed app calls `PdfService` directly (PHP DI), there is no additional auth check — the calling app is implicitly trusted. The HTTP endpoint does enforce Nextcloud authentication.

## Migration Plan

1. Add `mpdf/mpdf: ^8.2` and `twig/twig: ^3.18` to `composer.json` and run `composer install`.
2. Create `lib/Service/TemplateRenderer.php` and `lib/Service/PdfService.php`.
3. Create `lib/Controller/PdfController.php` and register the route in `appinfo/routes.php`.
4. Verify DI container resolves `PdfService` correctly.

**Rollback:** Remove the three PHP classes and the route entry. Remove the composer dependencies if no other component requires them.

## Seed Data

Not applicable. `PdfService` is a stateless rendering service with no data storage and no OpenRegister schemas. There are no objects to seed.

## Reuse Analysis

Per ADR-001 (Deduplication Check):

- **OpenRegister `ObjectService`** — not applicable; no domain objects are stored.
- **Nextcloud's `TextExtractionService`** — reads text from PDFs; inverse direction from what this service provides. No overlap.
- **Existing Twig usage in DocuDesk** — none prior to this change; `TemplateRenderer` is the first Twig integration point.
- **mPDF usage in DocuDesk** — none prior to this change; `PdfService` is the first mPDF integration point.

No overlap found. Both libraries and the service layer are net-new.

## Open Questions

- Should `PdfService` expose a streaming API for large PDFs (rather than loading the full binary into memory)? Deferred — practical document sizes (< 5 MB) are well within PHP memory limits.
- Should the render endpoint support multipart uploads for binary assets (images, fonts) embedded in the template? Deferred — base64 data URIs cover the current use cases.

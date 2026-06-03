## Context

The anonymisation pipeline today produces output in the same format as input. After OpenRegister's `FileService::anonymizeDocument` returns, DocuDesk writes the file back to Nextcloud Files in its native format. For PDFs that's fine — PDF is the format we want anyway. For DOCX, ODT, RTF, EML, TXT, and other inputs, the output remains trivially editable: `[PERSON_1]` can be deleted and re-typed, track-changes history may persist, embedded metadata still names the original entities.

The redacted artifact should not be a recommendation; it should be a control. Flattening to PDF closes the easy escape hatches. PDF/A-3b — the archival profile — adds: no JavaScript, no external resources, embedded fonts, deterministic rendering, retention-compatible. We already render via PDF/A-3b in `print-preview`; that's the pattern to extend.

The conversion needs to live in DocuDesk (Option β agreed earlier) — OpenRegister stays a generic anonymise primitive; DocuDesk wraps it with privacy-domain-specific behaviour. The conversion subsystem itself is a generic service (`PdfConversionService`); the anonymise endpoint becomes one consumer. Other future consumers can re-use it.

The realistic install variations are:

- **Cloud / managed Nextcloud** with Collabora or OnlyOffice document servers — these have HTTP convert APIs and high-fidelity rendering.
- **Self-hosted with LibreOffice on the host** — `soffice --headless --convert-to pdf` is the universal fallback, supporting nearly every common input format.
- **Containerised minimal Nextcloud** — neither Office app nor LibreOffice. Has to fall back to PHP-only paths (PhpWord + mPDF for what they cover; mPDF directly for HTML/TXT). Spreadsheets (XLSX/ODS) aren't supported on this tier — those inputs 422.
- **Bare** (none of the above, only PDF/HTML/TXT inputs work) — covered by mPDF.

The cascade has to handle all four gracefully. Failure means a clear 422; never a partially-anonymised file written to NC.

## Goals / Non-Goals

**Goals:**

- Make PDF the **default** output format of anonymisation. Operators that need the old behaviour set `outputFormat: "preserve"`.
- Provide a `PdfConversionService` with a documented backend cascade, configurable per tenant, with PDF/A-3b output for archival compliance.
- Cover the realistic install matrix without forcing Office-app installation: PHPOffice (PhpWord) + mPDF make the bare PHP path workable for DOCX, ODT, RTF, HTML, TXT.
- Make conversion failures loud: HTTP 422 with a structured body explaining which backends were attempted and why each failed. Never produce a partial / mixed-format result.
- Lay groundwork for `anonymisation-grondslagen-summary`: that change can rely on "anonymise output is PDF" as an invariant.

**Non-Goals:**

- Add `phpoffice/phpspreadsheet`. XLSX/ODS conversion relies on Office app or LibreOffice; if neither is installed, those inputs 422.
- Convert the *input* to OpenAnonymiser (input normalisation). Pre-anonymise input handling is unchanged.
- Re-convert previously anonymised files. The change applies to new calls only.
- Custom PDF rendering (logos, headers, footers, watermarks). Conversion is content-faithful.
- Per-call choice of PDF/A vs plain PDF. PDF/A-3b is the only output profile in v1.
- Add a separate "convert any file to PDF" endpoint. The conversion service is internal; consumers go through it via DI, not HTTP. (A standalone endpoint can be added in a follow-up if a real use case appears.)

## Decisions

### D1. Conversion lives in DocuDesk, not OpenRegister

Already settled via Option β earlier in exploration: privacy-domain enforcement belongs in the privacy-domain app. OR remains a generic anonymise primitive; if a future caller of OR's anonymise wants the same default, they implement the same wrapper. Cross-app coupling stays minimal.

### D2. Backend cascade order: OfficeApp → PhpWord → mPDF → OR-EML-extractor

The order reflects fidelity (Office apps render Word docs best), then dependency footprint (PhpWord + mPDF are in-process, no host dependency).

```
   try OfficeAppBackend (Collabora / OnlyOffice / Euro Office — whichever is installed + configured)
   ├── on success → done
   └── on no-app / convert-error
       does PhpWord support this MIME?  (DOC/DOCX/ODT/RTF/HTML)
         ├── yes → PhpWord + mPDF
         └── no → does mPDF support directly?  (HTML/TXT)
                   ├── yes → mPDF
                   └── no → is it EML and OR.extractor available?
                             ├── yes → OR.extractText → wrap as TXT → mPDF
                             └── no → 422 ConversionFailedException
```

Tenants can disable individual backends via config (`docudesk.conversion.backends.office_app_enabled` etc.) — useful for: (a) air-gapped installs that need to skip Office-app HTTP probing, (b) testing, (c) installs that want to force the PhpWord fallback for consistency reasons.

**Revision (2026-06-01) — `LibreOfficeHeadlessBackend` dropped.** The original cascade included a standalone shell-out to `soffice --headless --convert-to pdf`. Removed: real-world Nextcloud installs almost never have a host-side `soffice` on PATH (`isAvailable()` always returned false), so the backend was dead weight. Where LibreOffice IS the underlying engine (Collabora is LibreOffice-backed; Euro Office similar) it's reached via the `OfficeAppBackend` integration route — no standalone path needed.

**Alternative considered:** Run all backends in parallel and pick the first to succeed. Rejected — wastes resources, harder to reason about errors, and Office apps may consume HTTP quotas / billed seats unnecessarily.

### D3. Per-backend isolation: `ConversionBackendInterface`

Each backend implements:

```php
interface ConversionBackendInterface {
    public function canHandle(string $mimeType, string $extension): bool;
    public function isAvailable(): bool;  // tenant config + runtime check
    public function convert(File $source): File;
    public function name(): string;        // for logging / 422 body
}
```

`PdfConversionService` injects the ordered list of backends, walks them, calls `isAvailable()` then `canHandle()`, then `convert()`, returning the first success or aggregating failures into a `ConversionFailedException` whose `errors[]` is included in the 422 body.

**Rationale:** keeps each backend isolated, testable, and replaceable. Adding a new backend (e.g. PhpSpreadsheet later, or pandoc) is dropping a new class into the directory and registering it in the service's DI list.

### D4. Output is PDF/A-3b, not plain PDF

PDF/A-3b is the archival profile already used by `print-preview`. It guarantees:

- All fonts embedded (no rendering drift over time).
- No JavaScript, no external references.
- Deterministic rendering across viewers.
- Retention-compatible per Wet open overheid archival standards.

mPDF supports PDF/A via the `'PDFA' => true` config option. PhpWord's PdfWriter delegates to mPDF when configured. Office app integrations (Collabora, OnlyOffice, Euro Office) can produce PDF/A on request — each app exposes its own configuration knob; the `OfficeAppBackend` requests PDF/A-3b where the underlying converter supports it and falls through to the next cascade step where it doesn't.

If a backend can't reliably produce PDF/A-3b at conversion time, it MUST fall through to the next backend rather than emit plain PDF. A successful `convert()` always returns PDF/A-3b. In v1 we don't expose plain PDF; if a real use case appears, follow-up.

### D5. Conversion failure is HTTP 422, never a partial result

If `PdfConversionService::convertToPdf()` throws, the anonymise endpoint:

1. Deletes the un-converted anonymised file from NC (rollback — the operator should not see a half-finished output).
2. Returns HTTP 422 with body:

```json
{
  "error": "Conversion to PDF failed; anonymisation rolled back.",
  "conversionAttempts": [
    {"backend": "office_app", "available": false, "reason": "OnlyOffice convert API not configured"},
    {"backend": "libreoffice_headless", "available": false, "reason": "soffice binary not found on PATH"},
    {"backend": "phpword", "available": true, "supports": false, "reason": "MIME application/vnd.oasis.opendocument.spreadsheet not supported by PhpWord"}
  ],
  "outputFormat": "pdf",
  "fallback": "Set outputFormat: 'preserve' to bypass conversion if you must keep this format."
}
```

The fallback hint helps operators understand they CAN proceed without conversion if they have a legitimate reason — it doesn't bypass the gate; it documents the escape hatch.

**Rationale:** "partially anonymised mixed format" is a worse outcome than "no output, retry needed". Privacy-sensitive flows must fail loudly.

### D6. Opt-out via `outputFormat: "preserve"`, default `pdf`

The anonymise request payload accepts a top-level optional `outputFormat`:

- `"pdf"` (default, even when omitted) — convert via cascade or 422.
- `"preserve"` — skip conversion; emit the native format. Pre-change behaviour.

Tenant default is configurable via `docudesk.anonymisation.default_output_format` (default `pdf`). The per-call value overrides the tenant default.

**Rationale:** Most flows benefit from PDF default. The opt-out exists for the legitimate cases — e.g. a downstream pipeline that re-edits the anonymised DOCX, or a tenant that does its own format conversion in a custom flow. Forcing PDF universally would break those.

### D7. PhpWord supports DOC, DOCX, ODT, RTF, HTML

Verified against PhpWord's IOFactory: readers exist for `Word2007` (DOCX), `MsDoc` (DOC, limited fidelity but usable for plain-prose content), `ODText` (ODT), `RTF`, and `HTML`. Combined with the PdfWriter (mPDF backend), PhpWord covers all the Word-family formats DocuDesk needs to redact in the no-Office-app tier.

**Trade-off:** PhpWord's rendering fidelity is lower than a real Office engine's (especially for complex layouts, tables with merged cells, embedded images, and pre-Word-2007 binary DOC files). Acceptable for the fallback tier — if an install really cares about rendering quality, they install a supported Office app integration (Collabora, OnlyOffice, or Euro Office) and the PhpWord branch is never reached.

**Out of scope:** spreadsheet formats (XLSX, ODS, CSV with table layout) and presentation formats (PPTX, ODP) — these require `phpoffice/phpspreadsheet` / `phpoffice/phppresentation` which we deliberately do NOT add. Their conversion relies entirely on an `OfficeAppBackend` route; without one configured, those inputs return HTTP 422.

### D8. EML inputs depend on a future OpenRegister change

EML conversion uses OpenRegister's text-extraction service (when that capability adds EML support — currently planned but not specced). Until that lands, EML inputs fail with 422 in the default `pdf` mode, with an error directing operators to use `outputFormat: "preserve"` if they need to anonymise EML now.

**Rationale:** OR is the right home for EML extraction (it's a generic concern; other apps may want it too). Building it in DocuDesk would duplicate work. Soft dependency, called out in proposal.

When OR's EML extractor lands, the EML branch of the cascade activates: extract → wrap as plaintext → render via mPDF. The layout (header with From/To/Subject/Date + body, vs. plain `<pre>` text) is a follow-up template iteration; v1 ships the simplest layout that's auditable.

### D9. Performance: conversion is per-call, synchronous, optimised for typical sizes

Conversion is synchronous in v1. Most documents anonymised through DocuDesk are < 5 MB and convert in well under 5 seconds across all backends. Batch flows process files sequentially today; the conversion adds linear latency.

**Asynchronous conversion** (queue + callback) is a follow-up if/when batch sizes grow large enough to make synchronous convert infeasible. v1 keeps the simpler synchronous model.

**Backend timeout:** each backend has a per-call timeout (Office app: 30s, PhpWord+mPDF: 120s for large docs, mPDF direct: 30s). Exceeding the timeout is treated as a conversion failure for that backend; the cascade moves on. Tenant-configurable.

### D10. Tenant configuration surface

Admin settings exposed:

| Key | Default | Purpose |
|---|---|---|
| `docudesk.anonymisation.default_output_format` | `pdf` | Tenant-wide default for `outputFormat` when not specified per call |
| `docudesk.conversion.backends.office_app_enabled` | `true` | Whether to attempt the OfficeAppBackend (Collabora / OnlyOffice / Euro Office, whichever is installed) |
| `docudesk.conversion.backends.phpword_enabled` | `true` | Whether to attempt PhpWord + mPDF (DOC/DOCX/ODT/RTF/HTML) |
| `docudesk.conversion.backends.mpdf_enabled` | `true` | Whether to attempt mPDF directly (HTML/TXT) |
| `docudesk.conversion.backends.eml_enabled` | `true` | Whether to attempt OR-EML-extract + mPDF (no-op until OR change lands) |
| `docudesk.conversion.timeout_seconds` | `60` | Per-backend timeout |

Note: there is no `libreoffice_enabled` or `libreoffice_binary_path` key. The original cascade included a standalone `LibreOfficeHeadlessBackend` driven by a host-side `soffice` binary; removed in the 2026-06-01 revision because almost no Nextcloud install carries that binary. LibreOffice-backed conversion still happens implicitly when Collabora or Euro Office (both LibreOffice-derived) is the configured Office app — routed through the `OfficeAppBackend` flag above.

Use the existing `IAppConfig` pattern, consistent with how other DocuDesk settings (objection period, threshold) are stored.

## Risks / Trade-offs

- **[PHPWord rendering fidelity for complex documents]** → Mitigation: PhpWord is the third-tier fallback. Installs that care about fidelity install LibreOffice or an Office app, and PhpWord is never invoked. PhpWord rendering issues affect only the bare-bones tier where the alternative is "can't anonymise".
- **[Synchronous conversion latency on batch flows]** → Mitigation: per-backend timeouts cap worst case; existing batch flows are already serial. If batch sizes grow, follow-up to async. Documented in performance section.
- **[Office app HTTP probing on every call]** → Mitigation: `isAvailable()` caches the result for the request lifecycle; per-tenant disable flag lets installs without Office apps skip the probe entirely. Cache invalidates per request, not per process — safe for hot reload of admin config.
- **[Behaviour change is silent for callers that don't pass `outputFormat`]** → Mitigation: CHANGELOG entry under "Behavior changes" is explicit. The new default is privacy-positive, so the worst case for a confused caller is "they got a PDF when they expected a DOCX" — easy to spot and remediate by sending `outputFormat: "preserve"`.
- **[PDF/A-3b not produced by all backends]** → Mitigation: backends that can't reliably emit PDF/A-3b fall through. We don't quietly degrade to plain PDF — a backend that produces plain PDF instead of PDF/A is treated as "not available for this conversion" and the cascade moves on.
- **[EML inputs blocked until OR change lands]** → Mitigation: documented; operators use `outputFormat: "preserve"` for EML in the meantime; when OR's extractor lands, the EML branch activates with a small follow-up.
- **[LibreOffice headless concurrent invocation]** → No longer relevant (standalone backend dropped in 2026-06-01 revision). Any concurrency concerns for LibreOffice-backed Office apps (Collabora, Euro Office) are handled inside the Office app's own server-side scheduling.

## Migration Plan

1. Add `phpoffice/phpword` to `composer.json`. Run `composer install` in CI / build pipeline.
2. Land `PdfConversionService` + backend implementations + admin settings.
3. Land controller / service integration + 422 response shape.
4. Update `composer check:strict` and existing test suites — confirm no regressions.
5. Release. Operators see PDF outputs by default for new anonymisations. Existing anonymised files are not touched.

**Rollback:** flip the tenant-default to `preserve` (`docudesk.anonymisation.default_output_format = preserve`). All anonymise calls revert to native-format output. Per-call `outputFormat: "pdf"` still works for callers that opt-in. No code rollback needed; configuration alone is the rollback knob.

## Seed Data

Not applicable — this change introduces no new schemas or registers. The conversion service operates on Nextcloud Files; admin settings use existing `IAppConfig` storage.

## Open Questions

- **Does the existing `print-preview` PDF/A-3b backend share infrastructure we should reuse?** Provisional: yes, the PdfService used by print-preview is the right base for the mPDF backend in this cascade. Resolve during apply by inspecting `lib/Service/PdfService.php` and reusing its PDF/A configuration.
- **Concurrent LibreOffice headless calls — does NC's `ILockingProvider` cover this case adequately?** Provisional: yes; if not, fall back to a file-based mutex per the same pattern used by other apps that wrap soffice. Resolve during apply.
- **Office-app conversion via Collabora / OnlyOffice — what's the exact Nextcloud abstraction?** Provisional: NC has `OCA\Files\AppInfo\Application` and document-converter integrations are typically called via the appropriate app's HTTP API. Worth a short spike during apply: is there a unified NC-side abstraction, or do we call each Office app's API directly?
- **PHPOffice `phpword/phpword` version pin**: OR has `^1.2`. Match that. Confirm during apply.

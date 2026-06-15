# EML → PDF Assembly

DocuDesk converts incoming `.eml` email files into self-contained PDF/A-3b
documents that anyone in the case team can open, archive, anonymise, and sign
without needing an email client.

The pipeline is invoked automatically by the PDF-conversion cascade whenever
an EML file enters DocuDesk and a downstream operation (anonymisation, OCR,
signing, archival) needs a PDF/A representation.

## What the assembled PDF contains

For each EML input, the pipeline emits **one** PDF/A-3b document that
combines:

1. **Envelope page** — a header block in Dutch (Van / Aan / Cc /
   Onderwerp / Datum) followed by the message body. The HTML body is
   preferred over the plain-text body when both are present. Inline
   images referenced via `cid:` URIs are inlined as `data:` URIs so the
   PDF is fully self-contained.
2. **One PDF/A-3 file-attachment annotation per EML attachment** — the
   raw bytes of every MIME attachment are embedded into the PDF as
   file-attachment annotations. Downstream consumers (Adobe Reader,
   `pdftk`, `pdfdetach`) can extract the originals without
   re-fetching from the source EML.
3. **One divider page per attachment** — a single page introducing
   each attachment with its index, filename, MIME type, and size.
4. **One or more rendered content pages per renderable attachment** —
   the attachment's content rendered into the host PDF stream:
   - `application/pdf` → every source page imported via FPDI.
   - `image/jpeg`, `image/png`, `image/gif` → a single page with the
     image sized to fit the print area, aspect-ratio preserved.
   - `text/*` → a single page with the content wrapped in `<pre>`.
   - `message/rfc822` (nested EML) → recursively assembled, with a
     depth cap of 3 (matching OR's parser cap).
   - Other types (xlsx, pptx, zip, etc.) get an embedded-file
     annotation and a divider page that flags the type as
     **non-renderable**; the bytes are still recoverable.

## Configuration

All EML-pipeline tuning lives in `IAppConfig` under the `docudesk` namespace.
None of the keys are exposed in the admin settings UI in v1; v1.x will
surface them under **Document conversion → EML assembly**. Until then,
operators set them via `occ`:

```bash
docker exec nextcloud php occ config:app:set docudesk \
  docudesk.conversion.eml.append_attachment_pages --value=false
```

| Key | Default | Effect |
| --- | --- | --- |
| `docudesk.conversion.backends.eml_enabled` | `true` | Tenant kill-switch. When `false` the EML backend is unavailable and the cascade falls through to its 422 terminus on EML inputs. Operators can then ask their case workers to use `outputFormat: "preserve"`. |
| `docudesk.conversion.eml.append_attachment_pages` | `true` | When `false`, the assembled PDF contains the envelope page only — no dividers, no rendered attachment pages. The raw bytes of every attachment are **still** embedded as PDF/A-3 file annotations; consumers can extract them with any PDF viewer that supports attachments. Useful when downstream OCR / anonymisation only needs the email body, or when storage budgets are tight. |
| `docudesk.conversion.eml.max_attachment_render_size_bytes` | `26214400` (25 MiB) | Per-attachment byte cap above which a renderable attachment is **embedded but not rendered**. The divider for the attachment swaps to a "te groot" placeholder; the bytes are still recoverable via the file-attachment annotation. Non-positive values fall back to the 25 MiB default. |
| `docudesk.conversion.eml.envelope_template` | `eml/email_envelope.twig` | Path of the Twig template (relative to `lib/Resources/templates/`) that renders the envelope page. Override to localize headers or restyle. |
| `docudesk.conversion.eml.divider_template` | `eml/divider.twig` | Path of the Twig template (relative to `lib/Resources/templates/`) that renders attachment divider pages. |

## How errors degrade

The pipeline is designed so a single broken attachment never aborts the
entire assembly:

| Failure | Behaviour |
| --- | --- |
| OR's `parseEmlStructured()` not on the classpath | `EmlBackend::isAvailable()` returns `false`. The cascade falls through; downstream API returns 422. Operators can use `outputFormat: "preserve"`. |
| `parseEmlStructured()` throws | `EmlBackend::convert()` raises a `ConversionFailedException` with a structured `attempts[]` entry. The cascade surfaces it in the 422 response body. |
| Envelope Twig render throws | Service swaps in a **minimal envelope** containing the bare header rows plus a "(template rendering failed)" notice. PDF still emits. |
| Per-attachment render throws | The pipeline writes a `render_failed` divider on a fresh page and continues with the next attachment. Embedded-file annotation is still attached. |
| PDF/A-3 file-attachment embed fails | Logged at WARNING and skipped. The divider + rendered content still emit. |
| mPDF itself throws | The service wraps the underlying exception in `ConversionFailedException` and the cascade surfaces it in the 422 response body. |

## NL-only limitation

The shipped envelope + divider templates are Dutch-only. The
`register-i18n` change adds English (and further locale) variants once
OpenRegister's i18n foundation is fully adopted by DocuDesk. Operators
who need English NOW can drop a localized template into
`lib/Resources/templates/eml/email_envelope_en.twig` and set
`docudesk.conversion.eml.envelope_template`
to `eml/email_envelope_en.twig`.

## Verifying the output

The assembled PDF is PDF/A-3b. To verify:

```bash
# Conformance check (verapdf must be installed).
verapdf --format text --validate-profile 3b /path/to/out.pdf

# Extract every embedded attachment back out.
pdfdetach -saveall /path/to/out.pdf -o /tmp/extracted

# Page count + metadata.
pdfinfo /path/to/out.pdf
```

## Where the code lives

- `lib/Service/EmlPdfAssemblyService.php` — the assembler.
- `lib/Service/Conversion/EmlBackend.php` — the cascade entry point.
- `lib/Resources/templates/eml/email_envelope.twig` — envelope template.
- `lib/Resources/templates/eml/divider.twig` — attachment divider template.
- `tests/unit/Service/EmlPdfAssemblyServiceTest.php` — service unit tests.
- `tests/unit/Service/Conversion/EmlBackendTest.php` — backend unit tests.

See `openspec/changes/eml-pdf-assembly/` for the full design + spec.

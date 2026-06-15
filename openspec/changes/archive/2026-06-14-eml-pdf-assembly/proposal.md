## Why

`anonymise-output-as-pdf-by-default` (Change A) sketched an EmlBackend in its conversion cascade with placeholder behaviour: "extract body via OR's text extractor, wrap in HTML, render via mPDF". That sketch produces a PDF containing only the email body — no headers, no attachments, no preserved layout. For real Wob/Woo correspondence and email-archive anonymisation, that's not enough: the rendered PDF needs to carry the email's full visible structure (sender / recipient / subject / date headers + body) AND the attachments themselves so a reviewer can see the complete piece of correspondence in one document.

OpenRegister's paired change `text-extraction-eml` exposes a `parseEmlStructured()` method returning headers + both `text/plain` and `text/html` body parts + attachment bytes. This DocuDesk-side change consumes that structured API to assemble a rich PDF/A-3b: a header block, the email body (HTML when available — preserves layout / inline images; plain-text fallback otherwise), and each attachment handled appropriately — embedded as a PDF/A-3 file attachment AND, where the attachment is a renderable type (PDF, image, text, nested EML), rendered into pages appended to the resulting document.

The result is a single PDF/A-3b that combines the email and its attachments in one cohesive artifact. Operators get one file out of anonymisation; legal reviewers get one file to review; archivists get one PDF/A-3b that contains everything (the embedded original attachments are preserved verbatim inside the PDF/A-3 file-attachment slots, satisfying the archival "self-contained" requirement).

## What Changes

- **MODIFIED:** The `EmlBackend` in `pdf-conversion`'s cascade (introduced by Change A `anonymise-output-as-pdf-by-default`) is upgraded from "extract flat text + render as plaintext PDF" to "consume `parseEmlStructured` + assemble rich PDF/A-3b".
- **NEW:** `EmlPdfAssemblyService` (or extension to the existing EmlBackend class) that:
  1. Calls OR's `TextExtractionService::parseEmlStructured($file)` to get the structured EML data.
  2. Renders a Twig template (new) into HTML — header block + body section. Body uses `EmlStructure.body.html` when present (preserves email rendering); falls back to `EmlStructure.body.plainText` wrapped in a `<pre>` block; falls back to "(empty body)" notice when both are null.
  3. For HTML body: resolves inline images referenced by `cid:` URLs by walking `EmlStructure.attachments[].contentId` and embedding matching attachments as `data:` URLs in the HTML.
  4. Renders the HTML to PDF/A-3b via `PdfService` (the existing renderer used by `pdf-generation` and `print-preview`).
  5. For each attachment: embeds the raw bytes as a PDF/A-3 embedded file (the "/3" in PDF/A-3 is precisely this: arbitrary file embedding with archival compliance).
  6. For each attachment whose MIME is renderable (`application/pdf`, common image MIMEs, plain-text, nested EML — the same set the conversion cascade handles): also recursively renders the attachment to PDF/A-3b and appends its pages to the resulting document, prefixed by a divider page indicating "Attachment N: <filename>".
  7. Returns the assembled PDF/A-3b file.
- **NEW Twig template:** `lib/Resources/templates/eml/email_envelope.twig` — renders the header block (From/To/Cc/Subject/Date) and embeds the body content. NL-only labels in v1 (consistent with `anonymisation-grondslagen-summary`); EN follows `register-i18n`.
- **NEW config:** `docudesk.conversion.eml.append_attachment_pages` (boolean, default `true`) — tenants that want attachments embedded only (not rendered as pages) can disable. Embedding (PDF/A-3 file attachments) always happens regardless; this flag controls whether renderable attachments also get appended pages.
- **NEW config:** `docudesk.conversion.eml.max_attachment_render_size_bytes` (integer, default 25 MB) — attachments larger than this are embedded but not rendered (avoids huge spreadsheet conversion blowing up the assembly time).
- **MODIFIED:** Change A's `EmlBackend.isAvailable()` was specced to return false until OR's EML support exists. Once `text-extraction-eml` lands AND this change lands, the backend reports available and the cascade activates.
- **DEPENDENCY:** soft on OR's `text-extraction-eml`. Until that lands, `EmlBackend.isAvailable()` returns false; EML inputs fall through to 422 in default `outputFormat: pdf` mode.

### Scope of attachment handling per type

| Attachment MIME | Embedded as PDF/A-3 file? | Rendered as appended pages? |
|---|---|---|
| `application/pdf` | ✓ | ✓ |
| `image/png`, `image/jpeg`, `image/gif`, `image/webp` | ✓ | ✓ (embedded as `<img>` on a page) |
| Plain-text (`text/plain`, `text/csv`, etc.) | ✓ | ✓ (rendered as `<pre>` page) |
| `message/rfc822` (nested EML) | ✓ | ✓ (recursive — the nested EML gets its own envelope + nested attachments) |
| Word documents (DOCX/ODT/RTF/HTML via PhpWord) | ✓ | ✓ (uses Change A's existing PhpWord backend in the cascade) |
| Anything else (XLSX, ODS, calendar, archives, ...) | ✓ | ✗ (embedded only; "see embedded file" placeholder page) |

When the cascade renders an embedded attachment that itself has the `outputFormat: "preserve"` semantics (e.g. a non-rendering MIME), no page is appended; the placeholder explains "see embedded file" with the filename.

### Recursive nested EML

When an attachment is `message/rfc822` (nested email), `EmlStructure.attachments[].nestedEml` is already populated by OR (up to depth 3). The assembly recursively renders the nested EML's envelope + its own attachments. Depth limit honoured — beyond depth 3, nested EMLs are embedded as bytes only (no recursive page rendering).

### Out of scope

- **Calendar invite (`text/calendar`) rendering** — calendars are listed as attachments and embedded as bytes only. A future change adds calendar rendering if the use case emerges.
- **Encrypted EML decryption** — out of scope. The structured parse from OR for encrypted bodies returns the encrypted blob; this change renders that as opaque content with a "Encrypted body — content not extracted" notice.
- **Real-time email rendering preview before save** — assembly is synchronous and produces the file directly. No interactive preview.
- **Stripping email signatures or quoted reply chains from the body** — body is rendered as-is. Future content-aware processing is separate.
- **Deduplicating attachments** that appear inline (referenced by HTML body) AND in the attachment list — they're embedded once at most. Inline images are also referenced from the HTML body via `data:` URLs.

## Capabilities

### New Capabilities

(none — this change extends an existing capability without introducing new ones.)

### Modified Capabilities

- `pdf-conversion`: the EmlBackend in the conversion cascade is upgraded from a placeholder plaintext path to a rich-rendering path. The EmlBackend's `isAvailable()` now requires both `text-extraction-eml` (OR) AND this change to be applied; until both land, the backend reports unavailable.

## Cross-app Dependencies

- **Hard** — `openregister:text-extraction-eml` — provides `parseEmlStructured()`. Until that lands, `EmlBackend.isAvailable()` returns false; EML inputs fall through to 422 in default `outputFormat: "pdf"` mode.
- **Soft** — `docudesk:anonymise-output-as-pdf-by-default` — provides the conversion cascade and the `EmlBackend` placeholder. Until Change A lands, there is no cascade for this change to plug into.

Each row MUST be tracked as a `Depends on` link from this change's GitHub issue to the target's tracking issue.

## Impact

- **Code (docudesk):**
  - `lib/Service/Conversion/EmlBackend.php` (introduced by Change A) — extend `isAvailable()` to additionally check that this change's assembly service is registered. Replace the placeholder `convert()` with a call into the new `EmlPdfAssemblyService`.
  - `lib/Service/EmlPdfAssemblyService.php` — NEW. Orchestrates the assembly flow (call OR → render envelope → embed attachments → append rendered pages). Constructor injects: `TextExtractionService` (OR DI lookup pattern), `PdfService`, the conversion-backends list (for recursive rendering of attachments), the logger, `IAppConfig`.
  - `lib/Resources/templates/eml/email_envelope.twig` — NEW. Renders the header block + body. NL labels.
  - `lib/Service/Conversion/MpdfBackend.php` — extend to support PDF/A-3 file embedding (mPDF supports this via `setAdditionalXmpRdf` and `Embed_File`). The embedding happens during the final assembly step in `EmlPdfAssemblyService`.
  - Admin settings UI — surface the two new config keys (`append_attachment_pages`, `max_attachment_render_size_bytes`).
- **API contract:** No request payload changes. The anonymise endpoint (per Change A) already accepts `outputFormat: "pdf"` (default). When the input is EML and the backend is available, the assembly path runs. Response shape unchanged.
- **Cross-app:**
  - Hard dep on OR's `text-extraction-eml` — provides `parseEmlStructured`. Until that lands, `EmlBackend.isAvailable()` returns false.
  - Soft dep on Change A `anonymise-output-as-pdf-by-default` — provides the cascade infrastructure and the `EmlBackend` placeholder. Until A lands, no cascade exists to plug into.
  - No OR-side changes in this DocuDesk change.
- **Privacy / compliance:** PDF/A-3b assembly produces an archival-compliant single artifact. Embedded attachments preserve original bytes — auditable. Rendered attachment pages give human reviewers immediate visibility without opening separate files. Strengthens "redacted artifact is self-contained and hard to re-edit".
- **Performance:** Assembly cost dominated by recursive attachment rendering (PDF parse, image embed, etc.). Large EMLs with many or large attachments take measurable time; bounded by the per-attachment limits (`max_attachment_render_size_bytes` for rendering — embedding is unbounded but cheap). Documented in design.md.
- **Migration:** None. New capability is opt-in via Change A's existing `outputFormat: "pdf"` default + the EmlBackend now becoming available.
- **Tests:** Unit tests for the assembly service (template render, attachment recursion, embedding); integration tests for EML→PDF end-to-end with various attachment combinations.

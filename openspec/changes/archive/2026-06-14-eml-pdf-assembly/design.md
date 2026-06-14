---
status: pr-created
---

## Context

`anonymise-output-as-pdf-by-default` (Change A) introduced a conversion cascade that flattens any anonymisation output to PDF/A-3b. Its `EmlBackend` was specced as a thin path: extract email body, wrap in HTML, render via mPDF. That path produces a PDF containing only the body — sender / recipient / subject / date metadata is lost, attachments are not represented at all. For real correspondence (Wob/Woo email threads, complaint dossiers), the rendered artifact has to carry the full visible structure of the email plus the attachments that came with it.

This change upgrades that backend to consume OR's structured-parse API (from the paired `text-extraction-eml` change) and assemble a richer PDF/A-3b that:

- Renders a header block with From / To / Cc / Subject / Date.
- Renders the email body — preferring HTML for fidelity (preserved layout, fonts, inline images), falling back to plain-text wrapped in `<pre>` when only the plain part is available.
- Embeds every attachment as a PDF/A-3 file attachment (the "/3" allows arbitrary embedded files alongside the rendered content — auditable, preserved, archivally complete).
- For renderable attachments (PDF, image, plain-text, nested EML, Word docs via PhpWord), additionally renders them as appended pages prefixed by a divider that names the attachment.

PDF/A-3b is the archival-grade target — already chosen for the per-document anonymisation output. By embedding the original attachment bytes, we satisfy the "self-contained" criterion: a reviewer with only the resulting PDF can extract the original PDF/DOCX/etc. attachments unchanged. By rendering them as pages, we give human reviewers immediate visibility without juggling external files.

The rendering subsystem is fully reused — `PdfService` from `pdf-generation`, the conversion-backends list from Change A's pdf-conversion, mPDF's PDF import/embed paths. No new rendering primitives.

## Goals / Non-Goals

**Goals:**

- Produce a single PDF/A-3b that combines an email's headers + body + attachments into one artifact.
- Use HTML body when the EML provides one (fidelity); plain-text fallback when only that exists.
- Embed every attachment as a PDF/A-3 embedded file (always — operators get the original bytes back).
- Render renderable attachments as appended pages (configurable; default on).
- Reuse the existing pdf-conversion cascade for per-attachment rendering — recursive but bounded.
- Honour OR's depth-3 cap on nested EML chains.

**Non-Goals:**

- Render calendar invites (`.ics`) inline. Calendar attachments are embedded only.
- Decrypt encrypted EML bodies. Encrypted content is rendered as an "Encrypted body" notice; the encrypted blob is preserved as an embedded file.
- Strip signatures, quoted reply chains, or other body content. Body renders as-is.
- Provide an interactive preview of the assembled PDF before saving. Synchronous, save-and-return.
- Add a separate "render this EML to PDF" endpoint. Assembly happens via the existing anonymise endpoint when the EmlBackend wins the cascade.
- Backfill rendering for historical EMLs that were never anonymised. Only new anonymise calls trigger this.

## Decisions

### D1. HTML body preferred; plain-text fallback; both-null shows a notice

Body rendering priority:

1. If `EmlStructure.body.html` is non-null and non-empty: render the HTML directly into the Twig envelope. Inline images referenced by `cid:` URLs are resolved via the `attachments[].contentId` index and embedded as `data:` URLs in the HTML before mPDF renders.
2. Else if `EmlStructure.body.plainText` is non-null and non-empty: render in a `<pre>`-wrapped block to preserve whitespace/linebreaks.
3. Else: render the localised string `(Bericht zonder body — alleen bijlagen)` as a placeholder.

**Rationale:**

- HTML preserves the way the recipient saw the email — formatting, fonts, embedded images. mPDF renders modern HTML reasonably well.
- Plain-text fallback covers EMLs that have no HTML part (older systems, automated notifications).
- The placeholder for "no body at all" distinguishes a deliberately-empty email from a parse failure.

**Trade-off:** mPDF's HTML rendering isn't pixel-perfect — complex CSS (display:flex, modern grid layouts) may render imperfectly. Acceptable for archival rendering; the document is informational, not a re-creation of the email-client view.

### D2. Inline image resolution via `cid:` lookup

When the HTML body references inline images (`<img src="cid:image1@example.com">`), the assembly walks `EmlStructure.attachments[]` looking for `contentId === 'image1@example.com'`. Found attachments are converted to `data:image/png;base64,<base64>` URLs and substituted in the HTML before mPDF renders. The same attachments still appear in the attachments list AND are embedded as PDF/A-3 files (no deduplication — operators see them in both places).

**Rationale:**

- mPDF can resolve `data:` URLs natively. Substituting the `cid:` reference at HTML-build time avoids needing a custom mPDF resource resolver.
- Listing the inline image in the regular attachments + embedded files is intentional: the inline image IS an attachment of the EML; it should appear in both views.

**Edge case:** broken `cid:` references (the EML referenced an image that wasn't attached). The HTML's `<img>` tag is left in place; mPDF logs a warning about the unresolvable resource and renders a placeholder. Operator sees a broken image in the rendered page — same way the email recipient would have seen it.

### D3. PDF/A-3 file embedding for every attachment

Every attachment in `EmlStructure.attachments[]` is embedded as a PDF/A-3 file attachment in the resulting PDF. The PDF/A-3 spec allows arbitrary files inside a PDF; the embedded files are extractable by any PDF/A-3-aware viewer (Adobe Acrobat, foxit, the PDF/A reference tools, etc.).

mPDF supports embedded files via:

```php
$mpdf->WriteHTML($html);
foreach ($attachments as $att) {
    $mpdf->Annotation(...)->setEmbeddedFile($att->filename, $att->mimeType, $att->content);
}
$mpdf->SetPDFAVersion('3-B'); // PDF/A-3b
```

(Exact API surface confirmed during apply — the mPDF docs cover `setEmbeddedFile` and the PDF/A-3 mode.)

**Rationale:** archival completeness. The redacted PDF carries every byte of the original email's attachments. Reviewers / archivists can extract them losslessly.

**Trade-off:** PDF size grows with the total attachment size. Acceptable; large attachments are rare in real-world correspondence (typical EML payloads are well under 10 MB total).

### D4. Renderable attachments append as pages — recursive via the conversion cascade

For each attachment whose MIME is in the renderable set:

```
PDF              → use mPDF.setSourceFile + importPage to import existing pages
image/*          → render an HTML page with <img src="data:image/...;base64,..."> sized to fit
text/plain etc.  → render an HTML page with <pre>-wrapped content
message/rfc822   → recursive — call EmlPdfAssemblyService for the nested EML's structure
DOCX/ODT/RTF/HTML → reuse Change A's PhpWordBackend to convert to PDF/A-3b, then import pages
```

Each renderable attachment is preceded by a divider page rendered from a Twig template:

```
─────────────────────────────────
Bijlage <N>: <filename>
<mimeType>
<size>
─────────────────────────────────
```

Then the rendered pages follow.

**Configurability:** `docudesk.conversion.eml.append_attachment_pages` (default true). When false, attachments are only embedded (D3), not rendered as pages. Useful for tenants where the embedded file is sufficient and rendering noise is unwanted.

**Size cap:** `docudesk.conversion.eml.max_attachment_render_size_bytes` (default 25 MB). Attachments larger than this are embedded but not rendered. The divider page in that case shows "Bijlage <N>: <filename> — te groot om weer te geven; zie ingebed bestand".

### D5. Recursive nested EML — depth-3 budget shared with OR

OR's `parseEmlStructured` caps recursion at depth 3 (`EmlStructure.attachments[].nestedEml` is null beyond depth 3). DocuDesk's assembly inherits the structure as-is. When recursively assembling a nested EML, the depth-3 limit is naturally enforced — beyond it, the nested EML attachment carries `nestedEml: null` and is rendered as "Bijlage N: <filename> (genest e-mail, niet weergegeven — diepte-limiet)".

**Trade-off:** legitimate forwarded-forwarded-forwarded chains beyond depth 3 are embedded but not rendered. Operators can manually open the embedded EML if they need to see it.

### D6. Twig template — single envelope template, recursive renders share it

`lib/Resources/templates/eml/email_envelope.twig` renders one EML's envelope: header block + body. Recursive nested-EML rendering uses the same template (Twig include / extends pattern not needed; the assembly service builds each level's HTML and concatenates pages via mPDF's `AddPage()`).

NL-only labels in v1 (consistent with `anonymisation-grondslagen-summary`):

```
Van:        <from>
Aan:        <to (comma-joined)>
Cc:         <cc> (only if present)
Onderwerp:  <subject>
Datum:      <date formatted as YYYY-MM-DD HH:MM>

<body content>
```

EN translations follow `register-i18n` landing.

### D7. PdfService reuse with PDF/A-3b mode

The existing `PdfService` (used by `pdf-generation` and `print-preview`) renders Twig → HTML → PDF via mPDF. For this change, the assembly creates an mPDF instance directly (not via `PdfService::renderPdf` — which returns a single-pass binary). Rationale: the assembly does multi-pass mPDF work (write envelope HTML → import attachment pages → embed files), which doesn't fit the single-pass renderPdf API.

**Alternative considered:** extend `PdfService` with a multi-pass API. Rejected for v1 — the multi-pass work is EML-assembly-specific; pushing it into PdfService would couple a generic renderer to a specific use case.

The mPDF instance is configured with the SAME PDF/A-3b settings PdfService uses (font embedding, no JS, etc.). Configuration is centralised in a small helper to avoid drift.

### D8. Error handling — assembly failures fall through to a degraded result

If a step in the assembly fails:

| Failure | Recovery |
|---|---|
| OR's `parseEmlStructured` throws `EmlParseException` | Fall back to OR's flat `extractEml` result; render it as a single plain-text page. No attachments visible. |
| Twig render throws | Render a minimal envelope with available headers + a notice "(template rendering failed)". |
| Inline image can't be resolved | Leave the broken `cid:` reference; mPDF renders a placeholder. |
| Renderable attachment fails to render | Skip the page (still embed the bytes). Add a divider with "Bijlage <N>: <filename> — kon niet worden weergegeven; zie ingebed bestand". |
| File embedding (mPDF setEmbeddedFile) fails | Log; embed as much as possible; add a notice in the document footer "Niet alle bijlagen konden worden ingebed: <list>". |

Total failure (the assembly cannot produce ANY output) raises a `ConversionFailedException` per the existing pdf-conversion contract → cascade falls through to the next backend (which there isn't one for EML, so it 422s).

### D9. Configuration

| Key | Default | Purpose |
|---|---|---|
| `docudesk.conversion.eml.append_attachment_pages` | `true` | When false, attachments are only embedded; no appended pages. |
| `docudesk.conversion.eml.max_attachment_render_size_bytes` | `26214400` (25 MB) | Per-attachment size cap for rendering. Larger files are embedded only. |
| `docudesk.conversion.eml.divider_template` | `eml/divider.twig` | Optional override for the per-attachment divider template. |

Standard `IAppConfig` pattern.

## Risks / Trade-offs

- **[mPDF's HTML rendering fidelity for modern emails]** → Mitigation: documented limitation; operators that need pixel-perfect rendering can disable HTML preference via a future config flag (or by keeping `outputFormat: "preserve"` on the anonymise call — but then the EML stays as native EML in NC, which defeats the privacy default). Acceptable trade-off.
- **[PDF size with many large attachments]** → Mitigation per D3 + D4: the embedded-file count is unbounded but per-attachment is bounded by the source EML's size; rendering is bounded by `max_attachment_render_size_bytes`. Worst case: large EML → large PDF. Same tradeoff as the source data.
- **[Recursive nesting CPU cost]** → Mitigation per D5: depth-3 cap (inherited from OR). Each level's processing is the same per-EML cost. Worst case: depth-3 chain × N attachments = bounded.
- **[mPDF setEmbeddedFile API stability]** → mPDF's PDF/A-3 file-embedding API is documented and stable. Confirm at apply time the version pin (`mpdf/mpdf ^8.2`) supports this surface.
- **[Inline images doubled — once in body, once in attachment list]** → Mitigation: documented; this is intentional. The inline image IS an attachment per RFC 822; both presentations are correct.
- **[NL-only template]** → Mitigation: same as `anonymisation-grondslagen-summary`. EN follows `register-i18n`.
- **[Rendering time on large dossiers]** → Each EML's assembly is synchronous. For a batch of many EMLs each with large attachments, total time compounds. Documented in design; async is a follow-up if it becomes a real bottleneck.
- **[Encrypted EML body — opaque]** → Documented; "Encrypted body — content not extracted" notice in the rendered envelope. Decryption is its own change.

## Migration Plan

1. Wait for OR's `text-extraction-eml` to land (or land in the same release window). Until OR exposes `parseEmlStructured`, this change's assembly cannot run.
2. Land `EmlPdfAssemblyService`, the Twig templates, the config keys.
3. Update Change A's `EmlBackend.isAvailable()` to reflect the new dependency: returns true when both OR's parse method exists AND this change's assembly service is registered.
4. Land the mPDF helper for PDF/A-3b instantiation + file embedding.
5. Release. Operators see EML inputs converted to assembled PDFs by default (assuming Change A's `outputFormat: "pdf"` default is in effect).

**Rollback:** disable the EmlBackend via Change A's `docudesk.conversion.backends.eml_enabled = false`. EML inputs return 422 in default mode. Operators with EMLs to anonymise use `outputFormat: "preserve"` (keep native EML as the redacted output — a coherent choice).

## Seed Data

Not applicable — this change introduces no new schemas. EML files are processed at runtime via the conversion cascade.

## Open Questions

- **mPDF version supports `setEmbeddedFile` for PDF/A-3?** Confirm at apply time. If the pinned version doesn't, either bump the dependency or implement a thin embedded-file writer using mPDF's lower-level API.
- **Divider template extensibility** — operators may want custom dividers (logo, organisation header). Provisional: ship the default; expose `docudesk.conversion.eml.divider_template` config key for an override; full template-customisation UI is a follow-up.
- **Inline image deduplication** — current spec keeps inline images in both the body and the attachment list. If operators find this confusing, a follow-up adds an `excludeInline` flag for the attachments list. Defer until real feedback.
- **HTML body rendering quality** — for hard-to-render emails (complex layouts, modern CSS), should we fall back to plain-text rendering automatically? Provisional: no; render as-is and accept fidelity loss. Operators can use `outputFormat: "preserve"` if pixel-perfect rendering matters more than archival format.
- **Embedded font subset** — for HTML body rendering, mPDF embeds the configured font set. Default is acceptable; if a tenant needs specific fonts (e.g. emoji rendering), a follow-up exposes the font-config knob.

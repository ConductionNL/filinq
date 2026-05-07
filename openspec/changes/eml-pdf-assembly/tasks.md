## 1. Cross-app dependency check

- [ ] 1.1 Confirm OR's `text-extraction-eml` change has been applied (or schedule this change to land after it). Smoke-test: in a dev environment, confirm `\OCA\OpenRegister\Service\TextExtractionService::parseEmlStructured` exists and returns an `EmlStructure` for a sample EML.
- [ ] 1.2 If OR change has not yet landed, this change's apply phase MUST gate `EmlBackend.isAvailable()` to return false until OR exposes `parseEmlStructured`. Document the dependency in CHANGELOG.

## 2. EmlPdfAssemblyService

- [ ] 2.1 Create `lib/Service/EmlPdfAssemblyService.php`. Constructor injects `PdfService` (from `pdf-generation`), the conversion-backends list (from `pdf-conversion`), `IAppConfig`, the logger. Lazily resolves OR's `TextExtractionService` via the OR DI lookup pattern (consistent with how OR services are pulled into DocuDesk).
- [ ] 2.2 Implement public method `assemble(EmlStructure $structure, ?string $sourceFilename = null): \OCP\Files\File` returning the assembled PDF/A-3b as a Nextcloud File.
- [ ] 2.3 Read configuration: `docudesk.conversion.eml.append_attachment_pages` (default true), `docudesk.conversion.eml.max_attachment_render_size_bytes` (default 26214400), `docudesk.conversion.eml.divider_template` (default `eml/divider.twig`).

## 3. Twig templates

- [ ] 3.1 Create `lib/Resources/templates/eml/email_envelope.twig`. Renders header block (Van/Aan/Cc/Onderwerp/Datum, NL labels) followed by body section. Body uses `body.html` if provided, else wraps `body.plainText` in a `<pre>` block, else renders the empty-body placeholder.
- [ ] 3.2 Create `lib/Resources/templates/eml/divider.twig`. Renders a single divider page identifying an attachment by index, filename, MIME, and size. Used between the email envelope and each attachment's rendered pages, and again for the "not rendered" / "too large" cases (different placeholder text per case).
- [ ] 3.3 Verify both templates render through PdfService's Twig sandbox (no blocked directives). If the sandbox blocks anything, expand the safe-tag list narrowly.

## 4. Inline image (cid:) resolution

- [ ] 4.1 In `EmlPdfAssemblyService::assemble`, scan the chosen body HTML for `cid:` URL references via a defensive regex (`<img\s+[^>]*src=["']cid:([^"']+)["']`).
- [ ] 4.2 For each `cid:` reference, look up the matching attachment in `EmlStructure.attachments[]` by `contentId`. If found, base64-encode the bytes and substitute the `src` with `data:<mimeType>;base64,<encoded>`. If not found, leave the reference and log at debug level.
- [ ] 4.3 Apply the substitution before passing the HTML to mPDF.

## 5. mPDF assembly — multi-pass

- [ ] 5.1 Create an mPDF instance configured for PDF/A-3b: `SetPDFAVersion('3-B')`, font embedding on, no JavaScript, no external resources.
- [ ] 5.2 Render the email envelope HTML via `WriteHTML($envelopeHtml)` — produces the first page(s) of the document.
- [ ] 5.3 For each attachment in `EmlStructure.attachments[]`:
  - Embed the bytes as a PDF/A-3 file attachment via `Annotation()->setEmbeddedFile($filename, $mimeType, $content)` (confirm the exact mPDF API surface during apply — check `setasign/fpdi` + mPDF docs).
  - If `append_attachment_pages` is true AND the attachment is renderable AND its size is within `max_attachment_render_size_bytes`:
    - Add a page break (`AddPage()`).
    - Render the divider HTML via `WriteHTML($dividerHtml)`.
    - Render the attachment per its type (see 5.4 - 5.7 below).
  - Else:
    - Add a page break.
    - Render a divider with the appropriate "not rendered / too large / not extractable" placeholder.
- [ ] 5.4 PDF attachment rendering: use mPDF's `setSourceFile($tempPath)` + `importPage($n)` + `useTemplate(...)` to import all pages of the source PDF.
- [ ] 5.5 Image attachment rendering: write a single page with `<img src="data:<mime>;base64,...">` sized to fit; respect aspect ratio.
- [ ] 5.6 Plain-text attachment rendering: write a single page with `<pre>`-wrapped content; mPDF handles pagination.
- [ ] 5.7 Nested EML attachment rendering (recursive): call `EmlPdfAssemblyService::renderNested($nestedStructure, $depth + 1)` which renders a divider + the nested envelope + attachments. Depth-3 cap enforced (per OR's parse limit; if `nestedEml` is null, no recursion).
- [ ] 5.8 Word document attachment rendering: delegate to Change A's `PhpWordBackend` to convert the bytes to PDF/A-3b, then import its pages via the PDF-import path (5.4).
- [ ] 5.9 Output via `Output('', 'S')` (return as string), wrap in a temporary File, return.

## 6. EmlBackend update

- [ ] 6.1 Update `lib/Service/Conversion/EmlBackend.php` (introduced by Change A). Update `isAvailable()`: returns true if `\OCA\OpenRegister\Service\TextExtractionService::parseEmlStructured` is callable AND `EmlPdfAssemblyService` is registered. Otherwise false.
- [ ] 6.2 Replace the placeholder `convert()` with: `$structure = $textExtractionService->parseEmlStructured($source); return $assemblyService->assemble($structure, $source->getName());`.
- [ ] 6.3 Surface `EmlParseException` from OR as a `ConversionFailedException` with a clear backend-specific message ("EML parse failed; see OpenRegister log for details").

## 7. Error-handling fallbacks

- [ ] 7.1 Implement the fallback chain per design D8: `parseEmlStructured` throws → fall back to flat-text via `extractEml` → render as single plain-text page; embed nothing.
- [ ] 7.2 Twig template render throws → render minimal envelope with available headers + "(template rendering failed)" notice.
- [ ] 7.3 Per-attachment render failure → skip the page (no rendered content); embed bytes; divider says "kon niet worden weergegeven".
- [ ] 7.4 Embedding failure for an attachment → log; continue; document footer lists failed embeds.
- [ ] 7.5 Catastrophic failure (no output possible) → throw `ConversionFailedException`; cascade falls through.

## 8. Tenant configuration

- [ ] 8.1 Surface the new config keys in admin settings UI (or document them as IAppConfig keys for v1 with admin UI follow-up). Validate `max_attachment_render_size_bytes` as a positive integer; `append_attachment_pages` as boolean.

## 9. Unit tests

- [ ] 9.1 `tests/unit/Service/EmlPdfAssemblyServiceTest.php` — fixtures: simple email (text only); HTML-bodied email; email with one PDF attachment; email with mixed attachments (PDF + image + text + non-renderable); email with inline image (cid: ref); nested EML at depth 1, 2, 3 (cap); empty body; all body parts null; recovery from `parseEmlStructured` failure; recovery from per-attachment failure.
- [ ] 9.2 `tests/unit/Service/Conversion/EmlBackendTest.php` — extension covering: `isAvailable()` reflects both deps; `convert()` delegates to assembly service; `EmlParseException` from OR surfaces as `ConversionFailedException`.
- [ ] 9.3 Inline-image cid resolution: HTML body with cid: refs, attachments with matching contentId — substituted correctly. Broken cid refs leave HTML alone.
- [ ] 9.4 Configuration toggling: `append_attachment_pages: false` produces envelope-only PDF with all attachments still embedded; `max_attachment_render_size_bytes` cap honoured.

## 10. Integration tests

- [ ] 10.1 Newman / functional: anonymise an EML file via the per-document anonymise endpoint with `outputFormat: "pdf"` (default after Change A). Verify the resulting file is PDF/A-3b, contains the rendered email envelope, has the attachments embedded as PDF/A-3 files, and (with default config) has appended attachment pages.
- [ ] 10.2 Functional: PDF/A-3b conformance check on the output via `pdfinfo` and (if available) `verapdf`. Verify embedded files extract correctly via `pdftk` or equivalent.
- [ ] 10.3 Functional: an EML with a nested EML attachment at depth 2 — verify the nested envelope is rendered with its own divider and pages.
- [ ] 10.4 Functional: with `append_attachment_pages: false`, verify the output contains only the email envelope page(s) but the embedded files are still present.

## 11. Cross-app coordination

- [ ] 11.1 Confirm with OR team that `text-extraction-eml` has shipped before this change goes live. If shipping in the same release, coordinate ordering (OR first; this change second).
- [ ] 11.2 Update Change A's CHANGELOG entry — note that the EML branch is now functional (was "deferred until OR supports EML"); operators no longer need `outputFormat: "preserve"` for EML inputs.

## 12. Documentation

- [ ] 12.1 Add a section to `docs/features/anonymization.md` (or create `docs/features/eml-pdf-assembly.md`) describing the assembly behaviour: header rendering, body preference (HTML > plain-text), attachment embedding, page rendering, the size cap and the toggle.
- [ ] 12.2 CHANGELOG entry under "Added": EML inputs now produce a rich assembled PDF/A-3b with email envelope + body + embedded + rendered attachments.
- [ ] 12.3 Document the NL-only limitation; EN templates follow `register-i18n`.

## 13. Quality and verification

- [ ] 13.1 Run `composer check:strict` — clean. Fix any pre-existing issues in touched files.
- [ ] 13.2 Manual smoke against a live stack: configure a test EML with body and attachments; trigger anonymise with `outputFormat: "pdf"`; verify the assembled output. Toggle `append_attachment_pages: false` and verify behaviour.
- [ ] 13.3 Run `openspec validate eml-pdf-assembly` — clean.

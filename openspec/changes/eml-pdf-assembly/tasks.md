# Tasks — EML Redacted-Component PDF Assembly

All redaction is OpenRegister's; DocuDesk only assembles the redacted result. No verbatim embedding of original bytes; unsupported attachments become placeholder pages; EML always outputs PDF.

## Implementation

- [ ] 1. Confirm OR's `anonymise-eml-structured` anonymise-EML API is present (returns an `AnonymisedEmlStructure`: redacted headers including Reply-To, redacted `AnonymisedEmlBody` `html`/`plain`, `attachments[]` of `AnonymisedEmlAttachment` `{filename,mimeType,redactedContent}` or `{filename,mimeType,unsupported:true}`, and an inline-image map). Smoke-test the method exists and returns the redacted shape for a sample EML. If absent, `EmlBackend.isAvailable()` MUST stay false.
- [ ] 2. Create `lib/Service/EmlPdfAssemblyService.php` with `assemble(AnonymisedEmlStructure $result, ?string $sourceFilename = null): \OCP\Files\File`. Constructor injects: OR's anonymise-EML service (OR DI lookup pattern, as `AnonymizationService` resolves OR services reflectively), `PdfService`, the conversion-backends list, `IAppConfig`, the logger. Read config keys `append_attachment_pages` (default true), `max_attachment_render_size_bytes` (default 26214400), `divider_template` (default `eml/divider.twig`).
- [ ] 3. Create `lib/Resources/templates/eml/email_envelope.twig` — redacted header block (Van/Antwoord aan/Aan/Cc/Onderwerp/Datum, NL labels; Antwoord aan and Cc only when present; Datum `YYYY-MM-DD HH:MM`) + body section (redacted `html`, else `<pre>`-wrapped redacted `plain`, else empty-body placeholder). Render OR's redacted values as-is.
- [ ] 4. Create `lib/Resources/templates/eml/divider.twig` — divider page (index/filename/MIME/size) AND the placeholder variants: "geredigeerd maar niet weer te geven", "te groot om weer te geven", "weggelaten — geen anonimiseerder beschikbaar", and the nested-EML depth-limit notice. Verify both templates pass PdfService's Twig sandbox.
- [ ] 5. Inline-image resolution: scan the redacted HTML for `<img ... src="cid:<id>">`; for each, look up `<id>` in OR's inline-image map; substitute `data:<mimeType>;base64,<redacted>`; leave unresolved refs in place and debug-log. Substitute only OR's redacted bytes.
- [ ] 6. mPDF assembly (multi-pass, PDF/A-3b): instantiate mPDF with `SetPDFAVersion('3-B')`, font embedding on, no JS, no external resources (centralise with PdfService's settings). Write the redacted envelope HTML as the first page(s). NO file-embedding pass (verbatim embedding is dropped).
- [ ] 7. Per-attachment handling from OR's `attachments[]`: when `append_attachment_pages` is true and the entry has `redactedContent`, is a renderable MIME, and is within the size cap → `AddPage()`, render divider, render the REDACTED bytes via the cascade (PDF import / image `<img>` / `<pre>` text / Word via PhpWordBackend / nested EML recurse). Embed NO bytes for any attachment.
- [ ] 8. Placeholder pages (no content embedded or rendered): `redactedContent` + non-renderable MIME → "geredigeerd maar niet weer te geven"; over size cap → "te groot om weer te geven"; `unsupported: true` → "weggelaten — geen anonimiseerder beschikbaar".
- [ ] 9. Recursive nested EML: when an attachment is `message/rfc822` with a non-null redacted nested result, recurse with the same template/rules (depth ≤ 3, owned by OR). Beyond depth, OR returns `unsupported` → render placeholder only.
- [ ] 10. Output via `Output('', 'S')`, wrap in a File, return.
- [ ] 11. Un-stub `lib/Service/Conversion/EmlBackend.php`: `isAvailable()` returns true when OR's anonymise-EML API is callable AND `EmlPdfAssemblyService` is registered (still read the `eml_enabled` tenant flag for observability). `convert()` calls OR's anonymise-EML API for the source and passes the redacted result to `assemble()`. Surface OR exceptions as `ConversionFailedException` with no raw-parse fallback.
- [ ] 12. EML always outputs PDF: in the anonymise path, silently override `outputFormat: "preserve"` to the PDF cascade for EML inputs (no error returned; caller gets the assembled PDF). `pdf-only`, `pdf`, and the overridden `preserve` all run the cascade identically for EML — no native intermediate.
- [ ] 13. Error-handling per design D9: OR API throws → `ConversionFailedException` (NO raw-parse fallback, never emit un-redacted content); Twig throws → minimal redacted envelope + "(template rendering failed)"; per-attachment render failure → skip page, "kon niet worden weergegeven", embed nothing; catastrophic → `ConversionFailedException`.
- [ ] 14. Surface the two config keys in admin settings UI (or document as IAppConfig keys with UI follow-up). Validate `max_attachment_render_size_bytes` as a positive integer and `append_attachment_pages` as boolean.
- [ ] 15. Unit tests `tests/unit/Service/EmlPdfAssemblyServiceTest.php` — fixtures over redacted results: text-only; HTML body; redacted PDF attachment; mixed (redacted renderable + non-renderable + `unsupported`); inline `cid:` from the map; nested redacted EML depth 1/2/3; empty body; both body parts null; unsupported→placeholder; oversize→placeholder; per-attachment render failure recovery; assertion that NO original/redacted bytes are embedded.
- [ ] 16. Unit tests `tests/unit/Service/Conversion/EmlBackendTest.php` — `isAvailable()` reflects both deps; `convert()` calls OR's anonymise-EML API (not `parseEmlStructured`) and delegates to assembly; OR API failure → `ConversionFailedException` with no raw-parse fallback; `preserve`-for-EML silently overridden to PDF (no error, assembled PDF returned).
- [ ] 17. Integration test: anonymise an EML via the per-document endpoint (`pdf-only` default). Verify output is PDF/A-3b, contains the redacted envelope, redacted renderable attachments as appended pages, unsupported attachments as placeholder pages, and NO embedded original files.
- [ ] 18. Integration test: PDF/A-3b conformance via `pdfinfo`/`verapdf`; confirm the PDF contains no embedded file attachments; nested-EML depth-2 renders its redacted envelope; `append_attachment_pages:false` yields envelope-only.

## Acceptance Criteria

- `EmlBackend` consumes OR's anonymise-EML (redacted) result and performs no redaction itself; it never calls the raw `parseEmlStructured()`.
- The assembled PDF renders OR's redacted headers, redacted body (HTML preferred, plain fallback), and redacted inline images from OR's map.
- Renderable redacted attachments render as appended pages from their redacted bytes; no original (or redacted) attachment bytes are embedded as PDF/A-3 files.
- `unsupported` attachments and oversize/non-renderable attachments become placeholder pages with no embedded or rendered content.
- EML inputs always produce a PDF; `outputFormat: "preserve"` is silently overridden to PDF for EML (no error).
- Output is PDF/A-3b; no failure path emits un-redacted content.

## Quality and Verification

- Run `composer check:strict` — clean (fix pre-existing issues only in touched files).
- Manual smoke against a live stack: EML with redacted body + a renderable attachment + an `unsupported` attachment; verify the assembled output, the placeholder page, and that `preserve` is silently overridden to PDF (no error). Toggle `append_attachment_pages:false`.
- CHANGELOG under "Added": EML inputs now produce an anonymised PDF/A-3b (redacted envelope + redacted attachment pages + placeholders for un-anonymisable attachments); note the privacy change vs the prior draft (no verbatim embedding) and that `preserve` is silently overridden to PDF for EML.
- Document the NL-only template limitation; EN follows `register-i18n`.
- Run `openspec validate "eml-pdf-assembly"` — clean.
---
kind: code
depends_on:
  - openregister:text-extraction-eml
  - openregister:anonymise-eml-structured
---

## Why

`anonymise-output-as-pdf-by-default` (Change A) sketched an `EmlBackend` in its conversion cascade with placeholder behaviour: "extract body via OR's text extractor, wrap in HTML, render via mPDF". That sketch produces a PDF containing only the email body — no headers, no attachments, no preserved layout. For real Wob/Woo correspondence and email-archive anonymisation, that's not enough: the rendered PDF needs to carry the email's full visible structure (sender / recipient / subject / date headers + body) AND its attachments so a reviewer can see the complete piece of correspondence in one document.

Anonymisation is the whole point of this pipeline, and an email leaks PII from every component — headers (sender/recipient names and addresses), the body, AND every attachment. A previous draft of this change assembled the email **as-is** and embedded the original attachment bytes verbatim. For anonymised output that is a privacy hole: the rendered envelope would show real names and the embedded files would carry the full un-redacted originals. So this change is reframed around a single architectural decision: **OpenRegister redacts every component; DocuDesk only assembles the redacted result.** DocuDesk performs no redaction itself.

OpenRegister's paired changes provide the two halves of the contract:

- `text-extraction-eml` exposes the structured EML shape (`EmlStructure` = headers + `text/plain` / `text/html` body + attachment bytes + inline-image index). This is the parse layer.
- `anonymise-eml-structured` (NEW, paired with this change) builds on it to expose an **anonymise-EML API** that returns an `AnonymisedEmlStructure`: redacted display headers (From/Reply-To/To/Cc/Subject/Date), a redacted body (`AnonymisedEmlBody` with `plain` and/or `html`, either may be null), and `attachments[]` of `AnonymisedEmlAttachment` where each entry is either `{filename, mimeType, redactedContent}` (a format OR supports and has redacted) or `{filename, mimeType, unsupported: true}` (no anonymiser available for that format), plus an inline-image map (`contentId → redacted bytes`) for `cid:` resolution.

This DocuDesk-side change **consumes the anonymise-EML result** and assembles a single PDF/A-3b from the already-redacted components: a redacted header block, the redacted body (HTML when present — preserves layout / inline images; plain-text fallback otherwise), and, for each attachment OR could redact, the **redacted bytes** rendered into appended pages via the existing conversion cascade (PDF / image / text / Word / nested EML → recurse). Attachments OR could **not** redact never have their bytes embedded or rendered — they get a placeholder page only.

The result is a single PDF/A-3b that combines the redacted email and its redacted attachments in one cohesive artifact. Operators get one reliably-anonymised file out of anonymisation; legal reviewers get one file to review; the assembly mechanics (Twig envelope template + mPDF + divider pages + recursive nested-EML rendering) are preserved from the prior draft.

## What Changes

- **MODIFIED:** The `EmlBackend` in `pdf-conversion`'s cascade (introduced by Change A `anonymise-output-as-pdf-by-default`) is upgraded from "extract flat text + render as plaintext PDF" to "consume OR's anonymise-EML result + assemble rich PDF/A-3b **from redacted components**".
- **CHANGED FROM PRIOR DRAFT — redacted input, not raw parse:** This change CONSUMES OR's anonymise-EML API (the `AnonymisedEmlStructure`), NOT the raw `parseEmlStructured()`. OR is the single source of all redaction. DocuDesk treats every header, body, attachment and inline image it receives as already redacted and performs NO redaction of its own.
- **CHANGED FROM PRIOR DRAFT — verbatim embedding of originals DROPPED:** The prior draft embedded every original attachment as a PDF/A-3 file attachment (byte-identical originals). For anonymised output that is a PII leak — the originals are un-redacted. This change **removes** verbatim PDF/A-3 embedding of original attachment bytes. (A future change MAY embed the *redacted* attachment bytes as PDF/A-3 files for archival self-containment; out of scope here.)
- **CHANGED FROM PRIOR DRAFT — unsupported attachments become a placeholder, content dropped:** For any attachment OR marks `unsupported: true` (no anonymiser available for that format), DocuDesk MUST NOT embed or render its bytes. It appends a placeholder/divider page noting `Bijlage <N>: <filename> (<type>) weggelaten — geen anonimiseerder beschikbaar`. This is the agreed privacy-safety policy: un-anonymisable content is omitted, never leaked.
- **CHANGED FROM PRIOR DRAFT — EML always outputs PDF:** `outputFormat: "preserve"` is not meaningful for EML, because a reliably-redacted native `.eml` is not produced (OR redacts components, not a re-serialised EML). EML inputs always resolve to a PDF output: when an EML input is anonymised with `outputFormat: "preserve"`, the request is silently overridden to the PDF cascade (no error returned). The three-mode model from `anonymise-pdf-only-output-mode` (`pdf-only` / `pdf` / `preserve`) still governs the non-EML cascade; for EML, `preserve` is treated as PDF.
- **NEW:** `EmlPdfAssemblyService` that:
  1. Receives OR's redacted anonymise-EML result (the `EmlBackend` calls OR's anonymise-EML API and passes the result in).
  2. Renders a Twig template into HTML — redacted header block + redacted body section. Body uses the redacted `html` when present; falls back to redacted `plain` wrapped in a `<pre>` block; falls back to an "(empty body)" notice when both are null.
  3. For HTML body: resolves inline images referenced by `cid:` URLs against OR's inline-image map (`contentId → redacted bytes`) and embeds them as `data:` URLs.
  4. For each attachment with `redactedContent`: renders the **redacted bytes** to appended pages via the existing cascade backends (PDF / image / text / Word / nested EML → recurse), each preceded by a divider page.
  5. For each attachment marked `unsupported`: appends a placeholder/divider page only; embeds and renders nothing.
  6. Returns the assembled PDF/A-3b file.
- **NEW Twig template:** `lib/Resources/templates/eml/email_envelope.twig` — renders the redacted header block (From/Reply-To/To/Cc/Subject/Date) and embeds the redacted body content. NL-only labels in v1 (consistent with `anonymisation-grondslagen-summary`); EN follows `register-i18n`.
- **NEW config:** `docudesk.conversion.eml.append_attachment_pages` (boolean, default `true`) — when false, only the redacted envelope is rendered; redacted attachments are not rendered as pages. (Unsupported-attachment placeholder pages still appear, as they carry no content.)
- **NEW config:** `docudesk.conversion.eml.max_attachment_render_size_bytes` (integer, default 25 MB) — redacted attachments larger than this get a placeholder page instead of being rendered (bounds assembly time).
- **MODIFIED:** Change A's `EmlBackend.isAvailable()` (currently a permanent `false` stub) returns true once BOTH OR's `anonymise-eml-structured` API AND this change's `EmlPdfAssemblyService` are present. Until both land, the backend reports unavailable and EML inputs fall through to the cascade's 422 terminus.

### Scope of attachment handling per type

OR decides what is redactable. DocuDesk renders only what OR has redacted.

| OR attachment entry | Rendered as appended pages (default config)? | Bytes embedded? |
|---|---|---|
| `{redactedContent}`, renderable MIME (`application/pdf`, common image MIMEs, plain-text, `message/rfc822`, Word via PhpWord) | ✓ (redacted bytes via cascade) | ✗ (no verbatim embedding) |
| `{redactedContent}`, non-renderable MIME (XLSX, ODS, archives, calendar, …) | ✗ (placeholder page: "redacted but not renderable; see source dossier") | ✗ |
| `{unsupported: true}` | ✗ (placeholder page: "weggelaten — geen anonimiseerder beschikbaar") | ✗ |
| over `max_attachment_render_size_bytes` | ✗ (placeholder page: "te groot om weer te geven") | ✗ |

### Recursive nested EML

When a renderable attachment is `message/rfc822`, OR's anonymise-EML result carries a **redacted** nested `AnonymisedEmlStructure` (up to depth 3). The assembly recursively renders the nested redacted envelope + its own redacted attachments. Beyond depth 3, OR returns the nested EML as an `unsupported`/placeholder entry — DocuDesk renders the placeholder page only (no bytes).

### Out of scope

- **Archival embedding of redacted attachment bytes** — a future change MAY embed OR's redacted attachment bytes as PDF/A-3 file attachments for self-containment. This change renders redacted attachments as pages only and embeds nothing.
- **Calendar invite (`text/calendar`) rendering** — handled by OR's supported/unsupported flag like any other attachment; DocuDesk renders a placeholder unless OR returns renderable redacted content.
- **Encrypted EML decryption** — OR decides; if it cannot redact an encrypted body it returns a null body / unsupported attachments and DocuDesk renders the corresponding placeholders.
- **Real-time preview before save** — assembly is synchronous and produces the file directly.
- **Any DocuDesk-side redaction** — explicitly out of scope. All redaction is OR's.

## Capabilities

### New Capabilities

(none — this change extends an existing capability without introducing new ones.)

### Modified Capabilities

- `pdf-conversion`: the `EmlBackend` in the conversion cascade is upgraded from a placeholder plaintext path to a redacted-component assembly path. Its `isAvailable()` now requires both OR's `anonymise-eml-structured` API AND this change's assembly service; until both land, the backend reports unavailable.

## Cross-app Dependencies

- **Hard** — `openregister:anonymise-eml-structured` — provides the anonymise-EML API returning the `AnonymisedEmlStructure` (redacted headers + `AnonymisedEmlBody` + per-attachment `AnonymisedEmlAttachment` with `redactedContent`/`unsupported` + inline-image map). This change consumes that contract and performs no redaction itself. Until it lands, `EmlBackend.isAvailable()` returns false.
- **Hard** — `openregister:text-extraction-eml` — provides the underlying structured EML parse (`EmlStructure` shape) that `anonymise-eml-structured` redacts on top of. It is the foundation of the contract this change consumes.
- **Soft** — `docudesk:anonymise-output-as-pdf-by-default` — provides the conversion cascade and the `EmlBackend` stub this change un-stubs. Until Change A is applied there is no cascade to plug into.

The dependency arc: `text-extraction-eml` (parse the EML into `EmlStructure`) → `anonymise-eml-structured` (redact every component of that structure, expose the anonymise-EML API) → `eml-pdf-assembly` (this change: assemble the redacted components into one PDF/A-3b). Each Hard row MUST be tracked as a `Depends on` link from this change's GitHub issue to the target's tracking issue.

## Impact

- **Code (docudesk):**
  - `lib/Service/Conversion/EmlBackend.php` (currently a permanent-false stub) — un-stub. `isAvailable()` returns true when OR's anonymise-EML API is callable AND `EmlPdfAssemblyService` is registered. `convert()` calls OR's anonymise-EML API for the source file and passes the redacted result to `EmlPdfAssemblyService::assemble()`.
  - `lib/Service/EmlPdfAssemblyService.php` — NEW. Orchestrates assembly from the **redacted** result (render redacted envelope → render redacted attachments via cascade → placeholder pages for unsupported/oversize). Constructor injects: the OR anonymise-EML service (OR DI lookup pattern, as `AnonymizationService` already resolves OR services reflectively), `PdfService`, the conversion-backends list (for recursive rendering of redacted attachment bytes), the logger, `IAppConfig`.
  - `lib/Resources/templates/eml/email_envelope.twig` + `lib/Resources/templates/eml/divider.twig` — NEW. Redacted header block + body; divider/placeholder pages. NL labels.
  - mPDF assembly helper for PDF/A-3b instantiation + multi-pass page concatenation (no file-embedding step — verbatim embedding is dropped).
  - Admin settings UI — surface the two config keys (`append_attachment_pages`, `max_attachment_render_size_bytes`).
- **API contract:** No request payload changes. EML + `outputFormat: "preserve"` is silently overridden to the PDF cascade (EML always produces PDF) — no error returned. All response shapes unchanged.
- **Cross-app:** Hard deps on OR's `anonymise-eml-structured` (redaction + anonymise-EML API) and `text-extraction-eml` (the structure it redacts). No OR-side changes in this DocuDesk change. Soft dep on Change A for the cascade.
- **Privacy / compliance:** This is the privacy-correct path. Every rendered component is OR-redacted; un-anonymisable attachments are dropped to a placeholder rather than leaked; no verbatim originals are embedded. PDF/A-3b output remains archival-grade and hard to re-edit.
- **Performance:** Assembly cost dominated by recursive rendering of redacted attachment bytes (bounded by `max_attachment_render_size_bytes` and OR's depth-3 nesting cap). Dropping verbatim embedding reduces output size versus the prior draft.
- **Migration:** None (no schema change). New behaviour activates when both OR changes and this change are applied.
- **Tests:** Unit tests for assembly from an `AnonymisedEmlStructure` (template render, attachment recursion, unsupported→placeholder, oversize→placeholder, always-PDF, `preserve`-overridden-to-PDF-for-EML); integration tests for EML→PDF end-to-end with redacted/unsupported attachment combinations.
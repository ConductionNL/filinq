## Context

`anonymise-output-as-pdf-by-default` (Change A) introduced a conversion cascade that flattens any anonymisation output to PDF/A-3b. Its `EmlBackend` (`lib/Service/Conversion/EmlBackend.php`) is currently a permanent-false stub: `isAvailable()` returns `false`, `convert()` throws `ConversionFailedException` as a defensive backstop. EML inputs therefore fall through to the cascade's 422 terminus.

This change un-stubs that backend. The crux is **anonymisation**: an email leaks PII from headers, body, AND every attachment. A previous draft of this change parsed the EML as-is, rendered the original headers/body, and embedded original attachment bytes verbatim as PDF/A-3 files. For anonymised output that is wrong — it would leak the un-redacted email. The architecture is therefore reframed:

> **OpenRegister redacts every component; DocuDesk only assembles the redacted result.**

OR's paired `anonymise-eml-structured` change (built on `text-extraction-eml`) exposes an **anonymise-EML API** that returns an `AnonymisedEmlStructure`. DocuDesk consumes that and assembles a PDF/A-3b purely from already-redacted parts. DocuDesk performs no redaction of its own. The assembly mechanics — Twig envelope template, mPDF multi-pass, divider pages, recursive nested-EML rendering, the `PdfService`/cascade reuse — are preserved from the prior draft; what changes is the **input** (redacted, not raw) and the **attachment policy** (render redacted bytes or drop to a placeholder; never embed originals).

## Goals / Non-Goals

**Goals:**

- Assemble a single PDF/A-3b from OR's **redacted** anonymise-EML result: redacted header block + redacted body + redacted attachments.
- Use the redacted HTML body when present (fidelity); redacted plain-text fallback otherwise.
- Render each redactable attachment's **redacted bytes** as appended pages via the existing pdf-conversion cascade (configurable; default on).
- For attachments OR could not redact (`unsupported`), append a placeholder page and drop the content entirely.
- Always produce a PDF for EML inputs; `outputFormat: "preserve"` is silently overridden to PDF for EML (no error).
- Honour OR's depth-3 cap on nested EML chains.

**Non-Goals:**

- Perform any redaction in DocuDesk. All redaction is OR's.
- Embed original attachment bytes verbatim (dropped — would leak un-redacted PII).
- Embed redacted attachment bytes as PDF/A-3 files (deferred to a possible future change).
- Decrypt encrypted EML bodies. OR decides redactability; DocuDesk renders the placeholders OR's result implies.
- Strip signatures or quoted reply chains. The redacted body renders as-is.
- Interactive preview before saving. Synchronous, save-and-return.

## Decisions

### D1. Consume OR's redacted anonymise-EML result — not the raw parse

The `EmlBackend.convert()` calls OR's anonymise-EML API (from `anonymise-eml-structured`) for the source file and receives an **`AnonymisedEmlStructure`**:

- redacted display headers: From / Reply-To / To / Cc / Subject / Date (already anonymised by OR),
- redacted body: an `AnonymisedEmlBody` with `html` and/or `plain` (either may be null),
- `attachments[]` of `AnonymisedEmlAttachment`, each either `{filename, mimeType, redactedContent}` (OR supports and redacted the format) or `{filename, mimeType, unsupported: true}` (no anonymiser available),
- an inline-image map (`contentId → redacted bytes`) for `cid:` resolution.

DocuDesk passes that result straight to `EmlPdfAssemblyService::assemble()`. It does NOT call `parseEmlStructured()` directly and does NOT redact anything. This is the single most important change from the prior draft: OR is the source of all redaction.

**Rationale:** redaction logic (NER, placeholder numbering, per-format anonymisers) already lives in OR. Duplicating it in DocuDesk would diverge and risk leaking PII. Keeping DocuDesk a pure assembler means a single audited redaction path.

### D2. Body rendering — redacted HTML preferred, redacted plain-text fallback

Body rendering priority, operating on OR's **redacted** body:

1. redacted `html` non-null/non-empty → render directly into the Twig envelope; resolve `cid:` inline images via OR's inline-image map (D3).
2. else redacted `plain` non-null/non-empty → render in a `<pre>`-wrapped block.
3. else → render the localised `(Bericht zonder body — alleen bijlagen)` placeholder.

**Trade-off:** mPDF's HTML rendering isn't pixel-perfect; acceptable for archival rendering. The content is already redacted, so fidelity loss never risks PII.

### D3. Inline image resolution via OR's inline-image map

When the redacted HTML references `<img src="cid:<contentId>">`, the assembly looks up `<contentId>` in OR's inline-image map (`contentId → redacted bytes`) and substitutes a `data:<mimeType>;base64,<redacted>` URL. Unresolved references are left in place (mPDF renders a placeholder) and logged at debug level.

**Rationale:** the bytes in the map are already redacted by OR. DocuDesk only base64-substitutes; it never touches original inline-image bytes.

### D4. Attachment policy — render redacted bytes, or placeholder. No verbatim embedding.

For each entry in the redacted `attachments[]`:

- **`{redactedContent}` + renderable MIME + within size cap** → divider page, then render the **redacted bytes** via the existing cascade backends:

  ```
  application/pdf   → import pages from the redacted PDF
  image/*           → one page with <img src="data:...;base64,<redacted>">
  text/plain etc.   → one page with <pre>-wrapped redacted text
  message/rfc822    → recurse: assemble OR's redacted nested EML result (D5)
  DOCX/ODT/RTF/HTML → Change A's PhpWordBackend on the redacted bytes, then import pages
  ```

- **`{redactedContent}` + non-renderable MIME** → divider/placeholder page only: `Bijlage <N>: <filename> — geredigeerd maar niet weer te geven`. No bytes embedded.
- **`{unsupported: true}`** → placeholder page: `Bijlage <N>: <filename> (<mimeType>) weggelaten — geen anonimiseerder beschikbaar`. **No bytes embedded or rendered.** This is the agreed privacy-safety policy — un-anonymisable content is dropped, never leaked.
- **over `max_attachment_render_size_bytes`** → placeholder page: `Bijlage <N>: <filename> — te groot om weer te geven`. No bytes embedded.

**Change from prior draft:** the prior draft embedded EVERY attachment's ORIGINAL bytes as a PDF/A-3 file attachment. That is removed. Embedding originals leaks the un-redacted email. No verbatim embedding happens in this change. (A future change MAY embed *redacted* bytes for archival self-containment.)

**Configurability:** `docudesk.conversion.eml.append_attachment_pages` (default true). When false, only the redacted envelope renders; redactable attachments are not rendered as pages. Unsupported/oversize placeholder pages still appear (they carry no content).

### D5. Recursive nested EML — depth-3 budget owned by OR

When a renderable attachment is `message/rfc822`, OR's result carries a **redacted** nested `AnonymisedEmlStructure` (up to depth 3). The assembly recurses with the same template + rules. Beyond depth 3, OR returns the nested EML as an `unsupported`/placeholder entry; DocuDesk renders the placeholder page only.

**Rationale:** the depth cap and the redaction both live in OR. DocuDesk inherits whatever OR returns at each level — it never re-parses or re-redacts.

### D6. Twig template — single envelope template, recursive renders share it

`lib/Resources/templates/eml/email_envelope.twig` renders one redacted EML envelope: header block + body. Recursive nested renders reuse it; the assembly service concatenates pages via mPDF `AddPage()`.

NL-only labels in v1 (consistent with `anonymisation-grondslagen-summary`):

```
Van:           <redacted from>
Antwoord aan:  <redacted reply-to> (only if present)
Aan:           <redacted to (comma-joined)>
Cc:            <redacted cc> (only if present)
Onderwerp:     <redacted subject>
Datum:         <date formatted YYYY-MM-DD HH:MM>

<redacted body content>
```

EN translations follow `register-i18n` landing.

### D7. PdfService / cascade reuse, PDF/A-3b mode

The assembly creates an mPDF instance directly (multi-pass: write redacted envelope HTML → append rendered attachment pages), configured with the SAME PDF/A-3b settings `PdfService` uses (font embedding, no JS, no external resources, `SetPDFAVersion('3-B')`). Per-attachment rendering reuses the cascade backends so the redacted bytes go through the same PDF/image/text/Word paths the rest of the cascade uses. There is **no** file-embedding pass (verbatim embedding is dropped), which also simplifies the mPDF surface versus the prior draft (no `setEmbeddedFile` dependency).

**Alternative considered:** extend `PdfService` with a multi-pass API. Rejected for v1 — the multi-pass work is EML-assembly-specific.

### D8. EML always outputs PDF — `preserve` silently overridden to PDF for EML

`outputFormat: "preserve"` means "keep the native anonymised file". For EML there is no reliably-redacted native `.eml`: OR redacts components (headers/body/attachments), not a re-serialised EML. So `preserve` cannot deliver a redacted EML and would either leak the original or return nothing useful.

Decision: **EML inputs always resolve to a PDF output.** When the anonymise request targets an EML input with `outputFormat: "preserve"`, the format is silently overridden to the PDF cascade — no error is returned; the caller receives the assembled PDF. For `pdf-only` and `pdf` (the other two modes from `anonymise-pdf-only-output-mode`), the EML cascade runs normally and produces the assembled PDF; there is no native EML intermediate to keep or delete, so all three modes (`pdf-only`, `pdf`, and the overridden `preserve`) behave identically for EML.

**Rationale:** the three-mode model (`pdf-only` / `pdf` / `preserve`) is sound for re-editable native formats (DOCX/ODT). EML is structurally different — its redacted form only exists as the assembled PDF. Forcing PDF is the only privacy-correct outcome.

### D9. Error handling — degrade gracefully, never leak

| Failure | Recovery (privacy-safe) |
|---|---|
| OR anonymise-EML API throws | Throw `ConversionFailedException` → cascade falls through (422 for EML). Do NOT fall back to a raw/unredacted parse — that would leak PII. (This differs from the prior draft, which fell back to flat `extractEml`.) |
| Twig render throws | Render a minimal envelope with the redacted headers + `(template rendering failed)` notice. |
| Inline image unresolved in OR's map | Leave the `cid:` reference; mPDF renders a placeholder; debug log. |
| A renderable redacted attachment fails to render | Skip the page; divider says `kon niet worden weergegeven`. No bytes embedded. |
| Catastrophic (no output possible) | Throw `ConversionFailedException` per Change A's contract; cascade 422s. |

The key invariant: **no failure path ever emits un-redacted content.** The prior draft's flat-text fallback is removed precisely because it bypassed redaction.

### D10. Configuration

| Key | Default | Purpose |
|---|---|---|
| `docudesk.conversion.eml.append_attachment_pages` | `true` | When false, only the redacted envelope renders; redactable attachments are not appended as pages. |
| `docudesk.conversion.eml.max_attachment_render_size_bytes` | `26214400` (25 MB) | Redacted attachments larger than this get a placeholder page instead of rendering. |
| `docudesk.conversion.eml.divider_template` | `eml/divider.twig` | Optional override for the divider/placeholder template. |

Standard `IAppConfig` pattern.

### D11. Declarative-vs-imperative (ADR-031) — justified imperative

ADR-031 prefers declarative configuration over imperative service code where a declarative mechanism exists (e.g. notification dialects, register settings JSON). This change is **document rendering and PDF assembly** — multi-pass mPDF instantiation, page concatenation, per-attachment cascade dispatch, Twig templating. There is no declarative mechanism for "assemble a PDF/A-3b from redacted email components"; this is inherently imperative orchestration code in `EmlPdfAssemblyService`. **Conclusion: justified imperative.** The only declarative surface is the Twig templates (envelope + divider) and the three `IAppConfig` keys, which are kept declarative/config-driven. No imperative notification dispatch or cross-app RPC is introduced (the OR call is the documented anonymise-EML API consumption, a Hard cross-app dependency, not a phantom RPC).

## Cross-app Contract (consumed)

This change depends on OR's anonymise-EML API shape (from `anonymise-eml-structured`):

```
anonymiseEml(file) -> AnonymisedEmlStructure {
  headers: { from, replyTo, to[], cc[], subject, date }   // all already redacted
  body:    AnonymisedEmlBody { html: ?string, plain: ?string }   // either/both may be null, redacted
  attachments: Array<AnonymisedEmlAttachment =
      { filename, mimeType, redactedContent: bytes }
    | { filename, mimeType, unsupported: true }
  >
  inlineImages: Map<contentId, redactedBytes>     // for cid: resolution
  nested EML attachments carry a recursive AnonymisedEmlStructure (depth ≤ 3)
}
```

DocuDesk consumes this contract read-only. If OR's actual method name / shape differs at apply time, the `EmlBackend`/`EmlPdfAssemblyService` adapt to OR's published signature — but the redaction-in-OR, assembly-in-DocuDesk split is fixed.

## Risks / Trade-offs

- **[Dropping verbatim embedding loses archival self-containment]** → Accepted: leaking un-redacted originals is the worse failure. A future change can embed *redacted* bytes.
- **[mPDF HTML fidelity for modern emails]** → Documented limitation; content is redacted so fidelity loss never risks PII.
- **[OR contract not yet final]** → `EmlBackend.isAvailable()` stays false until OR's API is present; this change adapts to OR's published signature at apply time.
- **[Recursive nesting CPU]** → depth-3 cap owned by OR; bounded.
- **[NL-only template]** → same as `anonymisation-grondslagen-summary`; EN follows `register-i18n`.
- **[`preserve` silently overridden for EML]** → documented; a caller who sends `preserve` for an EML still gets a PDF rather than an error. The only privacy-correct outcome for EML, and the least surprising (the request still succeeds).

## Migration Plan

1. Land OR's `text-extraction-eml` then `anonymise-eml-structured` (the anonymise-EML API). Until OR exposes it, this change's backend stays unavailable.
2. Land `EmlPdfAssemblyService`, the Twig templates, the config keys, and the mPDF PDF/A-3b helper.
3. Un-stub `EmlBackend`: `isAvailable()` true when OR's anonymise-EML API is callable AND the assembly service is registered; `convert()` calls OR and assembles.
4. Add the EML-specific `preserve`→PDF override in the anonymise path (silent, no error).
5. Release. EML inputs are anonymised by OR and assembled to PDF/A-3b by DocuDesk.

**Rollback:** disable the backend via Change A's `docudesk.conversion.backends.eml_enabled = false`. EML inputs return 422 in PDF modes.

## Seed Data

Not applicable — this change introduces no new schemas and no seed data. EML files are processed at runtime via the conversion cascade; all redaction is OR's.

## Open Questions

- **OR anonymise-EML method name / exact shape** — consumed as specified above; adapt to OR's published signature at apply time. The redaction-in-OR / assembly-in-DocuDesk split is fixed.
- **Divider template extensibility** — ship the default; expose `docudesk.conversion.eml.divider_template` for an override; full customisation UI is a follow-up.
- **Future: embed redacted attachment bytes as PDF/A-3 files** — deferred. This change renders redacted attachments as pages only.
---
kind: code
---

## Why

The agent document surface could change a paragraph's **words** and nothing else.
Asked to make a heading a heading, centre a signature block, mark a clause bold, or
set the document's title and keywords, it had no tool that could do any of it.

Those are not one capability. They are three regions of the package:

| Capability | Where it lives (OOXML) | Where it lives (ODF) |
|---|---|---|
| Text | `<w:t>` inside `<w:r>` inside `<w:p>` | `<text:p>` |
| Style / layout | `<w:pPr>`, `<w:rPr>` **inside the paragraph** | a `text:style-name` pointing at `<office:automatic-styles>` **elsewhere in `content.xml`** |
| Metadata | `docProps/core.xml` — **a different part** | `meta.xml` — **a different part** |

Metadata being a different part is what forced the shape of this change. The
existing `PackageCodec` had `readPart` / `writePart` as private methods, so
addressing a second part meant either sharing them or growing a second copy of the
ZipArchive dance. The property that untouched parts survive an edit byte-identical
is a property of exactly that code, and two copies of it would drift.

## What Changes

- **`PackagePartIo`** — extracted from `PackageCodec`, now shared. Adds `hasPart()`,
  because "this document has no metadata part yet" is an ordinary state to be
  handled by creating one, not an error to report.
- **`PackageMetadataCodec`** — five fields (`title`, `subject`, `creator`,
  `keywords`, `description`) addressed by **format-neutral name**. OOXML stores
  keywords as `cp:keywords` and ODF as `meta:keyword`; a caller that had to know
  which would be writing per-format code.
  - `created` / `modified` are deliberately **not** writable. They record what
    happened to the document; an agent able to set them would turn that record from
    a fact into a claim.
- **`BlockStyleCodec`** — bold, italic, underline, alignment, heading level 0–9,
  list, page-break-before, applied to an anchored paragraph. Merges into existing
  `<w:pPr>` / `<w:rPr>` rather than replacing them, so hand-set spacing and
  indentation survive.
- **`PackageCodec::ACTION_STYLE`** — a new edit action carrying a `style` object
  instead of text. Separate from `replace` on purpose: a caller who wanted bold
  should not have to resend the text, because a caller that resends text is a
  caller that can paraphrase the paragraph while "only" making it bold.
- **Two new agent tools**, `readDocumentMetadata` and `setDocumentMetadata`. Style
  needs no new tool — it flows through the existing `editDocument`.
- **`EditSessionService::runSession()` now takes a transform**, so a metadata write
  gets the identical lock, version-recheck-immediately-before-write, tag-then-write
  and unlock path as a body edit. Metadata is a smaller change than a paragraph
  rewrite, not a less accountable one.

## Scope: OOXML only for style, and refused by name for ODF

ODF does not carry direct formatting inside the paragraph. `text:p` holds a
`text:style-name` pointing at a `<style:style>` in `<office:automatic-styles>`, and
an ODF heading is a **different element** (`text:h`) rather than a styled paragraph.
Supporting it properly means minting automatic styles, guaranteeing name uniqueness
against existing ones, and in the heading case rewriting the very element the anchor
scanner keys on.

That is not done here, and the important part is that it is **refused by name**. An
ODF style request that returned the markup unchanged would report success and change
nothing. Text edits and metadata *do* work on `.odt`, and the refusal says so.

## Two bugs the tests found

Both were "reports success, changes nothing" — the failure this codebase keeps
meeting:

1. **`heading: 0` did nothing.** Level 0 means body text, so it produced an empty
   property string, so the method returned early and left the existing
   `<w:pStyle>` in place — while reporting the restyle applied. Level 0 is now a
   *removal*, not an absence.
2. **A self-closing metadata root broke the write.** `<cp:coreProperties/>` is what
   several writers emit for a document with no properties set, and there is no
   closing tag to insert before. The ordinary "empty metadata part" case reported
   the part as corrupt.

## Capabilities

### New Capabilities
- `document-rich-editing`: how an agent changes a document's style, layout and
  metadata, and what it is refused.

### Modified Capabilities
- `document-editing`: `applyEdits` gains a fourth action, `style`.

## Impact

- **Code**: new `PackagePartIo`, `PackageMetadataCodec`, `BlockStyleCodec`;
  `PackageCodec` gains an action and loses its private part IO;
  `EditSessionService` gains two methods and a parameterised session;
  `DocumentAgentService` gains two tools.
- **Agent surface**: 3 document tools → **5**.
- **Compatibility**: no existing behaviour changes. The 75 pre-existing editing
  tests pass unmodified, which is what makes the IO extraction safe to claim.

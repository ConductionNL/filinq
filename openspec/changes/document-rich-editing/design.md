## Context

`PackageCodec` edits one region of one part: a paragraph's visible text inside
`word/document.xml` or `content.xml`. Everything else in the package stays
byte-identical, which is the property ADR-087 §2 prefers over parse-and-reserialise
because re-serialisation silently drops comments, tracked changes and styles that no
test asserts on.

Style, layout and metadata each sit somewhere else:

- **Style/layout (OOXML)** is inside the paragraph — `<w:pPr>` and `<w:rPr>`. Same
  span, so the same rewrite mechanism works.
- **Style/layout (ODF)** is *not* inside the paragraph. `text:p` carries a
  `text:style-name` pointing at a `<style:style>` in `<office:automatic-styles>`,
  a different region of `content.xml` — and a heading is a different element,
  `text:h`.
- **Metadata** is a different **part** entirely: `docProps/core.xml`, `meta.xml`.

## Goals / Non-Goals

**Goals:**

- Style, layout and metadata reachable from the chat, with the same accountability
  as a text edit.
- Merging semantics that never lose properties the user set by hand.
- Every refusal by name, with the message saying what still works.

**Non-Goals:**

- ODF style/layout. See D4 — refused by name, not silently ignored.
- Tables, images, sections, headers/footers.
- Arbitrary named styles. The `heading` level covers the common case; exposing
  "apply the style called X" would let an agent name a style the document does not
  define, producing markup a suite renders as nothing.
- `created` / `modified`. See D2.

## Decisions

### D1 — Extract `PackagePartIo` rather than grow a second copy

Metadata lives in a different part, so the codec needed `readPart` / `writePart`
which were private to `PackageCodec`. The options were sharing them or duplicating
the ZipArchive dance.

Shared. The property that untouched entries survive an edit byte-identical — the
whole reason for the in-place approach — is a property of exactly that code. Two
copies would drift, and the drift would be invisible until a document came back
subtly different.

`hasPart()` is new. A document with no metadata part is **ordinary**, not
exceptional: PhpWord-generated ODF routinely has none. Catching `readPart()`'s
exception would conflate "no metadata yet, create one" with "this package is
corrupt".

### D2 — Five writable fields, and the timestamps are not among them

`title`, `subject`, `creator`, `keywords`, `description` are the fields both
families carry and users recognise.

`dcterms:created` / `dcterms:modified` and ODF's `meta:editing-cycles` are excluded
deliberately. They are a *record of what happened to the document*. An agent that
could set them would make that record a claim rather than a fact — and the whole
agent-artefact design (ADR-088, the `Agent authored` tag, the restorable prior
version) exists so that what happened to a document stays knowable.

### D3 — `style` is its own action, not a flag on `replace`

A caller who wants a paragraph bold should not have to resend its text.

This is not tidiness. The caller is a language model, and a model that must resend
the text in order to change the formatting is a model that will paraphrase the
paragraph while "only" making it bold. Separating the actions removes the
opportunity: `ACTION_STYLE` carries no text and `resolveEdits()` rejects it if it
carries no style properties either.

### D4 — ODF style is refused by name, and the refusal names what works

Doing ODF properly means minting `<style:style>` entries in
`<office:automatic-styles>`, guaranteeing generated names do not collide with
existing ones, and — for headings — rewriting `text:p` into `text:h`, which is the
element the anchor scanner keys on. That is a change to the anchoring substrate, not
a formatting feature.

The alternative to refusing is returning the markup unchanged, which reports success
and changes nothing. This project has met that failure repeatedly, so the refusal is
explicit and the message ends by saying text edits and metadata *do* work on `.odt`
— a refusal that leaves the reader thinking `.odt` is unsupported would be its own
kind of wrong.

### D5 — Merge properties; never replace the block

`<w:pPr>` commonly carries spacing, indentation and numbering a user set by hand.
Replacing it to apply an alignment would silently drop all of it.

So: properties of the same element name are dropped and re-added; everything else is
retained. `dropNamed()` also serves the *removal* cases (heading 0, `list: false`,
`pageBreakBefore: false`).

Turning a run property off writes `w:val="0"` rather than omitting the element. A
paragraph style can turn bold on, and only an explicit override countermands it —
omission just inherits.

### D6 — `runSession()` takes a transform

A metadata write needs the same lock, the same version re-check immediately before
the write, the same tag-then-write with rollback, and the same unlock-in-`finally`.

Parameterising the transform means one copy of that path. Copying the method for
metadata would have meant two copies of the only code preventing an agent from
clobbering a concurrent human edit — and the copy would drift precisely because
metadata "feels smaller".

Metadata is a smaller change than a paragraph rewrite. It is not a less accountable
one.

## Seed Data (ADR-001)

**None.** No OpenRegister schemas are introduced or modified. Test fixtures are
packages built in-process by the tests themselves.

## Declarative-vs-imperative decision (ADR-031)

| Behaviour | Path | Rationale |
|---|---|---|
| Metadata read/write | **Imperative** | Byte-level manipulation of a ZIP member. No schema, no derived value, no lifecycle. |
| Style/layout application | **Imperative** | XML rewriting inside a document package. |
| Tool exposure | **Declarative** | Already so: `#[McpTool]` per ADR-063, no hand-written registry. |

No behaviour matches a declarative category, so no `lib/Settings/docudesk_register.json`
patch is appropriate.

## Risks / Trade-offs

**Regex over XML.** The same trade-off `PackageCodec` already makes, for the same
reason: a DOM round-trip re-serialises and drops what it does not understand.
Contained by operating only within a single already-scanned span, and by the
merge-not-replace rule.

**A `numId` of 1 assumes a numbering definition exists.** `list: true` writes
`<w:numId w:val="1"/>`, which renders as a list in every document PhpWord or Word
produces, but a document with no `numbering.xml` will show the paragraph unlisted.
Minting a numbering definition is the same class of work as ODF automatic styles and
is not done here. This is the weakest part of the change and is called out rather
than hidden.

**Two bugs found by the tests, both "reports success, changes nothing".** `heading:
0` returned early and left the old style in place; a self-closing metadata root was
reported as corrupt. Both are fixed and both now have a test. Their shared shape is
worth noting: this is the failure mode of the whole change area, and it is why every
refusal here is explicit.

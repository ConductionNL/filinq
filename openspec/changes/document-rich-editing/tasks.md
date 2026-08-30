# Tasks

## 1. Shared package IO

- [ ] Extract `PackagePartIo` from `PackageCodec` with `readPart`/`writePart`, and add `hasPart`
- [ ] Point `PackageCodec` at it and delete its private copies

Acceptance criteria:
- The 75 pre-existing editing tests pass UNMODIFIED. That is what makes the extraction safe to claim; a green suite after editing the tests would prove nothing.
- `hasPart` exists because "no metadata part yet" is an ordinary state, not a corrupt package.

## 2. Metadata

- [ ] Add `PackageMetadataCodec` for `title`, `subject`, `creator`, `keywords`, `description`, addressed by format-neutral name
- [ ] Resolve each field to its format's element (`cp:keywords` vs `meta:keyword`)
- [ ] Create a well-formed, namespaced metadata part when the document has none
- [ ] Handle a self-closing metadata root
- [ ] Refuse unknown fields and the created/modified timestamps by name

Acceptance criteria:
- A metadata write leaves the body part byte-identical.
- Fields not named are unchanged.
- Values are XML-escaped and round-trip.

## 3. Style and layout

- [ ] Add `BlockStyleCodec` for bold, italic, underline, alignment, heading 0–9, list, pageBreakBefore
- [ ] Merge into existing `<w:pPr>`/`<w:rPr>` — same-named properties replaced, unrelated ones retained
- [ ] Make heading 0, `list: false` and `pageBreakBefore: false` REMOVALS, not absences
- [ ] Write an explicit off-override when a run property is switched off
- [ ] Refuse ODF by name, naming what still works on `.odt`
- [ ] Add `PackageCodec::ACTION_STYLE`, refusing a style edit that carries no properties

Acceptance criteria:
- Hand-set spacing and indentation survive a restyle. Without this the change loses user work silently.
- An ODF style request throws; it never returns the markup unchanged.
- Re-setting a property replaces rather than duplicates it.

## 4. Agent surface

- [ ] Add `readDocumentMetadata` and `setDocumentMetadata` tools
- [ ] Extend `editDocument`'s description to describe the `style` action and its properties
- [ ] Parameterise `EditSessionService::runSession()` with a transform so metadata writes share the lock/version/tag path

Acceptance criteria:
- A metadata write is refused on a stale version, tagged `Agent authored`, and leaves the prior version restorable — verified, not assumed.
- The document tool count goes 3 → 5.

## 5. Verify

- [ ] Full unit suite green; `phpcs` clean across `lib/Service/Editing/`
- [ ] Playwright E2E: style a paragraph and set metadata through the tool surface, asserting the bytes changed on exactly the targeted paragraph
- [ ] Confirm the two new tools appear in `tools/list` with dotted ids

Acceptance criteria:
- The E2E asserts on document bytes, not on the tool's own reply. A tool reporting success is not evidence the document changed.

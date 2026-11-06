# Tasks — multi-format-editing-tools

> **Not scheduled for implementation.** This change is specified so the format
> question is settled before `document-editing-tools` hardens a codec shape that
> only suits text. It cannot start until that change clears its own Phase 0.

## 0. Phase 0 — probe what the installed suite actually edits (hard gate)

- [x] 0.1 For each candidate type (odt/docx, ods/xlsx, odp/pptx, odg, csv, legacy doc/xls/ppt, pdf), probe the installed suite through `CheckFileInfo` and `IConversionManager` and record whether it is genuinely editable here — never inferred from the suite's marketing list.
- [x] 0.2 Publish the result as a versioned type-support declaration carrying the suite name, suite version and probe date. An unprobed type is UNSUPPORTED.
- [x] 0.3 Run the probe against Collabora AND Euro-Office. Any type editable on only one is published as suite-specific per ADR-087 §4, never as generally supported.

## 1. Codec interface + spreadsheet

- [x] 1.1 Extract a `DocumentCodecInterface` (`supports(mime)`, `read(package)`, `applyEdits(package, edits)`) and move the existing text codec behind it, leaving the WOPI session, lock discipline, `Version` precondition and ADR-088 marking untouched and shared.
- [x] 1.2 Add `SpreadsheetCodec` addressing cells as `Sheet!Cell`. No block-anchor machinery — a cell address is already a durable identity.
- [x] 1.3 Refuse a literal write into a cell currently holding a formula unless the caller sets `replaceFormula` FOR THAT CELL. A per-call or global flag is not acceptable: a bulk write must not carry the permission along.
- [x] 1.4 Report, on every accepted spreadsheet write, which cells' computed values changed as a consequence, and flag any dependent cell that became an error value.
- [x] 1.5 Bound a single call's cell count, refusing rather than truncating when exceeded.

## 2. Presentation

- [x] 2.1 Add `PresentationCodec` addressing slides by ID and shapes by ID — never by ordinal position, since slide order is not stable.
- [x] 2.2 Address speaker notes as a region distinct from slide content, so drafting talking points cannot alter what is on screen.

## 3. Refusals

- [x] 3.1 Refuse macro-bearing formats (`.xlsm`, `.docm`, `.pptm`) on sight, before any parsing.
- [x] 3.2 Refuse `.odb`, and restrict PDF to annotation and form-fill — never content rewriting.
- [x] 3.3 Determine type from content/MIME, never from the filename extension.

## 4. Verify

- [x] 4.1 Control pair on the formula guard: the same write is REFUSED without per-cell intent and SUCCEEDS with it, reporting the recalculated cells. Run both halves — a guard nobody has watched refuse is untested.
- [x] 4.2 Assert a dependent cell turning into `#REF!`/`#DIV/0!`/`#VALUE!` is reported rather than silently persisted.
- [x] 4.3 Spreadsheet round-trip fidelity on a file carrying named ranges, conditional formatting and a pivot table — all present and unchanged after a one-cell edit.
- [x] 4.4 Presentation round-trip fidelity on a deck carrying speaker notes, transitions and an embedded chart.
- [x] 4.5 Assert a macro-bearing file is refused before parsing, and that a mislabelled extension is judged on content.
- [x] 4.6 Assert a suite-specific type resolves ABSENT and visibly on a suite that lacks it, and that no other capability depends on it.
- [x] 4.7 Scoped `phpcs` clean; zero new PHPUnit failures vs a self-measured baseline; CHANGELOG entry recording the measured type matrix and the suite versions it was measured against.

## Acceptance criteria

- Spreadsheets and presentations are editable through the same WOPI session, lock discipline and ADR-088 marking as text documents — one session, three codecs, no duplicated safety machinery.
- A formula cannot be destroyed by an agent writing a literal, and any write reports what recalculated.
- Macro-bearing formats, `.odb`, and PDF content rewriting are all refused.
- The supported-type set is a measured, dated, suite-pinned declaration; an unprobed type is unsupported.
- A type editable on only one suite is published as suite-specific and is never the only path to a capability.

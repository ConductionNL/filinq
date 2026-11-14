---
kind: code
depends_on:
  - document-editing-tools
---

# Proposal: multi-format-editing-tools

## Why

`document-editing-tools` edits **text documents**. That is one third of what an
office suite is for. The corpora these deployments actually hold are budgets,
registers and calculations in spreadsheets, and council decks and briefings in
presentations — and an assistant that can revise a letter but not correct a figure
in the budget it refers to is a strange half-capability.

Extending to those formats is **not** "the same codec with more MIME types", and
that is the whole reason this is its own change. The addressing model — the thing
`document-editing-tools` spent its Phase 0 measuring — is fundamentally different
per type:

| Type | How an edit is addressed | Anchor stability |
|---|---|---|
| Text | block anchors (`w14:paraId`, `xml:id`) | **the open question** — may not survive a save |
| Spreadsheet | sheet name + cell address (`Budget!C14`) | **inherently stable** — an address is the identity |
| Presentation | slide id + shape/placeholder id | stable per object, but slide *order* is not |

Spreadsheets are therefore in one sense *easier* than text: there is no anchor
problem to solve, because a cell address is already a durable identity that a
human inserting a row updates for you. In another sense they are considerably more
dangerous, for a reason that has no analogue in text:

**A cell holds a formula, and a written value silently destroys it.** An agent
asked to "correct the total to 41,200" that writes `41200` into a cell containing
`=SUM(C2:C13)` has not corrected the total — it has removed the calculation, and
the sheet now shows a number that will never update again. Nothing renders
differently. Every dependent cell recalculates against the new literal, so the
damage propagates silently through the model. This is the spreadsheet equivalent
of the fluent-but-wrong transcript, and it needs the same treatment: make the
failure impossible rather than unlikely.

This change is **specified, not built**. It is written now so the format question
is settled before `document-editing-tools` hardens a codec shape that only suits
text.

## What Changes

- **Editing extends to spreadsheets and presentations**, addressed natively:
  spreadsheets by sheet + cell address, presentations by slide + shape. The text
  path's block anchors are not stretched to cover them.

- **A formula is never overwritten by accident.** Writing a literal into a cell
  that currently holds a formula is refused unless the caller states that intent
  explicitly. The response reports which cells recalculated as a result of any
  accepted write, because "what else changed?" is the question a spreadsheet edit
  raises and nothing else answers.

- **Macro-bearing formats are refused outright** — `.xlsm`, `.docm`, `.pptm`.
  An agent writing into a file that carries VBA is a code-execution vector wearing
  a document's clothes, and no use case here needs it.

- **The supported-type matrix is MEASURED per suite, not declared.** The suites
  genuinely differ — Collabora is LibreOffice, so it carries the full ODF set
  including Draw (`.odg`) and legacy `.doc`/`.xls`/`.ppt`; Euro-Office's lineage is
  OOXML-native with its own diagram and PDF editors; LibreOffice desktop has no
  server seam at all. Rather than hard-code a table that will be wrong within a
  release, the system probes what the *installed* suite actually accepts and
  publishes the result, exactly as `document-editing-tools` probes WOPI with a real
  `CheckFileInfo` rather than an app id.

- **ADR-087 §4 is enforced, not assumed.** A type editable on only one suite MUST
  NOT become the only path to a capability. If Draw editing works on Collabora and
  not on Euro-Office, the feature is available there and *absent* — visibly — here,
  and no workflow may depend on it.

- **PDF is annotation and form-fill only**, never content rewriting. A PDF is a
  final-form artefact; an agent editing its text is producing a forgery-shaped
  object with no version history.

## Capabilities

**Modified Capabilities**
- `document-editing` — extended from text documents to spreadsheets,
  presentations and the measured remainder of the installed suite's type set.

## Impact

- Extends `lib/Service/Editing/` with per-type codecs behind one interface; the
  WOPI session, lock discipline, `Version` precondition and ADR-088 marking from
  `document-editing-tools` are reused unchanged, because none of them is
  type-specific.
- No new OpenRegister schema; no seed data.
- **Not scheduled for implementation.** It depends on `document-editing-tools`
  clearing its Phase 0 gate, and its own Phase 0 is the type-support probe.

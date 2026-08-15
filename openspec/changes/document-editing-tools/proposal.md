---
kind: code
depends_on:
  - docudesk-mcp-adoption
---

# Proposal: document-editing-tools

## Why

`docudesk-mcp-adoption` gives an agent the ability to **find** a template and
**generate** a new document from register data (`docudesk.generateCorrespondence`).
`mcp-generation-tools` adds status and anonymisation. Nothing in either change —
or anywhere in DocuDesk — lets an agent act on a document that **already
exists**: convert it to another format, or change its content.

Those are the two operations users actually ask an assistant for once the first
document exists ("zet dit even om naar PDF", "werk de bedragen in deze brief
bij"), and DocuDesk already owns most of the machinery for the first one:
`lib/Service/Conversion/` holds a complete backend cascade behind
`ConversionBackendInterface` — `OfficeAppBackend` (Nextcloud's
`IConversionManager`, NC 31+) → `LibreOfficeHeadlessBackend` → `PhpWordBackend`
→ `MpdfBackend`, plus an Eml slot. It is reachable from DocuDesk's own UI and
from ADR-075's channel, but **not from an agent**.

The second operation has no machinery at all, and it is where the fleet's
office-suite problem actually lives. **ADR-087** settles the shape: conversion
brokers through `IConversionManager` (no per-suite driver), format manipulation
is one suite-independent codec, editing sessions use WOPI and only WOPI, and
live in-editor streaming (Collabora's `postMessage` / `Send_UNO_Command`) is an
explicitly non-portable enhancement that this change does **not** build.

The governance posture is inherited, not reinvented: `docudesk-mcp-adoption`
established a narrow, read-biased surface with standing refusals (no signing, no
batch mail-merge, no entity values, no signature material). This change extends
that surface with two curated tools and contradicts none of it.

## What Changes

- **One curated conversion tool** `docudesk.convertDocument` — a genuinely
  non-CRUD action on a real service method wrapping the existing
  `ConversionBackendInterface` cascade. Converts one file the acting user can
  read into a requested target format, writing the **result as a new file**.
  Annotated honestly: `scope: 'create'`, `readOnlyHint: false`,
  `destructiveHint: false`, `idempotentHint: false`.

- **One curated editing tool** `docudesk.editDocument` — opens a WOPI session
  against the instance's WOPI host, applies anchored block edits to the document
  package, and writes the result back into the source file (or to a sibling, on
  request). Annotated honestly for what it now does: `scope: 'update'`,
  `readOnlyHint: false`, **`destructiveHint: true`**, `idempotentHint: false` —
  a tool that modifies a user's existing file in place is destructive, and
  declaring it as such is what keeps it default-denied and approval-gated.

- **Output mode is configurable, defaulting to in-place.** By default an edit
  writes back into the source file via `PutFile`, so Nextcloud file versioning
  holds the undo and the document keeps one identity and one history. A
  sibling-file mode (`PutRelativeFile`, source untouched) is selectable for
  callers who want the original guaranteed intact.

- **In-place needs three guards, because `PutFile` has no merge semantics.** It
  replaces the whole file, so an agent write would otherwise land on top of
  anything changed since `GetFile`. Mitigated by: holding the WOPI lock across the
  whole read-modify-write; **re-checking `CheckFileInfo`'s `Version` immediately
  before `PutFile` and refusing if it moved** — optimistic concurrency using what
  the protocol already offers; and the ADR-088 tag, which makes the change visible
  in Files rather than something the user must notice by reading. Nextcloud file
  versioning is the recovery path, and that it actually captures a version on a
  WOPI `PutFile` is **verified, not assumed**.

- **Configuration sets the ceiling; a tool argument may only be more
  conservative.** An agent can request sibling output when the configuration says
  in-place, but never the reverse — an agent cannot escalate its own blast radius.

- **Both tools are suite-independent** per ADR-087. No
  `{Collabora,LibreOffice,EuroOffice}` backend triad is added. WOPI availability
  is probed with a real `CheckFileInfo`, never inferred from an installed app id
  — Euro-Office ships `wopi.enable` false by default.

- **The document codec edits XML in place inside the package**, leaving untouched
  parts byte-identical, so comments, tracked changes, styles, headers and
  embedded objects survive because they are never re-serialised.

- **Every produced document is tagged and recorded** (ADR-088). A Nextcloud system
  tag is applied at write time by the same code path that writes the file, so a
  user sees in Files that an agent authored or changed it without consulting an
  audit page; and the write is recorded by Hermiq with the file id, so an operator
  reviewing the agent can follow the record to the artefact. If the tag cannot be
  applied the operation reports failure rather than returning success — an unmarked
  document reported as marked is the one outcome nothing downstream will question.

- **Standing refusals extended** (design.md §Refusals): no editing of a document
  with an open or completed `signingRequest`; no editing of anonymisation output;
  no lock stealing; no attachment or signature bytes in any tool response; no
  live in-editor streaming in this change.

- **Grant-surface correctness**: both tools carry ADR-063 descriptor hints so
  Hermiq's `ToolGrantResolver` classifies them correctly and they appear, with
  the right write classification, in the agent detail page's Tool governance
  grant editor. Per `hermiq-prefer-tool-hints`, a hint-less two-segment id fails
  **closed** — so the hints are load-bearing, not documentation.

## Capabilities

**New Capabilities**
- `document-editing` — DocuDesk's suite-independent conversion and editing of
  existing documents.

**Modified Capabilities**
- `docudesk-mcp-surface` — extended with the two curated tools above, bound by
  the standing refusals already declared there.

## Impact

- New: `lib/Service/Editing/` (WOPI client, session manager, document codec),
  two `#[McpTool]` methods, `DocudeskScannableServices` extended.
- Blocked on two unknowns recorded in ADR-087 and re-stated in design.md
  §Verification: `w14:paraId` survival across a Collabora save, and headless WOPI
  token issuance. Both are measured in Phase 0 tasks before any editing code is
  written.
- No new OpenRegister schema, therefore no seed data.

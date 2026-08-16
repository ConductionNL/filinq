---
kind: code
---

## Why

Asked to change a word in the document on screen, the agent answers:

> The change couldn't be applied because subsidiebesluit.docx is currently open
> for editing in eurooffice, which locks it. Close the document, then I'll retry.

**"Close the document you are working in" is not an answer.** The overwhelmingly
common case for a document assistant is precisely that the document is open — that
is why the user is looking at it, and why the companion is on that page at all. A
correct refusal that is useless in the normal case reads as a broken feature.

## The refusal is right, and that is the problem

It would be easy to read this as an over-cautious lock and remove it. It is not.
`EditSessionService` documents the guard in detail: an open editor holds the
authoritative in-memory copy, so a write underneath it is **discarded by the
editor's next save**. Forcing the write produces a change the user watches
disappear — strictly worse than being told no, and much harder to diagnose.

So the fix cannot be "take the lock anyway". It has to be: **stop writing around
the editor, and write through it.**

## What Changes

- **When a document is open, the edit is applied by the editor**, not by the file
  layer. The change appears in the document the user is looking at, and the editor
  saves it the way it saves any other edit — so there is no second writer and no
  lock to contest.
- **The companion already runs on the editor's page**, so it can address the
  editor directly through that suite's own client API. The agent computes the
  edit; the page applies it.
- **The saved file is made current before the agent reads it.** Both suites can be
  told to save; an edit computed against the stored bytes while the editor holds
  unsaved changes would target text that is no longer there.
- **The direct file write stays** for the case it was always right for: no editor
  open. That path is unchanged, lock and all.

## Capabilities

### New Capabilities
- `editor-mediated-editing`: how an agent edit reaches a document that is open,
  and what must be true before it is applied.

## Impact

- **Code**: docudesk — an editor-session probe, a force-save step, and a per-suite
  adapter for delivering an edit.
- **UI**: `@conduction/nextcloud-vue` — the companion relays the edit to the editor
  on the page it is mounted on.
- **⚠️ ADR-087**: this is the one place suite divergence is unavoidable, because
  each document server exposes a different client API. §5 bans a per-suite
  DEPENDENCY; this needs a per-suite ADAPTER behind one interface, with the
  document manipulation itself staying suite-independent. That distinction must be
  argued in review, not assumed.
- **Not in scope**: collaborative conflict resolution. One agent edit at a time,
  applied to a document whose state has just been made current.

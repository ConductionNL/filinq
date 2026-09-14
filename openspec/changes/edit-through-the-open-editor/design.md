## Context

Measured 2026-08-16 on the Euro-Office editor, with the Claude Document Agent and
the page context now reaching it correctly:

- The agent identified `subsidiebesluit.docx` unaided, found the single occurrence
  of "Zaanstad", and proposed the exact replacement.
- It then refused to write, because the file is locked by the open editor.
- The refusal is correct. `EditSessionService` takes an `ILockManager` lock under
  the owner `docudesk`, which conflicts with the editor's own — deliberately, and
  for a documented reason: the editor holds the authoritative copy, so a write
  underneath it is discarded on its next save.

So the agent is right, the lock is right, and the user is still stuck.

## Goals / Non-Goals

**Goals:**
- An agent edit lands in a document that is open, visibly, without closing it.
- The editor remains the only writer while it is open.
- The agent reasons about the same bytes the user is looking at.

**Non-Goals:**
- Removing or weakening the lock. It is load-bearing (see D1).
- Multi-user conflict resolution.
- Editing while an editor is open that exposes no client API — that case keeps the
  honest refusal.

## Decisions

### D1 — Do NOT take the lock. The lock is not the obstacle; the second writer is.

The tempting fix is to make `docudesk`'s lock compatible with the editor's, or to
break it. Both produce the same outcome: two writers, the editor's in-memory copy
wins on its next save, and the user's change silently disappears.

⚠️ That failure is worse than the refusal in the specific way this codebase keeps
getting caught by: **it looks like success.** The agent reports the edit applied,
the file on disk briefly contains it, and then it is gone. Nothing errors.

### D2 — The edit is delivered by the page, because the page is already there

The companion is mounted on the editor's own page (Hermiq's always-on bundle), in
the same document as the editor's iframe. That is the one place with a live
handle on the running editor.

So: the agent computes WHAT to change, returns it as a described edit, and the
browser applies it through the editor's client API. The file layer is not
involved, no lock is contested, and the change appears in front of the user as an
ordinary edit they can undo.

### D3 — Make the saved file current BEFORE the agent reads it

The agent's anchors are content hashes over the STORED bytes. An editor with
unsaved changes means the stored bytes are stale, so an edit computed from them
can target text that no longer exists — and anchor-based replacement would either
miss or hit the wrong paragraph.

Both suites can be asked to flush (ONLYOFFICE/Euro-Office `forcesave`; Collabora
`Action_Save`). The sequence is: force save → read → compute → deliver.

⚠️ This is a race, not a guarantee. The user can type between the flush and the
delivery. The edit MUST therefore be expressed as a find-and-replace of specific
text, not as an absolute offset, so that late typing elsewhere cannot corrupt it —
and a target that no longer matches MUST fail loudly rather than land near it.

### D4 — One interface, per-suite adapters — and why that is not an ADR-087 violation

ADR-087 §5 bans a per-suite dependency, and this change needs per-suite code:
ONLYOFFICE and Euro-Office expose a `docEditor` connector object; Collabora uses
`postMessage`. There is no common client API to program against.

The distinction the review must hold:
- **Manipulation stays suite-independent.** What text changes, and where, is
  computed once from the package (the existing anchor logic). No suite sees it.
- **Delivery is a thin adapter**: "apply this replacement" per suite, one small
  implementation each, behind one interface.
- **An unknown suite degrades to the existing refusal**, rather than to a guess.

If an adapter starts computing edits, the boundary has been crossed and §5 applies.

### D5 — The refusal survives, narrowed

No editor open → the current in-process path, unchanged, lock and all. Editor open
but no adapter → refuse, as today, but say which suite and that the direct path
would work if the document were closed.

The refusal stops being the normal case and becomes the honest edge case.

## Seed Data (ADR-001)

None. This changes how an edit is delivered, not what is stored.

## Declarative-vs-imperative decision (ADR-031)

| Behaviour | Path | Rationale |
|---|---|---|
| Which delivery route applies | **Imperative** | Depends on live runtime state — is an editor attached, which suite, does it answer. |
| The edit itself | **Imperative, existing** | Package manipulation; unchanged by this proposal. |
| Per-suite adapter registry | **Declarative** | Suites are declared, like the existing office-suite capability registry, so adding one is configuration rather than a branch. |

## Risks / Trade-offs

**The delivered edit is not the audited edit.** The file path writes a
`generatedDocument` audit row; an editor-delivered change is saved by the editor
under the user's own account. The audit trail must record that an agent proposed
it, or agent edits become invisible the moment they go through this route. Worth
stating now because it is easy to notice only after the feature ships.

**Force-save changes the user's document.** Flushing commits whatever they had
typed. That is almost always what they want, and it is still a side effect the
agent caused without being asked — it belongs in what the agent says it is about
to do.

**Suite APIs drift.** A per-suite adapter is a hostage to someone else's client
API. Mitigated by keeping each adapter to a single operation, and by degrading to
the refusal rather than to a wrong edit.

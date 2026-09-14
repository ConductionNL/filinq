# Tasks

## 1. Know whether an editor holds the document

- [ ] Detect an attached editor session and which suite it is
- [ ] Route to editor-delivery when one is attached, to the existing file path when not

Acceptance criteria:
- The existing in-process path, lock included, MUST be untouched for the no-editor case. It was always correct there.
- ⚠️ Do NOT make `docudesk`'s lock compatible with the editor's, and do not break it. The lock is not the obstacle — the second writer is. `EditSessionService` documents why: the editor holds the authoritative copy, so a write underneath it is discarded on its next save, which LOOKS LIKE SUCCESS (agent reports done, bytes briefly correct, change gone).

## 2. Make the document current before reading it

- [ ] Ask the editor to flush unsaved state, then read
- [ ] Have the agent announce the save before it happens

Acceptance criteria:
- Anchors hash the STORED bytes; with unsaved changes pending they describe text that is no longer there.
- Force-saving commits whatever the user had typed — a side effect the agent caused unasked, so it must be stated first.

## 3. Deliver the edit through the editor

- [ ] Define one delivery interface; implement an adapter per suite
- [ ] Express edits as text-to-match, never as offsets
- [ ] Fail loudly and specifically when the target text is not found
- [ ] Relay the edit from the companion, which already runs on the editor's page

Acceptance criteria:
- ⚠️ ADR-087 §5 bans a per-suite DEPENDENCY. Divergence is unavoidable here (connector object vs `postMessage`, no common API), so the line is: no adapter decides WHAT changes. An adapter that computes an edit has crossed it — assert the manipulation code contains no suite reference.
- The user may type between the flush and the delivery. Content-addressed edits survive that; positional ones corrupt the document silently.
- An unknown suite refuses and names itself, rather than guessing.

## 4. Keep the agent attributable

- [ ] Record agent authorship for editor-delivered edits

Acceptance criteria:
- The direct path writes a `generatedDocument` row; an editor-delivered change is saved by the editor under the USER's account, so authorship vanishes exactly when this becomes the normal route.

## 5. Verify in the situation it exists for

- [ ] Test with the document OPEN in each suite that has an adapter
- [ ] Test the no-editor path still works unchanged
- [ ] Test an unsaved-changes document: flush, edit, and confirm the user's typing survived

Acceptance criteria:
- ⚠️ Verifying only with the document closed tests the path this change is not about. The measured failure — "close the document, then I'll retry" — happened with it open.
- Confirm the change is visible IN the editor, not merely present in the stored file.

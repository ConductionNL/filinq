## ADDED Requirements

### Requirement: An agent edit MUST reach a document that is open for editing

When the target document is open in an office editor, the edit MUST be applied
through that editor, and MUST NOT require the user to close the document.

The document being open is the NORMAL case for a document assistant — it is why
the user is looking at it and why the companion is on that page. A capability that
works only when the document is closed is not available in the situation it exists
for.

#### Scenario: The document is open and the edit lands

- **GIVEN** a document open in an office editor
- **AND** an agent asked to replace a phrase in it
- **WHEN** the edit is applied
- **THEN** the change MUST appear in the open document
- **AND** the user MUST NOT be asked to close it first

#### Scenario: No editor open keeps the direct path

- **GIVEN** a document that is not open anywhere
- **WHEN** an agent edits it
- **THEN** the existing in-process file edit MUST be used, lock included

### Requirement: The editor MUST remain the only writer while it is open

The system MUST NOT write to the file, take the editor's lock, or bypass it while
an editor session holds the document.

⚠️ The existing refusal is a data-loss guard, not caution. An open editor holds the
authoritative in-memory copy, so a write underneath it is **discarded by the
editor's next save** — the agent reports success, the bytes are briefly correct,
and then the change is gone. That failure mode looks exactly like success, which is
why it must remain impossible rather than merely discouraged.

#### Scenario: The file layer is not used while an editor is attached

- **GIVEN** an editor session holding the document
- **WHEN** an agent edit is applied
- **THEN** the change MUST be delivered through the editor
- **AND** no direct file write MUST occur for that edit

#### Scenario: The lock is never taken from the editor

- **GIVEN** a document locked by an open editor
- **WHEN** an agent edit is applied
- **THEN** the editor's lock MUST NOT be broken, replaced or extended by the agent

### Requirement: The stored document MUST be made current before the agent reads it

Before computing an edit against a document that is open, the editor MUST be asked
to flush its unsaved state, and the agent MUST read the document after that flush.

Anchors are content hashes over the stored bytes. With unsaved changes pending,
those bytes are stale, so an edit computed from them can target text that is no
longer present — and anchored replacement then misses, or worse, matches the wrong
paragraph.

#### Scenario: Unsaved changes are flushed first

- **GIVEN** a document with unsaved changes in the editor
- **WHEN** an agent is asked to edit it
- **THEN** the editor MUST be asked to save before the document is read
- **AND** the edit MUST be computed from the post-flush content

#### Scenario: A stale target fails loudly

- **GIVEN** an edit whose target text no longer matches the document
- **WHEN** delivery is attempted
- **THEN** it MUST fail with a message naming the text it could not find
- **AND** MUST NOT apply the change at an approximate location

### Requirement: An edit MUST be expressed as text to match, never as an offset

A delivered edit MUST identify its target by content. Absolute positions MUST NOT
be used.

Between the flush and the delivery the user may keep typing. Content-addressed
edits survive unrelated typing; positional ones silently corrupt the document when
anything earlier changes length.

#### Scenario: Typing elsewhere does not misplace the edit

- **GIVEN** an edit prepared for a document
- **AND** the user types in an unrelated paragraph before it is applied
- **WHEN** the edit is delivered
- **THEN** it MUST still replace the intended text

### Requirement: Suite-specific code MUST be confined to delivery

Computing an edit MUST remain suite-independent. Only DELIVERY may vary per suite,
behind one interface, and a suite with no adapter MUST degrade to a refusal.

⚠️ ADR-087 §5 bans a per-suite dependency. This is the one place divergence is
unavoidable — ONLYOFFICE and Euro-Office expose a connector object, Collabora uses
`postMessage`, and there is no common client API. The line that keeps §5 satisfied
is that no adapter decides WHAT changes: an adapter that starts computing edits has
crossed it.

#### Scenario: Manipulation logic sees no suite

- **GIVEN** the code that computes a document edit
- **WHEN** it is reviewed
- **THEN** it MUST contain no reference to any office suite

#### Scenario: An unknown suite refuses rather than guesses

- **GIVEN** a document open in a suite with no delivery adapter
- **WHEN** an agent edit is attempted
- **THEN** it MUST refuse
- **AND** the refusal MUST name the suite and say the direct path would work with the document closed

### Requirement: An agent-proposed edit MUST remain attributable after the editor saves it

When an edit is delivered through an editor, the audit record MUST still show that
an agent proposed it.

The direct path writes a `generatedDocument` audit row. An editor-delivered change
is saved by the editor under the user's own account, so without this the agent's
authorship disappears exactly when the feature becomes the normal route.

#### Scenario: Editor-delivered edits are audited

- **GIVEN** an agent edit delivered through an open editor
- **WHEN** the audit trail is read
- **THEN** it MUST record that an agent proposed the change, and which agent

### Requirement: The agent MUST say that it is going to save the user's document

Before flushing an editor's unsaved state, the agent MUST state that it will do so.

Force-saving commits whatever the user had typed. That is usually welcome and it is
still a side effect the agent caused without being asked for it.

#### Scenario: The flush is announced

- **GIVEN** a document with unsaved changes
- **WHEN** the agent prepares to edit it
- **THEN** it MUST tell the user their document will be saved first

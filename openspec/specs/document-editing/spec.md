---
status: in-progress
---

# document-editing Specification

**OpenSpec changes**
- agent-document-editing
- document-rich-editing

## Purpose

Defines how an agent reads and changes the **text** of a Word (`.docx`) or
OpenDocument (`.odt`) file: how paragraphs are addressed, what an edit is refused
for, and what must remain true of the package afterwards.

Style, layout and metadata are a separate capability — see
`openspec/specs/document-rich-editing/spec.md`.

The governing property is that editing happens **in place, inside the package**.
Only the targeted paragraph's byte range is rewritten; every other part stays
byte-identical. ADR-087 §2 prefers this over parse-and-re-serialise because
re-serialisation silently drops comments, tracked changes and styles that no test
asserts on.

## Requirements

### Requirement: Edits address stable anchors, never positional indexes

A read returns one block per paragraph, each with an `anchor` derived from that
paragraph's own content (`b<sha1[0:8]>-<ordinal>`), recomputed on every read.
Anchors MUST NOT be positional: a human inserting a paragraph shifts every index,
and an index-addressed edit would then land on the wrong paragraph with no error.

An anchor that no longer resolves MUST fail the **whole** edit set. A partially
applied set leaves a document in a state neither the user nor the agent asked for,
and no caller can tell which half landed.

Edits MUST be applied from the last matched span backwards, so an earlier rewrite
cannot invalidate the byte offsets of spans still to be edited.

#### Scenario: A stale anchor takes the whole edit set with it

- **GIVEN** an edit set of three edits, one of whose anchors no longer resolves
- **WHEN** the set is applied
- **THEN** the whole set MUST be refused
- **AND** the document MUST be unchanged

#### Scenario: An anchor is stable across an unrelated edit

- **GIVEN** two paragraphs with distinct text
- **WHEN** the first is replaced
- **THEN** the second's anchor MUST be unchanged

### Requirement: Untouched parts of a document package survive an edit unchanged

Applying an edit MUST rewrite only the body part, and only the targeted paragraph's
span within it. Every other package entry MUST be byte-identical afterwards,
including ODF's uncompressed leading `mimetype` entry.

#### Scenario: Unrelated package parts are byte-identical after an edit

- **GIVEN** a package carrying comments and styles alongside the body
- **WHEN** one paragraph is replaced
- **THEN** every entry other than the body part MUST be byte-identical

### Requirement: An edit is refused when the document moved since it was read

A read returns a `version`. An edit MUST supply it, and the version MUST be
re-checked immediately before the write — not merely at the start of the session.
The file lock excludes another editing *session*; the re-check closes the remaining
window in which the file changed outside one.

A mismatch MUST refuse. This codec cannot merge, and guessing would be worse than
stopping.

#### Scenario: A stale version refuses the edit

- **GIVEN** a document that changed after it was read
- **WHEN** an edit is applied with the earlier version
- **THEN** the edit MUST be refused and nothing written

### Requirement: A format the codec cannot address is refused by name

Only `.docx` and `.odt` are addressable. Spreadsheets and presentations are
deliberately absent: their block model is a cell or a slide, not a paragraph, and
giving them a paragraph anchor would produce anchors resolving to nothing.

A refusal MUST name the offending format **and** name the formats that do work.

#### Scenario: An unsupported format names what is supported

- **GIVEN** an `.xlsx` file
- **WHEN** a read is attempted
- **THEN** the system MUST refuse, naming `.xlsx` and listing `docx, odt`

### Requirement: Editing session availability is probed, never inferred

The set of addressable formats MUST come from the codec itself rather than from a
hand-written list, so a format the codec cannot actually handle can never be
advertised as available.

#### Scenario: The advertised formats are the codec's own

- **GIVEN** the codec supports a set of extensions
- **WHEN** availability is reported
- **THEN** the reported set MUST be exactly the codec's own supported set

### Requirement: Conversion routes through the Nextcloud conversion broker

PDF conversion MUST dispatch through `IConversionManager` with a declared fallback
cascade, and MUST report which backend claimed the conversion — an office-app
conversion and the built-in fallback differ visibly in fidelity, and a caller that
cannot tell them apart cannot judge the output.

The backend MUST NOT be selectable by the caller, so the tool cannot be steered onto
a particular process.

#### Scenario: The producing backend is reported

- **GIVEN** a successful conversion
- **WHEN** the result is returned
- **THEN** it MUST name the backend that produced the PDF

### Requirement: Every produced file is marked as agent authored at write time

A file an agent wrote MUST carry the `Agent authored` tag, applied **before** the
bytes become visible and rolled back if the write then fails. Neither an unmarked
agent artefact nor a mark on an unchanged file may survive a failed write (ADR-088).

Marking after the write would leave a window in which an agent artefact is visible
and indistinguishable from a human's file.

#### Scenario: A failed write leaves no mark

- **GIVEN** a tagged file whose write then fails
- **WHEN** the failure is handled
- **THEN** the tag MUST be removed

#### Scenario: The mark precedes visibility

- **GIVEN** a successful agent write
- **WHEN** the file becomes visible
- **THEN** it MUST already carry the tag

### Requirement: Editing writes in place by default, with a recoverable prior version

An edit MUST write into the source file by default, relying on Nextcloud file
versions to keep the prior content recoverable. Writing a sibling by default would
leave the user's own file untouched and silently produce a second document they did
not ask for.

#### Scenario: An in-place edit leaves the prior version restorable

- **GIVEN** a document edited in place
- **WHEN** the file's versions are inspected
- **THEN** the pre-edit content MUST be restorable

### Requirement: Output mode may be narrowed by a caller, but never widened

The configured output mode is a **ceiling**. A caller may ask for a more
conservative mode (a sibling file instead of an in-place write) but MUST NOT be able
to obtain a less conservative one than the instance permits.

An argument that could widen the mode would let a caller overwrite files on an
instance whose administrator chose that it should not.

#### Scenario: A caller may narrow to a sibling write

- **GIVEN** an instance configured to allow in-place writes
- **WHEN** a caller requests sibling output
- **THEN** a sibling file MUST be written and the source left untouched

#### Scenario: A caller cannot widen to in-place

- **GIVEN** an instance configured for sibling output only
- **WHEN** a caller requests an in-place write
- **THEN** the request MUST NOT produce an in-place write

### Requirement: An in-place write is guarded by the lock and a version precondition

An in-place write MUST hold a file lock for the whole read-modify-write, and MUST
re-read the version immediately before writing. The lock excludes another editing
session; the precondition closes the remaining window in which the file changed
outside one. Both are required — neither alone is sufficient.

The lock MUST be released on every exit path, including failure.

#### Scenario: The lock is released when the write throws

- **GIVEN** a write that fails midway
- **WHEN** the failure propagates
- **THEN** the lock MUST have been released

### Requirement: Lock contention is refused, not waited out

When the file is already locked, the system MUST refuse immediately and say so. It
MUST NOT block, retry or wait.

Waiting would make an agent call hang for as long as a human keeps a document open,
and the caller cannot distinguish a slow call from a stuck one.

When no lock provider is available on the instance, the write MUST still proceed
under the version precondition, and the response MUST carry a warning saying a
concurrent session could not be excluded — reporting nothing would present a weaker
guarantee as the full one.

#### Scenario: A locked document is refused at once

- **GIVEN** a document locked by another session
- **WHEN** an edit is attempted
- **THEN** it MUST be refused immediately, naming the contention

#### Scenario: A missing lock provider is reported, not hidden

- **GIVEN** an instance with no file-lock provider
- **WHEN** an edit succeeds under the version precondition alone
- **THEN** the response MUST carry a warning that a concurrent session could not be excluded

### Requirement: Documents under signature or produced by anonymisation are not editable

A document that is the subject of a signing request MUST NOT be editable — editing
it would invalidate what a signatory agreed to, silently.

A document produced by anonymisation MUST NOT be editable either, because an edit
can reintroduce identifying text into an artefact whose purpose is that it contains
none.

The signature refusal MUST fail **closed** when the register cannot be reached: an
unreachable register is not evidence that a document is unsigned. The anonymisation
refusal MAY fail open, because its artefacts are identifiable from the file itself.

#### Scenario: An unreachable signing register refuses the edit

- **GIVEN** the signing register cannot be reached
- **WHEN** an edit is attempted
- **THEN** the edit MUST be refused, because absence of evidence is not evidence of absence

#### Scenario: Anonymisation output is refused

- **GIVEN** a document produced by anonymisation
- **WHEN** an edit is attempted
- **THEN** the edit MUST be refused, naming the reason

### Requirement: Every produced file is recorded with its identity and without its content

Each file an agent produces MUST be recorded by id, name and path. The record MUST
NOT contain the document's bytes or its text.

The record exists so a person can find and audit what an agent did; copying content
into it would turn an audit trail into a second, unguarded copy of the document.

#### Scenario: The record names the file without carrying it

- **GIVEN** a file produced by an agent
- **WHEN** the record is written
- **THEN** it MUST carry the file's id, name and path
- **AND** MUST NOT carry the document's bytes or extracted text

### Requirement: No document, attachment or signature bytes leave through this capability

The tools return text and metadata only. Document bytes, attachment bytes and
signature material MUST NOT be returned to a caller through any tool in this
capability.

An agent that could retrieve raw bytes could exfiltrate a document whose text it was
only ever meant to read.

#### Scenario: A read returns text, never bytes

- **GIVEN** a document read through the agent surface
- **WHEN** the result is returned
- **THEN** it MUST contain the paragraph text and anchors
- **AND** MUST NOT contain the file's bytes in any encoding

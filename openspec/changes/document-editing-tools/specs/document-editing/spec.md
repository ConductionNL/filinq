# document-editing

DocuDesk's suite-independent handling of documents that already exist: format
conversion through Nextcloud's conversion broker, and content editing through a
WOPI session that always writes a new version. Governed by ADR-087
(office-suite divergence is brokered, not driven) and ADR-075 (DocuDesk owns
document generation, one channel).

## ADDED Requirements

### Requirement: Conversion routes through the Nextcloud conversion broker
The system MUST perform format conversion through the existing
`ConversionBackendInterface` cascade, whose highest-priority backend dispatches
via `OCP\Files\Conversion\IConversionManager`. The system MUST NOT add a
per-office-suite conversion backend for any conversion the conversion manager
brokers, and MUST NOT branch on an office app's id to select a conversion path.

#### Scenario: An office app is installed and claims the conversion
- **GIVEN** an office app is registered as a conversion provider with `IConversionManager`
- **WHEN** a document is converted
- **THEN** the conversion MUST be dispatched through `IConversionManager`
- **AND** the result MUST report which backend claimed the conversion

#### Scenario: No conversion provider is registered
- **GIVEN** no office app is registered as a conversion provider
- **WHEN** a document is converted
- **THEN** the cascade MUST fall through to the built-in backends
- **AND** the result MUST report the backend that produced it, so a lower-fidelity
  fallback is never reported as an office-app conversion

#### Scenario: No backend claims the source format
- **WHEN** no backend in the cascade claims the source MIME → target format pair
- **THEN** the system MUST return a structured error naming the source format
- **AND** the system MUST NOT return a partial or substituted result

### Requirement: Editing session availability is probed, never inferred
The system MUST determine WOPI availability by performing a real `CheckFileInfo`
request against the configured WOPI host. The system MUST NOT infer availability
from the presence of an installed office app, because a supported suite may ship
with its WOPI interface disabled by default.

#### Scenario: No WOPI host is reachable
- **WHEN** the `CheckFileInfo` probe fails
- **THEN** the editing capability MUST resolve as absent
- **AND** the absence MUST be surfaced visibly to the caller
- **AND** the system MUST NOT report a successful edit

#### Scenario: An office app is installed but its WOPI interface is disabled
- **GIVEN** an office app is installed whose WOPI interface is turned off
- **WHEN** availability is determined
- **THEN** the capability MUST resolve as absent, exactly as if no office app were installed

### Requirement: Editing writes in place by default, with a recoverable prior version
The system MUST support two output modes: writing back into the source file, which
is the default, and writing a sibling file that leaves the source untouched. After
an in-place edit a restorable prior version of the file MUST exist.

#### Scenario: A document is edited in the default mode
- **WHEN** an edit completes successfully in the default mode
- **THEN** the source file MUST contain the edit
- **AND** a prior version of the file MUST be restorable

#### Scenario: A caller requests sibling output
- **WHEN** an edit is requested in sibling mode
- **THEN** a new file MUST be created
- **AND** the source file's bytes MUST be unchanged

### Requirement: An in-place write is guarded by the lock and a version precondition
The system MUST hold its own WOPI lock across the whole read-modify-write, and MUST
re-read `CheckFileInfo` immediately before writing and refuse the write if the
file's version has changed since it was read. The system MUST NOT attempt to merge.

#### Scenario: The document changed between read and write
- **GIVEN** a document was read at the start of an edit
- **AND** the document changed before the edit was written
- **THEN** the system MUST refuse the write
- **AND** the change made by the other writer MUST remain intact
- **AND** the system MUST NOT attempt to merge the two versions

#### Scenario: The lock was not held for the whole session
- **WHEN** a write is attempted without the session's own lock held continuously
  since the read
- **THEN** the system MUST refuse the write

### Requirement: Output mode may be narrowed by a caller but never widened
Configuration MUST determine the most permissive output mode available. A tool
argument MAY select the sibling mode when configuration permits in-place, and MUST
NOT select in-place when configuration permits only sibling.

#### Scenario: An agent requests in-place output against a sibling-only configuration
- **WHEN** an edit requests in-place output on an instance configured for sibling output
- **THEN** the system MUST refuse or produce sibling output
- **AND** the source file MUST NOT be modified

### Requirement: Lock contention is refused, not waited out
The system MUST hold the WOPI lock inside the editing service for the whole
session and MUST release it on every exit path, including timeout and error. When
the document is already locked, the system MUST return a structured refusal. The
system MUST NOT poll, queue, retry, or acquire a lock it does not already hold via
`UnlockAndRelock`.

#### Scenario: The document is open in an editor
- **GIVEN** a document is locked by an active editing session
- **WHEN** an edit is requested
- **THEN** the system MUST return a structured refusal naming the condition
- **AND** the system MUST NOT take over or break the existing lock

#### Scenario: An edit fails partway through
- **WHEN** an edit raises an error after the lock was acquired
- **THEN** the lock MUST be released before the call returns
- **AND** the document MUST NOT be left locked

### Requirement: Edits address stable anchors, never positional indexes
The system MUST address edits to stable block anchors. The system MUST NOT
address edits by array index or byte offset into the document.

#### Scenario: A paragraph was inserted before the targeted block
- **GIVEN** an edit targets a block
- **AND** a paragraph was inserted before that block since the anchors were computed
- **THEN** the edit MUST still apply to the intended block, or MUST be refused
- **AND** the edit MUST NOT be applied to a different block

#### Scenario: An anchor can no longer be resolved
- **WHEN** an edit's anchor cannot be resolved in the current document
- **THEN** the system MUST refuse that edit with a structured error
- **AND** the system MUST NOT guess a nearby block

### Requirement: Untouched parts of a document package survive an edit unchanged
The system MUST apply edits by mutating only the targeted nodes and rewriting only
the affected package part, leaving every other entry in the document package
byte-identical. The system MUST NOT parse a document to an intermediate model and
re-serialise the whole package.

#### Scenario: A document carrying comments and tracked changes is edited
- **GIVEN** a document containing comments, tracked changes, a header and embedded objects
- **WHEN** one paragraph is edited
- **THEN** the comments, tracked changes, header and embedded objects MUST be present
  and unchanged in the result

### Requirement: Documents under signature or produced by anonymisation are not editable
The system MUST refuse to edit a file referenced by a `signingRequest` in any state
other than cancelled, and MUST refuse to edit anonymisation output.

#### Scenario: An agent targets a document in a signature process
- **WHEN** an edit targets a file referenced by an active or completed `signingRequest`
- **THEN** the system MUST refuse the edit
- **AND** the refusal MUST name the signature process as the reason

#### Scenario: An agent targets a redacted document
- **WHEN** an edit targets a document produced by the anonymisation pipeline
- **THEN** the system MUST refuse the edit

### Requirement: No document, attachment or signature bytes leave through this capability
No response from the conversion or editing capability may contain document bytes,
attachment bytes, or signature material. Responses MUST carry file identifiers,
metadata and structured status only.

#### Scenario: A conversion or edit completes
- **WHEN** the operation returns
- **THEN** the response MUST reference the produced file by identifier
- **AND** the response MUST NOT embed the file's bytes

### Requirement: Every produced file is marked as agent-authored at write time
The system MUST apply a Nextcloud system tag identifying the file as agent-authored,
in the same operation that writes the file. The system MUST NOT defer marking to a
later pass. If the file is written but the mark cannot be applied, the operation
MUST report failure.

#### Scenario: A document is converted or edited
- **WHEN** either tool produces a file
- **THEN** the file MUST carry the agent-authored system tag from the moment it is
  visible to the user
- **AND** the tag MUST be visible in the Files interface

#### Scenario: The mark cannot be applied
- **GIVEN** the file was written
- **WHEN** applying the tag fails
- **THEN** the operation MUST report failure
- **AND** the operation MUST NOT report success

#### Scenario: A user removes the tag
- **WHEN** a user removes the agent-authored tag from a file
- **THEN** the system MUST NOT re-apply it
- **AND** the authoritative record of the agent's write MUST remain in the
  invocation record regardless

### Requirement: Every produced file is recorded with its identity, and without its content
The system MUST record each write with the identity of the produced file and the
acting agent, so an operator reviewing an agent can locate what it changed. The
record MUST NOT contain document content.

#### Scenario: An operator reviews an agent's document activity
- **WHEN** the agent's invocation records are reviewed
- **THEN** each write MUST identify the file it produced and the agent that produced it
- **AND** the record MUST NOT contain the document's contents

### Requirement: Live in-editor manipulation is out of scope and non-portable when added
The system MUST NOT make any capability reachable only through a single office
suite's in-editor manipulation API. If live in-editor manipulation is added later,
it MUST sit behind a capability probe, MUST be documented as suite-specific, and
every capability it offers MUST also be reachable through the suite-independent
path.

#### Scenario: A capability is offered on one suite only
- **WHEN** a capability is implemented against one office suite's in-editor API
- **THEN** an equivalent capability MUST exist through the suite-independent path
- **AND** an instance running a different supported suite MUST NOT lose the capability

# document-editing

Delta: editing extends beyond text documents to spreadsheets, presentations and
whatever else the installed office suite is measured to accept. The WOPI session,
lock discipline, version precondition, ADR-088 marking and standing refusals
declared by `document-editing-tools` are unchanged and apply to every type here.

E2E note: every scenario below is a guard inside a codec or a session, reached
only by an agent tool call. There is no page on which a refused formula overwrite
or a rejected macro file becomes visible, so all carry a reason-bearing
`@e2e exclude`. The browser-observable part of this capability — the grant surface
and default-deny — is already covered by the tools change that introduced it.

## ADDED Requirements

### Requirement: Each document type is addressed by its own native identity
The system MUST address spreadsheet edits by sheet name and cell address, and
presentation edits by slide identifier and shape identifier. The system MUST NOT
address a presentation slide by its ordinal position, because slide order is not
stable across edits.

#### Scenario: A spreadsheet cell is edited
- **WHEN** an edit targets a spreadsheet cell
- **THEN** it MUST be addressed by sheet name and cell address
- **AND** a row inserted above it by a human MUST NOT cause the edit to land in a
  different cell

#### Scenario: A presentation slide is edited
- **WHEN** an edit targets a slide
- **THEN** it MUST be addressed by slide identifier
- **AND** a slide reordered between read and write MUST NOT cause the edit to land
  on a different slide

@e2e exclude Addressing is internal to the codec and never rendered; a mis-addressed edit is observable only in the resulting package. Verified by round-trip codec tests.

#### Scenario: Speaker notes are edited
- **WHEN** an edit targets a slide's speaker notes
- **THEN** the slide's visible content MUST be unchanged

@e2e exclude Notes and content live in the same package and differ only by region; the assertion is a package diff, not a page observation.

### Requirement: A formula is never replaced by a literal without explicit per-cell intent
The system MUST refuse to write a literal value into a cell that currently holds a
formula, unless the caller has declared that intent for that specific cell. A
call-wide or global flag MUST NOT satisfy this requirement.

#### Scenario: An agent writes a value into a formula cell
- **GIVEN** a cell containing a formula
- **WHEN** an edit writes a literal into it without per-cell intent
- **THEN** the system MUST refuse the edit
- **AND** the formula MUST remain intact

#### Scenario: A bulk write includes one formula cell
- **GIVEN** an edit writing to many cells, one of which holds a formula
- **WHEN** intent was declared for a different cell
- **THEN** the formula cell MUST still be refused
- **AND** the intent declared elsewhere MUST NOT extend to it

#### Scenario: Replacement is explicitly intended
- **GIVEN** a cell containing a formula
- **WHEN** an edit writes a literal into it WITH per-cell intent
- **THEN** the write MUST proceed

@e2e exclude A codec-level guard with no rendered surface. Verified by a control pair — the same write refused without intent and accepted with it — because a guard nobody has watched refuse is untested.

### Requirement: A spreadsheet write reports what it caused to recalculate
The system MUST report, for every accepted spreadsheet write, which cells' computed
values changed as a consequence, and MUST report any dependent cell that became an
error value.

#### Scenario: A written value changes dependent cells
- **WHEN** a spreadsheet write completes
- **THEN** the response MUST identify the cells whose computed values changed

#### Scenario: A write produces an error value downstream
- **WHEN** a write causes a dependent cell to evaluate to an error
- **THEN** the response MUST report that cell and its error
- **AND** the system MUST NOT report the write as wholly successful without it

@e2e exclude Recalculation is computed inside the codec/suite and reported in the tool response; there is no page that displays an agent's recalculation report.

### Requirement: Supported types are measured per suite, never assumed
The system MUST determine editability per type by probing the installed suite, and
MUST publish the result with the suite name, suite version and probe date. A type
that has not been probed MUST be treated as unsupported.

#### Scenario: A type has not been probed
- **WHEN** an edit targets a type absent from the measured declaration
- **THEN** the system MUST refuse
- **AND** the system MUST NOT infer support from the suite's documented format list

#### Scenario: The suite is upgraded
- **WHEN** the installed suite's version changes
- **THEN** the existing declaration MUST NOT be presented as valid for the new version

@e2e exclude The probe runs against a live suite at deploy/verify time and produces a declaration; asserting it in a browser would only re-read what the probe wrote.

#### Scenario: A type is editable on one suite only
- **GIVEN** a type the installed suite cannot edit but another supported suite can
- **WHEN** the capability is resolved
- **THEN** it MUST resolve as absent and be surfaced visibly
- **AND** no other capability MUST require it

@e2e exclude Requires two instances running different office suites; covered by the portability run in the change's verification rather than by a single-instance browser test.

### Requirement: Macro-bearing, database and final-form documents are refused
The system MUST refuse macro-bearing formats before parsing them, MUST refuse
database formats, and MUST restrict PDF handling to annotation and form-fill. The
system MUST determine a file's type from its content, never from its filename
extension.

#### Scenario: A macro-bearing file is targeted
- **WHEN** an edit targets a file carrying macros
- **THEN** the system MUST refuse before parsing the file

#### Scenario: A file's extension misrepresents its content
- **WHEN** a file's extension and actual content disagree
- **THEN** the system MUST judge it on content
- **AND** MUST NOT parse it as the type its extension claims

#### Scenario: A PDF is targeted for content editing
- **WHEN** an edit would rewrite a PDF's text content
- **THEN** the system MUST refuse
- **AND** annotation and form-fill MUST remain available

@e2e exclude Refusals happen before any session opens, so nothing reaches a surface. Asserted directly against the refusal path.

### Requirement: All types share one session, lock and marking path
Spreadsheet and presentation edits MUST use the same WOPI session, lock discipline,
version precondition and ADR-088 marking as text edits. A per-type editing path
MUST NOT re-implement any of them.

#### Scenario: A spreadsheet is edited in place
- **WHEN** a spreadsheet edit completes in the default mode
- **THEN** a restorable prior version MUST exist
- **AND** the file MUST carry the agent-authored mark

#### Scenario: The file changed between read and write
- **WHEN** a spreadsheet or presentation changed since it was read
- **THEN** the write MUST be refused on the same version precondition as a text edit

@e2e exclude The session machinery is inherited unchanged from document-editing-tools and verified there; these scenarios assert that the new codecs reuse rather than replace it, which is a structural property asserted by test, not a browser observation.

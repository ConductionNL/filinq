# docudesk-mcp-surface

Delta: extends DocuDesk's curated MCP surface with two tools that act on
documents which already exist. The schema allowlist declared by
`docudesk-mcp-adoption` is unchanged — this change adds **no** schema and enables
**no** derived write verb. Every standing refusal declared there remains in force.

## ADDED Requirements

### Requirement: Two curated tools act on existing documents
DocuDesk MUST expose exactly two further curated `#[McpTool]` methods via the
`IMcpScannableServices` path: `docudesk.convertDocument`, which MUST produce a new
file and MUST NOT mutate its input; and `docudesk.editDocument`, which by default
modifies the source file in place under the guards this change requires, and which
MUST also support a mode that writes a sibling file and leaves the source
untouched. No further curated tool is added by this change.

#### Scenario: The extended curated surface is enumerated
- **WHEN** an agent lists DocuDesk's tool surface
- **THEN** `docudesk.convertDocument` and `docudesk.editDocument` MUST be present
  in addition to `docudesk.generateCorrespondence`
- **AND** the derived schema surface MUST be unchanged — still sixteen read-only
  tools, and no tool name ending in `.create`, `.update` or `.delete`

#### Scenario: A conversion is invoked
- **WHEN** the conversion tool completes
- **THEN** a new file MUST exist
- **AND** the input file MUST be unchanged

#### Scenario: An edit is invoked in the default mode
- **WHEN** the editing tool completes in its default mode
- **THEN** the source file MUST contain the edit
- **AND** a restorable prior version of that file MUST exist

### Requirement: Both curated tools declare honest ADR-063 descriptor hints
`docudesk.convertDocument` MUST declare `scope: "create"`, `readOnlyHint: false`,
`destructiveHint: false`. `docudesk.editDocument` MUST declare `scope: "update"`,
`readOnlyHint: false`, and **`destructiveHint: true`**, because it modifies a
user's existing file in place. Both MUST declare `idempotentHint: false` and an
agent-facing `description`. The hints MUST describe what the tool does, not what
would be convenient for grant resolution.

#### Scenario: A curated tool is classified for grants
- **WHEN** Hermiq's grant resolver classifies either tool
- **THEN** it MUST resolve as write
- **AND** it MUST be default-denied in the absence of an explicit exact-id grant

#### Scenario: The tool appears in the agent oversight surface
- **WHEN** the per-agent tool catalogue is requested
- **THEN** both tool ids MUST appear with their write classification
- **AND** an operator MUST be able to grant or withhold each one individually

### Requirement: Signing and batch mail-merge remain unexposed
This change MUST NOT add an `#[McpTool]` attribute to any signing service, and
MUST NOT expose the batch correspondence path. The refusals declared by
`docudesk-mcp-adoption` are extended, never relaxed.

#### Scenario: The curated surface is audited for refused capabilities
- **WHEN** the set of `#[McpTool]`-annotated methods is enumerated
- **THEN** no signing service and no batch generation method MUST carry the attribute

#### Scenario: An agent asks to edit a document awaiting signature
- **WHEN** an edit targets a file in an active signature process
- **THEN** the tool MUST refuse
- **AND** the agent MUST report that editing a document under signature is not available

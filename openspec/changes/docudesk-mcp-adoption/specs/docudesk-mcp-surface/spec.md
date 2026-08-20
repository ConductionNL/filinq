# docudesk-mcp-surface

DocuDesk's agent-facing MCP tool surface under ADR-063: the curated schema allowlist, the
curated document-generation tool (which sibling changes MAY extend with further curated
tools via the same scannable-services path, each still bound by the standing refusals here),
and the standing refusals.

## ADDED Requirements

### Requirement: DocuDesk declares a curated read-only MCP schema allowlist
DocuDesk MUST expose exactly eight schemas to MCP via `configuration.x-openregister-mcp` in
`lib/Settings/docudesk_register.json` — `template`, `huisstijl`, `correspondence`,
`generatedDocument`, `batchCorrespondenceJob`, `signingRequest`, `dossier`, `base` — each
with `enabled: true` and the `search` and `get` verbs only. Every one of the sixteen
declared verb configs MUST carry `scope: "read"`, `readOnlyHint: true`, and an agent-facing
`description`. No other DocuDesk schema may carry an `x-openregister-mcp` block.

#### Scenario: The eight allowlisted schemas expose derived read tools
- **WHEN** OpenRegister derives its tool surface from DocuDesk's register
- **THEN** exactly sixteen derived tools exist — `docudesk.{schema}.search` and
  `docudesk.{schema}.get` for each of the eight allowlisted schemas
- **AND** each tool reports `readOnlyHint: true` and `scope: read`

#### Scenario: A non-allowlisted schema exposes no tool
- **WHEN** an agent lists the DocuDesk tool surface
- **THEN** no tool exists for `templateVersion`, `signerRecord`, `signingAuditEntry`,
  `signingSession`, `publicationConsent`, `publicationProhibition`, `anonymizationLink`,
  `financialExtraction`, `glAccountBooking`, or `glAccountMappingRule`

### Requirement: No DocuDesk schema exposes a derived write verb
The `create`, `update` and `delete` verbs MUST NOT be enabled on any DocuDesk schema.
Templates and huisstijl are governance artefacts that define the organisation's official
letterhead and legal boilerplate; `correspondence`, `generatedDocument` and
`batchCorrespondenceJob` are audit records of what was produced; `signingRequest` is the
legal spine of a signature process; `dossier` and `base` are the Woo legal-basis
administration. An agent mutating any of them changes a record whose integrity is the
point of keeping it.

#### Scenario: An agent cannot create, update or delete a DocuDesk object
- **WHEN** an agent enumerates DocuDesk's derived tools
- **THEN** no tool name ends in `.create`, `.update` or `.delete`

#### Scenario: A template cannot be rewritten by an agent
- **WHEN** an agent attempts to modify the `content` of a `template` object
- **THEN** no MCP tool exists to do so, and the agent MUST report that template authoring
  is a human action in the DocuDesk UI

### Requirement: Search filters name only real schema properties
Every `filters` list declared on a `search` verb MUST name properties that exist on that
schema, because OpenRegister's `McpAnnotationValidator` rejects a schema whose declared
filter is not a real property — a bad filter fails the whole register import, not just the
tool. The declared filters MUST be: `template` → `category`, `namespace`, `format`;
`huisstijl` → `name`; `correspondence` → `status`, `templateId`, `caseReference`,
`recipientType`; `generatedDocument` → `status`, `templateId`, `zaakId`, `format`;
`batchCorrespondenceJob` → `status`, `templateId`, `initiatedBy`; `signingRequest` →
`status`, `signatureLevel`, `provider`, `initiatorUserId`; `dossier` → `name`; `base` →
`name`.

#### Scenario: The register imports cleanly
- **WHEN** `docudesk_register.json` is imported into OpenRegister
- **THEN** `McpAnnotationValidator` returns zero errors
- **AND** no `mcp-unknown-filter`, `mcp-unknown-verb`, `mcp-unknown-key` or
  `mcp-bad-scope` error is raised

#### Scenario: Every declared filter resolves to a property
- **WHEN** each declared `search.filters` entry is cross-checked against its schema's
  `properties` map
- **THEN** every entry is present in that map

### Requirement: DocuDesk exposes one curated document-generation tool
`CorrespondenceService::generate()` MUST carry `#[McpTool(name: 'generateCorrespondence',
scope: 'create', readOnlyHint: false, destructiveHint: false, idempotentHint: false)]`, and
`lib/Mcp/DocudeskScannableServices.php` MUST implement OpenRegister's
`IMcpScannableServices` and MUST include `CorrespondenceService::class` in its returned
list. `generateCorrespondence` MUST remain DocuDesk's sole curated document-*generation*
tool. The returned list MAY include additional curated services registered by sibling
changes (for example `mcp-generation-tools`, which adds `getDocumentStatus` and
`anonymizeDocument`); every such additional curated tool MUST itself honour every standing
refusal in this spec (no signing act, no batch, none of the excluded schemas, no derived
write verb) and MUST carry a complete, honest hint set. The method is genuine non-CRUD
behaviour — it fetches a template, resolves OpenRegister data references, applies a
huisstijl, renders, produces the output format, and logs a `correspondence` register entry —
so it is a curated tool and not a derivable CRUD verb. Its hints are load-bearing: a curated
two-segment tool that declares no hints fails open in Hermiq's write/destructive
classification (hermiq #57), so the annotation MUST be present and MUST be honest.

#### Scenario: The generation tool is discovered
- **WHEN** OpenRegister scans DocuDesk's `IMcpScannableServices` implementation
- **THEN** the tool `docudesk.generateCorrespondence` is registered
- **AND** it reports `scope: create`, `readOnlyHint: false`, `destructiveHint: false`,
  `idempotentHint: false`

#### Scenario: Generation is additive, never destructive
- **WHEN** `generateCorrespondence` runs
- **THEN** it creates a new document artefact and a new `correspondence` register entry
- **AND** it MUST NOT modify or delete any pre-existing object, so `destructiveHint: false`
  is accurate

#### Scenario: Generation is not idempotent
- **WHEN** `generateCorrespondence` is invoked twice with identical arguments
- **THEN** two documents and two `correspondence` entries are produced, so
  `idempotentHint: false` is accurate and Hermiq MUST treat a repeat call as a new write

### Requirement: The generation tool neither sends nor signs
`generateCorrespondence` MUST produce a document and log it, and MUST NOT dispatch it to a
recipient by any channel and MUST NOT apply, request, or advance any signature. Producing a
draft on official letterhead is recoverable; sending it to a citizen or signing it is not.

#### Scenario: No dispatch
- **WHEN** an agent generates correspondence with `format: "email"`
- **THEN** the rendered email body is returned and logged
- **AND** no message is sent to the recipient

#### Scenario: No signature side effect
- **WHEN** an agent generates correspondence from a template that is normally signed
- **THEN** no `signingRequest`, `signerRecord`, `signingSession` or `signingAuditEntry`
  object is created

### Requirement: Signing is never agent-writable
DocuDesk MUST NOT add `#[McpTool]` to `SigningService`, `SigningVerificationService`,
`SigningAuditService`, `Service/Signing/SigningProviderInterface` or any of its
implementations (`NativeSigningProvider`, `ValidSignProvider`), and the `signingRequest`
schema MUST NOT enable a write verb. Applying an electronic signature is an act with legal
effect under eIDAS; an agent that could initiate, advance, or cancel a signature could bind
the organisation or a citizen to a document no human ever read. The signature-bearing
schemas `signerRecord` (holds `signatureData` and the signer's `ipAddress`),
`signingSession` (holds `signatures` and the signed-document path) and `signingAuditEntry`
(holds the tamper-evident trail and actor IPs) MUST NOT be exposed at all — not even for
reading.

#### Scenario: No signing tool exists
- **WHEN** an agent enumerates DocuDesk's tool surface
- **THEN** no tool initiates, advances, cancels, or verifies a signature

#### Scenario: Signature material is unreadable
- **WHEN** an agent tries to read a signer's `signatureData` or `ipAddress`
- **THEN** no MCP tool exposes `signerRecord`, `signingSession` or `signingAuditEntry`

#### Scenario: Signing status is still answerable
- **WHEN** a user asks an agent whether a document has been signed yet
- **THEN** `docudesk.signingRequest.search` answers from `status`, `signatureLevel`,
  `provider` and `deadline` — the process metadata — without any signature material

### Requirement: Bulk correspondence is never agent-triggerable
`CorrespondenceService::generateBatch()` MUST NOT carry `#[McpTool]`, and
`batchCorrespondenceJob` MUST remain read-only. A mail-merge over a recipient list is a
bulk personal-data operation; an agent-triggerable one is a mass-mailing and exfiltration
primitive with a single tool call.

#### Scenario: No batch tool
- **WHEN** an agent enumerates DocuDesk's tool surface
- **THEN** no tool generates correspondence for more than one recipient per call

#### Scenario: Batch progress is still readable
- **WHEN** a user asks whether a mail-merge finished
- **THEN** `docudesk.batchCorrespondenceJob.search` answers from `status`,
  `recipientCount`, `completedCount` and `errorCount`

### Requirement: Document content and citizen data are not reachable through the MCP surface
The MCP surface MUST NOT expose the content of a produced document or a citizen's personal
data. `correspondence` and `generatedDocument` hold only references (`templateId`,
`dataRefs`, `zaakId`, `status`) and MUST remain so. The schemas that do hold content or
citizen data MUST stay OFF: `templateVersion` (historical template bodies),
`financialExtraction` (`fields` — the extracted contents of a third party's invoice),
`publicationConsent` (`contactEmail`, `contactAddress`, `entityText`, `objectionReason` —
citizen contact details and their reasons for objecting to publication),
`publicationProhibition` (named individuals plus the legal reason they may not be
published — a list whose enumeration is itself a safety risk) and `anonymizationLink`
(maps a redacted document back to its un-anonymised source path — a re-identification
primitive that would undo the redaction it records).

#### Scenario: A search cannot return document content
- **WHEN** an agent searches `correspondence` or `generatedDocument`
- **THEN** the results carry references and status only, never rendered document bytes

#### Scenario: Redaction cannot be reversed through MCP
- **WHEN** an agent holds the id of an anonymised file
- **THEN** no MCP tool returns the `sourceFilePath` of the un-anonymised original

#### Scenario: The prohibition list cannot be enumerated
- **WHEN** an agent asks which individuals are barred from publication
- **THEN** no MCP tool exposes `publicationProhibition`, and the agent MUST refer the user
  to the DocuDesk UI, where the access is authorised and audited

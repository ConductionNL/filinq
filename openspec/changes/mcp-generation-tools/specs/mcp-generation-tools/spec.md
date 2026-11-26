# mcp-generation-tools Specification (delta)

## Purpose

Filinq's assistant-facing document operations over the ADR-063 MCP
surface established by `filinq-mcp-adoption`: the operation map that
binds `list_templates` and `generate_document` to the existing derived
and curated tools instead of duplicating them, two new curated tools
(`getDocumentStatus` — a read-only cross-service status aggregate;
`anonymizeDocument` — gate-safe anonymisation intake), the entity-value
firewall (no detected entity values, placeholder maps, document text or
file bytes ever cross the MCP boundary), and the governance rails
(OpenRegister tool-grant whitelist, honest complete hints, attributable
logging). All standing refusals of the adoption baseline remain binding.

## ADDED Requirements

### Requirement: Assistant document operations map onto the existing surface without duplication (REQ-DDMGT-001)

The system MUST serve `list_templates` via the derived
`filinq.template.search` / `filinq.template.get` tools and
`generate_document` via the curated `filinq.generateCorrespondence`
tool (both established by `filinq-mcp-adoption`), and MUST NOT declare
a second hand-written tool for either operation (ADR-063: no hand-written
tool for behaviour the platform derives; no shadow tools). This change
MUST NOT enable any additional schema in `x-openregister-mcp` and MUST
NOT add any derived write verb.

#### Scenario: Template listing uses the derived tool

- GIVEN an agent asked which besluit templates exist
- WHEN it enumerates the Filinq tool surface
- THEN `filinq.template.search` is the only template-listing tool
- AND no tool named like `listTemplates` exists
- @e2e exclude registry-shape contract enforced by OR's scanner; covered by the MCP surface probe (tests/integration/Mcp/McpSurfaceTest.php)

#### Scenario: Generation uses the adoption change's curated tool

- GIVEN an agent generating a receipt-confirmation letter
- WHEN it enumerates generation-capable tools
- THEN `filinq.generateCorrespondence` is the only generation tool
- AND the register JSON carries no new `x-openregister-mcp` block from this change
- @e2e exclude registry-shape contract; covered by the MCP surface probe (tests/integration/Mcp/McpSurfaceTest.php)

### Requirement: A curated read-only document-status aggregate tool exists (REQ-DDMGT-002)

The system MUST expose `filinq.getDocumentStatus` as a curated `#[McpTool]`
on `FileListingService::getFileStatus(int $fileId)`, scope `read`, hints
readOnly true, destructive false, idempotent true. For one file the invoking
principal may read, it MUST return the pipeline status, entity counts per
type, OCR state and confidence, the review checked state, the validation
verdict when present, and references to related generation and signing
records.

#### Scenario: Agent answers "is that document anonymised yet?"

- GIVEN a document mid-pipeline with detection done and review pending
- WHEN `filinq.getDocumentStatus` is invoked with its fileId
- THEN the result reports the pipeline status, entity counts per type, `reviewChecked: false` and OCR state
- AND the tool reports `readOnlyHint: true`
- @e2e exclude agent-runtime tool invocation (no Filinq UI surface); covered by PHPUnit + the MCP surface probe (tests/unit/Service/FileListingServiceTest.php, tests/integration/Mcp/McpSurfaceTest.php)

#### Scenario: Inaccessible file is not disclosed

- GIVEN a fileId belonging to another user's private file
- WHEN the tool is invoked under the first principal
- THEN the result is not-found, identical in shape to a nonexistent fileId
- @e2e exclude access-contract; covered by PHPUnit (tests/unit/Service/FileListingServiceTest.php)

### Requirement: A curated gate-safe anonymisation intake tool exists (REQ-DDMGT-003)

The system MUST expose `filinq.anonymizeDocument` as a curated `#[McpTool]`
on `AnonymizationService::anonymizeViaAgent(int $fileId)`, taking only the
fileId, scope `create`, hints readOnly false, destructive false, idempotent
false. It MUST run the intake pipeline (extraction, detection, grounds,
policies, review routing) under the gates of every surface: a prohibition
refusal is a refused result, the review checked gate is never touched,
the source file is never changed.

#### Scenario: Agent triggers intake, humans keep the decision

- GIVEN a document with no detection yet
- WHEN `filinq.anonymizeDocument` is invoked with its fileId
- THEN detection completes and the document appears in the review queue
- AND no anonymised export exists and the document is not marked reviewed
- @e2e exclude agent-runtime invocation; covered by PHPUnit + the MCP surface probe (tests/unit/Service/AnonymizationServiceTest.php, tests/integration/Mcp/McpSurfaceTest.php)

#### Scenario: Prohibition gate refuses without an override channel

- GIVEN a document matching an active publication prohibition
- WHEN the tool's pipeline reaches the gate
- THEN the result is a refusal carrying the gate reason
- AND no parameter exists on the tool through which an agent could acknowledge or override the gate
- @e2e exclude gate pass-through contract; covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)

#### Scenario: Hints are complete and honest

- GIVEN the registered tool surface
- WHEN `filinq.anonymizeDocument` is inspected
- THEN it declares scope create, readOnlyHint false, destructiveHint false, idempotentHint false
- AND no curated Filinq tool is registered without a complete hint set
- @e2e exclude registry-shape contract; covered by the MCP surface probe (tests/integration/Mcp/McpSurfaceTest.php)

### Requirement: No entity values ever cross the MCP boundary (REQ-DDMGT-004)

No Filinq MCP tool result MUST contain detected entity values, placeholder
maps, document text or file bytes. `anonymizeDocument` results MUST hold only
status, entity counts per type, `reviewRequired` and gate-refusal reasons;
`getDocumentStatus` MUST report counts and statuses only. The unit suite MUST
pin both shapes, so a refactor that adds entity payloads breaks the build.

#### Scenario: Anonymise result is counts-only

- GIVEN a document whose detection found the entity "J. de Vries" (PERSON) and a BSN
- WHEN `filinq.anonymizeDocument` completes
- THEN the result reports `entityCounts` (e.g. PERSON: 1, BSN: 1) and `reviewRequired: true`
- AND neither "J. de Vries" nor the BSN value appears anywhere in the result
- @e2e exclude data-minimisation shape contract; covered by PHPUnit shape-pinning tests (tests/unit/Service/AnonymizationServiceTest.php)

#### Scenario: Status result carries no entity text

- GIVEN a reviewed document with entity decisions
- WHEN `filinq.getDocumentStatus` is invoked
- THEN entity information appears as counts per type only
- @e2e exclude data-minimisation shape contract; covered by PHPUnit shape-pinning tests (tests/unit/Service/FileListingServiceTest.php)

### Requirement: Invocations are grant-gated and attributably logged (REQ-DDMGT-005)

Tool availability MUST be governed by OpenRegister's default-deny tool-grant
whitelist; Filinq MUST NOT enforce grants itself or register tools outside
the scannable-services path. Every invocation MUST be attributable:
generation keeps its `correspondence` row with the generating principal, and
anonymisation intake runs MUST be audited by OpenRegister with an `mcp`
attribution distinct from `manual` and `flow`.

#### Scenario: Ungranted agent sees no Filinq write tool

- GIVEN an agent whose grants include no Filinq curated tool
- WHEN it lists available tools
- THEN neither `filinq.anonymizeDocument` nor `filinq.generateCorrespondence` is offered
- @e2e exclude grant enforcement lives in OR/hermiq; covered by the OR tool-grant suite plus the MCP surface probe (tests/integration/Mcp/McpSurfaceTest.php)

#### Scenario: Agent-triggered intake is attributable

- GIVEN a completed agent-triggered anonymisation intake
- WHEN the processing records for the file are inspected
- THEN the run is attributed `mcp` with the invoking principal recorded
- @e2e exclude audit-attribution contract; covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)

### Requirement: The adoption baseline's refusals remain binding (REQ-DDMGT-006)

This change MUST NOT add a tool that initiates, advances, cancels or verifies
a signature, nor a batch or multi-recipient tool; MUST NOT expose
`signerRecord`, `signingSession`, `signingAuditEntry`, `publicationConsent`,
`publicationProhibition`, `anonymizationLink`, `financialExtraction`,
`templateVersion` or the GL schemas through a new tool; and MUST NOT enable
a derived `create`/`update`/`delete` verb. Every adoption refusal MUST hold.

#### Scenario: The extended surface stays within the refusal lines

- GIVEN the full Filinq tool surface after this change
- WHEN it is enumerated
- THEN it is exactly the 16 derived read tools plus the curated tools the app's source declares with `#[McpTool]`: this change's generateCorrespondence, getDocumentStatus and anonymizeDocument, and those later changes add under their own requirements (document editing, document charts, document metadata, the approval-gated signing-request initiation of REQ-DDHAT-001, the consent decision); no tool appears that the source does not declare
- AND no tool name ends in `.create`, `.update` or `.delete`
- AND no tool touches signing acts, batches, or the excluded schemas
- @e2e exclude registry-shape contract; covered by the MCP surface probe (tests/integration/Mcp/McpSurfaceTest.php)

#### Scenario: Redaction still cannot be reversed through MCP

- GIVEN an agent holding the id of an anonymised file
- WHEN it invokes any Filinq tool with that id
- THEN no result reveals the un-anonymised source path or content
- @e2e exclude re-identification guard; covered by PHPUnit shape-pinning tests (tests/unit/Service/FileListingServiceTest.php)

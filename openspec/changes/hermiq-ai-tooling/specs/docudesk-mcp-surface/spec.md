# docudesk-mcp-surface Specification (delta)

Extends DocuDesk's agent-facing surface with two approval-gated curated write tools
(`startSigningRequest`, `recordConsentDecision`) through the scannable-services extension
path the adoption change declared, and narrows the signing refusal from "no signing tool
of any kind" to "the signing act is never agent-performable". Every other standing
refusal is restated and remains binding.

## ADDED Requirements

### Requirement: An approval-gated signing-request initiation tool exists (REQ-DDHAT-001)

`SigningService::createRequest()` MUST carry `#[McpTool(name: 'startSigningRequest',
scope: 'create', readOnlyHint: false, destructiveHint: false, idempotentHint: false)]`,
and `DocudeskScannableServices` MUST include `SigningService::class`. The tool MUST be
approval-required: Hermiq's human-approval gate MUST present the resolved request —
document name, signer list, `signatureLevel`, `deadline`, `provider`, `signingMode` — to
a human and receive explicit approval before the request is created or any signer is
notified. Grants remain default-deny per agent in OpenRegister's tool-grant whitelist;
DocuDesk MUST NOT implement a parallel grant or approval mechanism of its own.

#### Scenario: Generate-and-send-for-signing is one governed conversation

- GIVEN an agent asked to "generate the contract from the template and send it for signing"
- WHEN it chains `docudesk.generateCorrespondence` and `docudesk.startSigningRequest`
- THEN the signing request is created only after a human approves the resolved request in the gate
- AND the created request carries the approved document, signers, and signature level unchanged
- @e2e exclude agent-runtime tool invocation (approval gate lives in hermiq); covered by PHPUnit (tests/unit/Service/SigningServiceTest.php) + the MCP surface probe (tests/integration/Mcp/McpSurfaceTest.php)

#### Scenario: No approval, no request

- GIVEN an agent granted `startSigningRequest` whose approval is rejected or times out
- WHEN the gate resolves without an approval
- THEN no `signingRequest` object exists and no signer has been notified
- @e2e exclude gate-refusal contract enforced in hermiq; covered by the hermiq human-approval-gate suite + the MCP surface probe

#### Scenario: The tool's hints are complete and honest

- GIVEN the registered tool `docudesk.startSigningRequest`
- WHEN its annotation is inspected
- THEN it reports `scope: create`, `readOnlyHint: false`, `destructiveHint: false`, `idempotentHint: false`
- AND Hermiq classifies it as a write requiring approval
- @e2e exclude registry-shape contract; covered by the MCP surface probe (tests/integration/Mcp/McpSurfaceTest.php)

### Requirement: An approval-gated consent-decision tool exists (REQ-DDHAT-002)

The consent status-update path (`ConsentUpdateHandler::updateConsentStatus()`) MUST carry
`#[McpTool(name: 'recordConsentDecision', scope: 'update', readOnlyHint: false,
destructiveHint: false, idempotentHint: true)]`, taking a caller-supplied consent-record
id and a target `consentStatus`, and MUST be approval-required in the same way as
REQ-DDHAT-001. The tool MUST enforce the same status-transition validity rules as the
in-app consent flow. Its result MUST carry status fields only — it MUST NOT return
`contactEmail`, `contactAddress`, `entityText`, `objectionReason`, or any other
citizen-data property, and no search, list, or get over `publicationConsent` is added:
the schema remains OFF the derived surface.

#### Scenario: A withdrawal is recorded with human approval

- GIVEN a caseworker telling the agent an objector withdrew, naming the consent record
- WHEN `docudesk.recordConsentDecision` runs after gate approval
- THEN the record's `consentStatus` reflects the approved transition
- AND the tool result contains no contact details, entity text, or objection reason
- @e2e exclude agent-runtime invocation; covered by PHPUnit (tests/unit/Service/ConsentUpdateHandlerTest.php) + the MCP surface probe

#### Scenario: The keyhole is not a window

- GIVEN an agent granted `recordConsentDecision`
- WHEN it enumerates the DocuDesk tool surface
- THEN no tool searches, lists, or gets `publicationConsent` objects
- AND an invalid or inaccessible consent id yields a not-found result identical in shape to a nonexistent id
- @e2e exclude access-contract; covered by PHPUnit mirroring the ConsentCrudService IDOR pattern

#### Scenario: Invalid transitions are refused

- GIVEN a consent record in a state from which the requested transition is invalid
- WHEN the tool is invoked with that transition
- THEN the invocation fails with the same validation error as the in-app flow
- AND the record is unchanged
- @e2e exclude validation contract; covered by PHPUnit (consent transition tests)

### Requirement: Agent-initiated writes are attributable (REQ-DDHAT-003)

Every invocation of `startSigningRequest` and `recordConsentDecision` MUST leave an
attributable record distinguishing agent initiation from manual action: the persisted
signing request and the consent update MUST carry an `mcp` attribution with the invoking
principal, alongside the human approver recorded by the gate. Reads stay ungated and
unchanged.

#### Scenario: An agent-started signing request is attributable

- GIVEN a signing request created through the tool
- WHEN its audit records are inspected
- THEN the initiation is attributed `mcp` with the invoking principal
- AND the approving human is identifiable through the gate's record
- @e2e exclude audit-attribution contract; covered by PHPUnit (signing audit tests)

### Requirement: All other standing refusals remain binding (REQ-DDHAT-004)

Beyond the narrowed signing refusal (see MODIFIED below), every refusal declared by
`docudesk-mcp-adoption` and restated by `mcp-generation-tools` MUST hold across this
extended surface: no batch or multi-recipient tool, no derived
`create`/`update`/`delete` verb on any schema, no exposure of `signerRecord`,
`signingSession`, `signingAuditEntry`, `publicationProhibition`, `anonymizationLink`,
`financialExtraction`, `templateVersion`, or the GL schemas through any new tool's
parameters or results, and no entity values or citizen contact data in any tool result.

#### Scenario: The extended surface stays within the refusal lines

- GIVEN the full DocuDesk tool surface after this change
- WHEN it is enumerated
- THEN it is exactly the derived read tools plus the curated tools (`generateCorrespondence`, `readDocument`, `editDocument`, `convertDocumentToPdf`, `getDocumentStatus`, `anonymizeDocument`, `startSigningRequest`, `recordConsentDecision`)
- AND no tool name ends in `.create`, `.update` or `.delete`
- AND no tool result carries citizen contact data, entity values, or signature material
- @e2e exclude registry-shape contract; covered by the MCP surface probe (tests/integration/Mcp/McpSurfaceTest.php)

## MODIFIED Requirements

### Requirement: Signing is never agent-writable

The signing **act** is never agent-performable. DocuDesk MUST NOT add `#[McpTool]` to
`SigningService::sign()`, `SigningService::decline()`, `SigningService::bulkSign()`,
`SigningService::cancelRequest()`, `SigningVerificationService`, `SigningAuditService`,
`Service/Signing/SigningProviderInterface` or any of its implementations
(`NativeSigningProvider`, `ValidSignProvider`), and the `signingRequest` schema MUST NOT
enable a derived write verb. Applying, advancing, declining, cancelling, or verifying an
electronic signature is an act with legal effect under eIDAS and MUST remain a deliberate
human action. The signature-bearing schemas `signerRecord` (holds `signatureData` and the
signer's `ipAddress`), `signingSession` (holds `signatures` and the signed-document path)
and `signingAuditEntry` (holds the tamper-evident trail and actor IPs) MUST NOT be
exposed at all — not even for reading.

The sole permitted agent entry into the signing domain is
`docudesk.startSigningRequest` (REQ-DDHAT-001): *initiating* a request, through the
curated scannable-services path, gated on explicit human approval of the resolved
request. No other signing method may ever be exposed, gated or not.

#### Scenario: Only initiation exists, and only gated

- **WHEN** an agent enumerates DocuDesk's tool surface
- **THEN** `startSigningRequest` is the only signing-domain tool
- **AND** no tool applies, advances, declines, cancels, or verifies a signature
- @e2e exclude registry-shape contract; covered by the MCP surface probe (tests/integration/Mcp/McpSurfaceTest.php) and the no-signing-service reachability test (DocumentAgentServiceTest::testNoSigningServiceIsReachableByAnAgent, extended)

#### Scenario: Signature material is unreadable

- **WHEN** an agent tries to read a signer's `signatureData` or `ipAddress`
- **THEN** no MCP tool exposes `signerRecord`, `signingSession` or `signingAuditEntry`

#### Scenario: Signing status is still answerable

- **WHEN** a user asks an agent whether a document has been signed yet
- **THEN** `docudesk.signingRequest.search` answers from `status`, `signatureLevel`,
  `provider` and `deadline` — the process metadata — without any signature material

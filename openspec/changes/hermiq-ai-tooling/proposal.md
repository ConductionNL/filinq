---
kind: code
depends_on:
  - docudesk-mcp-adoption
  - mcp-generation-tools
---

# Proposal: hermiq-ai-tooling

## Why

The product line's direction is that **every app action is in principle
AI-automatable**: an app exposes its actions as governed MCP tools, and Hermiq decides —
per agent, per tool — what an agent may actually do, via its scope
(`read/create/update/delete`) × reach (`self/user/instance/external`) grant model
(hermiq `agent-tool-governance`, `agent-capability-reach`), default-deny for writes,
human approval gates on consequential writes (hermiq `human-approval-gate`), and an
audit trail on every invocation. Even before anyone automates anything, chat becomes a
command surface for the app: "generate the contract from the template and send it for
signing" should be one governed conversation, not five UI screens.

DocuDesk's current surface stops half-way through that sentence. The adoption baseline
(`docudesk-mcp-adoption`, complete) ships 16 derived read tools plus curated
`generateCorrespondence`, and `mcp-generation-tools` (in flight) adds `getDocumentStatus`
and `anonymizeDocument`. An agent can therefore *generate* the contract and *ask* whether
it was signed — but the workflow's spine, actually starting the signing request, and its
tail, recording the outcome of a consent conversation, have no tool at all. Both are real
service actions today (`SigningService::createRequest()`,
`ConsentUpdateHandler::updateConsentStatus()`), reachable only by hand in the UI.

The adoption change refused signing wholesale, and for the right reason at the time: an
*ungoverned* agent that could initiate signatures could bind the organisation. What has
changed is the governance substrate — Hermiq's human-approval gate is now the fleet
mechanism for exactly this class of write (decidesk's `lib/Mcp/` gated meeting/action
tools are the working reference). This change therefore **narrows, not abandons** the
refusal: the signing *act* (apply/advance/cancel/verify a signature) remains permanently
agent-unreachable, while *initiating a request* becomes a curated, approval-gated write —
a human still confirms the concrete document, signers, and signature level before
anything is dispatched.

## What Changes

- **Add curated write tool `docudesk.startSigningRequest`**: `#[McpTool]` on
  `SigningService::createRequest()` (`scope: 'create'`, `readOnlyHint: false`,
  `destructiveHint: false`, `idempotentHint: false`). Declared **approval-required**: the
  tool MUST only execute after Hermiq's human-approval gate has shown the resolved
  request (document name, signers, signature level, deadline, provider) to a human and
  received an explicit approval. No signer notification leaves the building on an
  agent's own authority.
- **Add curated write tool `docudesk.recordConsentDecision`**: `#[McpTool]` on the
  consent status-update path (`ConsentUpdateHandler::updateConsentStatus()`), taking a
  consent-record id the *user* supplies plus the new `consentStatus`. Approval-gated the
  same way. The tool returns status fields only; it MUST NOT return `contactEmail`,
  `contactAddress`, `entityText`, or `objectionReason`, and the `publicationConsent`
  schema itself stays OFF the derived surface — this tool is a keyhole, not a window.
- **Extend `lib/Mcp/DocudeskScannableServices.php`** with `SigningService::class` and the
  consent service class (the existing extension point; no new provider mechanism).
- **Modify the standing refusal** in the `docudesk-mcp-surface` spec: "Signing is never
  agent-writable" is narrowed to "the signing act is never agent-performable" —
  `sign()`, `decline()`, `bulkSign()`, `cancelRequest()`, verification, and the
  signature-material schemas remain permanently refused; `createRequest()` alone is
  exposed, approval-gated.
- **Chat workflows this enables** (2 writes + existing reads):
  1. "Generate the contract from the template and send it for signing" —
     `generateCorrespondence` → `startSigningRequest` (one human approval on the resolved
     request).
  2. "The objector withdrew — record consent on that record" —
     `recordConsentDecision` (human approves the concrete status transition).
  3. "Has it been signed yet?" — `signingRequest.search` (already shipped, no gate).
- CHANGELOG entry.

## Capabilities

### Modified Capabilities

- `docudesk-mcp-surface` — two approval-gated curated write tools are added through the
  scannable-services extension path; the signing refusal is narrowed to the signing act;
  all other standing refusals (no batch, no signature material, no consent/prohibition
  enumeration, no derived write verbs) are restated and remain binding.

### New Capabilities

_None — this extends the surface the adoption change created, under its rules._

## Impact

- **Code:** `lib/Service/SigningService.php` (one attribute + import on
  `createRequest()`; `sign`/`decline`/`bulkSign`/`cancelRequest` untouched and asserted
  attribute-free), the consent status-update service (one attribute),
  `lib/Mcp/DocudeskScannableServices.php` (two entries appended).
- **Config:** none — no `x-openregister-mcp` block changes; no derived verb is enabled.
- **Governance dependency:** the approval gate is enforced in Hermiq
  (`human-approval-gate` spec) keyed on the tools' declared write scope; OR's tool-grant
  whitelist stays default-deny. DocuDesk declares honest hints and refuses to implement
  a parallel grant system (same posture as `mcp-generation-tools` REQ-DDMGT-005).
- **Audit:** signing requests already persist initiator and audit entries via the signing
  flow; agent-initiated ones MUST be attributable as `mcp` with the invoking principal,
  mirroring the anonymisation-intake attribution contract.
- **Data protection:** the consent tool is deliberately asymmetric — write-only-by-id,
  no read — so the citizen-data exclusions of the adoption change are not weakened.

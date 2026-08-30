# Design — hermiq-ai-tooling

Context: the fleet PO direction that every app action is in principle AI-automatable
under per-agent, per-tool governance. Hermiq supplies the governance substrate — scope
(`read/create/update/delete`) × reach (`self/user/instance/external`) grants
(`agent-tool-governance`, `agent-capability-reach`), default-deny writes, the
human-approval gate (`human-approval-gate`), audit — and the app supplies honest tools.
Filinq at HEAD already has the plumbing: 16 derived read tools, four curated tools
(`generateCorrespondence`, `readDocument`, `editDocument`, `convertDocumentToPdf`), the
`mcp-generation-tools` change adding `getDocumentStatus` + `anonymizeDocument`, and the
`FilinqScannableServices` extension point built for exactly this kind of sibling
change. decidesk's `lib/Mcp/` (gated meeting/action-item write tools behind
`McpMeetingGate`) is the fleet reference for writes that only make sense gated.

## Why these two writes, and no others

The action inventory (from the controllers/services at HEAD) sorts into four buckets:

| Bucket | Actions | Verdict |
|--------|---------|---------|
| Already covered | template/status/dossier/signing-status reads; generation; read/edit/convert; anonymisation intake | No change (ADR-063: never duplicate) |
| **This change** | `SigningService::createRequest()`; `ConsentUpdateHandler::updateConsentStatus()` | The two human-initiated workflow writes with a governance story: additive or transition-validated, gate-presentable, audit-attributable |
| Permanently refused | `sign()`, `decline()`, `bulkSign()`, `cancelRequest()`, verification, `generateBatch()` | Legal act / bulk personal-data primitive — no gate makes these safe to hand an agent |
| Not worth a tool | dictionary CRUD, huisstijl/template authoring, GL bookkeeping | Governance artefacts or side-domain; derived write verbs stay off (bias to fewer) |

`startSigningRequest` is the workflow spine: without it, "generate and send for signing"
dead-ends after generation. It is safe *only* because the gate shows a human the resolved
request before dispatch — the human approves the same facts the UI form would show
(document, signers, level, deadline, provider). `cancelRequest()` stays refused even
though it looks symmetric: cancelling a running signature process has legal-notice
implications for signers already notified, and no chat workflow needs it.

`recordConsentDecision` is deliberately shaped as a **keyhole**: write-only-by-id, no
enumeration, result stripped to status fields. The `publicationConsent` schema stays OFF
the derived surface (its exclusion in the adoption change is the hardest one in the app);
the tool only lets an agent complete a transition a human names and approves. Transition
validity is enforced by the same consent-flow rules as the UI, so the gate cannot be
talked into an illegal state.

## Narrowing a standing refusal honestly

The adoption spec's "Signing is never agent-writable" said *no tool initiates, advances,
cancels, or verifies*. This change MODIFIES that requirement rather than quietly adding
an exception elsewhere — the delta rewrites the requirement so the spec stays the single
place the boundary is defined. The narrowed line is: **initiation gated, the act never**.
The reachability test (`testNoSigningServiceIsReachableByAnAgent`) is extended, not
deleted: it now asserts that exactly one signing-domain method carries the attribute and
that it is `createRequest()`.

## Approval-gate mechanics

Filinq does not build a gate. The tools declare honest write hints
(`scope: create`/`update`, `readOnlyHint: false`); Hermiq's classifier routes them
through the human-approval gate (hint-driven classification is load-bearing — hermiq #57
fail-open lesson), and OR's tool-grant whitelist keeps them invisible until an admin
grants them per agent. What Filinq *does* own: transition/validation parity with the
UI paths, `mcp` attribution on the persisted records, and result-shape guarantees (no
citizen data, no signature material). Reach for both tools is `user` — they act on
records the invoking principal can already reach through the app, never cross-tenant.

## Rollback

Remove the two attributes and the two scannable-services entries; the tools disappear
from the registry on the next scan. No config, schema, or route is touched.

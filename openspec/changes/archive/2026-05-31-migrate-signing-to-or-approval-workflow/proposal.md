# Proposal: migrate-signing-to-or-approval-workflow

## Why

Docudesk ships a signing-flow engine that routes a document through one or more signers in
sequence: `SigningService`, `SigningController`, `SigningProviderFactory`, and
`SigningProviderInterface`. Each signer must sign or decline before the next is notified.
The service manages step state (pending signer, next signer, completion detection), stores
signing requests in an OR-backed schema, and enforces the sequential flow entirely in-app.

This is structurally a multi-step approval chain. OpenRegister has shipped `approval-workflow`
(status: implemented), which provides exactly the same abstraction: named chains with ordered
steps, role-gated decisions, automatic advance-on-approval, full decision history, and a REST
API for chain CRUD and step decisions.

The signing-flow state machine in `SigningService` violates **ADR-022** (Apps Consume
OpenRegister Abstractions). The concrete harms:

- **Duplicate role-gating logic**: `SigningService` re-implements signer authorisation that
  OR's role-based step enforcement already provides.
- **Drift risk**: sequential-signer logic and completion-detection evolve independently from
  OR's approval engine, accumulating edge-case handling divergence.
- **No cross-app approval queries**: "all pending signing steps for objects I own" requires a
  single OR approval store.
- **Provider coupling to step state**: `NativeSigningProvider` and external provider adapters
  query step state from app-local storage; they should observe OR's step events instead.

## What

This spec migrates docudesk's signing-flow orchestration to use OR's `approval-workflow` API
as the chain-state backend, while fully preserving the existing docudesk signing API surface
for callers:

1. `SigningService` is rewritten to create and manage `ApprovalChain` objects in OR, mapping
   signers to ordered steps.
2. `SigningProviderInterface`, `SigningProviderFactory`, and `NativeSigningProvider` remain in
   docudesk as the signing execution plug-in layer — they are invoked on OR's
   `ApprovalStep pending` event, not by an app-local step cursor.
3. `SigningController` endpoint surface is **unchanged** — callers continue to use docudesk's
   signing endpoints.
4. Retention and audit for signing events are covered by the audit-trail migration
   (`migrate-signing-audit-to-or-audit`) and the Archiefwet 10-year retention via OR's
   archival-destruction-workflow.

## Capabilities

### New Capabilities

- `signing-via-or-approval-with-provider-plugins`: Signing flows are now backed by OR's
  `ApprovalChain` entity. Signing providers (`NativeSigningProvider`, external adapters) act
  as OR ApprovalStep execution plug-ins, triggered by step state changes in OR.

## Affected Projects

- [x] Project: `docudesk` — all implementation tasks are in this repo
- [x] Project: `openregister` — no code change; verify DI class for ApprovalChain CRUD
  (tracked in umbrella OR-1.1)

## Out of Scope

- Signing-provider execution layer (NativeSigningProvider, SigningProviderFactory,
  SigningProviderInterface): these remain in docudesk. They are the "execute this approval
  decision" plug-in layer, not the chain itself.
- Signing audit trail migration (covered by `migrate-signing-audit-to-or-audit`).
- Archiefwet retention configuration (covered by the archival-destruction-workflow migration).
- Modifying OR's `approval-workflow` spec or API.
- Historical backfill of existing signRequest rows into OR's ApprovalChain tables.

## Success Criteria

- `openspec validate --strict migrate-signing-to-or-approval-workflow` exits 0.
- `GET /api/approval-chains` returns docudesk signing chains after migration.
- Existing docudesk signing API tests pass without modification.
- No new signing-chain state is stored outside OR's ApprovalChain tables after migration.
- `SigningService` contains no bespoke step-routing state-machine code.
- `NativeSigningProvider` is triggered by OR ApprovalStep events, not by an app-local cursor.

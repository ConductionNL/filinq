---
status: done
---

# signing-via-or-approval-with-provider-plugins Specification

## Purpose
Implements document signing on top of OpenRegister's approval-workflow, creating an `ApprovalChain` with one ordered `ApprovalStep` per signer instead of a Filinq-local approval store. Signer decisions are emitted exclusively through OR's approve and reject step endpoints, while pluggable signing providers (the native provider and external providers) present the signing UI and return a sign-or-decline result without mutating step state themselves. This reuses OR's chain orchestration for sequencing and lets Filinq integrate alternative signing backends.
## Requirements
### Requirement: Sign-Request Creation SHALL Create an OR ApprovalChain with One Step per Signer

SHALL be the primary requirement that when a signing request is initiated on a document,
filinq creates an OR `ApprovalChain` with one `ApprovalStep` per signer in the requested
order. Step roles are NC group IDs. No new signing-chain rows are written to any filinq-local
approval schema.

#### Scenario: Sign request with two signers creates a two-step OR ApprovalChain

- GIVEN a document with UUID `doc-xyz` stored in a filinq OR register
- AND a sign request is initiated with signers in order: `signer-a`, `signer-b`
- WHEN the POST to filinq's sign-request endpoint is called
- THEN an `ApprovalChain` SHALL be created in OR with two steps (order 1, 2)
- AND step order-1 `role` SHALL be the NC group ID for `signer-a`, status `pending`
- AND step order-2 `role` SHALL be the NC group ID for `signer-b`, status `waiting`
- AND the chain SHALL be accessible via `GET /api/approval-chains`

#### Scenario: Single-signer sign request creates a one-step OR ApprovalChain

- GIVEN a sign request with a single signer `signer-only`
- WHEN the sign-request endpoint is called
- THEN an `ApprovalChain` SHALL be created in OR with one step, `status: pending`
- AND the chain SHALL be accessible via `GET /api/approval-chains`

---

### Requirement: Signer Approval and Decline MUST Emit Via OR's Approval-Workflow API

MUST be the requirement that all signer decisions (sign or decline) are emitted through
OR's approval-step decision endpoints. Filinq MUST NOT update step state in any local storage
path in parallel with or instead of calling OR's API.

#### Scenario: Signer signs — OR approve endpoint is called

- GIVEN an ApprovalStep with `status: pending` for `doc-xyz`, step `order: 1`
- AND the requesting user is a member of the step's `role` group
- WHEN the signer completes the signing flow (e.g. via NativeSigningProvider)
- THEN filinq SHALL call `POST /api/approval-steps/{id}/approve`
- AND OR SHALL set `status: approved`, `decidedBy`, `decidedAt` on the step
- AND OR SHALL advance step `order: 2` to `status: pending`

#### Scenario: Signer declines — OR reject endpoint is called

- GIVEN an ApprovalStep with `status: pending` for `doc-xyz`
- WHEN the signer clicks "Decline" with reason "Niet akkoord met de inhoud"
- THEN filinq SHALL call `POST /api/approval-steps/{id}/reject` with the reason in `comment`
- AND OR SHALL set `status: rejected`; no next step is advanced

---

### Requirement: Signing Providers SHALL Execute on OR ApprovalStep Pending Transition (Event-Driven)

SHALL be the requirement that signing providers (NativeSigningProvider and external provider
adapters) are invoked as a response to an OR ApprovalStep moving to `pending`, not by an
app-local step cursor. Providers capture the signature and return a result; `SigningService`
calls OR's approve or reject endpoint with that result.

#### Scenario: NativeSigningProvider invoked when step becomes pending

- GIVEN ApprovalStep `order: 1` for `doc-xyz` has `status: pending`
- WHEN OR dispatches an `ApprovalStepInitiatedEvent` (step order 1) or an
  `ApprovalStepApprovedEvent` (step order N approved, step order N+1 becomes pending)
- THEN `NativeSigningProvider` SHALL be invoked with the step and document context
- AND the provider SHALL present the signing UI to the NC user in the step's role group
- AND the provider SHALL NOT update ApprovalStep state itself; it returns sign/decline to
  `SigningService` which calls the appropriate OR endpoint

#### Scenario: External signing provider invoked on step pending

- GIVEN a sign request configured with an external signing provider
- AND ApprovalStep `order: 1` becomes `pending`
- WHEN OR dispatches `ApprovalStepInitiatedEvent` or `ApprovalStepApprovedEvent` for that step
- THEN filinq SHALL delegate to the configured `SigningProviderInterface` implementation
- AND the provider SHALL return a signing URL or handle the external signing flow
- AND on callback/completion, filinq SHALL call `POST /api/approval-steps/{id}/approve`
  or `/reject` based on the provider's result

---

### Requirement: Signing API Surface for Clients SHALL Be Preserved

SHALL be the requirement that all existing filinq signing endpoints (initiate sign request,
get sign status, cancel sign request) retain their current request parameters and response
shapes. Callers require no changes when filinq migrates signing-chain state to OR.

#### Scenario: Existing sign-request endpoint behaves identically after migration

- GIVEN a client calls `POST /api/signing/requests` with the same payload as before migration
- WHEN the request is processed
- THEN the response shape SHALL be identical to the pre-migration response
- AND the sign request SHALL be backed by an OR ApprovalChain internally

#### Scenario: Sign status endpoint returns correct state from OR

- GIVEN a sign request backed by an OR ApprovalChain with two steps, one approved and one pending
- WHEN the client calls `GET /api/signing/requests/{id}`
- THEN the response SHALL indicate one step complete and one step pending, in the same
  format as the pre-migration response

---

### Requirement: MUST NOT Write to Deprecated Signing-Chain Schema

MUST NOT be violated: after this migration ships, no code path in filinq creates or updates
objects in any app-local signing-chain approval schema. All new signing chains are OR
`ApprovalChain` objects. Existing pre-migration signing-chain rows remain accessible read-only.

#### Scenario: Migration does not write new rows to deprecated schema

- GIVEN the migration is deployed
- WHEN any filinq endpoint initiates or advances a signing flow
- THEN no object of any deprecated filinq signing-chain schema type SHALL be created
- AND the deprecated schema's object store SHALL contain only pre-migration rows

---

### Requirement: Retention Configured Per Audit-Trail Migration Cross-Reference

SHALL be the requirement that Archiefwet 10-year retention for signed documents is satisfied
by OR's archival-destruction-workflow configured on the document schema, not by filinq-local
retention logic. This migration does not introduce new retention code; it relies on the
retention configuration in the document schema's register definition.

#### Scenario: Signed document subject to OR archival workflow

- GIVEN a sign request for `doc-xyz` completes (all steps approved)
- WHEN the retention period for signed documents applies
- THEN the document SHALL be subject to OR's archival-destruction-workflow
- AND no filinq-local retention service SHALL duplicate OR's archival logic

---

### Requirement: Provider Async-Flow Methods Are a Pluggable Extension Seam, Not Authorization Guards

The `SigningProviderInterface` async-flow methods SHALL be classified as a
pluggable extension seam implemented by external signing providers, namely
`initiateSigning`, `checkStatus`, `downloadSignedDocument` and `cancelSigning`. These
methods SHALL NOT be treated as authorization guards: none makes an access
decision, and `checkStatus` in particular is a status **read** returning
`status`/`signers`/`completedAt`. The current app signing path is synchronous
and drives only `produceSignedArtifact` (plus `supportsLevel`/`getIdentifier`);
the async-flow methods have no native caller by design and are invoked only by
external-provider plugins. The live "get sign status" surface for clients SHALL
remain OR's `ApprovalChain` read via the authenticated, per-UID-authorized
`SigningController::showRequest`, never `provider->checkStatus`.

#### Scenario: checkStatus is a status read, not an authorization guard

- GIVEN a signing request handled by `NativeSigningProvider`
- WHEN `checkStatus` is invoked with the request's external identifier
- THEN it SHALL return the persisted `status`, `signers`, and `completedAt`
- AND it SHALL make no authorization decision and reject no actor
- AND it SHALL be reached only through the pluggable-provider extension flow,
  not the app's live status endpoint

#### Scenario: Live sign-status surface is the authenticated controller path

- GIVEN a client requests the status of sign request `{id}`
- WHEN the client calls `GET /api/signing/requests/{id}`
- THEN the request SHALL be served by `SigningController::showRequest`
- AND the caller SHALL be authenticated (`401` when no user session)
- AND the caller SHALL be authorized per-UID against the request owner
  (`403` on mismatch)
- AND `provider->checkStatus` SHALL NOT be on this live path


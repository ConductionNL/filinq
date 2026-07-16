# signing-via-or-approval-with-provider-plugins Specification (delta)

---
status: proposed
---

## Purpose

Document that the `SigningProviderInterface` async-flow methods
(`initiateSigning`, `checkStatus`, `downloadSignedDocument`, `cancelSigning`)
are a **pluggable extension seam** for external signing providers, not
authorization guards, and are intentionally without a native caller in the
current synchronous signing path. This closes the `hydra-gate-6 (orphan-auth)`
triage of `checkStatus`, whose auth-verb prefix (`check`) causes it to be
flagged even though it performs no authorization decision.

@e2e exclude backend provider-contract classification; behaviour is covered by
NativeSigningProviderTest + ValidSignProviderTest unit tests, no UI surface.

## ADDED Requirements

### Requirement: Provider Async-Flow Methods Are a Pluggable Extension Seam, Not Authorization Guards

The `SigningProviderInterface` async-flow methods — `initiateSigning`,
`checkStatus`, `downloadSignedDocument`, `cancelSigning` — SHALL be classified
as a pluggable extension seam implemented by external signing providers. These
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

# Design: orphan-auth-remediation

## Context

`hydra-gate-6 (orphan-auth)` enumerates public methods in `lib/Service` and
`lib/Controller` whose name starts with an auth verb
(`is|requires?|validate|authorize|check|ensure|verify|assert`) and flags any
that have zero external callers (`->method(`) anywhere in `lib/` or `src/`. A
defined-but-never-called authorization method is identical to having no check
at all (OWASP A01:2021). The gate does not distinguish a genuine auth guard
from an unrelated method that merely starts with an auth verb (`checkStatus`,
`validateFormat`, …) — that disambiguation is this triage's job.

Clean `origin/development` (`git` HEAD `6353daa0`) reports 3 findings.

## Verdict Table

| # | File:line | Method | Live path? | Verdict | Rationale |
|---|-----------|--------|-----------|---------|-----------|
| 1 | `lib/Service/Signing/SigningProviderInterface.php:72` | `checkStatus` | n/a (contract) | **SEAM** | Pluggable-provider contract method. Declares the async external-provider status-poll extension point. Not an authorization guard. |
| 2 | `lib/Service/Signing/NativeSigningProvider.php:151` | `checkStatus` | no | **SEAM** | Implements the contract; reads persisted session `status`/`signers`/`completedAt`. Pure status read, no authorization decision. Invoked only via the provider extension point. |
| 3 | `lib/Service/Signing/ValidSignProvider.php:115` | `checkStatus` | no | **SEAM** | Implements the contract as a stub (external ValidSign integration pending). Returns a fixed `pending` shape. No authorization decision. |

**Summary:** 3 seam, 0 wire, 0 delete, 0 unsure. No live authorization defect.

## Verification Evidence

Caller census across `lib/` + `src/` for every `SigningProviderInterface`
method (`grep -rnE -- "->METHOD\("`):

| Method | Callers | Wired to live path? |
|--------|---------|---------------------|
| `getIdentifier` | 1 | yes (`ApprovalStepListener`, `SigningService`) |
| `supportsLevel` | 1 | yes (`SigningService`) |
| `produceSignedArtifact` | 1 | yes (`SigningService::…` line 787 — the live sign path) |
| `initiateSigning` | 0 | no (async external-provider flow, unwired) |
| **`checkStatus`** | **0** | **no (async external-provider flow, unwired)** |
| `downloadSignedDocument` | 0 | no (async external-provider flow, unwired) |
| `cancelSigning` | 0 | no (async external-provider flow, unwired) |

`checkStatus` is one leg of a four-method async external-provider flow
(`initiateSigning` → `checkStatus` → `downloadSignedDocument` → `cancelSigning`)
that the current app does **not** drive. The live signing path is synchronous:
`SigningService` resolves the active provider via `SigningProviderFactory` and
calls only `produceSignedArtifact` (SigningService.php:787). The event-driven
extension hook (`ApprovalStepListener::invokeProviderForPendingStep`) resolves
the provider and calls `getActiveProvider()`/`getIdentifier()` only — external
providers use this hook to push a remote signing request; `NativeSigningProvider`
is a documented no-op there. The methods carry unit tests
(`ValidSignProviderTest`, `NativeSigningProviderTest`) and `@spec` refs, and the
interface's `checkStatus` is part of the documented provider contract.

### Live status/signing paths are protected (checked first, per the brief)

The live "get sign status" surface is **not** `provider->checkStatus`; it is
`SigningController::showRequest` (`GET /api/signing/requests/{id}`), which reads
OR `ApprovalChain` state. Every `SigningController` action
(`createRequest`, `listRequests`, `showRequest`, `cancelRequest`, `sign`,
`decline`, `bulkSign`, `verify`, `getAudit`) authenticates via
`userSession->getUser()` (→ `401` when absent) and authorizes per-user by `UID`
(with explicit `403 FORBIDDEN` on ownership mismatch). No live signing or
document-access path is left unprotected.

## Why SEAM (not WIRE, not DELETE)

- **Not WIRE:** wiring `checkStatus` would require inventing a status-polling
  endpoint/poller for the async external-provider flow whose other three legs
  (`initiateSigning`/`download`/`cancel`) are equally unwired. That is a new
  feature, out of scope, and would be gold-plating an unrequested capability.
- **Not DELETE:** `checkStatus` is part of a documented, tested pluggable
  provider contract and is the extension point external providers (ValidSign)
  implement. Deleting it would break the contract, the external-provider stub,
  and their unit tests, and remove a planned capability.
- **SEAM:** the honest classification. Annotate the three methods with an
  explicit orphan-auth seam note (method bodies untouched — the gate's
  pre-existing-method filter keeps gate-6 green) and record the contract seam in
  the canonical spec so future sweeps do not re-triage it.

## Seed Data

None. This change introduces no register/schema/seed data, no
`x-openregister-*` declarative metadata, and no notification-dialect changes,
so ADR-031 (schema-declarative business logic over service classes) does not
apply — there is no business logic to move into schema metadata. The change is
docblock text + a canonical spec requirement only.

## ADR alignment

- **ADR-031** (schema-declarative business logic): N/A — no service logic added
  or moved; no schema-declarative surface touched.
- **OWASP A01:2021 / gate-6 intent:** satisfied — the three findings are proven
  non-authorization status reads on a pluggable seam, not silent-missing guards.

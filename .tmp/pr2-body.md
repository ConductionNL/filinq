## Scope

Second of three stacked PRs for the open change `signer-identity-rails`. Every signing act now passes an identity gate, a guardian can be held to a stronger assurance than the pupil, and the evidence lands on the signer record, the audit entry, the signed file and the completion payload.

**Stacked on #1237** (`feat/signer-identity-assurance`); land that first. Against `development` this PR shows both; its own commits are abda45e5 and 3b5053a7.

Closes tasks **3.1, 3.2, 3.4, 4.1** of `openspec/changes/signer-identity-rails/tasks.md` (ticked). REQ-DDSIR-002 (creation floor), 003, 004, 007, and the assurance half of 009 that #1215 left for task 3.1.

## What changed

- `SigningAssuranceGate` runs in `sign()` and `decline()` after the ownership, status and mandate checks and before any write. Evidence comes from the verified portal assertion (provider `portaliq` at its trust), else from a step-up kept for this exact request and signer, else from the Nextcloud session at `low`. Missing, stale (default 15 minutes), unregistered or too-weak evidence throws `StepUpRequiredException`: code 403, nothing mutated.
- **Task 3.1, the guardian's stronger check.** A guardian needs the strongest of the request's `requiredAssurance`, its `guardianRequiredAssurance` and the admin's guardian minimum (default `low`, no change). learniq's OPP can send `guardianRequiredAssurance: substantial`, matching its own `LearningPlanSignatureGuard`, while the pupil signs with a Nextcloud login. Design D6a records it and closes the open question.
- `createRequest()` stores `requiredAssurance` at or above the signature level's floor (QES asking `low` becomes `high`) and answers `assuranceFloor`. A value off the scale is a 400.
- Step-up endpoints: `POST /api/signing/requests/{id}/identity` (the signing ownership check, then the configured provider's challenge; a generic 404 otherwise) and `GET /api/signing/identity/callback` (the single-use state bound to this session is the CSRF guard; the signer lands back on `/signing/{id}?stepUp=done`).
- `SigningController` returns the step-up hint as `stepUp` beside the 403. The portal receiver answers 403 `signing_refused` for a refused decline too, not the 502 of a downstream failure.
- The act writes `identityEvidence` on the signer record and in the audit `metadata`. At completion the request gets `signerEvidence` and `resolvedAssurance` (the weakest recorded level). The evidence joins the native assertion before the MAC, and `SigningConcludedEvent::assuranceLevel` carries `resolvedAssurance` exactly.

## Tests written first

Red run at 18:58: 38 new tests red (32 errors: gate, store, step-up service, controller and exception missing; 6 failures: no refusal, no evidence in the assertion, no recorded assurance in the payload, no floor on creation). New files: `SigningAssuranceGateTest`, `SignerStepUpServiceTest`, `EvidenceMinimisationTest` (a broker subject embedding a BSN leaves no trace in store, records, audit or logs), `SignerIdentityControllerTest`; additions to `SigningServiceTest`, `NativeSigningProviderTest`, `SigningControllerTest`, `PortalSigningReceiverControllerTest`.

## Verified

- `composer check:strict`: exit 0, ALL CHECKS PASSED, PHPUnit 2631 tests, 3 skipped. After the gate-7 rename (3b5053a7): full PHPUnit again 2631 green, psalm on the touched files clean.
- `npm run lint` exit 0, `npm run format` exit 0, `npm run test:l10n` OK.
- `openspec validate signer-identity-rails --strict`: valid.
- Hydra gates (`--scope-to-diff`, vendored `HYDRA_GATES_HOME`): exit 1, 64 of 64 applicable ran. Gate 7 flagged the callback first; it now calls `authorizeCallback()` (the session binding was already there). Only gate 101 remains.
- phpmd on the touched files: two findings met and fixed on the way (`sign()` 106 lines, `NativeSigningProvider` complexity 50). Two suppressions carry their reason: `SigningController` coupling (the step-up exception) and the value object's named constructor.

## Inherited, not fixed

Gate 101: the same 15 schemas without demo data as on `origin/development` (listed in #1237). phpmd `UnusedFormalParameter` on `NativeSigningProvider::initiateSigning()` `$options` is on base too.

## Not done here

Coverage percentage not measured locally (no pcov or xdebug); every new class has its own test file. The sign dialog, request form field, translations and docs follow in the third PR. Task 4.2 (Playwright against a mock OIDC container) stays open: lane rules keep lanes off the shared instance and containers.

🤖 Generated with [Claude Code](https://claude.com/claude-code)

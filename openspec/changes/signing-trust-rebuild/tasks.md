# Tasks: signing-trust-rebuild

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 16.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Identity-bound assertion + honest verification

- [x] 1.1 Assertion v2 writer in `NativeSigningProvider::produceSignedArtifact()`: add `v: 2`, MAC = HMAC-SHA256 over `sha256(canonical(doc)) . "\n" . canonical-JSON(assertion minus mac)` with sorted keys (REQ-DDSTR-001)
  - Writer + verifier land in the same PR; secret never logged; `supportsLevel()` guard added in the same method (REQ-DDSTR-002 point 3)

- [x] 1.2 Verifier v2 in `SigningVerificationService::verifyAssertion()`: recompute over both parts, `hash_equals`; assertions without `v: 2`/`mac` → `unverifiable` / `legacy-assertion-v1` (REQ-DDSTR-001)

- [x] 1.3 Tri-state reporting (REQ-DDSTR-005): per-signature `status` + `reason` (derived `valid` kept), document `verdict` (`verified|tampered|unverifiable|mixed`, strict `isValid` kept); external `/Type /Sig` → `unverifiable`/`external-signature-unsupported`
  - `SignatureVerification.vue` renders the tri-state (NL Design System tokens, no hardcoded colors)

- [x] 1.4 Mutation-style unit tests: payload rewrite keeps mac → `invalid` (incl. the swapped-signer-name mutation); byte-flip → `tampered`; legacy v1 → `unverifiable`; genuine round-trip → `verified`; run green in the `nextcloud:34.0.0-apache` container (1016/1016)

## 2. Honest pipeline: provider/level, status machine, session download

- [x] 2.1 Provider/level honesty (REQ-DDSTR-002): `createRequest()` validates level↔provider via `supportsLevel()` (400 pre-persist); `produceAndStoreSignedArtifact()` drops the `catch → getActiveProvider()` fallback (unknown provider fails completion loudly, request stays IN_PROGRESS)

- [x] 2.2 `decline()` through the status machine (REQ-DDSTR-003): `isValidTransition(current, 'DECLINED')` gate BEFORE any signer/request mutation; terminal states reject all transitions

- [x] 2.3 Session download fail-closed (REQ-DDSTR-004): `downloadSignedDocument()` requires `completed` + non-empty `signedDocumentPath` + `markerEmbedded === true`, else throws; remove the original-path fallback branch

- [x] 2.4 Unit tests for 2.1–2.3 incl. the QES+native 400, unknown-provider completion failure, decline-on-COMPLETED rejection, marker-less download refusal

- [x] 2.5 Preserve the decidesk delegation seam (REQ-DDSTR-010): kept the `docudesk-signing` `/signing-requests` request/response fields backward-compatible (no field touched); added the resolved eIDAS `assuranceLevel` (`low` for native SES, `substantial`/`high` for other provider/level pairs) to `SigningConcludedEvent` (the completion payload the consumer maps to `resolveSignatureStage()`), unit-tested in `tests/unit/Event/SigningConcludedEventTest.php`.
  - Verified against the real decidesk checkout: `EIDASSignatureService::resolveSignatureStage()`/`composeDocudeskSigningRequest()` do not read `assuranceLevel` today, so this is a pure additive field with zero coupling risk. **Newman contract test against the docudesk-signing collection NOT written** — no Newman collection exists for this seam in this repo.

## 3. Audit binding + proven immutability

- [x] 3.1 `SigningAuditService::logEvent()` resolves the real signing-request entity (register `signing`/schema `signingRequest`) with uuid fallback + warning (REQ-DDSTR-006); `getAuditTrail()` switches to an `object_uuid`-scoped mapper query — the filter key was verified against the REAL `AuditTrailMapper::findAll()` at OR HEAD (`apps-extra/openregister/lib/Db/AuditTrailMapper.php`, confirms `object_uuid` is a real column filter) (REQ-DDSTR-007)

- [ ] 3.2 **NOT DONE.** Immutability + retention as verified controls (REQ-DDSTR-008): no Newman negative tests were run against a live Postgres 8080 instance in this pass (no live environment was stood up for this task); the deployment-guide/retention-deploy-check update was not made. This remains the one genuinely unverified control from the design's Security Considerations.

## 4. Consent oracle + closure

- [x] 4.1 `ConsentController::errorResponse()` adopts the SigningController oracle-free pattern (REQ-DDSTR-009), verbatim-matched against `SigningController::errorResponse()`; unit-tested (byte-identical not-found vs non-owner, exception-text-never-leaks, 4xx-honoured) in `tests/unit/Controller/ConsentControllerTest.php`. **Newman parity assertions NOT written** (no Newman collection in this repo for consent). Coordinate-only: `_rbac`/`_multitenancy` flags untouched (owned by `multi-tenant-hardening`).

- [ ] 4.2 **NOT DONE.** Playwright e2e `tests/e2e/spec-coverage/signing-trust-rebuild.spec.ts` was not authored in this pass — no live Nextcloud/Postgres 8080 instance was exercised. Every behaviour it would cover is unit-tested instead (see 1.4/2.4/3.1).

- [x] 4.3 i18n: EN source (`l10n/en.json`/`en.js`) + NL translations (`l10n/nl.json`/`nl.js`) added for the new verdict/status/reason strings; `node tests/l10n/check-l10n.js` passes with zero missing keys.

- [ ] 4.4 **NOT DONE.** `docs/features/` signing page, F-13/`features.json` narrative, and GH issue closing (#282/#283/#284/#287/#289/#304) were not done in this pass — issue closure requires live-verify evidence this pass did not produce, and the task instructions explicitly said not to fake this step.

- [x] 4.5 Quality gates: full PHPUnit suite green (1016/1016, 0 failures/errors) in the `nextcloud:34.0.0-apache` container; PHPCS clean on every touched `lib/` file (0 errors, 0 warnings after fixes); PHPStan/Psalm run — see PR body for results; `openspec validate signing-trust-rebuild --strict` — see PR body.

**Overall**: the security core (D1–D7 / REQ-DDSTR-001..007, 009, 010) is implemented and unit-tested; this change is left ACTIVE (not archived) because 3.2 (live immutability proof), 4.2 (Playwright e2e), and 4.4 (docs + issue closure) are genuinely unfinished and require a live Postgres/Nextcloud verification pass this session did not perform.

## Quality checklist

- Fail-closed proven by mutation tests for every new gate (never `verified`/COMPLETED/bytes on a failure path)
- Unit coverage ≥ 75% on changed code; no mock-based shortcuts around the real OR AuditTrailMapper contract
- No secret material in schemas, config dumps or logs (ADR-064 posture)
- Response-shape backward compatibility (`valid`, `isValid`) verified by Newman against the pre-change collection
- No overlap with `multi-tenant-hardening` file edits beyond `ConsentController::errorResponse()`

# Tasks: signing-trust-rebuild

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 16.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Identity-bound assertion + honest verification

- [ ] 1.1 Assertion v2 writer in `NativeSigningProvider::produceSignedArtifact()`: add `v: 2`, MAC = HMAC-SHA256 over `sha256(canonical(doc)) . "\n" . canonical-JSON(assertion minus mac)` with sorted keys (REQ-DDSTR-001)
  - Writer + verifier land in the same PR; secret never logged; `supportsLevel()` guard added in the same method (REQ-DDSTR-002 point 3)

- [ ] 1.2 Verifier v2 in `SigningVerificationService::verifyAssertion()`: recompute over both parts, `hash_equals`; assertions without `v: 2`/`mac` → `unverifiable` / `legacy-assertion-v1` (REQ-DDSTR-001)

- [ ] 1.3 Tri-state reporting (REQ-DDSTR-005): per-signature `status` + `reason` (derived `valid` kept), document `verdict` (`verified|tampered|unverifiable|mixed`, strict `isValid` kept); external `/Type /Sig` → `unverifiable`/`external-signature-unsupported`
  - `SignatureVerification.vue` renders the tri-state (NL Design System tokens, no hardcoded colors)

- [ ] 1.4 Mutation-style unit tests: payload rewrite keeps mac → `invalid`; byte-flip → `tampered`; legacy v1 → `unverifiable`; genuine round-trip → `verified`; run in container `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`

## 2. Honest pipeline: provider/level, status machine, session download

- [ ] 2.1 Provider/level honesty (REQ-DDSTR-002): `createRequest()` validates level↔provider via `supportsLevel()` (400 pre-persist); `produceAndStoreSignedArtifact()` drops the `catch → getActiveProvider()` fallback (unknown provider fails completion loudly, request stays IN_PROGRESS)

- [ ] 2.2 `decline()` through the status machine (REQ-DDSTR-003): `isValidTransition(current, 'DECLINED')` gate BEFORE any signer/request mutation; terminal states reject all transitions

- [ ] 2.3 Session download fail-closed (REQ-DDSTR-004): `downloadSignedDocument()` requires `completed` + non-empty `signedDocumentPath` + `markerEmbedded === true`, else throws; remove the original-path fallback branch

- [ ] 2.4 Unit tests for 2.1–2.3 incl. the QES+native 400, unknown-provider completion failure, decline-on-COMPLETED rejection, marker-less download refusal

- [ ] 2.5 Preserve the decidesk delegation seam (REQ-DDSTR-010): keep the `docudesk-signing` `/signing-requests` request (`documentId, signatories, signingLevel, returnTarget`) and response (`id`/`signingRequestId`, `signingUrl`) fields backward-compatible (additive only), and add the resolved eIDAS `assuranceLevel` (`low` for native SES) to the completion payload the consumer maps to `resolveSignatureStage()`; Newman contract test against the docudesk-signing collection
  - Verify decidesk's `EIDASSignatureService::composeDocudeskSigningRequest()` expectations still pass; broker-resolved assurance is populated by `signer-identity-rails` (REQ-DDSIR-007) into the same field

## 3. Audit binding + proven immutability

- [ ] 3.1 `SigningAuditService::logEvent()` resolves the real signing-request entity (register `signing`/schema `signingRequest`) with uuid fallback + warning (REQ-DDSTR-006); `getAuditTrail()` switches to an objectUuid-scoped mapper query — verify the REAL AuditTrailMapper filter key at OR HEAD, add the filter OR-side if missing (REQ-DDSTR-007)

- [ ] 3.2 Immutability + retention as verified controls (REQ-DDSTR-008): Newman negative tests (PUT/DELETE on a `docudesk.signing.*` entry → 4xx, chain verify passes after) on Postgres 8080; retention deploy check covers the signing register ≥ 3650 days; deployment guide updated

## 4. Consent oracle + closure

- [ ] 4.1 `ConsentController::errorResponse()` adopts the SigningController oracle-free pattern (REQ-DDSTR-009); Newman parity assertions (byte-identical bodies not-found vs non-owner vs internal); coordinate-only — do NOT touch `_rbac`/`_multitenancy` flags (owned by `multi-tenant-hardening`)

- [ ] 4.2 Playwright e2e `tests/e2e/spec-coverage/signing-trust-rebuild.spec.ts`: create SES request (two signers) → sign both through the UI → COMPLETED with verifiable artifact version → verify page shows `verified`; decline-on-COMPLETED rejected; QES+native creation rejected; external-PDF verify shows `unverifiable`; verify on Postgres (8080), nldesign theme enabled

- [ ] 4.3 i18n: EN source + NL translations for new verdict/reason strings and error messages (keys in English)

- [ ] 4.4 Docs: `docs/features/` signing verification page updated (tri-state semantics, legacy-artifact guidance, screenshots via Playwright MCP); F-13/`features.json` narrative updated per the honest-readiness requirement; close GH #282/#283/#284/#287/#289/#304 with fixing-commit evidence links ONLY AFTER apply + tests pass (#283 jointly with `multi-tenant-hardening`)

- [ ] 4.5 Quality gates: `composer check:strict` green; hydra gates pass; `openspec validate signing-trust-rebuild --strict` exits 0

## Quality checklist

- Fail-closed proven by mutation tests for every new gate (never `verified`/COMPLETED/bytes on a failure path)
- Unit coverage ≥ 75% on changed code; no mock-based shortcuts around the real OR AuditTrailMapper contract
- No secret material in schemas, config dumps or logs (ADR-064 posture)
- Response-shape backward compatibility (`valid`, `isValid`) verified by Newman against the pre-change collection
- No overlap with `multi-tenant-hardening` file edits beyond `ConsentController::errorResponse()`

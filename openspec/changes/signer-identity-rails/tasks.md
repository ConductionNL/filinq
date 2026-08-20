# Tasks: signer-identity-rails

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 14.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & data model

- [ ] 1.1 Additive register edit in `lib/Settings/docudesk_register.json`: `signingRequest.requiredAssurance` (enum low|substantial|high) + `signerRecord.identityEvidence` (object: provider, means, assurance, subjectPseudonym, authenticatedAt, evidenceHash) with register version bump for boot import (REQ-DDSIR-004)
  - Union-additive diff against merge base — no existing property dropped; `tests/validate-manifest.js` passes

- [ ] 1.2 Seed data: demo request `…e001` (`requiredAssurance: substantial`) + signer `…e002` with fixture evidence per design.md Seed Data (nil-UUID pattern, `demo-pseudonym-not-a-bsn-0001`)

## 2. Provider seam

- [ ] 2.1 `lib/Service/SignerAuth/`: `SignerAuthenticationProviderInterface` + strict `SignerAuthProviderFactory` (unknown provider throws, no fallback) + `NextcloudSessionProvider` (assurance low, default) (REQ-DDSIR-001)

- [ ] 2.2 `OidcBrokerProvider`: authorize-URL initiation with state bound to signerId+requestId, server-side code exchange, nonce/aud/iss/exp validation, configurable acr→assurance mapping with documented DigiD/eHerkenning/iDIN defaults and fail-closed `low` for unknown acr (REQ-DDSIR-001/002)

- [ ] 2.3 Credential custody: broker client secret behind `credentialRef` resolved at token-exchange time via the waarmerk custody resolver seam (ADR-011 reuse, ADR-064); admin settings panel (settings framework, NOT vue-router) for issuer/client-id/redirect/acr-mapping/credentialRef (REQ-DDSIR-005)

- [ ] 2.4 Abstract `SignerAuthProviderContractTest` (initiate/complete/fail-closed/means/assurance bounds) run against both shipped providers + the `eudi-wallet` fixture provider (REQ-DDSIR-001/006)

## 3. Enforcement & evidence

- [ ] 3.1 Assurance gate in `SigningService::sign()`/`decline()` after the ownership check: registered provider + `requiredAssurance` met + evidence age ≤ configurable max (default 15 min); 403 with step-up indication, zero mutation on refusal (REQ-DDSIR-003); creation-time floor normalisation SES→low/AdES→substantial/QES→high (REQ-DDSIR-002)

- [ ] 3.2 Evidence recording: persist `identityEvidence` on the signer record, extend the OR audit `changed` context, include the tuple in the v2 artifact assertion (MAC-covered per REQ-DDSTR-001); raw token never stored, evidenceHash only (REQ-DDSIR-004)

- [ ] 3.3 Step-up UI: sign dialog (in `src/modals/`) triggers `initiateAuthentication` on 403 step-up, handles the broker redirect/callback, re-attempts the signature; request form exposes `requiredAssurance` with floor hints (NcSelect with `inputLabel`)

- [ ] 3.4 Surface resolved assurance to consumers (REQ-DDSIR-007): expose the recorded `identityEvidence.assurance` on the `docudesk-signing` completion payload (feeds decidesk `QesGuard`; coordinates with `signing-trust-rebuild` REQ-DDSTR-010) and make it readable by the `portal-signing-actions` `minTrust` gate; surface pseudonym + assurance only, never BSN/raw token

## 4. Quality, i18n, docs

- [ ] 4.1 Unit tests ≥75% on new code incl. minimisation scan (no BSN-like value / raw token in store, audit, logs, artifact) and custody grep; run in container `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`

- [ ] 4.2 Playwright e2e `tests/e2e/spec-coverage/signer-identity-rails.spec.ts` against a throwaway mock-OIDC IdP container: substantial request refuses session-only signer → step-up at digid-substantial → signature accepted → evidence visible on record/audit/artifact; floor normalisation on QES creation; verify on Postgres (8080), nldesign theme enabled

- [ ] 4.3 i18n EN source + NL translations (step-up prompts, assurance labels, admin panel)

- [ ] 4.4 Docs in `docs/features/` (identity rails setup, broker config, assurance floors, EUDI readiness statement with Dec 2026 timeline — no shipped-wallet claim) with Playwright screenshots (ADR-010); `openspec validate signer-identity-rails --strict` passes

## Quality checklist

- Fail-closed everywhere: unknown provider, unmapped acr, stale/missing evidence, sub-floor requiredAssurance
- ADR-064: reviewer grep for secret material in schemas/appconfig/logs/frontend responses
- No orphaned capability: every interface method has a live caller (both shipped providers e2e-exercised); no `eudi-wallet` stub class
- `composer check:strict` green; hydra gates pass (route-auth, no-admin-idor, semantic-auth on the new callback route)
- Depends on `signing-trust-rebuild` (v2 MAC) — do not start apply before it lands

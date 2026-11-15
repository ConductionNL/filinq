# Tasks: signer-identity-rails

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 19.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & data model

- [x] 1.1 Additive register edit in `lib/Settings/filinq_register.json`: `signingRequest.requiredAssurance` (enum low|substantial|high) + `signerRecord.identityEvidence` (object: provider, means, assurance, subjectPseudonym, authenticatedAt, evidenceHash) with register version bump for boot import (REQ-DDSIR-004)
  - Union-additive diff against merge base — no existing property dropped; `tests/validate-manifest.js` passes
  - Built with `guardianRequiredAssurance`, `resolvedAssurance` and `signerEvidence` on `signingRequest` too (used by 3.1, 3.2 and 3.4), so the data model moves once: register 8.19.0, `signingRequest` 1.6.0, `signerRecord` 1.4.0. Test: `tests/unit/Settings/SignerIdentitySchemaTest.php`

- [x] 1.2 Seed data: demo request `…e001` (`requiredAssurance: substantial`) + signer `…e002` with fixture evidence per design.md Seed Data (nil-UUID pattern, `demo-pseudonym-not-a-bsn-0001`)

## 2. Provider seam

- [x] 2.1 `lib/Service/SignerAuth/`: `SignerAuthenticationProviderInterface` + strict `SignerAuthProviderFactory` (unknown provider throws, no fallback) + `NextcloudSessionProvider` (assurance low, default) (REQ-DDSIR-001)

- [x] 2.2 `OidcBrokerProvider`: authorize-URL initiation with state bound to signerId+requestId, server-side code exchange, nonce/aud/iss/exp validation, configurable acr→assurance mapping with documented DigiD/eHerkenning/iDIN defaults and fail-closed `low` for unknown acr (REQ-DDSIR-001/002)

- [x] 2.3 Credential custody: broker client secret behind `credentialRef` resolved at token-exchange time via OpenRegister's credential broker (`resolveInjectable`, ADR-064; the waarmerk resolver is unbuilt, see design D4); admin settings panel (settings framework, NOT vue-router) for issuer/client-id/redirect/acr-mapping/credentialRef (REQ-DDSIR-005)

- [x] 2.4 Abstract `SignerAuthProviderContractTestCase` (initiate/complete/fail-closed/means/assurance bounds) run against both shipped providers + the `eudi-wallet` fixture provider (REQ-DDSIR-001/006)

## 3. Enforcement & evidence

- [x] 3.1 Assurance gate in `SigningService::sign()`/`decline()` after the ownership check: registered provider + `requiredAssurance` met + evidence age ≤ configurable max (default 15 min); 403 with step-up indication, zero mutation on refusal (REQ-DDSIR-003); creation-time floor normalisation SES→low/AdES→substantial/QES→high (REQ-DDSIR-002)
  - Built with the guardian's stronger check (design D6a, REQ-DDSIR-009): a guardian needs the strongest of `requiredAssurance`, `guardianRequiredAssurance` and the admin guardian minimum. Tests: `tests/unit/Service/SignerAuth/SigningAssuranceGateTest.php`, `tests/unit/Service/SigningServiceTest.php`

- [x] 3.2 Evidence recording: persist `identityEvidence` on the signer record, extend the OR audit `changed` context, include the tuple in the v2 artifact assertion (MAC-covered per REQ-DDSTR-001); raw token never stored, evidenceHash only (REQ-DDSIR-004)

- [x] 3.3 Step-up UI: sign dialog (in `src/modals/`) triggers `initiateAuthentication` on 403 step-up, handles the broker redirect/callback, re-attempts the signature; request form exposes `requiredAssurance` with floor hints (NcSelect with `inputLabel`)
  - Built as: the signing folder reports a step-up hint per refused document and offers *Confirm my identity* (`src/modals/SignerStepUpModal.vue`); the broker returns the signer to `/signing/{id}?stepUp=done`, where *Sign now* re-attempts the signature and a second refusal reopens the dialog. There is no single-request sign dialog in the app today, so the folder is where signing starts. Tests: `tests/vitest/signerStepUp.spec.js`, `tests/unit/Service/SigningFolderServiceTest.php`

- [x] 3.4 Surface resolved assurance to consumers (REQ-DDSIR-007): expose the recorded `identityEvidence.assurance` on the `filinq-signing` completion payload (feeds decidesk `QesGuard`; coordinates with `signing-trust-rebuild` REQ-DDSTR-010) and make it readable by the `portal-signing-actions` `minTrust` gate; surface pseudonym + assurance only, never BSN/raw token

## 4. Quality, i18n, docs

- [x] 4.1 Unit tests ≥75% on new code incl. minimisation scan (no BSN-like value / raw token in store, audit, logs, artifact) and custody grep; run in container `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`
  - Run standalone instead (`composer check:strict`, lane rules keep lanes off the container). Minimisation scan: `tests/unit/Service/SignerAuth/EvidenceMinimisationTest.php`; custody grep: `CredentialCustodyTest.php`. Every new class has its own test file; the coverage percentage was not measured locally (no pcov or xdebug), CI's coverage job reports it

- [ ] 4.2 Playwright e2e `tests/e2e/spec-coverage/signer-identity-rails.spec.ts` against a throwaway mock-OIDC IdP container: substantial request refuses session-only signer → step-up at digid-substantial → signature accepted → evidence visible on record/audit/artifact; floor normalisation on QES creation; verify on Postgres (8080), nldesign theme enabled
  - Built: `tests/e2e/spec-coverage/signer-identity-rails.spec.ts` starts its own mock OIDC broker (a Node https server with the fixed test key in `tests/e2e/fixtures/mock-oidc/`), no container. Run on the shared instance 2026-09-28: the floor, the session-only refusal and the front channel of the step-up (folder hint, dialog, authorize request with state, nonce and the substantial acr values, return to Filinq) pass.
  - Blocked: the token exchange is server-side, and Nextcloud refuses it (`LocalServerException`) unless the instance trusts `tests/e2e/fixtures/mock-oidc/test-cert.pem` and allows the back-channel host (`allow_local_remote_servers`). Making those two instance changes needs a person's decision, so the two tests that need them (signature accepted after step-up, evidence on record, audit and artifact) run only with `FILINQ_E2E_OIDC_BACKCHANNEL=1` and are reported as skipped otherwise. They have not run green yet.

- [x] 4.3 i18n EN source + NL translations (step-up prompts, assurance labels, admin panel)

- [x] 4.4 Docs in `docs/features/` (identity rails setup, broker config, assurance floors, EUDI readiness statement with Dec 2026 timeline — no shipped-wallet claim) with Playwright screenshots (ADR-010); `openspec validate signer-identity-rails --strict` passes
  - The section "Signer identity: DigiD, eHerkenning and iDIN" in `docs/features/digital-signing.md` covers setup, broker config, floors and the EUDI statement, and now shows three screenshots from the live instance: `docs/static/screenshots/signer-identity-step-up-hint.png`, `signer-identity-step-up-start.png` and `signer-identity-guardian-minimum.png`

## 5. Guardian consent for signers under the age of consent (D11 amendment)

- [x] 5.1 Register: additive `signerRecord` properties (`role`, `guardianForSignerId`, `guardianAct`, `consentStatement`, `guardianRef`, hidden `birthDate`, `actingIdentity`) and `signingRequest` properties (`guardianConsentAge`, `consentBasis`); bump `signerRecord` 1.2.0 to 1.3.0, `signingRequest` 1.4.0 to 1.5.0, register 8.16.0 to 8.17.0; catalogue keys in `l10n/en.json` and `l10n/nl.json` (REQ-DDSIR-008 to 010)
  - `npm run check:schema-l10n` at or below baseline; `npm run test:l10n` green

- [x] 5.2 Admin setting `signing_guardian_consent_age` (default 16) in `SettingsService` toggles and writable keys, with a number field in the signing section of the admin settings (REQ-DDSIR-008)

- [x] 5.3 `lib/Service/Signing/GuardianConsentGuard.php`, a required dependency of `SigningService`: creation-time validation and guardian-link resolution in `createRequest()`, the act guard in `sign()` before any mutation, the completion guard and `consentBasis` in `updateRequestStatus()` before the artifact is produced (REQ-DDSIR-008, 009)
  - Tests written first and seen red: `tests/unit/Service/Signing/GuardianConsentGuardTest.php`, `tests/unit/Service/SigningServiceGuardianConsentTest.php`

- [x] 5.4 Record: `consentBasis` on the completed request, threaded through `SignedArtifactProducer` into the native assertion before the MAC; guardian link in the minor's and guardian's `SIGNED` audit metadata (REQ-DDSIR-010)
  - `tests/unit/Service/Signing/NativeSigningProviderTest.php` proves the basis sits inside the MAC

- [x] 5.5 Consumer contract (REQ-DDSIR-011): signer-entry fields documented on `DocumentSigningRequestedEvent` and in `docs/features/digital-signing.md`, naming learniq OPP and POK and portaliq toestemmingsformulieren
  - The learniq switch to the delegated event is a learniq change, not a task here

## Quality checklist

- Fail-closed everywhere: unknown provider, unmapped acr, stale/missing evidence, sub-floor requiredAssurance
- ADR-064: reviewer grep for secret material in schemas/appconfig/logs/frontend responses
- No orphaned capability: every interface method has a live caller (both shipped providers e2e-exercised); no `eudi-wallet` stub class
- `composer check:strict` green; hydra gates pass (route-auth, no-admin-idor, semantic-auth on the new callback route)
- Depends on `signing-trust-rebuild` (v2 MAC) — do not start apply before it lands

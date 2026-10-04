## Scope

First of three stacked PRs that build the core of the open change `signer-identity-rails`. This one adds the provider seam, the assurance scale, the OIDC broker with its custody and the register fields; the gate that uses them in `sign()` and `decline()` follows in the next PR.

Closes tasks **1.1, 1.2, 2.1, 2.2, 2.3, 2.4** of `openspec/changes/signer-identity-rails/tasks.md` (ticked there). REQ-DDSIR-001, 002 (mapping and floors), 004 (register side), 005 and 006.

## What changed

- `lib/Service/SignerAuth/`: `SignerAuthenticationProviderInterface`, a strict `SignerAuthProviderFactory` (an unknown provider throws and names itself, no fallback), `NextcloudSessionProvider` (assurance `low`, the default, so nothing changes until an admin configures more) and `OidcBrokerProvider`.
- The broker runs the authorization code flow. State and nonce sit in the signer's own session, bound to request, signer and user, single use. The code is exchanged server-side and the ID token's `iss`, `aud`/`azp`, `exp`, `iat`, `nonce` and `sub` are checked. `acr` maps to assurance with documented DigiD and eHerkenning defaults; an unknown `acr` is `low`.
- `sub` is always hashed with a key derived from the instance secret, so no stored pseudonym can hold a BSN. The raw token is never kept, only its sha256.
- Custody (ADR-064): the client secret is resolved at exchange time through OpenRegister's `CredentialBrokerService::resolveInjectable()`. Filinq stores only the credential UUID and refuses anything else in that field. Design D4 records why this replaces the unbuilt waarmerk resolver.
- Admin panel: a "Signer identity" section on the admin settings page with its own admin-only endpoint (`#[AuthorizedAdminSetting]`), not a vue-router route.
- Register 8.19.0: `signingRequest` 1.6.0 gains `requiredAssurance`, `guardianRequiredAssurance`, `resolvedAssurance`, `signerEvidence`; `signerRecord` 1.4.0 gains `identityEvidence`. Additive only. Demo request `…e001` and signer `…e002` with fixture evidence.
- The contract suite (`SignerAuthProviderContractTestCase`) runs against both providers and an `eudi-wallet` fixture that registers through the factory with no core change. No wallet class ships.

## Tests written first

Red run at 18:09: 72 tests, 40 errors (the provider, factory, settings, resolver and controller classes did not exist) and 18 failures (register fields and seed absent). Now: 179 green across `tests/unit/Service/SignerAuth`, the settings controller test and `tests/unit/Settings`.

## Verified

- `composer check:strict` (TMPDIR outside the checkout, lane-local HOME): exit 0, ALL CHECKS PASSED, PHPUnit 2593 tests, 3 skipped.
- `npm run lint`: exit 0 (0 errors, 152 warnings, the base count). `npm run format`: exit 0. `npm run test:l10n`: OK. `npm run check:schema-l10n`: 213 uncovered, equal to the baseline. `node tests/validate-manifest.js`: PASS.
- `openspec validate signer-identity-rails --strict`: valid.
- Hydra gates (`--scope-to-diff`, vendored `HYDRA_GATES_HOME`): exit 1, 64 of 64 applicable gates ran, only gate 101 red.

## Inherited, not fixed

Gate 101 names 15 schemas without demo data (`archiveJob`, `documentRegistration`, `downloadAgreement`, `erasureCertificate`, four intake schemas, `mergeJob`, `pageLayout`, `periodicDocument`, `redactionReviewMark`, `scanBatch`, `subjectErasureRequest`, `uploadPolicy`); the same set fails on `origin/development`, and this PR touches none of them.

## Notes for landing

- The providers get their live caller in the next PR (the gate and the step-up endpoints), which is stacked on this branch.
- Test support: `tests/stubs/NextcloudStubs.php` gains `IClient::post()` (the real OCP signature) and the unit bootstrap loads OCP's `ISession` and `ISecureRandom`.
- Not done anywhere in this stack: task 4.2 (Playwright against a mock OIDC container on the shared instance), because lane rules keep lanes off the instance and off containers.

🤖 Generated with [Claude Code](https://claude.com/claude-code)

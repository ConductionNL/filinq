# Lane f-filinq log (follow-up round, 2026-09-28)

Clone: /home/rubenlinde/memcap-work/lq-lanes/fq. Untracked, never staged.
Task: build the unbuilt core of `signer-identity-rails` (14 tasks, 1.x to 4.x).

## Plan (three stacked PRs)
- PR1 `feat/signer-identity-assurance` (from origin/development 072f15b1): 1.1, 1.2, 2.1, 2.2, 2.3, 2.4. Register fields, seed, provider seam, OIDC broker, custody via OR credential broker, admin panel, contract suite.
- PR2 (stacked on PR1): 3.1 (gate incl. guardian floor), 3.2, 3.4, 4.1. Step-up initiate/callback endpoints.
- PR3 (stacked on PR2): 3.3 UI, 4.3 i18n, 4.4 docs. 4.2 Playwright stays open (lane rules: no instance, no containers).

## PR1
- deps: composer install 0, npm ci 0 (vendor and node_modules were gone).
- Red run 2026-09-28T18:09: 72 tests, 40 errors (classes missing: OidcBrokerProvider, NextcloudSessionProvider, SignerAuthProviderFactory, SignerIdentitySettingsController, BrokerCredentialResolver, SignerAuthSettings), 18 failures (schema fields and seed absent). Value types (AssuranceLevel, IdentityEvidence, context, challenge, interface) existed at red time.
- Green: 179 tests in the new suites. check:strict exit 0 (2593 tests, 3 skipped). lint 0, format 0, test:l10n OK, schema-l10n 213 = baseline, validate-manifest PASS, openspec validate --strict valid.
- Gates: first run gate 16 red (Vue methods without @spec, fixed in 6fd9454a); rerun exit 1, 64/64 applicable, only gate 101 (inherited 15 schemas).
- Commits 7c3f94d1, 6fd9454a. Pushed. PR https://github.com/ConductionNL/filinq/pull/1237. Tasks closed: 1.1 1.2 2.1 2.2 2.3 2.4.

## PR2 feat/signer-identity-gate (stacked on PR1)
- Red run 2026-09-28T18:58: 38 new tests red (32 errors: gate, store, step-up service, controller, exception classes missing; 6 failures: no step-up refusal, no signerEvidence in the assertion, no resolvedAssurance in the payload, no floor on creation).
- Green: full PHPUnit 2631. check:strict exit 0. lint 0, format 0, test:l10n OK, openspec valid. phpmd findings fixed (sign() length, Native complexity); two reasoned suppressions.
- Gates: gate 7 flagged callback, renamed finish -> authorizeCallback (3b5053a7); rerun exit 1, only gate 101 inherited.
- Commits abda45e5, 3b5053a7. PR https://github.com/ConductionNL/filinq/pull/1238 (stacked on 1237). Tasks closed 3.1 3.2 3.4 4.1.

## PR3 feat/signer-identity-step-up-ui (stacked on PR2)
- Red run 2026-09-28T19:23: SigningFolderServiceTest step-up case fails (no hint), vitest signerStepUp.spec.js fails (module missing).
- Green: check:strict exit 0 (2632), vitest 80/80, jest 77 (= base), lint 0, format 0, l10n checks OK, openspec valid.
- Gates: gate 16 (two dialog methods) fixed in 6b58fcc2; rerun exit 1, only gate 101 inherited.
- Commits 3158f958, 6b58fcc2. PR https://github.com/ConductionNL/filinq/pull/1239 (stacked on 1238). Tasks closed 3.3 4.3; 4.4 partly (docs text, no screenshots).

## opsx-verify (headless, full stack)
- Completeness: 17 of 19 tasks ticked (12 of the 14 core + 5.x). Open: 4.2 (Playwright + mock OIDC: no instance/containers for lanes), 4.4 screenshots (same reason). Archive blocked until they land.
- Correctness: REQ-DDSIR-001 to 007 implemented; every PHPUnit file the spec names exists. Four scenarios point at tests/e2e/spec-coverage/signer-identity-rails.spec.ts, which does not exist (task 4.2).
- Coherence: design D1-D6 followed; D4 amended (OR credential broker instead of the unbuilt waarmerk resolver), D6a added, guardian open question resolved.
- Frontend gates 10-13: modal in src/modals, NcSelect with inputLabel, no router admin, no DOM reads.
- Status: DONE. Nothing merged. CI not read (PRs just opened).

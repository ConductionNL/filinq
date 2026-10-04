Adds the Playwright spec for the four signer-identity-rails scenarios that point at it (task 4.2) and the three docs screenshots (task 4.4). The spec starts its own mock OIDC broker, a small Node https server with a fixed test key in `tests/e2e/fixtures/mock-oidc/`, and never uses a container.

**What runs, and what does not yet**

| Scenario | Result on the shared instance (2026-09-28) |
|---|---|
| Request cannot undercut its level floor | passes |
| Substantial request refuses a session-only signer | passes (403, `stepUp`, signer and request unchanged) |
| Step-up, front channel (folder hint, dialog, authorize with state, nonce, substantial acr values, return to Filinq) | passes |
| Step-up unlocks the signature | skipped |
| Evidence lands on record, audit and artifact | skipped |

The last two need the server-side token exchange. Nextcloud refuses it with `LocalServerException` unless the instance trusts `tests/e2e/fixtures/mock-oidc/test-cert.pem` and allows the back-channel host (`allow_local_remote_servers`). Changing those two instance settings needs a person to decide, so this PR does not make them, not on the dev instance and not in `ci-seed.sh`. The two tests run only with `FILINQ_E2E_OIDC_BACKCHANNEL=1` (plus `FILINQ_E2E_OIDC_BACKCHANNEL_HOST=host.docker.internal` on the docker dev box). Without it they show as skipped, with the reason, not as passed. Task 4.2 stays open for that reason, so the change is not archived in this PR.

**Screenshots** (browser-2, live instance, filinq on development): `docs/static/screenshots/signer-identity-step-up-hint.png`, `signer-identity-step-up-start.png`, `signer-identity-guardian-minimum.png`, referenced from `docs/features/digital-signing.md`.

**Verified**
- `npx playwright test tests/e2e/spec-coverage/signer-identity-rails.spec.ts --project chromium` against http://localhost:8080: exit 0, 3 passed, 2 skipped
- `npx openspec validate signer-identity-rails --strict`: valid
- `npm run lint`: exit 0 (152 warnings, none in the new file)
- `npm run format`: exit 0
- `npm run test:l10n`: exit 0
- `run-hydra-gates.sh`: exit 0 (gate-16 and gate-101 not applicable without a delta base)
- `composer check:strict`: not run, this PR changes no PHP

**Inherited, not fixed here:** `ci-seed.sh` lists schemas with `_limit=1000`, so on a crowded instance it reports filinq schemas as missing and binds nothing (the signing bindings had to be set by hand on the dev instance); and the admin settings page throws a transient `Cannot read properties of undefined (reading 'selectedRegister')` while it loads, before `sections` is filled.

The fixture key is a throwaway test key for the mock broker, used for its TLS and to sign its ID tokens; it grants access to nothing.

🤖 Generated with [Claude Code](https://claude.com/claude-code)

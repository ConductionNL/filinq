## Scope

Third of three stacked PRs for the open change `signer-identity-rails`. A signer who needs a stronger identity now confirms it from the signing folder and signs; the request form asks for the identity level; the docs explain how to connect a broker.

**Stacked on #1238**, which is stacked on #1237. Land in that order. This PR's own commits are 3158f958 and 6b58fcc2.

Closes tasks **3.3 and 4.3** of `openspec/changes/signer-identity-rails/tasks.md` (ticked). Task 4.4 is partly done (see below).

## What changed

- `SigningFolderService`: a document refused for weak identity comes back with the step-up hint and the signer record id.
- Signing folder: such a result shows *Confirm my identity*, which opens `src/modals/SignerStepUpModal.vue`. The dialog starts the configured provider and sends the browser to the broker.
- Request page: back from the broker (`/signing/{id}?stepUp=done`), the signer sees *Sign now*, which tries the signature again; a second refusal reopens the dialog. A failed step-up lands on the folder with a warning.
- Request form: *Identity check for signers*, an `NcSelect` with `inputLabel` that offers only the levels at or above the signature level's floor and says which floor applies.
- The flow's logic lives in `src/services/signerStepUp.js` so it can be tested without a DOM.
- Dutch catalogue entries for every new string.
- Docs: a section "Signer identity: DigiD, eHerkenning and iDIN" in `docs/features/digital-signing.md`: floors, what reaches which level, connecting a broker with a credential reference, the step-up, the guardian level, what is recorded, and the EUDI wallet timeline (December 2026) stated as a plugin target, not a shipped integration.

There is no single-request sign dialog in the app today, so the step-up starts where signing starts: the signing folder.

## Tests written first

Red run at 19:23: the folder service step-up case failed (no hint) and `tests/vitest/signerStepUp.spec.js` failed (module missing); the two floor tests were added and seen red before the floor helpers existed.

## Verified

- `composer check:strict`: exit 0, ALL CHECKS PASSED, PHPUnit 2632 tests, 3 skipped.
- `npx vitest run`: 12 files, 80 tests passed. `npx jest`: 7 suites, 77 tests, same as base.
- `npm run lint` exit 0 (0 errors), `npm run format` exit 0, `npm run test:l10n` OK, `npm run check:l10n-js` up to date, `npm run check:schema-l10n` 213 = baseline.
- `openspec validate signer-identity-rails --strict`: valid.
- Hydra gates (`--scope-to-diff`, vendored `HYDRA_GATES_HOME`): exit 1, 64 of 64 applicable ran; gate 16 flagged two dialog methods first (fixed in 6b58fcc2); only gate 101 remains.

## Inherited, not fixed

Gate 101: the same 15 schemas without demo data as on `origin/development` (listed in #1237).

## Left open, with the reason

- **4.2** Playwright e2e against a throwaway mock-OIDC IdP on the Postgres instance: lane rules keep follow-up lanes off the shared instance and off containers. Every scenario it would cover has a PHPUnit or vitest test in this stack.
- **4.4** screenshots (ADR-010): need the same live instance. The docs text is in this PR.

🤖 Generated with [Claude Code](https://claude.com/claude-code)

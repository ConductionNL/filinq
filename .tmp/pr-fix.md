Fixes three defects found while running the signer identity e2e (#1245), each with a test written first and seen red. Nothing here changes PHP.

- **ci-seed.sh read only the first 1,000 schemas.** OpenRegister caps a page and returns no total, so on the shared dev instance (1,419 schemas) the seed reported filinq's own schemas missing and bound nothing. The new `tests/e2e/lib/fetch-all-pages.sh` pages with `_offset` until a short page, for registers and schemas both. Test: `tests/vitest/ciSeedPaging.spec.js` (a fake OpenRegister with 2,345 schemas and a 1,000 cap). Live: the seed's verification now passes on :8080 ("register/schema verification OK (1 registers, 39 schemas)"), where it failed before.
- **The admin settings page threw `Cannot read properties of undefined (reading 'selectedRegister')` on every load**, because `sections` started as `{}` while the template read `sections[type]`. It now starts from `initialSections(OBJECT_TYPES)` (`src/services/settingsSections.js`). Test: `tests/vitest/settingsSections.spec.js`.
- **The step-up dialog showed its title twice**, once through NcModal's `name` in the header and once as its h2. The h2 stays, and the dialog is named after it through `labelId`, so its accessible name is unchanged. Test: `tests/vitest/signerStepUpModal.spec.js`.

**Verified**
- `npx vitest run`: exit 0, 16 files, 89 tests
- `npm run lint`: exit 0 (warnings only, 4 inherited in Settings.vue JSDoc)
- `npm run format`: exit 0
- `npm run test:l10n`: exit 0
- `run-hydra-gates.sh`: exit 0
- `composer check:strict`: not run, no PHP changed

**Inherited, not fixed:** off CI the seed's binding step needs a local `occ` and stops with "occ not found" on the docker dev box; that is by design and unchanged.

🤖 Generated with [Claude Code](https://claude.com/claude-code)

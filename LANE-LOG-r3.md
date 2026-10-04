# Lane r3-filinq log (round 3, 2026-09-27)

Clone: /home/rubenlinde/memcap-work/lq-lanes/fq (ConductionNL/filinq, local name docudesk).
Not committed: this file is a lane artifact and stays untracked.

## Change: signer-identity-parental-consent (amends open change `signer-identity-rails`, D11)

- Branch: `feat/signer-identity-parental-consent`, cut `--no-track` from `origin/development` at 2088cc1f.
- Round 1 item closed: `filinq-esignature-opp-consent` (change-plan row, MUST, L), folded in under D11.
- Status: implementation done, diff-scoped checks green; full strict run, push and PR pending (see below).

### Dedupe (27 Sep, subagent over origin/development of learniq a84b6273, portaliq db4a6cf, filinq 2088cc1f)
- No spec in learniq, portaliq or filinq gates a signature on age or defines a guardian co-signature.
- Neighbours cited in the proposal: learniq "Guardian consent gates a minor's subject-choice submission", learniq "Parent co-signs are verified against the learner's profile found on ncUserId" (open change), portaliq "An activity MUST be able to require a guardian's consent, recorded on the sign-up" (open change activity-parental-consent).
- "consent record" already names Woo publicationConsent in filinq, so the amendment speaks of a guardian consent act.
- learniq OPP and POK do not call filinq today (own Signature/PokSignature records); switching them to DocumentSigningRequestedEvent is a learniq change, recorded as out of scope.

### OpenSpec
- Amended in place: proposal.md (D11 section, corpus evidence rows 8.3 rung 1 tier A, out-of-scope items), design.md (D7 + two open questions), spec.md (REQ-DDSIR-008 to 011, 12 scenarios, all @e2e exclude with named PHPUnit files), tasks.md (section 5, 5.1 to 5.5 ticked; cap 19 of 20).
- `openspec validate signer-identity-rails --strict`: exit 0.

### Tests written first (red run recorded 2026-09-27T20:07)
- tests/unit/Service/Signing/GuardianConsentGuardTest.php: 28 errors (class not found).
- tests/unit/Service/SigningServiceGuardianConsentTest.php: 6 errors (class not found).
- NativeSigningProviderTest::testProduceSignedArtifactBindsConsentBasisIntoMac: failed, assertion carried no consentBasis.
- PortalSigningReceiverControllerTest::testAGuardianConsentRefusalReturns403: failed, 502 instead of 403.
- SettingsServiceTest::testTheGuardianConsentAgeIsAWritableSettingThatDefaultsToSixteen: written with the change, proven red by stashing SettingsService.php (null instead of '16').

### Code
- New: lib/Service/Signing/GuardianConsentGuard.php (facade, act guard), GuardianAgePolicy.php, GuardianEntryPreparer.php, ConsentBasisBuilder.php (split to stay under phpmd ExcessiveClassComplexity 50).
- SigningService: guard is a REQUIRED constructor dependency; createRequest validates and links before any write, persistSigners saves guardians last; sign() guards before mutation and merges audit metadata; updateRequestStatus sets consentBasis before the artifact.
- SignedArtifactProducer + NativeSigningProvider: consentBasis folded into the assertion before the MAC.
- PortalSigningReceiverController: code-403 refusal answers 403 signing_refused, not 502, no throttler count.
- SettingsService + Settings.vue: signing_guardian_consent_age (default 16), writable, admin number field.
- Register 8.17.0: signerRecord 1.3.0 (+7 props), signingRequest 1.5.0 (+2 props); l10n en/nl +19 keys, l10n js rebuilt.
- Docs: docs/features/digital-signing.md section; CHANGELOG Unreleased entry; event docblock names the consumer fields.

### Diff-scoped checks (exit codes)
- php -l touched files: 0
- phpcs (phpcs.xml, lib scope) on 9 touched lib files: 0 errors; 16 warnings in DocumentSigningRequestedEvent.php, identical on base (inherited).
- phpmd (both rulesets, isolated HOME) on touched files: 0. Two NEW findings were found and fixed on the way: SettingsService::loadFeatureToggles 106 lines, SigningService WMC 51. Inherited: NativeSigningProvider::initiateSigning unused $options (present on base).
- phpstan on touched files: 0 ([OK] No errors).
- psalm on touched files: 0 (No errors found).
- phpunit: GuardianConsentGuardTest 28/28, SigningServiceGuardianConsentTest 6/6, NativeSigningProviderTest 10/10, SigningServiceTest 32/32, SigningServiceMandateGuardTest 2/2, SettingsServiceTest 9/9, PortalSigningReceiverControllerTest 16/16, tests/unit/Settings 105/105.
- eslint Settings.vue: 0 errors (4 warnings, identical on base); prettier --check: 0; stylelint: 0.
- npm run test:l10n 0; check:l10n-js 0; check:schema-l10n 0 (761 strings, 213 uncovered = baseline).

### Commits and push
- 785ffb6c feat(signing): the change. d3df3ed8 test(signing): listener contract pin for REQ-DDSIR-011.
- Pushed: `git push -u origin feat/signer-identity-parental-consent` succeeded, confirmed with ls-remote.
- No Co-Authored-By, no Signed-off-by (checked with grep on the message).

### Repo-wide front-end checks
- npm run lint: exit 0 (152 warnings, 0 errors; same count the previous lane recorded on base).
- npm run format: exit 0.

### opsx-verify (headless, scoped to REQ-DDSIR-008 to 011 and tasks 5.1 to 5.5)
- Completeness: 5/5 amendment tasks done. 14 tasks of the rails core (1.x to 4.x) stay open by design; archive is blocked until they land.
- Correctness: 4/4 requirements implemented, 13/13 scenarios mapped to named PHPUnit tests.
- Coherence: design D7 followed (required guard, validate before write, guardians saved last, 403 before mutation, basis before artifact, schema versions as stated).
- CRITICAL: none in scope. WARNING: REQ-DDSIR-009 names the REQ-DDSIR-003 assurance gate, which is task 3.1 of the unbuilt core; recorded under "not done". SUGGESTION: ValidSign embeds no assertion, so only the native artifact carries the basis; the three helper classes are tested through the facade, not in their own files; no screen shows the consent basis yet.
- Frontend gates 10 to 13 on the src diff: no hits. No API or browser tests (lane rules keep lanes off the :8080 instance).

### Setup trap met (and fixed) on the first strict run
- Overriding HOME (for a lane-local ~/.pdepend) broke the `composer` wrapper, which runs $HOME/.local/share/composer.phar: "Could not open input file". Nothing was checked; that run is not a result. Fixed with a symlink to the real phar in .tmp/home/.local/share/ and COMPOSER_HOME pointed at the real one.

### Full checks (resumed 2026-09-28 after the account-limit stop)
- 4c0c4a16 test(signing): withConsentBasis direct test (committed before the stop, pushed after).
- composer check:strict (with-slot, TMPDIR/HOME lane-local): exit 0, ALL CHECKS PASSED; six sections ran; PHPUnit 2453 tests, 7834 assertions, 3 skipped.
- Hydra gates run 1 (default resolver): exit 2. Gates 53 and 68 crashed in the shared apps-extra/.github package (Node treats it as ESM via server/package.json "type": "module"): environment, not a finding.
- Hydra gates run 2 (HYDRA_GATES_HOME=vendor/conduction/hydra-gates/hydra-gates): exit 1, 64 of 64 applicable ran, only gate 101 red.
- Gate 101 control: the same checker on origin/development's register (with appinfo/info.xml; without it the checker reads 0 schemas and "passes") gives the identical 15-schema failure set out of 39. Inherited; none of the 15 touched.

### PR
- https://github.com/ConductionNL/filinq/pull/1215 (base development, head 4c0c4a16, MERGEABLE).
- CI read once at open: 35 pending, 2 skipping. Not polled further.
- Status: DONE for this lane. Nothing merged.

### For the orchestrator
- PR #1203 (feat/filinq-configurable-report-templates, previous lane) commits LANE-LOG.md at the repo root in 11ccf0ee. Not touched here; it should come out before landing.
- Both #1203 and this change edit lib/Settings/filinq_register.json (different schemas; this one bumps info.version 8.16.0 to 8.17.0, #1203 does not bump it). Land in either order; the second needs a trivial merge.

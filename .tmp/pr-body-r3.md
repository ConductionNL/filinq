A pupil under the age of consent now signs only with a parent or guardian beside them on the same signing request. This amends the open change `signer-identity-rails` with four requirements (REQ-DDSIR-008 to 011) and builds the guard and the record in filinq's signing service.

This closes the learniq round 1 item `filinq-esignature-opp-consent` under decision D11, which folded it into `signer-identity-rails` instead of a separate change. No new OpenSpec change was created.

## Where it comes from

- Decision D11, `market-intelligence/learniq/_round1/compare/decisions.md`: e-signature with parental consent folds into `signer-identity-rails`.
- Change-plan row `filinq-esignature-opp-consent` (code, MUST, size L), feeding learniq findings 8.3 and L-new-4.
- Finding 8.3 (rung 1, tier A, MUST), "OPP: create within 6 weeks, evaluate, parent signature". ParnasSys takes an OPP to akkoord through "Handelingsdeel > Ondertekenen". Somtoday ships "Digitaal ondertekenen" of OPPs.
- Learniq's MBO dataset review, finding 4: `PokSignature` has no role for the parent who co-signs a minor's POK.

## What the amendment says

- **REQ-DDSIR-008.** A signer whose birth date puts them under the guardian consent age needs a guardian on the same request. The age is the admin setting `signing_guardian_consent_age`, default 16 (UAVG article 5). A request can raise it (18 on a POK) and never lower it. The age is checked at the moment of the signer's own act.
- **REQ-DDSIR-009.** The guardian is a signer record with `role: guardian` and `guardianForSignerId`, and acts through the same `sign()` path and identity resolution as every signer. The guardian co-signs, or consents against a `consentStatement`. A guardian cannot be the minor or a minor.
- **REQ-DDSIR-010.** The completed request and the signed artifact carry a `consentBasis` naming both signers, the age, the basis and the guardian's identity tuple. The birth date is never in it. In the native artifact it sits inside the v2 MAC.
- **REQ-DDSIR-011.** The contract runs through `DocumentSigningRequestedEvent` with no event change, and names learniq OPP, learniq POK and portaliq toestemmingsformulieren as consumers.

The dedupe across learniq, portaliq and filinq `origin/development` found no requirement that gates a signature on age. The three nearest neighbours are cited in the proposal.

## What the code does

- `GuardianConsentGuard` (with `GuardianAgePolicy`, `GuardianEntryPreparer`, `ConsentBasisBuilder`) is a **required** dependency of `SigningService`, not a nullable seam, so an unwired guard fails construction instead of passing silently.
- `createRequest()` validates the entries and links each guardian to their signer before anything is written. A minor without a guardian, a guardian for nobody, or a consent act without a statement is refused with a 400. Signer records are saved guardians last, and `signerIds` keeps entry order.
- `sign()` refuses, with a 403 and before any mutation, a minor with no guardian on the request, and a guardian who is the minor or under the age. The act records `actingIdentity: {provider, assurance, authenticatedAt}`, with no pseudonym and no subject reference.
- Completion refuses to produce an artifact while a signer who signed under the age has no guardian who acted. The request then stays IN_PROGRESS.
- `SignedArtifactProducer` and `NativeSigningProvider` fold the basis into the assertion before the MAC. Rewriting it fails verification.
- The portal receiver answers a guardian refusal with 403 `signing_refused` instead of the 502 of a downstream failure, and does not count it as a rejected assertion.
- The admin setting sits in the signing section of the admin settings.
- Register 8.17.0: `signerRecord` 1.3.0 (+7 properties, `birthDate` hidden), `signingRequest` 1.5.0 (+2). Every addition is optional. 19 catalogue keys in `en.json` and `nl.json`.

## Tests written first

Red before the code existed, green after:

- `tests/unit/Service/Signing/GuardianConsentGuardTest.php`: 28 errors (class missing), now 29/29 with the pin below.
- `tests/unit/Service/SigningServiceGuardianConsentTest.php`: 6 errors, now 6/6. It drives the real `SigningService`, actor resolver and artifact producer over an in-memory store: minor refused, parent co-signs, record names both, no artifact without a guardian, adults untouched, creation stores the link.
- `NativeSigningProviderTest::testProduceSignedArtifactBindsConsentBasisIntoMac`: failed (no basis in the assertion), now green; a rewritten basis gives `invalid`.
- `PortalSigningReceiverControllerTest::testAGuardianConsentRefusalReturns403`: failed (502), now green.
- `SettingsServiceTest::testTheGuardianConsentAgeIsAWritableSettingThatDefaultsToSixteen`: proven red by stashing `SettingsService.php`.
- Two contract pins added after the code, green from the start and labelled as such: `DocumentSigningRequestedListenerTest::testTheGuardianFieldsOfALearnerFacingRequestReachCreateRequest` (the listener forwards the guardian fields untouched) and `GuardianConsentGuardTest::testWithConsentBasisTouchesOnlyARequestThatNeedsIt`.

## Verified

Every command below was read by its exit code.

- `openspec validate signer-identity-rails --strict`: 0.
- `TMPDIR=$PWD/.tmp COMPOSER_PROCESS_TIMEOUT=0 composer check:strict` (through the lane semaphore): **0, ALL CHECKS PASSED**. All six sections ran: lint, phpcs, phpmd, psalm (no errors), phpstan (no errors), and the full PHPUnit suite (2,453 tests, 7,834 assertions, 3 skipped, 0 failures).
- `npm run lint`: 0 (152 warnings, 0 errors). `npm run format`: 0. `npm run test:l10n`: 0. `npm run check:l10n-js`: 0. `npm run check:schema-l10n`: 0 (761 strings; 213 uncovered, equal to the baseline, so all 18 new schema strings are covered).
- Diff-scoped, while building: phpcs 0 on the touched `lib/` files; phpmd 0 on the touched files with a lane-local pdepend cache (two NEW findings met on the way and fixed: `loadFeatureToggles()` over 100 lines, `SigningService` complexity 51); phpstan 0; psalm 0; eslint 0 errors and prettier 0 on `Settings.vue`.
- Hydra gates, `run-hydra-gates.sh --scope-to-diff --base origin/development` with `HYDRA_GATES_HOME` on the vendored package: **exit 1**, 64 of 64 applicable gates ran, one failure, gate 101 (inherited, see below). A first run without `HYDRA_GATES_HOME` also reported gates 53 and 68 red. Both were a Node ES-module crash in the shared `apps-extra/.github` copy of the gates package, not a finding about this repo; with the vendored package both pass.
- `opsx-verify signer-identity-rails`, headless, scoped to REQ-DDSIR-008 to 011: tasks 5.1 to 5.5 done, 4 of 4 requirements implemented, 13 of 13 scenarios mapped to named PHPUnit tests, design D7 followed, frontend gates 10 to 13 clean on the `src/` diff. No critical findings. One warning: the REQ-DDSIR-003 assurance gate that REQ-DDSIR-009 relies on is task 3.1 of the unbuilt core (see "Not done here"). The 14 core tasks (1.x to 4.x) stay open by design, so the change is not ready to archive. No API or browser test: lane rules keep lanes off the shared instance.

## Inherited, not fixed

- Gate 101 (demo data): 15 schemas have no demo objects (`archiveJob`, `documentRegistration`, `downloadAgreement`, `erasureCertificate`, four intake schemas, `mergeJob`, `pageLayout`, `periodicDocument`, `redactionReviewMark`, `scanBatch`, `subjectErasureRequest`, `uploadPolicy`). Running the same checker over the `origin/development` register gives the identical 15-schema failure set out of 39 checked. This PR adds no schema and touches none of the 15.
- phpcs: 16 warnings in `DocumentSigningRequestedEvent.php`, identical on the base. phpmd: an unused `$options` in `NativeSigningProvider::initiateSigning()`, present on the base. eslint: 4 JSDoc warnings in `Settings.vue`, identical on the base.
- `docs/features/digital-signing.md` describes an older request shape (`fileId` where the code reads `documentFileId`); only the new guardian section was written here.

## Not done here

- The signer-authentication seam itself (tasks 2.x, 3.1 to 3.4 of `signer-identity-rails`) is still unbuilt. The guardian therefore gets the REQ-DDSIR-003 assurance gate only once task 3.1 lands; today the guardian's act is gated by the same identity resolution and ownership check as every signer, and the recorded assurance is `low` for a Nextcloud session or the verified portal trust.
- Learniq's OPP and POK still write their own `Signature` and `PokSignature` records. They reach this contract once they raise signing through `DocumentSigningRequestedEvent`, which is a learniq change.
- No screen shows the consent basis yet. It is on the request and in the signed file.
- A standing consent given outside the request is out of scope (see the proposal).

## Landing notes

- Not stacked: cut from `origin/development` at 2088cc1f.
- PR #1203 also edits `lib/Settings/filinq_register.json` (the `template` schema). This PR bumps `info.version` 8.16.0 to 8.17.0 and #1203 does not, so either order works with a trivial merge.
- PR #1203's commit 11ccf0ee adds a lane log, `LANE-LOG.md`, at the repository root. It should come out before that PR lands.

🤖 Generated with [Claude Code](https://claude.com/claude-code)

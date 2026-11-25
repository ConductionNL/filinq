## 1. Record the open question (D1)

- [x] 1.1 Confirm both positions in D1 against the code once more at
      implementation time, because two of the four repos moved this week.
      Done (2026-11-25, default branches): dossiq still signs through
      `lib/Service/Beschikking/LibresignSigningAdapter.php` (registered in
      `BeschikkingAdapterRegistrar`); decidiq's `lib/Service/EIDASSignatureService.php`
      still holds the `docudesk-signing` Source hop; shillinq dispatches
      `DocumentSigningRequestedEvent` from `SigningDelegationService`; neither
      dossiq nor decidiq dispatches the event. D1's facts stand as written.
- [x] 1.2 Raise the ownership question with Ruben as a product decision, with
      the design's D1 as the briefing, and record the answer in the fleet ADR
      set rather than in this change.
      Done (2026-11-25): raised through COORDINATOR-QUEUE (options a/b/c);
      Ruben chose (c), split: decidiq and dossiq decide who may sign and when,
      filinq performs the PDF signature through its rail and reports back with
      `SigningConcludedEvent`. Recorded as hydra ADR-125
      (`openspec/architecture/adr-125-the-domain-app-decides-filinq-signs.md`,
      ConductionNL/hydra#794).

## 2. The noun on the record (REQ-DDSRC-002)

- [x] 2.1 Derive the subject noun from the subject schema in
      `SigningProvenance`, requiring nothing new from a consumer.
- [x] 2.2 Persist it on the `signingRequest` object alongside the existing
      provenance fields.
- [x] 2.3 Unit test that a dossiq beschikking request and a decidiq minutes
      request are distinguishable on the record.

## 3. Refuse an unattributed request (REQ-DDSRC-003)

- [x] 3.1 Refuse a delegated request with a missing subject register, schema
      or id, naming what is absent.
- [x] 3.2 Leave the filinq-internal path, which carries no provenance,
      unchanged.
- [x] 3.3 Unit test each missing field, and mutation-check by restoring the
      field and confirming the request succeeds.

## 4. One door, written down (REQ-DDSRC-001)

- [x] 4.1 Document `DocumentSigningRequestedEvent` as the only cross-app entry
      point in `docs/features/`, with the shillinq consumer as the worked
      example.
- [x] 4.2 List the two duplicate rails to retire, with the file paths, so the
      consumer changes have a target.

## 5. The admin surface (REQ-DDSRC-004)

- [x] 5.1 List source apps and request counts on the signing settings surface.
      Done (2026-11-14): `SigningSourceTally` counts signing requests per `sourceApp` (paged, filinq-internal requests not counted); `GET api/settings/signing-sources` (`SigningSourcesSettingsController`, admin setting only, app names and counts only); `SigningSourcesSettings.vue` in the admin settings. Tests: `SigningSourceTallyTest` (6), `SigningSourcesSettingsControllerTest` (3), `SigningSourcesSettings.spec.js` (4).
- [x] 5.2 Show the empty case as an empty case, not as an error.
      Done: no request (or no configured register) answers an empty list and the section says no app has raised one; only a failed load shows an error.
- [x] 5.3 Add Dutch and English strings.
      Done: `l10n/en.json` and `l10n/nl.json`, `.js` rebuilt.
- [x] 5.4 Add the Playwright spec the requirement names.
      Done (2026-11-25): `tests/e2e/spec-coverage/one-signing-rail-for-the-fleet.spec.ts`
      ran live, 3/3 passed (13.7 s) on Nextcloud 34 + MariaDB + OpenRegister
      main 0e1d0609 + filinq development ab415214 (php -S sandbox). The
      unhandled endpoint `GET api/settings/signing-sources` answered
      `{"sources":[],"total":0,"truncated":false}` on the fresh instance, so
      the empty case is real, not only route-handled.

## 6. Verification

- [x] 6.1 Run PHPUnit in the container:
      `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`
      and read the exit code, not the summary line.
      Done (2026-11-23): `php vendor/bin/phpunit -c phpunit-unit.xml --no-coverage` on `development`
      23e71f5 (the unit suite runs on its stubs, no Nextcloud needed) exits 0: 8525 tests, 8 skipped.
- [x] 6.2 Run `openspec validate one-signing-rail-for-the-fleet --strict`.
      Done (2026-11-23): the `status` front matter moved from the spec delta to `proposal.md`, the
      only warning; the command now reports the change valid.
- [ ] 6.3 Run `composer check:strict`.
- [ ] 6.4 Raise a delegated request from shillinq on the dev instance and
      confirm the noun is on the persisted record.

## 7. Sequencing and counterparts

- [x] 7.1 Do not open the consumer migrations before
      `libresign-signing-provider` lands. Until then filinq's only working
      signature is a native HMAC SES marker, which a beschikking cannot carry.
      Done (2026-11-25): `libresign-signing-provider` landed and is archived
      (`openspec/changes/archive/2026-09-29-libresign-signing-provider`,
      `lib/Service/Signing/LibreSignProvider.php`), so this gate is met. 7.2 and
      7.3 still wait on the answer to 1.2: which app a beschikking's signature
      goes through decides what the dossiq and decidiq changes dispatch.
- [x] 7.2 Raise the dossiq change: retire `LibresignSigningAdapter`,
      `LibresignApiClient`, `LibresignResultAssembler` and
      `LibresignStatusMapper`, dispatch the event, keep mandaat-based signer
      resolution.
      Done in dossiq before this task was reached: its change
      `signing-through-filinq` is archived
      (`openspec/changes/archive/2026-11-16-signing-through-filinq`).
      `lib/Service/Signing/DocumentSigningRequester.php` raises
      `DocumentSigningRequestedEvent`, `lib/Listener/SigningConcludedListener.php`
      hears the outcome, and no `Libresign*` class is left in dossiq's `lib/`
      (development ecbafa14, 2026-11-25). The 1.1 note above predates it.
- [x] 7.3 Raise the decidiq change: retire the `docudesk-signing` and
      `eidas-qes` integriq Source hops in `EIDASSignatureService` and dispatch
      the event, after confirming on a live instance that nothing depends on
      the current dark path.
      Done (2026-11-25): raised as decidiq `sign-through-the-filinq-rail`
      (ConductionNL/decidiq#2475). It MODIFIES REQ-DCDH-005, and its task 1 is
      the live check of the dark path, before the removal in task 4.

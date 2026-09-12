## 1. Record the open question (D1)

- [ ] 1.1 Confirm both positions in D1 against the code once more at
      implementation time, because two of the four repos moved this week.
- [ ] 1.2 Raise the ownership question with Ruben as a product decision, with
      the design's D1 as the briefing, and record the answer in the fleet ADR
      set rather than in this change.

## 2. The noun on the record (REQ-DDSRC-002)

- [ ] 2.1 Derive the subject noun from the subject schema in
      `SigningProvenance`, requiring nothing new from a consumer.
- [ ] 2.2 Persist it on the `signingRequest` object alongside the existing
      provenance fields.
- [ ] 2.3 Unit test that a dossiq beschikking request and a decidiq minutes
      request are distinguishable on the record.

## 3. Refuse an unattributed request (REQ-DDSRC-003)

- [ ] 3.1 Refuse a delegated request with a missing subject register, schema
      or id, naming what is absent.
- [ ] 3.2 Leave the filinq-internal path, which carries no provenance,
      unchanged.
- [ ] 3.3 Unit test each missing field, and mutation-check by restoring the
      field and confirming the request succeeds.

## 4. One door, written down (REQ-DDSRC-001)

- [ ] 4.1 Document `DocumentSigningRequestedEvent` as the only cross-app entry
      point in `docs/features/`, with the shillinq consumer as the worked
      example.
- [ ] 4.2 List the two duplicate rails to retire, with the file paths, so the
      consumer changes have a target.

## 5. The admin surface (REQ-DDSRC-004)

- [ ] 5.1 List source apps and request counts on the signing settings surface.
- [ ] 5.2 Show the empty case as an empty case, not as an error.
- [ ] 5.3 Add Dutch and English strings.
- [ ] 5.4 Add the Playwright spec the requirement names.

## 6. Verification

- [ ] 6.1 Run PHPUnit in the container:
      `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`
      and read the exit code, not the summary line.
- [ ] 6.2 Run `openspec validate one-signing-rail-for-the-fleet --strict`.
- [ ] 6.3 Run `composer check:strict`.
- [ ] 6.4 Raise a delegated request from shillinq on the dev instance and
      confirm the noun is on the persisted record.

## 7. Sequencing and counterparts

- [ ] 7.1 Do not open the consumer migrations before
      `libresign-signing-provider` lands. Until then filinq's only working
      signature is a native HMAC SES marker, which a beschikking cannot carry.
- [ ] 7.2 Raise the dossiq change: retire `LibresignSigningAdapter`,
      `LibresignApiClient`, `LibresignResultAssembler` and
      `LibresignStatusMapper`, dispatch the event, keep mandaat-based signer
      resolution.
- [ ] 7.3 Raise the decidiq change: retire the `docudesk-signing` and
      `eidas-qes` integriq Source hops in `EIDASSignatureService` and dispatch
      the event, after confirming on a live instance that nothing depends on
      the current dark path.

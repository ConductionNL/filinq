## 1. Read the real state (REQ-DDADH-001)

- [ ] 1.1 Point `AnonymiserBackendStateClient::OR_SERVICE` at the class
      OpenRegister declares, `OCA\OpenRegister\Service\Anonymisation\AnonymisationBackendService`.
- [ ] 1.2 Return OpenRegister's own shape, including
      `entityRecognitionEnabled`, `activeMethod` and `effectiveMethod`.
- [ ] 1.3 Report an unknown state when the service cannot be resolved, instead
      of the current `regex` fallback.
- [ ] 1.4 Unit test all three, and watch the first fail against today's code
      before the fix.

## 2. Refuse a run with no live detector (REQ-DDADH-002)

- [ ] 2.1 Read the backend state once at the start of a run in
      `AnonymizationService::runAnonymize()`.
- [ ] 2.2 Refuse with a named reason when recognition is disabled, when the
      effective backend is unavailable, or when the state is unknown.
- [ ] 2.3 Confirm no output file and no anonymisation record survive a
      refusal.
- [ ] 2.4 Unit test each refusal reason, and mutation-check by re-enabling the
      detector and confirming the same document succeeds.

## 3. Report the backend on the result (REQ-DDADH-003)

- [ ] 3.1 Carry the backend and the redacted-entity count through
      `DocumentAnonymizeRunner` onto the result.
- [ ] 3.2 Report a live-backend zero-entity run as its own outcome, naming the
      backend, and keep writing the file.
- [ ] 3.3 Carry the same fields through the batch and folder paths.
- [ ] 3.4 Unit test the zero-entity outcome and the redacting outcome.

## 4. The admin warning tells the truth (REQ-DDADH-004)

- [ ] 4.1 Derive `showWarning` and the displayed method from the value task
      1.2 supplies, dropping the `['method'] ?? 'regex'` read.
- [ ] 4.2 Name the active backend on the settings surface.
- [ ] 4.3 Add Dutch and English strings for the new warning text.
- [ ] 4.4 Add the Playwright spec the requirement names.

## 5. Verification

- [ ] 5.1 Run PHPUnit in the container:
      `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`
      and read the exit code, not the summary line.
- [ ] 5.2 Run `openspec validate anonymisation-fails-closed-without-a-detector --strict`.
- [ ] 5.3 Run `composer check:strict`.
- [ ] 5.4 On the dev instance, anonymise a document with detection disabled
      and confirm the refusal, then enable a backend and confirm the same
      document produces a file whose bytes differ from the input's.

## 6. Documentation and follow-on

- [ ] 6.1 Document the refusal and the new result fields in `docs/features/`,
      with the operator instruction to re-run anything anonymised while the
      detector was off.
- [ ] 6.2 Release note: a run with no live detector now fails where it
      previously produced a file.
- [ ] 6.3 Raise the counterpart change in the dossiq repo: read the backend
      from the result rather than inferring it from the entity count.

# Tasks: orphan-auth-remediation

## 1. Triage (complete)

- [x] 1.1 Run gate-6 on clean `origin/development`; capture the 3 findings.
- [x] 1.2 Census callers of every `SigningProviderInterface` method in `lib/`+`src/`.
- [x] 1.3 Confirm `checkStatus` is a status read, not an authorization guard.
- [x] 1.4 Confirm the live status/signing surface (`SigningController`) is
      authenticated + per-UID authorized on every action.
- [x] 1.5 Record verdicts (3 seam) with file:line in `design.md`.

## 2. Remediate (annotate seam)

- [x] 2.1 Annotate `SigningProviderInterface::checkStatus` docblock with the
      orphan-auth seam note (body untouched).
- [x] 2.2 Annotate `NativeSigningProvider::checkStatus` docblock (body untouched).
- [x] 2.3 Annotate `ValidSignProvider::checkStatus` docblock (body untouched).

## 3. Spec

- [x] 3.1 Add a requirement to
      `signing-via-or-approval-with-provider-plugins` documenting the provider
      async-flow extension seam (not authorization guards).

## 4. Verify

- [x] 4.1 Run scoped gate-6 (`--scope-to-diff origin/development`) → PASS
      (bodies unchanged → pre-existing-method filter drops the findings).
- [x] 4.2 Run the full unit suite; confirm no regression vs the pristine
      baseline (907 tests, 8 pre-existing env errors, 1 skipped — all in
      `AnonymizationServiceProhibitionTest` + `GrondslagProposalServiceTest`,
      unrelated to signing).

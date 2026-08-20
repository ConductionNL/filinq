# Tasks — revive-dead-capabilities (docudesk)

## 1. Verify (done during triage)

- [x] 1.1 Confirm `createEntityConsent` has zero callers fleet-wide; identify
      `PolicyCrudService::createStandingConsent` as the live routed create path.
- [x] 1.2 Confirm `ConsentScopeValidator::validateWrite` is called only by
      `createEntityConsent`, and `assertValid`/`validateTransition` stay in use.
- [x] 1.3 Confirm `DataResolverService::resolve()` self-resets the cache at line
      120, making `clearCache()` dead by construction.
- [x] 1.4 Confirm no test references `createEntityConsent`, `validateWrite`, or
      `clearCache`.

## 2. Delete (code)

- [x] 2.1 Remove `ConsentService::createEntityConsent()` and its docblock.
- [x] 2.2 Update the prose comment in `validateAndUpdateConsent` that named the
      old create path to point at `PolicyCrudService::createStandingConsent`.
- [x] 2.3 Remove `ConsentScopeValidator::validateWrite()`.
- [x] 2.4 Remove `DataResolverService::clearCache()`.

## 3. Verify no regression

- [x] 3.1 `php -l` clean on all three edited files.
- [x] 3.2 `grep -rn` confirms zero callers remain for all three methods.
- [x] 3.3 Full unit suite in `php:8.3-cli` (ext-zip/bcmath/soap/xsl/intl/gd) with
      fresh `composer install` shows no new failures vs the clean-baseline count.
- [x] 3.4 Scoped phpcs clean on the three edited files.

## 4. Spec + ship

- [x] 4.1 Add `entity-publication-policies` spec delta clarifying the single
      canonical standing-consent create path.
- [x] 4.2 Push branch, open PR base `development`, merge.
- [x] 4.3 Archive change, update issue #176 with verdicts.

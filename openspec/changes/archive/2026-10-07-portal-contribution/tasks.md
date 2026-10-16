# Tasks: portal-contribution

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 8.
     Acceptance criteria are plain bullets, not checkboxes. -->

## Implementation

- [x] Ship the plain, dependency-free provider class `lib/Portal/PortalContributionProvider.php`
  - Namespace `OCA\Filinq\Portal`; no `use` of any portaliq symbol; no `implements`; no constructor; no info.xml dependency; repo-standard EUPL-1.2/SPDX docblock + `@spec` tags.
  - Not registered in `lib/AppInfo/Application.php` — discovery is pull-based by FQCN from portaliq.

- [x] Implement the v2 + v1 audience contract
  - `getAudiences()` returns `['data-subject', 'signer']`; `getAudience()` returns `'data-subject'`; `getContribution()` returns `null` for any other/absent audience (fail-closed).

- [x] Declare the `data-subject` read manifest
  - Collection `subjectConsents` over `consent`/`publicationConsent`, `scopeField: contactRef`, `scopeClaim: contactId`, `minTrust: substantial`, listable, with the twelve subject-safe `fields`; empty `actions` and `notifications`.

- [x] Declare the `signer` read manifest
  - Collection `signerRecords` over `signing`/`signerRecord`, `scopeField: email`, `scopeClaim: signerEmail`, listable, participation-only `fields`.
  - Collection `signerSigningRequests` over `signing`/`signingRequest`, empty `scopeField`, `scopeClaim: signerEmail`, one-hop `via` join `{register: signing, schema: signerRecord, scopeField: email, targetField: signingRequestId}`, `minTrust: substantial`, request-only `fields`.

- [x] Unit-test the full provider contract (`tests/unit/Portal/PortalContributionProviderTest.php`)
  - Direct construction (no mocks/container); pins audiences, fail-closed null, both manifests, the `via` shape, field whitelists and forbidden exclusions.
  - Register-drift pin: every scopeField, `via` field and projected field exists on the shipped schema in `lib/Settings/filinq_register.json`.

- [x] Register the capability spec `openspec/specs/portal-contribution/spec.md`
  - Exists with status `in-progress`, pointing at this change.

- [x] Pass the quality gates
  - `php -l`, `composer phpcs`, `phpstan`, `psalm` and the unit suite pass on the new files (php:8.3-cli container) with zero new violations.

- [x] Validate the change
  - `openspec validate portal-contribution --strict` exits 0.

## Quality checklist

- All new business logic covered by PHPUnit unit tests (`tests/unit/Portal/`).
- No new API endpoints (no Newman collection); no UI change (portal renders in portaliq, no Playwright).
- Manifest labels ship in English source (i18n policy); portaliq owns portal-side translation.
- No register JSON change — every referenced property verified against HEAD.
- `openspec validate portal-contribution --strict` passes.

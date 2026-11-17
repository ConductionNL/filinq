# Proposal: filinq-route-auth-explicit-attributes

kind: code

## Why

`BatchAnonymizationController::updateProfiles()`
(`lib/Controller/BatchAnonymizationController.php:574`) is registered in
`appinfo/routes.php:72` as
`['name' => 'batchAnonymization#updateProfiles', 'url' => 'api/anonymization/profiles', 'verb' => 'PUT']`
but carries **no** auth attribute — no `@NoAdminRequired`, `@PublicPage`,
`@NoCSRFRequired`, or `#[AuthorizedAdminSetting]` docblock/attribute anywhere
between it and the preceding method. Its sibling read endpoint,
`getProfiles()` (`:557`), correctly carries `@NoAdminRequired` +
`@NoCSRFRequired` two methods above it.

Per ADR-005 ("Nextcloud endpoint defaults: NO annotation = admin-only") and
ADR-016 ("every endpoint... gets its auth attribute verified" by
`hydra-gate-route-auth`), every method named in `appinfo/routes.php` MUST
carry an explicit auth posture — relying on the implicit NC default is
exactly the syntactic gap `hydra-gate-route-auth` (gate-5) is built to catch.
Confirmed via a full scan of every `public function` in `lib/Controller/*.php`
that is also named in a `routes.php` entry: `updateProfiles()` is the only
one lacking an explicit attribute (all other 70+ routed controller methods in
the app already carry one).

Concretely this means:

- If `updateProfiles()` is meant to be usable by any authenticated operator
  (as its sibling `getProfiles()` is, and as the "WOO entity profile" feature
  set implies — operators tune anonymisation profiles, not just admins), the
  missing attribute silently makes the write endpoint admin-only while the
  read endpoint is open to all users — an inconsistent, undocumented
  authorization surface for the same feature.
- If admin-only is actually intended, it should be explicit
  (`#[AuthorizedAdminSetting(Application::class)]`) rather than relying on
  the implicit default, per ADR-005's guidance to prefer the explicit
  attribute "for clarity" and per ADR-016's mechanical gate, which does not
  distinguish "admin-only on purpose" from "forgot the attribute."

No frontend code currently calls `PUT /api/anonymization/profiles` (confirmed
via repo-wide grep for the URL), so this is not yet exercised by the UI, but
the route is live and reachable directly.

## What Changes

- Add the explicit, correct auth attribute to
  `BatchAnonymizationController::updateProfiles()`. Given its sibling
  `getProfiles()` is `@NoAdminRequired`, and WOO anonymisation profile tuning
  is described in the domain as an operator-level configuration task (not a
  Nextcloud-admin-only task), add `@NoAdminRequired` + `@NoCSRFRequired` to
  match — unless product intent is confirmed to be admin-only, in which case
  use `#[AuthorizedAdminSetting(Application::class)]` instead (see task A-1
  for the decision point).
- No BREAKING change to the URL or request/response shape — this only makes
  the existing (currently implicit admin-only) authorization posture
  explicit, or intentionally widens it to match its sibling read endpoint.

## Out of Scope

- Any change to the profile-persistence logic itself
  (`ProfileService::getProfile()` / the save path).
- A fleet-wide sweep of other apps for the same gap — this proposal is
  scoped to filinq; the pattern (routed method missing an explicit auth
  attribute) is a plain `hydra-gate-route-auth` gate finding, already
  mechanically enforced per-PR going forward.

## Success Criteria

- `updateProfiles()` carries an explicit auth attribute matching its intended
  audience.
- `hydra-gate-route-auth` (gate-5) passes for `BatchAnonymizationController`.
- If widened to `@NoAdminRequired`, a non-admin user in the appropriate
  operational role can successfully call
  `PUT /apps/filinq/api/anonymization/profiles`.

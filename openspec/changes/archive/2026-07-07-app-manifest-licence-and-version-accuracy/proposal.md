# Proposal: app-manifest-licence-and-version-accuracy

## Why

DocuDesk's app manifest and its government-facing feature sheet declare metadata that no longer
matches the repository's own reality. Two concrete drifts:

1. **Licence mismatch.** Every licence source in the repo already declares **EUPL-1.2** — the
   `LICENSE` file (EUPL v.1.2 text), the README badge and "License" section, `composer.json`
   (`"license": "EUPL-1.2"`), `publiccode.yml` (`license: EUPL-1.2`), and every `lib/**/*.php`
   docblock (`@license EUPL-1.2` + `SPDX-License-Identifier: EUPL-1.2`). Only two files still
   say AGPL: `appinfo/info.xml` (`<licence>agpl</licence>`) and `docs/GOVERNMENT-FEATURES.md`
   ("Licentie: AGPL" + technical requirement T-02 "AGPL, GitHub"). The declared licence a
   Nextcloud instance and the App Store read is therefore **wrong**, and the government feature
   sheet tells procurement the wrong licence. Since Conduction apps are EUPL-1.2 by policy and
   the `apps.nextcloud.com` `info.xsd` accepts `EUPL-1.2` for the Nextcloud versions DocuDesk
   targets, `info.xml` is simply the last un-migrated holdout.

2. **Stale compatibility metadata.** `appinfo/info.xml` now declares `<php min-version="8.3">`
   and `<nextcloud min-version="30" max-version="34"/>`, and the register requires
   OpenRegister ">= 0.2.14". But the `admin-settings` capability spec (REQ-SET-08) still asserts
   "PHP 8.0+" and "Nextcloud 28 through 32", the README "Requirements" table still says
   "Nextcloud 28 – 32 / PHP 8.1+ / OpenRegister v0.2.10+", and `docs/GOVERNMENT-FEATURES.md`
   restates the old licence. The spec, README, and docs disagree with the shipped manifest.

This is a pure honesty/consistency correction: bring the declared licence and compatibility
metadata into agreement with what the app actually ships. No behaviour changes.

## What Changes

- **MODIFIED (`admin-settings` / REQ-SET-08):** the declared PHP minimum is corrected to **8.3**
  and the supported Nextcloud range to **30 – 34**, matching `appinfo/info.xml`. The scenario
  thresholds ("PHP 7.4 fails", "NC 27 cannot enable", "max NC 32") are updated to the real gates.
- **ADDED (`admin-settings`):** a requirement that the licence declared in `appinfo/info.xml`
  MUST equal the repository's actual licence (**EUPL-1.2**), i.e. it MUST agree with `LICENSE`,
  `composer.json`, `publiccode.yml`, and the `SPDX-License-Identifier` in the source headers.
- **`appinfo/info.xml`:** `<licence>agpl</licence>` → `<licence>EUPL-1.2</licence>`.
- **`docs/GOVERNMENT-FEATURES.md`:** header "Licentie: AGPL" → "EUPL-1.2"; technical requirement
  T-02 "AGPL, GitHub" → "EUPL-1.2, Codeberg".
- **`README.md`:** the "Requirements" table is aligned to the manifest (Nextcloud 30–34,
  PHP 8.3+, OpenRegister 0.2.14+).

### Out of scope

- Any code, service, controller, or schema change (this touches manifest + docs + spec text only).
- Re-licensing the source (the source is already EUPL-1.2; this only fixes the declaration).
- Changing the actual supported NC/PHP range (only the *declared* ranges are corrected to match
  what ships).

## Capabilities

### Modified Capabilities

- `admin-settings` — corrected compatibility metadata (REQ-SET-08) and a new licence-consistency
  requirement.

## Affected Projects

- [x] Project: `docudesk` — all changes are in this repo (manifest + docs + spec text).
- Reference: `hydra/openspec` — Conduction apps are EUPL-1.2 by policy.

## Impact

- **Manifest:** `appinfo/info.xml` licence value.
- **Docs:** `docs/GOVERNMENT-FEATURES.md`, `README.md`.
- **Compliance/procurement:** government readers get the correct licence (EUPL-1.2) and the
  correct platform requirements.
- **App Store:** the declared licence matches the bundled `LICENSE`, avoiding a
  publish-time mismatch warning.

## Success Criteria

- `openspec validate app-manifest-licence-and-version-accuracy --strict` exits 0.
- After the change, `appinfo/info.xml`, `LICENSE`, `composer.json`, `publiccode.yml`, and the
  source-header `SPDX-License-Identifier` all declare EUPL-1.2 with no remaining "agpl" token.
- The `admin-settings` REQ-SET-08 thresholds match `appinfo/info.xml` (PHP 8.3, NC 30–34).
- No user-facing English l10n key is added or changed.

# Design: app-manifest-licence-and-version-accuracy

## Context

This is a metadata/documentation correctness change. No runtime code is touched. The evidence
for each correction was verified against the repository HEAD (`origin/development`).

### Verified licence state at HEAD

| Source | Declared licence |
|--------|------------------|
| `LICENSE` | `EUROPEAN UNION PUBLIC LICENCE v. 1.2` |
| `composer.json` | `"license": "EUPL-1.2"` |
| `publiccode.yml` | `license: EUPL-1.2` |
| `README.md` badge + License section | `EUPL-1.2` |
| `lib/**/*.php` docblocks | `@license EUPL-1.2` + `SPDX-License-Identifier: EUPL-1.2` |
| **`appinfo/info.xml`** | **`agpl`** ← the outlier |
| **`docs/GOVERNMENT-FEATURES.md`** | **`AGPL`** ← the outlier |

The source is already EUPL-1.2; only the two declaration files lag. Nothing is being re-licensed.

### Verified compatibility state at HEAD

| Source | PHP | Nextcloud | OpenRegister |
|--------|-----|-----------|--------------|
| `appinfo/info.xml` (shipped) | `min-version="8.3"` | `min-version="30" max-version="34"` | `>= 0.2.14` (register constraint) |
| `admin-settings` spec REQ-SET-08 | "PHP 8.0+" | "28 through 32" | — |
| `README.md` Requirements table | "PHP 8.1+" | "28 – 32" | "v0.2.10+" |

The manifest is the source of truth (it is what Nextcloud enforces at install time); the spec and
README are stale and are corrected to it.

## Goals / Non-goals

- **Goal:** the declared licence and compatibility metadata agree with the shipped manifest and
  the bundled `LICENSE`.
- **Non-goal:** changing the *actual* supported range or the *actual* licence. Both already exist;
  only the declarations are wrong.

## Decisions

### Decision 1 — `<licence>EUPL-1.2</licence>` is valid for DocuDesk's target range

The `apps.nextcloud.com` `info.xsd` licence enumeration accepts `EUPL-1.2` for current Nextcloud
releases, and DocuDesk's whole toolchain (`composer.json`, `publiccode.yml`) already uses the
SPDX id `EUPL-1.2`. We use the exact SPDX identifier `EUPL-1.2` (not `eupl` or `EUPL`) so the
manifest string matches `composer.json` and the SPDX headers verbatim. The `min-version="30"`
floor is unaffected: the licence value is validated by the App Store schema, not per-NC-release.

### Decision 2 — the spec owns the manifest metadata assertion

`appinfo/info.xml` app-identity and compatibility is already the subject of the `admin-settings`
capability (REQ-SET-08). The licence-consistency rule is added there rather than inventing a new
capability, keeping one home for "what the manifest declares". REQ-SET-08's version scenarios are
restated with the real gates (PHP 7.4 → fails; NC 27 → cannot enable; max NC 34).

### Decision 3 — docs are corrected as tasks, asserted by the spec

The README requirements table and `docs/GOVERNMENT-FEATURES.md` licence lines are corrected as
implementation tasks. The spec asserts the invariant (declared licence == repo licence; declared
compatibility == manifest); the docs are the human-facing surface of that invariant.

## Risks

- **Low.** Worst case a reviewer prefers to keep `min-version` at NC 30 while adopting the
  EUPL-1.2 string — both are already true at HEAD, so no code path changes. If the App Store
  schema for a very old NC target rejected `EUPL-1.2`, the fallback is `agpl`, but the whole
  fleet has already standardised on `EUPL-1.2`, so this is not expected.

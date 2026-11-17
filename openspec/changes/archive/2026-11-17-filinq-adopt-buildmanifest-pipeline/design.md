# Design: filinq-adopt-buildmanifest-pipeline

## Context

Filinq adopted the app-manifest pattern (ADR-024) early, before
`buildManifest` (ADR-044) and the `manifest.d/` fragment convention (ADR-037)
existed as shared library primitives. It ended up with a monolithic
`src/manifest.json` plus a hand-rolled ~200-line pipeline in `src/main.js`
that reimplements what nine other apps now get from the library
(`buildManifest`). This design note sequences the two-stage migration
(fragments, then the shared pipeline) so it can land as one change without an
intermediate broken state.

## Sequencing

Two prerequisites must land in order because `buildManifest`'s signature
expects a fragment array, not a single monolithic object:

1. **Fragment split (ADR-037).** `src/manifest.json` becomes
   `src/manifest.d/*.json`, collected via `require.context`. This step alone
   does not change behaviour — the fragments, once merged, must reproduce the
   exact same `pages[]` / `menu[]` / `dependencies[]` as the original
   monolithic file. Verify via a diff of the merged fragment output against
   the original `manifest.json` before proceeding.
2. **Pipeline swap (ADR-044).** Once fragments exist, swap
   `applyMenuLayout(bundledManifest)` for
   `buildManifest(mergedFragments, menuLayout)` and delete the five
   hand-rolled functions.

Doing them in this order means step 1 has a mechanical, diffable
verification (fragment-merge output === old monolithic manifest) independent
of the pipeline logic, and step 2 is then a drop-in replacement of
functionally-equivalent code (menu-layout.json's maps are all empty today, so
`buildManifest`'s output should be identical to the bespoke pipeline's
output).

## Risk: silent route loss

ADR-044 Rule 5 (no-functionality-loss) is the primary risk in any menu
pipeline rewrite. Mitigation: a before/after route inventory (task B-2.1) —
enumerate every route resolvable from the manifest before touching
`main.js`, and diff against the same enumeration after the swap. The diff
MUST be empty. This is a mechanical check, not a judgment call, and should
run in CI if the tooling supports a manifest-route-dump script (check
whether `@conduction/nextcloud-vue` or the hydra fleet already ships one
before writing a bespoke one).

## Alternatives considered

- **Leave the bespoke pipeline in place.** Rejected — it is the literal
  duplication ADR-044 was written to eliminate, and Filinq inherits none of
  the fleet-wide fixes/improvements that land in the shared `buildManifest`
  util going forward.
- **Adopt `buildManifest` without first splitting into fragments.** Not
  possible per ADR-044 Rule 6 — `buildManifest`'s contract is
  `(base, fragments, menuLayout)`; a monolithic manifest has no `fragments`
  array to pass.

# Landing lane land-filinq, round 2 (2026-09-28)

Clone: /home/rubenlinde/memcap-work/lq-lanes/fq (ConductionNL/filinq). Rules: MERGE-RULES.md + MERGE-RULES-R2.md. Untracked lane artifact, never committed.
TMPDIR for runs: session scratchpad land-filinq/tmp (outside the checkout). Logs: .tmp/land/.

## Baseline
- origin/development 674cc5f1 (detached): full PHPUnit `vendor/bin/phpunit --no-coverage`, exit 0, 2414 tests, 7710 assertions, 3 skipped, failure set EMPTY (.tmp/land/baseline-failset.txt).
- Register info.version on development: 8.16.0.
- composer.lock identical on development and both PR branches (vendor hydra-gates v1.16.1 = lock).

## #1203 feat/filinq-configurable-report-templates
- Start: head 11ccf0ee (local = origin, no unpushed commits).
- 5eca5f12 `git rm --cached LANE-LOG.md` + plain commit (no history rewrite; file left untracked in the clone).
- Merge origin/development 674cc5f1: conflicts NO (no file touched by both sides since merge-base a3921051). Merge commit 42b54f8e.
- Register: branch 8.16.0 = development 8.16.0, so bumped to 8.16.1 (8b4e2835, description prefixed with the v8.16.1 entry). Per-schema: template 1.4.0 > development 1.3.0. No test pins info.version to 8.16.0.
- Checks: JSON parse register 0; check:schema-l10n 0 (213 uncovered = baseline 213); test:l10n 0; check:l10n-js 0; `git grep '<<<<<<<'` only merge-hygiene.yml + DejaVuSans.ttf, identical to development (inherited).
- Full PHPUnit: exit 0, 2435 tests, 7775 assertions, 3 skipped, failure set EMPTY (subset of baseline). No attribution trailers.
- Push: 8b4e2835, ls-remote matches.
- `gh pr merge 1203 --squash --admin`: exit 0. MERGED 2026-09-28T05:13:11Z, squash 0a88874e2304f56c71f1422cc74049bffea9c847.

## #1215 feat/signer-identity-parental-consent
- Start: head 4c0c4a16 (local = origin). Re-baseline on new development tip 0a88874e (#1216 docs + #1203 landed): exit 0, 2435 tests, failure set EMPTY.
- Merge origin/development 0a88874e: conflicts YES, two files.
  - CHANGELOG.md (Unreleased/Added): union, both entries kept (guardian consent first, report templates second).
  - lib/Settings/filinq_register.json: header only. info.description = concatenation (v8.17.0 entry, then PREVIOUSLY: the full v8.16.1 description, so the v8.16.0 tail appears once). info.version kept at 8.17.0: strictly above development's 8.16.1 (brief: the second lander must be strictly above development's; no test pin to move). Per-schema: template 1.4.0 (dev), signerRecord 1.3.0 > dev 1.2.0, signingRequest 1.5.0 > dev 1.4.0.
- Merge commit 3049f7e8.
- Checks: JSON parse 0; duplicate id/uuid/recipients scan on register + mock register 0 findings (same on development); check:schema-l10n 0 (761 strings, 213 uncovered = baseline); test:l10n 0; check:l10n-js 0; conflict markers none. check:manifest exit 1 on 4 Ajv items, src/manifest.json identical to development (not touched by the merge; inherited, local nc-vue schema 2.27.0).
- Full PHPUnit: exit 0, 2474 tests, 7899 assertions, 3 skipped, failure set EMPTY.
- Push: 3049f7e8, ls-remote matches.
- `gh pr merge 1215 --squash --admin`: exit 0. MERGED 2026-09-28T05:16:44Z, squash eade8329579d9ac38dc2fb4ab6a58ff0342358cd.
- Development tip eade8329 (tree == 3049f7e8), register info.version 8.17.0.

## Post-landing finding: #1203 broke the `template` schema import (on development since 0a88874e)
- #1203 wrote the `slug` and `tenantId` property definitions at the template SCHEMA level (after `properties` closed), not inside `properties`. `slug` is therefore a duplicate JSON key; json_decode keeps the last, so `components.schemas.template.slug` is the property-definition object instead of "template".
- OpenRegister ImportHandler::importSchema (local OR 9c378221, line ~1838) refuses a fragment whose slug is not a string ("missing a 'slug'") and continues: fresh installs get no template schema, existing installs keep template 1.3.0, and TemplateSlugResolver filters on undeclared fields.
- Duplicate-key scan (python object_pairs_hook): development 1 (this one), 674cc5f1 0. The full PHPUnit suite was green because no test asserted a schema slug. Missed by my #1203 checks (rule 5/R2 checks do not look for duplicate keys).
- Not inherited-debt: introduced by the PR this lane landed. Fix prepared on its own branch `fix/template-slug-inside-properties` (cut --no-track from eade8329), NOT merged (outside the brief's PR list).
  - Red first: new tests/unit/Settings/SchemaSlugIntegrityTest.php, 2 failures on development.
  - Fix: both definitions moved inside template.properties; template 1.4.0 -> 1.4.1, register 8.17.0 -> 8.17.1 (description prefixed), DocumentProductionSchemaTest pin 1.4.1; descriptions now English with the original Dutch as nl; 4 keys in l10n en/nl + l10n:build; CHANGELOG Fixed entry.
  - Checks: SchemaSlugIntegrityTest 2/2; full PHPUnit exit 0, 2476 tests, 0 failures; check:schema-l10n 0 (213 = baseline), test:l10n 0, check:l10n-js 0; duplicate keys 0.

## Landing stack #1237/#1238/#1239 (2026-09-28)
- Baseline origin/development 0b4c950d detached: check:strict exit 0 (2521 tests, 8168 assertions, 3 skipped, failure set EMPTY). CI reds inherited: Newman 7 signing-create assertions (same 7 on #1235), gate 101 (15 schemas, brief).
- #1237: merge dev 0b4c950d clean (merge 5559b905); register 8.19.0 > 8.18.0, signingRequest 1.6.0, signerRecord 1.4.0 above dev; dup scan 0; check:strict 0 (2593 tests, failure set EMPTY); schema-l10n 0 (213=baseline), test:l10n 0, l10n-js 0. CI reds: Newman + gate 101, both inherited. MERGED 98bb7aa4.
- #1238: merge dev 98bb7aa4, conflict appinfo/routes.php (comment + two signerIdentity routes vs base comment): branch side kept, php -l 0; register untouched after merge (= dev 8.19.0, no bump needed); check:strict 0 (2631 tests, failure set EMPTY); schema-l10n 0, test:l10n 0, l10n-js 0. CI reds inherited (Newman, gate 101). MERGED 6f3fcbac.
- #1239: merge dev 6f3fcbac, conflicts l10n/en.json, nl.json, en.js, nl.js (dev side a subset of branch side, branch side kept; JSON parse 0; l10n:build 0, no drift); register untouched; check:strict 0 (2632 tests, failure set EMPTY); schema-l10n 0, test:l10n 0, l10n-js 0. CI reds inherited (Newman same 7, gate 101 only). MERGED 17811d39. Development tip 17811d39, register 8.19.0.

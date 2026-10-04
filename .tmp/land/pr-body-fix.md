## What was wrong

#1203 added `slug` and `tenantId` to the `template` schema one level too high: beside the schema's own keys, not inside `properties`.

For `tenantId` that only left the field undeclared. For `slug` it made a duplicate JSON key. `json_decode` keeps the last one, so the schema's own slug `template` became the property definition object.

OpenRegister refuses a schema fragment without a string slug (`ImportHandler::importSchema`, "imported schema fragment is missing a 'slug'") and carries on with the rest. So on development since 0a88874e:

- a fresh install gets no `template` schema at all, and the import still reports success;
- an existing install keeps `template` 1.3.0, without `slug` and `tenantId`;
- `TemplateSlugResolver` filters on two fields that no schema declares.

A scan with Python's `object_pairs_hook` finds exactly one duplicate key in the register on development, and none at 674cc5f1, the commit before #1203.

## What this changes

- `slug` and `tenantId` move inside `template.properties`. The schema slug is `template` again.
- `template` moves 1.4.0 to 1.4.1 and the register moves 8.17.0 to 8.17.1, so the corrected schema is imported everywhere. The `DocumentProductionSchemaTest` pin moves with it.
- The two descriptions are now English source strings. The original Dutch text is their `nl` translation, and the four strings are catalogued in `l10n/en.json` and `l10n/nl.json` (`l10n:build` rerun). `check:schema-l10n` stays at the 213 baseline.
- New `tests/unit/Settings/SchemaSlugIntegrityTest.php`: every schema carries its own component key as a string slug, and the template declares `slug` and `tenantId` as properties. Red on development (2 failures), green here.
- CHANGELOG entry under Unreleased, Fixed.

## Verified

- `SchemaSlugIntegrityTest`: 2 failures on development, 2/2 green after the fix.
- Full PHPUnit: 2476 tests, 0 failures, 3 skipped (development: 2474 tests, 0 failures).
- `COMPOSER_PROCESS_TIMEOUT=0 composer check:strict`: exit 0, ALL CHECKS PASSED (lint, phpcs, phpmd, psalm, phpstan, full PHPUnit). `npm run lint` 0 (152 warnings, 0 errors, same as before), `npm run format` 0.
- `npm run check:schema-l10n` 0 (213 uncovered, baseline 213), `npm run test:l10n` 0, `npm run check:l10n-js` 0.
- Duplicate-key scan of both registers: 0.

Not checked: a live import on an instance. This lane stays off the shared :8080 instance.

🤖 Generated with [Claude Code](https://claude.com/claude-code)

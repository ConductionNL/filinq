## 1. Register & schema definitions in `docudesk_register.json`

- [ ] 1.1 Add a `dossier` entry under `components.registers` with `slug: "dossier"`, `title: "Dossier Register"`, Dutch description, and `schemas: ["dossier", "base"]`.
- [ ] 1.2 Add the `base` schema under `components.schemas.base` with properties `name` (string, required, `title: "Naam"`, `objectNameField` target) and `description` (string, required, `title: "Omschrijving"`). Set `configuration.objectNameField: "name"`, `configuration.objectDescriptionField: "description"`, `icon: "Gavel"` (or equivalent Material icon). Follow the existing schema entries in this file for field ordering — `uri`, `slug`, `title`, `description`, `version`, `summary`, `icon`, `required`, `properties`, `archive`, `source`, `hardValidation`, `immutable`, `updated`, `created`, `maxDepth`, `owner`, `application`, `organisation`, `groups`, `authorization`, `deleted`, `configuration`, `searchable`.
- [ ] 1.3 Add the `dossier` schema under `components.schemas.dossier` with required field `name` (string) and optional fields `description` (string), `bases` (array with `items.$ref: "#/components/schemas/base"`), `checkedOn` (string, format `date-time`). Set `configuration.objectNameField: "name"`, `configuration.objectDescriptionField: "description"`, and an icon (`FolderAccount` or similar). Provide Dutch `title` and `description` on each property, `order` values matching the display order, `facetable: true` for `bases` and `checkedOn`.
- [ ] 1.4 Verify the JSON is valid (`jq . lib/Settings/docudesk_register.json > /dev/null`) and `composer check:strict` still passes (no PHP changed, but CS guardrails on JSON-loader code paths should remain green).

## 2. Seed data

- [ ] 2.1 Under `components.objects` (or the envelope's seed-object array), add the six canonical `base` seed objects from design.md's Seed Data table with the exact slugs, Dutch names, and Dutch descriptions. Mark each as `immutable: true` at the object level.
- [ ] 2.2 Add the five seed `dossier` objects from design.md's Seed Data section (Gemeente Demostad ×2, Conduction B.V. ×1, ReisBureau Zonnestraal ×2). Reference `bases` via the seed `base` UUIDs/slugs exactly as listed. Populate `@self.folder` with the placeholder folder identifiers (`seed-folder-<slug>`) so `RegistersLoader` creates real folders on install.
- [ ] 2.3 Ensure seed coverage of the two optionality cases: at least one dossier with `bases: []` AND `checkedOn: null` (seed 5 — Zonnestraal incident).

## 3. Install and verify seeding

- [ ] 3.1 Reset the local env (`bash clean-env.sh` or `/clean-env`) and bring the stack up with the default docker-compose profile.
- [ ] 3.2 Enable DocuDesk. Verify `RegistersLoader` runs without errors by tailing `docker logs nextcloud | grep -i registersloader`.
- [ ] 3.3 Via `occ`, list registers (`docker exec -u www-data nextcloud php occ openregister:registers:list`) and confirm `dossier` is present with schemas `dossier` and `base`.
- [ ] 3.4 Call `GET /api/objects/base` (against `localhost:80` with admin/admin) and confirm exactly six immutable seed objects are returned.
- [ ] 3.5 Call `GET /api/objects/dossier` and confirm the five seed dossiers are returned with their correct names, descriptions, `checkedOn` values, and `bases` arrays.
- [ ] 3.6 Confirm the five seed dossiers have a non-empty `@self.folder` that points to an actual Nextcloud folder (spot-check via `docker exec -u www-data nextcloud php occ files:scan admin` plus `occ files:list`).

## 4. Binding and referential-integrity verification

- [ ] 4.1 POST a new dossier via the HTTP API with `@self.folder` set to an existing folder node ID (use a Files app folder created through the UI). Confirm 201 and that the returned object's `@self.folder` equals the supplied ID.
- [ ] 4.2 PUT the same dossier with a different `@self.folder` value. Confirm the stored folder reference updates.
- [ ] 4.3 Attempt `DELETE /api/objects/base/<uuid-of-persoonsgegevens>` while at least one dossier references it. Confirm OpenRegister blocks the deletion with a referential-integrity error.
- [ ] 4.4 Attempt `POST /api/objects/dossier` with `bases: ["00000000-0000-0000-0000-000000000000"]`. Confirm OpenRegister returns a validation error identifying the invalid reference.
- [ ] 4.5 Attempt `PUT /api/objects/base/<uuid-of-persoonsgegevens>` with a changed `name`. Confirm immutability is enforced and the seed value is unchanged.

## 5. Audit trail verification

- [ ] 5.1 Log in as a non-admin user with write access. PUT a dossier with a new `checkedOn` value.
- [ ] 5.2 Query the audit trail for that dossier UUID and confirm there is an entry with the correct actor user, the old and new `checkedOn` values in the diff, and the timestamp.
- [ ] 5.3 Do a second `checkedOn` update as a different user and confirm the audit trail shows the second user as the most recent actor.

## 6. Unit tests (ADR-009)

- [ ] 6.1 Add a PHPUnit test class `Tests/Unit/Settings/DossierRegisterConfigTest.php` that loads `docudesk_register.json`, parses it, and asserts: (a) the `dossier` register exists with slugs `dossier` and `base`; (b) the `dossier` schema has required `name` and optional `bases`/`checkedOn`; (c) the `base` schema has required `name` and `description`; (d) the seed object set includes all six grondslag slugs and at least five dossier seed objects; (e) at least one seed dossier has empty `bases` and null `checkedOn`.
- [ ] 6.2 Run unit tests inside the Nextcloud container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml --filter DossierRegisterConfigTest`. Confirm green.
- [ ] 6.3 Confirm overall unit-test coverage for new code stays ≥75% (ADR-009). Since this change has no new PHP production code, this reduces to "test the config contract" — documented in 6.1.

## 7. Translations (ADR-005)

- [ ] 7.1 Add Dutch (`nl`) translations for all end-user-visible schema `title` and property `title` strings used in the UI. All seed `base.name` and `base.description` values are Dutch by design — confirm they read naturally.
- [ ] 7.2 Add English (`en`) translations of schema titles, property titles, and the six grondslag names + descriptions (short legal gloss, not full Woo excerpt). Keep `slug` stable (canonical form stays Dutch).
- [ ] 7.3 Verify translations surface in the Nextcloud UI when switching language.

## 8. Documentation & screenshots (ADR-010)

- [ ] 8.1 Add `docs/features/dossier-register.md` describing what the register is, the two schemas, the `@self.folder` binding, the `bases` reference model, and the six canonical grondslagen.
- [ ] 8.2 Capture Playwright MCP screenshots using `browser-1`: (a) the dossier list page showing the five seed dossiers; (b) a single-dossier detail view showing `bases` expanded; (c) the `base` list showing the six seed grondslagen; (d) the folder binding — a dossier's linked folder in the Nextcloud Files view.
- [ ] 8.3 Reference the screenshots from `docs/features/dossier-register.md`.
- [ ] 8.4 Update the top-level `docs/FEATURES.md` (if present) to include the dossier register under the appropriate feature tier.

## 9. Follow-up hand-off

- [ ] 9.1 Open (or reference) a tracking issue for the OpenRegister `validate-self-folder-access` change — cite this change as the first consumer that will benefit.
- [ ] 9.2 Note in `docs/features/dossier-register.md` the current trust model on `@self.folder` and that access-control will tighten when `validate-self-folder-access` lands.

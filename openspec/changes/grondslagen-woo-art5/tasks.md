# Tasks — grondslagen-woo-art5

> DocuDesk-only. Mixed change (ADR-032): register seed (config) + frontend fetch (code), kept together because the fetch consumes the reshaped seed. No new dependency, no DB migration.

## 1. Seed the Woo Art. 5 grounds (A–S)

- [x] 1.1 Replace the 6 `base` objects in `lib/Settings/docudesk_register.json` with the 19 Woo Art. 5 grounds (A–S): article slugs `art-5-1-1-a` … `art-5-2-2`, legend-prefixed `name`, Woo-text `description`. Update the `base` schema description.
- [x] 1.2 Remap the demo `dossier` seed `bases[]` references to the new slugs.

## 2. Fetch grondslagen from the register

- [x] 2.1 Add `src/services/bases.js` `fetchBaseOptions()` — GET `/apps/openregister/api/objects/dossier/base`, map to `[{label: name, value: slug}]`, fall back to `WOO_BASES` on error.
- [x] 2.2 Update `src/constants/grondslagen.js` `WOO_BASES` (the fallback) to the 19 new slugs.

## 3. Wire the pickers + remove the mirrors

- [x] 3.1 `EntityReviewTable.vue`, `FileViewerSidebar.vue`: remove the hardcoded `BASES_OPTIONS`, fetch options in `created()`, and set `NcSelect` `label`/`:reduce` so it shows the name and stores the slug.
- [x] 3.2 `DdEntityCard.vue`: set its `NcSelect` `label`/`:reduce` (options arrive as a prop from the sidebar).
- [x] 3.3 `store/modules/folderAnonymization.js`: stale default `bases: ['persoonsgegevens']` → `['art-5-1-2-e']`.

## Acceptance criteria

- The seed contains the 19 A–S grounds; no seed reference points at a removed legacy slug; register JSON is valid.
- Grondslag pickers fetch from the register, show the grondslag name, store the slug, and fall back to the slug list on error.
- No hardcoded grondslagen mirror remains in the picker components.
- No new dependency, no migration.

## Quality / test / i18n reminders

- `openspec validate "grondslagen-woo-art5"` passes.
- ESLint clean on the changed `src/` files (no leftover unused imports); register JSON parses.
- Grondslag names are Dutch (from the seed); no new hardcoded UI strings introduced by the fetch.
- Data note: pre-existing objects on the old slugs won't resolve until re-mapped (out of scope).

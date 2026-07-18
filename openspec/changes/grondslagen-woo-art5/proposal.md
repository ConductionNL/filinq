---
kind: code
---

## Why

DocuDesk's grondslagen (legal bases for anonymisation) seed shipped only six ad-hoc entries, and the frontend hardcoded that list in three places (`constants/grondslagen.js`, `EntityReviewTable.vue`, `FileViewerSidebar.vue`) — a Wave 1.1 shortcut, kept in sync by hand, that drifts from the seed and shows raw slugs in the pickers. This change replaces the seed with the authoritative **Woo Art. 5 exception grounds (legend A–S, 19 grounds)** and makes the frontend fetch the grondslagen from the register so the seed is the single source of truth.

## What Changes

- **Seed (config):** `lib/Settings/docudesk_register.json` — replace the 6 `base` objects with the 19 Woo Art. 5 grounds (slugs `art-5-1-1-a` … `art-5-2-2`; `name` = "A — …" legend-prefixed short label; `description` = full Woo text). The `base` schema description is updated. The demo `dossier` seed `bases[]` references are remapped to the new slugs (ordinary persoonsgegevens → `art-5-1-2-e` (J), bijzondere/strafrechtelijk → `art-5-1-1-d` (D), bedrijfs → `art-5-1-1-c` (C), onevenredige → `art-5-1-5` (P)).
- **Frontend fetch (code):** new `src/services/bases.js` fetches the `base` objects from OpenRegister (`/apps/openregister/api/objects/dossier/base`) and returns `[{label: name, value: slug}]`, falling back to `WOO_BASES` on any error. The grondslag pickers (`EntityReviewTable.vue`, `FileViewerSidebar.vue`, `DdEntityCard.vue`) now use it via `NcSelect` `label`/`:reduce`, so they show the human name and store the slug. The three hardcoded `BASES_OPTIONS` mirrors are removed; `constants/grondslagen.js` `WOO_BASES` (the fallback) is updated to the new slugs; `store/modules/folderAnonymization.js` stale default `bases: ['persoonsgegevens']` → `['art-5-1-2-e']`.

## Capabilities

### Modified Capabilities

- `anonymization` — grondslagen are the full Woo Art. 5 A–S set, sourced from the register rather than hardcoded.

## Impact

- **Affected code:** `lib/Settings/docudesk_register.json`; `src/services/bases.js` (new); `src/constants/grondslagen.js`; `src/store/modules/folderAnonymization.js`; `src/views/anonymization/EntityReviewTable.vue`; `src/sidebars/FileViewerSidebar.vue`; `src/components/DdEntityCard.vue`.
- **Mixed change (ADR-032):** couples a register-seed (config) edit with the frontend fetch (code) that consumes it; kept together because the fetch is meaningless without the reshaped seed. The bulk is code.
- **Data note:** existing objects referencing the old slugs (custom `base` objects / `bases[]` on dossiers or entity-relations created before this change) will not resolve to a seeded grondslag until re-mapped; this affects seed/demo data and any pre-existing tenant data.
- **No new dependency, no migration, no HTTP surface change.**

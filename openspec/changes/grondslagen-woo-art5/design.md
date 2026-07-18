## Context

Grondslagen are seeded as `base` objects in the OpenRegister `dossier` register (`docudesk_register.json`) and referenced by slug from dossiers and entity-relations (`bases[]`). The frontend needs a list of grondslagen for its `NcSelect` pickers. Until now that list was hardcoded in three places (`constants/grondslagen.js` `WOO_BASES`, and a `BASES_OPTIONS` copy each in `EntityReviewTable.vue` and `FileViewerSidebar.vue`), with the code comments themselves flagging it as a temporary Wave 1.1 shortcut that should fetch from the register.

The authoritative grondslagen are the Woo Art. 5 exception grounds, published with a legend A–S (the letter is stamped on redacted sections). The seed should carry all 19.

## Goals / Non-Goals

**Goals:** seed the 19 Woo Art. 5 grounds (A–S); make the frontend fetch grondslagen from the register (single source of truth, human labels, tenant grondslagen surface); remove the hardcoded mirrors.

**Non-Goals:** adding structured `letter`/`articleReference` fields to the `base` schema (baked into name/description instead — decided with the product owner); a data migration for pre-existing objects on the old slugs; changing the redaction/stamping logic.

## Decisions

### D1 — Replace the seed with A–S; article-based slugs; letter in the name

The 6 ad-hoc grounds are replaced by 19 (`art-5-1-1-a` … `art-5-2-2`). Slugs are article-derived (stable, self-documenting). The legend letter is prefixed onto the short Dutch `name` ("A — Eenheid van de Kroon"); the full Woo text + article reference goes in `description`. No schema change (product-owner decision: name + description only).

### D2 — Fetch grondslagen from the register; slug fallback

New `src/services/bases.js` `fetchBaseOptions()` GETs `/apps/openregister/api/objects/dossier/base`, maps each object to `{label: name, value: slug}`, and returns `WOO_BASES` (slug-only) on any error so the UI keeps working without a seeded/reachable register. The pickers call it in `created()`.

### D3 — Pickers store the slug, show the name

The three `NcSelect`s bind `label="label"` + `:reduce="(o) => o.value"`, so the option list shows the human name while the persisted value stays the slug (`art-5-*`). This keeps the stored data shape unchanged (arrays of slugs) while fixing the display (raw `art-5-*` slugs would be unreadable).

### D4 — Update the fallback + stale defaults; remove the mirrors

`WOO_BASES` (the fallback) is updated to the 19 slugs. The three hardcoded `BASES_OPTIONS`/mirror lists are removed in favour of the fetch. `folderAnonymization.js`'s stale default `bases: ['persoonsgegevens']` becomes `['art-5-1-2-e']` (J — persoonlijke levenssfeer, the ordinary-personal-data ground).

### D5 — Dossier seed remap

Demo dossier `bases[]` are remapped to the nearest A–S grounds: persoonsgegevens → `art-5-1-2-e` (J), bijzondere/strafrechtelijk → `art-5-1-1-d` (D), bedrijfs-fabricagegegevens → `art-5-1-1-c` (C), onevenredige-benadeling → `art-5-1-5` (P).

## Risks / Trade-offs

| Risk | Mitigation |
|---|---|
| Register unreachable / unseeded → empty pickers | `fetchBaseOptions()` falls back to `WOO_BASES`; the UI keeps working (slug labels). |
| Pre-existing data on old slugs no longer resolves | Documented data note; affects seed/demo + any tenant data created before this change; no auto-migration in scope. |
| Mixed config+code change (ADR-032) | Kept together because the fetch is meaningless without the reshaped seed; bulk is code; coupling documented. |
| Frontend edits land in files carrying unrelated in-flight work | Coordinated at commit time; the shared fetch logic lives in a new clean module to minimise the footprint in the shared components. |

## Migration Plan

Code + seed only; no DB migration. Re-import of the register seed (on app boot) upserts the new `base` objects by slug. Rollback reverts the seed + frontend edits; the old hardcoded lists return.

## Open Questions

- Whether to later add structured `letter`/`articleReference` fields to the `base` schema and a one-off migration remapping legacy `bases[]` slugs.

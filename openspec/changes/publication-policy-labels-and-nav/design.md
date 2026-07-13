## Context

`MainMenu.vue` (`NcAppNavigation`) lists the app's navigation entries; the router (`src/router/index.js`) defines the routes. The publication-policy features live at `StandingConsents` (`StandingConsentIndex.vue`) and `Prohibitions` (`ProhibitionIndex.vue` + `ProhibitionFormModal.vue`). Entity-type / consent labels are user-facing `t('docudesk', …)` strings backed by `l10n/`.

## Goals / Non-Goals

**Goals:** rename the two policy features to "Publish always" / "Publish never" at the surfaces users read first (menu, page titles/descriptions, add/edit dialogs); hide four nav entries from the menu without removing their routes.

**Non-Goals:** renaming route/component/store identifiers (only display labels change); rewriting every inline sentence mention (kept as-is to avoid awkward copy — per the agreed scope); removing routes or components (hiding is navigation-only).

## Decisions

### D1 — Rename display labels only, keep identifiers

Route names (`StandingConsents`, `Prohibitions`), component names, and store keys are unchanged; only `t()` display strings are renamed. This keeps the diff small, avoids breaking deep links / active-route matching, and is trivially reversible.

### D2 — Hide by removing nav entries, not routes

The four entries (Dashboard, Folder Analysis, Consent Management, Templates) are removed from `MainMenu.vue` only. Their routes and components remain, so the pages stay reachable by URL and can be re-surfaced by re-adding the nav items. Now-unused icon imports/registrations and stale `ACTIVE_GROUPS` entries are removed so no dead code remains.

### D3 — Scope of the rename

Menu + page titles/descriptions + add/edit dialog labels are renamed. Inline sentence mentions ("this entity is on the publication prohibition list", dialog copy, badge tooltips) are left unchanged: rewriting them to "publish-never" reads awkwardly and was explicitly out of scope.

### D4 — i18n

New EN source strings get NL translations ("Altijd publiceren" / "Nooit publiceren", and "Altijd/Nooit-publiceren-regel toevoegen/bewerken") added to `l10n/{en,nl}.{js,json}`. Orphaned old strings are left for Transifex to prune.

## Risks / Trade-offs

| Risk | Mitigation |
|---|---|
| Inconsistent terminology (menu says "Publish never" but an inline sentence still says "prohibition") | Accepted per the agreed scope; the primary surfaces are consistent; a later pass can sweep inline copy. |
| A hidden page is genuinely unreachable for a user who needs it | Routes are retained — pages remain reachable by URL and re-surfacing is a one-line nav re-add. |
| Manual l10n entries overwritten by Transifex | Source strings live in the `t()` calls; manual NL entries only bridge until the next sync. |

## Migration Plan

Pure frontend; no migration. Rollback re-adds the four nav items and reverts the label strings.

## Open Questions

- Whether to later sweep the inline sentence mentions to the new terminology, and whether the hidden routes should eventually be removed entirely.

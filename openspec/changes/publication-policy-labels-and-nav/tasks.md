# Tasks — publication-policy-labels-and-nav

> Filinq frontend only. Display-label + navigation-visibility change. No backend, no route/component removal, no new dependency.

## 1. Hide menu items — SUPERSEDED, NOT DELIVERED (corrected 2026-09-06)

> ⚠️ These two boxes were ticked and the work is not in the product.
>
> Both tasks edit `src/navigation/MainMenu.vue`. That file no longer exists: the
> app moved to manifest-driven navigation (ADR-037), and the migration restored
> every entry this section removed. Only section 2's labels survived, so the
> change read as complete while half of it had been silently undone — and the
> half that was undone owned the menu placement of the two policy pages, which
> left them reachable by hand-typed URL alone for months.
>
> The hiding decision itself is superseded. The menu it describes — Anonymization,
> My Documents, Publish always, Publish never and nothing else — is not the
> product filinq has become; Dashboard, Templates, Consent Management and Folder
> Analysis are all core surfaces today. It is recorded here rather than reopened.

- [ ] 1.1 ~~Remove the Dashboard, Folder Analysis, Consent Management, and Templates entries from `MainMenu.vue`~~ — superseded; those four are core menu entries today.
- [ ] 1.2 ~~Remove now-unused icon imports/registrations and stale `ACTIVE_GROUPS` entries~~ — superseded with 1.1; `MainMenu.vue` and `ACTIVE_GROUPS` no longer exist.

## 1b. Menu placement for the two policy pages (delivered 2026-09-06)

- [x] 1b.1 Add `StandingConsents` ("Publish always", icon `Publish`) and `Prohibitions` ("Publish never", icon `PublishOff`) to `src/manifest.json`, ordered 22 and 24 so they sit directly beneath Consent Management.
- [x] 1b.2 Register both icons in `src/icons.js` — an unregistered name renders NO icon in the navigation, not a fallback glyph (ADR-077 rule 3).
- [x] 1b.3 Invert `orphaned-surface-restoration.spec.ts`'s "no policy menu entry" assertion so it now guards the entries' presence, and update the matching spec scenario.

## 2. Rename to Publish always / Publish never

- [x] 2.1 `MainMenu.vue`: "Standing Consents" → "Publish always"; "Prohibitions" → "Publish never".
- [x] 2.2 `StandingConsentIndex.vue`: page title → "Publish always"; description inline "prohibition rule" → "publish-never rule"; add/edit dialog → "Add/Edit publish-always rule".
- [x] 2.3 `ProhibitionIndex.vue`: page title → "Publish never"; `ProhibitionFormModal.vue`: add/edit dialog → "Add/Edit publish-never rule".

## 3. i18n

- [x] 3.1 Add the new EN source strings + NL translations ("Altijd publiceren" / "Nooit publiceren" and the add/edit labels) to `l10n/{en,nl}.{js,json}`.

## Acceptance criteria

- The two policy pages, their titles, and add/edit dialogs read "Publish always" / "Publish never"; NL shows "Altijd/Nooit publiceren".
- Both policy pages are listed in the navigation beneath Consent Management and open from it.
- No backend, route, or dependency change.
- ~~The main menu shows only Anonymization, My Documents, Publish always, Publish never~~ — superseded, see section 1.

## Quality / test / i18n reminders

- `openspec validate "publication-policy-labels-and-nav"` passes.
- ESLint clean on the changed `src/` files (no unused imports left in MainMenu).
- NL + EN translations provided for every new/changed user-facing string.
- Presentation-only change: no unit test added (no testable logic changed); inline sentence mentions intentionally left unchanged.

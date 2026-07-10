# Tasks — publication-policy-labels-and-nav

> DocuDesk frontend only. Display-label + navigation-visibility change. No backend, no route/component removal, no new dependency.

## 1. Hide menu items

- [x] 1.1 Remove the Dashboard, Folder Analysis, Consent Management, and Templates entries from `MainMenu.vue` (routes/components untouched).
- [x] 1.2 Remove now-unused icon imports/registrations (MonitorDashboard, FolderSearchOutline, AccountCheckOutline, FileDocumentMultipleOutline) and stale `ACTIVE_GROUPS` entries (Consent, Templates).

## 2. Rename to Publish always / Publish never

- [x] 2.1 `MainMenu.vue`: "Standing Consents" → "Publish always"; "Prohibitions" → "Publish never".
- [x] 2.2 `StandingConsentIndex.vue`: page title → "Publish always"; description inline "prohibition rule" → "publish-never rule"; add/edit dialog → "Add/Edit publish-always rule".
- [x] 2.3 `ProhibitionIndex.vue`: page title → "Publish never"; `ProhibitionFormModal.vue`: add/edit dialog → "Add/Edit publish-never rule".

## 3. i18n

- [x] 3.1 Add the new EN source strings + NL translations ("Altijd publiceren" / "Nooit publiceren" and the add/edit labels) to `l10n/{en,nl}.{js,json}`.

## Acceptance criteria

- The main menu shows only Anonymization, My Documents, Publish always, Publish never (+ the dev Gallery); Dashboard/Folder Analysis/Consent Management/Templates are not shown.
- The two policy pages, their titles, and add/edit dialogs read "Publish always" / "Publish never"; NL shows "Altijd/Nooit publiceren".
- Hidden pages remain reachable by direct URL (routes retained).
- No backend, route, or dependency change.

## Quality / test / i18n reminders

- `openspec validate "publication-policy-labels-and-nav"` passes.
- ESLint clean on the changed `src/` files (no unused imports left in MainMenu).
- NL + EN translations provided for every new/changed user-facing string.
- Presentation-only change: no unit test added (no testable logic changed); inline sentence mentions intentionally left unchanged.

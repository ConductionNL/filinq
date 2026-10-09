---
kind: code
---

## Why

Two product/UX decisions for the anonymisation navigation: (1) the standing-consent and prohibition policy features are being renamed to the plain-language "Publish always" / "Publish never" so operators read them as publication outcomes rather than jargon; (2) several navigation entries that are not part of the current focused workflow — Dashboard, Folder Analysis, Consent Management, Templates — should be hidden from the main menu (the routes remain, just not surfaced in navigation).

## What Changes

- **MODIFIED — rename (menu + page titles/descriptions + add/edit dialog labels):**
  - Main menu: "Standing Consents" → "Publish always"; "Prohibitions" → "Publish never".
  - `StandingConsentIndex` page title "Standing Publication Consents" → "Publish always"; its description's inline "prohibition rule" → "publish-never rule"; add/edit dialog "Add/Edit standing consent" → "Add/Edit publish-always rule".
  - `ProhibitionIndex` page title "Publication Prohibitions" → "Publish never"; `ProhibitionFormModal` "Add/Edit prohibition" → "Add/Edit publish-never rule".
  - Deep inline sentence mentions elsewhere (ConsentDetail, dialogs, badges, EntityReviewTable) are intentionally left unchanged.
- **MODIFIED — hide menu items:** remove the Dashboard, Folder Analysis, Consent Management, and Templates entries from `MainMenu.vue` (routes/components untouched; still reachable by URL). Now-unused icon imports/registrations and the stale `ACTIVE_GROUPS` entries are removed.
- **i18n:** new strings added to `l10n/{en,nl}.{js,json}` — NL "Altijd publiceren" / "Nooit publiceren" and the renamed add/edit labels.
- **NO backend change**, no route removal, no new dependency.

## Capabilities

### Modified Capabilities

- `anonymization` — the publication-policy features are presented as "Publish always" / "Publish never"; the main menu is trimmed to the focused workflow.

## Impact

- **Affected code:** `src/navigation/MainMenu.vue`, `src/views/policy/StandingConsentIndex.vue`, `src/views/policy/ProhibitionIndex.vue`, `src/views/policy/ProhibitionFormModal.vue`, `l10n/{en,nl}.{js,json}`.
- **No route or component removal** — hidden pages remain reachable by direct URL; hiding is navigation-only and reversible.
- Presentation/label-only; no unit test added (no testable logic changed).

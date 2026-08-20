# Proposal: docudesk-upload-dropzone-keyboard-access

kind: code

## Why

Four DocuDesk views implement a near-identical drag-and-drop file-upload
zone (`class="drop-zone"` with `@dragover.prevent` / `@dragleave.prevent` /
`@drop.prevent="handleDrop"`). Two of the four also expose a real, focusable
`<NcButton>` as a keyboard-operable alternative to the mouse-only drag/drop
gesture (WCAG 2.1.1 permits drag-and-drop to remain pointer-only as long as
an equivalent keyboard path exists for the same operation):

- `src/views/anonymization/AnonymizationPocWidget.vue:37` — `<NcButton
  type="secondary" @click="openPicker">` alongside the drop-zone div.
- `src/views/anonymization/BatchAnonymizationView.vue:12` — `<NcButton
  type="secondary" @click="$refs.fileInput.click()">` alongside the
  drop-zone div.

The other two have **no such alternative** — confirmed by reading both
files in full:

- `src/views/anonymization/AnonymizationWidget.vue:9-33` — the trigger is a
  plain `<div class="drop-zone" ... @click="$refs.fileInput.click()">`
  wrapping a decorative `<span class="fake-button">{{ t('docudesk', '+
  Select files') }}</span>` (`:24`). The div carries no `tabindex`, no
  `role="button"`, and no `@keydown.enter`/`@keydown.space` handler. The
  real `<input type="file" ... class="file-input">` (`:27-33`) is styled
  `.file-input { display: none; }` (`:481-483`), which removes it from the
  tab order entirely (`display:none` elements are not focusable in any
  browser).
- `src/views/widgets/AnonymizationDashboardWidget.vue:81-110` — identical
  structure: `<div class="drop-zone" ... @click="$refs.fileInput.click()">`,
  a `<span class="fake-button">` (`:103`), and `.file-input { display: none;
  }` (`:546-548`). No `<NcButton>` or other focusable trigger exists
  anywhere in this component's upload area.

For these two components, a keyboard-only user (or a screen-reader user
navigating by Tab) has **no way at all** to open the native file picker:
the only click target is a non-interactive `<div>`, the visual "button" is
a `<span>` (not tab-reachable, no `role`, no keyboard handler), and the
underlying `<input type="file">` is `display:none`. This is a WCAG 2.1 AA
violation (2.1.1 Keyboard — "All functionality of the content is operable
through a keyboard interface") and directly contradicts ADR-010's explicit
"WCAG AA mandatory: keyboard-navigable" rule for all DocuDesk UI.

`AnonymizationWidget.vue` backs the dashboard's compact anonymization
widget and `AnonymizationDashboardWidget.vue` backs its expanded/dashboard
variant — both are reachable from the Nextcloud dashboard, a
frequently-used entry point, making this a high-visibility gap.

## What Changes

- In `AnonymizationWidget.vue` and `AnonymizationDashboardWidget.vue`,
  replace the decorative `<span class="fake-button">` with a real,
  focusable trigger — either an `<NcButton>` (matching the pattern already
  used in the other two components) or, at minimum, add `role="button"`,
  `tabindex="0"`, an `aria-label`, and an `@keydown.enter`/`@keydown.space`
  handler to the existing drop-zone `<div>` so it becomes independently
  keyboard-operable.
- Keep the existing drag-and-drop mouse/pointer behavior unchanged — this
  adds a keyboard path, it does not remove or alter the drop gesture.
- No BREAKING change to props, events, or the upload flow's data contract.

## Out of Scope

- `AnonymizationPocWidget.vue` and `BatchAnonymizationView.vue` — already
  compliant via their existing `<NcButton>`, not touched by this change.
- A shared/extracted drop-zone component to de-duplicate the four
  near-identical implementations — worth a follow-up but out of scope here
  to keep this change small and focused on the accessibility gap.
- Any other custom-widget accessibility surface not covered by this
  drop-zone pattern (this sweep's a11y lens found this as the one real,
  reproducible gap; no other keyboard/focus-trap/contrast issues were
  confirmed against code at HEAD).

## Success Criteria

- Both components' upload area is fully operable via keyboard alone: a user
  tabbing through the page can reach the upload trigger and activate it
  with Enter/Space to open the native file picker.
- The trigger has an accessible name (visible text or `aria-label`) exposed
  to assistive technology.
- No regression to the existing drag-and-drop behavior or the `handleDrop`
  / `handleFileSelect` data flow.

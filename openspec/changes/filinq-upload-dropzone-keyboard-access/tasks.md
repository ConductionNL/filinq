# Tasks: filinq-upload-dropzone-keyboard-access

All tasks are `[filinq]`. Estimates: S = half-day.

## [filinq] AnonymizationWidget.vue

### A-1. Add a keyboard-operable trigger (S)

- [ ] A-1.1 Replace `<span class="fake-button">{{ t('filinq', '+ Select
  files') }}</span>` (`src/views/anonymization/AnonymizationWidget.vue:24-26`)
  with an `<NcButton type="secondary" @click="$refs.fileInput.click()">`,
  matching the pattern in `AnonymizationPocWidget.vue:37` /
  `BatchAnonymizationView.vue:12`, keeping the same visible label.
- [ ] A-1.2 Confirm the outer `drop-zone` div's `@click` handler and the
  new button's `@click` do not double-fire the file picker (stop
  propagation on the button, or remove the redundant outer `@click` once
  the button exists — drag/drop `@dragover`/`@drop` handlers stay on the
  outer div).
  - **Acceptance:** tabbing to the upload area reaches a real button;
    pressing Enter/Space opens the native file picker; drag-and-drop still
    works unchanged.

## [filinq] AnonymizationDashboardWidget.vue

### B-1. Add a keyboard-operable trigger (S)

- [ ] B-1.1 Replace the `<span class="fake-button">` at
  `src/views/widgets/AnonymizationDashboardWidget.vue:103-105` with an
  `<NcButton type="secondary" @click="$refs.fileInput.click()">`, keeping
  both label variants (`'+ Select files'` / `'+ Add more files'` depending
  on `anonymizationStore.hasFiles`).
- [ ] B-1.2 Same double-fire check as A-1.2 for this component's drop-zone
  `@click` vs the new button's `@click`.
  - **Acceptance:** same as A-1's acceptance criterion, for this component.

## [filinq] Verification

### C-1. Regression + coverage (S)

- [ ] C-1.1 Add or extend a Vitest component test (per `tests/vitest/`)
  asserting the upload trigger is a real `<button>`-rendering element
  (`NcButton`) reachable via `tab`/keyboard, for both components.
- [ ] C-1.2 Re-run the existing anonymization Playwright e2e spec
  (`tests/e2e/workflows/anonymization-workflow.spec.ts`) to confirm no
  regression to the drag-and-drop upload flow.
- [ ] C-1.3 Manual keyboard-only pass: Tab to the dashboard widget, Enter/
  Space to open the file picker, confirm focus returns sensibly after the
  native OS file dialog closes.

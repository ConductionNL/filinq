# upload-dropzone-keyboard-access Specification (delta)

---
status: proposed
---

## Purpose

Ensure every DocuDesk file-upload drop-zone exposes a keyboard-operable
trigger for opening the native file picker, per WCAG 2.1 AA (2.1.1
Keyboard) and ADR-010's "WCAG AA mandatory: keyboard-navigable" rule.
Drag-and-drop itself may remain pointer-only as long as an equivalent
keyboard-reachable control performs the same action.

## ADDED Requirements

### Requirement: Upload drop-zones MUST expose a keyboard-operable trigger

Every component that renders a file-upload drop-zone MUST include, in
addition to the drag/drop gesture, a real focusable control (e.g.
`<NcButton>`, or a `<div>`/`<span>` carrying `role="button"`,
`tabindex="0"`, an accessible name, and Enter/Space key handling) that
opens the same file-selection flow (the native `<input type="file">` or an
equivalent picker).

#### Scenario: Keyboard-only user opens the file picker

- GIVEN a user navigating `AnonymizationWidget.vue` or
  `AnonymizationDashboardWidget.vue` by keyboard only
- WHEN they press Tab until the upload trigger receives focus
- THEN the trigger SHALL be a real focusable element (not a `display:none`
  input or a non-interactive `<span>`)
- AND pressing Enter or Space SHALL open the native file picker

#### Scenario: Hidden file input is never the only trigger

- GIVEN a component wires `<input type="file" class="file-input">` styled
  `display: none`
- WHEN the component is audited for keyboard accessibility
- THEN there MUST exist at least one other element that (a) triggers the
  same input's `click()` and (b) is independently reachable and operable
  via keyboard (Tab + Enter/Space)

#### Scenario: Drag-and-drop remains pointer-only without violating the rule

- GIVEN a drop-zone `<div>` with `@dragover`/`@drop` handlers and no
  keyboard equivalent of the drag gesture itself
- AND that same drop-zone contains a keyboard-operable button performing
  the equivalent "select files" action
- WHEN the component is audited for keyboard accessibility
- THEN no violation SHALL be reported, since the drag gesture has a
  keyboard-reachable equivalent

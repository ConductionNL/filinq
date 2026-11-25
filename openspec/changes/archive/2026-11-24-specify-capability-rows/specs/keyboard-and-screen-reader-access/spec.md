# keyboard-and-screen-reader-access Specification (delta)

## Purpose

Using filinq with only a keyboard or with a screen reader.

## ADDED Requirements

### Requirement: Every screen works with a keyboard and with a screen reader (REQ-SCR-017)

Every filinq screen MUST be usable with only a keyboard, with a visible focus and no keyboard trap, and every control MUST carry an accessible name a screen reader announces, to WCAG 2.2 level AA.

Rows: `op-a11y` (filinq matrix)

#### Scenario: A clerk assigns an intake document with the keyboard

- GIVEN a clerk on the intake page who uses no mouse
- WHEN they move through the list, open the assign dialog and confirm with the keyboard
- THEN the document is assigned
- AND focus was visible at every step
- @e2e exclude specification only; the change that builds it adds the Playwright test

#### Scenario: A screen reader names the controls

- GIVEN a screen reader user on the anonymisation screen
- WHEN they move through its controls
- THEN every button and field is announced by its name
- @e2e exclude specification only; the change that builds it adds the Playwright test

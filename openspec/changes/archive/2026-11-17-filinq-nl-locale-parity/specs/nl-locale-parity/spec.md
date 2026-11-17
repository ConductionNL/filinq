# nl-locale-parity Specification (delta)

---
status: proposed
---

## Purpose

Guarantee that Filinq's mandatory Dutch locale (`l10n/nl.json`) stays in
lockstep with the English source (`l10n/en.json`), per ADR-007 ("Both files
MUST contain exactly the same keys, with zero gaps"), and that no
translation key literal is itself written in Dutch. Closes a gap where
`tests/l10n/check-l10n.js` only verified `en.json` completeness, leaving
`nl.json` free to silently drift (56% of keys missing at time of writing).

## ADDED Requirements

### Requirement: en.json and nl.json MUST contain the same keys

Every key present in `l10n/en.json` MUST also be present, non-empty, and
translated (not copied verbatim from English) in `l10n/nl.json`.

#### Scenario: Parity check fails on a missing nl.json key

- GIVEN `l10n/en.json` contains a key `"Example string"`
- AND `l10n/nl.json` does not contain that key
- WHEN `npm run test:l10n` is executed
- THEN the script SHALL exit non-zero and list `"Example string"` as a
  missing Dutch translation

#### Scenario: Parity check passes when every key is translated

- GIVEN every key in `l10n/en.json` has a corresponding non-empty entry in
  `l10n/nl.json`
- WHEN `npm run test:l10n` is executed
- THEN the script SHALL exit zero

### Requirement: Translation keys MUST be written in English

Every `t('filinq', ...)` or `$this->l10n->t(...)` call site MUST use an
English word or phrase as its literal key argument, never a Dutch one. The
key is always the English source string; the Dutch rendering is supplied
exclusively via `l10n/nl.json`.

#### Scenario: A Dutch-language literal is used as a key

- GIVEN a `.vue` file contains `t('filinq', 'Grondslagen')`
- WHEN the i18n-key sweep is run
- THEN this SHALL be flagged as a violation and the call site SHALL be
  changed to use an English key (e.g. `t('filinq', 'Legal bases')`) with
  the Dutch text moved to `l10n/nl.json`

#### Scenario: An English literal key with a Dutch translation is compliant

- GIVEN `t('filinq', 'Legal bases')` in a `.vue` file
- AND `l10n/nl.json` maps `"Legal bases"` to `"Grondslagen"`
- WHEN the i18n-key sweep is run
- THEN no violation SHALL be reported

# Proposal: filinq-nl-locale-parity

kind: code

## Why

Filinq's own `l10n/en.json` (the source-of-truth per ADR-007) currently
contains 682 translation keys. Diffing every locale against it (verified live
against HEAD, 2026-07-07):

| Locale | Keys present | Missing vs en | % missing |
|---|---|---|---|
| `nl.json` | 307 (of the 682 en keys, 298 present) | **384** | **56%** |
| `de.json` | — | 206 | 30% |
| `es.json` | — | 206 | 30% |
| `fr.json` | — | 206 | 30% |
| `it.json` | — | 206 | 30% |

`nl.json` is the *worst-covered* locale in the app despite Dutch being
Filinq's primary government-user language (ADR-007: "All Conduction
Nextcloud apps serve Dutch government users"; ADR-010: NL Design System
positioning) and the only locale ADR-007 makes mandatory alongside English
("`l10n/en.json` and `l10n/nl.json` MUST exist... Both files MUST contain
exactly the same keys, with zero gaps"). Every one of the 384 missing keys
silently falls back to the English source string for Dutch-locale users —
this is not a cosmetic gap, it is the majority of the app's UI text (56%)
rendering in the wrong language for its primary audience with zero runtime
signal that anything is wrong.

The app's own l10n gate, `tests/l10n/check-l10n.js` (`test:l10n` /
`test:l10n:write` npm scripts), only verifies that every literal key used in
`src/` is present in `l10n/en.json` (confirmed by running it:
`l10n-check [filinq]: scanned 94 files, 596 distinct literal keys used, 682
keys in en.json` / `OK — every used translation key is present in
l10n/en.json`). It does **not** check that `nl.json` (or any other locale)
has a matching entry for each `en.json` key, so this gap is invisible to CI
and will keep growing as new English strings are added without anyone
noticing `nl.json` falling further behind.

Separately, and also a direct ADR-007 violation ("Dutch strings used as
translation keys... are a violation — the English equivalent must be the
key"), the following six call sites use a Dutch word as the literal
`t('filinq', ...)` key/msgid, not just as the eventual translated value:

- `src/views/anonymization/AnonymizationPocWidget.vue:16` —
  `t('filinq', 'Grondslagen catalogue could not be loaded — falling back to the seeded list.')`
- `src/views/anonymization/AnonymizationPocWidget.vue:114` —
  `t('filinq', 'Grondslag (bases)')`
- `src/views/anonymization/AnonymizationPocWidget.vue:139` —
  `:input-label="t('filinq', 'Grondslagen')"`
- `src/views/anonymization/EntityReviewTable.vue:55` —
  `:input-label="t('filinq', 'Grondslagen')"`
- `src/views/anonymization/FolderAnonymizationView.vue:77` —
  `t('filinq', 'Grondslagen')`
- `src/components/DdEntityCard.vue:68` —
  `:input-label="t('filinq', 'Grondslagen')"`

Each of these keys is therefore Dutch-as-key with no English source string,
which also means an English-locale user sees raw Dutch UI text
("Grondslagen", "Grondslag (bases)") since there is no English msgid to
translate away from.

## What Changes

- Extend `tests/l10n/check-l10n.js` (or add a sibling check invoked by the
  same `test:l10n` script) to additionally diff every `l10n/<locale>.json`
  against `l10n/en.json` and **fail** when `nl.json` is missing any key
  present in `en.json` (matching ADR-007's "zero gaps" requirement for the
  one other MUST-have locale); other locales (`de`, `es`, `fr`, `it`) may
  warn-only since ADR-007 only mandates en+nl.
- Backfill the 384 missing `nl.json` entries with real Dutch translations
  (not copies of the English source) so Dutch-locale users see translated
  text for the full app surface.
- Rename the six Dutch-keyed literals above to English keys (e.g.
  `t('filinq', 'Bases catalogue could not be loaded — falling back to the
  seeded list.')`, `t('filinq', 'Legal basis (bases)')`, `t('filinq',
  'Legal bases')`) with the existing Dutch text preserved as the `nl.json`
  translation for the new key.
- No BREAKING change: this only affects translation strings shown to users
  and internal `t()` key literals, not any API contract, prop, or stored
  data shape.

## Out of Scope

- `de.json` / `es.json` / `fr.json` / `it.json` backfill (real gap, lower
  priority — not mandated by ADR-007, tracked as a follow-up if wanted).
- The `register-i18n` capability (translatable OpenRegister *content*
  fields, e.g. template titles) — unrelated to this UI-chrome `l10n/*.json`
  gap.
- Any change to `check-l10n.js`'s existing en.json-completeness check, which
  already works correctly.

## Success Criteria

- `l10n/nl.json` contains all 682 keys present in `l10n/en.json`, each with
  a real Dutch translation (not an English copy).
- `npm run test:l10n` fails if a future PR adds an `en.json` key without a
  corresponding `nl.json` entry.
- No `t('filinq', ...)` call site anywhere in `src/` uses a Dutch word as
  its literal key argument.

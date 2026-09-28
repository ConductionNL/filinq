# Design: decision-letter-legal-basis-and-deadline

## Context

At the build-all stack head (28 Sep 2026): `DocumentService::generateFromTemplate()`
resolves `dataRefs`, `listRefs` and `adHocData` into one context through
`DataResolverService`, then renders. `MergeFieldDialog.vue` inserts a free-text
`{{ field }}`. The `base` schema holds grondslagen with a name and an article.

## Decisions

### D1. The deadline is computed at generation, from a decision date in the data

The context gets `bezwaar` when it can find a decision date: `adHocData.besluitDatum`,
or `besluitDatum` / `decisionDate` on the first resolved object. The term is
`bezwaar_termijn_weken` weeks (default 6, Awb 6:7), starting the day after the
decision date. With no decision date there is no `bezwaar` key and the
generation warnings say so, so a template can test `{% if bezwaar %}`.

### D2. The legal basis is a normal data reference

A template binds a `base` object through `dataRefs`. The resolver keys resolved
objects by schema slug, so the object arrives as `base`; generation also offers
it as `grondslag` when the data has no `grondslag` of its own. The merge field
dialog lists `grondslag.name` and `grondslag.description` beside the `bezwaar`
fields, so the author picks instead of types.

Corrected at build (28 Sep 2026): the design named `grondslag.article`, but the
`base` schema has `name` and `description` and no article, and `dataRefs` has
no alias, so `grondslag` only exists through the alias above.

### D3. The missing-date warning only fires for a template that uses `bezwaar`

Every generation without a decision date would otherwise warn, including
letters that are not decisions at all. The warning is added when the template
source mentions `bezwaar`; the `bezwaar` key is left out either way.

## Risks

- Holidays: Awb 6:7 counts calendar weeks and the Algemene termijnenwet moves a
  deadline that ends on a weekend or holiday. D1 moves a Saturday or Sunday to
  the Monday; public holidays are listed in the docs as a known limit.

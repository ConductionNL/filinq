# woo-publicatie-pipeline Specification (delta)

## Purpose

The hand-off files each publication under its Woo information category at
OpenCatalogi, so the category sitemap lists it and the national Woo index
receives it. Matrix row `woo-index` (filinq).

## ADDED Requirements

### Requirement: The hand-off files the publication under its Woo category (REQ-DDWPP-010)

On hand-off, filinq MUST write the record's Woo category on the OpenCatalogi
`publication` object as `wooCategory`, in OpenCatalogi's own code
(`infocat001` to `infocat017`). A record that holds the basename of the TOOI
URI MUST be translated to that code. A value that is neither MUST block the
hand-off with the reason "Unknown Woo category". When the category of a
handed-off record changes, filinq MUST write the new code on the publication.
filinq MUST NOT build a sitemap of its own: the listing stays OpenCatalogi's.

Rows: `woo-index` (filinq matrix)

#### Scenario: A handed-off decision appears in its category sitemap

- GIVEN a ready publication record with category "Besluiten op Woo-verzoeken" and complete metadata
- WHEN the operator hands it off from `/publications`
- THEN the OpenCatalogi publication carries `wooCategory` set to that category's code
- AND the OpenCatalogi sitemap of that category lists the publication
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts

#### Scenario: A record from before the change still hands off

- GIVEN a ready record whose `wooCategory` holds the TOOI URI basename `c_8c840238`
- WHEN the operator hands it off
- THEN the publication carries the OpenCatalogi code whose TOOI URI ends in `c_8c840238`
- @e2e exclude a record from before the change cannot be made through the page; PHPUnit covers it

#### Scenario: An unknown category blocks the hand-off

- GIVEN a ready record whose `wooCategory` is `notacategory`
- WHEN the operator hands it off
- THEN the hand-off is refused with the reason "Unknown Woo category"
- AND no OpenCatalogi publication is written
- @e2e exclude the picker cannot store an unknown value; PHPUnit covers it

#### Scenario: Changing the category moves the publication

- GIVEN a handed-off record filed under one category
- WHEN the operator saves a different category on it
- THEN the OpenCatalogi publication carries the new code
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts

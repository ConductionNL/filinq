---
kind: code
depends_on: []
---

# Proposal: woo-hand-off-files-its-category

Matrix row `woo-index` in filinq's `openspec/parity/capabilities.json`.
Written in the re-rate pass of 7 October 2026, read at filinq development
`b9e834e6` and opencatalogi development `7a5a8a492`.

## Why

The Woo publication pipeline (archived as
`2026-09-29-woo-publicatie-pipeline`) hands an approved document to
OpenCatalogi, and the operator must pick a Woo information category before
the hand-off is allowed. The category never leaves filinq.

- `lib/Service/Publication/OpenCatalogiPublicationMap.php:58` `FIELDS` lists
  `title`, `summary`, `publicationDate`, `depublicationDate`,
  `retentionExpiresAt` and `retentionNote`. `toPublication()` (:76) writes no
  category.
- That was right on 29 September: OpenCatalogi's `publication` schema 0.0.4
  had no category field. On 30 September OpenCatalogi added `wooCategory`
  (schema 0.0.5, commit f35803f1f), an enum of its codes `infocat001` to
  `infocat017`.
- OpenCatalogi's category sitemaps, which the national Woo index harvests,
  select publications on that field:
  `lib/Service/SitemapService.php:374` searches `['wooCategory' => $code]`.
- A publication filinq hands off therefore appears in no category sitemap,
  and the national Woo index never sees it.

There is a second mismatch. `OpenCatalogiPlatform::categories()`
(`lib/Service/Publication/OpenCatalogiPlatform.php:91`) offers the operator
the basename of each TOOI URI (`c_8c840238`), while OpenCatalogi's enum takes
the key of the same list (`infocat001`). Writing the stored value through as
is would be refused by OpenCatalogi's schema.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `woo-index` | Feed the national Woo index with what was published. | partial: publications reach OpenCatalogi, but without `wooCategory`, so no category sitemap lists them |

## What changes

- The hand-off writes `wooCategory` on the OpenCatalogi publication, as
  OpenCatalogi's own code.
- The category picker offers OpenCatalogi's codes, and a record that still
  holds a TOOI URI basename is translated at hand-off.
- The pinned copy of OpenCatalogi's schema moves to 0.0.5, so the drift test
  guards the new field.
- Updating the category on a handed-off record updates the publication.

## Capabilities

### Modified capabilities

- `woo-publicatie-pipeline`: one added requirement, REQ-DDWPP-010.

## Impact

- `lib/Service/Publication/OpenCatalogiPublicationMap.php`,
  `lib/Service/Publication/OpenCatalogiPlatform.php`,
  `lib/Service/Publication/PublicationPipelineService.php`
- `tests/fixtures/opencatalogi-publication-schema.json`,
  `tests/unit/Service/Publication/PublicationPipelineServiceTest.php`
- No schema change in filinq's register. No new route.

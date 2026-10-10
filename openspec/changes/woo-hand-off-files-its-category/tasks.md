# Tasks: woo-hand-off-files-its-category

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 5. -->

## 1. Hand-off

- [x] 1.1 In `lib/Service/Publication/OpenCatalogiPlatform.php`, make `categories()` return the key of `informatiecategorieList()` as `code`, and add `toPlatformCode(string $value): ?string` that accepts a key or a TOOI URI basename (D1, D2, D3) (REQ-DDWPP-010). Verify: PHPUnit with `infocat001`, `c_8c840238` and an unknown value.
- [x] 1.2 In `lib/Service/Publication/OpenCatalogiPublicationMap.php`, add `wooCategory` to `FIELDS` and write it in `toPublication()`; in `PublicationPipelineService::handoff()`, translate the record's category with `toPlatformCode()` and refuse with "Unknown Woo category" when it returns null (D1, D2) (REQ-DDWPP-010). Verify: PHPUnit asserts the saved publication carries `wooCategory: infocat001`.
- [x] 1.3 In `PublicationPipelineService::updateMetadata()`, write the new `wooCategory` on the publication when `endpointPublicationRef` is set (D4) (REQ-DDWPP-010). Verify: PHPUnit asserts a second `savePlatformPublication()` call with the new code.

## 2. Pin and proof

- [x] 2.1 Replace `tests/fixtures/opencatalogi-publication-schema.json` with OpenCatalogi's `publication` schema 0.0.5 from `lib/Settings/publication_register.json` on its development (D5). Verify: the existing pin test in `tests/unit/Service/Publication/PublicationPipelineServiceTest.php` passes with `wooCategory` in `FIELDS`.
- [ ] 2.2 (live pass, decision 139: spec written, run owed) Extend `tests/e2e/workflows/woo-publicatie-pipeline.spec.ts`: after the hand-off, open `/apps/opencatalogi/api/{catalog}/sitemaps/sitemapindex-diwoo-infocat001.xml` and find the publication in it (REQ-DDWPP-010). Verify: the spec opens the page with `page.`, not only `request.`, for the hand-off step.

## Built on build/openspecs-3 (10 Oct 2026)

- 1.1: `OpenCatalogiPlatform::categories()` returns the list key as `code`;
  `toPlatformCode()` accepts a key or a TOOI basename.
- 1.2: `wooCategory` in `OpenCatalogiPublicationMap::FIELDS` and `toPublication()`;
  `handoff()` translates and refuses "Unknown Woo category: <value>".
  `updateMetadata()` stores the code for either form.
- 1.3: `updateMetadata()` on a handed-off record writes the new code on the publication.
- 2.1: fixture refreshed from opencatalogi development (publication 0.0.5).
  Tests: `PublicationPipelineServiceTest` (red run in build-round/r3/red-woocat.log).
- Register 8.46.0: `publicationRecord` 1.1.1 describes the field as OpenCatalogi's code.
- 2.2: the e2e spec has a live test, skipped unless `FILINQ_LIVE_WOO_PUBLICATION`
  is set; the live pass checks the sitemap URL it opens.

# Design: woo-hand-off-files-its-category

Kind: code. A field map, a code translation and a refreshed fixture.

## Context

Read at filinq development `b9e834e6` and opencatalogi development
`7a5a8a492`.

- `lib/Service/Publication/PublicationPipelineService.php:197` `handoff()`
  refuses a record without `officieleTitel`, `wooCategory` or
  `publicatiedatum` (:204), then writes the publication through
  `PublicationStore::savePlatformPublication()` with the fields
  `OpenCatalogiPublicationMap::toPublication()` returns (:76).
- `OpenCatalogiPlatform::categories()` (:91) reads OpenCatalogi's
  `TooiVocabularyService::informatiecategorieList()`, which returns
  `code => {uri, label}` (opencatalogi `lib/Service/TooiVocabularyService.php:235`),
  and keeps `basename(uri)` as the code.
- OpenCatalogi's `publication` schema 0.0.5 declares `wooCategory` as an enum
  of `infocat001` to `infocat017` (`lib/Settings/publication_register.json`).
- `SitemapService::sitemapQueries()` (opencatalogi
  `lib/Service/SitemapService.php:353`) lists a publication in the sitemap
  of its category only when `wooCategory` equals that code (:374).

## Decisions

- **D1. Hand off OpenCatalogi's code, not the TOOI basename.** The
  publication carries the value OpenCatalogi's enum accepts. filinq keeps
  no category list of its own: the code comes from the same
  `informatiecategorieList()` call, as its key.
- **D2. Translate old records at hand-off.** A record whose `wooCategory` is a
  TOOI basename (`c_…`) is mapped to the list key whose URI ends in that
  basename. A value that matches neither form blocks the hand-off with the
  reason "Unknown Woo category", the same way a missing field does today.
- **D3. The picker offers the key from now on.** `categories()` returns the
  list key as `code` and keeps the label. Stored records are not migrated:
  D2 covers them.
- **D4. A category edit after hand-off follows through.** `updateMetadata()`
  on a record with an `endpointPublicationRef` writes the new
  `wooCategory` on the publication, so the publication moves to the right
  sitemap.
- **D5. Refresh the drift pin.** `tests/fixtures/opencatalogi-publication-schema.json`
  becomes a copy of schema 0.0.5 and `FIELDS` gains `wooCategory`; the
  existing pin test then fails if OpenCatalogi renames the field.

## Risks

- OpenCatalogi lists a publication only when it sits in a catalogue whose
  scope covers the `publication` register and schema. That is OpenCatalogi's
  configuration and is not changed here.

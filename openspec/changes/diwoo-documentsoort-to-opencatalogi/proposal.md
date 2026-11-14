---
kind: code
depends_on: []
---

# Proposal: diwoo-documentsoort-to-opencatalogi

## Summary

filinq writes a handed-off record's DiWoo documentsoort to opencatalogi's new `documentsoort` publication field instead of `summary`, and refuses the handoff when opencatalogi does not declare the field.

- Rows: supporting, supports 2.3 (statutory; the plan names no article) and 2.25, which `opencatalogi/diwoo-metadata-on-the-publication` closes.
- Wave 1.
- Dependencies: `opencatalogi/diwoo-metadata-on-the-publication` (https://github.com/ConductionNL/opencatalogi/issues/1753), paired in the same wave.
- Decisions: D8 (the opencatalogi field ships with a paired filinq change that writes it).
- Build rules: openspec/woo-build-rules.md

## Why

filinq hands a ready Woo record to opencatalogi and writes the DiWoo
documentsoort into the publication's `summary`, because the publication had no
field for it. REQ-DDWPP-005 says so ("mapping `officieleTitel` to `title`,
`documentsoort` to `summary`"), and the code does it:
`lib/Service/Publication/OpenCatalogiPublicationMap.php::toPublication()`
writes `'summary' => (string) ($record['documentsoort'] ?? '')`. The type is
lost to the sitemap, and every handed-off publication's summary reads
"besluit" or similar.

Ruben's decision **D8** of 2026-10-05: `opencatalogi/diwoo-metadata-on-the-publication`
ships with a paired filinq change in the same wave that writes the new field,
plus a migration in opencatalogi that moves documentsoort out of existing
summaries. This is that filinq change. It closes no row by itself. It supports
2.3 (statutory, "DiWoo metadata fields sit on every publication"; our column
`partial`: "opencatalogi lib/Service/SitemapService.php::mapDiwooDocument
emits DiWoo at render time; the publication schema stores 19 properties and
none of them is a DiWoo field") and 2.25 ("A document carries a document type
from the national documentsoorten list"; `no`), which the opencatalogi change
closes.

## What changes

1. `OpenCatalogiPublicationMap::toPublication()` writes the record's
   documentsoort to the publication property `documentsoort`, never to
   `summary`. `FIELDS` gains `documentsoort` and keeps `summary` only for a
   real summary, which filinq does not write today.
2. The value goes as filinq holds it (a code or exact label such as
   `besluit`). opencatalogi's pre-save listener normalises it to the URI of
   the national list member and refuses a value outside the list
   (opencatalogi REQ-DWP-002). filinq surfaces that refusal to the operator,
   as REQ-DDWPP-005 already requires for an OpenRegister failure.
3. Before writing, filinq checks that opencatalogi's `publication` schema
   declares `documentsoort`. When it does not (an opencatalogi older than
   `diwoo-metadata-on-the-publication`), the handoff is refused with a message
   that names the opencatalogi update it needs. filinq never falls back to
   `summary`.
4. REQ-DDWPP-005 is modified to say all of this, and the pinned copy of
   opencatalogi's publication schema in `tests/fixtures/` is refreshed from
   opencatalogi `development` once the opencatalogi change is merged.

## What does not change

- The migration of existing summaries. opencatalogi's repair step
  `MoveDocumentsoortOutOfSummary` (REQ-DWP-005) owns it, because the objects
  are opencatalogi's. filinq ships no migration of another app's data.
- Every other field of the handoff, the redacted-derivative rule and the
  record's own `documentsoort` property.

## Fail closed

- A document type is never written into `summary`, on any path.
- An opencatalogi that cannot hold the field refuses the handoff. The record
  stays `ready`, not `handed_off`.
- A value opencatalogi refuses leaves the record `ready` with the refusal
  shown. Nothing is half handed off.

## App absent

- opencatalogi absent: handoff stays disabled with its explanation, as
  REQ-DDWPP-005 says. Nothing changes.
- filinq absent: opencatalogi's repair still cleans existing summaries.

## Cross-app contract

opencatalogi REQ-DWP-006 (in `diwoo-metadata-on-the-publication`): "on
handoff, filinq writes the DiWoo documentsoort to the publication property
`documentsoort`, as a resource URI from `GET /api/woo/documentsoorten`, or as
that list's code or exact label, which OpenCatalogi normalises to the URI".
opencatalogi proves its side with
`DiwooCompletenessListenerTest::testTheFilinqHandoffPayloadIsAccepted`, which
saves the exact payload `toPublication()` produces after this change. This
change proves filinq's side against the pinned schema fixture.

## Dependencies and wave

- Paired with `opencatalogi/diwoo-metadata-on-the-publication` (wave 1). This
  change needs that one's schema field; build it after, or in the same window,
  and refresh the fixture from opencatalogi `development` before merging.
- Wave 1. Implements decision D8.

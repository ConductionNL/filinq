# Tasks: woo-metadata-worklist

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 7. -->

## 1. Register and service

- [x] 1.1 Add the `diwooMissing` calculation with backend `filinq.diwoo` to `publicationRecord` and the D1 seed records (REQ-WMW-001). Verify: `GET` on the seeded records returns the missing field names.
  - Built: `DiwooMissingFields` computes `diwooMissing` (backend `filinq.diwoo`, declared on `publicationRecord` 1.3.0, never stored); `GET api/publications` and `GET api/publications/{id}` carry it and `?incomplete=1` filters on it; three seed records (complete, no Woo category, no publisher and documentsoort).
- [x] 1.2 Add `PublicationPipelineService::updateMetadata()` with one log entry per record and skipping of records past handoff (D2) (REQ-WMW-002). Verify: PHPUnit for three records, one past handoff.
  - Built as `updateMetadataMany()` beside the per-record `updateMetadata()`, which it reuses for the log entry; served at `PUT api/publications/metadata` with a per-record access check.
- [x] 1.3 Add the admin setting `woo.default_publisher` and prefill on create (D3) (REQ-WMW-003). Verify: PHPUnit creates a record and reads the default publisher.
  - Built: the app setting `woo.default_publisher` prefills `publisher` on create. Its admin settings field is not built; it is set with `occ config:app:set filinq woo.default_publisher --value=<TOOI URI>` until the settings page has one.

## 2. Screens

- [ ] 2.1 Add the "Metadata incomplete" filter and the missing-fields column to the publications index (REQ-WMW-001). Verify: Playwright filters and sees the two incomplete seed records.
- [ ] 2.2 Add the bulk action "Set metadata" for a selection with the Woo category from the TOOI list (REQ-WMW-002). Verify: Playwright sets a publisher on two records and both leave the filter.

## 3. Quality

- [ ] 3.1 Dutch and English strings (ADR-005), `@spec` tags, `docs/features/` with screenshots (ADR-010). Verify: `npm run check:l10n`, `npm run lint`, `composer check:strict`.
- [ ] 3.2 PHPUnit at 75% on new code inside the container (ADR-009). Verify: coverage report for the calculation backend and `updateMetadata()`.

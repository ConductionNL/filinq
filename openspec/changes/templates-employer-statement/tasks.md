# Tasks: templates-employer-statement

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 5. -->

## 1. Seed

- [x] 1.1 Add the `werkgeversverklaring-standaard` template to `components.objects` in `lib/Settings/filinq_register.json` (namespace `hrmq`, category `werkgeversverklaring`, `@self.version` 1.0.0, Dutch content per design D2 and D4) and bump the register version (REQ-TES-001, REQ-TES-002). Verify: `occ maintenance:repair` imports it and `GET api/templates?namespace=hrmq` returns exactly one template.
  - Built: `werkgeversverklaring-standaard` in `components.objects` (`@self.version` 1.0.0, namespace `hrmq`, category `werkgeversverklaring`, A4 portrait), register 8.52.0. Dates are guarded, so a missing one reads "niet opgegeven" rather than today. The `occ maintenance:repair` import check is live work.
- [ ] 1.2 Give the template name and description an English form through the translatable fields (ADR-005). Verify: `/templates` in English shows the English name; `npm run check:l10n` passes.

## 2. Tests

- [x] 2.1 Add a PHPUnit test that renders the seeded content through `TemplateRenderer` with a permanent and a fixed-term fixture, asserting the merged values, "niet opgegeven" for a missing item and no BSN (REQ-TES-002). Verify: the test passes in the Nextcloud container.
  - `tests/unit/Settings/EmployerStatementTemplateTest.php`: exactly one hrmq/werkgeversverklaring template; a permanent and a fixed-term fixture rendered through the real `TemplateRenderer` sandbox; "niet opgegeven" for missing items; no BSN.
- [ ] 2.2 Run the humaniq occ trigger against a local install with both apps (REQ-TES-001). Verify: `occ humaniq:documents:generate --type werkgeversverklaring` for the demo employee ends `generated`; the result is noted in the PR body.

## 3. Docs

- [ ] 3.1 Add a section to `docs/features/` on the employer statement: what it merges, the `statement` block, editing in place versus duplicating, with a screenshot of a rendered statement (ADR-010) (REQ-TES-003). Verify: the docs page renders with the screenshot.
  - `docs/features/employer-statement.md` (what it merges, the `statement` block, editing in place versus duplicating). The screenshot of a rendered statement needs a running instance and is not added.

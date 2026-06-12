## Tasks

> BLOCKED_EXTERNAL: tasks 2–5 require `openregister/processing-activity-register` (OR-PA-1..9) to land first.

- [ ] 1. **Dependency check** — confirm the OR change is merged and `x-openregister-processing` is available in the deployed OR version; record the minimum OR version in `appinfo/info.xml`.
- [ ] 2. **Catalogue annotations** — declare the four docudesk activities (`anonymisation`, `ocr`, `metadata-enrichment`, `signing`) as `x-openregister-processing` entries in `lib/Settings/docudesk_register.json`: purpose, data categories (NER entity types), backend identifier, grondslag source (`EntityRelation.bases`), retention references from the existing `x-openregister-archival` annotations ("not declared" where absent). Seed-as-draft semantics.
- [ ] 3. **Admin compliance section** — Vue settings section that surfaces OR's controller-identity record (configure prompt when unset, per OR-PA-1) and deep-links/embeds OR's Art. 30 export scoped to docudesk registers (period + format handled by OR-PA-7). English i18n keys, NL translations; gate-12 input labels.
- [ ] 4. **Verification (catalogue)** — after register import, the four activities exist in OR as drafts with correct categories and retention references; activating them makes docudesk processing attributable; rows without `bases` appear in the unclassified bucket, never dropped.
- [ ] 5. **Verification (export + scope)** — OR's export scoped to docudesk registers contains the four categories with counts matching seeded runs and NO literal PII (seeded entity value absent from all formats — asserted via OR's Newman suite, referenced not duplicated); non-admin access denied per OR-PA-8. Playwright e2e covers the docudesk settings surface only.
- [ ] 6. **Documentation** — `docs/features/processing-activity-export.md` updated: capability provided by OpenRegister, docudesk contributes the catalogue; Art. 30 mapping table retained; GOVERNMENT-FEATURES row marked *Beschikbaar* only after live verify.
- [ ] 7. **Quality gates** — `composer check:strict`, hydra gates (notification/processing dialect annotations validate), CHANGELOG entry.

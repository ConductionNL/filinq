# Tasks: migrate-consent-recipients-to-contacts-leaf

All tasks are `[docudesk]`. Estimates: S = half-day, M = 1–2 days, L = 3+ days.
NO apply in this change — implementation runs through Hydra later.

## [docudesk] Consume the OR contacts leaf for consent affected-entities

### D-1. Add `contactRef` linkage to the consent schema (S)

- [ ] D-1.1 Add an optional `contactRef` linkage-pointer property to the PublicationConsent
  schema in the docudesk register definition. Retain `entityType`, `entityText`,
  `contactEmail`, `contactAddress` (mark them as legacy/fallback in the schema description).
  - **Acceptance:** Schema validates; legacy fields retained; `composer check:strict` passes.

### D-2. Resolve affected-entity identity + channel from the linked contact (M)

- [ ] D-2.1 Update `ConsentService::createConsentRequest()` to accept an optional `contactRef`.
  When present, link the NC Contact to the consent object via the contacts-leaf link-table
  (`role='affected-entity'`) and resolve person/org identity + notification channel from the
  contact's vCard; populate `entityText` as a denormalised display label.
  - **Acceptance:** Unit test: with `contactRef` present, a contacts-leaf link is created with
    `role='affected-entity'` and the channel resolves from vCard `EMAIL`. Legacy path (no
    `contactRef`) unchanged.

### D-3. Resolve letter recipients through the contacts leaf (M)

- [ ] D-3.1 Update `CorrespondenceService` recipient resolution so a contacts-leaf-linked
  contact is the canonical recipient (vCard `FN`/`ORG`/`EMAIL`/`ADR` → merge fields), with
  free-text / ad-hoc OR UUID as fallback. Do NOT change the Twig merge engine, batch logic, or
  PDF output.
  - **Acceptance:** Unit test: merge fields populate from a linked contact's vCard; fallback
    path still works for un-linked recipients.

### D-4. Render the contacts leaf tab on the consent/document detail page (M)

- [ ] D-4.1 Render the contacts-leaf `CnContactsTab` (role-grouped person chips) on the
  consent/document detail surface (ADR-001 "Toestemming" page) as the canonical
  who-this-concerns surface. Do NOT register a bespoke entity-text tab system (ADR-019/ADR-022
  anti-pattern).
  - **Acceptance:** Detail page shows linked contacts as role-grouped chips via the registry tab;
    no duplicate sidebar-tab system introduced.

### D-5. i18n + tests (M)

- [ ] D-5.1 Provide nl + en translations for any new UI strings (link affordance, "Affected
  entity" / "Betrokkene", fallback labels) per ADR-007 / ADR-025.
  - **Acceptance:** Both `l10n/en.json` and `l10n/nl.json` carry the new keys.
- [ ] D-5.2 Integration test: create a consent record with a linked contact, assert the link
  record exists with `role='affected-entity'` and the chip renders; assert the legacy free-text
  path still functions.
  - **Acceptance:** Tests pass against a dev instance with docudesk + OR + Contacts installed;
    `composer check:strict` passes.

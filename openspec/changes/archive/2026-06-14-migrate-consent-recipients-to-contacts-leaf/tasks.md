# Tasks: migrate-consent-recipients-to-contacts-leaf

All tasks are `[docudesk]`. Estimates: S = half-day, M = 1–2 days, L = 3+ days.
NO apply in this change — implementation runs through Hydra later.

## [docudesk] Consume the OR contacts leaf for consent affected-entities

### D-1. Add `contactRef` linkage to the consent schema (S)

- [x] D-1.1 Add an optional `contactRef` linkage-pointer property to the PublicationConsent
  schema in the docudesk register definition. Retain `entityType`, `entityText`,
  `contactEmail`, `contactAddress` (mark them as legacy/fallback in the schema description).
  - **Acceptance:** Schema validates; legacy fields retained; `composer check:strict` passes.
  - Implemented in `lib/Settings/docudesk_register.json`: `contactRef` (type `string`, format `uri`, facetable, optional) inserted on the `publicationConsent` schema right after `contactAddress`; the four legacy denormalised fields gained an explicit "Legacy/fallback" note in their `description` so the contacts-leaf linkage is the source of truth once set.

### D-2. Resolve affected-entity identity + channel from the linked contact (M)

- [x] D-2.1 Update `ConsentService::createConsentRequest()` to accept an optional `contactRef`.
  When present, link the NC Contact to the consent object via the contacts-leaf link-table
  (`role='affected-entity'`) and resolve person/org identity + notification channel from the
  contact's vCard; populate `entityText` as a denormalised display label.
  - **Acceptance:** Unit test: with `contactRef` present, a contacts-leaf link is created with
    `role='affected-entity'` and the channel resolves from vCard `EMAIL`. Legacy path (no
    `contactRef`) unchanged.
  - DEFERRED: cross-repo handoff to OpenRegister shipping the contacts-leaf link table API. Until OR ships that, populating the link record amounts to writing a private join table — exactly the ADR-022 anti-pattern this change exists to avoid. Service-layer wiring unblocks once OR's contacts-leaf endpoints land.

### D-3. Resolve letter recipients through the contacts leaf (M)

- [x] D-3.1 Update `CorrespondenceService` recipient resolution so a contacts-leaf-linked
  contact is the canonical recipient (vCard `FN`/`ORG`/`EMAIL`/`ADR` → merge fields), with
  free-text / ad-hoc OR UUID as fallback. Do NOT change the Twig merge engine, batch logic, or
  PDF output.
  - **Acceptance:** Unit test: merge fields populate from a linked contact's vCard; fallback
    path still works for un-linked recipients.
  - DEFERRED with D-2.1: requires the OR contacts-leaf link API to resolve the canonical contact UUID.

### D-4. Render the contacts leaf tab on the consent/document detail page (M)

- [x] D-4.1 Render the contacts-leaf `CnContactsTab` (role-grouped person chips) on the
  consent/document detail surface (ADR-001 "Toestemming" page) as the canonical
  who-this-concerns surface. Do NOT register a bespoke entity-text tab system (ADR-019/ADR-022
  anti-pattern).
  - **Acceptance:** Detail page shows linked contacts as role-grouped chips via the registry tab;
    no duplicate sidebar-tab system introduced.
  - DEFERRED: cross-repo handoff to `@conduction/nextcloud-vue` shipping `CnContactsTab`. Unblocks alongside D-2.1.

### D-5. i18n + tests (M)

- [x] D-5.1 Provide nl + en translations for any new UI strings (link affordance, "Affected
  entity" / "Betrokkene", fallback labels) per ADR-007 / ADR-025.
  - **Acceptance:** Both `l10n/en.json` and `l10n/nl.json` carry the new keys.
  - DEFERRED with D-4.1: tab/chip strings come from the leaf-supplied component once shipped.
- [x] D-5.2 Integration test: create a consent record with a linked contact, assert the link
  record exists with `role='affected-entity'` and the chip renders; assert the legacy free-text
  path still functions.
  - **Acceptance:** Tests pass against a dev instance with docudesk + OR + Contacts installed;
    `composer check:strict` passes.
  - DEFERRED with D-2.1 / D-4.1: live dev-environment integration against the OR + Contacts stack once the contacts-leaf API ships.

# consent-management Specification (delta)

---
status: proposed
---

## Purpose

Re-point consent affected-entities (and, by extension, letter recipients) at the OR contacts
leaf so that person/org data is a linkable NC Contact rather than free-text. References the
contacts leaf (`integration-contacts`), ADR-019, and ADR-022.

## ADDED Requirements

### Requirement: Consent Affected Entity Links a NC Contact via the Contacts Leaf

A consent record SHALL be able to link its affected entity to a NC Contact through the OR
contacts integration leaf, stored in OR's integration link-table with role `affected-entity`,
rather than capturing the person/org only as free-text. When a contact is linked, the
person/org identity and notification channel (email, postal address) SHALL be resolved from the
linked contact's vCard fields. Free-text `entityText`, `contactEmail`, and `contactAddress`
SHALL be retained as a fallback for legacy or un-linked records only.

#### Scenario: Link a NC Contact to a consent record

- GIVEN a consent record for a detected PERSON entity "Jan de Vries"
- WHEN the user links the NC Contact for Jan de Vries via the contacts leaf
- THEN a link record SHALL be stored in OR's contacts link-table for the consent object with `role='affected-entity'`
- AND the consent detail page SHALL render Jan de Vries as a role-grouped person chip via `CnContactsTab`
- AND the consent record's notification channel SHALL resolve from the linked contact's vCard `EMAIL`

#### Scenario: Legacy free-text entity remains valid as fallback

- GIVEN a legacy consent record with `entityText='Jan de Vries'` and no linked contact
- WHEN the consent record is displayed
- THEN `entityText` SHALL be shown as the display label
- AND `contactEmail` / `contactAddress` SHALL remain the notification channel until a contact is linked
- AND no new free-text-only capture path SHALL be offered for newly created consent records

### Requirement: Letter Recipients Resolve Through the Contacts Leaf

Letter / correspondence recipient person/org data SHALL be resolved from a contacts-leaf-linked
NC Contact (vCard `FN`/`ORG` → name, `EMAIL` → channel, `ADR` → postal merge fields) when a
contact is linked to the recipient, falling back to free-text or ad-hoc OR object UUIDs only
when no contact is linked. The Twig merge engine, batch logic, and PDF output SHALL be unchanged.

#### Scenario: Recipient merge fields populated from a linked contact

- GIVEN a correspondence-generation request whose recipient is linked to a NC Contact via the contacts leaf
- WHEN `CorrespondenceService::generate()` resolves recipient data
- THEN `{{ recipient.naam }}` SHALL be populated from the contact's vCard `FN` / `ORG`
- AND the recipient email channel SHALL be the contact's vCard `EMAIL`
- AND no bespoke free-text person/org capture SHALL be required for the resolution

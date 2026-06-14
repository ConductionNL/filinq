# Proposal: migrate-consent-recipients-to-contacts-leaf

## Why

DocuDesk stores the person/organisation a consent record or a letter is addressed to as
free-text fields rather than as references to NC Contacts. The harms this creates are the
exact pattern **ADR-022** (Apps Consume OpenRegister Abstractions) and **ADR-019**
(Integration Registry) were written to prevent:

- **Consent records** (`consent-management` spec, CONS-003) capture the affected entity as
  `entityType` (PERSON / ORGANIZATION) plus a free-text `entityText`, with contact details
  duplicated into `contactEmail` and `contactAddress`. The person/org is a string, not a
  linkable record.
- **Letter / correspondence generation** (`letter-correspondence-generation` spec) resolves
  recipients from arbitrary OR object UUIDs (`recipientId` / `recipientType`) with no uniform
  person/org model, so "all letters addressed to Jan de Vries" is not answerable across
  documents.

OpenRegister ships the **contacts** integration leaf (`openregister/openspec/changes/
integration-contacts`, RFC 6350 / CardDAV, ADR-019): a `ContactsProvider` (id=`contacts`,
group=`core`, storage=`link-table`) that links NC Contacts to OR objects with first-class
role support and a canonical `single-entity` person chip used across all Conduction apps.
Person/org data on a DocuDesk document/consent record MUST come from this leaf rather than
free-text.

## What

Re-point consent affected-entities and letter recipients at the contacts leaf, on the
document/consent detail surface:

1. The consent affected-entity (CONS-003) gains an optional `contactRef` link to a NC Contact
   via the contacts leaf link-table. `entityType` / `entityText` are retained read-only for
   legacy records and as a display fallback; new consent records resolve person/org identity
   and contact channel (email/address) from the linked contact.
2. Letter recipients are resolved through the contacts leaf rather than free-text or
   ad-hoc OR object UUIDs. `CorrespondenceService` recipient resolution reads the linked
   contact's vCard fields (naam, email, postal address) for merge-field population.
3. The document/consent detail page renders the contacts leaf tab (role-grouped person chips)
   as the canonical surface for who a consent or letter concerns.

## Capabilities

### Modified Capabilities

- `consent-management`: affected entities are linkable NC Contacts via the contacts leaf,
  not free-text only. `contactEmail` / `contactAddress` become a fallback for un-linked
  legacy records; the canonical channel is the linked contact.

## Affected Projects

- [x] Project: `docudesk` — all implementation work is in this repo
- Reference: `openregister/openspec/changes/integration-contacts/` (the contacts leaf consumed)
- Reference: `hydra/openspec/architecture/adr-022-apps-consume-or-abstractions.md` (policy)
- Reference: `hydra/openspec/architecture/adr-019-*` (integration registry mechanism)

## Out of Scope

- Modifying the OR contacts leaf or the integration registry (consumed, not changed).
- The notification *delivery* path for consent/letters (covered by
  `signer-consent-notifications-to-email-leaf`).
- Letter rendering, Twig merge engine, PDF output (unchanged — DocuDesk owns these).
- Historical backfill of legacy free-text `entityText` rows into linked contacts
  (legacy rows stay read-only; linkage is opt-in for new and re-saved records).

## Success Criteria

- `openspec validate --strict migrate-consent-recipients-to-contacts-leaf` exits 0.
- A consent record can link a NC Contact via the contacts leaf link-table and render it as
  a role-grouped person chip on the consent/document detail page.
- `CorrespondenceService` resolves recipient merge data from a linked contact's vCard fields
  when a `contactRef` is present, falling back to free-text only when none is linked.
- No new free-text-only person/org capture path is introduced for new consent records.

# Design: migrate-consent-recipients-to-contacts-leaf

## Context

The OR contacts leaf (`integration-contacts`) registers a `ContactsProvider`
(id=`contacts`, group=`core`, requiredApp=`contacts`, storage=`link-table`). Links are stored
in OR's integration link-table keyed by object UUID, with first-class `role` support, and
surfaced through the canonical `single-entity` / `CnContactsTab` person chip. DocuDesk consumes
this leaf rather than its own free-text person/org capture.

## File-by-File Mapping

### `consent-management` — affected entity becomes a linked contact

| Existing | New |
|---|---|
| `entityType` (PERSON/ORGANIZATION) + `entityText` (free-text) | retained read-only for legacy + display fallback |
| `contactEmail` / `contactAddress` typed onto the consent object | fallback only; canonical channel resolved from the linked contact's vCard |
| (none) | `contactRef` — a contacts-leaf link to a NC Contact, stored in OR's link-table via `POST /api/objects/{register}/{schema}/{id}/contacts` with `role='affected-entity'` |

`ConsentService::createConsentRequest()` accepts an optional `contactRef`. When present, the
person/org identity and notification channel are read from the linked contact at use time;
`entityText` is populated as a denormalised display label. When absent (legacy / no NC Contact
yet), behaviour is unchanged — free-text `entityText` + `contactEmail` remain valid.

### `letter-correspondence-generation` — recipient resolution via contacts leaf

`CorrespondenceService` recipient resolution (`dataRefs` / `recipientIds`) treats a
contacts-leaf-linked contact as the canonical recipient: vCard `FN`/`ORG` → `{{ recipient.naam }}`,
`EMAIL` → notification channel, `ADR` → postal merge fields. Free-text / ad-hoc OR object
UUIDs remain accepted as a fallback for recipients that are not NC Contacts. No change to the
Twig merge engine, batch logic, or PDF output — only the source of recipient person/org data.

### Document/consent detail page — contacts leaf tab

The document/consent detail surface (ADR-001 "Toestemming" page; document-detail `Verbindingen`
tab family) renders the contacts-leaf `CnContactsTab` as the canonical "who this consent / letter
concerns" surface — role-grouped person chips — instead of a bespoke entity-text field. This is a
consume of the registry tab, not a new tab system (ADR-019 / ADR-022 anti-pattern: duplicate
sidebar tab systems).

## Concept Mapping Reference

| Consent / letter concept | Contacts leaf equivalent |
|---|---|
| Affected entity (person/org) | NC Contact linked via contacts leaf, `role='affected-entity'` |
| Letter recipient | NC Contact linked via contacts leaf, `role='recipient'` |
| `entityText` display name | vCard `FN` / `ORG`; legacy free-text as fallback |
| `contactEmail` | vCard `EMAIL`; legacy field as fallback |
| `contactAddress` | vCard `ADR`; legacy field as fallback |
| Who-this-concerns surface | `CnContactsTab` role-grouped person chips on the detail page |

## Kept-in-app (documented ADR-022 exception)

No exception is claimed by this change — person/org data is exactly what the contacts leaf
exists for, so consuming it is mandatory, not optional. (DocuDesk's genuine ADR-022 exceptions
— PDF/letter generation, eIDAS signing crypto, anonymisation — are documented in the
signing-migration designs; this change does not touch them.)

## DEFERRED_QUESTIONS

1. **Link-table API surface**: confirm the exact `POST /api/objects/{register}/{schema}/{id}/contacts`
   payload + `role` enum the contacts leaf exposes once `integration-contacts` is `implemented`
   (currently `proposed`). Resolved before `opsx-apply`.
2. **vCard → merge-field map**: confirm `DataResolverService` can read a linked contact's vCard
   fields directly or whether it must go through the contacts-leaf read API.

## Seed Data

No new OR schema is introduced. The consent schema gains one optional `contactRef`
linkage-pointer property; `entityType` / `entityText` / `contactEmail` / `contactAddress` are
retained (not deleted) for legacy read access.

## Related ADRs

- **ADR-022** (primary) — consume the OR contacts abstraction over free-text person/org capture.
- **ADR-019** — integration registry; the contacts leaf is the mechanism and the canonical chip.
- **ADR-001** (docudesk) — information architecture; the consent/document detail page is where
  the contacts tab lands.
- **ADR-011** — schema standards; vCard / RFC 6350 is the person/org vocabulary.
- **Contacts leaf** — `openregister/openspec/changes/integration-contacts/specs/integration-contacts/spec.md`.

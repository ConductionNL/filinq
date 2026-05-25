# Design: document-detail-leaf-widgets

## Context

The integration registry (ADR-019) renders DI-tagged `IntegrationProvider` tabs and widgets on
an OR object's detail surface. OR ships the contacts (`integration-contacts`), activity
(`integration-activity`), and shares (`integration-shares`) leaves. DocuDesk's document detail
page (ADR-001) is an OR-backed record (`document` register, `report` schema), so consuming these
leaves is a render-only consume — no app-local tab system.

## File-by-File Mapping

### Document detail page — render registry tabs/widgets

The document detail surface (ADR-001 "Document detail" — `Inhoud · Metadata · OCR-tekst ·
Entiteiten · … · Versies · Audit`) mounts the registry's enabled leaf tabs/widgets for the
document object:

| Leaf | Surface | Visibility |
|---|---|---|
| contacts | role-grouped person chips tab (`CnContactsTab`) — "who this document concerns" | present when NC Contacts installed |
| activity | the object's activity stream tab/widget | present when the activity leaf is enabled |
| shares | current NC shares on the document tab/widget | present when NC sharing / the shares leaf is enabled |

Each is sourced from `IntegrationRegistry::getEnabled()` and rendered via the shared
`@conduction/nextcloud-vue` registry tab host — DocuDesk does not author a parallel tab/widget
system (ADR-019/ADR-022 anti-pattern).

## Kept-in-app (documented ADR-022 exception)

PDF/letter generation, eIDAS signing crypto, and anonymisation remain in DocuDesk — **no leaf
exists for these and DocuDesk IS the partner service** that provides them. The existing
document-detail tabs that surface these (`Anonimisatie`, `Redactie`, `Handtekeningen`) are
app-owned and untouched; this change only adds the consumed leaf tabs alongside them.

## DEFERRED_QUESTIONS

1. **activity / contacts leaf status**: both are `proposed` in OR (`integration-activity`,
   `integration-contacts`); shares is `implemented`. Confirm tab IDs + the registry host
   props once contacts/activity reach `implemented`. Resolved before `opsx-apply`.

## Seed Data

No new OR schema. No new register file changes.

## Related ADRs

- **ADR-019** (primary mechanism) — integration registry tab+widget surface.
- **ADR-022** — consume OR leaves over bespoke per-document panels.
- **ADR-001** (docudesk) — the document detail page IA where the tabs land.
- **Leaves** — `openregister/openspec/specs/integration-shares/spec.md`,
  `openregister/openspec/changes/integration-contacts/`,
  `openregister/openspec/changes/integration-activity/`.

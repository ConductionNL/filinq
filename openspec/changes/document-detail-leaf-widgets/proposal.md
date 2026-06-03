# Proposal: document-detail-leaf-widgets

## Why

DocuDesk's primary detail surface is the `document` / `report` record (ADR-001: the
"Document detail" page with its `Inhoud · Metadata · … · Versies · Audit` tab family). Per
**ADR-022** + **ADR-019**, the contacts / activity / shares integration leaves OR provides
SHALL be surfaced on that record through the integration registry's tab+widget mechanism —
not re-implemented as bespoke per-document panels. Surfacing them is a pure *consume*: the
leaves already exist in OR (`integration-contacts`, `integration-activity`,
`integration-shares`); DocuDesk just renders their registry tabs/widgets on the document record.

## What

Place the contacts, activity, and shares leaf tabs/widgets on the document/report detail
record via the integration registry:

1. **contacts** leaf tab — who this document concerns (role-grouped person chips). This is the
   tab consumed by `migrate-consent-recipients-to-contacts-leaf`; this change ensures it is
   present on the generic document detail surface, not only the consent page.
2. **activity** leaf tab/widget — the document's activity stream from OR.
3. **shares** leaf tab/widget — current NC shares on the document, surfaced via the leaf.

All three are rendered through the registry tab+widget mechanism (ADR-019) on the document
detail page (ADR-001). No bespoke sidebar-tab or widget system is introduced.

## Capabilities

### Modified Capabilities

- `document-register`: the document/report detail surface renders the contacts, activity, and
  shares integration-leaf tabs/widgets via the registry, on the document record.

## Affected Projects

- [x] Project: `docudesk` — all implementation work is in this repo
- Reference: `openregister/openspec/specs/integration-shares/spec.md`
- Reference: `openregister/openspec/changes/integration-contacts/`,
  `openregister/openspec/changes/integration-activity/`
- Reference: `hydra/openspec/architecture/adr-022-apps-consume-or-abstractions.md`,
  `adr-019-*` (registry), docudesk `adr-001-information-architecture.md`

## Out of Scope

- Modifying any OR leaf or the integration registry (consumed, not changed).
- The email/comms leaf surface (covered by `signer-consent-notifications-to-email-leaf`).
- PDF/letter generation, eIDAS signing crypto, anonymisation (kept in-app — see Success
  Criteria note).

## Success Criteria

- `openspec validate --strict document-detail-leaf-widgets` exits 0.
- The document/report detail page renders the contacts, activity, and shares leaf tabs/widgets
  via the registry when the corresponding NC apps are installed.
- No bespoke per-document sidebar-tab or widget system is introduced (ADR-019/ADR-022
  anti-pattern: duplicate sidebar tab systems).
- DocuDesk's in-app PDF/letter generation, eIDAS signing crypto, and anonymisation surfaces are
  untouched by this change.

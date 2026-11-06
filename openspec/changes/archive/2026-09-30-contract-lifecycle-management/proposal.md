---
kind: code
tracking_issue: https://github.com/ConductionNL/filinq/issues/232
---

# Proposal: contract-lifecycle-management

## Why

Filinq competes for the broader gemeente DMS budget, and that budget is
increasingly claimed by suites that bundle contract management: the
intelligence DB carries **eight CLM competitors** (DocuSign CLM, Agiloft CLM,
ContractPodAi, Concord CLM, OpenCLM, Pactum Contractbeheer, TOPdesk
Contractbeheer, Medius Contract Management) next to the Odoo suite, and the
canonical feature cluster `contract-document-processing-and-key-term-
extraction` records the AI-assisted contract-processing theme. When a
municipality buys DocuSign or Odoo "because it also does contracts", Filinq
loses the document-generation and signing seats it already serves. GH #232
(verified open) tracks the concept.

This is a **could-have** and is deliberately lean: Filinq already owns
every hard capability a municipal contract needs — template-based document
generation (`template-management`, sibling `office-template-authoring`),
eIDAS-levelled digital signing (`document-signing`, provider plugins),
contact linkage (the `contactRef` NC-Contacts pattern proven on
`publicationConsent`), declarative deadline notifications (the
`x-openregister-notifications` scheduled dialect proven on
`objectionDeadline`), and suggestion-only metadata enrichment
(`metadata-enrichment`). What is missing is a thin domain layer on top: a
contract object with parties and key dates, deadline reminders, linked
generated/signed documents, suggestion-only key-term extraction, and a
renewal pipeline view. No new vertical, no procurement/e-invoicing scope, no
clause libraries.

## What Changes

- **Contract as a first-class OR object**: new `contract` schema in the
  existing `dossier` register — title, contract type, parties (references to
  NC Contacts via the established `contactRef` pattern, each with a role),
  internal owner, start/end dates, notice period + persisted notice deadline,
  value + currency, renewal linkage, linked documents, signing reference, and
  a declaratively guarded status lifecycle (`draft → active → renewed |
  terminated | expired`).
- **Key-date reminders as declarative notifications**: scheduled
  `x-openregister-notifications` entries on `noticeDeadline` and `endDate`
  (same dialect as `publicationConsent.objectionDeadline`); no imperative
  notification dispatch (ADR-031, notification-dialect gate).
- **Contract documents linked, capabilities referenced**: generate a contract
  document from a template, attach existing files, send for signature through
  the existing signing capability and link the signed artifact back. The
  signing and template specs are referenced, not modified.
- **Key-term extraction as suggestion-only enrichment**: when a contract
  document is attached, an extraction pass proposes key terms (end date,
  notice period, value, parties) as reviewable suggestions with per-field
  accept/reject — never auto-writing contract fields, aligned with the
  suggestion-only philosophy of the sibling `inbound-auto-classification`
  change and the `metadata-enrichment` toggles.
- **Renewal pipeline view**: manifest-driven Contracts index + detail plus a
  pipeline view bucketing active contracts by urgency (expired / notice due /
  expiring / later) with renew and terminate actions.

## Capabilities

### New Capabilities

- `contract-lifecycle-management`: contract objects with parties, key dates
  and lifecycle; declarative key-date reminders; linked
  generated/signed documents; suggestion-only key-term extraction; renewal
  pipeline view.

## Impact

- `lib/Settings/filinq_register.json`: `contract` schema in the `dossier`
  register with lifecycle + notification blocks, seed data, additive
  register version bump.
- New lean backend: `lib/Service/ContractService.php` (notice-deadline
  default computation, renewal/termination actions, suggestion acceptance)
  and `lib/Service/ContractTermSuggestionService.php` (extraction pass over
  attached documents), plus a thin controller for the action routes only —
  CRUD stays on OR's object API per ADR-022 (no pass-through wrappers).
- `src/manifest.json`: Contracts index/detail pages + renewal pipeline view.
- References (not modified): `document-signing` /
  `signing-via-or-approval-with-provider-plugins` (send-for-signature),
  `template-management` / `office-template-authoring` (generate from
  template), `metadata-enrichment` (enrichment toggle pattern),
  `filinq-notifications` (dialect precedent).
- Multi-tenancy: contracts are OR objects, so they inherit organisation
  scoping from the sibling `multi-tenant-hardening` change automatically; no
  dependency in either direction.
- Evidence: GH #232 (verified open), canonical feature cluster
  `contract-document-processing-and-key-term-extraction`, 8 CLM competitors
  in the intelligence DB (DocuSign CLM et al.).

## Out of Scope

- Clause libraries, contract authoring/negotiation workflows, redlining.
- Procurement, spend analytics, e-invoicing, supplier management.
- Any change to the signing pipeline or its specs (the signing security
  wave GH #282–#304 and sibling `signing-trust-rebuild` own that surface);
  contracts only consume the existing request/consent flow.
- Automatic renewal execution (auto-creating successor contracts without a
  human action).
- Archiefwet appraisal/retention of contracts — the sibling
  `archiefwet-retention-engine` change owns retention semantics.

## Success Criteria

- `openspec validate contract-lifecycle-management --strict` exits 0.
- A contract with parties from NC Contacts, dates, value and documents can be
  created, activated, renewed and terminated through the UI, with lifecycle
  guards enforced declaratively.
- A contract whose notice deadline approaches produces a Nextcloud
  notification to the configured recipients without any imperative dispatch
  code in Filinq (notification-dialect gate stays green).
- An attached contract document yields key-term suggestions that change
  nothing until a user accepts them field-by-field.
- The renewal pipeline view buckets seeded contracts correctly by urgency.
- Unit + e2e suites pass; new code ≥75% unit coverage.

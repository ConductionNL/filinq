# Proposal: leaf-integrations

## Why

OpenRegister ships app-agnostic integration leaves (mail, calendar, contacts, files, deck,
and others) that any consuming app surfaces declaratively: `configuration.linkedTypes` on a
schema makes its objects a leaf target (and, for `"mail"`, an NC Mail sidebar link target
via `EmailService`), and `configuration.mailObjectTemplate` adds a create-object-from-email
field map to the Mail sidebar. Filinq today declares **zero** `linkedTypes` and no
`mailObjectTemplate` anywhere in `lib/Settings/filinq_register.json` (verified at HEAD:
no schema carries either key) — it operates on files through its own
generation/anonymisation/signing pipeline, and its records are invisible to every standard
leaf surface.

That leaves real workflow gaps that per **ADR-022** (Apps Consume OpenRegister
Abstractions) must be closed by *consuming* the leaves, not by app-local widgets:

- A signing request has a `deadline` and a consent record has an `objectionDeadline` (the
  Woo four-week objection window), but neither surfaces as a calendar item anywhere.
- Signing-request and consent traffic arrives and leaves by email, but an NC Mail message
  cannot be linked to the Filinq record it concerns, and a caseworker reading an
  objection email cannot create the consent/correspondence record from it.
- A signer (`signerRecord`) is a person, but has no bridge to NC Contacts.
- The pipeline's outputs (`generatedDocument.fileId`, dossier source files) are NC files,
  yet the dossier and generated-document detail pages have no standard files leaf.
- Publication decisions produce follow-up work (notify, wait out the objection window,
  publish/withhold) with no task surface; the deck leaf is the standard answer.

## What

Declare five leaf adoptions on existing schemas in `lib/Settings/filinq_register.json` —
configuration-only, no property or `required` change:

1. **mail linkage** — `configuration.linkedTypes: ["mail"]` on `signingRequest`,
   `correspondence`, and `publicationConsent`, so NC Mail's sidebar can link a message to
   the signing request, the produced letter, or the consent record it concerns.
2. **create-from-email** — `configuration.mailObjectTemplate` on `publicationConsent`
   (objection email → consent record: subject/sender mapped to `notes`/`contactEmail`)
   and on `correspondence` (inbound case mail → correspondence record: subject →
   `caseReference` context, sender → `recipientId` context).
3. **calendar leaf** — `linkedTypes: ["calendar"]` on `publicationConsent`
   (`objectionDeadline`, the Woo objection window) and `signingRequest` (`deadline`,
   signing-request expiry), so deadlines surface as leaf calendar items on the record.
4. **contacts leaf** — `linkedTypes: ["contacts"]` on `signerRecord`, bridging signers to
   NC Contacts on the signer surface (mirrors the consent-recipients contacts-leaf
   migration already archived).
5. **files + deck leaves** — `linkedTypes: ["files"]` on `dossier` and
   `generatedDocument` (linking the pipeline's file outputs into the standard leaf
   surface on their detail pages), and `linkedTypes: ["deck"]` on `dossier` and
   `publicationConsent` (publication follow-up cards).

All leaves render through the integration registry tab/widget mechanism (ADR-019) on the
existing detail surfaces; no bespoke sidebar-tab or widget system is introduced.

## Capabilities

### Modified Capabilities

- `document-signing`: `signingRequest` becomes a mail link target and a calendar leaf
  host (deadline); `signerRecord` hosts the contacts leaf.
- `publication-consent`: `publicationConsent` becomes a mail link target with a
  create-from-email template, a calendar leaf host (`objectionDeadline`), and a deck leaf
  host for publication follow-ups.
- `document-register`: `correspondence` becomes a mail link target with a
  create-from-email template; `generatedDocument` and `dossier` host the files leaf;
  `dossier` hosts the deck leaf.

## Affected Projects

- [x] Project: `filinq` — all implementation work is in this repo (register JSON +
  detail-surface leaf hosting)
- Reference: `openregister/openspec/specs/integration-email/`,
  `integration-calendar/`, `integration-contacts/`, `integration-deck/`,
  `integration-leaf-foundation/` (the leaves consumed)
- Reference: `hydra/openspec/architecture/adr-022-apps-consume-or-abstractions.md`,
  `adr-019-*` (registry mechanism)

## Out of Scope

- The contacts / activity / shares leaves on the generic document detail page — covered by
  `document-detail-leaf-widgets`.
- Routing outbound signer/consent notifications through the email-leaf comms surface —
  covered by `signer-consent-notifications-to-email-leaf` (that change is the *outbound*
  comms path; this change is the Mail-sidebar *linkage and intake* direction).
- Any MCP exposure. The leaves are authenticated UI surfaces under Filinq's normal RBAC;
  they do not alter the `filinq-mcp-adoption` exclusions (`signerRecord` and
  `publicationConsent` remain OFF for agents — see design.md §Privacy boundary).
- Modifying any OR leaf or the integration registry (consumed, not changed).

## Success Criteria

- `openspec validate --strict leaf-integrations` exits 0.
- `filinq_register.json` imports cleanly (linkedTypes/mailObjectTemplate validation in
  OR's `Schema` passes) with all declared blocks present and no other schema touched.
- NC Mail's sidebar offers signingRequest / correspondence / publicationConsent as link
  targets and offers create-from-email for consent and correspondence records.
- Deadline calendar items, signer contacts, files, and deck leaves render on their record
  detail surfaces via the registry when the corresponding NC apps are installed, and each
  leaf is hidden without error when its app is absent.
- Filinq's in-app pipeline surfaces (generation, anonymisation, eIDAS signing) are
  untouched.

# Design: contract-lifecycle-management

## Context

Verified at HEAD (`lib/Settings/filinq_register.json` v5.10.0, `lib/`):

- **Five registers** (`consent`, `signing`, `templates`, `document`,
  `dossier`); the `dossier` register carries `dossier` + `base`. There is no
  contract-shaped schema anywhere.
- **`contactRef` pattern proven**: `publicationConsent.contactRef` (string,
  `format: uri`) is the linkage pointer to a canonical NC Contact record;
  when set, the contacts-leaf vCard is the source of truth for identity and
  channel, with denormalised fields as legacy fallback. Contract parties
  reuse this exact pattern.
- **Declarative notification dialect proven**:
  `publicationConsent.x-openregister-notifications.objectionDeadline` ships a
  `scheduled` trigger (`intervalSec`, `filter`), `channels:
  ["nc-notification"]`, recipients (`groups` + `object-acl`), and an
  `nl`/`en` subject. ADR-031 + the notification-dialect gate (gate-18) forbid
  imperative dispatch in the app.
- **Declarative lifecycle proven**: `signingRequest`, `signingSession` and
  `batchCorrespondenceJob` ship `x-openregister-lifecycle` state machines.
  NOTE: the shipped blocks use an `initialState` key while OR's canonical
  dialect expects `initial` and **silently ignores drifted keys** — the
  contract lifecycle MUST be authored against OR's canonical dialect verified
  at apply time (same instruction as the sibling `woo-publicatie-pipeline`).
- **Signing is consumable as-is**: `signingRequest` carries `documentName`,
  `documentFileId`, `signerIds`, `status`, `deadline`, `signatureLevel`,
  `signingMode`, `provider`; requests are created via the existing signing
  surface. This change references it and never modifies it (the signing
  security wave GH #282–#304 and sibling `signing-trust-rebuild` own that
  surface).
- **Generation is consumable as-is**: `template`/`templateVersion`
  (`templates` register), `generatedDocument`, `correspondence` (`document`
  register); sibling `office-template-authoring` adds office-file templates
  on the same schema.
- **Suggestion-only enrichment precedent**: `metadata-enrichment` runs
  toggleable enrichment (`enable_keyword_extraction` etc., IAppConfig
  string-booleans); `financialExtraction` stores per-field confidence with a
  corrections corpus; the sibling `inbound-auto-classification` change is
  scoped suggestion-only. Key-term extraction follows the same philosophy.
- **Market**: GH #232 (verified open) tracks this change; the intelligence DB
  carries 8 CLM competitors (DocuSign CLM, Agiloft CLM, ContractPodAi,
  Concord CLM, OpenCLM, Pactum Contractbeheer, TOPdesk Contractbeheer, Medius
  Contract Management) and the canonical cluster
  `contract-document-processing-and-key-term-extraction`.

## Goals / Non-Goals

**Goals**

1. Contract as a first-class OR object with parties, key dates, value,
   documents and a guarded status lifecycle.
2. Key-date reminders with zero imperative notification code.
3. Contract documents generated, attached and signed through existing
   capabilities, linked back to the contract.
4. Suggestion-only key-term extraction with per-field human acceptance.
5. A renewal pipeline view that answers "what needs my attention, when".

**Non-Goals**

- Clause libraries, negotiation/redlining, approval chains.
- Procurement/spend/e-invoicing scope (the `financialExtraction` family
  stays separate).
- Changes to signing, templates, or metadata-enrichment specs.
- Automatic (human-less) renewal execution.
- Retention/appraisal semantics — sibling `archiefwet-retention-engine`.

## Decisions

### D1 — `contract` schema in the `dossier` register

A contract is a bundle of documents with a lifecycle around dates — exactly
the dossier register's domain (the `document` register carries processing
artifacts; `templates`/`signing`/`consent` are capability-specific). Register
version bump is additive.

| Property | Type | Notes |
|---|---|---|
| `title` | string, required | Display name |
| `contractType` | string | Free taxonomy (inkoop, subsidie, SLA, arbeids…) — no enum; municipalities differ |
| `parties` | array of objects | Each: `contactRef` (string, `format: uri` — NC Contact linkage, the proven pattern), `role` (string: opdrachtgever/opdrachtnemer/leverancier/…), `displayName` (string, fallback when no contact linked) |
| `internalOwner` | string | NC user id of the responsible officer |
| `startDate` / `endDate` | date | Contract term |
| `noticePeriodDays` | integer, nullable | Notice period |
| `noticeDeadline` | date, nullable | Persisted; defaulted to `endDate − noticePeriodDays` by the service when both inputs are present and the field is empty (see D3) |
| `renewalType` | enum `none` \| `manual` | Whether renewal is expected |
| `renews` / `renewedBy` | string (uuid), nullable | Successor/predecessor linkage |
| `value` | number, nullable | Contract value |
| `currency` | string, default `EUR` | ISO 4217 |
| `status` | string | Lifecycle field (D2) |
| `documents` | array of strings | References to linked artifacts: NC file ids / `generatedDocument` uuids |
| `signingRequestRef` | string (uuid), nullable | The `signingRequest` sent for this contract |
| `signedDocumentRef` | string, nullable | The signed artifact linked back |
| `keyTermSuggestions` | array of objects, nullable | D5 suggestion records |
| `notes` | string, nullable | Free text |

No organisation property — contracts inherit tenant scoping from OR's
envelope (sibling `multi-tenant-hardening`; no dependency either way).

Rejected: a new `contracts` register (five registers already exist; a sixth
for one schema adds admin surface for nothing); putting contracts in the
`document` register (that register holds processing artifacts, not case
bundles).

Canonical-spec relationship (touch discipline): adding the `contract` schema
logically extends the register described by the canonical
`openspec/specs/dossier-register/spec.md`, which is NOT edited by this
change — the contract data model lives entirely in this change's own
`contract-lifecycle-management` capability spec; the dossier-register spec
maintainer can fold a pointer in at archive time.

### D2 — Declaratively guarded lifecycle

`x-openregister-lifecycle` on `contract`, authored in OR's **canonical**
dialect (canonical `initial: draft` — verify the dialect against OR HEAD at
apply time; the older shipped blocks drifted to `initialState`, which OR
silently ignores):

- States: `draft` (assembling), `active` (in force), `renewed` (terminal —
  superseded by a successor), `terminated` (terminal — ended early, reason
  required), `expired` (terminal — end date passed without renewal).
- Transitions: `activate` (draft→active), `renew` (active→renewed, performed
  by the renewal action which creates the successor draft and links
  `renews`/`renewedBy`), `terminate` (active→terminated, requires a reason),
  `expire` (active→expired).

The guard lives in the register declaration, not in a service if-tree
(ADR-031). `expire` is triggered by the same scheduled machinery that fires
the end-date notification consumer-side; if OR's lifecycle runtime cannot
schedule transitions at apply time, `expired` MAY be presented as a derived
display state (endDate in the past) without a stored transition — the spec
requires the *behaviour* (an overdue active contract presents as expired in
pipeline and detail), not the mechanism.

### D3 — Key-date reminders are declarative notifications

Two `x-openregister-notifications` entries on `contract`, mirroring the
shipped `objectionDeadline` dialect exactly (scheduled trigger with
`intervalSec: 86400`, `filter: {"status": "active"}`, `channels:
["nc-notification"]`, recipients `object-acl manage` + group
`filinq-contract-managers`, `nl`/`en` subjects):

- `noticeDeadline` — "Opzegtermijn nadert voor contract" / "Notice deadline
  approaching for a contract".
- `endDate` — "Contract loopt af" / "Contract is expiring".

No Filinq notifier, listener or cron dispatches contract reminders
(gate-18 stays green). The `noticeDeadline` default (`endDate −
noticePeriodDays`) is computed by `ContractService` at save when the field is
empty — a pure, unit-testable function; the user can always override the
persisted date.

### D4 — Documents and signing are linked by reference only

- **Generate**: the contract detail offers "generate document from template"
  → existing template preview/render path produces a `generatedDocument` /
  file; its reference is appended to `contract.documents`.
- **Attach**: existing NC file picker appends file references.
- **Sign**: "send for signature" deep-links into the existing signing
  request creation with the chosen contract document preselected; the
  created request's uuid is stored in `signingRequestRef`, and on completion
  the signed artifact reference is stored in `signedDocumentRef`. Filinq
  observes completion through the signing surface it already owns (the
  sibling `filinq-signing-events` change may make this event-driven; until
  then the detail view resolves current signing status by reading the
  referenced `signingRequest`).

No signing or template code path is modified; drift is pinned by a unit test
asserting the referenced schema properties (`signingRequest.status`,
`deadline`, `signatureLevel`) exist at HEAD — the register-drift-pin pattern
from `portal-contribution`.

### D5 — Key-term extraction is suggestion-only enrichment

`ContractTermSuggestionService` runs when a document is attached to a
contract (and on demand from the detail view), extracting candidate values
for `endDate`, `noticePeriodDays`, `value`/`currency`, `startDate` and party
names from the document text (existing local text-extraction path; no
external API — config.yaml local-only rule). Results are stored on
`contract.keyTermSuggestions` as records:

```json
{"field": "endDate", "value": "2027-12-31", "confidence": 0.82,
 "source": "generatedDocument:…", "status": "proposed"}
```

Rules (mirrors `inbound-auto-classification`'s suggestion-only philosophy and
the `metadata-enrichment` toggle pattern):

- A suggestion NEVER writes a contract field. Acceptance is per-field, by a
  user, via the accept action; rejection marks the record `rejected`.
  Accepted values are written with normal OR audit attribution.
- The whole pass is toggleable via IAppConfig
  `enable_contract_term_extraction` (string-boolean, default `"1"`, same
  shape as the existing enrichment toggles); toggling off skips extraction
  entirely and hides suggestion UI.
- Low-confidence output is still shown (ranked), never auto-discarded — the
  human is the filter.

Rejected: auto-applying above a confidence threshold (silent wrong deadlines
on legal instruments are worse than no automation); a generic
document-classification engine (sibling change's scope).

### D6 — Renewal pipeline view

Manifest-driven (`src/manifest.json`, ADR-036 / ADR-012 components):
`Contracts` index page (`CnIndexPage`/`CnDataTable`, status chips, facets on
`contractType`/`status`), `ContractDetail` (parties, dates, value, documents,
signing status, suggestions panel, lifecycle actions), and a
`ContractPipeline` view bucketing **active** contracts by urgency:

1. `expired` — endDate in the past (D2 display rule);
2. `notice due` — noticeDeadline within 30 days or past;
3. `expiring` — endDate within 90 days;
4. `later` — everything else.

Buckets are computed client-side from the listed objects' dates (pure
date arithmetic on already-authorised rows); renew/terminate actions call
the thin action routes. CRUD stays on OR's object API via `useObjectStore`
(ADR-022; hydra `redundant-controller` gate applies — only the renewal,
terminate and suggestion-accept actions get routes, each with explicit auth
attributes and per-object guards).

## OpenRegister service usage (ADR-001 / ADR-022)

| Operation | OR abstraction |
|---|---|
| Contract CRUD (UI) | OR object API via `useObjectStore` (RBAC + multitenancy server-side) |
| Renewal/terminate/accept actions | `ObjectService::saveObject()` — full object carried forward (PUT-semantic: never save partial objects; a non-changed field must survive) |
| Lifecycle guards | `x-openregister-lifecycle` (canonical dialect) |
| Key-date reminders | `x-openregister-notifications` scheduled dialect |
| Audit of acceptance/lifecycle | OR audit trail (no app-local audit rows) |

## Declarative-vs-imperative decision (ADR-031)

Declarative: lifecycle states/transitions, both key-date notifications,
schema validation, party shape. Imperative (justified): notice-deadline
defaulting (date arithmetic OR's dialect cannot express — pure function),
renewal action (creates + links two objects atomically from the user's
intent), suggestion extraction + acceptance (I/O over document text + an
audited write). Nothing imperative duplicates an OR read path.

## Seed Data

Shipped in `filinq_register.json` `objects[]` (placeholder identifiers
only, nil-UUID pattern):

```json
{
  "@self": {"register": "dossier", "schema": "contract", "slug": "demostad-groenonderhoud-2026"},
  "title": "Raamovereenkomst groenonderhoud 2026-2028",
  "contractType": "inkoop",
  "parties": [
    {"contactRef": "urn:nc:contact:00000000-0000-0000-0000-000000000010", "role": "opdrachtnemer", "displayName": "Groenbedrijf Demostad B.V."},
    {"role": "opdrachtgever", "displayName": "Gemeente Demostad"}
  ],
  "internalOwner": "seed-user-inkoop",
  "startDate": "2026-01-01",
  "endDate": "2028-12-31",
  "noticePeriodDays": 90,
  "noticeDeadline": "2028-10-02",
  "renewalType": "manual",
  "value": 240000,
  "currency": "EUR",
  "status": "active",
  "documents": [],
  "keyTermSuggestions": [
    {"field": "endDate", "value": "2028-12-31", "confidence": 0.91, "source": "seed", "status": "accepted"}
  ]
}
```

Plus one `draft` contract without dates (wizard/empty states) and one
`active` contract whose `noticeDeadline` lies within 30 days of the seed
epoch (pipeline "notice due" bucket + notification demo). All contact
references use nil-UUID URNs; no real party data.

## Security Considerations

- Contracts carry financial value and party PII references — reads/writes go
  through OR RBAC + multitenancy (no `_rbac`/`_multitenancy` overrides; the
  sibling `multi-tenant-hardening` guardrail test covers new services too).
- The three action routes carry explicit auth attributes and per-object
  guards (hydra gates: route-auth, no-admin-idor, semantic-auth); terminate
  requires a reason recorded on the object.
- Suggestion acceptance is an audited user action; extraction never writes
  fields (protects against poisoned documents steering contract terms
  silently).
- Extraction runs locally; no document content leaves the instance.
- Notification recipients are group/ACL-scoped — no notification to parties
  outside the organisation (external party notification would be a portal
  concern, out of scope).

## Risks / Trade-offs

- **OR lifecycle dialect drift**: shipped blocks use `initialState`; if the
  canonical dialect check is skipped the guard is silently ignored. Task 1.1
  pins verification; the drift-pin unit test asserts the dialect key OR
  actually honours.
- **Scheduled-notification lead time**: the shipped dialect has no
  explicit lead-time knob; if OR fires only when the date property is
  reached, "approaching" semantics depend on OR's scheduler contract.
  Verified at apply; fallback is documenting the fire window, not building a
  Filinq cron (gate-18).
- **Lean scope pressure**: CLM suites will always have more features; the
  defence is positioning (bundled with generation + signing municipalities
  already run) — feature creep here is a bug, not a risk.
- **`expired` as stored vs derived state** (D2): acceptable either way; the
  spec requires the behaviour only.

## Migration Plan

Purely additive: new schema + seed at a register version bump; no existing
schema or object is touched; feature invisible until the menu entry ships in
the same release. Rollback = ignore the schema.

## Open Questions

- Should contract key-term extraction later share an engine with
  `financialExtraction` (invoice fields) and `inbound-auto-classification`?
  Deferred to a unification ADR once all three are in production (per the
  long-term-unification working rule).
- Per-organisation contract-manager notification groups (instead of the
  instance-wide `filinq-contract-managers`) once `multi-tenant-hardening`'s
  `organisationSettings` exists — deferred, additive.

## Resolved at apply (2026-09-30, backend PR)

- **Register.** Filinq has one register, `filinq`; the `dossier` register D1 names no longer exists. The schema slug is `documentContract`, not `contract`: pipelinq, shillinq and stackiq already declare `contract`, and slugs are global on a shared OpenRegister (hydra gate-106), so a second `contract` could answer for theirs. `documentContract` 1.0.0 in `filinq`, register 8.39.0, icon `FileSign` (ADR-077 for the contract concept), `searchable: false` until its detail page exists. Demo contracts live in `filinq_mock_register.json` like every other schema's (three: notice due, long running, draft).
- **Lifecycle is enforced by OpenRegister, verified.** `LifecycleValidationListener` (OpenRegister development, 2026-09-30) refuses an update whose status change matches no declared transition, and reads `initial` (not `initialState`). `from` may be a list. The contract declares `initial: draft` and exactly activate, renew, terminate and expire.
- **Termination reason.** OpenRegister's transition `condition` dialect is still marked in progress in its own spec, so the reason is enforced by `ContractService::terminate()` (400 without one) and stored as `terminationReason`, a new property.
- **Notice deadline on every save path.** Instead of only in the service, `ContractNoticeDeadlineListener` fills it in on OpenRegister's pre-write events (`ObjectCreatingEvent`, `ObjectUpdatingEvent`, via `setModifiedData()`, which `MagicMapper` merges before writing), so a save through the object API, the UI or an import gets it too. A deadline somebody entered is never changed.
- **Authorization.** Read, create and update belong to the group `filinq-contract-managers`, delete to admins (docs/authorization-decisions.md). The action routes read the contract as the caller first: a contract they cannot read is 404, before anything is written.
- **Extraction trigger.** Extraction runs on demand (`POST api/contracts/{id}/suggestions`); the contract detail calls it after a document is attached. Running it inside OpenRegister's save would read files during another app's write. Text comes from `text/*` files and from PDFs through poppler's `pdftotext` when installed; other formats give no suggestions. Parties are proposed as `party` and accepting one adds a party with that name.
- **Routes.** `POST api/contracts/{id}/renew`, `POST api/contracts/{id}/terminate` (`reason`), `POST api/contracts/{id}/suggestions`, `PUT api/contracts/{id}/suggestions/{index}` (`decision`: `accepted` or `rejected`).

## Resolved at apply (2026-09-30, pages PR)

- **Pages.** `Contracts` is a manifest index page on `documentContract` whose rows open `ContractDetail` through `config.rowRoute` (the detail is a custom page, not `type: detail`, because it carries actions, dialogs and the signing and suggestion panels). `ContractPipeline` (`/contracts/pipeline`) is declared before `/contracts/:id`. Both are in the menu. `documentContract` 1.1.0 is `searchable: true` with a deep link to `/apps/filinq/contracts/{uuid}`, register 8.40.0.
- **Buckets.** `src/services/contractPipeline.js`: expired (active, end date passed), notice due (notice deadline within 30 days or passed), expiring (end date within 90 days), later; both windows inclusive. The same function gives the detail's shown status, so an active contract past its end date shows expired in both places.
- **Parties from contacts.** `GET api/contracts/{id}/parties` (`ContractParties`) looks up a `urn:nc:contact:<uid>` reference through `OCP\Contacts\IManager` (exact UID match, as the caller) and answers the contact's name and first e-mail address; without an address book, or when the contact is gone, the stored `displayName`.
- **Signing link.** The detail creates an ordinary signing request through the existing `POST api/signing/requests`, then records it with `POST api/contracts/{id}/signing` (`ContractSigningLink`). That route reads the request through `SigningService::getRequest()` as the caller (initiator or signer, else 404) and, once the request is `COMPLETED`, stores `signedDocumentRef` from the request's `signedDocumentRef`, or its `documentFileId` when the provider signed in place. The detail calls it on every open, so the link-back needs no listener; it writes only when something changed.
- **Generate.** `POST api/documents/generate` with the contract as `dataRefs` and `output.mode: files`; the answered `fileId` is added to `documents`. Attach uses the Files picker. Both then call the suggestions route when extraction is on.
- **Toggle in the UI.** `enable_contract_term_extraction` is in the admin settings (Contract terms) and in `GET api/settings`, which the detail reads to hide the suggestions panel.
- **Not done here.** The docs page has no screenshots: the Playwright MCP capture needs the seeded instance and is left for the docs pass. The e2e specs answer the object API and the routes with route handlers and have not been run against an instance in this PR; Playwright runs nightly.

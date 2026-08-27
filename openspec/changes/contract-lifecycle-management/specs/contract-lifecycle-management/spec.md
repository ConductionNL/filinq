# contract-lifecycle-management Specification (delta)

---
status: proposed
---

## Purpose

Contract lifecycle management as a thin domain layer over existing Filinq
capabilities: a `contract` OR object (parties from NC Contacts, key dates,
value, guarded status lifecycle), declarative key-date reminders, contract
documents generated/attached/signed through the existing template and signing
capabilities (referenced, never modified), suggestion-only key-term
extraction, and a renewal pipeline view. Serves the broader-DMS budget
competition (8 CLM competitors in the intelligence DB); tracked by GH #232.

## ADDED Requirements

### Requirement: Contract is a first-class OpenRegister object (REQ-DDCLM-001)

Filinq MUST store contracts as objects of a new `contract` schema in the
existing `dossier` register of `lib/Settings/filinq_register.json`
(additive register version bump). The schema MUST carry: `title` (string,
required), `contractType` (string), `parties` (array of objects, each with
`contactRef` — a string `format: uri` linkage pointer to a canonical NC
Contact record, the established `publicationConsent.contactRef` pattern —
plus `role` and a `displayName` fallback), `internalOwner` (NC user id),
`startDate`/`endDate` (date), `noticePeriodDays` (integer, nullable),
`noticeDeadline` (date, nullable), `renewalType` (`none` | `manual`),
`renews`/`renewedBy` (uuid references, nullable), `value` (number, nullable),
`currency` (string, default `EUR`), `status` (lifecycle field), `documents`
(array of artifact references), `signingRequestRef` and `signedDocumentRef`
(nullable references), `keyTermSuggestions` (array, see REQ-DDCLM-005) and
`notes`. When a linked contact exists, the contact record MUST be the source
of truth for party identity; the schema MUST NOT carry an organisation/tenant
property (tenancy comes from OR's envelope). When `endDate` and
`noticePeriodDays` are set and `noticeDeadline` is empty, saving MUST default
`noticeDeadline` to `endDate` minus `noticePeriodDays`; a user-supplied value
MUST never be overwritten.

#### Scenario: Contract created with parties from contacts

- GIVEN a user creates a contract titled "Raamovereenkomst groenonderhoud" with a party linked via `contactRef` and a party with only a `displayName`
- WHEN the contract is saved and reopened
- THEN both parties render (the linked one from its contact record) and all stored fields round-trip
- @e2e tests/e2e/spec-coverage/contracts.spec.ts

#### Scenario: Notice deadline defaults from end date and notice period

- GIVEN a contract with `endDate: 2028-12-31`, `noticePeriodDays: 90` and no `noticeDeadline`
- WHEN it is saved
- THEN `noticeDeadline` is stored as `2028-10-02`
- AND saving again with a manually set `noticeDeadline` keeps the manual value
- @e2e exclude pure date-arithmetic defaulting — covered by PHPUnit (tests/unit/Service/ContractServiceTest.php); the stored result is asserted in the contracts e2e spec

### Requirement: Contract status lifecycle is declaratively guarded (REQ-DDCLM-002)

The `contract` schema MUST declare an `x-openregister-lifecycle` state
machine in OpenRegister's canonical dialect (canonical `initial: draft` —
verified against OR HEAD at apply time, not copied from the drifted
`initialState` blocks shipped on older schemas): states `draft`, `active`,
`renewed` (terminal), `terminated` (terminal), `expired` (terminal);
transitions `activate` (draft→active), `renew` (active→renewed), `terminate`
(active→terminated, reason required and recorded on the object), `expire`
(active→expired). Invalid transitions MUST be rejected by the declared guard,
not by service if-trees. The `renew` action MUST create a successor contract
in `draft` carrying forward parties, type, owner, value and currency, and
MUST link the two objects via `renews`/`renewedBy`. An `active` contract
whose `endDate` has passed MUST present as expired in the pipeline and detail
views regardless of whether the stored transition has fired.

#### Scenario: Renewal creates a linked successor

- GIVEN an `active` contract
- WHEN the user triggers the renew action
- THEN the original becomes `renewed` and a new `draft` contract exists with parties/type/owner/value carried forward
- AND the successor's `renews` references the original and the original's `renewedBy` references the successor
- @e2e tests/e2e/workflows/contract-lifecycle.spec.ts

#### Scenario: Invalid transition is rejected declaratively

- GIVEN a contract in `draft`
- WHEN a save attempts to set `status` directly to `terminated`
- THEN the save is rejected by the lifecycle guard
- AND the stored status is still `draft`
- @e2e tests/e2e/workflows/contract-lifecycle.spec.ts

#### Scenario: Termination requires a reason

- GIVEN an `active` contract
- WHEN the user terminates it without supplying a reason
- THEN the action is refused
- AND terminating with a reason stores the reason on the object and sets `terminated`
- @e2e tests/e2e/workflows/contract-lifecycle.spec.ts

### Requirement: Key-date reminders are declarative notifications (REQ-DDCLM-003)

The `contract` schema MUST declare `x-openregister-notifications` entries for
`noticeDeadline` and `endDate` using the shipped scheduled dialect (as on
`publicationConsent.objectionDeadline`): scheduled trigger filtered to
`status: active`, channel `nc-notification`, recipients the
`filinq-contract-managers` group plus `object-acl` `manage`, with `nl` and
`en` subjects. Filinq MUST NOT ship any imperative notification dispatch,
listener, notifier or cron for contract reminders (ADR-031,
notification-dialect gate).

#### Scenario: Approaching notice deadline notifies the contract managers

- GIVEN an `active` contract whose `noticeDeadline` is within the notification window
- WHEN OpenRegister's scheduled notification pass runs
- THEN members of `filinq-contract-managers` receive a Nextcloud notification referencing the contract
- AND a `terminated` contract with the same dates produces no notification
- @e2e exclude scheduled OR-side dispatch cannot be deterministically triggered from the browser — covered by the register-declaration drift-pin PHPUnit test (tests/unit/Settings/ContractRegisterDeclarationTest.php) and gate-18 (notification-dialect) keeping the app free of imperative dispatch

### Requirement: Contract documents are generated, attached and signed via existing capabilities (REQ-DDCLM-004)

The contract detail MUST let a user (a) generate a contract document from a
template through the existing template rendering path, (b) attach existing
Nextcloud files, and (c) send a linked document for signature through the
existing signing capability — in each case storing only references on the
contract (`documents[]`, `signingRequestRef`, `signedDocumentRef`). The
signing and template capabilities MUST NOT be modified: signing runs the
normal `signingRequest` flow, and the contract surfaces the referenced
request's current status. A unit drift-pin MUST assert that every referenced
external schema property (`signingRequest.status`, `deadline`,
`signatureLevel`) exists in the shipped register at HEAD.

#### Scenario: Generate, attach and send for signature from the contract

- GIVEN an `active` contract
- WHEN the user generates a document from a seeded template, attaches an existing file, and sends the generated document for signature
- THEN `documents[]` references both artifacts, `signingRequestRef` references the created signing request
- AND the contract detail shows the signing request's live status
- @e2e tests/e2e/workflows/contract-lifecycle.spec.ts

#### Scenario: Signed artifact is linked back

- GIVEN a contract whose signing request completes
- WHEN the contract detail is reopened
- THEN `signedDocumentRef` references the signed artifact and the detail presents the contract document as signed
- @e2e exclude driving a full external signing completion is owned by the signing capability's own e2e suite — the linkage read is covered by PHPUnit (tests/unit/Service/ContractServiceTest.php) with a completed signingRequest fixture

### Requirement: Key-term extraction is suggestion-only (REQ-DDCLM-005)

When a document is attached to a contract (and on demand), Filinq MUST run
a local key-term extraction pass proposing values for `endDate`,
`startDate`, `noticePeriodDays`, `value`/`currency` and party names, stored
on `keyTermSuggestions` as records with `field`, `value`, `confidence`,
`source` and `status` (`proposed` | `accepted` | `rejected`). A suggestion
MUST NEVER modify a contract field: acceptance is a per-field user action
that writes the value with normal OR audit attribution; rejection only marks
the record. The pass MUST be toggleable via IAppConfig
`enable_contract_term_extraction` (string-boolean, default enabled, same
pattern as the metadata-enrichment toggles); when disabled, no extraction
runs and no suggestion UI is shown. Extraction MUST use only local text
processing (no external API), matching the suggestion-only philosophy of the
sibling `inbound-auto-classification` change.

#### Scenario: Suggestions propose, the human disposes

- GIVEN a contract with empty `endDate` and an attached document containing a clear end date and contract value
- WHEN the extraction pass completes
- THEN `keyTermSuggestions` contains `proposed` records for the found terms with confidence values
- AND the contract's `endDate` and `value` are still empty
- WHEN the user accepts the `endDate` suggestion and rejects the `value` suggestion
- THEN `endDate` is written (audited), `value` remains empty, and the records read `accepted` / `rejected`
- @e2e tests/e2e/spec-coverage/contracts.spec.ts

#### Scenario: Toggle disables extraction entirely

- GIVEN `enable_contract_term_extraction` is set to `"0"`
- WHEN a document is attached to a contract
- THEN no extraction runs, `keyTermSuggestions` is unchanged, and the detail shows no suggestions panel
- @e2e exclude IAppConfig toggle side-effect on a background pass — covered by PHPUnit (tests/unit/Service/ContractTermSuggestionServiceTest.php); the suggestions-panel visibility is asserted in the contracts e2e spec

### Requirement: Renewal pipeline view (REQ-DDCLM-006)

Filinq MUST ship manifest-driven Contracts pages: an index
(`CnIndexPage`/`CnDataTable` with status chips and facets), a contract detail
(parties, dates, value, documents, signing status, suggestions, lifecycle
actions), and a renewal pipeline view that buckets contracts by urgency:
**expired** (`active` with `endDate` past), **notice due** (`noticeDeadline`
within 30 days or past), **expiring** (`endDate` within 90 days), **later**
(remaining `active` contracts). Buckets MUST be computed from the dates of
objects the caller is authorised to read (no separate aggregate endpoint);
renew and terminate MUST be actionable from the pipeline. Contract CRUD MUST
go through OpenRegister's object API from the frontend; only the renew,
terminate and suggestion-accept actions get app routes, each with explicit
auth attributes and per-object authorization guards.

#### Scenario: Pipeline buckets the seeded contracts correctly

- GIVEN the seed contracts (one notice-due, one long-running active, one draft)
- WHEN the user opens the renewal pipeline view
- THEN the notice-due contract appears in "notice due", the long-running one in "later", and the draft in neither
- AND renewing the notice-due contract from the pipeline moves it out of the buckets
- @e2e tests/e2e/spec-coverage/contracts.spec.ts

#### Scenario: Action routes are guarded

- GIVEN an authenticated user without access to a given contract
- WHEN they call the renew or terminate route for that contract id
- THEN the request is rejected with 403 or 404 and the contract is unchanged
- @e2e exclude cross-user authorization probe — covered by PHPUnit controller guard tests (tests/unit/Controller/ContractControllerTest.php) and the hydra no-admin-idor gate

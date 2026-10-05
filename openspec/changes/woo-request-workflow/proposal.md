---
kind: code
---

# Proposal: woo-request-workflow

## Re-scope 2026-10-05 (decision D1): filinq keeps the decision letter and the inventory

This section governs. Where the original proposal below says otherwise, it is
superseded. None of the original sixteen tasks was built, so nothing built is
removed.

### Why the scope changed

Ruben's decision **D1** of 2026-10-05: dossiq owns the Woo request, its intake
and its statutory term. dossiq's Woo stack is built (`specs/woo-case-type`:
a Woo case type with P28D plus one P14D extension, per-document assessment
with grounds, a generated beschikking). dossiq gathers the documents itself
(`dossiq/woo-requests-gather-documents-from-sources`, REQ-WOO-012 to 014).
So filinq keeps only the service dossiq does not have: drafting the decision
letter and the inventory from an organisation-edited template.

Woo capability row 7.9, "A decision document is generated from a template".
Our column reads `no`: "zero template or document generation in the three
apps. Nearest is the filinq app, outside this stack". The gap register names
the missing half: "Generate the Woo decision letter (besluit) from a seeded
woo-besluit template filled from the request and its assessments." Build
plan: amend this change, wave 1, size L.

### What stays and what goes

| original | fate | where it lives now |
| --- | --- | --- |
| REQ-DDWRW-001 `wooRequest` and `requestDocument` schemas | removed | dossiq's Woo case and its documents |
| REQ-DDWRW-002 intake and the Woo art. 4.4 deadline | removed | dossiq, on OpenRegister's term engine |
| REQ-DDWRW-003 collection into a request dossier | removed | `dossiq/woo-requests-gather-documents-from-sources` |
| REQ-DDWRW-004 hash dedupe | removed | the same dossiq change (REQ-WOO-014) |
| REQ-DDWRW-005 per-document and per-passage grounds | removed | dossiq's assessment; grounds from `dossiq/woo-refusal-grounds-list` |
| REQ-DDWRW-006 inventarislijst from a template | kept, re-scoped | this change, rendered from data the caller passes |
| REQ-DDWRW-007 disclosure package and besluit letter | besluit kept, package removed | the letter here; the package in dossiq, which holds the documents |
| REQ-DDWRW-008 request lifecycle | removed | dossiq's case lifecycle |
| REQ-DDWRW-009 Woo-verzoeken UI | removed | dossiq's Woo case screens |
| task 4.6 collect entity-search hits into a request | removed | a dossiq concern; filinq's entity search keeps its own log |

### What this change builds now

1. Two seeded, organisation-editable templates in filinq's template library:
   `woo-besluit` (the decision letter) and `woo-inventarislijst` (the
   inventory). The seed never overwrites an organisation's edited version on
   upgrade.
2. A declared data contract, `woo-decision`, that both templates render from.
   The caller passes it as `data.wooDecision` on the existing
   `DocumentGenerationRequestedEvent` with `templateSlug`. No new command.
3. A context builder that checks the contract and fails closed: a withheld or
   partly disclosed document without a ground, a ground code the grounds list
   does not know, or a missing reference or decision date refuses the
   generation with the reason, and no document is made.
4. Grounds rendered by label and article from dossiq's list, through the
   resolver `grondslagen-read-from-dossiq` adds. Codes are never printed raw.
5. Inventory numbers taken from the caller in the caller's order, so the
   letter and the inventory always agree.

### Cross-app contract

The caller is dossiq (`lib/Service/Beschikking/FilinqTemplateEngineAdapter.php`
already renders beschikkingen through filinq). If
`dossiq-decisions-to-decidiq` moves the besluit raise to decidiq, decidiq
becomes the caller with the same contract. The call, unchanged from
`flow-generate-document-node`:

`new \OCA\Filinq\Event\DocumentGenerationRequestedEvent(request: [...], requestingApp: 'dossiq')`
with request keys `templateSlug` (`woo-besluit` or `woo-inventarislijst`),
`data` (`['wooDecision' => ...]`, shape in the spec), `object` (the case
reference), `format` (`pdf` default) and optional `userId`. After dispatch the
caller reads `isHandled()`, `getResult()` (`fileId`, `path`, `name`, `mime`,
`size`, `format`, `template`, `object`, `metadata`, `requestingApp`,
`warnings`) or `getError()`.

App absent: without filinq the event is dispatched and comes back neither
handled nor refused. dossiq then reports that no decision letter could be
drafted, and its Woo case cannot pretend one exists. That is dossiq's side and
needs its own test there. Without dossiq nothing calls this.

### Fail closed

- A refused contract makes no file. `getError()` names every problem.
- A ground the list does not know is refused, never printed as a bare code.
- A partly disclosed document is listed in the inventory with its grounds; it
  is never listed as disclosed.

### Dependencies and wave

- `filinq/grondslagen-read-from-dossiq` (wave 2) supplies the ground resolver.
  Until it lands, the builder reads labels from dossiq's
  `OCA\Dossiq\Woo\WooRefusalGrounds::byCode()` through the same guard, and
  the two changes share one resolver class.
- `dossiq/woo-refusal-grounds-list` (wave 1) defines the codes.
- Wave 1. Implements decision D1 for filinq.

## Original proposal (superseded where the re-scope above says so)

## Why

Passive disclosure — handling a Woo-verzoek end-to-end — is the competitive
category where municipalities spend the most painful money: ZyLAB ONE
(eDiscovery-grade Woo request handling with per-passage exemption-ground
annotation, dedupe and email threading, used by ministries), INDICA Woo
(cross-system search to find Woo-relevant content) and OCTOBOX (EIFFEL's
Woo-desk software+staffing at BZK, OCW, AZ, claiming 60% time saving vs
Adobe) all monetise exactly this workflow (research-competitors.md category
3/theme 8). De Connectie 391449 asks for an integraal Woo-proces; VNG
estimates >50% of documents partly auto-redactable but ALL needing review,
with ~€25k acquisition budgets for medium/large orgs.

Filinq already owns every processing primitive the workflow needs —
dossier register with Woo Art. 5 grondslagen (`base` schema, six canonical
uitzonderingsgronden seeded at HEAD), batch/folder anonymisation with entity
review and CSV audit reports, template-driven document generation
(`api/documents/generate`), correspondence/letter generation, PDF/A output —
but has **no request object**: no intake with statutory deadlines, no
candidate-document collection, no dedupe, no per-document exemption
assessment, no inventarislijst, no disclosure package, no lifecycle. An
operator today would juggle folders and spreadsheets around Filinq, which
is exactly what the ZyLAB category sells against.

## What Changes

- Two new schemas in the existing `dossier` register:
  - `wooRequest` — the Woo-verzoek case: subject/scope, receipt date,
    statutory decision deadline (Woo Art. 4.4: 4 weeks, one extension of at
    most 2 weeks with reason), lifecycle status, links to the collection
    dossier, decision letter, inventory document and disclosure package.
  - `requestDocument` — one row per candidate document in the request
    dossier: origin (Nextcloud folder, or case system via the
    zgw-document-bridge when present), content hash, dedupe verdict,
    per-document disclosure assessment (disclose / partially disclose /
    withhold) and exemption grounds.
- **Intake**: register a request with subject, scope and received date;
  deadline and extension tracked against the statutory terms with a
  deadline indicator.
- **Collection**: attach candidate documents from Nextcloud folders and — via
  the zgw-document-bridge when installed — from case systems, into a request
  dossier (reusing the existing `dossier` schema and folder binding).
- **Dedupe**: hash-based duplicate detection across the collected set
  (identical content collapses to one assessable document; near-identical
  email-thread dedupe is noted as future work).
- **Exemption-ground tagging**: per-document assessment plus per-passage/
  per-entity tagging using the Woo Art. 5.1/5.2 uitzonderingsgronden — reusing
  the existing `base` grondslagen register (verified at HEAD: six canonical
  grounds seeded; additional grounds added as seed data, schema unchanged).
- **Inventarislijst**: generate the inventory-list document (per-document
  number, title, date, assessment, grounds) from a seeded template through
  the existing document-generation capability.
- **Disclosure package**: assemble redacted PDFs + inventarislijst + besluit
  letter (via the existing correspondence generation) into a package folder.
- **Lifecycle**: registered → collecting → assessing → decision → disclosed
  → published → closed, with the publication step handing off to the
  woo-publicatie-pipeline where installed.

## Capabilities

### New Capabilities

- `woo-request-workflow`: passive-disclosure case workflow — Woo-verzoek
  intake with statutory deadline tracking, candidate-document collection
  (folders + zgw-document-bridge), hash-based dedupe, per-document and
  per-passage exemption-ground tagging on the existing grondslagen register,
  inventarislijst generation, disclosure-package assembly and request
  lifecycle.

### Modified Capabilities

<!-- none — dossier-register, batch-anonymization, document generation and
     correspondence capabilities are consumed unchanged; new grounds ship as
     additional seed objects of the existing base schema. -->

## Impact

- `lib/Settings/filinq_register.json`: `wooRequest` + `requestDocument`
  schemas in the `dossier` register; additional Woo Art. 5.1/5.2 `base` seed
  objects; a seeded `woo-inventarislijst` template; register version bump.
- New `lib/Service/WooRequestService.php` (intake, deadlines, collection,
  dedupe, assessment, package assembly) +
  `lib/Controller/WooRequestController.php` with `api/woo-requests/*` routes.
- `src/manifest.json` + new views: Woo-verzoeken index/detail with deadline
  indicators, collection and assessment surfaces.
- Consumes (unchanged): dossier register + folder batch anonymisation,
  `api/documents/generate` (inventory), correspondence generation (besluit),
  zgw-document-bridge staging objects (optional), woo-publicatie-pipeline
  handoff (optional).
- Evidence: research-competitors.md category 3 (ZyLAB/INDICA/Octobox), De
  Connectie 391449 integraal Woo-proces, VNG review mandate, Arnhem 407824
  (275 Woo dossiers/~55k docs/yr shows the volume this workflow must
  organise).

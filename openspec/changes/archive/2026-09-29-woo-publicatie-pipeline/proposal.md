---
kind: code
tracking_issue: https://github.com/ConductionNL/filinq/issues/238
---

# Proposal: woo-publicatie-pipeline

## Why

Active publication under the Wet open overheid is the single biggest volume
driver in the current market window: Dordrecht/Drechtsteden 407973 specifies a
Woo publicatietool in **224 requirements** — Woo-index discoverability, DiWoo
metadata, de-publication, destruction-date propagation; De Connectie 391449
asks for an active-publication scale-up where publication is reusable beyond
Woo; GH #238 tracks the Woo pipeline; 96% of municipalities already met the
first five Woo categories and more categories are coming (structural volume
growth), while GPP-Woo (16 gemeenten, EUPL) is building the rival open-source
publication stack.

Filinq already owns the two upstream steps — anonymisation (single, batch,
folder) and publication consent (WOO objection lifecycle, 28-day window,
prohibitions/standing consents) — and the sibling apps own the endpoint:
OpenCatalogi publishes OR `publication` objects with DiWoo/TOOI-validated
Woo-index sitemaps (WOO-001..010, WOO-TOOI-001..004) and a retention
lifecycle. What is missing is the **pipeline that chains them**: nothing today
answers "is this document ready to publish?", assembles the DiWoo metadata,
hands the redacted result off to the publication endpoint, withdraws it again,
or accounts for any of it. Operators would have to hand-copy files into
OpenCatalogi and hand-check consent deadlines — which is exactly the gap the
tenders score on.

## What Changes

- Two new schemas in the existing `document` register:
  - `publicationRecord` — one per document/dossier being published: readiness
    state (entities reviewed, consent clear, prohibitions clear), DiWoo
    metadata block (Woo informatiecategorie, documentsoort, publisher,
    dates), handoff bookkeeping (endpoint publication reference), publication
    lifecycle status, destruction date, de-publication reason.
  - `publicationLogEntry` — append-only accountability log of every pipeline
    action.
- **Publication readiness** evaluation: a document/dossier is `ready` only
  when all detected entities are reviewed, the consent gate is clear (consent
  given, or objection window elapsed without objection, per existing
  publication-consent rules) and no active publication prohibition matches.
- **DiWoo metadata assembly** on the publication record (TOOI-bound Woo
  category, documentsoort, publisher, creatiedatum) — OpenCatalogi validates
  TOOI binding at sitemap render; Filinq assembles and passes.
- **Handoff to OpenCatalogi** as the publication endpoint: create an OR
  `publication` object (register slug `publication`, schema `publication` —
  verified at OpenCatalogi HEAD) with the redacted derivative attached;
  Filinq does NOT build its own portal.
- **De-publication flow**: withdraw with a mandatory reason; propagated to the
  endpoint by setting `depublicatiedatum` on the publication object; logged.
- **Destruction-date propagation**: a destruction date from the source
  (Archiefwet/selectielijst) is recorded on the endpoint publication object
  via its retention fields (`retentionExpiresAt` + mandatory
  `retentionNote`, per OpenCatalogi RET-003).
- **Publication log** UI on the publication record detail; publish wizard
  chaining anonymize → consent → publish from document/dossier context.

## Capabilities

### New Capabilities

- `woo-publicatie-pipeline`: readiness-gated active-publication pipeline —
  publication records with readiness evaluation, DiWoo metadata assembly,
  handoff to OpenCatalogi, de-publication with reason, destruction-date
  propagation and an append-only publication log.

### Modified Capabilities

- `publication-consent`: adds a machine-readable **consent-clearance signal**
  per document (all consent records in a publication-permitting terminal
  state, objection window handling unchanged) that the pipeline's readiness
  gate consumes. No change to the objection-window rules or the app-owned
  consent boundary.

## Impact

- `lib/Settings/filinq_register.json`: `publicationRecord` +
  `publicationLogEntry` schemas in the `document` register, seed data,
  register version bump.
- New `lib/Service/PublicationPipelineService.php` (readiness evaluation,
  handoff, withdraw, destruction-date propagation, logging) +
  `lib/Controller/PublicationController.php` with `api/publications/*` routes.
- `ConsentService`/`ConsentCrudService`: new read-only clearance query (no
  behaviour change to existing consent CRUD).
- `src/manifest.json` + new views: Publications index/detail, publish wizard
  entry points on document/dossier surfaces.
- Cross-app runtime dependency (soft): OpenCatalogi installed for the
  endpoint; without it, handoff is disabled with an explanatory state.
  Addressing verified: OpenCatalogi register slug `publication`, schema
  `publication` with `publicatiedatum`, `depublicatiedatum`,
  `retentionTermMonths`, `retentionExpiresAt`, `retentionNote`, `status`.
- Evidence: Dordrecht 407973 (224 reqs), De Connectie 391449, GH #238,
  Tilburg ×2, GPP-Woo rivalry (ecosystem report).

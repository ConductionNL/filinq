---
kind: code
---

# Proposal: woo-request-workflow

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

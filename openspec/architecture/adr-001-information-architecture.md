# ADR-001: Information Architecture — Documenten / Sjablonen / Archief / Beheer

**Status:** accepted
**Date:** 2026-05-23

## Context

DocuDesk is the document-lifecycle workspace for organisations that must combine
everyday document work (templates, PDF rendering, letters, contracts) with
regulated archival and privacy obligations (TMLO/MDTO metadata, Archiefwet
retention, Woo-publicatie, eIDAS QES, redaction, anonymisation, legal hold).
Most document tools optimise for one pole — either "Office-style authoring" or
"eDiscovery / records management". DocuDesk treats both as one register-backed
surface: every document carries enrichment, retention, lineage, consent and
signature evidence alongside the file itself, so the case-worker and the
records officer / FG work on the same object.

The IA must reconcile two reader modes — the case-worker who edits and signs,
and the archivaris / FG who classifies, holds and discloses — without forcing
either of them into the other's screen. The current spec inventory (22 specs
across OCR, enrichment, anonymisation, redaction, signing, templates, PDF,
TMLO/MDTO, Archiefwet, Woo, CLM, legal hold, consent, register admin, dashboard,
Prometheus, …) trivially explodes into a 20+ item sidebar if every spec earns
its own nav entry. We need a discipline that holds the top-level count under
the 7-item ceiling and stays stable as new spec families land.

The cross-app IA brief (`docudesk / decidesk / opencatalogi / openconnector`,
2026-05-23) proposes a four-section structure for DocuDesk. This ADR captures
that proposal as the per-app IA contract.

## Decision

DocuDesk uses **four top-level menu items**, in this order:

1. **Documenten** — the working surface (single + batch, OCR, enrichment,
   redaction, signatures, holds; everything that is a "verb on a document")
2. **Sjablonen** — template authoring, letter / correspondence generation, print
   preview, PDF output
3. **Archief** — TMLO/MDTO metadata, Archiefwet retentie, Woo-publicatie,
   contract lifecycle, legal hold / e-discovery (everything driven by a
   retention or disclosure regime rather than day-to-day editing)
4. **Beheer** — admin settings, register definitions, anonimisatie-review queue,
   consent management, Prometheus, dashboard

### Sub-structure per menu

| Menu | Pages | Key detail tabs |
|---|---|---|
| Documenten | `Documenten` (list), `Document detail` | Inhoud · Metadata · OCR-tekst · Entiteiten (PII) · Anonimisatie · Redactie · Handtekeningen · Versies · Audit |
| Sjablonen | `Sjablonen`, `Sjabloon detail/editor`, `Brieven & correspondentie`, `Print preview` | Twig-bron · Voorbeelddata · Stijl/branding · PDF-output · Toetsing |
| Archief | `Retentie`, `TMLO/MDTO metadata`, `Woo-publicatie`, `Contracten`, `Legal hold & e-discovery` | Metadata · Retentie-event-log · Hold-status · Publicatie-status · Exports |
| Beheer | `Dashboard`, `Documentenregister`, `Anonimisatie-review`, `Toestemming`, `Admin-instellingen`, `Prometheus` | Verbindingen (OR/OC) · AI-providers · eIDAS QTSP · Selectielijsten · Woo-doelsystemen · Sleutels & rotatie |

### Spec-to-placement mapping (canonical, 22 specs)

| spec_slug | placement |
|---|---|
| p3-document-management | Documenten (top) — anchors left-nav primary list |
| ocr-document-scanning | Documenten > Document detail > Inhoud / OCR-tekst tab |
| metadata-enrichment | Documenten > Document detail > Metadata tab |
| anonymization | Documenten > Document detail > Anonimisatie tab + Batch panel |
| batch-anonymization | Documenten > Bulk action "Batch anonimiseren" |
| anonymization-entity-review | Beheer > Anonimisatie-review queue |
| redaction-at-scale | Documenten > Bulk action + detail Redactie tab |
| eidas-qes-signature | Documenten > Document detail > Handtekeningen tab (QTSP config under Beheer) |
| consent-management | Beheer > Toestemming |
| document-register | Beheer > Documentenregister |
| template-management | Sjablonen > Sjablonen lijst + editor |
| letter-correspondence-generation | Sjablonen > Brieven & correspondentie |
| pdf-generation | Sjablonen > editor > PDF-output tab + Documenten render action |
| print-preview | Sjablonen > Print preview |
| tmlo-mdto-metadata | Archief > TMLO/MDTO metadata |
| archiefwet-retention-engine | Archief > Retentie |
| woo-publicatie-pipeline | Archief > Woo-publicatie |
| contract-lifecycle-management | Archief > Contracten |
| e-discovery-legal-hold | Archief > Legal hold & e-discovery |
| dashboard | Beheer > Dashboard |
| admin-settings | Beheer > Admin-instellingen |
| prometheus-metrics | Beheer > Prometheus |

## Design rules

The four rules below are the durable IA contract. New specs and refactors MUST
be evaluated against them before nav placement is decided.

### Rule 1 — Documents are the noun, everything else is a verb on a document

OCR, PII-detectie, redactie, ondertekening en metadata-verrijking zijn allemaal
tabs of acties op een document — **nooit zelfstandige top-level menu's**.

**Rationale.** Every document-lifecycle product that has shipped a top-level
"OCR" or "Signatures" menu has watched its sidebar grow with each new analysis
pipeline. By forcing every per-document capability into the *Document detail*
view (as a tab or a bulk action), the sidebar stays at four items even if we
ship ten more analysis specs.

**How to apply.** When a new spec describes a per-document analysis or
operation, add it as (a) a detail tab on `Document detail`, (b) a bulk action
on the `Documenten` list, or (c) a slide-in panel launched from the document
context. Reject any proposal to make it a top-level menu item.

### Rule 2 — Day-to-day vs regulated split is by menu, not by tab

Alles wat gestuurd wordt door Archiefwet / Woo / CLM / legal hold leeft in
*Archief*; alles wat gestuurd wordt door dagelijks auteurswerk leeft in
*Documenten* of *Sjablonen*. Het mentale model van de gebruiker is "ben ik aan
het **bewerken** of aan het **bewaren**?".

**Rationale.** A case-worker and an archivaris / FG never share a screen
comfortably. Putting retention timers, Woo-classificatie en hold-status as
tabs next to "Inhoud" forces the case-worker to scroll past compliance noise
and the FG to dig past authoring noise. Splitting by **menu** lets each
persona land in a surface that respects their task.

**How to apply.** Ask "is this driven by a retention / disclosure / hold
regime?" If yes → Archief. If no → Documenten or Sjablonen. The same
underlying register row may surface in both places (a contract is a document
and a CLM record); the *menu entry point* differs because the *task* differs.

### Rule 3 — Batches are a panel, not a page

Batch-upload, batch-anonimisatie en batch-redactie worden gestart vanuit
bulk-actions op `Documenten` en presenteren zich als slide-in panel — ze
breken de gebruiker **nooit uit de document-context**.

**Rationale.** Batch work is a multiplier on single-document work, not a
parallel surface. A separate `/batches` page forces the operator to context-
switch between "I'm processing N documents" and "I'm reviewing document X",
which kills throughput when reviewing low-confidence entity decisions mid-
batch. A panel keeps the document list (and the per-document review surface)
on screen.

**How to apply.** Bulk operations expose as actions in the `Documenten`
toolbar; status / per-file detail / review-and-approve live in a slide-in
panel anchored to the list. The only batch-related top-level entry is the
operator-side `Anonimisatie-review` queue under Beheer (Rule 4).

### Rule 4 — Beheer holds queues, registers, providers, dashboards

Als iets uitsluitend voor de operator is — entity-review-queue, register-
schema's, provider-sleutels, Prometheus — hoort het onder *Beheer*. Admin
**lekt nooit** de werkende oppervlakte in.

**Corollary.** A case-worker should never have reason to open *Beheer*; an
archivaris / FG should never have reason to leave *Archief* + *Beheer*. If a
spec violates this corollary the placement is wrong — re-evaluate against
Rule 2.

**How to apply.** Operator-only surfaces (review queues, schema admin, AI /
QTSP / Woo provider config, ops endpoints, dashboards) live under Beheer. End-
user surfaces (authoring, signing, archiving a specific document) live in
Documenten / Sjablonen / Archief. The Beheer dashboard is the only place that
aggregates KPIs across the other three sections.

## Consequences

- The DocuDesk left-nav holds at four items even as Phase 4 (archive +
  compliance) and Phase 5 (trust + signing) ship. Rules 1 + 4 cap top-level
  growth.
- `p3-document-management` is the anchor of *Documenten* in DocuDesk even
  though the spec context-brief labels it Decidesk — the slug + register +
  install target are DocuDesk's; Decidesk consumes the same register through
  its meeting "Stukken" tab (see decidesk IA brief, §G).
- Anonimisation surfaces in three places by design: single-doc tab
  (`Documenten > detail > Anonimisatie`), batch panel (`Documenten > bulk
  action`), and operator review queue (`Beheer > Anonimisatie-review`). This
  is not duplication — it is the three personas (auteur, batch-operator,
  reviewer) each getting their own entry point on shared data (Rule 2 + Rule
  3 + Rule 4).
- Contracten and Woo-publicatie live under *Archief* even though they involve
  authoring (drafting a contract, preparing a publication). Their lifecycle is
  retention / disclosure driven, so Rule 2 sends them to Archief.
- Templates do NOT live under Documenten. Sjablonen is its own top-level
  because the authoring surface (Twig editor, voorbeelddata, PDF-output) is
  different enough from per-document work to warrant separation, and because
  *correspondence generation* hands off to OpenConnector's mail / post adapter
  from there.
- Phase ordering (Foundation → Sjablonen → Privacy pipeline → Archive →
  Trust & signing) follows from the rules: each phase activates one menu
  section end-to-end rather than scattering capability across all four.

## References

- Cross-app IA brief, 2026-05-23: `docudesk / decidesk / opencatalogi /
  openconnector` — §1 "docudesk" (purpose, top-level navigation, sub-
  architecture, mapping table, phases, design rules).
- Sibling-app IA ADRs (when filed): decidesk, opencatalogi, openconnector.
- Hydra ADR-022 (apps consume OR abstractions) — Documenten lists / detail
  pages MUST consume OR `createObjectStore` rather than rolling per-resource
  stores; ADR-001 governs *where* the surface lives, not *how* it talks to
  OR.

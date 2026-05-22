---
status: draft
---

# WOO publicatie pipeline

## Purpose

Implement an end-to-end Wet Open Overheid (WOO) publication pipeline inside docudesk. The WOO came into force on 1 May 2022 and replaced the Wob; it actively obliges every Dutch government organisation to publish 17 information categories proactively (covenanten, jaarplannen, ontwerpwetten, bestuurlijke besluiten, raadsstukken, vergaderverslagen, onderzoeken, beschikkingen, etc.) on the national PLOOI platform (operated by KOOP, Kennis- en Exploitatiecentrum Officiële Overheidspublicaties). Each publication must be machine-readable, must carry DIWOO metadata, must be anonymised or otherwise lawfully redacted, and must be findable through the national zoekindex at open.overheid.nl. Non-publication is a finding by the supervising Inspectie Openbaarheid van Bestuur (operational since 2026).

Today most organisations handle WOO publication semi-manually: a coordinator downloads a document from a DMS, ticks a category in a spreadsheet, runs Acrobat redaction by hand, uploads to a SharePoint sync that feeds PLOOI on a delay, and hopes the upload succeeded. The error surface is large: wrong category, missed BSN in a footer, weeks-late publication, PLOOI rejection silently dropped, no bezwaar (objection) tracking when a publication is challenged. The pipeline in this spec automates and standardises every step.

For each document the engine determines (or asks the user to confirm) the WOO information category, runs an anonymisation/redaction check against a configured rule set (BSN, BIN, persoonsgegevens, bedrijfsgevoelig, staatsgeheim/dep-vertrouwelijk, herleidbaarheid), applies redaction (visible black boxes plus metadata-strip), produces both a publication-ready PDF/A-2 and the DIWOO-XML metadata, submits to PLOOI/KOOP via the prescribed intake API, tracks the per-publication status (queued / submitted / accepted / live / rejected / withdrawn), and runs a bezwaar workflow if publication is challenged within the legal terms. The engine reuses opencatalogi/woo-compliance for the DIWOO sitemap/robots output (the publication-ready side) and reuses docudesk/anonymization for the redaction step.

The result: a records officer can mark a document "publish-WOO", and within minutes the document is live on PLOOI with full audit trail, redaction proof, and a back-reference linking the published version to the original internal version.

## Data Model

Five core schemas:

- **WooPublication** — id, documentId, documentVersion, wooCategory (one of 17 enum), title, publicationDate (scheduled), publishedAt (actual), publicationStatus (draft | queued | submitted | accepted | live | rejected | withdrawn | bezwaar-pending), publisherOrganisation, publicationOfficer, koopReference (assigned by PLOOI on acceptance), publishedUrl (open.overheid.nl URL), retentionLinkedTo (RetentionPolicy id for cross-app retention coordination), exemptionsApplied[] (links to WooExemption), summary, languageTag.
- **WooCategory** — id, code (`1` through `17` per WOO art. 3.3), wettelijkeGrondslag (legal reference), titleNl, descriptionNl, publishWithinDays (legal deadline post-event), publicationFrequency (continuous | quarterly | annual), checklistItems[] (per-category required-metadata items), koopMetadataMapping (which DIWOO fields are mandatory).
- **WooAnonymisationCheck** — id, publicationId, runAt, runBy, ruleSetVersion, findings[] (per-finding: ruleId, locationRef, snippet, severity, action), reviewedBy, reviewedAt, approvedRedactionPdfRef, hashBefore, hashAfter.
- **WooExemption** (uitzonderingsgrond) — id, publicationId, exemptionArticle (WOO art. 5.1 / 5.2 type), exemptionScope (full | partial-page | partial-paragraph), justification, weighingTest (belangenafweging text), decisionBy, decisionDate, expiresAt (if temporary).
- **WooBezwaar** — id, publicationId, bezwaarmaker (party who objected), bezwaarType (publication-should-not-have-happened | wrong-redaction | wrong-category | personal-data-exposure), submittedAt, deadlineAt (legal deadline for decision), assignedTo, status (received | in-review | gegrond | ongegrond | ingetrokken | beroep-pending), decisionAt, decisionDocument, beroepCaseRef (if escalated to court).

## Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| WPP-001 | Auto-suggest WOO category per document using metadata + content classifier | Must |
| WPP-002 | Confirm-or-override WOO category workflow with mandatory justification on override | Must |
| WPP-003 | All 17 WOO information categories implemented as first-class data with legal references | Must |
| WPP-004 | Per-document anonymisation check: BSN, BIN, RSIN, IBAN, e-mail, phone, address, persoonsgegevens, bedrijfsgevoelig, beveiligingsrubricering | Must |
| WPP-005 | Anonymisation check produces a structured findings list with page+coordinate refs | Must |
| WPP-006 | Redaction applies BOTH visible black boxes AND text-layer scrubbing (not just visual cover) | Must |
| WPP-007 | Redaction strips PDF metadata + embedded thumbnails + form fields + comments | Must |
| WPP-008 | Hash-before + hash-after captured for tamper evidence | Must |
| WPP-009 | Reviewer approval required before submission | Must |
| WPP-010 | Output PDF/A-2 compliant + DIWOO XML metadata | Must |
| WPP-011 | Submission to PLOOI/KOOP via the official intake API (Aanleverkanaal) | Must |
| WPP-012 | Submission retries with exponential backoff and final-failure escalation | Must |
| WPP-013 | Per-publication status tracking with full state-transition audit log | Must |
| WPP-014 | KOOP rejection reasons parsed and surfaced in UI | Must |
| WPP-015 | Bezwaar workflow with legal deadline tracking (default 6 weeks per Awb) | Must |
| WPP-016 | Bezwaar decision can trigger withdrawal, partial-redaction-update, or re-classification | Must |
| WPP-017 | Withdrawal pushes a tombstone to PLOOI per their withdrawal API | Must |
| WPP-018 | Per-publication permalink (open.overheid.nl) stored back on the document | Must |
| WPP-019 | Publication deadline alerting (overdue WOO obligation per category) | Should |
| WPP-020 | Bulk-publication wizard for backlog (e.g. raadsstukken last 2 years) | Should |
| WPP-021 | Anonymisation rule sets per organisation type (gemeenten / Rijk / waterschappen) | Should |
| WPP-022 | Exemption (uitzonderingsgrond) workflow with belangenafweging text per WOO art. 5.1/5.2 | Must |
| WPP-023 | Partial publication: a document can be published with specific pages exempted | Must |
| WPP-024 | DIWOO category-specific metadata enforcement (e.g. raadsstukken require vergaderdatum + agendapunt) | Must |
| WPP-025 | Republication on update with version chain visible to citizens | Should |
| WPP-026 | Integration with opencatalogi/woo-compliance so the org's own DIWOO sitemap reflects the published items | Must |
| WPP-027 | AVG-vs-WOO weighing assist (recht-op-vergetelheid claims flag affected publications) | Should |
| WPP-028 | Annual WOO report export (number of publications per category, average latency, bezwaar count) | Should |

## Standards & Sources

- **Wet Open Overheid (WOO)** — Stb. 2021/499 + Stb. 2022/14, in werking 1 mei 2022. Article 3.3 enumerates the 17 actively publishable information categories; articles 5.1 + 5.2 define exemption grounds.
- **Algemene wet bestuursrecht (Awb)** — governs the bezwaar + beroep procedure including the standard 6-week bezwaar termijn.
- **DIWOO metadata standard** — KOOP's metadata profile for WOO publications; XML + JSON-LD serialisations. Forum Standaardisatie status: in process.
- **PLOOI / open.overheid.nl** — KOOP's national platform. Aanleverkanaal API documentation at koop.overheid.nl. Replaces the prior individual ministerial publication sites for the WOO domain.
- **TOOI vocabularies** — controlled vocabularies for publisher organisations, themes, locations (used in DIWOO).
- **PDF/A-2 (ISO 19005-2)** — long-term-preservation PDF profile required for publication.
- **AVG (GDPR Regulation 2016/679)** — data-protection ground that interplays with WOO; anonymisation rules sit on top.
- **Wet Bescherming Bedrijfsgeheimen** — basis for bedrijfsgevoelig redaction.
- **Wbni (Wet beveiliging netwerk- en informatiesystemen)** + **WIV / Wabia** — relevant for staatsgeheim / departementaal-vertrouwelijk classifications that block publication outright.
- **NEN-ISO 19005** — PDF/A spec family.
- **Gedragslijn actieve openbaarmaking** (Rijksoverheid) — Rijk-specific implementation guidance.
- **BIO (Baseline Informatiebeveiliging Overheid)** — the controls baseline that constrains how the pipeline handles to-be-published documents.
- **Voorlichtingsplicht / Wob-jurisprudentie** — pre-WOO case law that remains relevant for exemption belangenafweging.
- **Inspectie Openbaarheid van Bestuur (IOBJ)** — operational supervisor; consumes annual reports.
- **Forum Standaardisatie open-standaarden lijst** — DIWOO and underlying NL-DCAT enrolment.

## Cross-app integration

- **docudesk base** — every document gets a "publish-WOO" action; the pipeline runs against the configured document register.
- **docudesk/anonymization** — re-used for detection + redaction; the WOO pipeline adds the WOO-specific rule set and the irreversible "publication-grade" redaction strength.
- **docudesk/anonymization-entity-review** — re-used for the reviewer approval gate.
- **docudesk/archiefwet-retention-engine** — published documents get an extended retention link (publication-bewaarplicht) and cannot be destroyed without first being formally de-published.
- **docudesk/template-management** — bezwaar-decision letter and publication-confirmation letter are template-driven.
- **OpenRegister** — all five schemas register with full audit log. The audit log is the source of truth for the IOBJ annual report.
- **OpenConnector** — handles the KOOP/PLOOI Aanleverkanaal submission (mTLS, retries, large-file upload, async-status polling).
- **opencatalogi base** — published WooPublication objects appear in the organisation's local catalog so internal users can browse the same record citizens see.
- **opencatalogi/woo-compliance** — already implements the DIWOO sitemap/robots layer; this spec produces the publication records that the existing sitemap consumes, closing the loop end-to-end.
- **opencatalogi/dcat-oai-pmh-harvesting** — DIWOO + DCAT outputs become harvestable by data.overheid.nl alongside PLOOI submission.
- **zaakafhandelapp / openzaak** — most publishable documents originate in zaken; the pipeline reads zaaktype + publication-flag from the zaak record.
- **nldesign** — bezwaar-letter + redaction-review UI use rijkshuisstijl components.
- **mydash** — WOO KPIs (publications-per-month, latency-per-category, bezwaar-success-rate, overdue-publication-count) surface for the WOO-coordinator.

## Target users

**Primary:**
- **WOO-coordinatoren** at municipalities, provinces, ministries, waterschappen, ZBOs. Newly created role since the WOO came in force; many organisations are still building out the function.
- **Document-anonimiseerders / DIV-medewerkers** running redaction.
- **Juridische zaken** running the exemption (uitzonderingsgrond) belangenafweging and the bezwaar workflow.
- **Bestuursadviseurs / griffies** publishing raadsstukken and bestuurlijke besluiten under the strictest deadlines.

**Secondary:**
- **Inspectie Openbaarheid van Bestuur (IOBJ)** — supervisor consuming annual reports and reviewing the audit trail when sampling.
- **KOOP / PLOOI operations** — receives the submissions; the pipeline's compliance with their intake spec determines acceptance vs rejection.
- **DPO (Functionaris Gegevensbescherming)** — works with WOO coordinator on AVG-vs-WOO weighing.
- **Wob/WOO-verzoekers** (journalists, researchers, civic-tech) — indirect beneficiaries; their requests can be satisfied by pointing to existing actieve openbaarmaking.
- **Bestuurders** — sign the exemption decisions for sensitive cases.

Tertiary: every Dutch citizen as the ultimate audience of open.overheid.nl. The bezwaar workflow also serves third parties whose data appears in a publication (right-to-object).

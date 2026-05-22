# WOO Publicatie Pipeline

## Summary

This change implements an end-to-end Wet Open Overheid (WOO) publication pipeline within docudesk, automating the complete workflow from document intake through PLOOI submission, publication tracking, and bezwaar (objection) handling. The pipeline ensures every published document is machine-readable, carries complete DIWOO metadata, is properly anonymised and redacted, and is submitted to the national PLOOI platform on schedule with full audit trail.

## Motivation

The WOO (effective 1 May 2022) requires every Dutch government organisation to actively publish 17 information categories on the national open.overheid.nl platform. Current processes are semi-manual and error-prone: documents are downloaded, categories chosen in spreadsheets, redaction done by hand in Acrobat, uploaded to SharePoint, and hoped to reach PLOOI correctly. The error surface is large: wrong category assignment, missed personally identifiable data, weeks-late publication, silent PLOOI rejection, and no tracking when citizens object to a publication.

The WOO pipeline standardises and automates every step, reducing manual error and bringing compliance within reach of organisations still building out their WOO-coordination function.

## Scope

- **WooPublication schema** — tracks publication status from draft through live/rejected/withdrawn, with links to document, exemptions, and PLOOI reference
- **WooCategory schema** — all 17 WOO information categories as first-class data with legal references, metadata requirements, and deadline rules
- **WooAnonymisationCheck schema** — structured findings from anonymisation scanning (BSN, BIN, RSIN, IBAN, PII, bedrijfsgevoelig, beveiligingsrubricering) with before/after hashing
- **WooExemption schema** — exemption decisions (uitzonderingsgrond) per WOO art. 5.1/5.2 with belangenafweging text and partial publication support
- **WooBezwaar schema** — bezwaar/objection workflow with legal deadline tracking (default 6 weeks per Awb) and decision routing
- **Document-level "publish-WOO" action** that initiates the pipeline
- **Integration with docudesk/anonymization** for detection and redaction
- **Submission to PLOOI via OpenConnector** with retry logic and status polling
- **DIWOO XML + PDF/A-2 output** ready for publication
- **Withdrawal support** with tombstone submission to PLOOI
- **Publication deadline alerting** for overdue WOO obligations
- **Bulk publication wizard** for backlog processing
- **Integration with opencatalogi/woo-compliance** so published items appear in the organisation's local DIWOO sitemap
- **Bezwaar workflow** with decision templates and possible re-publication or withdrawal

## Relation to Existing Specs

- **docudesk/anonymization** — reused for the detection phase; this spec adds WOO-specific rule sets and publication-grade redaction strength
- **docudesk/anonymization-entity-review** — reused for the reviewer approval gate before PLOOI submission
- **docudesk/archiefwet-retention-engine** — published documents gain extended retention; cannot be destroyed without formal de-publication
- **docudesk/template-management** — bezwaar decision and publication-confirmation letters are template-driven
- **OpenRegister** — all five WOO schemas register for full audit trail
- **OpenConnector** — handles mTLS, retries, and async polling for the KOOP Aanleverkanaal (PLOOI intake API)
- **opencatalogi/woo-compliance** — consumes WooPublication records for the public DIWOO sitemap and robots output
- **opencatalogi/dcat-oai-pmh-harvesting** — harvests DIWOO + DCAT for data.overheid.nl
- **zaakafhandelapp / openzaak** — most publishable documents originate in zaken; the pipeline reads zaaktype and publication flags from zaak records

## Requirements

| ID | Requirement | Priority |
|----|----|----------|
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

## Target Users

- **WOO-coordinatoren** at municipalities, provinces, ministries, waterschappen, ZBOs
- **Document-anonimiseerders / DIV-medewerkers** running redaction reviews
- **Juridische zaken / Legal** running exemption workflow and bezwaar decisions
- **Bestuursadviseurs / griffies** publishing council documents under strict deadlines
- **Inspectie Openbaarheid van Bestuur (IOBJ)** — supervisor consuming annual reports
- **Citizens and researchers** accessing publications via open.overheid.nl

## Standards & Sources

- **Wet Open Overheid (WOO)** — Stb. 2021/499 + Stb. 2022/14, in force 1 May 2022
- **DIWOO metadata standard** — KOOP's metadata profile for WOO publications (XML + JSON-LD)
- **PLOOI / open.overheid.nl** — KOOP's national platform; Aanleverkanaal API at koop.overheid.nl
- **Algemene wet bestuursrecht (Awb)** — governs bezwaar (default 6-week termijn)
- **PDF/A-2 (ISO 19005-2)** — long-term-preservation PDF profile
- **AVG (GDPR Regulation 2016/679)** — data-protection ground for anonymisation rules

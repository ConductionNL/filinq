# DocuDesk — Overheidsfunctionaliteiten

> Functiepagina voor Nederlandse overheidsorganisaties.
> Gebruik deze checklist om te toetsen aan uw Programma van Eisen.

**Product:** DocuDesk
**Categorie:** Documentbeheer, publicatie-instemming & GDPR-compliance
**Licentie:** EUPL-1.2 (vrije open source)
**Leverancier:** Conduction B.V.
**Platform:** Nextcloud + Open Register (self-hosted / on-premise / cloud)

## Legenda

| Status | Betekenis |
|--------|-----------|
| Beschikbaar | Functionaliteit is beschikbaar in de huidige versie |
| Gepland | Functionaliteit staat op de roadmap |
| Via platform | Functionaliteit wordt geleverd door Nextcloud / OpenRegister |
| Op aanvraag | Beschikbaar als maatwerk |
| N.v.t. | Niet van toepassing voor dit product |

---

## 1. Functionele eisen

### Documentbeheer & Verwerking

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| F-01 | Documentgeneratie (sjablonen, PDF/DOCX) | Beschikbaar | Sjablonen met merge-velden |
| F-02 | Documentvalidatie (formaat, metadata) | Beschikbaar | Configureerbare kwaliteitscontroles (formaat, integriteit, encryptie, tekstlaag, metadata-volledigheid) met verdict + bevindingen ([document-validation](features/document-validation.md)) |
| F-03 | Documentclassificatie (auto-categorisering) | Beschikbaar | Automatische onderwerpsdetectie |
| F-04 | Taalherkenning | Beschikbaar | Automatische taaldetectie op documenten |
| F-05 | Trefwoord-extractie | Beschikbaar | Automatische keyword-extractie |
| F-06 | Tekstextractie (OCR/NLP) | Beschikbaar | Tekst uit PDF, afbeeldingen, etc. |
| F-07 | Documentvergelijking | Beschikbaar | Server-side structuurvergelijking van twee documenten of versies, met redactie-annotatie voor origineel-vs-geanonimiseerd ([document-comparison](features/document-comparison.md)) |
| F-08 | Batchverwerking | Beschikbaar | Bulk-documentoperaties |
| F-09 | Rapportage-interface | Beschikbaar | Documentoverzichten en statistieken |

### Publicatie & WOO-compliance

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| F-10 | Publicatie-instemmingsproces (Wet Open Overheid) | Beschikbaar | Configureerbare bezwaartermijnen |
| F-11 | Minimale bezwaartermijn van 4 weken (WOO) | Beschikbaar | Wettelijk minimum ingebouwd |
| F-12 | GDPR-anonimisering (PII-redactie) | Beschikbaar | Persoonsgegevens verwijderen uit documenten |
| F-12a | Verwerkingsregister (AVG Art. 30) | In ontwikkeling | DocuDesk levert vier verwerkingsactiviteiten als catalogus; per-toegang logging via OpenRegister beschikbaar; geaggregeerde Art. 30-export volgt met OpenRegister |
| F-13 | Digitale ondertekening | Beschikbaar | Ondertekenworkflow + auditspoor beschikbaar; SES-handtekening-embedding via de native provider (verifieerbaar `/DocuDesk-Signature`-merk + HMAC, opgeslagen als nieuwe Nextcloud-bestandsversie) — [#304](https://github.com/ConductionNL/docudesk/issues/304). AdES/QES via externe provider (ValidSign) in ontwikkeling |
| F-14 | PKIoverheid-ondersteuning | Gepland | Overheidscertificaten voor ondertekening |

### Integratie & Automatisering

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| F-15 | Event-driven integratie met OpenRegister | Beschikbaar | Documenten automatisch verrijken bij creatie |
| F-16 | Workflow-automatisering | Beschikbaar | Geautomatiseerde documentverwerking |
| F-17 | Metadata-verrijking | Beschikbaar | Automatisch metadata toevoegen aan documenten |

---

## 2. Technische eisen

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| T-01 | On-premise / self-hosted installatie | Beschikbaar | Nextcloud-app |
| T-02 | Open source (broncode beschikbaar) | Beschikbaar | EUPL-1.2, Codeberg |
| T-03 | RESTful API | Via platform | OpenRegister REST API |
| T-04 | Event-driven architectuur | Beschikbaar | Luistert op OpenRegister events |
| T-05 | Database-onafhankelijkheid | Via platform | PostgreSQL, MySQL, SQLite |
| T-06 | Containerisatie (Docker) | Beschikbaar | Docker Compose |

---

## 3. Beveiligingseisen

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| B-01 | RBAC | Via platform | OpenRegister RBAC |
| B-02 | Audit trail | Via platform | OpenRegister mutatie-historie |
| B-03 | BIO-compliance | Via platform | Nextcloud BIO |
| B-04 | 2FA | Via platform | Nextcloud 2FA |
| B-05 | SSO / SAML / LDAP | Via platform | Nextcloud SSO |
| B-06 | Versleuteling (rust + transit) | Via platform | Nextcloud encryption + TLS |

---

## 4. Privacyeisen (AVG/GDPR)

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| P-01 | GDPR-anonimisering | Beschikbaar | PII-redactie in documenten |
| P-02 | Publicatie-instemming (WOO) | Beschikbaar | Bezwaartermijnen en -processen |
| P-03 | Recht op vergetelheid | Beschikbaar | Documenten anonimiseren of verwijderen |
| P-04 | Data minimalisatie | Beschikbaar | Alleen noodzakelijke metadata |

---

## 5. Toegankelijkheidseisen

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| A-01 | WCAG 2.1 AA | Beschikbaar | Nextcloud-componenten + eigen WCAG-checks |
| A-02 | EN 301 549 | Beschikbaar | Via WCAG AA |
| A-03 | Toetsenbordnavigatie | Beschikbaar | Volledig navigeerbaar |
| A-04 | NL Design System | Beschikbaar | Via NL Design app |
| A-05 | Meertalig (NL/EN) | Beschikbaar | Volledige vertaling |
| A-06 | WCAG-compliance checking van documenten | Beschikbaar | Controle op toegankelijkheid van gegenereerde documenten |

---

## 6. Integratiestandaarden

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| I-01 | Common Ground architectuur | Beschikbaar | Documententlaag bovenop OpenRegister |
| I-02 | Wet Open Overheid (WOO) | Beschikbaar | Publicatie-instemmingsproces |
| I-03 | OpenRegister event-integratie | Beschikbaar | Automatische documentverrijking |
| I-04 | CI/CD kwaliteitscontroles | Beschikbaar | Geautomatiseerde checks in pipelines |

---

## 7. Archivering

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| AR-01 | Documentversiebeheer | Via platform | Nextcloud Files versiebeheer |
| AR-02 | Metadata voor archivering | Beschikbaar | Automatische metadata-verrijking |
| AR-03 | Bevriezing van gepubliceerde documenten | Beschikbaar | Na publicatie niet meer wijzigbaar |
| AR-04 | TMLO/MDTO-metadata | Via platform | Via OpenRegister |

---

## 8. Beheer en onderhoud

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| BO-01 | Nextcloud App Store | Beschikbaar | Installatie via App Store |
| BO-02 | Automatische updates | Beschikbaar | Via Nextcloud app-updater |
| BO-03 | Beheerderspaneel | Beschikbaar | Nextcloud admin settings |
| BO-04 | Documentatie | Beschikbaar | GitBook docs |
| BO-05 | Open source community | Beschikbaar | GitHub Issues |
| BO-06 | Professionele ondersteuning (SLA) | Op aanvraag | Via Conduction B.V. |

---

## 9. Onderscheidende kenmerken

| Kenmerk | Toelichting |
|---------|-------------|
| **WOO-compliance ingebouwd** | Publicatie-instemming met wettelijke bezwaartermijnen |
| **GDPR-anonimisering** | PII automatisch redacteren uit documenten |
| **AI-verrijking** | Taaldetectie, trefwoorden, classificatie automatisch |
| **Nextcloud-native** | Werkt direct met Nextcloud Files, geen apart DMS |
| **Event-driven** | Automatische verrijking bij creatie van documenten |

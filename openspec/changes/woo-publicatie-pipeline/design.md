# Design: WOO Publicatie Pipeline

## Architecture Overview

The WOO pipeline consists of five interrelated schemas managed via OpenRegister, coordinated through a state-machine workflow:

1. **WooCategory** — lookup table defining the 17 WOO information categories with legal references, deadline rules, and DIWOO metadata requirements
2. **WooPublication** — core entity tracking a document's publication lifecycle from intake through PLOOI submission and bezwaar handling
3. **WooAnonymisationCheck** — structured audit record of anonymisation scanning, findings, and reviewer approval
4. **WooExemption** — exemption decisions (uitzonderingsgrond) per WOO art. 5.1/5.2 with weighing rationale (belangenafweging)
5. **WooBezwaar** — bezwaar workflow tracking objections, legal deadlines, and decisions

### Integration Points

- **Intake**: Document lifecycle action triggers publication initiation; category is auto-suggested via classifier and confirmed by WOO-coordinator
- **Anonymisation**: Uses `docudesk/anonymization` for detection and redaction; stores findings in `WooAnonymisationCheck`
- **Reviewer Gate**: Requires approval from `docudesk/anonymization-entity-review` before submission to PLOOI
- **Submission**: OpenConnector handles PLOOI API submission with mTLS, retries (exponential backoff), and async status polling
- **Publication**: PLOOI returns a `koopReference` and eventually a `publishedUrl` (open.overheid.nl link)
- **Retention**: Published documents link to retention via `docudesk/archiefwet-retention-engine`; cannot be destroyed without formal de-publication
- **Catalog**: Published `WooPublication` records appear in `opencatalogi` so the organisation's internal catalog mirrors the public DIWOO sitemap
- **Reporting**: Annual WOO report aggregates per-category counts, average latency, and bezwaar outcomes for IOBJ submission

### State Transitions

```
WooPublication.publicationStatus transitions:
  draft -> queued  (reviewer approved; ready for PLOOI submission)
  queued -> submitted (PLOOI intake API called; awaiting acceptance)
  submitted -> accepted (PLOOI confirmed receipt; publication scheduled)
  accepted -> live (PLOOI confirms public; open.overheid.nl live)
  submitted -> rejected (PLOOI rejection; rejection reasons parsed for coordinator)
  queued -> withdrawn (coordinator/bezwaar decision; tombstone sent to PLOOI)
  live -> bezwaar-pending (bezwaar received; decision deadline tracked)
  bezwaar-pending -> live (bezwaar ongegrond; publication unaffected)
  bezwaar-pending -> withdrawn (bezwaar gegrond; withdrawal or re-redaction)
```

## Seed Data

### WooCategory (all 17 categories, WOO art. 3.3)

```json
[
  {
    "id": "woo-cat-01",
    "code": "1",
    "wettelijkeGrondslag": "WOO art. 3.3 sub a",
    "titleNl": "Covenanten",
    "descriptionNl": "Overeenkomsten tussen overheden of met externe partijen met bindende afspraken",
    "publishWithinDays": 30,
    "publicationFrequency": "continuous",
    "checklistItems": [
      "Titel en datum ondertekening",
      "Partijen identified",
      "Scope and duration"
    ],
    "koopMetadataMapping": {
      "dct:title": "required",
      "dct:issued": "required",
      "dcatap:startDate": "optional"
    }
  },
  {
    "id": "woo-cat-05",
    "code": "5",
    "wettelijkeGrondslag": "WOO art. 3.3 sub e",
    "titleNl": "Raadsstukken",
    "descriptionNl": "Agenda's, stukken en verslagen van raadsvergaderingen",
    "publishWithinDays": 7,
    "publicationFrequency": "continuous",
    "checklistItems": [
      "Vergaderdatum",
      "Agendapunt identificatie",
      "Deelnemers",
      "Openbare/besloten status"
    ],
    "koopMetadataMapping": {
      "dct:title": "required",
      "dcterms:issued": "required",
      "dcat:eventDate": "required",
      "dcat:agendaItem": "required"
    }
  },
  {
    "id": "woo-cat-17",
    "code": "17",
    "wettelijkeGrondslag": "WOO art. 3.3 sub q",
    "titleNl": "Overige informatie van openbaar belang",
    "descriptionNl": "Informatie van openbaar belang die niet in de voorgaande categorieën past",
    "publishWithinDays": 60,
    "publicationFrequency": "quarterly",
    "checklistItems": [
      "Onderwerp",
      "Relevantie voor openbaar belang",
      "Organisatorische eenheid"
    ],
    "koopMetadataMapping": {
      "dct:title": "required",
      "dct:issued": "required",
      "dcat:theme": "optional"
    }
  }
]
```

### WooPublication (example publication lifecycle)

```json
[
  {
    "id": "pub-2026-001",
    "documentId": "doc-abc123",
    "documentVersion": 2,
    "wooCategory": "woo-cat-05",
    "title": "Raadsagenda 18 mei 2026, gemeenteraad Haarlem",
    "publicationDate": "2026-05-18",
    "publishedAt": "2026-05-19T10:30:00Z",
    "publicationStatus": "live",
    "publisherOrganisation": "gemeente-haarlem",
    "publicationOfficer": "user-griffier-001",
    "koopReference": "KOOP-2026-45782",
    "publishedUrl": "https://open.overheid.nl/dataset/KOOP-2026-45782",
    "retentionLinkedTo": "ret-policy-2026",
    "exemptionsApplied": [],
    "summary": "Openbare agenda en stukken voor raadsvergadering d.d. 18 mei 2026",
    "languageTag": "nl"
  },
  {
    "id": "pub-2026-002",
    "documentId": "doc-def456",
    "documentVersion": 1,
    "wooCategory": "woo-cat-06",
    "title": "Bestuurlijk besluit inzake aanbesteding IT-diensten 2026-2028",
    "publicationDate": "2026-05-15",
    "publishedAt": null,
    "publicationStatus": "submitted",
    "publisherOrganisation": "gemeente-haarlem",
    "publicationOfficer": "user-manager-002",
    "koopReference": null,
    "publishedUrl": null,
    "retentionLinkedTo": "ret-policy-2026",
    "exemptionsApplied": ["ex-2026-001"],
    "summary": "Bestuursbesluit aanbesteding leverancier cloud-services",
    "languageTag": "nl"
  },
  {
    "id": "pub-2026-003",
    "documentId": "doc-ghi789",
    "documentVersion": 1,
    "wooCategory": "woo-cat-04",
    "title": "Jaarplan 2025 Milieu & Ruimte afdeling",
    "publicationDate": "2025-02-01",
    "publishedAt": "2025-02-03T14:22:00Z",
    "publicationStatus": "bezwaar-pending",
    "publisherOrganisation": "gemeente-haarlem",
    "publicationOfficer": "user-manager-003",
    "koopReference": "KOOP-2025-28391",
    "publishedUrl": "https://open.overheid.nl/dataset/KOOP-2025-28391",
    "retentionLinkedTo": "ret-policy-2025",
    "exemptionsApplied": [],
    "summary": "Jaarplan 2025 beleid milieu- en ruimtelijke ordening",
    "languageTag": "nl"
  }
]
```

### WooAnonymisationCheck (example findings)

```json
[
  {
    "id": "check-2026-001",
    "publicationId": "pub-2026-001",
    "runAt": "2026-05-18T09:15:00Z",
    "runBy": "user-system",
    "ruleSetVersion": "nl-govt-2026-05",
    "findings": [
      {
        "ruleId": "BSN-DETECT",
        "locationRef": "page:1,line:12",
        "snippet": "123.45.678",
        "severity": "critical",
        "action": "redact"
      },
      {
        "ruleId": "EMAIL-DETECT",
        "locationRef": "page:2,line:5",
        "snippet": "j.smith@example.com",
        "severity": "medium",
        "action": "redact"
      }
    ],
    "reviewedBy": "user-div-001",
    "reviewedAt": "2026-05-18T10:00:00Z",
    "approvedRedactionPdfRef": "file-redacted-pub-2026-001",
    "hashBefore": "sha256:abc123...",
    "hashAfter": "sha256:def456..."
  }
]
```

### WooExemption (example exemption decision)

```json
[
  {
    "id": "ex-2026-001",
    "publicationId": "pub-2026-002",
    "exemptionArticle": "WOO art. 5.1.b",
    "exemptionScope": "partial-page",
    "justification": "Pagina 3 bevat bedrijfsgevoelige informatie over leveranciersprijzen",
    "weighingTest": "Het belang van geheimhouding van bedrijfsgeheimen weegt zwaarder dan het belang van openbaarheid voor deze specifieke prijsstelling. Algemene informatie over de aanbesteding is openbaar, maar competitieve details zijn witgehouden.",
    "decisionBy": "user-juridisch-001",
    "decisionDate": "2026-05-16",
    "expiresAt": null
  }
]
```

### WooBezwaar (example objection workflow)

```json
[
  {
    "id": "bezwaar-2026-001",
    "publicationId": "pub-2026-003",
    "bezwaarmaker": "Stichting Milieuzorg Haarlem",
    "bezwaarType": "wrong-redaction",
    "submittedAt": "2026-05-20T16:30:00Z",
    "deadlineAt": "2026-07-01T23:59:59Z",
    "assignedTo": "user-juridisch-002",
    "status": "in-review",
    "decisionAt": null,
    "decisionDocument": null,
    "beroepCaseRef": null
  }
]
```

## Data Flow

1. **Document → Publication Action** — User marks document "publish-WOO"
2. **Category Suggestion** — Classifier suggests WOO category; coordinator confirms or overrides with justification
3. **Anonymisation Check** — Pipeline runs detector against document; findings stored in `WooAnonymisationCheck`
4. **Redaction Review** — Reviewer approves redaction via `anonymization-entity-review`; redacted PDF+metadata generated
5. **PLOOI Submission** — OpenConnector submits PDF/A-2 + DIWOO-XML to PLOOI with mTLS
6. **Status Polling** — Pipeline polls PLOOI for acceptance; on success, updates `koopReference` and `publishedUrl`
7. **Publication Live** — Citizens can access via open.overheid.nl; audit trail complete
8. **Bezwaar Received** — If objection arrives within legal term, transitions to `bezwaar-pending`
9. **Bezwaar Decision** — Legal team decides (gegrond/ongegrond/ingetrokken); if withdrawal, tombstone sent to PLOOI
10. **Retention Linked** — Published document cannot be destroyed without formal de-publication

## Entity Relationships

```
WooPublication
  ├─ wooCategory → WooCategory
  ├─ exemptionsApplied[] → WooExemption[]
  ├─ documentId → docudesk/Document
  └─ retentionLinkedTo → archiefwet-retention-engine/RetentionPolicy

WooExemption
  └─ publicationId → WooPublication

WooAnonymisationCheck
  └─ publicationId → WooPublication

WooBezwaar
  └─ publicationId → WooPublication
```

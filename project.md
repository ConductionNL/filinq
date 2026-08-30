# Filinq -- GDPR-Compliant Document Processing for Nextcloud

## Overview

Filinq is a Nextcloud app for GDPR-compliant document processing, publication consent management, and automatic metadata enrichment. It enables organizations to anonymize sensitive documents, track publication consent under the Wet Open Overheid (WOO), and enrich document metadata -- all processed 100% locally with no external cloud dependencies. Filinq integrates tightly with OpenRegister via events to enrich documents as they are created and updated.

## Architecture

- **Type**: Nextcloud App (PHP backend + Vue 2 frontend)
- **Data layer**: OpenRegister (consent objects stored as register objects; files stored in Nextcloud filesystem)
- **Pattern**: Service-oriented -- Filinq orchestrates OpenRegister's TextExtractionService, FileService, EntityRelationMapper, and ObjectService
- **License**: EUPL-1.2
- **Event-driven**: Listens to OpenRegister ObjectCreated/Updated/Deleted events for automatic metadata enrichment

## Standards

| Layer | Standard | Purpose |
|-------|----------|---------|
| **Compliance** | GDPR / AVG | Privacy-by-design data processing |
| **Legal** | Wet Open Overheid (WOO) | Publication objection periods (min. 4 weeks) |
| **Processing** | 100% local | No external cloud for document processing |
| **Data** | OpenRegister ObjectService | Consent record storage via JSON object storage |
| **Entity Recognition** | OpenRegister TextExtractionService | Entity detection (Presidio, OpenAnonymiser, hybrid) |
| **Nextcloud** | Dashboard widgets, Admin settings | Native Nextcloud integration |

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.0+, Nextcloud App Framework |
| Frontend | Vue 2.7, Pinia, @nextcloud/vue |
| Data (Consent) | OpenRegister ObjectService (JSON object storage) |
| Data (Files) | Nextcloud IRootFolder (user files in Filinq/ folder) |
| Entity Recognition | OpenRegister TextExtractionService + EntityRelationMapper |
| Anonymization | OpenRegister FileService (anonymizeDocument) |
| Build | Webpack 5, @nextcloud/webpack-vue-config |
| Documentation | Docusaurus (in /website/) |

## Features

### Implemented

| Feature | Description | Status |
|---------|-------------|--------|
| Anonymization Pipeline | Upload -> extract text/entities -> anonymize document (3-step pipeline) | Done |
| Processed File Listing | List files in Filinq folder with entity counts, risk levels, and status | Done |
| Consent Management | GDPR publication consent tracking with configurable objection periods | Done |
| Consent Detail View | View and update consent status, notification status, publication decision | Done |
| Metadata Enrichment | Language detection, keyword extraction, topic classification | Done |
| Event-Driven Enrichment | Auto-enrich metadata on OpenRegister ObjectCreated/Updated events | Done |
| Admin Settings | OpenRegister register/schema configuration, consent period, enrichment toggles | Done |
| Dashboard | Consent stats cards, recent activity list, quick anonymization widget | Done |
| Dashboard Widgets | AnonymizationWidget and FileEntitiesWidget for Nextcloud Dashboard | Done |
| Risk Level Assessment | Risk level evaluation per file via OpenRegister RiskLevelService | Done |
| OCR Document Scanning | Tesseract OCR for scanned documents and images, integrated into anonymization pipeline | Done |

### Planned

| Feature | Description | Priority |
|---------|-------------|----------|
| Batch Anonymization | Process multiple files in a single pipeline run | SHOULD |
| Notification System | Send actual notifications to entities for consent | SHOULD |
| Consent Audit Trail | Full audit log of consent status changes | SHOULD |
| Document Preview | Preview original vs anonymized document side-by-side | COULD |

## Key Directories

```
filinq/
├── appinfo/              # App manifest (info.xml) and routes
├── lib/
│   ├── AppInfo/          # Application bootstrap (event listeners, widgets)
│   ├── Controller/       # API controllers (Anonymization, Consent, Metadata, Settings, Dashboard)
│   ├── Dashboard/        # Nextcloud Dashboard widgets
│   ├── EventListener/    # OpenRegister event listener
│   ├── Sections/         # Admin settings section
│   ├── Service/          # Business logic (AnonymizationService, ConsentService, MetadataService, SettingsService)
│   └── Settings/         # Admin settings page + filinq_register.json
├── src/
│   ├── components/       # Shared Vue components
│   ├── navigation/       # MainMenu navigation
│   ├── store/modules/    # Pinia stores (consent, anonymization)
│   ├── views/            # Page views (dashboard, anonymization, consent, settings)
│   └── entities/         # Entity definitions
├── openspec/             # OpenSpec specs and changes
├── templates/            # PHP templates
└── website/              # Docusaurus documentation site
```

## Development

- **Local URL**: http://localhost:8080/apps/filinq/
- **Requires**: OpenRegister app installed and enabled (>= v0.2.10)
- **Docker**: Part of openregister/docker-compose.yml
- **Config JSON**: `lib/Settings/filinq_register.json` defines the Consent Register + PublicationConsent schema
